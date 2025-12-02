<?php

declare(strict_types=1);

namespace Powerline\PrescripcionModule\Controller\Ajax;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\ResultInterface;
use Powerline\PrescripcionModule\Api\ValidationServiceInterface;
use Powerline\PrescripcionModule\Model\Data\ConfigDto;
use Powerline\PrescripcionModule\Logger\Logger;

/**
 * AJAX endpoint for validation
 */
class Validate implements HttpPostActionInterface
{
    /**
     * @param RequestInterface $request
     * @param JsonFactory $jsonFactory
     * @param ValidationServiceInterface $validationService
     * @param Logger $logger
     */
    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $jsonFactory,
        private readonly ValidationServiceInterface $validationService,
        private readonly Logger $logger
    ) {
    }

    /**
     * Execute validation
     *
     * @return ResultInterface
     */
    public function execute(): ResultInterface
    {
        $result = $this->jsonFactory->create();

        try {
            // Get request data
            $data = $this->request->getContent();
            if (empty($data)) {
                return $result->setData([
                    'success' => false,
                    'error' => __('No data provided'),
                ]);
            }

            $requestData = json_decode($data, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return $result->setData([
                    'success' => false,
                    'error' => __('Invalid JSON data'),
                ]);
            }

            // Build ConfigDto
            $config = new ConfigDto();
            
            if (isset($requestData['product_id'])) {
                $config->setProductId((int)$requestData['product_id']);
            }
            
            if (isset($requestData['use_type'])) {
                $config->setUseType($requestData['use_type']);
            }
            
            if (isset($requestData['prescription_data'])) {
                $config->setPrescriptionData($requestData['prescription_data']);
            }
            
            if (isset($requestData['lens_material'])) {
                $config->setLensMaterial($requestData['lens_material']);
            }
            
            if (isset($requestData['lens_design'])) {
                $config->setLensDesign($requestData['lens_design']);
            }
            
            if (isset($requestData['lens_index'])) {
                $config->setLensIndex($requestData['lens_index']);
            }
            
            if (isset($requestData['treatments'])) {
                $config->setTreatments($requestData['treatments']);
            }
            
            if (isset($requestData['extras'])) {
                $config->setExtras($requestData['extras']);
            }

            // Validate configuration
            $validationResult = $this->validationService->validate($config);

            return $result->setData([
                'success' => true,
                'validation' => $validationResult->toArray(),
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error in AJAX validation', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $result->setData([
                'success' => false,
                'error' => __('An error occurred during validation'),
            ]);
        }
    }
}
