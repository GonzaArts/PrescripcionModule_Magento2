<?php
declare(strict_types=1);

namespace Powerline\PrescripcionModule\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item as QuoteItem;
use Powerline\PrescripcionModule\Api\AttachmentRepositoryInterface;
use Powerline\PrescripcionModule\Model\ResourceModel\Attachment\CollectionFactory as AttachmentCollectionFactory;
use Psr\Log\LoggerInterface;

/**
 * Observer para transferir datos de prescripción del Quote al Order
 * 
 * Se ejecuta en el evento sales_model_service_quote_submit_before
 * Basado en la lógica de IPrescription
 */
class SavePrescriptionDataToOrder implements ObserverInterface
{
    public function __construct(
        private readonly AttachmentRepositoryInterface $attachmentRepository,
        private readonly LoggerInterface $logger,
        private readonly AttachmentCollectionFactory $attachmentCollectionFactory
    ) {}

    /**
     * Transfer prescription data from quote to order
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        try {
            /** @var Quote $quote */
            $quote = $observer->getQuote();
            
            /** @var OrderInterface $order */
            $order = $observer->getOrder();

            if (!$quote || !$order) {
                return;
            }

            $this->logger->info('Starting prescription data transfer', [
                'quote_id' => $quote->getId(),
                'order_id' => $order->getId()
            ]);

            // Iterar sobre todos los order items
            foreach ($order->getItems() as $orderItem) {
                /** @var OrderItemInterface $orderItem */
                
                // Obtener product options
                $options = $orderItem->getProductOptions();
                
                if (empty($options)) {
                    continue;
                }

                // Verificar si tiene datos de prescripción en info_buyRequest
                if (!isset($options['info_buyRequest']['powerline_presc'])) {
                    continue;
                }

                $prescData = $options['info_buyRequest']['powerline_presc'];
                
                $this->logger->info('Found prescription data in order item', [
                    'order_item_id' => $orderItem->getItemId(),
                    'sku' => $orderItem->getSku(),
                    'presc_data_keys' => array_keys($prescData),
                    'has_attachment_hash' => isset($prescData['attachment_hash']),
                    'attachment_hash_value' => $prescData['attachment_hash'] ?? 'NOT SET',
                    'attachment_filename' => $prescData['attachment_filename'] ?? 'NOT SET'
                ]);

                // SOLUCIÓN SIMPLE: Guardar directamente en powerline_presc
                // El Block de admin lee de $options['powerline_presc']
                $options['powerline_presc'] = $prescData;
                
                $orderItem->setProductOptions($options);

                $this->logger->info('Prescription data saved to order item', [
                    'order_item_id' => $orderItem->getItemId()
                ]);

                // Actualizar attachments si existen (soportar 'hash' o 'attachment_hash')
                $attachmentHash = $prescData['attachment_hash'] ?? $prescData['hash'] ?? null;
                
                if (!empty($attachmentHash)) {
                    $this->updateAttachments(
                        $quote->getId(),
                        $order->getId(),
                        $orderItem->getQuoteItemId(),
                        $orderItem->getItemId(),
                        $attachmentHash,
                        $prescData['attachment_filename'] ?? $prescData['filename'] ?? 'prescription.pdf'
                    );
                }
            }

            $this->logger->info('Prescription data transfer completed');

        } catch (\Exception $e) {
            $this->logger->error('Error transferring prescription data', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
        }
    }

    /**
     * Update attachments with order information
     *
     * @param int $quoteId
     * @param int $orderId
     * @param int $quoteItemId
     * @param int $orderItemId
     * @param string $hash
     * @param string $filename
     * @return void
     */
    private function updateAttachments(
        int $quoteId,
        int $orderId,
        int $quoteItemId,
        int $orderItemId,
        string $hash,
        string $filename
    ): void {
        try {
            $this->logger->info('Attempting to update attachments', [
                'hash' => $hash,
                'filename' => $filename,
                'quote_id' => $quoteId,
                'order_id' => $orderId,
                'order_item_id' => $orderItemId
            ]);

            // Buscar el attachment por hash y quote_item_id usando Collection
            $attachmentCollection = $this->attachmentCollectionFactory->create();
            
            $attachmentCollection
                ->addFieldToFilter('hash', $hash)
                ->addFieldToFilter('quote_id', $quoteId)
                ->addFieldToFilter('quote_item_id', $quoteItemId);

            $found = false;

            /** @var \Powerline\PrescripcionModule\Model\Attachment $attachment */
            foreach ($attachmentCollection as $attachment) {
                $attachment->setData('order_id', $orderId);
                $attachment->setData('order_item_id', $orderItemId);
                
                // Extender retention period a 2 años después del pedido
                $attachment->setData('retention_until', date('Y-m-d H:i:s', strtotime('+2 years')));
                
                $this->attachmentRepository->save($attachment);
                $found = true;

                $this->logger->info('✅ Attachment updated with order information', [
                    'attachment_id' => $attachment->getId(),
                    'hash' => $hash,
                    'order_id' => $orderId,
                    'order_item_id' => $orderItemId
                ]);
            }

            if (!$found) {
                $this->logger->warning('⚠️  Attachment not found in database', [
                    'hash' => $hash,
                    'quote_id' => $quoteId,
                    'quote_item_id' => $quoteItemId,
                    'message' => 'The file was uploaded but not saved to database. It may not have been properly linked when added to cart.'
                ]);
            }

        } catch (\Exception $e) {
            $this->logger->error('Error updating attachments with order information', [
                'quote_id' => $quoteId,
                'order_id' => $orderId,
                'exception' => $e->getMessage()
            ]);
        }
    }
}
