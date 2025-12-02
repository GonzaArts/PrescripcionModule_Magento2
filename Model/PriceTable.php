<?php

declare(strict_types=1);

namespace Powerline\PrescripcionModule\Model;

use Magento\Framework\Model\AbstractModel;
use Powerline\PrescripcionModule\Model\ResourceModel\PriceTable as PriceTableResource;

/**
 * PriceTable Model
 *
 * Represents a pricing rule for lens configurations
 */
class PriceTable extends AbstractModel
{
    /**
     * Cache tag
     */
    public const CACHE_TAG = 'powerline_presc_price_table';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * @var string
     */
    protected $_eventPrefix = 'powerline_presc_price_table';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(PriceTableResource::class);
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
     * Get use type
     *
     * @return string
     */
    public function getUseType(): string
    {
        return (string)$this->getData('use_type');
    }

    /**
     * Get material
     *
     * @return string
     */
    public function getMaterial(): string
    {
        return (string)$this->getData('material');
    }

    /**
     * Get design
     *
     * @return string
     */
    public function getDesign(): string
    {
        return (string)$this->getData('design');
    }

    /**
     * Get index value
     *
     * @return string
     */
    public function getIndex(): string
    {
        return (string)$this->getData('index');
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
     * Get sphere range min
     *
     * @return float|null
     */
    public function getSphRangeMin(): ?float
    {
        $value = $this->getData('sph_range_min');
        return $value !== null ? (float)$value : null;
    }

    /**
     * Get sphere range max
     *
     * @return float|null
     */
    public function getSphRangeMax(): ?float
    {
        $value = $this->getData('sph_range_max');
        return $value !== null ? (float)$value : null;
    }

    /**
     * Get sphere surcharge
     *
     * @return float
     */
    public function getSphSurcharge(): float
    {
        return (float)$this->getData('sph_surcharge');
    }

    /**
     * Get cylinder range min
     *
     * @return float|null
     */
    public function getCylRangeMin(): ?float
    {
        $value = $this->getData('cyl_range_min');
        return $value !== null ? (float)$value : null;
    }

    /**
     * Get cylinder range max
     *
     * @return float|null
     */
    public function getCylRangeMax(): ?float
    {
        $value = $this->getData('cyl_range_max');
        return $value !== null ? (float)$value : null;
    }

    /**
     * Get cylinder surcharge
     *
     * @return float
     */
    public function getCylSurcharge(): float
    {
        return (float)$this->getData('cyl_surcharge');
    }

    /**
     * Get addition range min
     *
     * @return float|null
     */
    public function getAddRangeMin(): ?float
    {
        $value = $this->getData('add_range_min');
        return $value !== null ? (float)$value : null;
    }

    /**
     * Get addition range max
     *
     * @return float|null
     */
    public function getAddRangeMax(): ?float
    {
        $value = $this->getData('add_range_max');
        return $value !== null ? (float)$value : null;
    }

    /**
     * Get addition surcharge
     *
     * @return float
     */
    public function getAddSurcharge(): float
    {
        return (float)$this->getData('add_surcharge');
    }

    /**
     * Get prism surcharge
     *
     * @return float
     */
    public function getPrismSurcharge(): float
    {
        return (float)$this->getData('prism_surcharge');
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
     * Get priority
     *
     * @return int
     */
    public function getPriority(): int
    {
        return (int)$this->getData('priority');
    }
}
