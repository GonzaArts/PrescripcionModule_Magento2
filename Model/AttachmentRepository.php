<?php

declare(strict_types=1);

namespace Powerline\PrescripcionModule\Model;

use Magento\Framework\Api\SearchCriteriaInterface;
use Magento\Framework\Api\SearchResultsInterface;
use Magento\Framework\Api\SearchResultsInterfaceFactory;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Powerline\PrescripcionModule\Api\AttachmentRepositoryInterface;
use Powerline\PrescripcionModule\Api\Data\AttachmentInterface;
use Powerline\PrescripcionModule\Model\ResourceModel\Attachment as AttachmentResource;
use Powerline\PrescripcionModule\Model\ResourceModel\Attachment\CollectionFactory;

/**
 * Attachment Repository
 */
class AttachmentRepository implements AttachmentRepositoryInterface
{
    /**
     * @param AttachmentFactory $attachmentFactory
     * @param AttachmentResource $attachmentResource
     * @param CollectionFactory $collectionFactory
     * @param SearchResultsInterfaceFactory $searchResultsFactory
     */
    public function __construct(
        private readonly AttachmentFactory $attachmentFactory,
        private readonly AttachmentResource $attachmentResource,
        private readonly CollectionFactory $collectionFactory,
        private readonly SearchResultsInterfaceFactory $searchResultsFactory
    ) {
    }

    /**
     * Save attachment
     *
     * @param AttachmentInterface $attachment
     * @return AttachmentInterface
     * @throws CouldNotSaveException
     */
    public function save(AttachmentInterface $attachment): AttachmentInterface
    {
        try {
            $this->attachmentResource->save($attachment);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __('Could not save the attachment: %1', $exception->getMessage())
            );
        }
        return $attachment;
    }

    /**
     * Get attachment by ID
     *
     * @param int $attachmentId
     * @return AttachmentInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $attachmentId): AttachmentInterface
    {
        $attachment = $this->attachmentFactory->create();
        $this->attachmentResource->load($attachment, $attachmentId);
        if (!$attachment->getId()) {
            throw new NoSuchEntityException(__('Attachment with id "%1" does not exist.', $attachmentId));
        }
        return $attachment;
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
     * Delete attachment
     *
     * @param AttachmentInterface $attachment
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(AttachmentInterface $attachment): bool
    {
        try {
            $this->attachmentResource->delete($attachment);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __('Could not delete the attachment: %1', $exception->getMessage())
            );
        }
        return true;
    }

    /**
     * Delete attachment by ID
     *
     * @param int $attachmentId
     * @return bool
     * @throws CouldNotDeleteException
     * @throws NoSuchEntityException
     */
    public function deleteById(int $attachmentId): bool
    {
        return $this->delete($this->getById($attachmentId));
    }

    /**
     * Get attachments by quote ID
     *
     * @param int $quoteId
     * @return Attachment[]
     */
    public function getByQuoteId(int $quoteId): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addQuoteIdFilter($quoteId);
        return $collection->getItems();
    }

    /**
     * Get attachment by order ID
     *
     * @param int $orderId
     * @return AttachmentInterface
     * @throws NoSuchEntityException
     */
    public function getByOrderId(int $orderId): AttachmentInterface
    {
        $collection = $this->collectionFactory->create();
        $collection->addOrderIdFilter($orderId);
        $attachment = $collection->getFirstItem();
        if (!$attachment->getId()) {
            throw new NoSuchEntityException(__('Attachment for order "%1" does not exist.', $orderId));
        }
        return $attachment;
    }

    /**
     * Get attachments by customer ID
     *
     * @param int $customerId
     * @return Attachment[]
     */
    public function getByCustomerId(int $customerId): array
    {
        $collection = $this->collectionFactory->create();
        $collection->addCustomerIdFilter($customerId);
        return $collection->getItems();
    }

    /**
     * Clean up expired attachments
     *
     * @return int Number of deleted attachments
     */
    public function cleanupExpired(): int
    {
        $collection = $this->collectionFactory->create();
        $collection->addExpiredFilter();

        $count = 0;
        foreach ($collection as $attachment) {
            try {
                $this->delete($attachment);
                $count++;
            } catch (\Exception $e) {
                // Continue with next attachment
                continue;
            }
        }

        return $count;
    }
}
