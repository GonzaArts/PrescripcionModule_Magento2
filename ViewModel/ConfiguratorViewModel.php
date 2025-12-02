<?php

declare(strict_types=1);

namespace Powerline\PrescripcionModule\ViewModel;

use Magento\Framework\View\Element\Block\ArgumentInterface;
use Magento\Framework\Pricing\Helper\Data as PricingHelper;
use Magento\Store\Model\StoreManagerInterface;
use Magento\Framework\Locale\FormatInterface;

/**
 * Configurator ViewModel
 * 
 * Provides helper methods for template rendering:
 * - Price formatting with currency
 * - Range formatting for prescription fields
 * - Label translations and formatting
 * - Utility methods for complex template logic
 */
class ConfiguratorViewModel implements ArgumentInterface
{
    /**
     * @param PricingHelper $pricingHelper
     * @param StoreManagerInterface $storeManager
     * @param FormatInterface $localeFormat
     */
    public function __construct(
        private readonly PricingHelper $pricingHelper,
        private readonly StoreManagerInterface $storeManager,
        private readonly FormatInterface $localeFormat
    ) {
    }

    /**
     * Format price with currency symbol
     *
     * @param float $amount
     * @param bool $includeContainer
     * @return string
     */
    public function formatPrice(float $amount, bool $includeContainer = true): string
    {
        return $this->pricingHelper->currency($amount, $includeContainer, false);
    }

    /**
     * Format price for JavaScript (no HTML, just symbol + amount)
     *
     * @param float $amount
     * @return string
     */
    public function formatPriceForJs(float $amount): string
    {
        return $this->pricingHelper->currency($amount, false, false);
    }

    /**
     * Get currency code
     *
     * @return string
     */
    public function getCurrencyCode(): string
    {
        return $this->storeManager->getStore()->getCurrentCurrency()->getCode();
    }

    /**
     * Get currency symbol
     *
     * @return string
     */
    public function getCurrencySymbol(): string
    {
        return $this->storeManager->getStore()->getCurrentCurrency()->getCurrencySymbol();
    }

    /**
     * Format prescription range with units
     *
     * @param float $min
     * @param float $max
     * @param float $step
     * @param string $unit
     * @return string
     */
    public function formatRange(float $min, float $max, float $step, string $unit = ''): string
    {
        $formattedMin = $this->formatNumber($min);
        $formattedMax = $this->formatNumber($max);
        $formattedStep = $this->formatNumber($step);

        return sprintf(
            '%s%s to %s%s (step: %s)',
            $formattedMin,
            $unit ? ' ' . $unit : '',
            $formattedMax,
            $unit ? ' ' . $unit : '',
            $formattedStep
        );
    }

    /**
     * Format number according to locale
     *
     * @param float $number
     * @param int $precision
     * @return string
     */
    public function formatNumber(float $number, int $precision = 2): string
    {
        $priceFormat = $this->localeFormat->getPriceFormat();
        return number_format(
            $number,
            $precision,
            $priceFormat['decimalSymbol'] ?? '.',
            $priceFormat['groupSymbol'] ?? ','
        );
    }

    /**
     * Get use type label
     *
     * @param string $useType
     * @return string
     */
    public function getUseTypeLabel(string $useType): string
    {
        return match ($useType) {
            'monofocal' => __('Monofocal'),
            'bifocal' => __('Bifocal'),
            'progressive' => __('Progressive'),
            'occupational' => __('Occupational'),
            'reading' => __('Reading Glasses'),
            default => $useType
        };
    }

    /**
     * Get material label
     *
     * @param string $material
     * @return string
     */
    public function getMaterialLabel(string $material): string
    {
        return match ($material) {
            'CR39' => __('CR-39 Plastic'),
            'POLYCARBONATE' => __('Polycarbonate'),
            'TRIVEX' => __('Trivex'),
            'HIGH_INDEX' => __('High Index'),
            'GLASS' => __('Glass'),
            default => $material
        };
    }

    /**
     * Get design label
     *
     * @param string $design
     * @return string
     */
    public function getDesignLabel(string $design): string
    {
        return match ($design) {
            'SPHERICAL' => __('Spherical'),
            'ASPHERIC' => __('Aspheric'),
            'DOUBLE_ASPHERIC' => __('Double Aspheric'),
            'FREEFORM' => __('Freeform Digital'),
            'PERSONALIZED' => __('Personalized'),
            default => $design
        };
    }

    /**
     * Get treatment label
     *
     * @param string $treatment
     * @return string
     */
    public function getTreatmentLabel(string $treatment): string
    {
        return match ($treatment) {
            'AR_BASIC' => __('Anti-Reflective Basic'),
            'AR_PREMIUM' => __('Anti-Reflective Premium'),
            'AR_BLUE_LIGHT' => __('Blue Light + AR'),
            'BLUE_LIGHT' => __('Blue Light Filter'),
            'PHOTOCHROMIC' => __('Photochromic'),
            'POLARIZED' => __('Polarized'),
            'HARD_COAT' => __('Hard Coating'),
            'UV_PROTECTION' => __('UV Protection'),
            'MIRROR_COATING' => __('Mirror Coating'),
            default => $treatment
        };
    }

    /**
     * Format PD value
     *
     * @param array $pdData
     * @return string
     */
    public function formatPd(array $pdData): string
    {
        if (!isset($pdData['type'])) {
            return '';
        }

        if ($pdData['type'] === 'binocular') {
            return sprintf('%smm', $pdData['value'] ?? '');
        }

        return sprintf(
            'OD: %smm / OI: %smm',
            $pdData['od'] ?? '',
            $pdData['oi'] ?? ''
        );
    }

    /**
     * Format prescription value with sign
     *
     * @param float|null $value
     * @return string
     */
    public function formatPrescriptionValue(?float $value): string
    {
        if ($value === null) {
            return '—';
        }

        if ($value === 0.0) {
            return '0.00';
        }

        $sign = $value > 0 ? '+' : '';
        return sprintf('%s%.2f', $sign, $value);
    }

    /**
     * Get step icon HTML
     *
     * @param string $step
     * @return string
     */
    public function getStepIcon(string $step): string
    {
        return match ($step) {
            'uso' => '<i class="fa fa-eye"></i>',
            'prescripcion' => '<i class="fa fa-file-medical"></i>',
            'lentes' => '<i class="fa fa-glasses"></i>',
            'tratamientos' => '<i class="fa fa-shield-alt"></i>',
            'extras' => '<i class="fa fa-plus-circle"></i>',
            'resumen' => '<i class="fa fa-check-circle"></i>',
            default => '<i class="fa fa-circle"></i>'
        };
    }

    /**
     * Check if value is within range
     *
     * @param float|null $value
     * @param float $min
     * @param float $max
     * @return bool
     */
    public function isInRange(?float $value, float $min, float $max): bool
    {
        if ($value === null) {
            return false;
        }

        return $value >= $min && $value <= $max;
    }

    /**
     * Get field tooltip content
     *
     * @param string $field
     * @return string
     */
    public function getFieldTooltip(string $field): string
    {
        return match ($field) {
            'sph' => __('Sphere (SPH): Lens power needed to correct nearsightedness or farsightedness. Range: -20.00 to +20.00.'),
            'cyl' => __('Cylinder (CYL): Lens power for astigmatism correction. Range: -8.00 to +8.00. Leave empty if no astigmatism.'),
            'axis' => __('Axis: Angle of astigmatism correction (0-180°). Required only if CYL is present.'),
            'add' => __('Addition (ADD): Additional magnifying power for reading (0.25 to 4.00). Required for bifocal/progressive lenses.'),
            'prism' => __('Prism: Corrects eye alignment issues (0.00 to 10.00). Usually prescribed for double vision.'),
            'prism_base' => __('Prism Base: Direction of prism correction (UP/DOWN/IN/OUT). Required if prism value is present.'),
            'pd' => __('Pupillary Distance (PD): Distance between pupils (20-80mm). Critical for proper lens centering.'),
            default => ''
        };
    }

    /**
     * Get validation error message
     *
     * @param string $errorCode
     * @param array $params
     * @return string
     */
    public function getValidationErrorMessage(string $errorCode, array $params = []): string
    {
        return match ($errorCode) {
            'required' => __('%1 is required.', $params[0] ?? 'Field'),
            'out_of_range' => __('%1 must be between %2 and %3.', 
                $params[0] ?? 'Value',
                $params[1] ?? 'min',
                $params[2] ?? 'max'
            ),
            'invalid_increment' => __('%1 must be in increments of %2.', 
                $params[0] ?? 'Value',
                $params[1] ?? 'step'
            ),
            'axis_required' => __('Axis is required when Cylinder is present.'),
            'prism_base_required' => __('Prism Base is required when Prism is present.'),
            'add_required' => __('Addition is required for progressive/bifocal lenses.'),
            'pd_required' => __('Pupillary Distance is required.'),
            'incompatible' => __('%1 is not compatible with %2.', 
                $params[0] ?? 'Option',
                $params[1] ?? 'selection'
            ),
            default => __('Validation error: %1', $errorCode)
        };
    }

    /**
     * Get formatted file size
     *
     * @param int $bytes
     * @return string
     */
    public function formatFileSize(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes . ' B';
        } elseif ($bytes < 1048576) {
            return round($bytes / 1024, 2) . ' KB';
        } else {
            return round($bytes / 1048576, 2) . ' MB';
        }
    }

    /**
     * Check if customer is logged in
     *
     * @return bool
     */
    public function isCustomerLoggedIn(): bool
    {
        // This would typically use CustomerSession, but keeping ViewModel stateless
        // The actual implementation should be in the Block or use ObjectManager sparingly
        return false; // Placeholder
    }

    /**
     * Get price breakdown labels
     *
     * @return array
     */
    public function getPriceBreakdownLabels(): array
    {
        return [
            'base_lens_price' => __('Base Lens Price'),
            'surcharges' => __('Prescription Surcharges'),
            'treatments' => __('Treatments'),
            'extras' => __('Extras & Options'),
            'subtotal' => __('Subtotal'),
            'tax' => __('Tax (21%)'),
            'total' => __('Total')
        ];
    }
}
