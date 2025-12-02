<?php
declare(strict_types=1);

namespace Powerline\PrescripcionModule\Plugin\Quote;

use Magento\Quote\Model\Quote;
use Magento\Quote\Model\Quote\Item;
use Magento\Framework\Serialize\SerializerInterface;
use Psr\Log\LoggerInterface;

class CartItemProcessorPlugin
{
    private const OPTION_KEY = 'powerline_presc';
    private static $processing = [];
    
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly LoggerInterface $logger
    ) {}

    public function afterAddProduct(
        Quote $subject,
        $result,
        $product,
        $request = null
    ) {
        if (!$result instanceof Item) {
            $this->logger->debug('Result is not a Quote Item, skipping plugin');
            return $result;
        }

        // Use spl_object_hash para items sin ID todavía
        $itemKey = ($result->getId() ?: spl_object_hash($result)) . '_' . $product->getId();
        
        if (isset(self::$processing[$itemKey])) {
            $this->logger->debug('Already processing this item, skipping to avoid loop', [
                'item_key' => $itemKey
            ]);
            return $result;
        }
        
        self::$processing[$itemKey] = true;

        try {
            if (!$request instanceof \Magento\Framework\DataObject) {
                if (is_array($request)) {
                    $request = new \Magento\Framework\DataObject($request);
                } else {
                    $this->logger->debug('Request is not DataObject or array, skipping plugin');
                    return $result;
                }
            }

            $prescConfig = $request->getData(self::OPTION_KEY);
            
            if (!$prescConfig || !is_array($prescConfig)) {
                $this->logger->debug('No prescription config found in request, skipping plugin');
                return $result;
            }

            $this->logger->info('Processing prescription config for cart item', [
                'product_id' => $product->getId(),
                'item_id' => $result->getId()
            ]);

            $additionalOptions = $this->buildAdditionalOptions($prescConfig);
            
            if (!empty($additionalOptions)) {
                $result->addOption([
                    'code' => 'additional_options',
                    'value' => $this->serializer->serialize($additionalOptions)
                ]);
                
                $this->logger->info('Added additional_options to cart item', [
                    'options_count' => count($additionalOptions)
                ]);
            }

            $customPrice = $request->getData('powerline_presc_price');
            if ($customPrice && $customPrice > 0) {
                // El precio que viene del frontend YA incluye IVA
                // Determinar el IVA según la categoría del producto
                $taxRate = $this->getTaxRateForProduct($product);
                
                // Convertir el precio con IVA a precio sin IVA
                $priceExclTax = $customPrice / (1 + $taxRate);
                
                // Establecer el precio SIN IVA como precio personalizado
                // Magento aplicará el IVA automáticamente
                $result->setCustomPrice($priceExclTax);
                $result->setOriginalCustomPrice($priceExclTax);
                $result->getProduct()->setIsSuperMode(true);
                
                $this->logger->info('Set custom price for cart item (converted from tax-included price)', [
                    'product_id' => $product->getId(),
                    'original_price_incl_tax' => $customPrice,
                    'price_excl_tax_set' => $priceExclTax,
                    'tax_rate' => ($taxRate * 100) . '%'
                ]);
            }

        } catch (\Exception $e) {
            $this->logger->error('Error in CartItemProcessorPlugin', [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            // No relanzar la excepción para no romper el flujo de añadir al carrito
        } finally {
            unset(self::$processing[$itemKey]);
        }

        return $result;
    }

    private function buildAdditionalOptions(array $config): array
    {
        $options = [];

        // 1. TIPO DE USO (Usage Type)
        if (!empty($config['use_type'])) {
            $useTypeLabels = [
                'monofocal' => 'Monofocal',
                'progressive' => 'Progresivo',
                'no_prescription' => 'Sin Graduación'
            ];
            $options[] = [
                'label' => 'Tipo de Uso',
                'value' => $useTypeLabels[$config['use_type']] ?? strtoupper($config['use_type'])
            ];
        }

        // 2. TIPO DE PROGRESIVO (only if progressive)
        if (!empty($config['progressive_vision'])) {
            $progressiveLabels = [
                'normal' => 'Visión Normal',
                'wide' => 'Visión Amplia',
                'max' => 'Máxima Visión'
            ];
            $options[] = [
                'label' => 'Tipo Progresivo',
                'value' => $progressiveLabels[$config['progressive_vision']] ?? $config['progressive_vision']
            ];
        }

        // 3. GRADUACIÓN / PRESCRIPCIÓN (if not "Sin Graduación")
        if (!empty($config['prescription']) && $config['use_type'] !== 'no_prescription') {
            $presc = $config['prescription'];
            
            // OD (Ojo Derecho / Right Eye)
            $odParts = [];
            $odParts[] = 'ESF: ' . ($presc['od_esf'] ?? '-');
            $odParts[] = 'CIL: ' . ($presc['od_cil'] ?? '-');
            $odParts[] = 'EJE: ' . ($presc['od_axis'] ?? '-');
            if ($config['use_type'] === 'progressive' && !empty($presc['od_add'])) {
                $odParts[] = 'ADD: ' . $presc['od_add'];
            }
            
            $options[] = [
                'label' => 'OD (Ojo Derecho)',
                'value' => implode(', ', $odParts)
            ];
            
            // OI (Ojo Izquierdo / Left Eye)
            $oiParts = [];
            $oiParts[] = 'ESF: ' . ($presc['oi_esf'] ?? '-');
            $oiParts[] = 'CIL: ' . ($presc['oi_cil'] ?? '-');
            $oiParts[] = 'EJE: ' . ($presc['oi_axis'] ?? '-');
            if ($config['use_type'] === 'progressive' && !empty($presc['oi_add'])) {
                $oiParts[] = 'ADD: ' . $presc['oi_add'];
            }
            
            $options[] = [
                'label' => 'OI (Ojo Izquierdo)',
                'value' => implode(', ', $oiParts)
            ];
            
            // PD (Distancia Pupilar)
            if (!empty($presc['pd_right']) && !empty($presc['pd_left'])) {
                $pdValue = 'Derecho: ' . $presc['pd_right'] . ', Izquierdo: ' . $presc['pd_left'];
            } else {
                $pdValue = $presc['pd'] ?? '-';
            }
            
            $options[] = [
                'label' => 'DP (Distancia Pupilar)',
                'value' => $pdValue
            ];
        }

        // 4. TIPO DE CRISTAL (Lens Type)
        if (!empty($config['lens'])) {
            $lens = $config['lens'];
            $lensDetails = [];
            
            // Lens Type
            if (!empty($lens['type'])) {
                $typeLabels = [
                    'transparent' => 'Transparente',
                    'digital_protection' => 'Protección Digital',
                    'tinted' => 'Tintado',
                    'photochromic' => 'Fotocromático'
                ];
                $lensType = $typeLabels[$lens['type']] ?? ucfirst($lens['type']);
                $lensDetails[] = 'Tipo: ' . $lensType;
            }
            
            // Lens Brand
            if (!empty($lens['brand'])) {
                $brandLabels = [
                    'own' => 'Marca Propia',
                    'essilor' => 'Essilor',
                    'zeiss' => 'Zeiss'
                ];
                $lensDetails[] = 'Marca: ' . ($brandLabels[$lens['brand']] ?? ucfirst($lens['brand']));
            }
            
            // Lens Index
            if (!empty($lens['index'])) {
                $lensDetails[] = 'Índice: ' . $lens['index'];
            }
            
            if (!empty($lensDetails)) {
                $options[] = [
                    'label' => 'Tipo de Cristal',
                    'value' => implode(', ', $lensDetails)
                ];
            }
        }

        // 5. TINTADO (if tinted was selected)
        if (!empty($config['tinted_category'])) {
            $categoryLabels = [
                'basicos' => 'Básicos',
                'degradados' => 'Degradados',
                'espejados' => 'Espejados',
                'polarizados' => 'Polarizados'
            ];
            
            $tintedDetails = [];
            $tintedDetails[] = 'Categoría: ' . ($categoryLabels[$config['tinted_category']] ?? $config['tinted_category']);
            
            if (!empty($config['tinted_options'])) {
                $opts = $config['tinted_options'];
                
                if (!empty($opts['intensity'])) {
                    $tintedDetails[] = 'Intensidad: ' . $opts['intensity'];
                }
                if (!empty($opts['color'])) {
                    $colorLabels = [
                        'gris' => 'Gris',
                        'marron' => 'Marrón',
                        'verde' => 'Verde',
                        'verde-oscuro' => 'Verde Oscuro',
                        'gris-blanco' => 'Gris/Blanco',
                        'marron-blanco' => 'Marrón/Blanco',
                        'verde-blanco' => 'Verde/Blanco',
                        'azul' => 'Azul',
                        'morado' => 'Morado',
                        'rosa' => 'Rosa',
                        'amarillo' => 'Amarillo',
                        'rojo' => 'Rojo',
                        'naranja' => 'Naranja'
                    ];
                    $colorValue = $colorLabels[$opts['color']] ?? ucfirst($opts['color']);
                    $tintedDetails[] = 'Color: ' . $colorValue;
                }
            }
            
            $options[] = [
                'label' => 'Tintado',
                'value' => implode(', ', $tintedDetails)
            ];
        }

        // 6. RECETA ADJUNTA (if prescription file was uploaded)
        if (!empty($config['attachment_id'])) {
            $options[] = [
                'label' => 'Receta Adjunta',
                'value' => 'Archivo subido (ID: ' . $config['attachment_id'] . ')'
            ];
        }

        return $options;
    }

    /**
     * Obtener la tasa de IVA según la categoría del producto
     *
     * @param \Magento\Catalog\Model\Product $product
     * @return float Tax rate (0.10 para 10%, 0.21 para 21%)
     */
    private function getTaxRateForProduct($product): float
    {
        try {
            // Obtener las categorías del producto
            $categoryIds = $product->getCategoryIds();
            
            if (empty($categoryIds)) {
                $this->logger->warning('Product has no categories, defaulting to 21% tax', [
                    'product_id' => $product->getId()
                ]);
                return 0.21; // Por defecto 21%
            }

            $this->logger->debug('Product categories', [
                'product_id' => $product->getId(),
                'category_ids' => $categoryIds
            ]);

            // Mapeo de categorías a tasas de IVA
            // Gafas graduadas (ID 23): 10%
            if (in_array('23', $categoryIds) || in_array(23, $categoryIds)) {
                $this->logger->info('Applied 10% tax rate (Gafas graduadas - Category ID 23)');
                return 0.10;
            }

            // Gafas deportivas (ID 22): 10%
            if (in_array('22', $categoryIds) || in_array(22, $categoryIds)) {
                $this->logger->info('Applied 10% tax rate (Gafas deportivas - Category ID 22)');
                return 0.10;
            }

            // Lentillas (ID 9): 10%
            if (in_array('9', $categoryIds) || in_array(9, $categoryIds)) {
                $this->logger->info('Applied 10% tax rate (Lentillas - Category ID 9)');
                return 0.10;
            }

            // Gafas de sol (ID 4): 21%
            if (in_array('4', $categoryIds) || in_array(4, $categoryIds)) {
                $this->logger->info('Applied 21% tax rate (Gafas de sol - Category ID 4)');
                return 0.21;
            }

            // Si no coincide con ninguna categoría específica, usar 21%
            $this->logger->info('No specific category match, defaulting to 21% tax', [
                'product_id' => $product->getId(),
                'category_ids' => $categoryIds
            ]);
            return 0.21;

        } catch (\Exception $e) {
            $this->logger->error('Error determining tax rate for product', [
                'product_id' => $product->getId(),
                'error' => $e->getMessage()
            ]);
            return 0.21; // Por defecto 21% en caso de error
        }
    }
}
