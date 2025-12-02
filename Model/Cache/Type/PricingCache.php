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

namespace Powerline\PrescripcionModule\Model\Cache\Type;

use Magento\Framework\App\Cache\Type\FrontendPool;
use Magento\Framework\Cache\Frontend\Decorator\TagScope;

/**
 * Prescription pricing cache type
 */
class PricingCache extends TagScope
{
    /**
     * Cache type code unique among all cache types
     */
    public const TYPE_IDENTIFIER = 'powerline_prescription_pricing';

    /**
     * Cache tag used to distinguish the cache type from all other cache
     */
    public const CACHE_TAG = 'POWERLINE_PRESCRIPTION_PRICING';

    /**
     * @param FrontendPool $cacheFrontendPool
     */
    public function __construct(FrontendPool $cacheFrontendPool)
    {
        parent::__construct(
            $cacheFrontendPool->get(self::TYPE_IDENTIFIER),
            self::CACHE_TAG
        );
    }
}
