<?php

declare(strict_types=1);

namespace Powerline\PrescripcionModule\Controller\Ajax;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\InvalidRequestException;
use Powerline\PrescripcionModule\Api\AttachmentManagementInterface;
use Powerline\PrescripcionModule\Logger\Logger;

/**
 * AJAX Upload Controller
 * 
 * Handles asynchronous file uploads for prescription attachments
 */
class Upload implements HttpPostActionInterface, CsrfAwareActionInterface
{
    private const FILE_FIELD_ID = 'prescription_file';

    /**
     * @param RequestInterface $request
     * @param JsonFactory $jsonFactory
     * @param AttachmentManagementInterface $attachmentManagement
     * @param Logger $logger
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $jsonFactory,
        private readonly AttachmentManagementInterface $attachmentManagement,
        private readonly Logger $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function createCsrfValidationException(
        RequestInterface $request
    ): ?InvalidRequestException {
        return null;
    }

    /**
     * @inheritDoc
     */
    public function validateForCsrf(RequestInterface $request): ?bool
    {
        return true;
    }

    /**
     * Execute upload action
     *
     * @return \Magento\Framework\Controller\Result\Json
     */
    public function execute()
    {
        $result = $this->jsonFactory->create();

        $this->logger->info('=== UPLOAD REQUEST START ===');
        $this->logger->info('Request Method: ' . $this->request->getMethod());
        $this->logger->info('Files received: ' . print_r(array_keys($_FILES), true));
        $this->logger->info('POST params: ' . print_r($this->request->getParams(), true));

        // Validate POST request
        if (!$this->request->isPost()) {
            $this->logger->error('ERROR: Not a POST request');
            return $result->setData([
                'success' => false,
                'error' => 'Invalid request method'
            ]);
        }

        try {
            // Validate file upload
            if (!isset($_FILES[self::FILE_FIELD_ID])) {
                $this->logger->error('ERROR: No file in $_FILES with key: ' . self::FILE_FIELD_ID);
                $this->logger->error('Available $_FILES keys: ' . print_r(array_keys($_FILES), true));
                throw new LocalizedException(__('No file uploaded'));
            }

            $file = $_FILES[self::FILE_FIELD_ID];
            $this->logger->info('File info: ' . print_r($file, true));

            // Check for upload errors
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errorMessages = [
                    UPLOAD_ERR_INI_SIZE => 'File exceeds upload_max_filesize',
                    UPLOAD_ERR_FORM_SIZE => 'File exceeds MAX_FILE_SIZE',
                    UPLOAD_ERR_PARTIAL => 'File was only partially uploaded',
                    UPLOAD_ERR_NO_FILE => 'No file was uploaded',
                    UPLOAD_ERR_NO_TMP_DIR => 'Missing temporary folder',
                    UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk',
                    UPLOAD_ERR_EXTENSION => 'PHP extension stopped upload'
                ];
                $errorMsg = $errorMessages[$file['error']] ?? 'Unknown error: ' . $file['error'];
                $this->logger->error('File upload error code: ' . $file['error'] . ' - ' . $errorMsg);
                throw new LocalizedException(__($errorMsg));
            }

            $this->logger->info('File validation passed, reading content...');

            // Read file content and encode to base64
            $fileContent = file_get_contents($file['tmp_name']);
            if ($fileContent === false) {
                $this->logger->error('ERROR: Failed to read file from tmp_name: ' . $file['tmp_name']);
                throw new LocalizedException(__('Failed to read uploaded file'));
            }

            $this->logger->info('File content read successfully, size: ' . strlen($fileContent) . ' bytes');

            $base64Content = base64_encode($fileContent);
            $this->logger->info('File encoded to base64, length: ' . strlen($base64Content));

            // Get quote ID from request
            $quoteId = $this->request->getParam('quote_id', null);
            $quoteItemId = $this->request->getParam('quote_item_id', null);
            $this->logger->info('Quote ID: ' . ($quoteId ?? 'null') . ', Quote Item ID: ' . ($quoteItemId ?? 'null'));

            // Upload file using service
            $this->logger->info('Calling attachmentManagement->upload()...');
            $uploadResult = $this->attachmentManagement->upload(
                $base64Content,
                $file['name'],
                $file['type'],
                $quoteId ? (int)$quoteId : null,
                $quoteItemId ? (int)$quoteItemId : null
            );

            $this->logger->info('Upload successful! Result: ' . print_r($uploadResult, true));
            $this->logger->info('=== UPLOAD REQUEST END (SUCCESS) ===');

            return $result->setData([
                'success' => true,
                'attachment_id' => $uploadResult['attachment_id'],
                'hash' => $uploadResult['hash'],
                'filename' => $uploadResult['filename'],
                'file_path' => $uploadResult['file_path'],
                'url' => $uploadResult['url'],
                'thumbnail_path' => $uploadResult['thumbnail_path'] ?? null
            ]);

        } catch (LocalizedException $e) {
            $this->logger->error('LocalizedException: ' . $e->getMessage());
            $this->logger->error('Trace: ' . $e->getTraceAsString());
            $this->logger->info('=== UPLOAD REQUEST END (LOCALIZED ERROR) ===');
            return $result->setData([
                'success' => false,
                'error' => $e->getMessage()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Exception: ' . $e->getMessage());
            $this->logger->error('Trace: ' . $e->getTraceAsString());
            $this->logger->info('=== UPLOAD REQUEST END (EXCEPTION) ===');
            return $result->setData([
                'success' => false,
                'error' => 'Error: ' . $e->getMessage()
            ]);
        }
    }
}
