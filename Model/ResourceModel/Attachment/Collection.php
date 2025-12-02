<?php

declare(strict_types=1);

namespace Powerline\PrescripcionModule\Model\ResourceModel\Attachment;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;
use Powerline\PrescripcionModule\Model\Attachment as AttachmentModel;
use Powerline\PrescripcionModule\Model\ResourceModel\Attachment as AttachmentResource;

/**
 * Attachment Collection
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
    protected $_eventPrefix = 'powerline_presc_attachment_collection';

    /**
     * @var string
     */
    protected $_eventObject = 'attachment_collection';

    /**
     * Initialize collection model
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(AttachmentModel::class, AttachmentResource::class);
    }

    /**
     * Filter by quote ID
     *
     * @param int $quoteId
     * @return $this
     */
    public function addQuoteIdFilter(int $quoteId): self
    {
        $this->addFieldToFilter('quote_id', $quoteId);
        return $this;
    }

    /**
     * Filter by order ID
     *
     * @param int $orderId
     * @return $this
     */
    public function addOrderIdFilter(int $orderId): self
    {
        $this->addFieldToFilter('order_id', $orderId);
        return $this;
    }

    /**
     * Filter by customer ID
     *
     * @param int $customerId
     * @return $this
     */
    public function addCustomerIdFilter(int $customerId): self
    {
        $this->addFieldToFilter('customer_id', $customerId);
        return $this;
    }

    /**
     * Filter expired attachments
     *
     * @return $this
     */
    public function addExpiredFilter(): self
    {
        $this->addFieldToFilter('expires_at', ['notnull' => true]);
        $this->addFieldToFilter('expires_at', ['lt' => date('Y-m-d H:i:s')]);
        return $this;
    }
}
