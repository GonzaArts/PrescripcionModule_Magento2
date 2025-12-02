<?php

declare(strict_types=1);

namespace Powerline\PrescripcionModule\Service;

use Powerline\PrescripcionModule\Api\AttachmentManagementInterface;
use Powerline\PrescripcionModule\Api\AttachmentRepositoryInterface;
use Powerline\PrescripcionModule\Model\AttachmentFactory;
use Magento\Framework\App\Filesystem\DirectoryList;
use Magento\Framework\Filesystem;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\File\UploaderFactory;
use Magento\Framework\Image\AdapterFactory as ImageAdapterFactory;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Powerline\PrescripcionModule\Logger\Logger;

/**
 * Attachment Management Service
 * 
 * Handles file uploads for prescription attachments with:
 * - MIME type validation (images and PDF)
 * - File size limit (5MB)
 * - Secure storage in pub/media/prescription/
 * - Thumbnail generation for images
 * - Automatic cleanup of expired files
 */
class AttachmentManagement implements AttachmentManagementInterface
{
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'gif', 'pdf'];
    private const ALLOWED_MIME_TYPES = [
        'image/jpeg',
        'image/jpg',
        'image/png',
        'image/gif',
        'application/pdf'
    ];
    private const MAX_FILE_SIZE = 5242880; // 5MB in bytes
    private const UPLOAD_DIR = 'prescription';
    private const THUMBNAIL_WIDTH = 300;
    private const THUMBNAIL_HEIGHT = 300;
    private const EXPIRATION_DAYS = 30;

    /**
     * @param Filesystem $filesystem
     * @param UploaderFactory $uploaderFactory
     * @param ImageAdapterFactory $imageAdapterFactory
     * @param AttachmentFactory $attachmentFactory
     * @param AttachmentRepositoryInterface $attachmentRepository
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param DateTime $dateTime
     * @param Logger $logger
     */
    public function __construct(
        private readonly Filesystem $filesystem,
        private readonly UploaderFactory $uploaderFactory,
        private readonly ImageAdapterFactory $imageAdapterFactory,
        private readonly AttachmentFactory $attachmentFactory,
        private readonly AttachmentRepositoryInterface $attachmentRepository,
        private readonly SearchCriteriaBuilder $searchCriteriaBuilder,
        private readonly DateTime $dateTime,
        private readonly Logger $logger
    ) {
    }

    /**
     * Upload prescription attachment
     *
     * @param string $fileContent Base64 encoded file content
     * @param string $filename Original filename
     * @param string $mimeType MIME type
     * @param int|null $quoteId Quote ID
     * @param int|null $quoteItemId Quote Item ID
     * @return array ['attachment_id' => int, 'hash' => string, 'url' => string]
     * @throws LocalizedException
     */
    public function upload(
        string $fileContent,
        string $filename,
        string $mimeType,
        ?int $quoteId = null,
        ?int $quoteItemId = null
    ): array
    {
        $this->logger->info('AttachmentManagement::upload() called', [
            'filename' => $filename,
            'mime_type' => $mimeType,
            'content_length' => strlen($fileContent),
            'quote_id' => $quoteId
        ]);

        try {
            $this->logger->info('Decoding base64 content...');
            // Decode base64 content
            $fileContent = base64_decode($fileContent, true);
            if ($fileContent === false) {
                $this->logger->error('Failed to decode base64 content');
                throw new LocalizedException(__('Invalid file content encoding'));
            }
            $this->logger->info('Base64 decoded successfully, size: ' . strlen($fileContent));

            // Validate file
            $fileSize = strlen($fileContent);
            $this->logger->info('Validating file...');
            $this->validateFile($filename, $mimeType, $fileSize);
            $this->logger->info('File validation passed');
            
            // Get upload directory
            $this->logger->info('Getting upload directory...');
            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            $uploadPath = $mediaDirectory->getAbsolutePath(self::UPLOAD_DIR);
            $this->logger->info('Upload path: ' . $uploadPath);
            
            // Create directory if not exists
            if (!is_dir($uploadPath)) {
                $this->logger->info('Creating upload directory...');
                mkdir($uploadPath, 0777, true);
            }

            // Generate unique filename
            $extension = pathinfo($filename, PATHINFO_EXTENSION);
            $hash = bin2hex(random_bytes(16));
            $uniqueFilename = $hash . '.' . $extension;
            $filePath = self::UPLOAD_DIR . '/' . $uniqueFilename;
            $absolutePath = $uploadPath . '/' . $uniqueFilename;
            $this->logger->info('Generated unique filename: ' . $uniqueFilename);

            // Save file
            $this->logger->info('Saving file to: ' . $absolutePath);
            if (file_put_contents($absolutePath, $fileContent) === false) {
                $this->logger->error('Failed to write file to disk');
                throw new LocalizedException(__('Failed to save file'));
            }
            $this->logger->info('File saved successfully');

            $thumbnailPath = null;

            // Generate thumbnail for images
            if ($this->isImage($mimeType)) {
                $this->logger->info('Generating thumbnail for image...');
                $thumbnailPath = $this->generateThumbnail($absolutePath);
                $this->logger->info('Thumbnail generated: ' . ($thumbnailPath ?? 'none'));
            }

            // NO GUARDAMOS EN BD AQUÍ - se guardará cuando se añada al carrito
            $this->logger->info('Skipping database save - will save when added to cart');
            $this->logger->info('Prescription file uploaded successfully', [
                'filename' => $filename,
                'hash' => $hash,
                'file_path' => $filePath,
                'size' => $fileSize,
                'quote_id' => $quoteId
            ]);

            // Retornamos la info del archivo sin attachment_id (aún no está en BD)
            return [
                'attachment_id' => null, // Se asignará al añadir al carrito
                'hash' => $hash,
                'file_path' => $filePath,
                'filename' => $filename,
                'url' => '#', // URL temporal
                'thumbnail_path' => $thumbnailPath
            ];

        } catch (\Exception $e) {
            $this->logger->error('File upload error', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            throw new LocalizedException(
                __('Failed to upload file: %1', $e->getMessage())
            );
        }
    }

    /**
     * Validate file
     *
     * @param string $filename
     * @param string $mimeType
     * @param int $fileSize
     * @return bool
     * @throws LocalizedException
     */
    public function validateFile(string $filename, string $mimeType, int $fileSize): bool
    {
        // Validate file size
        if ($fileSize > self::MAX_FILE_SIZE) {
            throw new LocalizedException(
                __('File size exceeds maximum allowed size of %1MB', self::MAX_FILE_SIZE / 1024 / 1024)
            );
        }

        // Validate MIME type
        if (!in_array($mimeType, self::ALLOWED_MIME_TYPES, true)) {
            throw new LocalizedException(
                __('Invalid file type. Allowed types: JPG, PNG, GIF, PDF')
            );
        }

        // Validate extension
        $extension = strtolower(pathinfo($filename, PATHINFO_EXTENSION));
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new LocalizedException(
                __('Invalid file extension. Allowed extensions: %1', implode(', ', self::ALLOWED_EXTENSIONS))
            );
        }

        return true;
    }

    /**
     * Check if file is an image
     *
     * @param string $mimeType
     * @return bool
     */
    private function isImage(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'image/');
    }

    /**
     * Generate thumbnail for image
     *
     * @param string $imagePath
     * @return string|null Thumbnail path relative to media directory
     */
    private function generateThumbnail(string $imagePath): ?string
    {
        try {
            $imageAdapter = $this->imageAdapterFactory->create();
            $imageAdapter->open($imagePath);

            // Calculate resize dimensions maintaining aspect ratio
            $originalWidth = $imageAdapter->getOriginalWidth();
            $originalHeight = $imageAdapter->getOriginalHeight();
            $ratio = min(
                self::THUMBNAIL_WIDTH / $originalWidth,
                self::THUMBNAIL_HEIGHT / $originalHeight
            );

            $newWidth = (int)($originalWidth * $ratio);
            $newHeight = (int)($originalHeight * $ratio);

            // Resize
            $imageAdapter->resize($newWidth, $newHeight);

            // Generate thumbnail path
            $pathInfo = pathinfo($imagePath);
            $thumbnailPath = $pathInfo['dirname'] . '/' . $pathInfo['filename'] . '_thumb.' . $pathInfo['extension'];

            // Save thumbnail
            $imageAdapter->save($thumbnailPath);

            // Return relative path
            $mediaPath = $this->filesystem->getDirectoryRead(DirectoryList::MEDIA)->getAbsolutePath();
            return str_replace($mediaPath, '', $thumbnailPath);

        } catch (\Exception $e) {
            $this->logger->error('Thumbnail generation failed', [
                'image_path' => $imagePath,
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    /**
     * Calculate expiration date
     *
     * @return string
     */
    private function calculateExpirationDate(): string
    {
        return date('Y-m-d H:i:s', strtotime('+' . self::EXPIRATION_DAYS . ' days'));
    }

    /**
     * Get attachment by ID
     *
     * @param int $attachmentId
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getAttachment(int $attachmentId): array
    {
        try {
            $attachment = $this->attachmentRepository->getById($attachmentId);
            
            return [
                'attachment_id' => $attachment->getAttachmentId(),
                'filename' => $attachment->getFilename(),
                'file_path' => $attachment->getFilePath(),
                'file_type' => $attachment->getFileType(),
                'file_size' => $attachment->getFileSize(),
                'thumbnail_path' => $attachment->getThumbnailPath(),
                'uploaded_at' => $attachment->getUploadedAt(),
                'hash' => $attachment->getHash()
            ];
        } catch (\Exception $e) {
            $this->logger->error('Failed to get attachment', [
                'attachment_id' => $attachmentId,
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    /**
     * Get download URL for attachment
     *
     * @param int $attachmentId
     * @param string $hash For security validation
     * @return string
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getDownloadUrl(int $attachmentId, string $hash): string
    {
        // TODO: Implement proper download URL with security hash validation
        return '/pub/media/prescription/' . $attachmentId . '?hash=' . $hash;
    }

    /**
     * Delete attachment
     *
     * @param int $attachmentId
     * @return bool
     * @throws LocalizedException
     */
    public function delete(int $attachmentId): bool
    {
        try {
            $attachment = $this->attachmentRepository->getById($attachmentId);
            
            // Delete files from filesystem
            $mediaDirectory = $this->filesystem->getDirectoryWrite(DirectoryList::MEDIA);
            
            if ($attachment->getFilePath()) {
                $mediaDirectory->delete($attachment->getFilePath());
            }
            
            if ($attachment->getThumbnailPath()) {
                $mediaDirectory->delete($attachment->getThumbnailPath());
            }

            // Delete from database
            $this->attachmentRepository->delete($attachment);

            $this->logger->info('Attachment deleted', [
                'attachment_id' => $attachmentId
            ]);

            return true;

        } catch (\Exception $e) {
            $this->logger->error('Failed to delete attachment', [
                'attachment_id' => $attachmentId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Clean up expired attachments
     *
     * @return int Number of deleted attachments
     */
    public function cleanupExpired(): int
    {
        try {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('expires_at', $this->dateTime->gmtDate(), 'lt')
                ->create();

            $attachments = $this->attachmentRepository->getList($searchCriteria)->getItems();
            $count = 0;

            foreach ($attachments as $attachment) {
                if ($this->delete($attachment->getAttachmentId())) {
                    $count++;
                }
            }

            $this->logger->info('Expired attachments cleaned up', [
                'count' => $count
            ]);

            return $count;

        } catch (\Exception $e) {
            $this->logger->error('Cleanup expired attachments failed', [
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Get attachments by quote ID
     *
     * @param int $quoteId
     * @return array
     */
    public function getByQuoteId(int $quoteId): array
    {
        try {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('quote_id', $quoteId, 'eq')
                ->create();

            $attachments = $this->attachmentRepository->getList($searchCriteria)->getItems();
            $result = [];

            foreach ($attachments as $attachment) {
                $result[] = [
                    'attachment_id' => $attachment->getAttachmentId(),
                    'filename' => $attachment->getFilename(),
                    'file_path' => $attachment->getFilePath(),
                    'file_type' => $attachment->getFileType(),
                    'file_size' => $attachment->getFileSize(),
                    'thumbnail_path' => $attachment->getThumbnailPath(),
                    'uploaded_at' => $attachment->getUploadedAt(),
                    'hash' => $attachment->getHash()
                ];
            }

            return $result;

        } catch (\Exception $e) {
            $this->logger->error('Failed to get attachments by quote ID', [
                'quote_id' => $quoteId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }

    /**
     * Get attachments by order ID
     *
     * @param int $orderId
     * @return array
     */
    public function getByOrderId(int $orderId): array
    {
        try {
            $searchCriteria = $this->searchCriteriaBuilder
                ->addFilter('order_id', $orderId, 'eq')
                ->create();

            $attachments = $this->attachmentRepository->getList($searchCriteria)->getItems();
            $result = [];

            foreach ($attachments as $attachment) {
                $result[] = [
                    'attachment_id' => $attachment->getAttachmentId(),
                    'filename' => $attachment->getFilename(),
                    'file_path' => $attachment->getFilePath(),
                    'file_type' => $attachment->getFileType(),
                    'file_size' => $attachment->getFileSize(),
                    'thumbnail_path' => $attachment->getThumbnailPath(),
                    'uploaded_at' => $attachment->getUploadedAt(),
                    'hash' => $attachment->getHash()
                ];
            }

            return $result;

        } catch (\Exception $e) {
            $this->logger->error('Failed to get attachments by order ID', [
                'order_id' => $orderId,
                'error' => $e->getMessage()
            ]);
            return [];
        }
    }
}
