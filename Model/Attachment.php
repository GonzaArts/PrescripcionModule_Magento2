<?php

declare(strict_types=1);

namespace Powerline\PrescripcionModule\Model;

use Magento\Framework\Model\AbstractModel;
use Powerline\PrescripcionModule\Model\ResourceModel\Attachment as AttachmentResource;

/**
 * Attachment Model
 *
 * Represents an uploaded prescription file
 */
class Attachment extends AbstractModel
{
    /**
     * Cache tag
     */
    public const CACHE_TAG = 'powerline_presc_attachment';

    /**
     * @var string
     */
    protected $_cacheTag = self::CACHE_TAG;

    /**
     * @var string
     */
    protected $_eventPrefix = 'powerline_presc_attachment';

    /**
     * Initialize resource model
     *
     * @return void
     */
    protected function _construct(): void
    {
        $this->_init(AttachmentResource::class);
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
     * Get quote ID
     *
     * @return int|null
     */
    public function getQuoteId(): ?int
    {
        $value = $this->getData('quote_id');
        return $value !== null ? (int)$value : null;
    }

    /**
     * Get order ID
     *
     * @return int|null
     */
    public function getOrderId(): ?int
    {
        $value = $this->getData('order_id');
        return $value !== null ? (int)$value : null;
    }

    /**
     * Get customer ID
     *
     * @return int|null
     */
    public function getCustomerId(): ?int
    {
        $value = $this->getData('customer_id');
        return $value !== null ? (int)$value : null;
    }

    /**
     * Get filename
     *
     * @return string
     */
    public function getFilename(): string
    {
        return (string)$this->getData('filename');
    }

    /**
     * Get original filename
     *
     * @return string
     */
    public function getOriginalFilename(): string
    {
        return (string)$this->getData('original_filename');
    }

    /**
     * Get file path
     *
     * @return string
     */
    public function getFilePath(): string
    {
        return (string)$this->getData('file_path');
    }

    /**
     * Get file size
     *
     * @return int
     */
    public function getFileSize(): int
    {
        return (int)$this->getData('file_size');
    }

    /**
     * Get mime type
     *
     * @return string
     */
    public function getMimeType(): string
    {
        return (string)$this->getData('mime_type');
    }

    /**
     * Get uploaded at timestamp
     *
     * @return string
     */
    public function getUploadedAt(): string
    {
        return (string)$this->getData('uploaded_at');
    }

    /**
     * Get expires at timestamp
     *
     * @return string|null
     */
    public function getExpiresAt(): ?string
    {
        return $this->getData('expires_at');
    }

    /**
     * Check if expired
     *
     * @return bool
     */
    public function isExpired(): bool
    {
        $expiresAt = $this->getExpiresAt();
        if (!$expiresAt) {
            return false;
        }

        return strtotime($expiresAt) < time();
    }

    /**
     * Set quote ID
     *
     * @param int|null $quoteId
     * @return $this
     */
    public function setQuoteId(?int $quoteId): self
    {
        $this->setData('quote_id', $quoteId);
        return $this;
    }

    /**
     * Set order ID
     *
     * @param int|null $orderId
     * @return $this
     */
    public function setOrderId(?int $orderId): self
    {
        $this->setData('order_id', $orderId);
        return $this;
    }

    /**
     * Set expires at
     *
     * @param string|null $expiresAt
     * @return $this
     */
    public function setExpiresAt(?string $expiresAt): self
    {
        $this->setData('expires_at', $expiresAt);
        return $this;
    }
}
