<?php
declare(strict_types=1);

namespace Powerline\PrescripcionModule\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Psr\Log\LoggerInterface;

/**
 * Observer para ajustar el precio de items con prescripción
 * antes de que Magento calcule los impuestos
 */
class AdjustPriceBeforeTax implements ObserverInterface
{
    public function __construct(
        private readonly LoggerInterface $logger
    ) {}

    /**
     * Ajustar precio para evitar doble cálculo de IVA
     *
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer): void
    {
        /** @var \Magento\Quote\Model\Quote $quote */
        $quote = $observer->getEvent()->getQuote();
        
        if (!$quote) {
            return;
        }

        foreach ($quote->getAllVisibleItems() as $item) {
            // Solo procesar items que tienen precio personalizado de prescripción
            $customPrice = $item->getCustomPrice();
            
            if ($customPrice && $customPrice > 0) {
                // Verificar si tiene configuración de prescripción
                $additionalOptions = $item->getOptionByCode('additional_options');
                
                if ($additionalOptions) {
                    // Este item tiene prescripción, el precio YA incluye IVA
                    // Calcular el precio base sin IVA (21%)
                    $taxRate = 0.21;
                    $priceExclTax = $customPrice / (1 + $taxRate);
                    
                    // Establecer el precio sin IVA para que Magento lo calcule correctamente
                    $item->setCustomPrice($priceExclTax);
                    $item->setOriginalCustomPrice($priceExclTax);
                    
                    // NO establecer RowTotal aquí, dejar que Magento lo calcule
                    
                    $this->logger->info('Adjusted prescription item price before tax calculation', [
                        'item_id' => $item->getId(),
                        'original_price_incl_tax' => $customPrice,
                        'price_excl_tax' => $priceExclTax
                    ]);
                }
            }
        }
    }
}
