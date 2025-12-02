<?php

declare(strict_types=1);

namespace Powerline\PrescripcionModule\Model\Data;

use Powerline\PrescripcionModule\Api\Data\PriceBreakdownDtoInterface;

/**
 * Price Breakdown DTO implementation
 */
class PriceBreakdownDto implements PriceBreakdownDtoInterface
{
    /**
     * @param float $framePrice
     * @param float $baseLensPrice
     * @param float $sphereSurcharge
     * @param float $cylinderSurcharge
     * @param float $additionSurcharge
     * @param float $prismSurcharge
     * @param float $treatmentsTotal
     * @param array $treatmentsBreakdown
     * @param float $extrasTotal
     * @param float $subtotal
     * @param float $taxAmount
     * @param float $grandTotal
     * @param string $currencyCode
     */
    public function __construct(
        private float $framePrice = 0.0,
        private float $baseLensPrice = 0.0,
        private float $sphereSurcharge = 0.0,
        private float $cylinderSurcharge = 0.0,
        private float $additionSurcharge = 0.0,
        private float $prismSurcharge = 0.0,
        private float $treatmentsTotal = 0.0,
        private array $treatmentsBreakdown = [],
        private float $extrasTotal = 0.0,
        private float $subtotal = 0.0,
        private float $taxAmount = 0.0,
        private float $grandTotal = 0.0,
        private string $currencyCode = 'EUR'
    ) {
    }

    public function getFramePrice(): float
    {
        return $this->framePrice;
    }

    public function setFramePrice(float $price): self
    {
        $this->framePrice = $price;
        return $this;
    }

    public function getBaseLensPrice(): float
    {
        return $this->baseLensPrice;
    }

    public function setBaseLensPrice(float $price): self
    {
        $this->baseLensPrice = $price;
        return $this;
    }

    public function getSphereSurcharge(): float
    {
        return $this->sphereSurcharge;
    }

    public function setSphereSurcharge(float $surcharge): self
    {
        $this->sphereSurcharge = $surcharge;
        return $this;
    }

    public function getCylinderSurcharge(): float
    {
        return $this->cylinderSurcharge;
    }

    public function setCylinderSurcharge(float $surcharge): self
    {
        $this->cylinderSurcharge = $surcharge;
        return $this;
    }

    public function getAdditionSurcharge(): float
    {
        return $this->additionSurcharge;
    }

    public function setAdditionSurcharge(float $surcharge): self
    {
        $this->additionSurcharge = $surcharge;
        return $this;
    }

    public function getPrismSurcharge(): float
    {
        return $this->prismSurcharge;
    }

    public function setPrismSurcharge(float $surcharge): self
    {
        $this->prismSurcharge = $surcharge;
        return $this;
    }

    public function getTreatmentsTotal(): float
    {
        return $this->treatmentsTotal;
    }

    public function setTreatmentsTotal(float $total): self
    {
        $this->treatmentsTotal = $total;
        return $this;
    }

    public function getTreatmentsBreakdown(): array
    {
        return $this->treatmentsBreakdown;
    }

    public function setTreatmentsBreakdown(array $breakdown): self
    {
        $this->treatmentsBreakdown = $breakdown;
        return $this;
    }

    public function getExtrasTotal(): float
    {
        return $this->extrasTotal;
    }

    public function setExtrasTotal(float $total): self
    {
        $this->extrasTotal = $total;
        return $this;
    }

    public function getSubtotal(): float
    {
        return $this->subtotal;
    }

    public function setSubtotal(float $subtotal): self
    {
        $this->subtotal = $subtotal;
        return $this;
    }

    public function getTaxAmount(): float
    {
        return $this->taxAmount;
    }

    public function setTaxAmount(float $amount): self
    {
        $this->taxAmount = $amount;
        return $this;
    }

    public function getGrandTotal(): float
    {
        return $this->grandTotal;
    }

    public function setGrandTotal(float $total): self
    {
        $this->grandTotal = $total;
        return $this;
    }

    public function getCurrencyCode(): string
    {
        return $this->currencyCode;
    }

    public function setCurrencyCode(string $code): self
    {
        $this->currencyCode = $code;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'frame_price' => $this->framePrice,
            'base_lens_price' => $this->baseLensPrice,
            'sphere_surcharge' => $this->sphereSurcharge,
            'cylinder_surcharge' => $this->cylinderSurcharge,
            'addition_surcharge' => $this->additionSurcharge,
            'prism_surcharge' => $this->prismSurcharge,
            'treatments_total' => $this->treatmentsTotal,
            'treatments_breakdown' => $this->treatmentsBreakdown,
            'extras_total' => $this->extrasTotal,
            'subtotal' => $this->subtotal,
            'tax_amount' => $this->taxAmount,
            'grand_total' => $this->grandTotal,
            'currency_code' => $this->currencyCode,
        ];
    }
}
