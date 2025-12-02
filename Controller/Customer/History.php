<?php
declare(strict_types=1);

/**
 * Powerline PrescripcionModule - Customer Prescription History Controller
 *
 * @category  Powerline
 * @package   Powerline_PrescripcionModule
 * @author    Powerline Development Team
 * @copyright Copyright (c) 2025 Powerline
 */

namespace Powerline\PrescripcionModule\Controller\Customer;

use Magento\Framework\App\Action\HttpGetActionInterface;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Customer\Model\Session;

/**
 * Class History
 * 
 * Controller for displaying customer's prescription history
 */
class History implements HttpGetActionInterface
{
    /**
     * @var PageFactory
     */
    private PageFactory $resultPageFactory;

    /**
     * @var Session
     */
    private Session $customerSession;

    /**
     * @var \Magento\Framework\App\Response\RedirectInterface
     */
    private $redirect;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Session $customerSession
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Session $customerSession
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->customerSession = $customerSession;
        $this->redirect = $context->getRedirect();
    }

    /**
     * Execute action
     *
     * @return \Magento\Framework\Controller\ResultInterface
     */
    public function execute()
    {
        // Check if customer is logged in
        if (!$this->customerSession->isLoggedIn()) {
            $this->customerSession->setAfterAuthUrl($this->_url->getUrl('presc/customer/history'));
            $this->customerSession->authenticate();
            return;
        }

        /** @var \Magento\Framework\View\Result\Page $resultPage */
        $resultPage = $this->resultPageFactory->create();
        $resultPage->getConfig()->getTitle()->set(__('My Prescription Lenses'));

        // Add to customer navigation
        if ($block = $resultPage->getLayout()->getBlock('customer_account_navigation')) {
            $block->setActive('presc/customer/history');
        }

        return $resultPage;
    }
}
