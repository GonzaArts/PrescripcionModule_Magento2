<?php

declare(strict_types=1);

namespace Powerline\PrescripcionModule\Service;

use Powerline\PrescripcionModule\Api\Data\ConfigDtoInterface;
use Powerline\PrescripcionModule\Api\Data\PriceBreakdownDtoInterface;
use Powerline\PrescripcionModule\Api\PricingServiceInterface;
use Powerline\PrescripcionModule\Model\Data\PriceBreakdownDto;
use Powerline\PrescripcionModule\Logger\Logger;
use Magento\Catalog\Api\ProductRepositoryInterface;

/**
 * Simplified Pricing Service Implementation
 */
class PricingService implements PricingServiceInterface
{
    /**
     * Base lens prices by use type
     */
    private const BASE_LENS_PRICES = [
        'monofocal' => 20.90,
        'progressive' => 86.90,
        'no_prescription' => 20.90,
    ];

    /**
     * @param ProductRepositoryInterface $productRepository
     * @param Logger $logger
     */
    public function __construct(
        private readonly ProductRepositoryInterface $productRepository,
        private readonly Logger $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function quote(ConfigDtoInterface $config): PriceBreakdownDtoInterface
    {
        $priceBreakdown = new PriceBreakdownDto();

        try {
            // Get frame price with tax
            $framePrice = 0.0;
            if ($config->getProductId()) {
                try {
                    $product = $this->productRepository->getById($config->getProductId());
                    $framePrice = (float) $product->getPriceInfo()
                        ->getPrice('final_price')
                        ->getAmount()
                        ->getValue();
                } catch (\Exception $e) {
                    // Silence error - use default 0.0
                }
            }

            // Get base lens price
            $baseLensPrice = self::BASE_LENS_PRICES[$config->getUseType()] ?? 0.0;

            // Calculate extras
            $extrasTotal = 0.0;
            if ($config->getExtras()['prism'] ?? false) {
                $extrasTotal = 50.0;
            }

            $grandTotal = $framePrice + $baseLensPrice + $extrasTotal;

            $priceBreakdown->setFramePrice($framePrice);
            $priceBreakdown->setBaseLensPrice($baseLensPrice);
            $priceBreakdown->setExtrasTotal($extrasTotal);
            $priceBreakdown->setGrandTotal($grandTotal);

        } catch (\Exception $e) {
            // Simplified logging - just set defaults
            $priceBreakdown->setGrandTotal(0.0);
        }

        return $priceBreakdown;
    }

    /**
     * @inheritDoc
     */
    public function getBaseLensPrice(string $material, string $design, array $prescription): float
    {
        // Simplified implementation - return 0 for now
        return 0.0;
    }

    /**
     * @inheritDoc
     */
    public function calculateRangeSurcharge(array $prescription, string $material, string $design): float
    {
        // Simplified implementation - return 0 for now
        return 0.0;
    }

    /**
     * @inheritDoc
     */
    public function calculateTreatmentsCost(array $treatments, float $basePrice): array
    {
        // Simplified implementation
        return ['total' => 0.0, 'breakdown' => []];
    }

    /**
     * @inheritDoc
     */
    public function applyRounding(float $amount): float
    {
        // Round to 2 decimals
        return round($amount, 2);
    }
}
