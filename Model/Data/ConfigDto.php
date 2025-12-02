<?php

declare(strict_types=1);

namespace Powerline\PrescripcionModule\Model\Data;

use Powerline\PrescripcionModule\Api\Data\ConfigDtoInterface;

/**
 * Configuration DTO implementation
 */
class ConfigDto implements ConfigDtoInterface
{
    /**
     * @param int $productId
     * @param string $useType
     * @param array $prescriptionData
     * @param string|null $lensMaterial
     * @param string|null $lensDesign
     * @param string|null $lensIndex
     * @param array $treatments
     * @param array $extras
     * @param int|null $attachmentId
     */
    public function __construct(
        private int $productId = 0,
        private string $useType = '',
        private array $prescriptionData = [],
        private ?string $lensMaterial = null,
        private ?string $lensDesign = null,
        private ?string $lensIndex = null,
        private array $treatments = [],
        private array $extras = [],
        private ?int $attachmentId = null
    ) {
    }

    public function getProductId(): int
    {
        return $this->productId;
    }

    public function setProductId(int $productId): self
    {
        $this->productId = $productId;
        return $this;
    }

    public function getUseType(): string
    {
        return $this->useType;
    }

    public function setUseType(string $useType): self
    {
        $this->useType = $useType;
        return $this;
    }

    public function getPrescriptionData(): array
    {
        return $this->prescriptionData;
    }

    public function setPrescriptionData(array $data): self
    {
        $this->prescriptionData = $data;
        return $this;
    }

    public function getLensMaterial(): ?string
    {
        return $this->lensMaterial;
    }

    public function setLensMaterial(?string $material): self
    {
        $this->lensMaterial = $material;
        return $this;
    }

    public function getLensDesign(): ?string
    {
        return $this->lensDesign;
    }

    public function setLensDesign(?string $design): self
    {
        $this->lensDesign = $design;
        return $this;
    }

    public function getLensIndex(): ?string
    {
        return $this->lensIndex;
    }

    public function setLensIndex(?string $index): self
    {
        $this->lensIndex = $index;
        return $this;
    }

    public function getTreatments(): array
    {
        return $this->treatments;
    }

    public function setTreatments(array $treatments): self
    {
        $this->treatments = $treatments;
        return $this;
    }

    public function getExtras(): array
    {
        return $this->extras;
    }

    public function setExtras(array $extras): self
    {
        $this->extras = $extras;
        return $this;
    }

    public function getAttachmentId(): ?int
    {
        return $this->attachmentId;
    }

    public function setAttachmentId(?int $attachmentId): self
    {
        $this->attachmentId = $attachmentId;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'use_type' => $this->useType,
            'prescription_data' => $this->prescriptionData,
            'lens_material' => $this->lensMaterial,
            'lens_design' => $this->lensDesign,
            'lens_index' => $this->lensIndex,
            'treatments' => $this->treatments,
            'extras' => $this->extras,
            'attachment_id' => $this->attachmentId,
        ];
    }
}
