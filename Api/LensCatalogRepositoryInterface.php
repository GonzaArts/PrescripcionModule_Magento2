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

/**
 * Lens Catalog Repository Interface
 *
 * @api
 */
interface LensCatalogRepositoryInterface
{
    /**
     * Get available materials
     *
     * @param array $filters
     * @return array
     */
    public function getMaterials(array $filters = []): array;

    /**
     * Get available designs
     *
     * @param string|null $material
     * @param array $filters
     * @return array
     */
    public function getDesigns(?string $material = null, array $filters = []): array;

    /**
     * Get available indexes
     *
     * @param string|null $material
     * @return array
     */
    public function getIndexes(?string $material = null): array;

    /**
     * Get available treatments
     *
     * @param string|null $material
     * @param string|null $design
     * @param bool $activeOnly
     * @return array
     */
    public function getTreatments(
        ?string $material = null,
        ?string $design = null,
        bool $activeOnly = true
    ): array;

    /**
     * Get treatment by code
     *
     * @param string $code
     * @return array
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function getTreatmentByCode(string $code): array;

    /**
     * Check if treatment is compatible
     *
     * @param string $treatmentCode
     * @param string $material
     * @param string $design
     * @param array $otherTreatments
     * @return bool
     */
    public function isTreatmentCompatible(
        string $treatmentCode,
        string $material,
        string $design,
        array $otherTreatments = []
    ): bool;
}
