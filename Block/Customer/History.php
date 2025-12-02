<?php
declare(strict_types=1);

/**
 * Powerline PrescripcionModule - Customer Prescription History Block
 *
 * @category  Powerline
 * @package   Powerline_PrescripcionModule
 * @author    Powerline Development Team
 * @copyright Copyright (c) 2025 Powerline
 */

namespace Powerline\PrescripcionModule\Block\Customer;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Customer\Model\Session;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Class History
 * 
 * Block for displaying customer's prescription orders
 */
class History extends Template
{
    /**
     * @var Session
     */
    private Session $customerSession;

    /**
     * @var OrderCollectionFactory
     */
    private OrderCollectionFactory $orderCollectionFactory;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var array|null
     */
    private ?array $prescriptionItems = null;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Session $customerSession
     * @param OrderCollectionFactory $orderCollectionFactory
     * @param SerializerInterface $serializer
     * @param array $data
     */
    public function __construct(
        Context $context,
        Session $customerSession,
        OrderCollectionFactory $orderCollectionFactory,
        SerializerInterface $serializer,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->customerSession = $customerSession;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->serializer = $serializer;
    }

    /**
     * Get prescription items from customer orders
     *
     * @return array
     */
    public function getPrescriptionItems(): array
    {
        if ($this->prescriptionItems !== null) {
            return $this->prescriptionItems;
        }

        $this->prescriptionItems = [];
        $customerId = $this->customerSession->getCustomerId();

        if (!$customerId) {
            return $this->prescriptionItems;
        }

        // Load customer orders
        $orderCollection = $this->orderCollectionFactory->create()
            ->addFieldToFilter('customer_id', $customerId)
            ->setOrder('created_at', 'DESC');

        foreach ($orderCollection as $order) {
            foreach ($order->getAllVisibleItems() as $item) {
                $productOptions = $item->getProductOptions();
                
                if ($this->hasPrescriptionConfiguration($productOptions)) {
                    $config = $this->getPrescriptionConfig($productOptions);
                    
                    $this->prescriptionItems[] = [
                        'item_id' => $item->getItemId(),
                        'order_id' => $order->getEntityId(),
                        'order_increment_id' => $order->getIncrementId(),
                        'product_name' => $item->getName(),
                        'product_sku' => $item->getSku(),
                        'created_at' => $order->getCreatedAt(),
                        'status' => $order->getStatusLabel(),
                        'prescription_summary' => $this->formatPrescriptionSummary($config),
                        'configuration' => $config
                    ];
                }
            }
        }

        return $this->prescriptionItems;
    }

    /**
     * Check if product options contain prescription configuration
     *
     * @param array|null $productOptions
     * @return bool
     */
    private function hasPrescriptionConfiguration(?array $productOptions): bool
    {
        if (!$productOptions || !isset($productOptions['additional_options'])) {
            return false;
        }

        foreach ($productOptions['additional_options'] as $option) {
            if (isset($option['label']) && $option['label'] === 'Prescription Configuration') {
                return true;
            }
        }

        return false;
    }

    /**
     * Get prescription configuration
     *
     * @param array $productOptions
     * @return array|null
     */
    private function getPrescriptionConfig(array $productOptions): ?array
    {
        if (!isset($productOptions['additional_options'])) {
            return null;
        }

        foreach ($productOptions['additional_options'] as $option) {
            if (isset($option['label']) && $option['label'] === 'Prescription Configuration') {
                try {
                    return $this->serializer->unserialize($option['option_value']);
                } catch (\Exception $e) {
                    return null;
                }
            }
        }

        return null;
    }

    /**
     * Format prescription summary
     *
     * @param array|null $config
     * @return string
     */
    private function formatPrescriptionSummary(?array $config): string
    {
        if (!$config || !isset($config['prescription'])) {
            return __('N/A');
        }

        $parts = [];

        if (isset($config['prescription']['od']['sph'])) {
            $parts[] = sprintf('OD SPH %+.2f', $config['prescription']['od']['sph']);
        }

        if (isset($config['prescription']['od']['cyl'])) {
            $parts[] = sprintf('CYL %+.2f', $config['prescription']['od']['cyl']);
        }

        if (isset($config['prescription']['oi']['sph'])) {
            $parts[] = sprintf('| OI SPH %+.2f', $config['prescription']['oi']['sph']);
        }

        if (isset($config['prescription']['oi']['cyl'])) {
            $parts[] = sprintf('CYL %+.2f', $config['prescription']['oi']['cyl']);
        }

        return !empty($parts) ? implode(' ', $parts) : __('N/A');
    }

    /**
     * Get view details URL
     *
     * @param int $itemId
     * @return string
     */
    public function getViewUrl(int $itemId): string
    {
        return $this->getUrl('presc/customer/view', ['item_id' => $itemId]);
    }

    /**
     * Get order view URL
     *
     * @param int $orderId
     * @return string
     */
    public function getOrderViewUrl(int $orderId): string
    {
        return $this->getUrl('sales/order/view', ['order_id' => $orderId]);
    }

    /**
     * Get reorder URL with prescription
     *
     * @param array $config
     * @param int $productId
     * @return string
     */
    public function getReorderUrl(array $config, int $productId): string
    {
        $configJson = $this->serializer->serialize($config);
        return $this->getUrl('presc/prescription/index', [
            'pid' => $productId,
            'reorder' => 1,
            'config' => base64_encode($configJson)
        ]);
    }
}
