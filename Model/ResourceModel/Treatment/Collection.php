<?php

declare(strict_types=1);

namespace Powerline\PrescripcionModule\Model\ResourceModel\Treatment;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Powerline\PrescripcionModule\Model\Treatment as TreatmentModel;
use Powerline\PrescripcionModule\Model\ResourceModel\Treatment as TreatmentResource;

/**
 * Treatment Collection
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
    protected $_eventPrefix = 'powerline_presc_treatment_collection';

    /**
     * @var string
     */
    protected $_eventObject = 'treatment_collection';

    /**
     * Initialize collection model
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(TreatmentModel::class, TreatmentResource::class);
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
     * Filter by category
     *
     * @param string $category
     * @return $this
     */
    public function addCategoryFilter(string $category): self
    {
        $this->addFieldToFilter('category', $category);
        return $this;
    }

    /**
     * Filter by code
     *
     * @param string $code
     * @return $this
     */
    public function addCodeFilter(string $code): self
    {
        $this->addFieldToFilter('code', $code);
        return $this;
    }

    /**
     * Order by sort order
     *
     * @param string $dir
     * @return $this
     */
    public function orderBySortOrder(string $dir = 'ASC'): self
    {
        $this->setOrder('sort_order', $dir);
        return $this;
    }
}
