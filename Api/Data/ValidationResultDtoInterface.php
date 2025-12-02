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

namespace Powerline\PrescripcionModule\Api\Data;

/**
 * Validation Result DTO Interface
 *
 * @api
 */
interface ValidationResultDtoInterface
{
    /**
     * Check if validation passed
     *
     * @return bool
     */
    public function isValid(): bool;

    /**
     * Set validation status
     *
     * @param bool $isValid
     * @return $this
     */
    public function setIsValid(bool $isValid): self;

    /**
     * Get validation errors
     *
     * @return array
     */
    public function getErrors(): array;

    /**
     * Set validation errors
     *
     * @param array $errors
     * @return $this
     */
    public function setErrors(array $errors): self;

    /**
     * Add validation error
     *
     * @param string $field
     * @param string $message
     * @param string $code
     * @return $this
     */
    public function addError(string $field, string $message, string $code = 'error'): self;

    /**
     * Get warnings (non-blocking validation messages)
     *
     * @return array
     */
    public function getWarnings(): array;

    /**
     * Set warnings
     *
     * @param array $warnings
     * @return $this
     */
    public function setWarnings(array $warnings): self;

    /**
     * Add warning
     *
     * @param string $field
     * @param string $message
     * @return $this
     */
    public function addWarning(string $field, string $message): self;

    /**
     * Convert to array
     *
     * @return array
     */
    public function toArray(): array;
}
