<?php
/**
 * Powerline PrescripcionModule
 *
 * @category  Powerline
 * @package   Powerline_PrescripcionModule
 * @author    Powerline Development Team
 * @copyright Copyright (c) 2025 Powerline
 * @license   Proprietary
 */

declare(strict_types=1);

namespace Powerline\PrescripcionModule\Api;

/**
 * Attachment Management Interface
 *
 * @api
 */
interface AttachmentManagementInterface
{
    /**
     * Upload prescription attachment
     *
     * @param string $fileContent Base64 encoded file content
     * @param string $filename Original filename
     * @param string $mimeType MIME type
     * @param int|null $quoteId Quote ID
     * @param int|null $quoteItemId Quote Item ID
     * @return array ['attachment_id' => int, 'hash' => string, 'url' => string]
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function upload(
        string $fileContent,
        string $filename,
        string $mimeType,
        ?int $quoteId = null,
        ?int $quoteItemId = null
    ): array;

    /**
     * Get attachment by ID
     *
     * @param int $attachmentId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAttachment(int $attachmentId): array;

    /**
     * Get download URL for attachment
     *
     * @param int $attachmentId
     * @param string $hash For security validation
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getDownloadUrl(int $attachmentId, string $hash): string;

    /**
     * Delete attachment
     *
     * @param int $attachmentId
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(int $attachmentId): bool;

    /**
     * Get attachments by quote ID
     *
     * @param int $quoteId
     * @return array
     */
    public function getByQuoteId(int $quoteId): array;

    /**
     * Get attachments by order ID
     *
     * @param int $orderId
     * @return array
     */
    public function getByOrderId(int $orderId): array;

    /**
     * Clean up expired attachments
     *
     * @return int Number of cleaned attachments
     */
    public function cleanupExpired(): int;

    /**
     * Validate file
     *
     * @param string $filename
     * @param string $mimeType
     * @param int $fileSize
     * @return bool
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function validateFile(string $filename, string $mimeType, int $fileSize): bool;
}
