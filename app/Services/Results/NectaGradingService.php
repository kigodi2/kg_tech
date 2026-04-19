<?php

namespace App\Services\Results;

use App\Models\Candidate;
use App\Models\CandidateExamRegistration;
use App\Models\Subject;
use App\Models\SubjectMarks;
use App\Models\GradingProfile;
use App\Models\FinalGrade;
use Illuminate\Database\Eloquent\Collection;

/**
 * NectaGradingService
 * 
 * Implements NECTA grading system where:
 * - GENERAL STUDIES and BASIC APPLIED MATHEMATICS are excluded from GPA and total points
 * - These subjects are only included in TOTAL MARKS calculation
 * - All calculations follow NECTA's official grading methodology
 */
class NectaGradingService
{
    private const EXAM_TYPE_ACSEE = 'ACSEE';
    private const EXAM_TYPE_CSEE = 'CSEE';

    // NECTA Grade Boundaries (Marks to Grade)
    // A: 80-100, B: 70-79, C: 60-69, D: 50-59, E: 40-49, S: 35-39, F: 0-34
    private const GRADE_BOUNDARIES = [
        ['min' => 80, 'max' => 100, 'grade' => 'A', 'competence' => 'Excellent', 'color' => '#00AA7A'],
        ['min' => 70, 'max' => 79.99, 'grade' => 'B', 'competence' => 'Very Good', 'color' => '#1FEE0B'],
        ['min' => 60, 'max' => 69.99, 'grade' => 'C', 'competence' => 'Good', 'color' => '#1FEE0B'],
        ['min' => 50, 'max' => 59.99, 'grade' => 'D', 'competence' => 'Average', 'color' => '#EF7043'],
        ['min' => 40, 'max' => 49.99, 'grade' => 'E', 'competence' => 'Satisfactory', 'color' => '#DEF043'],
        ['min' => 35, 'max' => 39.99, 'grade' => 'S', 'competence' => 'Unsatisfactory', 'color' => '#FF7272'],
        ['min' => 0, 'max' => 34.99, 'grade' => 'F', 'competence' => 'Fail', 'color' => '#FF272F'],
    ];

    // NECTA Points Mapping (Grade to Points)
    private const GRADE_POINTS = [
        'A' => 1,
        'B' => 2,
        'C' => 3,
        'D' => 4,
        'E' => 5,
        'S' => 6,
        'F' => 7,
    ];

    // NECTA CSEE Grade Boundaries (Marks to Grade)
    // A: 75-100, B: 65-74, C: 45-64, D: 30-44, F: 0-29
    // Competence labels are confirmed from NECTA CSEE results pages.
    // CSEE color values below are populated only where exact `bgcolor`
    // values were observed from NECTA result-page markup.
    private const CSEE_GRADE_BOUNDARIES = [
        ['min' => 75, 'max' => 100, 'grade' => 'A', 'competence' => 'Excellent', 'color' => '#00A82A'],
        ['min' => 65, 'max' => 74.99, 'grade' => 'B', 'competence' => 'Very Good', 'color' => '#1FEE0B'],
        ['min' => 45, 'max' => 64.99, 'grade' => 'C', 'competence' => 'Good', 'color' => '#DEF043'],
        ['min' => 30, 'max' => 44.99, 'grade' => 'D', 'competence' => 'Satisfactory', 'color' => '#FF772F'],
        ['min' => 0, 'max' => 29.99, 'grade' => 'F', 'competence' => 'Fail', 'color' => '#FF272F'],
    ];

    // NECTA CSEE Points Mapping (best seven subjects in published result pages)
    private const CSEE_GRADE_POINTS = [
        'A' => 1,
        'B' => 2,
        'C' => 3,
        'D' => 4,
        'F' => 5,
    ];

    private const CSEE_DIVISION_BOUNDARIES = [
        ['min' => 7, 'max' => 17, 'division' => 1, 'competence' => 'Excellent'],
        ['min' => 18, 'max' => 21, 'division' => 2, 'competence' => 'Very Good'],
        ['min' => 22, 'max' => 25, 'division' => 3, 'competence' => 'Good'],
        ['min' => 26, 'max' => 33, 'division' => 4, 'competence' => 'Satisfactory'],
    ];

    // NECTA Division Points Range
    private const DIVISION_BOUNDARIES = [
        ['min' => 3, 'max' => 9, 'division' => 1, 'competence' => 'Excellent'],
        ['min' => 10, 'max' => 12, 'division' => 2, 'competence' => 'Very Good'],
        ['min' => 13, 'max' => 17, 'division' => 3, 'competence' => 'Good'],
        ['min' => 18, 'max' => 21, 'division' => 4, 'competence' => 'Average'],
    ];

    // NECTA GPA Competence Mapping (for examination centre GPA display)
    // GPA is based on average of grade points (A=1, B=2, C=3, D=4, E=5, S=6, F=7)
    private const GPA_COMPETENCE = [
        ['min' => 1.0, 'max' => 1.4, 'grade' => 'A', 'competence' => 'Excellent', 'color' => '#00A82A'],
        ['min' => 1.5, 'max' => 2.4, 'grade' => 'B', 'competence' => 'Very Good', 'color' => '#1FEE0B'],
        ['min' => 2.5, 'max' => 3.4, 'grade' => 'C', 'competence' => 'Good', 'color' => '#1FEE0B'],
        ['min' => 3.5, 'max' => 4.4, 'grade' => 'D', 'competence' => 'Average', 'color' => '#DEF043'],
        ['min' => 4.5, 'max' => 5.4, 'grade' => 'E', 'competence' => 'Satisfactory', 'color' => '#DEF043'],
        ['min' => 5.5, 'max' => 6.4, 'grade' => 'S', 'competence' => 'Unsatisfactory', 'color' => '#FF772F'],
        ['min' => 6.5, 'max' => 7.0, 'grade' => 'F', 'competence' => 'Fail', 'color' => '#FF272F'],
    ];

    // Subjects to exclude from GPA and total points
    private const EXCLUDED_SUBJECTS = [
        'GENERAL STUDIES',
        'BASIC APPLIED MATHEMATICS',
    ];

    /**
     * Calculate grade for marks
     * Marks stored as-is (e.g., 79.5, 79.4)
     */
    public function calculateGrade(float $marks): string
    {
        foreach (self::GRADE_BOUNDARIES as $boundary) {
            if ($marks >= $boundary['min'] && $marks <= $boundary['max']) {
                return $boundary['grade'];
            }
        }
        return 'F';
    }

    public function calculateGradeForExamType(float $marks, string $examTypeCode = self::EXAM_TYPE_ACSEE): string
    {
        foreach ($this->getGradeBoundariesForExamType($examTypeCode) as $boundary) {
            if ($marks >= $boundary['min'] && $marks <= $boundary['max']) {
                return $boundary['grade'];
            }
        }

        return 'F';
    }

    /**
     * Get competence level for grade
     */
    public function getCompetenceLevel(string $grade): string
    {
        foreach (self::GRADE_BOUNDARIES as $boundary) {
            if ($boundary['grade'] === $grade) {
                return $boundary['competence'];
            }
        }
        return 'Unknown';
    }

    public function getCompetenceLevelForExamType(string $grade, string $examTypeCode = self::EXAM_TYPE_ACSEE): string
    {
        foreach ($this->getGradeBoundariesForExamType($examTypeCode) as $boundary) {
            if ($boundary['grade'] === strtoupper($grade)) {
                return $boundary['competence'];
            }
        }

        return 'Unknown';
    }

    /**
     * Get color code (hex) for grade
     * Returns NECTA standard color for competence level display
     */
    public function getGradeColor(string $grade): string
    {
        foreach (self::GRADE_BOUNDARIES as $boundary) {
            if ($boundary['grade'] === $grade) {
                return $boundary['color'];
            }
        }
        return '#CCCCCC'; // Default gray if not found
    }

    public function getGradeColorForExamType(string $grade, string $examTypeCode = self::EXAM_TYPE_ACSEE): string
    {
        foreach ($this->getGradeBoundariesForExamType($examTypeCode) as $boundary) {
            if ($boundary['grade'] === strtoupper($grade)) {
                return $boundary['color'];
            }
        }

        return '#CCCCCC';
    }

    /**
     * Get points for grade
     */
    public function getGradePoints(string $grade): int
    {
        return self::GRADE_POINTS[$grade] ?? 7;
    }

    public function getGradePointsForExamType(string $grade, string $examTypeCode = self::EXAM_TYPE_ACSEE): int
    {
        $grade = strtoupper($grade);
        $mapping = strtoupper($examTypeCode) === self::EXAM_TYPE_CSEE
            ? self::CSEE_GRADE_POINTS
            : self::GRADE_POINTS;

        return (int) ($mapping[$grade] ?? (strtoupper($examTypeCode) === self::EXAM_TYPE_CSEE ? 5 : 7));
    }

    /**
     * Get all grade boundaries
     */
    public function getGradeBoundaries(): array
    {
        return self::GRADE_BOUNDARIES;
    }

    public function getGradeBoundariesForExamType(string $examTypeCode = self::EXAM_TYPE_ACSEE): array
    {
        return strtoupper($examTypeCode) === self::EXAM_TYPE_CSEE
            ? self::CSEE_GRADE_BOUNDARIES
            : self::GRADE_BOUNDARIES;
    }

    /**
     * Get all grade points mapping
     */
    public function getGradePointsMapping(): array
    {
        return self::GRADE_POINTS;
    }

    public function getGradePointsMappingForExamType(string $examTypeCode = self::EXAM_TYPE_ACSEE): array
    {
        return strtoupper($examTypeCode) === self::EXAM_TYPE_CSEE
            ? self::CSEE_GRADE_POINTS
            : self::GRADE_POINTS;
    }

    /**
     * Get GPA-to-competence boundaries.
     */
    public function getGpaCompetenceBoundaries(): array
    {
        return self::GPA_COMPETENCE;
    }

    /**
     * Get default GPA settings for ACSEE.
     */
    public function getDefaultGpaSettings(): array
    {
        return [
            'method' => 'average_points',
            'max_gpa' => 7.0,
            'rounding_decimals' => 2,
            'rounding_mode' => 'half_up',
            'principal_count' => null,
            'include_subsidiary' => false,
        ];
    }

    /**
     * Read-only ACSEE calculation reminders used by UI panels.
     * This mirrors the implemented NECTA calculation intent and must not write data.
     */
    public function getAcseeRulesNotes(): array
    {
        $aggregateRows = collect($this->getGradePointsMapping())
            ->map(fn (int $points, string $grade) => ['grade' => $grade, 'points' => $points])
            ->values()
            ->toArray();

        return [
            'title' => 'NECTA ACSEE Calculation Reminders',
            'version' => 'v1',
            'sections' => [
                [
                    'heading' => 'Subject Overall Mark (Multi-paper)',
                    'items' => [
                        'If any required paper is missing, the subject status is INC and no grade should be assigned.',
                        'Combine paper marks using configured paper weights, then normalize to a 100-point scale.',
                        'Apply grade only after the final normalized subject mark is obtained.',
                    ],
                    'formulas' => [
                        [
                            'label' => 'WeightedSum',
                            'latex' => '\text{WeightedSum}=\sum_{i=1}^{k}(m_i\cdot w_i)',
                            'plain' => 'WeightedSum = Σ(mark_i × weight_i)',
                        ],
                        [
                            'label' => 'WeightedMax',
                            'latex' => '\text{WeightedMax}=\sum_{i=1}^{k}(M_i\cdot w_i)',
                            'plain' => 'WeightedMax = Σ(max_i × weight_i)',
                        ],
                        [
                            'label' => 'SubjectMark100',
                            'latex' => '\text{SubjectMark100}=\left(\frac{\text{WeightedSum}}{\text{WeightedMax}}\right)\times 100',
                            'plain' => 'SubjectMark100 = (WeightedSum / WeightedMax) × 100',
                        ],
                    ],
                ],
                [
                    'heading' => 'AGGT (Aggregates) — ACSEE',
                    'items' => [
                        'AGGT is computed from the 3 combination core subjects only.',
                        'General Studies (GS) is excluded from AGGT.',
                        'Basic Applied Mathematics (BAM) is excluded unless it is defined as a core subject for the candidate combination.',
                        'Grade S and Grade F are included in aggregate mapping using configured points.',
                    ],
                    'tables' => [
                        [
                            'title' => 'Aggregate Points Mapping',
                            'rows' => $aggregateRows,
                        ],
                    ],
                    'formulas' => [
                        [
                            'label' => 'AGGT',
                            'latex' => '\text{AGGT}=\sum_{j=1}^{3}\text{AggPoints}(g_j)',
                            'plain' => 'AGGT = AggPoints(g1) + AggPoints(g2) + AggPoints(g3), for combination core subjects only.',
                        ],
                    ],
                    'examples' => [
                        'PCB example: PHY=C(3), CHEM=B(2), BIO=B(2), BAM=A(ignored), GS=D(ignored) => AGGT=7',
                    ],
                ],
                [
                    'heading' => 'Division Bands (ACSEE)',
                    'items' => [
                        'AGGT 3-9 => Division I',
                        'AGGT 10-12 => Division II',
                        'AGGT 13-17 => Division III',
                        'AGGT 18-21 => Division IV',
                        'AGGT > 21 => Division 0',
                        'Lower AGGT indicates better performance.',
                    ],
                    'tables' => [
                        [
                            'title' => 'Division by AGGT Range',
                            'rows' => [
                                ['grade' => '3-9', 'points' => 'Division I'],
                                ['grade' => '10-12', 'points' => 'Division II'],
                                ['grade' => '13-17', 'points' => 'Division III'],
                                ['grade' => '18-21', 'points' => 'Division IV'],
                                ['grade' => '>21', 'points' => 'Division 0'],
                            ],
                        ],
                    ],
                ],
                [
                    'heading' => 'Institutional GPA (Principal-based)',
                    'items' => [
                        'Institutional GPA uses principal subjects only.',
                        'Compute using principal grade points divided by number of principal subjects.',
                    ],
                    'formulas' => [
                        [
                            'label' => 'InstitutionalGPA',
                            'latex' => '\text{GPA}=\frac{\sum \text{principal grade points}}{\text{number of principal subjects}}',
                            'plain' => 'GPA = (Sum of principal grade points) / (Number of principal subjects)',
                        ],
                    ],
                ],
                [
                    'heading' => 'Final Correct ACSEE Division Algorithm',
                    'items' => [
                        '1. Identify combination core subjects (3).',
                        '2. Convert grades to aggregate points.',
                        '3. Compute AGGT = sum of points of the 3 core subjects.',
                        '4. Map AGGT using division bands: 3-9 => Division I; 10-12 => Division II; 13-17 => Division III; 18-21 => Division IV; >21 => Division 0.',
                    ],
                ],
            ],
        ];
    }

    /**
     * Resolve full grading configuration payload from a grading profile with safe service defaults.
     */
    public function resolveProfileConfig(?GradingProfile $profile): array
    {
        $defaultGradeBoundaries = collect($this->getGradeBoundaries())->values()->map(function (array $row, int $index) {
            return [
                'id' => null,
                'grade' => $row['grade'],
                'name' => $row['competence'],
                'min_mark' => (float) $row['min'],
                'max_mark' => (float) $row['max'],
                'points' => $this->getGradePoints($row['grade']),
                'is_principal' => false,
                'is_subsidiary' => false,
                'sort_order' => $index,
                'is_disabled' => false,
            ];
        })->toArray();

        $defaultGpaGradePoints = collect($this->getGradePointsMapping())->map(function (int $points, string $grade) {
            return [
                'grade' => strtoupper($grade),
                'gpa_point_value' => $points,
            ];
        })->values()->toArray();

        $defaultDivisionRules = collect($this->getDivisionBoundaries())->values()->map(function (array $row, int $index) {
            return [
                'id' => null,
                'division_label' => (string) $row['division'],
                'min_points' => (float) $row['min'],
                'max_points' => (float) $row['max'],
                'sort_order' => $index,
                'notes' => $row['competence'] ?? null,
                'is_disabled' => false,
            ];
        })->toArray();

        $defaultCompetenceRules = collect($this->getGpaCompetenceBoundaries())->values()->map(function (array $row, int $index) {
            return [
                'id' => null,
                'level_label' => $row['competence'] ?? $row['grade'],
                'min_value' => (float) $row['min'],
                'max_value' => (float) $row['max'],
                'basis' => 'GPA',
                'color_code' => $row['color'] ?? null,
                'sort_order' => $index,
                'is_disabled' => false,
            ];
        })->toArray();

        if (!$profile) {
            return [
                'grading_rules' => $defaultGradeBoundaries,
                'gpa_settings' => $this->getDefaultGpaSettings(),
                'gpa_grade_points' => $defaultGpaGradePoints,
                'division_rules' => $defaultDivisionRules,
                'competence_rules' => $defaultCompetenceRules,
            ];
        }

        $profileRules = $profile->gradingRules()->orderBy('sort_order')->get();
        $gradingRules = $profileRules->map(function ($rule, int $index) {
            return [
                'id' => $rule->id,
                'grade' => strtoupper((string) $rule->grade),
                'name' => $rule->grade_name,
                'min_mark' => (float) $rule->min_percentage,
                'max_mark' => (float) $rule->max_percentage,
                'points' => $rule->points ?? $this->getGradePoints((string) $rule->grade),
                'is_principal' => (bool) $rule->is_principal,
                'is_subsidiary' => (bool) $rule->is_subsidiary,
                'sort_order' => $rule->sort_order ?? $index,
                'is_disabled' => (bool) ($rule->is_disabled ?? false),
            ];
        })->values();

        if ($gradingRules->isEmpty() && is_array($profile->grade_boundaries)) {
            $gradingRules = collect($profile->grade_boundaries)->values()->map(function (array $row, int $index) {
                $grade = strtoupper((string) ($row['grade'] ?? 'F'));
                return [
                    'id' => null,
                    'grade' => $grade,
                    'name' => $row['grade_name'] ?? ($row['name'] ?? $this->getCompetenceLevel($grade)),
                    'min_mark' => (float) ($row['min'] ?? $row['min_mark'] ?? 0),
                    'max_mark' => (float) ($row['max'] ?? $row['max_mark'] ?? 0),
                    'points' => $row['points'] ?? $this->getGradePoints($grade),
                    'is_principal' => (bool) ($row['is_principal'] ?? false),
                    'is_subsidiary' => (bool) ($row['is_subsidiary'] ?? false),
                    'sort_order' => $row['sort_order'] ?? $index,
                    'is_disabled' => (bool) ($row['is_disabled'] ?? false),
                ];
            });
        }

        $gpaMapping = is_array($profile->gpa_mapping) ? $profile->gpa_mapping : [];
        $gpaSettings = $this->getDefaultGpaSettings();
        if (isset($gpaMapping['settings']) && is_array($gpaMapping['settings'])) {
            $gpaSettings = array_merge($gpaSettings, $gpaMapping['settings']);
        }
        $gpaGradePoints = collect($gpaMapping['grade_points'] ?? [])->values()->map(function (array $row) {
            return [
                'grade' => strtoupper((string) ($row['grade'] ?? '')),
                'gpa_point_value' => (float) ($row['gpa_point_value'] ?? 0),
            ];
        })->filter(fn (array $row) => $row['grade'] !== '')->values();
        if ($gpaGradePoints->isEmpty()) {
            $gpaGradePoints = collect($defaultGpaGradePoints);
        }

        $competenceLevels = is_array($profile->competence_levels) ? $profile->competence_levels : [];
        $divisionRules = collect($competenceLevels['division_rules'] ?? [])->values()->map(function (array $row, int $index) {
            return [
                'id' => $row['id'] ?? null,
                'division_label' => (string) ($row['division_label'] ?? $row['division'] ?? ''),
                'min_points' => (float) ($row['min_points'] ?? $row['min'] ?? 0),
                'max_points' => (float) ($row['max_points'] ?? $row['max'] ?? 0),
                'sort_order' => $row['sort_order'] ?? $index,
                'notes' => $row['notes'] ?? $row['competence'] ?? null,
                'is_disabled' => (bool) ($row['is_disabled'] ?? false),
            ];
        })->filter(fn (array $row) => $row['division_label'] !== '')->values();
        if ($divisionRules->isEmpty()) {
            $divisionRules = collect($defaultDivisionRules);
        }

        $competenceRules = collect($competenceLevels['rules'] ?? $competenceLevels)->values()->map(function (array $row, int $index) {
            return [
                'id' => $row['id'] ?? null,
                'level_label' => (string) ($row['level_label'] ?? $row['competence'] ?? ''),
                'min_value' => (float) ($row['min_value'] ?? $row['min'] ?? 0),
                'max_value' => (float) ($row['max_value'] ?? $row['max'] ?? 0),
                'basis' => strtoupper((string) ($row['basis'] ?? 'GPA')),
                'color_code' => $row['color_code'] ?? $row['color'] ?? null,
                'sort_order' => $row['sort_order'] ?? $index,
                'is_disabled' => (bool) ($row['is_disabled'] ?? false),
            ];
        })->filter(fn (array $row) => $row['level_label'] !== '')->values();
        if ($competenceRules->isEmpty()) {
            $competenceRules = collect($defaultCompetenceRules);
        }

        return [
            'grading_rules' => $gradingRules->toArray(),
            'gpa_settings' => $gpaSettings,
            'gpa_grade_points' => $gpaGradePoints->toArray(),
            'division_rules' => $divisionRules->toArray(),
            'competence_rules' => $competenceRules->toArray(),
        ];
    }

    public function calculateGradeWithRules(float $marks, array $gradingRules): string
    {
        foreach ($gradingRules as $row) {
            if (!empty($row['is_disabled'])) {
                continue;
            }
            if ($marks >= (float) $row['min_mark'] && $marks <= (float) $row['max_mark']) {
                return strtoupper((string) $row['grade']);
            }
        }
        return 'F';
    }

    public function getGradePointsWithMapping(string $grade, array $gradePoints): float
    {
        $upper = strtoupper($grade);
        foreach ($gradePoints as $row) {
            if (strtoupper((string) ($row['grade'] ?? '')) === $upper) {
                return (float) ($row['gpa_point_value'] ?? 0);
            }
        }
        return (float) $this->getGradePoints($upper);
    }

    public function calculateDivisionWithRules(float $totalPoints, array $divisionRules): array
    {
        if ($totalPoints == 0.0) {
            return ['division' => 0, 'competence' => 'Fail', 'points' => $totalPoints];
        }

        foreach ($divisionRules as $rule) {
            if (!empty($rule['is_disabled'])) {
                continue;
            }
            if ($totalPoints >= (float) $rule['min_points'] && $totalPoints <= (float) $rule['max_points']) {
                return [
                    'division' => is_numeric($rule['division_label']) ? (int) $rule['division_label'] : (string) $rule['division_label'],
                    'competence' => $rule['notes'] ?? 'Unknown',
                    'points' => $totalPoints,
                ];
            }
        }

        return ['division' => 0, 'competence' => 'Fail', 'points' => $totalPoints];
    }

    /**
     * Backward-compatible wrapper that now applies division bands only.
     */
    public function calculateDivisionWithRulesAndEligibility(
        float $totalPoints,
        int $principalPassCount,
        array $divisionRules,
        int $minPrincipalPasses = 2
    ): array {
        unset($principalPassCount, $minPrincipalPasses);

        return $this->calculateDivisionWithRules($totalPoints, $divisionRules);
    }

    /**
     * Backward-compatible wrapper that now applies division bands only.
     */
    public function calculateDivisionWithEligibility(
        float $totalPoints,
        int $principalPassCount,
        int $minPrincipalPasses = 2
    ): array {
        unset($principalPassCount, $minPrincipalPasses);

        return $this->calculateDivision($totalPoints) ?? ['division' => 0, 'competence' => 'Fail', 'points' => $totalPoints];
    }

    public function getCompetenceByBasis(float $value, string $basis, array $competenceRules): array
    {
        $targetBasis = strtoupper($basis);
        foreach ($competenceRules as $rule) {
            if (!empty($rule['is_disabled'])) {
                continue;
            }
            if (strtoupper((string) ($rule['basis'] ?? '')) !== $targetBasis) {
                continue;
            }
            if ($value >= (float) $rule['min_value'] && $value <= (float) $rule['max_value']) {
                return [
                    'level_label' => $rule['level_label'],
                    'color_code' => $rule['color_code'] ?? null,
                    'basis' => $targetBasis,
                ];
            }
        }

        return [
            'level_label' => $targetBasis === 'GPA' ? 'Fail' : 'Unknown',
            'color_code' => null,
            'basis' => $targetBasis,
        ];
    }

    /**
     * Check if subject is excluded from GPA/points calculation
     */
    public function isExcludedSubject(string $subjectName): bool
    {
        return in_array(strtoupper($subjectName), self::EXCLUDED_SUBJECTS);
    }

    public function isExcludedSubjectForAggt(string $subjectName, bool $isCoreSubject = false): bool
    {
        $normalized = strtoupper(trim($subjectName));
        if ($normalized === 'GENERAL STUDIES') {
            return true;
        }

        if ($normalized === 'BASIC APPLIED MATHEMATICS') {
            return !$isCoreSubject;
        }

        return false;
    }

    /**
     * Principal pass grades for ACSEE.
     */
    public function isPrincipalPassGrade(string $grade): bool
    {
        return in_array(strtoupper($grade), ['A', 'B', 'C', 'D', 'E'], true);
    }

    /**
     * Compute AGGT from subject grades.
     * Strict mode: requires principal/core subject ids, otherwise returns null.
     *
     * Expected row shape:
     * ['subject_id' => int|null, 'subject_name' => string|null, 'grade' => string, 'points' => float|int]
     */
    public function calculateAggtFromSubjectGrades(array $subjectGrades, ?array $coreSubjectIds = null): ?float
    {
        if ($coreSubjectIds === null || count($coreSubjectIds) === 0) {
            return null;
        }

        $coreSet = collect($coreSubjectIds)->map(fn ($id) => (int) $id)->values()->all();
        $rows = collect($subjectGrades)->filter(function (array $row) use ($coreSet) {
            $grade = strtoupper((string) ($row['grade'] ?? ''));
            if ($grade === '' || in_array($grade, ['INC', 'X', 'ABS'], true)) {
                return false;
            }
            $isCoreSubject = isset($row['subject_id']) && in_array((int) $row['subject_id'], $coreSet, true);
            if ($this->isExcludedSubjectForAggt((string) ($row['subject_name'] ?? ''), $isCoreSubject)) {
                return false;
            }
            return true;
        });

        $rows = $rows->filter(function (array $row) use ($coreSet) {
            return isset($row['subject_id']) && in_array((int) $row['subject_id'], $coreSet, true);
        })->sortBy(function (array $row) {
            return (float) ($row['points'] ?? 99);
        })->take(3)->values();

        if ($rows->isEmpty()) {
            return null;
        }

        return (float) $rows->sum(fn (array $row) => (float) ($row['points'] ?? 0));
    }

    /**
     * Count principal passes (A-E) on core subjects.
     * Strict mode: requires principal/core subject ids, otherwise returns 0.
     */
    public function countPrincipalPassesFromSubjectGrades(array $subjectGrades, ?array $coreSubjectIds = null): int
    {
        if ($coreSubjectIds === null || count($coreSubjectIds) === 0) {
            return 0;
        }

        $coreSet = collect($coreSubjectIds)->map(fn ($id) => (int) $id)->values()->all();
        $rows = collect($subjectGrades)->filter(function (array $row) use ($coreSet) {
            $grade = strtoupper((string) ($row['grade'] ?? ''));
            if ($grade === '' || in_array($grade, ['INC', 'X', 'ABS'], true)) {
                return false;
            }
            $isCoreSubject = isset($row['subject_id']) && in_array((int) $row['subject_id'], $coreSet, true);
            if ($this->isExcludedSubjectForAggt((string) ($row['subject_name'] ?? ''), $isCoreSubject)) {
                return false;
            }
            return true;
        });

        $rows = $rows->filter(function (array $row) use ($coreSet) {
            return isset($row['subject_id']) && in_array((int) $row['subject_id'], $coreSet, true);
        })->sortBy(function (array $row) {
            return (float) ($row['points'] ?? 99);
        })->take(3);

        return $rows->filter(fn (array $row) => $this->isPrincipalPassGrade((string) ($row['grade'] ?? '')))->count();
    }

    public function analyzeCoreSubjectCompleteness(array $subjectGrades, ?array $coreSubjectIds = null): array
    {
        $coreSet = collect($coreSubjectIds ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (count($coreSet) === 0) {
            return [
                'required_count' => 0,
                'present_count' => 0,
                'missing_subject_ids' => [],
                'is_complete' => true,
            ];
        }

        $presentIds = collect($subjectGrades)
            ->filter(function (array $row) use ($coreSet) {
                $grade = strtoupper((string) ($row['grade'] ?? ''));
                if ($grade === '' || in_array($grade, ['INC', 'X', 'ABS'], true)) {
                    return false;
                }
                if (!isset($row['subject_id']) || !in_array((int) $row['subject_id'], $coreSet, true)) {
                    return false;
                }
                return !$this->isExcludedSubjectForAggt((string) ($row['subject_name'] ?? ''), true);
            })
            ->pluck('subject_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        $missingSubjectIds = array_values(array_diff($coreSet, $presentIds));

        return [
            'required_count' => count($coreSet),
            'present_count' => count($presentIds),
            'missing_subject_ids' => $missingSubjectIds,
            'is_complete' => count($missingSubjectIds) === 0,
        ];
    }

    /**
     * Get list of excluded subjects
     */
    public function getExcludedSubjects(): array
    {
        return self::EXCLUDED_SUBJECTS;
    }

    /**
     * Calculate candidate's total marks (including excluded subjects)
     */
    public function calculateTotalMarks(Candidate $candidate, int $examTypeId, int $year): ?float
    {
        $marks = $candidate->marks()
            ->where('exam_type_id', $examTypeId)
            ->where('year', $year)
            ->get();

        if ($marks->isEmpty()) {
            return null;
        }

        return $marks->sum('marks_obtained');
    }

    /**
     * Calculate candidate's total points (excluding specified subjects)
     */
    public function calculateTotalPoints(Candidate $candidate, int $examTypeId, int $year): ?float
    {
        $marks = $candidate->marks()
            ->with('subject')
            ->where('exam_type_id', $examTypeId)
            ->where('year', $year)
            ->get();

        if ($marks->isEmpty()) {
            return null;
        }

        $subjectGrades = [];
        $principalSubjectIds = $candidate->subjectSelections()
            ->where('exam_type_id', $examTypeId)
            ->where('year', $year)
            ->where('is_active', true)
            ->where('is_principal', true)
            ->pluck('subject_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $coreSubjectIds = !empty($principalSubjectIds) ? $principalSubjectIds : null;

        foreach ($marks as $mark) {
            // Skip INC/X/ABS subjects — they must not affect points
            $subjectStatus = $mark->subject_status ?? null;
            if (in_array($subjectStatus, ['INC', 'X', 'ABS'], true)) {
                continue;
            }

            $subjectName = $mark->subject->name ?? '';

            // Skip excluded subjects
            if ($this->isExcludedSubject($subjectName)) {
                continue;
            }

            if ($mark->marks_obtained === null) {
                continue;
            }

            $grade = $this->calculateGrade($mark->marks_obtained);
            $points = $this->getGradePoints($grade);
            $subjectGrades[] = [
                'subject_id' => $mark->subject_id,
                'subject_name' => $subjectName,
                'grade' => $grade,
                'points' => $points,
            ];
        }

        return $this->calculateAggtFromSubjectGrades($subjectGrades, $coreSubjectIds);
    }

    /**
     * Calculate candidate's GPA
     * GPA = Total Points / Number of valid subjects (excluding general studies and basic applied math)
     */
    public function calculateGPA(Candidate $candidate, int $examTypeId, int $year): ?float
    {
        $marks = $candidate->marks()
            ->with('subject')
            ->where('exam_type_id', $examTypeId)
            ->where('year', $year)
            ->get();

        if ($marks->isEmpty()) {
            return null;
        }

        $totalPoints = 0;
        $validSubjectCount = 0;

        foreach ($marks as $mark) {
            // Skip INC/X/ABS subjects — they must not affect GPA
            $subjectStatus = $mark->subject_status ?? null;
            if (in_array($subjectStatus, ['INC', 'X', 'ABS'], true)) {
                continue;
            }

            $subjectName = $mark->subject->name ?? '';

            // Skip excluded subjects from GPA calculation
            if ($this->isExcludedSubject($subjectName)) {
                continue;
            }

            if ($mark->marks_obtained === null) {
                continue;
            }

            $grade = $this->calculateGrade($mark->marks_obtained);
            $points = $this->getGradePoints($grade);

            $totalPoints += $points;
            $validSubjectCount++;
        }

        if ($validSubjectCount === 0) {
            return null;
        }

        // GPA is calculated as average of points
        return round($totalPoints / $validSubjectCount, 2);
    }

    /**
     * Calculate candidate's division based on total points
     */
    public function calculateDivision(float $totalPoints): ?array
    {
        // Handle 0 points (ABS/INC)
        if ($totalPoints == 0) {
            return [
                'division' => 0,
                'competence' => 'Fail',
                'points' => $totalPoints,
            ];
        }

        foreach (self::DIVISION_BOUNDARIES as $boundary) {
            if ($totalPoints >= $boundary['min'] && $totalPoints <= $boundary['max']) {
                return [
                    'division' => $boundary['division'],
                    'competence' => $boundary['competence'],
                    'points' => $totalPoints,
                ];
            }
        }

        return [
            'division' => 0,
            'competence' => 'Fail',
            'points' => $totalPoints,
        ];
    }

    public function calculateDivisionForExamType(float $totalPoints, string $examTypeCode = self::EXAM_TYPE_ACSEE): ?array
    {
        $boundaries = $this->getDivisionBoundariesForExamType($examTypeCode);

        if ($totalPoints == 0) {
            return [
                'division' => 0,
                'competence' => 'Fail',
                'points' => $totalPoints,
            ];
        }

        foreach ($boundaries as $boundary) {
            if ($totalPoints >= $boundary['min'] && $totalPoints <= $boundary['max']) {
                return [
                    'division' => $boundary['division'],
                    'competence' => $boundary['competence'],
                    'points' => $totalPoints,
                ];
            }
        }

        return [
            'division' => 0,
            'competence' => 'Fail',
            'points' => $totalPoints,
        ];
    }

    /**
     * Get GPA competence level from GPA value
     * Maps a GPA (average of grade points) to its competence level
     */
    public function getGpaCompetence(float $gpa): array
    {
        // NECTA competence bands are interpreted on 1-decimal GPA values.
        // This avoids boundary gaps such as 2.41-2.49 when raw GPA has 4 decimals.
        $normalizedGpa = round($gpa, 1);

        foreach (self::GPA_COMPETENCE as $boundary) {
            if ($normalizedGpa >= $boundary['min'] && $normalizedGpa <= $boundary['max']) {
                return [
                    'grade' => $boundary['grade'],
                    'competence' => $boundary['competence'],
                    'color' => $boundary['color'],
                ];
            }
        }

        // Default to Fail if out of range
        return [
            'grade' => 'F',
            'competence' => 'Fail',
            'color' => '#FF272F',
        ];
    }

    /**
     * Get division boundaries
     */
    public function getDivisionBoundaries(): array
    {
        return self::DIVISION_BOUNDARIES;
    }

    public function getDivisionBoundariesForExamType(string $examTypeCode = self::EXAM_TYPE_ACSEE): array
    {
        return strtoupper($examTypeCode) === self::EXAM_TYPE_CSEE
            ? self::CSEE_DIVISION_BOUNDARIES
            : self::DIVISION_BOUNDARIES;
    }

    /**
     * Calculate overall grade for candidate (usually best grade among subjects)
     */
    public function calculateOverallGrade(Candidate $candidate, int $examTypeId, int $year): ?string
    {
        $marks = $candidate->marks()
            ->with('subject')
            ->where('exam_type_id', $examTypeId)
            ->where('year', $year)
            ->get();

        if ($marks->isEmpty()) {
            return null;
        }

        $bestGrade = 'F';
        $bestPoints = 7;

        foreach ($marks as $mark) {
            $grade = $this->calculateGrade($mark->marks_obtained);
            $points = $this->getGradePoints($grade);

            if ($points < $bestPoints) {
                $bestPoints = $points;
                $bestGrade = $grade;
            }
        }

        return $bestGrade;
    }

    /**
     * Generate complete grading report for candidate
     */
    public function generateGradingReport(Candidate $candidate, int $examTypeId, int $year): ?array
    {
        $marks = $candidate->marks()
            ->with('subject')
            ->where('exam_type_id', $examTypeId)
            ->where('year', $year)
            ->get();

        if ($marks->isEmpty()) {
            return null;
        }

        $subjectGrades = [];
        $includedSubjectGrades = [];
        $excludedSubjectGrades = [];
        $incSubjects = [];

        foreach ($marks as $mark) {
            $subjectName = $mark->subject->name ?? '';
            $subjectStatus = $mark->subject_status ?? null;

            // Handle INC/X/ABS — show status, not grade
            if (in_array($subjectStatus, ['INC', 'X', 'ABS'], true)) {
                $subjectGrade = [
                    'subject_id' => $mark->subject_id,
                    'subject_name' => $subjectName,
                    'marks_obtained' => null,
                    'grade' => $subjectStatus,
                    'points' => null,
                    'competence' => $subjectStatus === 'INC' ? 'Incomplete' : 'Absent',
                    'competence_level' => $subjectStatus,
                    'color' => '#999999',
                    'is_excluded' => true,
                    'subject_status' => $subjectStatus,
                ];
                $subjectGrades[] = $subjectGrade;
                if ($subjectStatus === 'INC') $incSubjects[] = $subjectGrade;
                continue;
            }

            $grade = $this->calculateGrade($mark->marks_obtained);
            $points = $this->getGradePoints($grade);
            $competence = $this->getCompetenceLevel($grade);
            $color = $this->getGradeColor($grade);
            $competenceLevel = "Grade {$grade} ({$competence})";

            $subjectGrade = [
                'subject_id' => $mark->subject_id,
                'subject_name' => $subjectName,
                'marks_obtained' => $mark->marks_obtained,
                'grade' => $grade,
                'points' => $points,
                'competence' => $competence,
                'competence_level' => $competenceLevel,
                'color' => $color,
                'is_excluded' => $this->isExcludedSubject($subjectName),
                'subject_status' => null,
            ];

            $subjectGrades[] = $subjectGrade;

            if ($this->isExcludedSubject($subjectName)) {
                $excludedSubjectGrades[] = $subjectGrade;
            } else {
                $includedSubjectGrades[] = $subjectGrade;
            }
        }

        $totalMarks = $this->calculateTotalMarks($candidate, $examTypeId, $year);
        $totalPoints = $this->calculateTotalPoints($candidate, $examTypeId, $year);
        $gpa = $this->calculateGPA($candidate, $examTypeId, $year);
        $principalSubjectIds = $candidate->subjectSelections()
            ->where('exam_type_id', $examTypeId)
            ->where('year', $year)
            ->where('is_active', true)
            ->where('is_principal', true)
            ->pluck('subject_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();
        $coreSubjectIds = !empty($principalSubjectIds) ? $principalSubjectIds : null;
        $principalPassCount = $this->countPrincipalPassesFromSubjectGrades($includedSubjectGrades, $coreSubjectIds);
        $division = $this->calculateDivisionWithEligibility((float) ($totalPoints ?? 0), $principalPassCount);
        $overallGrade = $this->calculateOverallGrade($candidate, $examTypeId, $year);

        return [
            'candidate_id' => $candidate->id,
            'candidate_name' => $candidate->full_name,
            'exam_type_id' => $examTypeId,
            'year' => $year,
            'subject_grades' => $subjectGrades,
            'included_subject_grades' => $includedSubjectGrades,
            'excluded_subject_grades' => $excludedSubjectGrades,
            'inc_subjects' => $incSubjects,
            'total_marks' => $totalMarks,
            'total_points' => $totalPoints,
            'gpa' => $gpa,
            'division' => $division,
            'overall_grade' => $overallGrade,
            'competence_level' => $division ? $division['competence'] : null,
            'has_inc' => !empty($incSubjects),
            'inc_note' => !empty($incSubjects) ? 'INC subjects excluded from GPA/Division calculation' : null,
        ];
    }

    /**
     * Process grading for all candidates in exam registration
     */
    public function processBatchGrading(int $examTypeId, int $year, ?int $schoolId = null): array
    {
        $query = CandidateExamRegistration::with(['candidate.marks', 'candidate.subject'])
            ->where('exam_type_id', $examTypeId)
            ->where('year', $year);

        if ($schoolId) {
            $query->whereHas('candidate', function ($q) use ($schoolId) {
                $q->where('school_id', $schoolId);
            });
        }

        $registrations = $query->get();
        $results = [];

        foreach ($registrations as $registration) {
            $report = $this->generateGradingReport($registration->candidate, $examTypeId, $year);
            if ($report) {
                $results[] = $report;
            }
        }

        return $results;
    }
}
