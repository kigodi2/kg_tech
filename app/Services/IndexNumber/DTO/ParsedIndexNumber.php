<?php

namespace App\Services\IndexNumber\DTO;

/**
 * Data Transfer Object representing a successfully parsed index number
 * 
 * Example:
 *   raw: "S0445-0001"
 *   normalized: "S0445-0001"
 *   centre_code: "S0445"
 *   prefix: "S"
 *   serial: "0001"
 *   candidate_type: "SCHOOL"
 */
class ParsedIndexNumber
{
    public function __construct(
        public string $raw,
        public string $normalized,
        public string $centre_code,
        public string $prefix,
        public string $serial,
        public string $candidate_type,
    ) {
    }

    /**
     * Create from raw index number string
     * Performs basic parsing without validation
     */
    public static function fromString(string $raw): ?self
    {
        $normalized = self::normalize($raw);
        
        // Split by delimiter
        $parts = explode('-', $normalized);
        if (count($parts) !== 2) {
            return null;
        }

        [$centreCode, $serial] = $parts;
        
        if (strlen($centreCode) < 1) {
            return null;
        }

        $prefix = $centreCode[0];
        $prefixMap = config('necta.index_number.centre_prefix_map', []);
        $candidateType = $prefixMap[$prefix] ?? 'UNKNOWN';

        return new self(
            raw: $raw,
            normalized: $normalized,
            centre_code: $centreCode,
            prefix: $prefix,
            serial: $serial,
            candidate_type: $candidateType,
        );
    }

    /**
     * Normalize index number according to config
     */
    private static function normalize(string $indexNumber): string
    {
        $config = config('necta.index_number.normalize', []);
        
        $normalized = $indexNumber;

        // Trim spaces
        if ($config['trim_spaces'] ?? false) {
            $normalized = trim($normalized);
        }

        // Remove extra spaces (collapse multiple spaces to single)
        if ($config['remove_extra_spaces'] ?? false) {
            $normalized = preg_replace('/\s+/', ' ', $normalized);
        }

        // Uppercase
        if ($config['uppercase'] ?? false) {
            $normalized = strtoupper($normalized);
        }

        return $normalized;
    }

    /**
     * Get string representation
     */
    public function toString(): string
    {
        return $this->normalized;
    }

    /**
     * Convert to array (useful for API responses)
     */
    public function toArray(): array
    {
        return [
            'raw' => $this->raw,
            'normalized' => $this->normalized,
            'centre_code' => $this->centre_code,
            'prefix' => $this->prefix,
            'serial' => $this->serial,
            'candidate_type' => $this->candidate_type,
        ];
    }
}
