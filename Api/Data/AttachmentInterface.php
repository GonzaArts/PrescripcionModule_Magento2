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

namespace Powerline\PrescripcionModule\Api\Data;

/**
 * Attachment interface
 */
interface AttachmentInterface
{
    public const ATTACHMENT_ID = 'attachment_id';
    public const ORDER_ID = 'order_id';
    public const FILE_PATH = 'file_path';
    public const FILE_NAME = 'file_name';
    public const FILE_TYPE = 'file_type';
    public const FILE_SIZE = 'file_size';
    public const CREATED_AT = 'created_at';

    /**
     * Get attachment ID
     *
     * @return int|null
     */
    public function getAttachmentId(): ?int;

    /**
     * Set attachment ID
     *
     * @param int $attachmentId
     * @return $this
     */
    public function setAttachmentId(int $attachmentId): self;

    /**
     * Get order ID
     *
     * @return int|null
     */
    public function getOrderId(): ?int;

    /**
     * Set order ID
     *
     * @param int $orderId
     * @return $this
     */
    public function setOrderId(int $orderId): self;

    /**
     * Get file path
     *
     * @return string|null
     */
    public function getFilePath(): ?string;

    /**
     * Set file path
     *
     * @param string $filePath
     * @return $this
     */
    public function setFilePath(string $filePath): self;

    /**
     * Get file name
     *
     * @return string|null
     */
    public function getFileName(): ?string;

    /**
     * Set file name
     *
     * @param string $fileName
     * @return $this
     */
    public function setFileName(string $fileName): self;

    /**
     * Get file type
     *
     * @return string|null
     */
    public function getFileType(): ?string;

    /**
     * Set file type
     *
     * @param string $fileType
     * @return $this
     */
    public function setFileType(string $fileType): self;

    /**
     * Get file size
     *
     * @return int|null
     */
    public function getFileSize(): ?int;

    /**
     * Set file size
     *
     * @param int $fileSize
     * @return $this
     */
    public function setFileSize(int $fileSize): self;

    /**
     * Get created at
     *
     * @return string|null
     */
    public function getCreatedAt(): ?string;

    /**
     * Set created at
     *
     * @param string $createdAt
     * @return $this
     */
    public function setCreatedAt(string $createdAt): self;
}
