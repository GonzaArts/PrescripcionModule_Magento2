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
 * Prescription Configuration DTO Interface
 *
 * @api
 */
interface ConfigDtoInterface
{
    /**
     * Get product ID
     *
     * @return int
     */
    public function getProductId(): int;

    /**
     * Set product ID
     *
     * @param int $productId
     * @return $this
     */
    public function setProductId(int $productId): self;

    /**
     * Get use type (monofocal, progressive, occupational, etc.)
     *
     * @return string
     */
    public function getUseType(): string;

    /**
     * Set use type
     *
     * @param string $useType
     * @return $this
     */
    public function setUseType(string $useType): self;

    /**
     * Get prescription data (OD/OI values)
     *
     * @return array
     */
    public function getPrescriptionData(): array;

    /**
     * Set prescription data
     *
     * @param array $prescriptionData
     * @return $this
     */
    public function setPrescriptionData(array $prescriptionData): self;

    /**
     * Get lens material
     *
     * @return string|null
     */
    public function getLensMaterial(): ?string;

    /**
     * Set lens material
     *
     * @param string|null $material
     * @return $this
     */
    public function setLensMaterial(?string $material): self;

    /**
     * Get lens design
     *
     * @return string|null
     */
    public function getLensDesign(): ?string;

    /**
     * Set lens design
     *
     * @param string|null $design
     * @return $this
     */
    public function setLensDesign(?string $design): self;

    /**
     * Get lens index
     *
     * @return string|null
     */
    public function getLensIndex(): ?string;

    /**
     * Set lens index
     *
     * @param string|null $index
     * @return $this
     */
    public function setLensIndex(?string $index): self;

    /**
     * Get selected treatments
     *
     * @return array
     */
    public function getTreatments(): array;

    /**
     * Set selected treatments
     *
     * @param array $treatments
     * @return $this
     */
    public function setTreatments(array $treatments): self;

    /**
     * Get extras
     *
     * @return array
     */
    public function getExtras(): array;

    /**
     * Set extras
     *
     * @param array $extras
     * @return $this
     */
    public function setExtras(array $extras): self;

    /**
     * Get attachment ID (uploaded prescription)
     *
     * @return int|null
     */
    public function getAttachmentId(): ?int;

    /**
     * Set attachment ID
     *
     * @param int|null $attachmentId
     * @return $this
     */
    public function setAttachmentId(?int $attachmentId): self;

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray(): array;
}
