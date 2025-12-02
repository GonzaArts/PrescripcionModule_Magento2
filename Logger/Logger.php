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

namespace Powerline\PrescripcionModule\Logger;

use Monolog\Logger as MonologLogger;

/**
 * Custom logger for Prescription Module
 */
class Logger extends MonologLogger
{
    /**
     * Log context keys
     */
    public const CONTEXT_CUSTOMER_ID = 'customer_id';
    public const CONTEXT_QUOTE_ID = 'quote_id';
    public const CONTEXT_ORDER_ID = 'order_id';
    public const CONTEXT_PRODUCT_ID = 'product_id';
    public const CONTEXT_CONFIG = 'config';
    public const CONTEXT_PRICE = 'price';
    public const CONTEXT_ERROR = 'error';
}
