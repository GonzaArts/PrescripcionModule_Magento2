<?php
/**
 * Powerline PrescripcionModule
 *
 * @category  Powerline
 * @package   Powerline_PrescripcionModule
 * @author    Powerline Development Team
 * @copyright Copyright (c) 2025 Powerline
 * @license   Proprietary
 */

declare(strict_types=1);

namespace Powerline\PrescripcionModule\Api;

use Powerline\PrescripcionModule\Api\Data\ConfigDtoInterface;
use Powerline\PrescripcionModule\Api\Data\PriceBreakdownDtoInterface;

/**
 * Pricing Service Interface
 *
 * @api
 */
interface PricingServiceInterface
{
    /**
     * Calculate price quote for prescription configuration
     *
     * @param ConfigDtoInterface $config
     * @return PriceBreakdownDtoInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function quote(ConfigDtoInterface $config): PriceBreakdownDtoInterface;

    /**
     * Get base lens price for material and design
     *
     * @param string $material
     * @param string $design
     * @param array $prescription
     * @return float
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getBaseLensPrice(string $material, string $design, array $prescription): float;

    /**
     * Calculate range surcharge (SPH, CYL, ADD, PRISM)
     *
     * @param array $prescription
     * @param string $material
     * @param string $design
     * @return float
     */
    public function calculateRangeSurcharge(array $prescription, string $material, string $design): float;

    /**
     * Calculate treatments cost
     *
     * @param array $treatments
     * @param float $basePrice
     * @return array ['total' => float, 'breakdown' => array]
     */
    public function calculateTreatmentsCost(array $treatments, float $basePrice): array;

    /**
     * Apply rounding rules
     *
     * @param float $amount
     * @return float
     */
    public function applyRounding(float $amount): float;
}
