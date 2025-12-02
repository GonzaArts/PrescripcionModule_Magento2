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

namespace Powerline\PrescripcionModule\Api\Data;

/**
 * Price Breakdown DTO Interface
 *
 * @api
 */
interface PriceBreakdownDtoInterface
{
    /**
     * Get frame price
     *
     * @return float
     */
    public function getFramePrice(): float;

    /**
     * Set frame price
     *
     * @param float $price
     * @return $this
     */
    public function setFramePrice(float $price): self;

    /**
     * Get base lens price
     *
     * @return float
     */
    public function getBaseLensPrice(): float;

    /**
     * Set base lens price
     *
     * @param float $price
     * @return $this
     */
    public function setBaseLensPrice(float $price): self;

    /**
     * Get sphere surcharge
     *
     * @return float
     */
    public function getSphereSurcharge(): float;

    /**
     * Set sphere surcharge
     *
     * @param float $surcharge
     * @return $this
     */
    public function setSphereSurcharge(float $surcharge): self;

    /**
     * Get cylinder surcharge
     *
     * @return float
     */
    public function getCylinderSurcharge(): float;

    /**
     * Set cylinder surcharge
     *
     * @param float $surcharge
     * @return $this
     */
    public function setCylinderSurcharge(float $surcharge): self;

    /**
     * Get addition surcharge
     *
     * @return float
     */
    public function getAdditionSurcharge(): float;

    /**
     * Set addition surcharge
     *
     * @param float $surcharge
     * @return $this
     */
    public function setAdditionSurcharge(float $surcharge): self;

    /**
     * Get prism surcharge
     *
     * @return float
     */
    public function getPrismSurcharge(): float;

    /**
     * Set prism surcharge
     *
     * @param float $surcharge
     * @return $this
     */
    public function setPrismSurcharge(float $surcharge): self;

    /**
     * Get treatments total
     *
     * @return float
     */
    public function getTreatmentsTotal(): float;

    /**
     * Set treatments total
     *
     * @param float $total
     * @return $this
     */
    public function setTreatmentsTotal(float $total): self;

    /**
     * Get treatments breakdown
     *
     * @return array
     */
    public function getTreatmentsBreakdown(): array;

    /**
     * Set treatments breakdown
     *
     * @param array $breakdown
     * @return $this
     */
    public function setTreatmentsBreakdown(array $breakdown): self;

    /**
     * Get extras total
     *
     * @return float
     */
    public function getExtrasTotal(): float;

    /**
     * Set extras total
     *
     * @param float $total
     * @return $this
     */
    public function setExtrasTotal(float $total): self;

    /**
     * Get subtotal (before rounding)
     *
     * @return float
     */
    public function getSubtotal(): float;

    /**
     * Set subtotal
     *
     * @param float $subtotal
     * @return $this
     */
    public function setSubtotal(float $subtotal): self;

    /**
     * Get tax amount
     *
     * @return float
     */
    public function getTaxAmount(): float;

    /**
     * Set tax amount
     *
     * @param float $taxAmount
     * @return $this
     */
    public function setTaxAmount(float $taxAmount): self;

    /**
     * Get grand total
     *
     * @return float
     */
    public function getGrandTotal(): float;

    /**
     * Set grand total
     *
     * @param float $grandTotal
     * @return $this
     */
    public function setGrandTotal(float $grandTotal): self;

    /**
     * Get currency code
     *
     * @return string
     */
    public function getCurrencyCode(): string;

    /**
     * Set currency code
     *
     * @param string $currencyCode
     * @return $this
     */
    public function setCurrencyCode(string $currencyCode): self;

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray(): array;
}
