<?php
/**
 * Powerline PrescripcionModule
 *
 * @category  Powerline
 * @package   Powerline_PrescripcionModule
 * @author    Powerline Development Team
 * @copyright Copyright (c) 2025 Powerline
 * @license   Proprietary
 */

declare(strict_types=1);

namespace Powerline\PrescripcionModule\Api;

use Powerline\PrescripcionModule\Api\Data\ConfigDtoInterface;
use Powerline\PrescripcionModule\Api\Data\ValidationResultDtoInterface;

/**
 * Validation Service Interface
 *
 * @api
 */
interface ValidationServiceInterface
{
    /**
     * Validate complete prescription configuration
     *
     * @param ConfigDtoInterface $config
     * @return ValidationResultDtoInterface
     */
    public function validate(ConfigDtoInterface $config): ValidationResultDtoInterface;

    /**
     * Validate prescription values (SPH, CYL, AXIS, ADD, PD, PRISM)
     *
     * @param array $prescription
     * @param string $useType
     * @return ValidationResultDtoInterface
     */
    public function validatePrescription(array $prescription, string $useType): ValidationResultDtoInterface;

    /**
     * Validate lens compatibility with prescription
     *
     * @param string $material
     * @param string $design
     * @param array $prescription
     * @return ValidationResultDtoInterface
     */
    public function validateLensCompatibility(
        string $material,
        string $design,
        array $prescription
    ): ValidationResultDtoInterface;

    /**
     * Validate treatment compatibility
     *
     * @param array $treatments
     * @param string $material
     * @param string $design
     * @return ValidationResultDtoInterface
     */
    public function validateTreatmentCompatibility(
        array $treatments,
        string $material,
        string $design
    ): ValidationResultDtoInterface;

    /**
     * Validate sphere value
     *
     * @param float|null $sph
     * @return bool
     */
    public function validateSphere(?float $sph): bool;

    /**
     * Validate cylinder value
     *
     * @param float|null $cyl
     * @return bool
     */
    public function validateCylinder(?float $cyl): bool;

    /**
     * Validate axis value
     *
     * @param int|null $axis
     * @param float|null $cyl
     * @return bool
     */
    public function validateAxis(?int $axis, ?float $cyl): bool;

    /**
     * Validate addition value
     *
     * @param float|null $add
     * @param string $useType
     * @return bool
     */
    public function validateAddition(?float $add, string $useType): bool;

    /**
     * Validate pupillary distance
     *
     * @param array $pd
     * @return bool
     */
    public function validatePupillaryDistance(array $pd): bool;
}
