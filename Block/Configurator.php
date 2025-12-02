<?php

declare(strict_types=1);

namespace Powerline\PrescripcionModule\Block;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Catalog\Api\ProductRepositoryInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Powerline\PrescripcionModule\Logger\Logger;
use Magento\Framework\Registry;
use Magento\Framework\Data\Form\FormKey;

/**
 * Prescription Configurator Block
 */
class Configurator extends Template
{
    /**
     * @param Context $context
     * @param ProductRepositoryInterface $productRepository
     * @param SerializerInterface $serializer
     * @param Logger $logger
     * @param Registry $registry
     * @param FormKey $formKey
     * @param array $data
     */
    public function __construct(
        Context $context,
        private readonly ProductRepositoryInterface $productRepository,
        private readonly SerializerInterface $serializer,
        private readonly Logger $logger,
        private readonly Registry $registry,
        private readonly FormKey $formKey,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get product ID from request
     *
     * @return int
     */
    public function getProductId(): int
    {
        return (int)$this->getRequest()->getParam('product_id', 0);
    }

    /**
     * Get product
     * First checks registry (set by controller with resolved simple product),
     * then falls back to loading by product_id from request
     *
     * @return \Magento\Catalog\Api\Data\ProductInterface|null
     */
    public function getProduct()
    {
        // First, check if controller registered a product (simple or configurable)
        if ($product = $this->registry->registry('current_product')) {
            return $product;
        }

        // Fallback: load by product_id from request
        $productId = $this->getProductId();
        if (!$productId) {
            return null;
        }

        try {
            return $this->productRepository->getById($productId);
        } catch (NoSuchEntityException $e) {
            $this->logger->error('Product not found in configurator block', [
                'product_id' => $productId,
            ]);
            return null;
        }
    }

    /**
     * Get AJAX endpoints configuration
     *
     * @return string JSON
     */
    public function getEndpointsConfig(): string
    {
        return $this->serializer->serialize([
            'validate' => $this->getUrl('presc/ajax/validate'),
            'price' => $this->getUrl('presc/ajax/price'),
            'upload' => $this->getUrl('presc/ajax/upload'),
            'addtocart' => $this->getUrl('presc/ajax/addtocart'),
        ]);
    }

    /**
     * Get upload URL
     *
     * @return string
     */
    public function getUploadUrl(): string
    {
        return $this->getUrl('presc/ajax/upload');
    }

    /**
     * Get configurator configuration
     *
     * @return string JSON
     */
    public function getConfiguratorConfig(): string
    {
        $product = $this->getProduct();
        
        $config = [
            'product_id' => $this->getProductId(),
            'product_name' => $product ? $product->getName() : '',
            'product_sku' => $product ? $product->getSku() : '',
            'currency_code' => $this->_storeManager->getStore()->getCurrentCurrencyCode(),
            'currency_symbol' => $this->_storeManager->getStore()->getCurrentCurrency()->getCurrencySymbol(),
            'debounce_delay' => 300, // ms for AJAX calls
            'use_types' => [
                ['value' => 'monofocal', 'label' => __('Monofocal')],
                ['value' => 'bifocal', 'label' => __('Bifocal')],
                ['value' => 'progressive', 'label' => __('Progressive')],
                ['value' => 'occupational', 'label' => __('Occupational')],
                ['value' => 'reading', 'label' => __('Reading')],
            ],
            'sph_range' => ['min' => -20.00, 'max' => 20.00, 'step' => 0.25],
            'cyl_range' => ['min' => -8.00, 'max' => 8.00, 'step' => 0.25],
            'axis_range' => ['min' => 0, 'max' => 180, 'step' => 1],
            'add_range' => ['min' => 0.25, 'max' => 4.00, 'step' => 0.25],
            'pd_range' => ['min' => 20, 'max' => 80, 'step' => 1],
        ];

        return $this->serializer->serialize($config);
    }

    /**
     * Get existing configuration for edit mode
     * 
     * @return string JSON|null
     */
    public function getExistingConfiguration(): ?string
    {
        $existingConfig = $this->getData('existing_configuration');
        
        if ($existingConfig && is_array($existingConfig)) {
            return $this->serializer->serialize($existingConfig);
        }
        
        return null;
    }

    /**
     * Get item ID being edited (if any)
     * 
     * @return int
     */
    public function getEditItemId(): int
    {
        return (int)$this->getRequest()->getParam('item_id', 0);
    }

    /**
     * Check if in edit mode
     * 
     * @return bool
     */
    public function isEditMode(): bool
    {
        return $this->getEditItemId() > 0;
    }

    /**
     * Get add to cart URL
     *
     * @return string
     */
    public function getAddToCartUrl(): string
    {
        return $this->getUrl('presc/ajax/addtocart');
    }

    /**
     * Check if customer is logged in
     *
     * @return bool
     */
    public function isCustomerLoggedIn(): bool
    {
        return $this->_session->isLoggedIn();
    }

    /**
     * Get form key for CSRF protection
     *
     * @return string
     */
    public function getFormKey(): string
    {
        return $this->formKey->getFormKey();
    }

    /**
     * Get size attribute ID
     * 
     * @return int|null
     */
    public function getSizeAttributeId(): ?int
    {
        $product = $this->getProduct();
        if (!$product) {
            return null;
        }

        // Get size attribute from configurable product
        if ($product->getTypeId() === 'configurable') {
            $configurableAttributes = $product->getTypeInstance()->getConfigurableAttributes($product);
            
            // First pass: try to find size/talla/taille/calibre
            foreach ($configurableAttributes as $attribute) {
                $productAttribute = $attribute->getProductAttribute();
                $attributeCode = $productAttribute->getAttributeCode();
                
                // Common attribute codes for size
                if (in_array($attributeCode, ['size', 'talla', 'taille', 'talle', 'medida', 'calibre'])) {
                    return (int)$productAttribute->getAttributeId();
                }
            }
            
            // Second pass: return the first configurable attribute (usually the size)
            foreach ($configurableAttributes as $attribute) {
                $productAttribute = $attribute->getProductAttribute();
                return (int)$productAttribute->getAttributeId();
            }
        }

        return null;
    }
}
