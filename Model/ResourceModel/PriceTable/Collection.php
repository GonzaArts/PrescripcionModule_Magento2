<?php

declare(strict_types=1);

namespace Powerline\PrescripcionModule\Model\ResourceModel\PriceTable;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Powerline\PrescripcionModule\Model\PriceTable as PriceTableModel;
use Powerline\PrescripcionModule\Model\ResourceModel\PriceTable as PriceTableResource;

/**
 * PriceTable Collection
 */
class Collection extends AbstractCollection
{
    /**
     * @var string
     */
    protected $_idFieldName = 'id';

    /**
     * @var string
     */
    protected $_eventPrefix = 'powerline_presc_price_table_collection';

    /**
     * @var string
     */
    protected $_eventObject = 'price_table_collection';

    /**
     * Initialize collection model
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(PriceTableModel::class, PriceTableResource::class);
    }

    /**
     * Filter by active status
     *
     * @return $this
     */
    public function addActiveFilter(): self
    {
        $this->addFieldToFilter('is_active', 1);
        return $this;
    }

    /**
     * Filter by use type
     *
     * @param string $useType
     * @return $this
     */
    public function addUseTypeFilter(string $useType): self
    {
        $this->addFieldToFilter('use_type', $useType);
        return $this;
    }

    /**
     * Filter by material
     *
     * @param string $material
     * @return $this
     */
    public function addMaterialFilter(string $material): self
    {
        $this->addFieldToFilter('material', $material);
        return $this;
    }

    /**
     * Order by priority
     *
     * @param string $dir
     * @return $this
     */
    public function orderByPriority(string $dir = 'ASC'): self
    {
        $this->setOrder('priority', $dir);
        return $this;
    }
}
