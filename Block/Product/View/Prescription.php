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

namespace Powerline\PrescripcionModule\Block\Product\View;

use Magento\Catalog\Block\Product\Context;
use Magento\Catalog\Block\Product\View;
use Magento\Catalog\Model\Product;

/**
 * Block for prescription configurator CTA on PDP
 */
class Prescription extends View
{
    /**
     * @param Context $context
     * @param \Magento\Framework\Url\EncoderInterface $urlEncoder
     * @param \Magento\Framework\Json\EncoderInterface $jsonEncoder
     * @param \Magento\Framework\Stdlib\StringUtils $string
     * @param \Magento\Catalog\Helper\Product $productHelper
     * @param \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig
     * @param \Magento\Framework\Locale\FormatInterface $localeFormat
     * @param \Magento\Customer\Model\Session $customerSession
     * @param \Magento\Catalog\Api\ProductRepositoryInterface $productRepository
     * @param \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency
     * @param array $data
     */
    public function __construct(
        Context $context,
        \Magento\Framework\Url\EncoderInterface $urlEncoder,
        \Magento\Framework\Json\EncoderInterface $jsonEncoder,
        \Magento\Framework\Stdlib\StringUtils $string,
        \Magento\Catalog\Helper\Product $productHelper,
        \Magento\Catalog\Model\ProductTypes\ConfigInterface $productTypeConfig,
        \Magento\Framework\Locale\FormatInterface $localeFormat,
        \Magento\Customer\Model\Session $customerSession,
        \Magento\Catalog\Api\ProductRepositoryInterface $productRepository,
        \Magento\Framework\Pricing\PriceCurrencyInterface $priceCurrency,
        array $data = []
    ) {
        parent::__construct(
            $context,
            $urlEncoder,
            $jsonEncoder,
            $string,
            $productHelper,
            $productTypeConfig,
            $localeFormat,
            $customerSession,
            $productRepository,
            $priceCurrency,
            $data
        );
    }

    /**
     * Check if product is gradable (can have prescription)
     *
     * @return bool
     */
    public function isGradable(): bool
    {
        $product = $this->getProduct();
        if (!$product || !$product->getId()) {
            return false;
        }

        // Verificar que el producto tenga el atributo has_prescription activado
        if (!$product->getData('has_prescription')) {
            return false;
        }

        // Verificar que NO esté en categorías deshabilitadas
        $categoryIds = $product->getCategoryIds();
        
        // Categorías donde NO se permite prescripción:
        // ID 22 - Gafas deportivas
        // ID 9 - Lentillas
        $disabledCategories = [22, 9, '22', '9'];
        
        foreach ($disabledCategories as $disabledCatId) {
            if (in_array($disabledCatId, $categoryIds)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get configurator URL
     *
     * @return string
     */
    public function getConfiguratorUrl(): string
    {
        $product = $this->getProduct();
        if (!$product || !$product->getId()) {
            return '';
        }

        // Always use the configurable product ID as base
        // JavaScript will replace it with the simple product ID if available
        return $this->getUrl('presc/prescription/index', [
            'product_id' => $product->getId(),
            '_secure' => true
        ]);
    }

    /**
     * Get product info for JS
     *
     * @return array
     */
    public function getProductJsonConfig(): array
    {
        $product = $this->getProduct();
        if (!$product || !$product->getId()) {
            return [];
        }

        return [
            'productId' => $product->getId(),
            'productName' => $product->getName(),
            'productSku' => $product->getSku(),
            'isGradable' => $this->isGradable(),
            'configuratorUrl' => $this->getConfiguratorUrl()
        ];
    }

    /**
     * Get product JSON config as string
     *
     * @return string
     */
    public function getProductJsonConfigString(): string
    {
        return json_encode($this->getProductJsonConfig());
    }
}
