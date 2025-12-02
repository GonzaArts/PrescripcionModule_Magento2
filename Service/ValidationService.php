<?php

declare(strict_types=1);

namespace Powerline\PrescripcionModule\Service;

use Powerline\PrescripcionModule\Api\Data\ConfigDtoInterface;
use Powerline\PrescripcionModule\Api\Data\ValidationResultDtoInterface;
use Powerline\PrescripcionModule\Api\ValidationServiceInterface;
use Powerline\PrescripcionModule\Model\Data\ValidationResultDto;
use Powerline\PrescripcionModule\Model\TreatmentRepository;
use Powerline\PrescripcionModule\Logger\Logger;

/**
 * Validation Service Implementation
 *
 * Validates prescription data, lens compatibility, and treatment compatibility
 */
class ValidationService implements ValidationServiceInterface
{
    private const SPH_MIN = -20.00;
    private const SPH_MAX = 20.00;
    private const CYL_MIN = -8.00;
    private const CYL_MAX = 8.00;
    private const AXIS_MIN = 0;
    private const AXIS_MAX = 180;
    private const ADD_MIN = 0.25;
    private const ADD_MAX = 4.00;
    private const PD_MIN = 20;
    private const PD_MAX = 80;
    private const PRISM_MIN = 0.00;
    private const PRISM_MAX = 10.00;
    private const VALID_PRISM_BASES = ['UP', 'DOWN', 'IN', 'OUT'];

    public function __construct(
        private readonly TreatmentRepository $treatmentRepository,
        private readonly Logger $logger
    ) {
    }

    /**
     * @inheritDoc
     */
    public function validate(ConfigDtoInterface $config): ValidationResultDtoInterface
    {
        $result = new ValidationResultDto();

        try {
            $prescriptionData = $config->getPrescriptionData();
            if (empty($prescriptionData)) {
                $result->addError('prescription_data', __('Prescription data is required'), 'required');
                return $result;
            }

            // Validate prescription with use type
            $prescResult = $this->validatePrescription($prescriptionData, $config->getUseType());
            foreach ($prescResult->getErrors() as $field => $error) {
                $result->addError($field, $error['message'], $error['code']);
            }
            foreach ($prescResult->getWarnings() as $field => $warning) {
                $result->addWarning($field, $warning);
            }

            // Validate lens compatibility
            if ($config->getLensMaterial() && $config->getLensDesign()) {
                $lensResult = $this->validateLensCompatibility(
                    $config->getLensMaterial(),
                    $config->getLensDesign(),
                    $prescriptionData
                );
                foreach ($lensResult->getWarnings() as $field => $warning) {
                    $result->addWarning($field, $warning);
                }
            }

            // Validate treatment compatibility
            if (!empty($config->getTreatments()) && $config->getLensMaterial() && $config->getLensDesign()) {
                $treatResult = $this->validateTreatmentCompatibility(
                    $config->getTreatments(),
                    $config->getLensMaterial(),
                    $config->getLensDesign()
                );
                foreach ($treatResult->getErrors() as $field => $error) {
                    $result->addError($field, $error['message'], $error['code']);
                }
            }

            $this->logger->info('Validation completed', [
                'is_valid' => $result->isValid(),
                'error_count' => count($result->getErrors()),
            ]);

        } catch (\Exception $e) {
            $this->logger->error('Error during validation', ['error' => $e->getMessage()]);
            $result->addError('general', __('Validation error occurred'), 'exception');
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function validatePrescription(array $prescription, string $useType): ValidationResultDtoInterface
    {
        $result = new ValidationResultDto();

        if (empty($prescription)) {
            $result->addError('prescription', __('Prescription data is required'), 'required');
            return $result;
        }

        // Validate use type
        $validTypes = ['monofocal', 'bifocal', 'progressive', 'occupational', 'reading'];
        if (!in_array($useType, $validTypes, true)) {
            $result->addError('use_type', __('Invalid use type'), 'invalid_value');
        }

        // Validate OD (Right Eye)
        if (isset($prescription['od'])) {
            $this->validateEyeData($prescription['od'], 'od', $useType, $result);
        }

        // Validate OI (Left Eye)
        if (isset($prescription['oi'])) {
            $this->validateEyeData($prescription['oi'], 'oi', $useType, $result);
        }

        // Validate PD
        if (isset($prescription['pd'])) {
            if (!$this->validatePupillaryDistance($prescription['pd'])) {
                $result->addError('pd', __('Invalid pupillary distance'), 'invalid_value');
            }
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function validateLensCompatibility(
        string $material,
        string $design,
        array $prescription
    ): ValidationResultDtoInterface {
        $result = new ValidationResultDto();

        // Calculate max sphere
        $maxSphere = 0.0;
        if (isset($prescription['od']['sph'])) {
            $maxSphere = max($maxSphere, abs((float)$prescription['od']['sph']));
        }
        if (isset($prescription['oi']['sph'])) {
            $maxSphere = max($maxSphere, abs((float)$prescription['oi']['sph']));
        }

        // Recommend high index for strong prescriptions
        if ($maxSphere > 6.00) {
            $result->addWarning(
                'lens_index',
                __('High index lens (1.67 or 1.74) recommended for strong prescriptions')
            );
        }

        // Check material compatibility with design
        if ($design === 'progressive' && $material === 'glass') {
            $result->addWarning(
                'lens_material',
                __('Progressive lenses work better with polycarbonate or high-index materials')
            );
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function validateTreatmentCompatibility(
        array $treatments,
        string $material,
        string $design
    ): ValidationResultDtoInterface {
        $result = new ValidationResultDto();

        try {
            foreach ($treatments as $code) {
                $treatment = $this->treatmentRepository->getByCode($code);
                
                if (!$treatment || !$treatment->isActive()) {
                    $result->addError(
                        "treatment.{$code}",
                        __('Treatment "%1" is not available', $code),
                        'not_available'
                    );
                    continue;
                }

                // Check material compatibility
                if (!$treatment->isCompatibleWithMaterial($material)) {
                    $result->addError(
                        "treatment.{$code}",
                        __('Treatment "%1" not compatible with material "%2"', $treatment->getName(), $material),
                        'incompatible'
                    );
                }

                // Check incompatibilities with other treatments
                foreach ($treatments as $otherCode) {
                    if ($code !== $otherCode && $treatment->isIncompatibleWith($otherCode)) {
                        $result->addError(
                            "treatment.{$code}",
                            __('Treatment "%1" incompatible with "%2"', $treatment->getName(), $otherCode),
                            'incompatible'
                        );
                    }
                }
            }
        } catch (\Exception $e) {
            $this->logger->error('Error validating treatments', ['error' => $e->getMessage()]);
        }

        return $result;
    }

    /**
     * @inheritDoc
     */
    public function validateSphere(?float $sph): bool
    {
        if ($sph === null) {
            return true;
        }

        if ($sph < self::SPH_MIN || $sph > self::SPH_MAX) {
            return false;
        }

        // Check 0.25 increments
        $remainder = abs($sph * 100) % 25;
        return $remainder === 0;
    }

    /**
     * @inheritDoc
     */
    public function validateCylinder(?float $cyl): bool
    {
        if ($cyl === null) {
            return true;
        }

        if ($cyl < self::CYL_MIN || $cyl > self::CYL_MAX) {
            return false;
        }

        // Check 0.25 increments
        $remainder = abs($cyl * 100) % 25;
        return $remainder === 0;
    }

    /**
     * @inheritDoc
     */
    public function validateAxis(?int $axis, ?float $cyl): bool
    {
        // Axis is required if cylinder is present and not zero
        if ($cyl !== null && $cyl !== 0.0) {
            if ($axis === null) {
                return false;
            }
            return $axis >= self::AXIS_MIN && $axis <= self::AXIS_MAX;
        }

        // If no cylinder, axis is optional
        return true;
    }

    /**
     * @inheritDoc
     */
    public function validateAddition(?float $add, string $useType): bool
    {
        // Addition is required for progressive/bifocal
        if (in_array($useType, ['progressive', 'bifocal'], true)) {
            if ($add === null) {
                return false;
            }
            
            if ($add < self::ADD_MIN || $add > self::ADD_MAX) {
                return false;
            }

            // Check 0.25 increments
            $remainder = abs($add * 100) % 25;
            return $remainder === 0;
        }

        // For other types, addition is optional
        if ($add !== null) {
            if ($add < self::ADD_MIN || $add > self::ADD_MAX) {
                return false;
            }
            $remainder = abs($add * 100) % 25;
            return $remainder === 0;
        }

        return true;
    }

    /**
     * @inheritDoc
     */
    public function validatePupillaryDistance(array $pd): bool
    {
        // Binocular PD
        if (isset($pd['binocular'])) {
            $value = (int)$pd['binocular'];
            if ($value < self::PD_MIN * 2 || $value > self::PD_MAX * 2) {
                return false;
            }
        }

        // Monocular PD
        if (isset($pd['od'])) {
            $value = (int)$pd['od'];
            if ($value < self::PD_MIN || $value > self::PD_MAX) {
                return false;
            }
        }

        if (isset($pd['oi'])) {
            $value = (int)$pd['oi'];
            if ($value < self::PD_MIN || $value > self::PD_MAX) {
                return false;
            }
        }

        return true;
    }

    /**
     * Validate complete eye data
     *
     * @param array $eyeData
     * @param string $eyeCode
     * @param string $useType
     * @param ValidationResultDto $result
     * @return void
     */
    private function validateEyeData(
        array $eyeData,
        string $eyeCode,
        string $useType,
        ValidationResultDto $result
    ): void {
        // Validate Sphere
        if (isset($eyeData['sph'])) {
            if (!$this->validateSphere((float)$eyeData['sph'])) {
                $result->addError(
                    "{$eyeCode}.sph",
                    __('Invalid sphere value'),
                    'invalid_value'
                );
            }
        }

        // Validate Cylinder
        $cyl = isset($eyeData['cyl']) ? (float)$eyeData['cyl'] : null;
        if ($cyl !== null && !$this->validateCylinder($cyl)) {
            $result->addError(
                "{$eyeCode}.cyl",
                __('Invalid cylinder value'),
                'invalid_value'
            );
        }

        // Validate Axis
        $axis = isset($eyeData['axis']) ? (int)$eyeData['axis'] : null;
        if (!$this->validateAxis($axis, $cyl)) {
            $result->addError(
                "{$eyeCode}.axis",
                __('Invalid or missing axis value'),
                'invalid_value'
            );
        }

        // Validate Addition
        $add = isset($eyeData['add']) ? (float)$eyeData['add'] : null;
        if (!$this->validateAddition($add, $useType)) {
            $result->addError(
                "{$eyeCode}.add",
                __('Invalid addition value'),
                'invalid_value'
            );
        }

        // Validate Prism
        if (isset($eyeData['prism'])) {
            $prism = (float)$eyeData['prism'];
            if ($prism < self::PRISM_MIN || $prism > self::PRISM_MAX) {
                $result->addError(
                    "{$eyeCode}.prism",
                    __('Invalid prism value'),
                    'invalid_value'
                );
            }

            // Validate Prism Base
            if ($prism > 0.0) {
                if (!isset($eyeData['prism_base'])) {
                    $result->addError(
                        "{$eyeCode}.prism_base",
                        __('Prism base is required'),
                        'required'
                    );
                } else {
                    $base = strtoupper($eyeData['prism_base']);
                    if (!in_array($base, self::VALID_PRISM_BASES, true)) {
                        $result->addError(
                            "{$eyeCode}.prism_base",
                            __('Invalid prism base'),
                            'invalid_value'
                        );
                    }
                }
            }
        }
    }
}
