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

use Powerline\PrescripcionModule\Api\Data\AttachmentInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Exception\CouldNotDeleteException;

/**
 * Attachment repository interface
 */
interface AttachmentRepositoryInterface
{
    /**
     * Save attachment
     *
     * @param AttachmentInterface $attachment
     * @return AttachmentInterface
     * @throws CouldNotSaveException
     */
    public function save(AttachmentInterface $attachment): AttachmentInterface;

    /**
     * Get attachment by ID
     *
     * @param int $attachmentId
     * @return AttachmentInterface
     * @throws NoSuchEntityException
     */
    public function getById(int $attachmentId): AttachmentInterface;

    /**
     * Get attachment by order ID
     *
     * @param int $orderId
     * @return AttachmentInterface
     * @throws NoSuchEntityException
     */
    public function getByOrderId(int $orderId): AttachmentInterface;

    /**
     * Delete attachment
     *
     * @param AttachmentInterface $attachment
     * @return bool
     * @throws CouldNotDeleteException
     */
    public function delete(AttachmentInterface $attachment): bool;

    /**
     * Delete attachment by ID
     *
     * @param int $attachmentId
     * @return bool
     * @throws NoSuchEntityException
     * @throws CouldNotDeleteException
     */
    public function deleteById(int $attachmentId): bool;
}
