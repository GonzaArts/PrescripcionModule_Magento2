<?php

declare(strict_types=1);

namespace Powerline\PrescripcionModule\Model;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Powerline\PrescripcionModule\Model\ResourceModel\PriceTable as PriceTableResource;
use Powerline\PrescripcionModule\Model\ResourceModel\PriceTable\CollectionFactory;

/**
 * PriceTable Repository
 */
class PriceTableRepository
{
    /**
     * @param PriceTableFactory $priceTableFactory
     * @param PriceTableResource $priceTableResource
     * @param CollectionFactory $collectionFactory
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     */
    public function __construct(
        private readonly PriceTableFactory $priceTableFactory,
        private readonly PriceTableResource $priceTableResource,
        private readonly CollectionFactory $collectionFactory,
        private readonly SearchResultsInterfaceFactory $searchResultsFactory
    ) {
    }

    /**
     * Save price table
     *
     * @param PriceTable $priceTable
     * @return PriceTable
     * @throws CouldNotSaveException
     */
    public function save(PriceTable $priceTable): PriceTable
    {
        try {
            $this->priceTableResource->save($priceTable);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not save the price table: %1', $exception->getMessage())
            );
        }
        return $priceTable;
    }

    /**
     * Get price table by ID
     *
     * @param int $id
     * @return PriceTable
     * @throws NoSuchEntityException
     */
    public function getById(int $id): PriceTable
    {
        $priceTable = $this->priceTableFactory->create();
        $this->priceTableResource->load($priceTable, $id);
        if (!$priceTable->getId()) {
            throw new NoSuchEntityException(__('Price table with id "%1" does not exist.', $id));
        }
        return $priceTable;
    }

    /**
     * Get list
     *
     * @param SearchCriteriaInterface $searchCriteria
     * @return SearchResultsInterface
     */
    public function getList(SearchCriteriaInterface $searchCriteria): SearchResultsInterface
    {
        $collection = $this->collectionFactory->create();

        foreach ($searchCriteria->getFilterGroups() as $filterGroup) {
            foreach ($filterGroup->getFilters() as $filter) {
                $condition = $filter->getConditionType() ?: 'eq';
                $collection->addFieldToFilter($filter->getField(), [$condition => $filter->getValue()]);
            }
        }

        $sortOrders = $searchCriteria->getSortOrders();
        if ($sortOrders) {
            foreach ($sortOrders as $sortOrder) {
                $collection->addOrder($sortOrder->getField(), $sortOrder->getDirection());
            }
        }

        $collection->setCurPage($searchCriteria->getCurrentPage());
        $collection->setPageSize($searchCriteria->getPageSize());

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($searchCriteria);
        $searchResults->setItems($collection->getItems());
        $searchResults->setTotalCount($collection->getSize());

        return $searchResults;
    }

    /**
     * Delete price table
     *
     * @param PriceTable $priceTable
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(PriceTable $priceTable): bool
    {
        try {
            $this->priceTableResource->delete($priceTable);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete the price table: %1', $exception->getMessage())
            );
        }
        return true;
    }

    /**
     * Delete price table by ID
     *
     * @param int $id
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $id): bool
    {
        return $this->delete($this->getById($id));
    }

    /**
     * Find matching price rules
     *
     * @param string $useType
     * @param string $material
     * @param string $design
     * @param string $index
     * @return PriceTable|null
     */
    public function findMatchingRule(
        string $useType,
        string $material,
        string $design,
        string $index
    ): ?PriceTable {
        $collection = $this->collectionFactory->create();
        $collection->addActiveFilter()
            ->addFieldToFilter('use_type', $useType)
            ->addFieldToFilter('material', $material)
            ->addFieldToFilter('design', $design)
            ->addFieldToFilter('index', $index)
            ->orderByPriority('DESC')
            ->setPageSize(1);

        return $collection->getFirstItem()->getId() ? $collection->getFirstItem() : null;
    }
}
