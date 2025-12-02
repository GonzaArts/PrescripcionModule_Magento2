<?php
declare(strict_types=1);

/**
 * Powerline PrescripcionModule - Logging Helper
 *
 * @category  Powerline
 * @package   Powerline_PrescripcionModule
 * @author    Powerline Development Team
 * @copyright Copyright (c) 2025 Powerline
 */

namespace Powerline\PrescripcionModule\Helper;

use Powerline\PrescripcionModule\Logger\Logger;
use Powerline\PrescripcionModule\Model\LogEventFactory;
use Powerline\PrescripcionModule\Model\ResourceModel\LogEvent as LogEventResource;
use Magento\Framework\Serialize\SerializerInterface;
use Magento\Customer\Model\Session as CustomerSession;
use Magento\Backend\Model\Auth\Session as AdminSession;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;

/**
 * Class LogHelper
 * 
 * Helper for structured logging with automatic context
 */
class LogHelper extends AbstractHelper
{
    /**
     * @var Logger
     */
    private Logger $logger;

    /**
     * @var LogEventFactory
     */
    private LogEventFactory $logEventFactory;

    /**
     * @var LogEventResource
     */
    private LogEventResource $logEventResource;

    /**
     * @var SerializerInterface
     */
    private SerializerInterface $serializer;

    /**
     * @var CustomerSession
     */
    private CustomerSession $customerSession;

    /**
     * @var AdminSession
     */
    private AdminSession $adminSession;

    /**
     * Constructor
     *
     * @param Context $context
     * @param Logger $logger
     * @param LogEventFactory $logEventFactory
     * @param LogEventResource $logEventResource
     * @param SerializerInterface $serializer
     * @param CustomerSession $customerSession
     * @param AdminSession $adminSession
     */
    public function __construct(
        Context $context,
        Logger $logger,
        LogEventFactory $logEventFactory,
        LogEventResource $logEventResource,
        SerializerInterface $serializer,
        CustomerSession $customerSession,
        AdminSession $adminSession
    ) {
        parent::__construct($context);
        $this->logger = $logger;
        $this->logEventFactory = $logEventFactory;
        $this->logEventResource = $logEventResource;
        $this->serializer = $serializer;
        $this->customerSession = $customerSession;
        $this->adminSession = $adminSession;
    }

    /**
     * Log configurator opened
     *
     * @param int $productId
     * @param array $context
     * @return void
     */
    public function logConfiguratorOpened(int $productId, array $context = []): void
    {
        $context['product_id'] = $productId;
        $this->logEvent(
            'info',
            'configurator_opened',
            sprintf('Configurator opened for product %d', $productId),
            $context
        );
    }

    /**
     * Log step view
     *
     * @param string $stepName
     * @param array $context
     * @return void
     */
    public function logStepView(string $stepName, array $context = []): void
    {
        $context['step_name'] = $stepName;
        $this->logEvent(
            'info',
            'step_view',
            sprintf('Step "%s" viewed', $stepName),
            $context
        );
    }

    /**
     * Log validation error
     *
     * @param string $field
     * @param string $error
     * @param array $context
     * @return void
     */
    public function logValidationError(string $field, string $error, array $context = []): void
    {
        $context['field'] = $field;
        $context['error'] = $error;
        $this->logEvent(
            'warning',
            'validation_error',
            sprintf('Validation error on field "%s": %s', $field, $error),
            $context
        );
    }

    /**
     * Log price calculation
     *
     * @param array $config
     * @param array $priceBreakdown
     * @param array $context
     * @return void
     */
    public function logPriceCalculation(array $config, array $priceBreakdown, array $context = []): void
    {
        $context['config'] = $config;
        $context['price_breakdown'] = $priceBreakdown;
        $context['total_price'] = $priceBreakdown['total_price'] ?? 0;
        
        $this->logEvent(
            'info',
            'price_calculated',
            sprintf('Price calculated: %.2f', $priceBreakdown['total_price'] ?? 0),
            $context
        );
    }

    /**
     * Log add to cart
     *
     * @param int $productId
     * @param int $quoteItemId
     * @param array $config
     * @param float $price
     * @param array $context
     * @return void
     */
    public function logAddToCart(
        int $productId,
        int $quoteItemId,
        array $config,
        float $price,
        array $context = []
    ): void {
        $context['product_id'] = $productId;
        $context['quote_item_id'] = $quoteItemId;
        $context['config'] = $config;
        $context['price'] = $price;
        
        $this->logEvent(
            'info',
            'add_to_cart',
            sprintf('Product %d added to cart with prescription (item %d, price: %.2f)', 
                $productId, $quoteItemId, $price),
            $context
        );
    }

    /**
     * Log file upload
     *
     * @param int $attachmentId
     * @param string $filename
     * @param int $fileSize
     * @param array $context
     * @return void
     */
    public function logFileUpload(
        int $attachmentId,
        string $filename,
        int $fileSize,
        array $context = []
    ): void {
        $context['attachment_id'] = $attachmentId;
        $context['filename'] = $filename;
        $context['file_size'] = $fileSize;
        
        $this->logEvent(
            'info',
            'file_uploaded',
            sprintf('File "%s" uploaded (%d KB, attachment %d)', 
                $filename, round($fileSize / 1024), $attachmentId),
            $context
        );
    }

    /**
     * Log configuration edit
     *
     * @param int $itemId
     * @param string $itemType (quote_item|order_item)
     * @param array $oldConfig
     * @param array $newConfig
     * @param array $context
     * @return void
     */
    public function logConfigurationEdit(
        int $itemId,
        string $itemType,
        array $oldConfig,
        array $newConfig,
        array $context = []
    ): void {
        $context['item_id'] = $itemId;
        $context['item_type'] = $itemType;
        $context['old_config'] = $oldConfig;
        $context['new_config'] = $newConfig;
        $context['changes'] = $this->detectChanges($oldConfig, $newConfig);
        
        $this->logEvent(
            'info',
            'config_edited',
            sprintf('Configuration edited for %s %d', $itemType, $itemId),
            $context
        );
    }

    /**
     * Log error
     *
     * @param string $message
     * @param \Exception $exception
     * @param array $context
     * @return void
     */
    public function logError(string $message, \Exception $exception, array $context = []): void
    {
        $context['exception'] = $exception->getMessage();
        $context['trace'] = $exception->getTraceAsString();
        $context['code'] = $exception->getCode();
        
        $this->logEvent(
            'error',
            'error',
            $message . ': ' . $exception->getMessage(),
            $context
        );
    }

    /**
     * Log generic event
     *
     * @param string $level (info|warning|error|debug)
     * @param string $eventType
     * @param string $message
     * @param array $context
     * @return void
     */
    public function logEvent(
        string $level,
        string $eventType,
        string $message,
        array $context = []
    ): void {
        // Add automatic context
        $context = $this->enrichContext($context);

        // Log to file (Monolog)
        $logMethod = $level === 'warning' ? 'warning' : ($level === 'error' ? 'error' : ($level === 'debug' ? 'debug' : 'info'));
        $this->logger->{$logMethod}($message, $context);

        // Log to database
        try {
            $logEvent = $this->logEventFactory->create();
            $logEvent->setLevel($level);
            $logEvent->setEventType($eventType);
            $logEvent->setMessage($message);
            $logEvent->setPayload($this->serializer->serialize($context));
            
            if (isset($context['customer_id'])) {
                $logEvent->setCustomerId((int) $context['customer_id']);
            }
            
            if (isset($context['user_id'])) {
                $logEvent->setUserId((int) $context['user_id']);
            }

            $this->logEventResource->save($logEvent);
        } catch (\Exception $e) {
            // Fail silently to avoid breaking functionality
            $this->logger->error('Failed to save log event to database: ' . $e->getMessage());
        }
    }

    /**
     * Enrich context with automatic data
     *
     * @param array $context
     * @return array
     */
    private function enrichContext(array $context): array
    {
        // Add customer ID if logged in
        if ($this->customerSession->isLoggedIn() && !isset($context['customer_id'])) {
            $context['customer_id'] = $this->customerSession->getCustomerId();
        }

        // Add admin user ID if in admin area
        if ($this->adminSession->isLoggedIn() && !isset($context['user_id'])) {
            $context['user_id'] = $this->adminSession->getUser()->getId();
        }

        // Add timestamp
        if (!isset($context['timestamp'])) {
            $context['timestamp'] = date('Y-m-d H:i:s');
        }

        // Add request URI
        if (!isset($context['request_uri'])) {
            $context['request_uri'] = $this->_request->getRequestUri();
        }

        return $context;
    }

    /**
     * Detect changes between configurations
     *
     * @param array $oldConfig
     * @param array $newConfig
     * @return array
     */
    private function detectChanges(array $oldConfig, array $newConfig): array
    {
        $changes = [];

        // Compare prescription values
        if (isset($oldConfig['prescription']) && isset($newConfig['prescription'])) {
            foreach (['od', 'oi'] as $eye) {
                if (isset($oldConfig['prescription'][$eye]) && isset($newConfig['prescription'][$eye])) {
                    foreach (['sph', 'cyl', 'axis', 'add', 'pd'] as $field) {
                        $oldValue = $oldConfig['prescription'][$eye][$field] ?? null;
                        $newValue = $newConfig['prescription'][$eye][$field] ?? null;
                        
                        if ($oldValue !== $newValue) {
                            $changes["prescription.{$eye}.{$field}"] = [
                                'old' => $oldValue,
                                'new' => $newValue
                            ];
                        }
                    }
                }
            }
        }

        // Compare other fields
        foreach (['use_type', 'lens', 'treatments'] as $field) {
            $oldValue = $oldConfig[$field] ?? null;
            $newValue = $newConfig[$field] ?? null;
            
            if ($oldValue !== $newValue) {
                $changes[$field] = [
                    'old' => $oldValue,
                    'new' => $newValue
                ];
            }
        }

        return $changes;
    }
}
