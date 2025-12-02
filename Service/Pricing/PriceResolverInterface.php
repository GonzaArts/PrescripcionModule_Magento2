<?php

declare(strict_types=1);

namespace Powerline\PrescripcionModule\Service\Pricing;

use Powerline\PrescripcionModule\Api\Data\ConfigDtoInterface;
use Powerline\PrescripcionModule\Api\Data\PriceBreakdownDtoInterface;

/**
 * Price Resolver Interface
 *
 * Base interface for Chain of Responsibility pattern in pricing
 */
interface PriceResolverInterface
{
    /**
     * Process pricing for the given configuration
     *
     * @param ConfigDtoInterface $config
     * @param PriceBreakdownDtoInterface $priceBreakdown
     * @return PriceBreakdownDtoInterface
     */
    public function resolve(
        ConfigDtoInterface $config,
        PriceBreakdownDtoInterface $priceBreakdown
    ): PriceBreakdownDtoInterface;
}
