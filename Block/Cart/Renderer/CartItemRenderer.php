<?php
declare(strict_types=1);

namespace Powerline\PrescripcionModule\Block\Cart\Renderer;

use Magento\Checkout\Block\Cart\Item\Renderer as DefaultRenderer;
use Magento\Framework\Serialize\SerializerInterface;

/**
 * Custom renderer para items de carrito con configuración de prescripción
 * 
 * Detecta si el item tiene product_options['powerline_presc']
 * Muestra sección expandible con detalles de la configuración
 */
class CartItemRenderer extends DefaultRenderer
{
    private const OPTION_KEY = 'powerline_presc';
    
    /**
     * @var SerializerInterface
     */
    private $serializer;
    
    // Labels para tipos de uso
    private const USE_TYPE_LABELS = [
        'monofocal' => 'Monofocal',
        'bifocal' => 'Bifocal',
        'progressive' => 'Progressive',
        'occupational' => 'Occupational',
        'reading' => 'Reading'
    ];
    
    // Labels para materiales
    private const MATERIAL_LABELS = [
        'CR39' => 'CR-39 Plastic',
        'POLYCARBONATE' => 'Polycarbonate',
        'TRIVEX' => 'Trivex',
        'HIGH_INDEX' => 'High Index',
        'GLASS' => 'Glass'
    ];
    
    // Labels para diseños
    private const DESIGN_LABELS = [
        'SINGLE_VISION' => 'Single Vision',
        'BIFOCAL' => 'Bifocal',
        'PROGRESSIVE' => 'Progressive',
        'OCCUPATIONAL' => 'Occupational',
        'READING' => 'Reading'
    ];
    
    // Labels para tratamientos
    private const TREATMENT_LABELS = [
        'AR_COATING' => 'Anti-Reflective Coating',
        'BLUE_LIGHT' => 'Blue Light Filter',
        'PHOTOCHROMIC' => 'Photochromic (Transitions)',
        'POLARIZED' => 'Polarized',
        'HARD_COAT' => 'Scratch-Resistant Coating',
        'UV_PROTECTION' => 'UV Protection',
        'MIRROR' => 'Mirror Coating',
        'HYDROPHOBIC' => 'Water-Repellent',
        'OLEOPHOBIC' => 'Oil-Repellent'
    ];
    
    /**
     * Get serializer
     * 
     * @return SerializerInterface
     */
    private function getSerializer(): SerializerInterface
    {
        if (!$this->serializer) {
            $this->serializer = \Magento\Framework\App\ObjectManager::getInstance()
                ->get(SerializerInterface::class);
        }
        return $this->serializer;
    }

    /**
     * Verificar si el item tiene configuración de prescripción
     * 
     * @return bool
     */
    public function hasPrescriptionConfiguration(): bool
    {
        return !empty($this->getConfigurationOptions());
    }

    /**
     * Obtener lista de opciones de configuración para mostrar en el carrito
     * Prioriza: additional_options > attributes_info > buy request
     * 
     * @return array Lista de pares ['label' => 'X', 'value' => 'Y']
     */
    public function getConfigurationOptions(): array
    {
        $item = $this->getItem();
        $result = [];

        // 1) PRIORIDAD: additional_options (creado por observers)
        if ($opt = $item->getOptionByCode('additional_options')) {
            try {
                $decoded = (array)$this->getSerializer()->unserialize($opt->getValue());
                foreach ($decoded as $row) {
                    if (!empty($row['label']) && isset($row['value']) && $row['value'] !== '') {
                        $result[] = [
                            'label' => (string)$row['label'],
                            'value' => is_array($row['value']) ? implode(', ', $row['value']) : (string)$row['value'],
                        ];
                    }
                }
            } catch (\Throwable $e) {
                // Log error but continue
            }
        }

        // 2) Fallback: attributes_info (talla/color de Magento)
        if (empty($result)) {
            $productOptions = $item->getProductOptions();
            if (!empty($productOptions['attributes_info']) && is_array($productOptions['attributes_info'])) {
                foreach ($productOptions['attributes_info'] as $row) {
                    if (!empty($row['label']) && isset($row['value']) && $row['value'] !== '') {
                        $result[] = [
                            'label' => (string)$row['label'],
                            'value' => is_array($row['value']) ? implode(', ', $row['value']) : (string)$row['value'],
                        ];
                    }
                }
            }
        }

        // 3) Último recurso: reconstruir desde buy request
        if (empty($result)) {
            $buyRequest = $item->getBuyRequest();
            if ($buyRequest && isset($buyRequest['prescription_config'])) {
                try {
                    $config = is_string($buyRequest['prescription_config']) 
                        ? $this->getSerializer()->unserialize($buyRequest['prescription_config'])
                        : $buyRequest['prescription_config'];
                    
                    if (is_array($config)) {
                        $result = $this->extractConfigOptionsFromBuyRequest($config);
                    }
                } catch (\Exception $e) {
                    // Continue
                }
            }
        }

        return array_values(array_filter($result, fn($r) => isset($r['value']) && $r['value'] !== ''));
    }

    /**
     * Extraer opciones desde buy request (fallback)
     * 
     * @param array $config
     * @return array
     */
    private function extractConfigOptionsFromBuyRequest(array $config): array
    {
        $result = [];
        
        // Frame
        if (!empty($config['frame'])) {
            $result[] = [
                'label' => __('Montura')->render(),
                'value' => $config['frame']['name'] ?? 'Seleccionada'
            ];
        }
        
        // Base
        if (!empty($config['base'])) {
            $result[] = [
                'label' => __('Cristal Base')->render(),
                'value' => $config['base']['name'] ?? 'Seleccionado'
            ];
        }
        
        // Treatments
        if (!empty($config['treatments']) && is_array($config['treatments'])) {
            $names = [];
            foreach ($config['treatments'] as $t) {
                if (isset($t['name'])) {
                    $names[] = $t['name'];
                }
            }
            if (!empty($names)) {
                $result[] = [
                    'label' => __('Tratamientos')->render(),
                    'value' => implode(', ', $names)
                ];
            }
        }
        
        // Extras
        if (!empty($config['extras']) && is_array($config['extras'])) {
            $names = [];
            foreach ($config['extras'] as $e) {
                if (isset($e['name'])) {
                    $names[] = $e['name'];
                }
            }
            if (!empty($names)) {
                $result[] = [
                    'label' => __('Extras')->render(),
                    'value' => implode(', ', $names)
                ];
            }
        }
        
        return $result;
    }

    /**
     * Obtener configuración de prescripción del item (DEPRECATED)
     * 
     * @return array|null
     * @deprecated Usar getConfigurationOptions() en su lugar
     */
    public function getPrescriptionConfig(): ?array
    {
        $item = $this->getItem();
        
        // Primero intentar obtener desde las opciones del item (buy request)
        $buyRequest = $item->getBuyRequest();
        if ($buyRequest && isset($buyRequest['prescription_config'])) {
            try {
                $config = is_string($buyRequest['prescription_config']) 
                    ? $this->getSerializer()->unserialize($buyRequest['prescription_config'])
                    : $buyRequest['prescription_config'];
                return is_array($config) ? $config : null;
            } catch (\Exception $e) {
                // Continue to next method
            }
        }

        return null;
    }

    /**
     * Verificar si un string es una configuración de prescripción serializada
     * 
     * @param string $value
     * @return bool
     */
    private function isPrescriptionConfig(string $value): bool
    {
        try {
            $data = $this->getSerializer()->unserialize($value);
            return is_array($data) && isset($data['prescription']) && isset($data['lens']);
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Formatear valor de prescripción
     * 
     * @param float $value
     * @return string
     */
    public function formatPrescriptionValue(float $value): string
    {
        return sprintf('%+.2f', $value);
    }

    /**
     * Obtener descripción de las lentes
     * 
     * @param array $lensData
     * @return string
     */
    public function getLensDescription(array $lensData): string
    {
        $parts = [];
        
        if (isset($lensData['material'])) {
            $parts[] = $this->getMaterialLabel($lensData['material']);
        }
        
        if (isset($lensData['design'])) {
            $parts[] = $this->getDesignLabel($lensData['design']);
        }
        
        if (isset($lensData['index']) && $lensData['index']) {
            $parts[] = 'Index: ' . $lensData['index'];
        }
        
        return implode(' | ', $parts);
    }

    /**
     * Obtener lista de tratamientos con labels
     * 
     * @param array $treatments
     * @return array
     */
    public function getTreatmentsList(array $treatments): array
    {
        $list = [];
        
        foreach ($treatments as $treatment) {
            $list[] = $this->getTreatmentLabel($treatment);
        }
        
        return $list;
    }

    /**
     * Obtener label para tipo de uso
     * 
     * @param string $value
     * @return string
     */
    public function getUseTypeLabel(string $value): string
    {
        return __(self::USE_TYPE_LABELS[$value] ?? $value)->render();
    }

    /**
     * Obtener label para material
     * 
     * @param string $value
     * @return string
     */
    public function getMaterialLabel(string $value): string
    {
        return __(self::MATERIAL_LABELS[$value] ?? $value)->render();
    }

    /**
     * Obtener label para diseño
     * 
     * @param string $value
     * @return string
     */
    public function getDesignLabel(string $value): string
    {
        return __(self::DESIGN_LABELS[$value] ?? $value)->render();
    }

    /**
     * Obtener label para tratamiento
     * 
     * @param string $value
     * @return string
     */
    public function getTreatmentLabel(string $value): string
    {
        return __(self::TREATMENT_LABELS[$value] ?? $value)->render();
    }

    /**
     * Obtener URL para editar configuración
     * 
     * @return string
     */
    public function getEditConfigurationUrl(): string
    {
        $item = $this->getItem();
        return $this->getUrl('presc/prescription/index', [
            'pid' => $item->getProduct()->getId(),
            'item_id' => $item->getId()
        ]);
    }

    /**
     * Obtener URL para descargar adjunto
     * 
     * @param int $attachmentId
     * @param string|null $hash
     * @return string
     */
    public function getAttachmentDownloadUrl(int $attachmentId, ?string $hash = null): string
    {
        $params = ['id' => $attachmentId];
        if ($hash) {
            $params['hash'] = $hash;
        }
        
        return $this->getUrl('presc/attachment/download', $params);
    }

    /**
     * Obtener label para frame
     * 
     * @param mixed $frame
     * @return string|null
     */
    private function getFrameLabel($frame): ?string
    {
        if (is_string($frame)) {
            return $frame;
        }
        if (is_array($frame)) {
            if (isset($frame['name'])) {
                return $frame['name'];
            }
            if (isset($frame['id'])) {
                return 'Frame ID: ' . $frame['id'];
            }
        }
        return null;
    }

    /**
     * Obtener label para base lens
     * 
     * @param mixed $base
     * @return string|null
     */
    private function getBaseLensLabel($base): ?string
    {
        if (is_string($base)) {
            return $base;
        }
        if (is_array($base)) {
            $parts = [];
            if (isset($base['name'])) {
                $parts[] = $base['name'];
            }
            if (isset($base['material'])) {
                $parts[] = $this->getMaterialLabel($base['material']);
            }
            if (isset($base['design'])) {
                $parts[] = $this->getDesignLabel($base['design']);
            }
            if (isset($base['index'])) {
                $parts[] = 'Index: ' . $base['index'];
            }
            return !empty($parts) ? implode(' | ', $parts) : null;
        }
        return null;
    }
}
