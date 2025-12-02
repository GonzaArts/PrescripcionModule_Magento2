<?php

declare(strict_types=1);

namespace Powerline\PrescripcionModule\Model;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Powerline\PrescripcionModule\Model\ResourceModel\Treatment as TreatmentResource;
use Powerline\PrescripcionModule\Model\ResourceModel\Treatment\CollectionFactory;

/**
 * Treatment Repository
 */
class TreatmentRepository
{
    /**
     * @param TreatmentFactory $treatmentFactory
     * @param TreatmentResource $treatmentResource
     * @param CollectionFactory $collectionFactory
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     */
    public function __construct(
        private readonly TreatmentFactory $treatmentFactory,
        private readonly TreatmentResource $treatmentResource,
        private readonly CollectionFactory $collectionFactory,
        private readonly SearchResultsInterfaceFactory $searchResultsFactory
    ) {
    }

    /**
     * Save treatment
     *
     * @param Treatment $treatment
     * @return Treatment
     * @throws CouldNotSaveException
     */
    public function save(Treatment $treatment): Treatment
    {
        try {
            $this->treatmentResource->save($treatment);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not save the treatment: %1', $exception->getMessage())
            );
        }
        return $treatment;
    }

    /**
     * Get treatment by ID
     *
     * @param int $id
     * @return Treatment
     * @throws NoSuchEntityException
     */
    public function getById(int $id): Treatment
    {
        $treatment = $this->treatmentFactory->create();
        $this->treatmentResource->load($treatment, $id);
        if (!$treatment->getId()) {
            throw new NoSuchEntityException(__('Treatment with id "%1" does not exist.', $id));
        }
        return $treatment;
    }

    /**
     * Get treatment by code
     *
     * @param string $code
     * @return Treatment
     * @throws NoSuchEntityException
     */
    public function getByCode(string $code): Treatment
    {
        $treatment = $this->treatmentFactory->create();
        $this->treatmentResource->load($treatment, $code, 'code');
        if (!$treatment->getId()) {
            throw new NoSuchEntityException(__('Treatment with code "%1" does not exist.', $code));
        }
        return $treatment;
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
     * Delete treatment
     *
     * @param Treatment $treatment
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(Treatment $treatment): bool
    {
        try {
            $this->treatmentResource->delete($treatment);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete the treatment: %1', $exception->getMessage())
            );
        }
        return true;
    }

    /**
     * Delete treatment by ID
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
     * Get all active treatments
     *
     * @return Treatment[]
     */
    public function getActiveTreatments(): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addActiveFilter()
            ->orderBySortOrder('ASC');

        return $collection->getItems();
    }

    /**
     * Get treatments by category
     *
     * @param string $category
     * @return Treatment[]
     */
    public function getTreatmentsByCategory(string $category): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addActiveFilter()
            ->addCategoryFilter($category)
            ->orderBySortOrder('ASC');

        return $collection->getItems();
    }
}
