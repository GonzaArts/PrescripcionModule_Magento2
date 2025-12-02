<?php

declare(strict_types=1);

namespace Powerline\PrescripcionModule\Model\Data;

use Powerline\PrescripcionModule\Api\Data\ValidationResultDtoInterface;

/**
 * Validation Result DTO implementation
 */
class ValidationResultDto implements ValidationResultDtoInterface
{
    /**
     * @param bool $isValid
     * @param array $errors
     * @param array $warnings
     */
    public function __construct(
        private bool $isValid = true,
        private array $errors = [],
        private array $warnings = []
    ) {
    }

    public function isValid(): bool
    {
        return $this->isValid;
    }

    public function setIsValid(bool $isValid): self
    {
        $this->isValid = $isValid;
        return $this;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }

    public function setErrors(array $errors): self
    {
        $this->errors = $errors;
        $this->isValid = empty($errors);
        return $this;
    }

    public function addError(string $field, string $message, string $code = 'error'): self
    {
        $this->errors[$field] = [
            'message' => $message,
            'code' => $code,
        ];
        $this->isValid = false;
        return $this;
    }

    public function getWarnings(): array
    {
        return $this->warnings;
    }

    public function setWarnings(array $warnings): self
    {
        $this->warnings = $warnings;
        return $this;
    }

    public function addWarning(string $field, string $message): self
    {
        $this->warnings[$field] = $message;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'is_valid' => $this->isValid,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
        ];
    }
}
