<?php
declare(strict_types=1);

/**
 * Powerline PrescripcionModule - Customer Prescription View Block
 *
 * @category  Powerline
 * @package   Powerline_PrescripcionModule
 * @author    Powerline Development Team
 * @copyright Copyright (c) 2025 Powerline
 */

namespace Powerline\PrescripcionModule\Block\Customer;

use Powerline\PrescripcionModule\Block\Adminhtml\Prescription\View as AdminView;

/**
 * Class View
 * 
 * Block for displaying prescription details to customer
 * Extends admin view block to reuse formatting logic
 */
class View extends AdminView
{
    /**
     * Get back URL (customer history instead of order view)
     *
     * @return string
     */
    public function getBackUrl(): string
    {
        return $this->getUrl('presc/customer/history');
    }

    /**
     * Check if customer can edit prescription
     * 
     * @return bool
     */
    public function canEdit(): bool
    {
        return false; // Customers cannot edit, only admins
    }
}
