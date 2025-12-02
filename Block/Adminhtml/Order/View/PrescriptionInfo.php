<?php
declare(strict_types=1);

namespace Powerline\PrescripcionModule\Block\Adminhtml\Order\View;

use Magento\Backend\Block\Template;
use Magento\Backend\Block\Template\Context;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\Data\OrderItemInterface;
use Magento\Framework\Registry;
use Powerline\PrescripcionModule\Model\ResourceModel\Attachment\CollectionFactory as AttachmentCollectionFactory;

/**
 * Block para mostrar información de prescripción en la vista de pedido del admin
 */
class PrescriptionInfo extends Template
{
    protected $_template = 'Powerline_PrescripcionModule::order/view/prescription_info.phtml';

    public function __construct(
        Context $context,
        private readonly Registry $registry,
        private readonly AttachmentCollectionFactory $attachmentCollectionFactory,
        array $data = []
    ) {
        parent::__construct($context, $data);
    }

    /**
     * Get current order
     *
     * @return OrderInterface|null
     */
    public function getOrder(): ?OrderInterface
    {
        return $this->registry->registry('current_order');
    }

    /**
     * Get order items with prescription data
     *
     * @return array
     */
    public function getPrescriptionItems(): array
    {
        $order = $this->getOrder();
        if (!$order) {
            return [];
        }

        $prescriptionItems = [];

        /** @var OrderItemInterface $item */
        foreach ($order->getAllVisibleItems() as $item) {
            $productOptions = $item->getProductOptions();
            
            if (isset($productOptions['powerline_presc'])) {
                $prescriptionItems[] = [
                    'item' => $item,
                    'prescription' => $productOptions['powerline_presc'],
                    'additional_options' => $productOptions['additional_options'] ?? []
                ];
            }
        }

        return $prescriptionItems;
    }

    /**
     * Check if order has prescription items
     *
     * @return bool
     */
    public function hasPrescriptionItems(): bool
    {
        return !empty($this->getPrescriptionItems());
    }

    /**
     * Get attachment for order item
     *
     * @param int $orderItemId
     * @return \Powerline\PrescripcionModule\Model\Attachment|null
     */
    public function getAttachment(int $orderItemId): ?\Powerline\PrescripcionModule\Model\Attachment
    {
        $collection = $this->attachmentCollectionFactory->create();
        $collection->addFieldToFilter('order_item_id', $orderItemId);
        
        return $collection->getFirstItem()->getId() ? $collection->getFirstItem() : null;
    }

    /**
     * Get download URL for attachment
     *
     * @param int $attachmentId
     * @return string
     */
    public function getDownloadUrl(int $attachmentId): string
    {
        return $this->getUrl('presc/attachment/download', ['id' => $attachmentId]);
    }

    /**
     * Format prescription value
     *
     * @param mixed $value
     * @return string
     */
    public function formatValue($value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
        }
        
        return (string) $value;
    }

    /**
     * Format prescription data for display
     *
     * @param array $prescData
     * @return array
     */
    public function formatPrescriptionForDisplay(array $prescData): array
    {
        $formatted = [];

        // Tipo de uso
        if (!empty($prescData['use_type'])) {
            $useTypeLabels = [
                'monofocal' => 'Monofocal',
                'progressive' => 'Progresivo',
                'no_prescription' => 'Sin Graduación'
            ];
            $formatted['Tipo de Uso'] = $useTypeLabels[$prescData['use_type']] ?? $prescData['use_type'];
        }

        // Graduación OD
        if (!empty($prescData['prescription'])) {
            $presc = $prescData['prescription'];
            
            if (isset($presc['od_esf']) || isset($presc['od_cil']) || isset($presc['od_axis'])) {
                $odParts = [];
                $odParts[] = 'ESF: ' . ($presc['od_esf'] ?? '-');
                $odParts[] = 'CIL: ' . ($presc['od_cil'] ?? '-');
                $odParts[] = 'EJE: ' . ($presc['od_axis'] ?? '-');
                if (!empty($presc['od_add'])) {
                    $odParts[] = 'ADD: ' . $presc['od_add'];
                }
                $formatted['OD (Ojo Derecho)'] = implode(', ', $odParts);
            }
            
            // Graduación OI
            if (isset($presc['oi_esf']) || isset($presc['oi_cil']) || isset($presc['oi_axis'])) {
                $oiParts = [];
                $oiParts[] = 'ESF: ' . ($presc['oi_esf'] ?? '-');
                $oiParts[] = 'CIL: ' . ($presc['oi_cil'] ?? '-');
                $oiParts[] = 'EJE: ' . ($presc['oi_axis'] ?? '-');
                if (!empty($presc['oi_add'])) {
                    $oiParts[] = 'ADD: ' . $presc['oi_add'];
                }
                $formatted['OI (Ojo Izquierdo)'] = implode(', ', $oiParts);
            }
            
            // PD
            if (!empty($presc['pd_right']) && !empty($presc['pd_left'])) {
                $formatted['DP (Distancia Pupilar)'] = 'Derecho: ' . $presc['pd_right'] . ', Izquierdo: ' . $presc['pd_left'];
            } elseif (!empty($presc['pd'])) {
                $formatted['DP (Distancia Pupilar)'] = $presc['pd'];
            }
        }

        // Tipo de cristal
        if (!empty($prescData['lens'])) {
            $lens = $prescData['lens'];
            $lensDetails = [];
            
            if (!empty($lens['type'])) {
                $typeLabels = [
                    'transparent' => 'Transparente',
                    'digital_protection' => 'Protección Digital',
                    'tinted' => 'Tintado',
                    'photochromic' => 'Fotocromático'
                ];
                $lensDetails[] = 'Tipo: ' . ($typeLabels[$lens['type']] ?? ucfirst($lens['type']));
            }
            
            if (!empty($lens['brand'])) {
                $brandLabels = [
                    'own' => 'Marca Propia',
                    'essilor' => 'Essilor',
                    'zeiss' => 'Zeiss'
                ];
                $lensDetails[] = 'Marca: ' . ($brandLabels[$lens['brand']] ?? ucfirst($lens['brand']));
            }
            
            if (!empty($lens['index'])) {
                $lensDetails[] = 'Índice: ' . $lens['index'];
            }
            
            if (!empty($lensDetails)) {
                $formatted['Tipo de Cristal'] = implode(', ', $lensDetails);
            }
        }

        // Tintado
        if (!empty($prescData['tinted_category'])) {
            $formatted['Categoría Tintado'] = $prescData['tinted_category'];
            
            if (!empty($prescData['tinted_options'])) {
                $opts = $prescData['tinted_options'];
                $tintedDetails = [];
                
                if (!empty($opts['intensity'])) {
                    $tintedDetails[] = 'Intensidad: ' . $opts['intensity'];
                }
                if (!empty($opts['color'])) {
                    $tintedDetails[] = 'Color: ' . $opts['color'];
                }
                
                if (!empty($tintedDetails)) {
                    $formatted['Opciones Tintado'] = implode(', ', $tintedDetails);
                }
            }
        }

        // Archivo adjunto
        if (!empty($prescData['attachment_filename'])) {
            $formatted['Receta Adjunta'] = $prescData['attachment_filename'];
        }

        // Precio total
        if (!empty($prescData['prices']['total'])) {
            $formatted['Precio Total Lentes'] = '€' . number_format($prescData['prices']['total'], 2, ',', '.');
        }

        return $formatted;
    }
}

