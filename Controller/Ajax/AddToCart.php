<?php
declare(strict_types=1);

namespace Powerline\PrescripcionModule\Controller\Ajax;

use Magento\Framework\App\Action\HttpPostActionInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Framework\Controller\Result\Json;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Checkout\Model\Cart;
use Magento\Customer\Model\Session as CustomerSession;
use Powerline\PrescripcionModule\Api\ValidationServiceInterface;
use Powerline\PrescripcionModule\Api\PricingServiceInterface;
use Powerline\PrescripcionModule\Api\AttachmentRepositoryInterface;
use Powerline\PrescripcionModule\Model\Attachment;
use Powerline\PrescripcionModule\Model\AttachmentFactory;
use Psr\Log\LoggerInterface;

/**
 * AJAX Controller para añadir producto con configuración de prescripción al carrito
 * 
 * Endpoint: POST /presc/ajax/addtocart
 * 
 * Request Body:
 * {
 *   "product_id": 123,
 *   "qty": 1,
 *   "configuration": {
 *     "prescription": {...},
 *     "use_type": "progressive",
 *     "lens": {...},
 *     "treatments": [...],
 *     "attachment_id": 456
 *   }
 * }
 * 
 * Response:
 * {
 *   "success": true,
 *   "item_id": 789,
 *   "cart_url": "/checkout/cart",
 *   "message": "Product added to cart"
 * }
 */
class AddToCart implements HttpPostActionInterface
{
    public function __construct(
        private readonly RequestInterface $request,
        private readonly JsonFactory $jsonFactory,
        private readonly SerializerInterface $serializer,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly Cart $cart,
        private readonly CustomerSession $customerSession,
        private readonly ValidationServiceInterface $validationService,
        private readonly PricingServiceInterface $pricingService,
        private readonly AttachmentRepositoryInterface $attachmentRepository,
        private readonly AttachmentFactory $attachmentFactory,
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Execute add to cart action
     * 
     * @return Json
     */
    public function execute(): Json
    {
        $result = $this->jsonFactory->create();

        try {
            // Validar método POST
            if (!$this->request->isPost()) {
                return $result->setData([
                    'success' => false,
                    'message' => __('Invalid request method. POST required.')
                ]);
            }

            // Obtener datos del request
            $requestData = $this->getRequestData();
            
            // Validar datos básicos
            $this->validateRequestData($requestData);

            // Cargar producto
            $product = $this->productRepository->getById($requestData['product_id']);
            
            if (!$product->getId()) {
                throw new LocalizedException(__('Product not found.'));
            }
            
            // Log del tipo de producto
            $this->logger->info('Product type loaded', [
                'product_id' => $product->getId(),
                'type' => $product->getTypeId(),
                'is_configurable' => $product->getTypeId() === 'configurable'
            ]);

            // Validar configuración básica
            $configuration = $requestData['configuration'];
            
            // Validar que exista use_type
            if (empty($configuration['use_type'])) {
                throw new LocalizedException(__('Use type is required.'));
            }
            
            // Solo validar prescription si NO es "sin graduación"
            if ($configuration['use_type'] !== 'no_prescription' && empty($configuration['prescription'])) {
                throw new LocalizedException(__('Prescription data is required.'));
            }
            
            // Para productos configurables, validar que tengan super_attribute
            if ($product->getTypeId() === 'configurable') {
                if (empty($configuration['super_attribute'])) {
                    $this->logger->warning('Configurable product missing super_attribute', [
                        'product_id' => $product->getId(),
                        'configuration' => $configuration
                    ]);
                    throw new LocalizedException(__('Please select product options (size, color, etc.) before adding to cart.'));
                }
                
                $this->logger->info('Configurable product validation passed', [
                    'super_attribute' => $configuration['super_attribute']
                ]);
            } else {
                $this->logger->info('Simple product, no super_attribute validation needed', [
                    'product_id' => $product->getId()
                ]);
            }

            // Calcular precio total desde la configuración enviada desde el frontend
            $totalPrice = 0;
            if (isset($configuration['prices'])) {
                $prices = $configuration['prices'];
                $totalPrice = ($prices['frame'] ?? 0) 
                    + ($prices['base'] ?? 0)
                    + ($prices['surcharges'] ?? 0)
                    + ($prices['treatments'] ?? 0)
                    + ($prices['extras'] ?? 0);
            }
            
            $this->logger->info('Adding prescription product to cart', [
                'product_id' => $product->getId(),
                'customer_id' => $this->customerSession->getCustomerId(),
                'total_price' => $totalPrice
            ]);

            // Preparar buyRequest
            $buyRequest = [
                'qty' => $requestData['qty'] ?? 1,
                'powerline_presc' => $configuration,
                'powerline_presc_price' => $totalPrice
            ];
            
            // Añadir super_attribute si existe (para productos configurables como la talla)
            if (isset($configuration['super_attribute'])) {
                $buyRequest['super_attribute'] = $configuration['super_attribute'];
                $this->logger->info('Adding super_attribute to cart', [
                    'super_attribute' => $configuration['super_attribute']
                ]);
            }

            // Añadir al carrito
            $cartItem = $this->cart->addProduct($product, new \Magento\Framework\DataObject($buyRequest));
            
            if (is_string($cartItem)) {
                // Error message returned
                throw new LocalizedException(__($cartItem));
            }

            // Guardar carrito
            $this->cart->save();

            $this->logger->info('Prescription product added to cart successfully', [
                'item_id' => $cartItem->getId(),
                'product_id' => $product->getId(),
                'custom_price' => $cartItem->getCustomPrice()
            ]);

            // Guardar attachment en base de datos si existe hash
            if (!empty($configuration['attachment_hash'])) {
                $this->saveAttachmentToDatabase(
                    $configuration['attachment_hash'],
                    $configuration['attachment_filename'] ?? 'prescription.pdf',
                    $configuration['attachment_filepath'] ?? '',
                    $cartItem
                );
            }

            return $result->setData([
                'success' => true,
                'item_id' => $cartItem->getId(),
                'cart_url' => '/checkout/cart',
                'message' => __('Product added to cart successfully.'),
                'total_price' => $totalPrice
            ]);

        } catch (LocalizedException $e) {
            $this->logger->warning('Error adding prescription product to cart', [
                'exception' => $e->getMessage(),
                'request_data' => $requestData ?? null
            ]);

            return $result->setData([
                'success' => false,
                'message' => $e->getMessage()
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Unexpected error adding prescription product to cart', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return $result->setData([
                'success' => false,
                'message' => __('An error occurred while adding the product to cart. Please try again.')
            ]);
        }
    }

    /**
     * Obtener datos del request (JSON body)
     * 
     * @return array
     * @throws LocalizedException
     */
    private function getRequestData(): array
    {
        $content = $this->request->getContent();
        
        if (empty($content)) {
            throw new LocalizedException(__('Empty request body.'));
        }

        try {
            $data = $this->serializer->unserialize($content);
            
            if (!is_array($data)) {
                throw new LocalizedException(__('Invalid JSON format.'));
            }

            return $data;

        } catch (\InvalidArgumentException $e) {
            throw new LocalizedException(__('Invalid JSON: %1', $e->getMessage()));
        }
    }

    /**
     * Validar datos básicos del request
     * 
     * @param array $data
     * @return void
     * @throws LocalizedException
     */
    private function validateRequestData(array $data): void
    {
        if (empty($data['product_id'])) {
            throw new LocalizedException(__('Product ID is required.'));
        }

        if (!isset($data['configuration']) || !is_array($data['configuration'])) {
            throw new LocalizedException(__('Configuration is required.'));
        }

        $config = $data['configuration'];

        // Validar estructura mínima
        if (!isset($config['use_type'])) {
            throw new LocalizedException(__('Use type is required.'));
        }

        // Solo validar prescription si NO es "sin graduación"
        if (!isset($config['prescription'])) {
            throw new LocalizedException(__('Prescription data is required.'));
        }
        
        if ($config['use_type'] !== 'no_prescription' && empty($config['prescription'])) {
            throw new LocalizedException(__('Prescription data is required.'));
        }

        if (!isset($config['lens'])) {
            throw new LocalizedException(__('Lens data is required.'));
        }
    }

    /**
     * Guardar attachment en la base de datos
     * 
     * @param string $hash
     * @param string $filename
     * @param string $filepath
     * @param \Magento\Quote\Model\Quote\Item $cartItem
     * @return void
     */
    private function saveAttachmentToDatabase(
        string $hash,
        string $filename,
        string $filepath,
        $cartItem
    ): void {
        try {
            $quote = $this->cart->getQuote();
            $customerId = $this->customerSession->getCustomerId();

            $this->logger->info('Saving attachment to database', [
                'hash' => $hash,
                'filename' => $filename,
                'quote_id' => $quote->getId(),
                'quote_item_id' => $cartItem->getId(),
                'customer_id' => $customerId
            ]);

            /** @var Attachment $attachment */
            $attachment = $this->attachmentFactory->create();
            $attachment->setData([
                'hash' => $hash,
                'filename' => $filename,
                'filepath' => $filepath,
                'mime_type' => 'application/pdf',
                'file_size' => 0,
                'quote_id' => $quote->getId(),
                'quote_item_id' => $cartItem->getId(),
                'customer_id' => $customerId,
                'retention_until' => date('Y-m-d H:i:s', strtotime('+90 days'))
            ]);

            $this->attachmentRepository->save($attachment);

            $this->logger->info('Attachment saved to database successfully', [
                'attachment_id' => $attachment->getId(),
                'hash' => $hash
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error saving attachment to database', [
                'exception' => $e->getMessage(),
                'hash' => $hash
            ]);
        }
    }
}
