<?php

declare(strict_types=1);

namespace Powerline\PrescripcionModule\Controller\Prescription;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\View\Result\PageFactory;
use Magento\Framework\View\Result\Page;
use Magento\Framework\App\RequestInterface;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Powerline\PrescripcionModule\Logger\Logger;
use Magento\ConfigurableProduct\Model\Product\Type\Configurable as ConfigurableType;
use Magento\Framework\Registry;

/**
 * Prescription configurator main page controller
 * 
 * Supports two modes:
 * 1. New configuration: /presc/prescription/index?product_id=X
 * 2. Edit existing: /presc/prescription/index?product_id=X&item_id=Y
 */
class Index implements HttpGetActionInterface
{
    /**
     * @param PageFactory $pageFactory
     * @param RequestInterface $request
     * @param ProductRepositoryInterface $productRepository
     * @param RedirectFactory $redirectFactory
     * @param SerializerInterface $serializer
     * @param CheckoutSession $checkoutSession
     * @param Logger $logger
     * @param ConfigurableType $configurableType
     * @param Registry $registry
     */
    public function __construct(
        private readonly PageFactory $pageFactory,
        private readonly RequestInterface $request,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly RedirectFactory $redirectFactory,
        private readonly SerializerInterface $serializer,
        private readonly CheckoutSession $checkoutSession,
        private readonly Logger $logger,
        private readonly ConfigurableType $configurableType,
        private readonly Registry $registry
    ) {
    }

    /**
     * Execute configurator page
     *
     * @return Page|\Magento\Framework\Controller\Result\Redirect
     */
    public function execute()
    {
        try {
            // Get product ID from request
            $productId = (int)$this->request->getParam('product_id');
            $itemId = (int)$this->request->getParam('item_id');
            
            if (!$productId) {
                $this->logger->error('Configurator accessed without product_id');
                $redirect = $this->redirectFactory->create();
                return $redirect->setPath('/');
            }

            // Verify product exists and is gradable
            try {
                $product = $this->productRepository->getById($productId);
                
                if (!$product->getData('has_prescription')) {
                    $this->logger->warning('Configurator accessed for non-gradable product', [
                        'product_id' => $productId,
                    ]);
                    $redirect = $this->redirectFactory->create();
                    return $redirect->setPath('catalog/product/view', ['id' => $productId]);
                }

                // Verificar que NO esté en categorías deshabilitadas
                $categoryIds = $product->getCategoryIds();
                $disabledCategories = [22, 9, '22', '9']; // Gafas deportivas (22), Lentillas (9)
                
                foreach ($disabledCategories as $disabledCatId) {
                    if (in_array($disabledCatId, $categoryIds)) {
                        $this->logger->warning('Configurator accessed for disabled category', [
                            'product_id' => $productId,
                            'category_id' => $disabledCatId
                        ]);
                        $redirect = $this->redirectFactory->create();
                        return $redirect->setPath('catalog/product/view', ['id' => $productId]);
                    }
                }
            } catch (NoSuchEntityException $e) {
                $this->logger->error('Product not found for configurator', [
                    'product_id' => $productId,
                ]);
                $redirect = $this->redirectFactory->create();
                return $redirect->setPath('/');
            }

            // 🔍 RESOLVE SIMPLE PRODUCT FROM CONFIGURABLE IF NEEDED
            // Extract super_attribute from request (supports both formats)
            $superAttributes = [];
            
            // Format 1: super_attribute_150=XX
            foreach ($this->request->getParams() as $key => $val) {
                if (preg_match('/^super_attribute_(\d+)$/', $key, $matches)) {
                    $superAttributes[(int)$matches[1]] = $val;
                }
            }
            
            // Format 2: super_attribute[150]=XX (preferred)
            $superAttrArray = (array)$this->request->getParam('super_attribute', []);
            foreach ($superAttrArray as $attrId => $val) {
                $superAttributes[(int)$attrId] = $val;
            }

            // If product is configurable and we have attribute selection, resolve child product
            if ($product->getTypeId() === \Magento\ConfigurableProduct\Model\Product\Type\Configurable::TYPE_CODE
                && !empty($superAttributes)) {
                
                $this->logger->info('🔍 Attempting to resolve simple product from configurable', [
                    'configurable_id' => $product->getId(),
                    'super_attributes' => $superAttributes,
                ]);
                
                try {
                    $childProduct = $this->configurableType->getProductByAttributes($superAttributes, $product);
                    
                    if ($childProduct && $childProduct->getId()) {
                        $this->logger->info('✅ Successfully resolved simple product', [
                            'configurable_id' => $product->getId(),
                            'simple_id' => $childProduct->getId(),
                            'simple_sku' => $childProduct->getSku(),
                            'super_attributes' => $superAttributes,
                        ]);
                        
                        // Replace product with the simple child
                        $product = $childProduct;
                    } else {
                        $this->logger->warning('⚠️ Could not resolve child product from attributes', [
                            'configurable_id' => $product->getId(),
                            'super_attributes' => $superAttributes,
                        ]);
                    }
                } catch (\Exception $e) {
                    $this->logger->error('❌ Error resolving child product', [
                        'configurable_id' => $product->getId(),
                        'super_attributes' => $superAttributes,
                        'error' => $e->getMessage(),
                    ]);
                }
            } else {
                $this->logger->info('📍 Product type or no attributes provided', [
                    'product_id' => $product->getId(),
                    'type' => $product->getTypeId(),
                    'has_attributes' => !empty($superAttributes),
                ]);
            }

            // Register the product (simple or configurable) for blocks
            $this->registry->unregister('current_product');
            $this->registry->register('current_product', $product);

            // Load existing configuration if editing
            $existingConfig = null;
            if ($itemId) {
                $existingConfig = $this->loadExistingConfiguration($itemId);
                
                if ($existingConfig) {
                    $this->logger->info('Loading existing configuration for edit', [
                        'item_id' => $itemId,
                        'product_id' => $productId,
                    ]);
                }
            }

            // Create page
            $page = $this->pageFactory->create();
            
            // Pass existing configuration to layout if available
            if ($existingConfig) {
                $page->getLayout()->getBlock('configurator.main')
                    ->setData('existing_configuration', $existingConfig);
            }
            
            // Add custom CSS class to body
            $page->getConfig()->addBodyClass('prescription-configurator');
            
            // Log configurator access for analytics
            $this->logger->info('Configurator accessed', [
                'product_id' => $productId,
                'product_sku' => $product->getSku(),
            ]);
            
            return $page;

        } catch (\Exception $e) {
            $this->logger->error('Error loading configurator', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
            
            $redirect = $this->redirectFactory->create();
            return $redirect->setPath('/');
        }
    }

    /**
     * Load existing configuration from cart item
     * 
     * @param int $itemId
     * @return array|null
     */
    private function loadExistingConfiguration(int $itemId): ?array
    {
        try {
            $quote = $this->checkoutSession->getQuote();
            
            if (!$quote || !$quote->getId()) {
                $this->logger->warning('No active quote found for item edit', [
                    'item_id' => $itemId,
                ]);
                return null;
            }

            // Find item in quote
            $item = $quote->getItemById($itemId);
            
            if (!$item) {
                $this->logger->warning('Cart item not found', [
                    'item_id' => $itemId,
                    'quote_id' => $quote->getId(),
                ]);
                return null;
            }

            // Get product options
            $options = $item->getProduct()->getTypeInstance()->getOrderOptions($item->getProduct());
            
            if (!isset($options['additional_options'])) {
                $this->logger->warning('No additional options found in cart item', [
                    'item_id' => $itemId,
                ]);
                return null;
            }

            // Find prescription configuration option
            foreach ($options['additional_options'] as $option) {
                if (isset($option['option_value'])) {
                    try {
                        $config = $this->serializer->unserialize($option['option_value']);
                        
                        if (is_array($config) && isset($config['prescription']) && isset($config['lens'])) {
                            $this->logger->info('Successfully loaded configuration from cart item', [
                                'item_id' => $itemId,
                            ]);
                            return $config;
                        }
                    } catch (\InvalidArgumentException $e) {
                        // Not a valid serialized config, continue
                        continue;
                    }
                }
            }

            $this->logger->warning('No valid prescription configuration found in cart item', [
                'item_id' => $itemId,
            ]);
            return null;

        } catch (\Exception $e) {
            $this->logger->error('Error loading existing configuration', [
                'item_id' => $itemId,
                'error' => $e->getMessage(),
            ]);
            return null;
        }
    }
}
