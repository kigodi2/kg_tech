<?php

namespace App\Services\IndexNumber\DTO;

use Illuminate\Support\Collection;

/**
 * Data Transfer Object representing the result of index number validation
 * 
 * Contains:
 * - ok: Whether validation passed
 * - errors: Field-specific validation errors (user-friendly)
 * - warnings: Non-blocking issues
 * - parsed: The ParsedIndexNumber (if parsing succeeded)
 * - resolved_school_id: School ID (if SCHOOL candidate and centre found)
 * - resolved_private_centre_id: Private centre ID (if PRIVATE candidate and centre found)
 * - duplicate_candidate_id: ID of duplicate candidate (if duplicate detected)
 */
class ValidationResult
{
    protected array $errors = [];
    protected array $warnings = [];

    public function __construct(
        public bool $ok = false,
        public ?ParsedIndexNumber $parsed = null,
        public ?int $resolved_school_id = null,
        public ?int $resolved_private_centre_id = null,
        public ?int $duplicate_candidate_id = null,
    ) {
    }

    /**
     * Add a validation error
     * 
     * @param string $code - Error code (e.g., 'INDEX_FORMAT_INVALID')
     * @param string $message - User-friendly message
     * @param string $field - Field name (default: 'index_number')
     */
    public function addError(string $code, string $message, string $field = 'index_number'): self
    {
        $this->errors[] = [
            'code' => $code,
            'message' => $message,
            'field' => $field,
        ];
        return $this;
    }

    /**
     * Add a warning (non-blocking)
     */
    public function addWarning(string $code, string $message): self
    {
        $this->warnings[] = [
            'code' => $code,
            'message' => $message,
        ];
        return $this;
    }

    /**
     * Get all errors
     */
    public function errors(): array
    {
        return $this->errors;
    }

    /**
     * Get all warnings
     */
    public function warnings(): array
    {
        return $this->warnings;
    }

    /**
     * Check if result has errors
     */
    public function hasErrors(): bool
    {
        return count($this->errors) > 0;
    }

    /**
     * Get first error (useful for simple error display)
     */
    public function firstError(): ?array
    {
        return $this->errors[0] ?? null;
    }

    /**
     * Mark result as successful
     */
    public function markSuccess(): self
    {
        $this->ok = true;
        return $this;
    }

    /**
     * Convert to array (for API responses, etc.)
     */
    public function toArray(): array
    {
        return [
            'ok' => $this->ok,
            'errors' => $this->errors,
            'warnings' => $this->warnings,
            'parsed' => $this->parsed?->toArray(),
            'resolved' => [
                'school_id' => $this->resolved_school_id,
                'private_centre_id' => $this->resolved_private_centre_id,
            ],
            'duplicate_candidate_id' => $this->duplicate_candidate_id,
        ];
    }

    /**
     * Convert to JSON
     */
    public function toJson(): string
    {
        return json_encode($this->toArray());
    }

    /**
     * Create a success result
     */
    public static function success(
        ParsedIndexNumber $parsed,
        ?int $schoolId = null,
        ?int $privateCentreId = null
    ): self {
        $result = new self(
            ok: true,
            parsed: $parsed,
            resolved_school_id: $schoolId,
            resolved_private_centre_id: $privateCentreId,
        );
        return $result;
    }

    /**
     * Create a failure result
     */
    public static function failure(string $code, string $message, string $field = 'index_number'): self
    {
        $result = new self();
        $result->addError($code, $message, $field);
        return $result;
    }
}
