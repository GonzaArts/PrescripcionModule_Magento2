<?php

declare(strict_types=1);

namespace Powerline\PrescripcionModule\Model;

use Magento\Framework\Model\AbstractModel;
use Powerline\PrescripcionModule\Model\ResourceModel\Treatment as TreatmentResource;

/**
 * Treatment Model
 *
 * Represents a lens treatment option
 */
class Treatment extends AbstractModel
{
    /**
     * Cache tag
     */
    public const CACHE_TAG = 'powerline_presc_treatment';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * @var string
     */
    protected $_eventPrefix = 'powerline_presc_treatment';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(TreatmentResource::class);
    }

    /**
     * Get identities
     *
     * @return array
     */
    public function getIdentities(): array
    {
        return [self::CACHE_TAG . '_' . $this->getId()];
    }

    /**
     * Get ID
     *
     * @return int|null
     */
    public function getId(): ?int
    {
        return $this->getData('id') ? (int)$this->getData('id') : null;
    }

    /**
     * Get treatment code
     *
     * @return string
     */
    public function getCode(): string
    {
        return (string)$this->getData('code');
    }

    /**
     * Get treatment name
     *
     * @return string
     */
    public function getName(): string
    {
        return (string)$this->getData('name');
    }

    /**
     * Get description
     *
     * @return string|null
     */
    public function getDescription(): ?string
    {
        return $this->getData('description');
    }

    /**
     * Get category
     *
     * @return string
     */
    public function getCategory(): string
    {
        return (string)$this->getData('category');
    }

    /**
     * Get base price
     *
     * @return float
     */
    public function getBasePrice(): float
    {
        return (float)$this->getData('base_price');
    }

    /**
     * Get compatible materials
     *
     * @return array
     */
    public function getCompatibleMaterials(): array
    {
        $materials = $this->getData('compatible_materials');
        if (is_string($materials)) {
            return json_decode($materials, true) ?? [];
        }
        return is_array($materials) ? $materials : [];
    }

    /**
     * Get compatible indexes
     *
     * @return array
     */
    public function getCompatibleIndexes(): array
    {
        $indexes = $this->getData('compatible_indexes');
        if (is_string($indexes)) {
            return json_decode($indexes, true) ?? [];
        }
        return is_array($indexes) ? $indexes : [];
    }

    /**
     * Get incompatible treatments
     *
     * @return array
     */
    public function getIncompatibleTreatments(): array
    {
        $treatments = $this->getData('incompatible_treatments');
        if (is_string($treatments)) {
            return json_decode($treatments, true) ?? [];
        }
        return is_array($treatments) ? $treatments : [];
    }

    /**
     * Check if active
     *
     * @return bool
     */
    public function isActive(): bool
    {
        return (bool)$this->getData('is_active');
    }

    /**
     * Get sort order
     *
     * @return int
     */
    public function getSortOrder(): int
    {
        return (int)$this->getData('sort_order');
    }

    /**
     * Check if treatment is compatible with material
     *
     * @param string $material
     * @return bool
     */
    public function isCompatibleWithMaterial(string $material): bool
    {
        $compatibleMaterials = $this->getCompatibleMaterials();
        return empty($compatibleMaterials) || in_array($material, $compatibleMaterials, true);
    }

    /**
     * Check if treatment is compatible with index
     *
     * @param string $index
     * @return bool
     */
    public function isCompatibleWithIndex(string $index): bool
    {
        $compatibleIndexes = $this->getCompatibleIndexes();
        return empty($compatibleIndexes) || in_array($index, $compatibleIndexes, true);
    }

    /**
     * Check if treatment is incompatible with another treatment
     *
     * @param string $treatmentCode
     * @return bool
     */
    public function isIncompatibleWith(string $treatmentCode): bool
    {
        return in_array($treatmentCode, $this->getIncompatibleTreatments(), true);
    }
}
