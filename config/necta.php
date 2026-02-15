<?php

/**
 * NECTA Index Number Validation Configuration
 * 
 * Defines the structure and validation rules for NECTA-style index numbers.
 * Format: CCCC-SSSS (e.g., S0445-0001)
 *   CCCC = Centre code (S/P prefix + 4 digits)
 *   SSSS = Serial/candidate number (4 digits)
 */

return [
    'index_number' => [
        // Delimiter separating centre code from serial
        'delimiter' => '-',

        // Prefix to candidate_type mapping
        'centre_prefix_map' => [
            'S' => 'SCHOOL',
            'P' => 'PRIVATE',
        ],

        // Regular expressions for validation
        'centre_code_regex' => '^[SP][0-9]{4}$',  // S0445, P0652, etc.
        'serial_regex' => '^[0-9]{4}$',            // 0001-9999

        // Full index number pattern (for quick validation)
        'full_pattern' => '^[SP][0-9]{4}-[0-9]{4}$',

        // Normalization rules
        'normalize' => [
            'uppercase' => true,        // Convert to uppercase
            'trim_spaces' => true,      // Trim leading/trailing spaces
            'remove_extra_spaces' => false,  // Collapse multiple spaces (disabled for now)
        ],
    ],

    'validation' => [
        // Enforce that centre code exists in system
        'enforce_known_centre' => true,

        // Enforce uniqueness per exam context (exam_year + exam_type + index_number)
        'enforce_unique_per_exam_context' => true,

        // School centre column (in schools table)
        'school_centre_column' => 'registration_number',

        // Whether to allow same index_number in different exam years
        'allow_same_index_different_years' => true,

        // Whether to allow same index_number in different exam types
        'allow_same_index_different_types' => true,

        // Maximum length for normalized index number
        'max_length' => 9,  // 9 chars: CCCC-SSSS
    ],

    'private_centre' => [
        // Table name for private centres (TODO: create if needed)
        'table' => 'private_centres',
        'centre_column' => 'registration_number',
        
        // Fallback: use static mapping if table doesn't exist
        'use_fallback_mapping' => false,
        
        // Static mapping of private centre codes to IDs (if table doesn't exist)
        // Format: 'P0652' => 1, 'P0653' => 2, etc.
        'fallback_mapping' => [
            // Add mappings as needed
        ],
    ],

    'error_codes' => [
        'INDEX_EMPTY' => 'Index number cannot be empty',
        'INDEX_FORMAT_INVALID' => 'Invalid format. Use: CCCC-SSSS (e.g., S0445-0001)',
        'CENTRE_CODE_INVALID' => 'Centre code must be 4 digits after prefix',
        'CENTRE_PREFIX_UNKNOWN' => 'Unknown centre prefix. Must be S (School) or P (Private)',
        'SERIAL_INVALID' => 'Serial number must be 4 digits',
        'CENTRE_NOT_FOUND' => 'Centre not found in system',
        'DUPLICATE_INDEX_NUMBER' => 'This index number is already registered for this exam',
        'EXAM_CONTEXT_MISSING' => 'Exam year and type required for validation',
    ],
];
