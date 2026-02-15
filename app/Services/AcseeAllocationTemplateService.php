<?php

namespace App\Services;

/**
 * ACSEE Allocation Template Service
 * 
 * Generates downloadable CSV templates for bulk subject allocation
 * - SCHOOL template: Combination-driven (template mode)
 * - PRIVATE template: Subject-codes-driven (manual mode)
 */
class AcseeAllocationTemplateService
{
    /**
     * Generate SCHOOL candidate allocation template
     * 
     * @return string CSV content
     */
    public function generateSchoolTemplate(): string
    {
        $csv = fopen('php://memory', 'w');
        
        // Write instructions as comments (first lines)
        fwrite($csv, "# ACSEE School Candidate Subject Allocation Template\n");
        fwrite($csv, "# Instructions: Fill in the exam_year, index_number, combination_code, and replace_allocations columns\n");
        fwrite($csv, "# - exam_year: 4-digit year (e.g., 2026)\n");
        fwrite($csv, "# - index_number: e.g., S0445-0004 (must exist in system)\n");
        fwrite($csv, "# - combination_code: e.g., PCB, HGL, PCM (must exist in system)\n");
        fwrite($csv, "# - replace_allocations: YES or NO (default NO). If YES, existing allocations for this exam year will be deleted\n");
        fwrite($csv, "# Note: Combination subjects will be looked up automatically. Do not include a 'subjects' column.\n");
        fwrite($csv, "#\n");
        
        // Write header row
        fputcsv($csv, ['exam_year', 'index_number', 'combination_code', 'replace_allocations']);
        
        // Write example rows
        fputcsv($csv, ['2026', 'S0445-0001', 'PCB', 'NO']);
        fputcsv($csv, ['2026', 'S0445-0002', 'HGL', 'NO']);
        fputcsv($csv, ['2026', 'S0445-0003', 'PCM', 'NO']);
        
        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);
        
        return $content;
    }

    /**
     * Generate PRIVATE candidate allocation template
     * 
     * @return string CSV content
     */
    public function generatePrivateTemplate(): string
    {
        $csv = fopen('php://memory', 'w');
        
        // Write instructions as comments (first lines)
        fwrite($csv, "# ACSEE Private Candidate Subject Allocation Template\n");
        fwrite($csv, "# Instructions: Fill in the exam_year, index_number, subject_codes, and replace_allocations columns\n");
        fwrite($csv, "# - exam_year: 4-digit year (e.g., 2026)\n");
        fwrite($csv, "# - index_number: e.g., P0652-0502 (must exist in system)\n");
        fwrite($csv, "# - subject_codes: Pipe-separated subject codes (e.g., 111|112|123|145)\n");
        fwrite($csv, "#   - Must include 111 (General Studies)\n");
        fwrite($csv, "#   - Must include at least 3 other subjects (principals)\n");
        fwrite($csv, "#   - Valid codes: 111 (GS), 001-999 (subject codes)\n");
        fwrite($csv, "# - replace_allocations: YES or NO (default NO). If YES, existing allocations for this exam year will be deleted\n");
        fwrite($csv, "#\n");
        
        // Write header row
        fputcsv($csv, ['exam_year', 'index_number', 'subject_codes', 'replace_allocations']);
        
        // Write example rows
        fputcsv($csv, ['2026', 'P0652-0501', '111|112|123|145', 'NO']);
        fputcsv($csv, ['2026', 'P0652-0502', '111|001|002|003', 'NO']);
        fputcsv($csv, ['2026', 'P0652-0503', '111|004|005|006', 'NO']);
        
        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);
        
        return $content;
    }
}
