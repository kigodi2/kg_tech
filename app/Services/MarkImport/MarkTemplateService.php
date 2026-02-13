<?php

namespace App\Services\MarkImport;

use App\Models\Subject;

class MarkTemplateService
{
    /**
     * Generate CSV template headers based on paper structure
     */
    public function generateTemplateHeaders(Subject $subject): array
    {
        $headers = [
            'Index Number',
            'Full Name',
        ];

        // Add paper columns
        for ($i = 1; $i <= $subject->written_papers; $i++) {
            $headers[] = "Paper {$i} (out of 100)";
        }

        // Add practical column if applicable
        if ($subject->has_practical) {
            $headers[] = 'Practical (out of 100)';
        }

        // Add project column if applicable
        if ($subject->has_project) {
            $headers[] = 'Project (out of 100)';
        }

        return $headers;
    }

    /**
     * Generate sample CSV template data
     * 
     * NOTE: Combination is no longer used for template generation.
     * Sample index numbers are generic.
     */
    public function generateSampleRows(Subject $subject, int $sampleCount = 3): array
    {
        $rows = [];
        
        for ($i = 1; $i <= $sampleCount; $i++) {
            $row = [
                'S' . str_pad($i, 6, '0', STR_PAD_LEFT),  // Generic sample index
                "SAMPLE CANDIDATE {$i}",
            ];

            // Add sample marks for papers
            for ($j = 1; $j <= $subject->written_papers; $j++) {
                $row[] = 75; // Sample mark
            }

            // Add practical marks if applicable
            if ($subject->has_practical) {
                $row[] = 80;
            }

            // Add project marks if applicable
            if ($subject->has_project) {
                $row[] = 85;
            }

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Generate CSV as string
     * 
     * Combination is no longer a parameter.
     * Template structure is based on subject only.
     */
    public function generateCsv(Subject $subject): string
    {
        $headers = $this->generateTemplateHeaders($subject);
        $samples = $this->generateSampleRows($subject);

        $csv = implode(',', $headers) . "\n";

        foreach ($samples as $row) {
            $csv .= implode(',', array_map(function ($val) {
                // Escape CSV values
                if (is_string($val) && (strpos($val, ',') !== false || strpos($val, '"') !== false)) {
                    return '"' . str_replace('"', '""', $val) . '"';
                }
                return $val;
            }, $row)) . "\n";
        }

        return $csv;
    }

    /**
     * Get template instructions
     */
    public function getInstructions(Subject $subject): string
    {
        $instructions = "ACSEE {$subject->code} ({$subject->name}) - Mark Entry Template\n\n";
        $instructions .= "Instructions:\n";
        $instructions .= "1. Do NOT modify the header row\n";
        $instructions .= "2. Index Number format: S[SCHOOL_CODE]-[INDEX]\n";
        $instructions .= "3. All marks must be numeric (0-100)\n";
        $instructions .= "4. Do NOT include commas or special characters in marks\n";
        $instructions .= "5. Each row must have all required columns\n";
        $instructions .= "6. Delete the sample rows before uploading\n\n";

        $instructions .= "Paper Structure:\n";
        $instructions .= "- Written Papers: {$subject->written_papers}\n";
        if ($subject->has_practical) {
            $instructions .= "- Practical: Yes\n";
        }
        if ($subject->has_project) {
            $instructions .= "- Project: Yes\n";
        }

        return $instructions;
    }
}
