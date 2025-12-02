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

use Magento\Framework\Logger\Handler\Base;
use Monolog\Logger as MonologLogger;

/**
 * Log handler for Prescription Module
 */
class Handler extends Base
{
    /**
     * Logging level
     *
     * @var int
     */
    protected $loggerType = MonologLogger::INFO;

    /**
     * Log file name
     *
     * @var string
     */
    protected $fileName = '/var/log/powerline_presc.log';
}
