<?php
declare(strict_types=1);

/**
 * Powerline PrescripcionModule - Customer Prescription View Controller
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
use Magento\Sales\Api\OrderItemRepositoryInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Controller\Result\RedirectFactory;
use Magento\Framework\Message\ManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Class View
 * 
 * Controller for viewing prescription details
 */
class View implements HttpGetActionInterface
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
     * @var OrderItemRepositoryInterface
     */
    private OrderItemRepositoryInterface $orderItemRepository;

    /**
     * @var OrderRepositoryInterface
     */
    private OrderRepositoryInterface $orderRepository;

    /**
     * @var RedirectFactory
     */
    private RedirectFactory $resultRedirectFactory;

    /**
     * @var ManagerInterface
     */
    private ManagerInterface $messageManager;

    /**
     * @var LoggerInterface
     */
    private LoggerInterface $logger;

    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * Constructor
     *
     * @param Context $context
     * @param PageFactory $resultPageFactory
     * @param Session $customerSession
     * @param OrderItemRepositoryInterface $orderItemRepository
     * @param OrderRepositoryInterface $orderRepository
     * @param RedirectFactory $resultRedirectFactory
     * @param ManagerInterface $messageManager
     * @param LoggerInterface $logger
     */
    public function __construct(
        Context $context,
        PageFactory $resultPageFactory,
        Session $customerSession,
        OrderItemRepositoryInterface $orderItemRepository,
        OrderRepositoryInterface $orderRepository,
        RedirectFactory $resultRedirectFactory,
        ManagerInterface $messageManager,
        LoggerInterface $logger
    ) {
        $this->resultPageFactory = $resultPageFactory;
        $this->customerSession = $customerSession;
        $this->orderItemRepository = $orderItemRepository;
        $this->orderRepository = $orderRepository;
        $this->resultRedirectFactory = $resultRedirectFactory;
        $this->messageManager = $messageManager;
        $this->logger = $logger;
        $this->request = $context->getRequest();
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
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('customer/account/login');
        }

        $itemId = (int) $this->request->getParam('item_id');

        if (!$itemId) {
            $this->messageManager->addErrorMessage(__('Invalid prescription item.'));
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('presc/customer/history');
        }

        try {
            // Load order item
            $orderItem = $this->orderItemRepository->get($itemId);
            $order = $this->orderRepository->get($orderItem->getOrderId());

            // Verify customer owns this order
            $customerId = $this->customerSession->getCustomerId();
            if ($order->getCustomerId() != $customerId) {
                $this->messageManager->addErrorMessage(__('You do not have permission to view this prescription.'));
                $resultRedirect = $this->resultRedirectFactory->create();
                return $resultRedirect->setPath('presc/customer/history');
            }

            // Create result page
            $resultPage = $this->resultPageFactory->create();
            $resultPage->getConfig()->getTitle()->set(__('Prescription Details'));

            return $resultPage;

        } catch (\Exception $e) {
            $this->logger->error('Customer prescription view error: ' . $e->getMessage());
            $this->messageManager->addErrorMessage(__('Unable to load prescription details.'));
            $resultRedirect = $this->resultRedirectFactory->create();
            return $resultRedirect->setPath('presc/customer/history');
        }
    }
}
