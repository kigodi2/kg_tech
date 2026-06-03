<?php

namespace App\Services\MarkEntry;

use App\Models\Candidate;
use App\Models\CandidateExamRegistration;
use App\Models\District;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\MarkImportBatch;
use App\Models\RawMark;
use App\Models\Region;
use App\Models\School;
use App\Models\Subject;
use App\Models\SystemEventLog;
use Illuminate\Support\Collection;
use ZipArchive;

class PsleScoresheetFpdfService
{
    private const TEMP_DIR = 'storage/app/temp/psle-scoresheets';

    public function generateSingle(string $examYearLabel, int $schoolId, int $subjectId, string $mode = 'approved', string $layout = 'formal', ?int $actorUserId = null): array
    {
        $data = $this->buildScoresheetData($examYearLabel, $schoolId, $subjectId, $mode, $layout);

        $this->ensureTempDirectory();
        $filename = sprintf(
            '%s_%s_%s_psle_scoresheet.pdf',
            $data['school']->code ?: 'school',
            $data['subject']->code ?: 'subject',
            $data['exam_year']->year_label
        );
        $path = base_path(self::TEMP_DIR . '/' . $filename);
        $this->renderPdf($data, $path);

        $this->logExport('psle_scoresheet_single', $data, $actorUserId, [
            'mode' => $mode,
            'layout' => $layout,
            'school_id' => $schoolId,
            'subject_id' => $subjectId,
            'filename' => $filename,
        ]);

        return [
            'file_path' => $path,
            'filename' => $filename,
        ];
    }

    public function generateEnteredSingle(string $examYearLabel, int $schoolId, int $subjectId, string $mode = 'approved', ?int $actorUserId = null): array
    {
        $data = $this->buildEnteredMarksSheetData($examYearLabel, $schoolId, $subjectId, $mode);

        $this->ensureTempDirectory();
        $filename = sprintf(
            '%s_%s_%s_psle_entered_marks_sheet.pdf',
            $data['school']->code ?: 'school',
            $data['subject']->code ?: 'subject',
            $data['exam_year']->year_label
        );
        $path = base_path(self::TEMP_DIR . '/' . $filename);
        $this->renderPdf($data, $path);

        $this->logExport('psle_entered_marks_sheet_single', $data, $actorUserId, [
            'mode' => $mode,
            'school_id' => $schoolId,
            'subject_id' => $subjectId,
            'filename' => $filename,
        ]);

        return [
            'file_path' => $path,
            'filename' => $filename,
        ];
    }

    public function generateSchoolZip(string $examYearLabel, int $schoolId, string $mode = 'approved', string $layout = 'formal', ?int $actorUserId = null): array
    {
        $examYear = $this->resolveExamYear($examYearLabel);
        $school = School::findOrFail($schoolId);
        $schoolFolderName = $this->displayFileLabel($school->name, 'SCHOOL');
        $subjects = $this->getSubjectsWithMarks($examYearLabel, $schoolId, $mode);

        if ($subjects->isEmpty()) {
            throw new \RuntimeException("No PSLE scoresheet subjects were found for {$school->name}.");
        }

        $this->ensureTempDirectory();
        $zipFilename = sprintf('%s_%s_psle_scoresheets.zip', $schoolFolderName, $examYear->year_label);
        $zipPath = base_path(self::TEMP_DIR . '/' . $zipFilename);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Failed to create school scoresheet ZIP archive.');
        }

        $pdfFiles = [];
        foreach ($subjects as $subject) {
            $data = $this->buildScoresheetData($examYearLabel, (int) $school->id, (int) $subject->id, $mode, $layout);
            $subjectFileName = $this->displayFileLabel($subject->name ?? $subject->code, 'SUBJECT');
            $pdfFilename = sprintf('%s_%s.pdf', $subjectFileName, $examYear->year_label);
            $pdfPath = base_path(self::TEMP_DIR . '/' . $pdfFilename);
            $this->renderPdf($data, $pdfPath);
            $zip->addFile($pdfPath, $schoolFolderName . '/' . $pdfFilename);
            $pdfFiles[] = $pdfPath;
        }

        $zip->close();
        foreach ($pdfFiles as $pdfFile) {
            @unlink($pdfFile);
        }

        $this->logExport('psle_scoresheet_school_zip', [
            'exam_year' => $examYear,
            'school' => $school,
            'subject' => null,
            'summary' => ['candidate_count' => 0],
        ], $actorUserId, [
            'mode' => $mode,
            'layout' => $layout,
            'school_id' => $schoolId,
            'subject_count' => $subjects->count(),
            'filename' => $zipFilename,
        ]);

        return [
            'file_path' => $zipPath,
            'filename' => $zipFilename,
            'total_subjects' => $subjects->count(),
        ];
    }

    public function generateDistrictZip(string $examYearLabel, int $districtId, string $mode = 'approved', string $layout = 'formal', ?int $actorUserId = null): array
    {
        $examYear = $this->resolveExamYear($examYearLabel);
        $district = District::findOrFail($districtId);
        $schools = $this->getSchoolsWithMarks($examYearLabel, $districtId, $mode);

        if ($schools->isEmpty()) {
            throw new \RuntimeException("No PSLE schools with registered candidates were found in {$district->name}.");
        }

        $this->ensureTempDirectory();
        $zipFilename = sprintf('district_%s_%s_psle_scoresheets.zip', $district->code ?: $district->id, $examYear->year_label);
        $zipPath = base_path(self::TEMP_DIR . '/' . $zipFilename);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Failed to create district scoresheet ZIP archive.');
        }

        $pdfFiles = [];
        foreach ($schools as $school) {
            $subjects = $this->getSubjectsWithMarks($examYearLabel, (int) $school->id, $mode);
            foreach ($subjects as $subject) {
                $data = $this->buildScoresheetData($examYearLabel, (int) $school->id, (int) $subject->id, $mode, $layout);
                $schoolFileName = $this->fileLabel($school->name, 'school');
                $schoolFolderName = $this->displayFileLabel($school->name, 'SCHOOL');
                $subjectFileName = $this->displayFileLabel($subject->name ?? $subject->code, 'SUBJECT');
                $entryName = sprintf('%s/%s_%s.pdf', $schoolFolderName, $subjectFileName, $examYear->year_label);
                $pdfPath = base_path(self::TEMP_DIR . '/' . str_replace('/', '_', $entryName));
                $this->renderPdf($data, $pdfPath);
                $zip->addFile($pdfPath, $entryName);
                $pdfFiles[] = $pdfPath;
            }
        }

        $zip->close();
        foreach ($pdfFiles as $pdfFile) {
            @unlink($pdfFile);
        }

        $this->logExport('psle_scoresheet_district_zip', [
            'exam_year' => $examYear,
            'school' => null,
            'subject' => null,
            'summary' => ['candidate_count' => 0],
        ], $actorUserId, [
            'mode' => $mode,
            'layout' => $layout,
            'district_id' => $districtId,
            'school_count' => $schools->count(),
            'filename' => $zipFilename,
        ]);

        return [
            'file_path' => $zipPath,
            'filename' => $zipFilename,
            'total_schools' => $schools->count(),
        ];
    }

    public function generateRegionZip(string $examYearLabel, int $regionId, string $mode = 'approved', string $layout = 'formal', ?int $actorUserId = null): array
    {
        $examYear = $this->resolveExamYear($examYearLabel);
        $region = Region::findOrFail($regionId);
        $districts = District::query()->where('region_id', $regionId)->orderBy('name')->get();

        $this->ensureTempDirectory();
        $zipFilename = sprintf('region_%s_%s_psle_scoresheets.zip', $region->code ?: $region->id, $examYear->year_label);
        $zipPath = base_path(self::TEMP_DIR . '/' . $zipFilename);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Failed to create region scoresheet ZIP archive.');
        }

        $pdfFiles = [];
        $exportedSchoolCount = 0;

        foreach ($districts as $district) {
            $schools = $this->getSchoolsWithMarks($examYearLabel, (int) $district->id, $mode);
            foreach ($schools as $school) {
                $subjects = $this->getSubjectsWithMarks($examYearLabel, (int) $school->id, $mode);
                foreach ($subjects as $subject) {
                    $data = $this->buildScoresheetData($examYearLabel, (int) $school->id, (int) $subject->id, $mode, $layout);
                    $schoolFolderName = $this->displayFileLabel($school->name, 'SCHOOL');
                    $subjectFileName = $this->displayFileLabel($subject->name ?? $subject->code, 'SUBJECT');
                    $entryName = sprintf(
                        '%s/%s/%s_%s.pdf',
                        $district->code ?: $district->id,
                        $schoolFolderName,
                        $subjectFileName,
                        $examYear->year_label
                    );
                    $pdfPath = base_path(self::TEMP_DIR . '/' . str_replace('/', '_', $entryName));
                    $this->renderPdf($data, $pdfPath);
                    $zip->addFile($pdfPath, $entryName);
                    $pdfFiles[] = $pdfPath;
                }
                $exportedSchoolCount++;
            }
        }

        if ($exportedSchoolCount === 0) {
            $zip->close();
            @unlink($zipPath);
            throw new \RuntimeException("No PSLE schools with registered candidates were found in {$region->name}.");
        }

        $zip->close();
        foreach ($pdfFiles as $pdfFile) {
            @unlink($pdfFile);
        }

        $this->logExport('psle_scoresheet_region_zip', [
            'exam_year' => $examYear,
            'school' => null,
            'subject' => null,
            'summary' => ['candidate_count' => 0],
        ], $actorUserId, [
            'mode' => $mode,
            'layout' => $layout,
            'region_id' => $regionId,
            'school_count' => $exportedSchoolCount,
            'filename' => $zipFilename,
        ]);

        return [
            'file_path' => $zipPath,
            'filename' => $zipFilename,
            'total_schools' => $exportedSchoolCount,
        ];
    }

    public function generateEnteredSchoolZip(string $examYearLabel, int $schoolId, string $mode = 'approved', ?int $actorUserId = null): array
    {
        $examYear = $this->resolveExamYear($examYearLabel);
        $school = School::findOrFail($schoolId);
        $schoolFolderName = $this->displayFileLabel($school->name, 'SCHOOL');
        $subjects = $this->getEnteredSubjects($examYearLabel, $schoolId, $mode);

        if ($subjects->isEmpty()) {
            throw new \RuntimeException("No PSLE entered-mark subjects were found for {$school->name}.");
        }

        $this->ensureTempDirectory();
        $zipFilename = sprintf('%s_%s_psle_entered_marks_sheets.zip', $schoolFolderName, $examYear->year_label);
        $zipPath = base_path(self::TEMP_DIR . '/' . $zipFilename);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Failed to create school entered marks ZIP archive.');
        }

        $pdfFiles = [];
        foreach ($subjects as $subject) {
            $data = $this->buildEnteredMarksSheetData($examYearLabel, (int) $school->id, (int) $subject->id, $mode);
            $subjectFileName = $this->displayFileLabel($subject->name ?? $subject->code, 'SUBJECT');
            $pdfFilename = sprintf('%s_%s_entered.pdf', $subjectFileName, $examYear->year_label);
            $pdfPath = base_path(self::TEMP_DIR . '/' . $pdfFilename);
            $this->renderPdf($data, $pdfPath);
            $zip->addFile($pdfPath, $schoolFolderName . '/' . $pdfFilename);
            $pdfFiles[] = $pdfPath;
        }

        $zip->close();
        foreach ($pdfFiles as $pdfFile) {
            @unlink($pdfFile);
        }

        $this->logExport('psle_entered_marks_sheet_school_zip', [
            'exam_year' => $examYear,
            'school' => $school,
            'subject' => null,
            'summary' => ['candidate_count' => 0],
        ], $actorUserId, [
            'mode' => $mode,
            'school_id' => $schoolId,
            'subject_count' => $subjects->count(),
            'filename' => $zipFilename,
        ]);

        return [
            'file_path' => $zipPath,
            'filename' => $zipFilename,
            'total_subjects' => $subjects->count(),
        ];
    }

    public function generateEnteredDistrictZip(string $examYearLabel, int $districtId, string $mode = 'approved', ?int $actorUserId = null): array
    {
        $examYear = $this->resolveExamYear($examYearLabel);
        $district = District::findOrFail($districtId);
        $schools = $this->getSchoolsWithEnteredMarks($examYearLabel, $districtId, $mode);

        if ($schools->isEmpty()) {
            throw new \RuntimeException("No PSLE schools with entered marks were found in {$district->name}.");
        }

        $this->ensureTempDirectory();
        $zipFilename = sprintf('district_%s_%s_psle_entered_marks_sheets.zip', $district->code ?: $district->id, $examYear->year_label);
        $zipPath = base_path(self::TEMP_DIR . '/' . $zipFilename);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Failed to create district entered marks ZIP archive.');
        }

        $pdfFiles = [];
        foreach ($schools as $school) {
            $subjects = $this->getEnteredSubjects($examYearLabel, (int) $school->id, $mode);
            foreach ($subjects as $subject) {
                $data = $this->buildEnteredMarksSheetData($examYearLabel, (int) $school->id, (int) $subject->id, $mode);
                $schoolFolderName = $this->displayFileLabel($school->name, 'SCHOOL');
                $subjectFileName = $this->displayFileLabel($subject->name ?? $subject->code, 'SUBJECT');
                $entryName = sprintf('%s/%s_%s_entered.pdf', $schoolFolderName, $subjectFileName, $examYear->year_label);
                $pdfPath = base_path(self::TEMP_DIR . '/' . str_replace('/', '_', $entryName));
                $this->renderPdf($data, $pdfPath);
                $zip->addFile($pdfPath, $entryName);
                $pdfFiles[] = $pdfPath;
            }
        }

        $zip->close();
        foreach ($pdfFiles as $pdfFile) {
            @unlink($pdfFile);
        }

        $this->logExport('psle_entered_marks_sheet_district_zip', [
            'exam_year' => $examYear,
            'school' => null,
            'subject' => null,
            'summary' => ['candidate_count' => 0],
        ], $actorUserId, [
            'mode' => $mode,
            'district_id' => $districtId,
            'school_count' => $schools->count(),
            'filename' => $zipFilename,
        ]);

        return [
            'file_path' => $zipPath,
            'filename' => $zipFilename,
            'total_schools' => $schools->count(),
        ];
    }

    public function generateEnteredRegionZip(string $examYearLabel, int $regionId, string $mode = 'approved', ?int $actorUserId = null): array
    {
        $examYear = $this->resolveExamYear($examYearLabel);
        $region = Region::findOrFail($regionId);
        $districts = District::query()->where('region_id', $regionId)->orderBy('name')->get();

        $this->ensureTempDirectory();
        $regionFileName = $this->displayFileLabel($region->name ?? ('region_' . $region->id), 'REGION');
        $zipFilename = sprintf('%s_%s_psle_entered_marks_sheets.zip', $regionFileName, $examYear->year_label);
        $zipPath = base_path(self::TEMP_DIR . '/' . $zipFilename);

        $zip = new ZipArchive();
        if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Failed to create region entered marks ZIP archive.');
        }

        $pdfFiles = [];
        $exportedSchoolCount = 0;

        $districtNames = District::query()
            ->where('region_id', $regionId)
            ->pluck('name', 'id');

        $schools = $this->getRegionSchoolsWithEnteredMarks($examYearLabel, $regionId, $mode);

        foreach ($schools as $school) {
            $subjects = $this->getEnteredSubjects($examYearLabel, (int) $school->id, $mode);

            $districtFolderName = $this->displayFileLabel(
                $districtNames[$school->district_id] ?? ('DISTRICT_' . $school->district_id),
                'DISTRICT'
            );

            $schoolFolderName = $this->displayFileLabel($school->name, 'SCHOOL');

            foreach ($subjects as $subject) {
                $data = $this->buildEnteredMarksSheetData($examYearLabel, (int) $school->id, (int) $subject->id, $mode);

                $subjectFileName = $this->displayFileLabel($subject->name ?? $subject->code, 'SUBJECT');

                $entryName = sprintf(
                    '%s/%s/%s_%s_entered.pdf',
                    $districtFolderName,
                    $schoolFolderName,
                    $subjectFileName,
                    $examYear->year_label
                );

                $pdfPath = base_path(self::TEMP_DIR . '/' . str_replace('/', '_', $entryName));

                $this->renderPdf($data, $pdfPath);
                $zip->addFile($pdfPath, $entryName);
                $pdfFiles[] = $pdfPath;
            }

            $exportedSchoolCount++;
        }

        if ($exportedSchoolCount === 0) {
            $zip->close();
            @unlink($zipPath);
            throw new \RuntimeException("No PSLE schools with entered marks were found in {$region->name}.");
        }

        $zip->close();
        foreach ($pdfFiles as $pdfFile) {
            @unlink($pdfFile);
        }

        $this->logExport('psle_entered_marks_sheet_region_zip', [
            'exam_year' => $examYear,
            'school' => null,
            'subject' => null,
            'summary' => ['candidate_count' => 0],
        ], $actorUserId, [
            'mode' => $mode,
            'region_id' => $regionId,
            'school_count' => $exportedSchoolCount,
            'filename' => $zipFilename,
        ]);

        return [
            'file_path' => $zipPath,
            'filename' => $zipFilename,
            'total_schools' => $exportedSchoolCount,
        ];
    }

    public function getSubjectsWithMarks(string $examYearLabel, int $schoolId, string $mode = 'approved'): Collection
    {
        $examYear = $this->resolveExamYear($examYearLabel);
        $psle = $this->resolvePsleExamType();

        $registeredCandidateIds = $this->registeredCandidateIds($schoolId, $examYear, $psle);
        if ($registeredCandidateIds->isEmpty()) {
            return collect();
        }

        $selectedSubjectIds = \App\Models\CandidateSubjectSelection::query()
            ->whereIn('candidate_id', $registeredCandidateIds)
            ->where('exam_type_id', $psle->id)
            ->where(function ($query) use ($examYear) {
                $query->where('exam_year_id', $examYear->id)
                    ->orWhere('year', (int) $examYear->year_label);
            })
            ->pluck('subject_id')
            ->filter()
            ->unique()
            ->values();

        $query = Subject::query()
            ->select('id', 'code', 'name', 'subject_group_label', 'paper_pattern_label')
            ->where('exam_type_id', $psle->id)
            ->where('is_active', true);

        if ($selectedSubjectIds->isNotEmpty()) {
            $query->whereIn('id', $selectedSubjectIds);
        }

        return $query
            ->orderBy('code')
            ->get()
            ->map(fn (Subject $subject) => (object) [
                'id' => $subject->id,
                'code' => $subject->code,
                'name' => $subject->name,
                'subject_group_label' => $subject->subject_group_label,
                'paper_pattern_label' => $subject->paper_pattern_label,
                'batch_status' => null,
                'latest_imported_at' => null,
            ]);
    }

    public function getEnteredSubjects(string $examYearLabel, int $schoolId, string $mode = 'approved'): Collection
    {
        return RawMark::query()
            ->join('mark_import_batches', 'mark_import_batches.id', '=', 'raw_marks.mark_import_batch_id')
            ->join('subjects', 'subjects.id', '=', 'raw_marks.subject_id')
            ->where('mark_import_batches.exam_type_id', $this->resolvePsleExamType()->id)
            ->where('mark_import_batches.exam_year', (string) $this->resolveExamYear($examYearLabel)->year_label)
            ->where('mark_import_batches.school_id', $schoolId)
            ->whereIn('mark_import_batches.status', $this->allowedStatuses($mode))
            ->where('raw_marks.has_errors', false)
            ->select(
                'subjects.id',
                'subjects.code',
                'subjects.name',
                'subjects.subject_group_label',
                'subjects.paper_pattern_label'
            )
            ->groupBy('subjects.id', 'subjects.code', 'subjects.name', 'subjects.subject_group_label', 'subjects.paper_pattern_label')
            ->orderBy('subjects.code')
            ->get()
            ->map(fn ($subject) => (object) [
                'id' => $subject->id,
                'code' => $subject->code,
                'name' => $subject->name,
                'subject_group_label' => $subject->subject_group_label,
                'paper_pattern_label' => $subject->paper_pattern_label,
                'batch_status' => null,
                'latest_imported_at' => null,
            ]);
    }

    public function buildScoresheetData(string $examYearLabel, int $schoolId, int $subjectId, string $mode = 'approved', string $layout = 'formal'): array
    {
        $examYear = $this->resolveExamYear($examYearLabel);
        $school = School::query()->with(['district:id,name,code', 'region:id,name,code'])->findOrFail($schoolId);
        $subject = Subject::findOrFail($subjectId);
        $psle = $this->resolvePsleExamType();

        $candidateQuery = Candidate::query()
            ->select('candidates.id', 'candidates.candidate_id', 'candidates.prem_no', 'candidates.full_name', 'candidates.gender')
            ->join('candidate_exam_registrations', 'candidate_exam_registrations.candidate_id', '=', 'candidates.id')
            ->where('candidates.school_id', $schoolId)
            ->where('candidate_exam_registrations.exam_type_id', $psle->id)
            ->where(function ($query) use ($examYear) {
                $query->where('candidate_exam_registrations.exam_year_id', $examYear->id)
                    ->orWhere('candidate_exam_registrations.year', (int) $examYear->year_label);
            })
            ->distinct()
            ->orderBy('candidates.candidate_id');

        $selectedCandidateIds = \App\Models\CandidateSubjectSelection::query()
            ->where('subject_id', $subjectId)
            ->where('exam_type_id', $psle->id)
            ->where(function ($query) use ($examYear) {
                $query->where('exam_year_id', $examYear->id)
                    ->orWhere('year', (int) $examYear->year_label);
            })
            ->pluck('candidate_id')
            ->filter()
            ->unique()
            ->values();

        if ($selectedCandidateIds->isNotEmpty()) {
            $candidateQuery->whereIn('candidates.id', $selectedCandidateIds);
        }

        $candidates = $candidateQuery->get();

        $candidates = \App\Services\PsleCandidateRosterService::deduplicate($candidates, $school->code);

        if ($candidates->isEmpty()) {
            throw new \RuntimeException("No registered PSLE candidates were found for {$school->name} / {$subject->name} in {$examYear->year_label}.");
        }

        $rows = $candidates->values()->map(function (Candidate $candidate, int $index) {
            return [
                'position' => $index + 1,
                'candidate_number' => $candidate->candidate_id ?: '-',
                'prem_no' => $candidate->prem_no ?: '-',
                'sex' => strtoupper(substr((string) ($candidate->gender ?: '-'), 0, 1)),
                'mark_display' => '',
                'initials' => '',
                'remarks' => '',
            ];
        })->all();

        $summary = [
            'candidate_count' => count($rows),
            'male_count' => collect($rows)->where('sex', 'M')->count(),
            'female_count' => collect($rows)->where('sex', 'F')->count(),
            'blank_mark_columns' => count($rows),
            'source' => $selectedCandidateIds->isNotEmpty() ? 'Subject allocation roster' : 'Registered PSLE candidate roster',
        ];

        return [
            'exam_year' => $examYear,
            'school' => $school,
            'subject' => $subject,
            'rows' => $rows,
            'summary' => $summary,
            'generated_at' => now(),
            'generated_by' => auth()->user()?->name ?? 'System',
            'mode' => $mode,
            'print_layout' => $layout === 'condensed' ? 'condensed' : 'formal',
            'sheet_variant' => 'blank',
        ];
    }

    public function buildEnteredMarksSheetData(string $examYearLabel, int $schoolId, int $subjectId, string $mode = 'approved'): array
    {
        $examYear = $this->resolveExamYear($examYearLabel);
        $school = School::query()->with(['district:id,name,code', 'region:id,name,code'])->findOrFail($schoolId);
        $subject = Subject::findOrFail($subjectId);
        $allowedStatuses = $this->allowedStatuses($mode);

        $marks = RawMark::query()
            ->select('raw_marks.*')
            ->join('mark_import_batches', 'mark_import_batches.id', '=', 'raw_marks.mark_import_batch_id')
            ->where('mark_import_batches.exam_type_id', $this->resolvePsleExamType()->id)
            ->where('mark_import_batches.exam_year', (string) $examYear->year_label)
            ->where('mark_import_batches.school_id', $schoolId)
            ->where('mark_import_batches.subject_id', $subjectId)
            ->whereIn('mark_import_batches.status', $allowedStatuses)
            ->where('raw_marks.subject_id', $subjectId)
            ->where('raw_marks.has_errors', false)
            ->with(['candidate:id,candidate_id,prem_no,gender', 'batch:id,batch_code,status,imported_at,approved_at,locked_at'])
            ->orderByDesc('mark_import_batches.imported_at')
            ->orderByDesc('mark_import_batches.id')
            ->orderBy('raw_marks.row_number')
            ->get()
            ->unique(fn (RawMark $mark) => $mark->candidate_id ?: $mark->candidate_index_number)
            ->sortBy(fn (RawMark $mark) => [$mark->candidate_index_number ?: '', $mark->row_number ?: 0])
            ->values();

        if ($marks->isEmpty()) {
            throw new \RuntimeException("No entered PSLE marks were found for {$school->name} / {$subject->name} in {$examYear->year_label}.");
        }

        $rows = $marks->map(function (RawMark $mark, int $index) {
            $candidate = $mark->candidate;
            $status = strtoupper((string) ($mark->subject_status ?? ''));
            $markValue = $status !== '' ? $status : $this->formatMark($mark->paper_1_marks);
            $remarks = $status !== ''
                ? ($mark->status_reason ?: 'Subject status recorded during intake.')
                : (($mark->has_warnings && is_array($mark->warning_messages) && count($mark->warning_messages) > 0) ? implode('; ', $mark->warning_messages) : '');

            return [
                'position' => $index + 1,
                'candidate_number' => $mark->candidate_index_number ?: ($candidate?->candidate_id ?? '-'),
                'prem_no' => $candidate?->prem_no ?: (data_get($mark->raw_data, 'prem_no') ?: '-'),
                'sex' => strtoupper(substr((string) ($candidate?->gender ?: data_get($mark->raw_data, 'sex', '-')), 0, 1)),
                'mark_display' => $markValue,
                'status' => $status !== '' ? $status : strtoupper((string) ($mark->batch?->status ?? '')),
                'batch_code' => $mark->batch?->batch_code ?: '-',
                'initials' => '',
                'verification_date' => '',
                'remarks' => $remarks,
            ];
        })->all();

        $summary = [
            'candidate_count' => count($rows),
            'male_count' => collect($rows)->where('sex', 'M')->count(),
            'female_count' => collect($rows)->where('sex', 'F')->count(),
            'numeric_mark_count' => collect($rows)->filter(fn (array $row) => is_numeric($row['mark_display']))->count(),
            'inc_count' => collect($rows)->where('status', 'INC')->count(),
            'latest_batch_code' => (string) (optional($marks->first()?->batch)->batch_code ?: '-'),
        ];

        return [
            'exam_year' => $examYear,
            'school' => $school,
            'subject' => $subject,
            'rows' => $rows,
            'summary' => $summary,
            'generated_at' => now(),
            'generated_by' => auth()->user()?->name ?? 'System',
            'mode' => $mode,
            'sheet_variant' => 'entered',
        ];
    }

    private function renderPdf(array $data, string $outputPath): void
    {
        if (!defined('FPDF_FONTPATH')) {
            define('FPDF_FONTPATH', app_path('Support/Pdf/font/'));
        }

        require_once app_path('Support/Pdf/fpdf.php');

        $data['last_page_number'] = $this->determineLastPageNumber($data);

        $pdf = new class($this, $data) extends \FPDF {
            public function __construct(
                private PsleScoresheetFpdfService $service,
                private array $data
            ) {
                parent::__construct('P', 'mm', 'A4');
                $this->SetMargins(8, 10, 8);
                $this->SetAutoPageBreak(true, 12);
                $this->AliasNbPages();
            }

            public function Header(): void
            {
                if ($this->PageNo() === 1) {
                    $this->service->renderHeader($this, $this->data);
                    return;
                }

                $this->service->renderContinuationHeader($this, $this->data);
            }

            public function Footer(): void
            {
                $this->service->renderFooter($this, $this->data);
            }
        };

        $pdf->AddPage();
        $this->renderRows($pdf, $data['rows'] ?? [], $data['sheet_variant'] ?? 'blank', $data['print_layout'] ?? 'formal');
        $pdf->Output('F', $outputPath);
    }

    public function text(?string $value): string
    {
        $value = trim((string) $value);
        if ($value === '') {
            return '';
        }

        $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);

        return $ascii !== false ? $ascii : $value;
    }

    public function renderHeader(\FPDF $pdf, array $data): void
    {
        if (($data['sheet_variant'] ?? 'blank') === 'blank' && ($data['print_layout'] ?? 'formal') === 'condensed') {
            $this->renderCondensedFirstPageHeader($pdf, $data);
            return;
        }

        $pageWidth = $pdf->GetPageWidth();
        $usableWidth = $pageWidth - 16;
        $emblem = public_path('images/emblem.jpg');
        if (!is_file($emblem)) {
            $emblem = public_path('images/emblem.png');
        }

        $pdf->SetFillColor(248, 250, 252);
        $pdf->Rect(0, 0, $pageWidth, $pdf->GetPageHeight(), 'F');
        $pdf->SetFillColor(15, 23, 42);
        $pdf->Rect(0, 0, $pageWidth, 4, 'F');

        if (is_file($emblem)) {
            $pdf->Image($emblem, 10, 9, 12, 12);
            $pdf->Image($emblem, $pageWidth - 22, 9, 12, 12);
        }

        $titleBlockX = 28;
        $titleBlockWidth = $pageWidth - 56;
        $pdf->SetXY($titleBlockX, 9);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetFont('Helvetica', 'B', 9);
        $pdf->Cell($titleBlockWidth, 4, $this->text("PRIME MINISTER'S OFFICE"), 0, 1, 'C');
        $pdf->SetTextColor(30, 64, 175);
        $pdf->SetX($titleBlockX);
        $pdf->Cell($titleBlockWidth, 4, $this->text('REGIONAL ADMINISTRATION AND LOCAL GOVERNMENT'), 0, 1, 'C');
        $pdf->SetTextColor(71, 85, 105);
        $pdf->SetFont('Helvetica', 'B', 8.9);
        $pdf->SetX($titleBlockX);
        $pdf->Cell($titleBlockWidth, 4.1, $this->text('SPECIAL ACADEMIC ZONE'), 0, 1, 'C');
        $pdf->SetFont('Helvetica', '', 8.8);
        $pdf->SetX($titleBlockX);
        $pdf->Cell($titleBlockWidth, 4.1, $this->text('TANGA, IRINGA, SINGIDA, MOROGORO, DODOMA, LINDI, MTWARA AND TABORA'), 0, 1, 'C');
        $pdf->SetTextColor(30, 64, 175);
        $pdf->SetFont('Helvetica', 'B', 8.9);
        $pdf->SetX($titleBlockX);
        $pdf->Cell($titleBlockWidth, 4.2, $this->text('STANDARD SEVEN ZONAL JOINT MOCK EXAMINATION - MAY, 2026'), 0, 1, 'C');
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetFont('Helvetica', 'B', 9.2);
        $pdf->SetX($titleBlockX);
        $documentTitle = ($data['sheet_variant'] ?? 'blank') === 'entered'
            ? 'ENTERED MARKS VERIFICATION SHEET - ' . strtoupper((string) ($data['school']->name ?? 'SCHOOL'))
            : 'OFFICIAL MARKING SCORESHEET - ' . strtoupper((string) ($data['school']->name ?? 'SCHOOL'));
        $pdf->Cell($titleBlockWidth, 4.2, $this->text($documentTitle), 0, 1, 'C');

        $pdf->Ln(1);
        $pdf->SetX(8);
        $pdf->SetFillColor(219, 234, 254);
        $pdf->SetTextColor(30, 64, 175);
        $pdf->SetFont('Helvetica', 'B', 6.8);
        $banner = ($data['sheet_variant'] ?? 'blank') === 'entered'
            ? 'PROFESSIONAL PSLE ENTERED MARKS REPORT'
            : 'PROFESSIONAL PSLE SCORESHEET REPORT';
        $pdf->Cell($usableWidth, 5, $this->text($banner), 0, 1, 'C', true);
        $pdf->Ln(3);

        $summary = $data['summary'];
        $pdf->Ln(2);
        $this->renderSummaryBlock($pdf, $data, $summary);

        if (($data['mode'] ?? 'approved') === 'all') {
            $currentY = $pdf->GetY();
            $pdf->SetFillColor(254, 226, 226);
            $pdf->SetDrawColor(248, 113, 113);
            $pdf->Rect($pageWidth - 56, 21, 48, 9, 'DF');
            $pdf->SetXY($pageWidth - 56, 23.5);
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->SetTextColor(153, 27, 27);
            $pdf->Cell(48, 3, $this->text('PREVIEW / DRAFT'), 0, 0, 'C');
            $pdf->SetY($currentY);
        }

        $this->renderTableHeader($pdf, $data);
    }

    public function renderCondensedFirstPageHeader(\FPDF $pdf, array $data): void
    {
        $pageWidth = $pdf->GetPageWidth();
        $usableWidth = $pageWidth - 16;

        $pdf->SetFillColor(248, 250, 252);
        $pdf->Rect(0, 0, $pageWidth, 34, 'F');
        $pdf->SetFillColor(15, 23, 42);
        $pdf->Rect(0, 0, $pageWidth, 3, 'F');

        $pdf->SetXY(8, 8);
        $pdf->SetTextColor(15, 23, 42);
        $pdf->SetFont('Helvetica', 'B', 10);
        $pdf->Cell($usableWidth, 5, $this->text('PRIMARY SCHOOL LEAVING EXAMINATION - ' . $data['exam_year']->year_label), 0, 1, 'C');
        $pdf->SetX(8);
        $pdf->SetTextColor(30, 64, 175);
        $pdf->SetFont('Helvetica', 'B', 8.5);
        $pdf->Cell($usableWidth, 4.5, $this->text('CONDENSED OPERATIONAL MARKING SCORESHEET'), 0, 1, 'C');
        $pdf->SetX(8);
        $pdf->SetFont('Helvetica', '', 7.4);
        $pdf->SetTextColor(71, 85, 105);
        $lineOne = sprintf(
            '%s | %s | %s',
            ($data['subject']->code ?? '-') . ' - ' . ($data['subject']->name ?? '-'),
            ($data['school']->code ?? '-') . ' - ' . ($data['school']->name ?? '-'),
            'Council: ' . ($data['school']->district?->name ?? '-')
        );
        $pdf->Cell($usableWidth, 4, $this->text($lineOne), 0, 1, 'C');
        $pdf->SetX(8);
        $lineTwo = sprintf(
            'Region: %s | Candidates: %s | Marking Centre: Regional Central Marking Centre | Mode: %s',
            $data['school']->region?->name ?? '-',
            (string) ($data['summary']['candidate_count'] ?? 0),
            ($data['mode'] ?? 'approved') === 'all' ? 'Preview copy' : 'Official print copy'
        );
        $pdf->Cell($usableWidth, 4, $this->text($lineTwo), 0, 1, 'C');
        $pdf->Ln(1);

        $this->renderTableHeader($pdf, $data);
    }

    public function renderContinuationHeader(\FPDF $pdf, array $data): void
    {
        $usableWidth = $pdf->GetPageWidth() - 16;

        $pdf->SetFillColor(248, 250, 252);
        $pdf->Rect(0, 0, $pdf->GetPageWidth(), 28, 'F');
        $pdf->SetFillColor(15, 23, 42);
        $pdf->Rect(0, 0, $pdf->GetPageWidth(), 3, 'F');

        $pdf->SetXY(8, 10);
        $pdf->SetTextColor(30, 64, 175);
        $pdf->SetFont('Helvetica', 'B', 8.5);
        $pdf->Cell($usableWidth, 4.5, $this->text(($data['sheet_variant'] ?? 'blank') === 'entered' ? 'ENTERED MARKS SCORESHEET' : 'OFFICIAL MARKING SCORESHEET'), 0, 1, 'C');
        $pdf->SetX(8);
        $pdf->SetFont('Helvetica', '', 7.3);
        $pdf->SetTextColor(71, 85, 105);
        $pdf->Cell($usableWidth, 4, $this->text(($data['subject']->code ?? '-') . ' - ' . ($data['subject']->name ?? '-') . ' | ' . ($data['school']->code ?? '-') . ' - ' . ($data['school']->name ?? '-')), 0, 1, 'C');
        $pdf->Ln(1);

        $this->renderTableHeader($pdf, $data);
    }

    public function renderFooter(\FPDF $pdf, array $data): void
    {
        $left = 8.0;
        $top = 266.0;
        $blockWidth = 56.0;
        $gap = 7.0;

        $pdf->SetY($top);
        $pdf->SetDrawColor(203, 213, 225);
        $pdf->SetLineWidth(0.3);

        if ($pdf->PageNo() === (int) ($data['last_page_number'] ?? 1)) {
            $blocks = ['Panel Chairperson', 'Panel Secretary', 'Chief Maker'];

            foreach ($blocks as $index => $label) {
                $x = $left + ($index * ($blockWidth + $gap));
                $pdf->Line($x, $top + 7, $x + $blockWidth, $top + 7);
                $pdf->SetXY($x, $top + 8.5);
                $pdf->SetTextColor(51, 65, 85);
                $pdf->SetFont('Helvetica', 'B', 8);
                $pdf->Cell($blockWidth, 4, $this->text($label), 0, 2, 'L');
                $pdf->SetX($x);
                $pdf->SetFont('Helvetica', '', 7);
                $pdf->Cell($blockWidth, 3.8, $this->text('Name / Signature / Date'), 0, 0, 'L');
            }
        }

        $pdf->SetY(286);
        $pdf->SetFont('Helvetica', '', 7);
        $pdf->SetTextColor(71, 85, 105);
        $certification = sprintf(
            'Generated by IRMS for %s. School: %s.',
            $data['exam_year']->year_label,
            $data['school']->name ?? '-'
        );
        $pdf->MultiCell(130, 3.8, $this->text($certification), 0, 'L');

        $pdf->SetXY(142, 286);
        $pdf->SetFont('Helvetica', 'B', 7);
        $pdf->Cell(59, 3.8, $this->text('Page ' . $pdf->PageNo() . '/{nb}'), 0, 2, 'R');
    }

    private function determineLastPageNumber(array $data): int
    {
        $pdf = new class($this, $data) extends \FPDF {
            public function __construct(
                private PsleScoresheetFpdfService $service,
                private array $data
            ) {
                parent::__construct('P', 'mm', 'A4');
                $this->SetMargins(8, 10, 8);
                $this->SetAutoPageBreak(true, 12);
            }

            public function Header(): void
            {
                if ($this->PageNo() === 1) {
                    $this->service->renderHeader($this, $this->data);
                    return;
                }

                $this->service->renderContinuationHeader($this, $this->data);
            }

            public function Footer(): void
            {
                // Dry run used only to identify the final page number.
            }
        };

        $pdf->AddPage();
        $this->renderRows($pdf, $data['rows'] ?? [], $data['sheet_variant'] ?? 'blank', $data['print_layout'] ?? 'formal');

        return $pdf->PageNo();
    }

    private function renderSummaryBlock(\FPDF $pdf, array $data, array $summary): void
    {
        $usableWidth = $pdf->GetPageWidth() - 16;
        $pdf->SetX(8);
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetDrawColor(203, 213, 225);
        $sectionY = $pdf->GetY();
        $pdf->Rect(8, $sectionY, $usableWidth, 10, 'DF');
        $pdf->SetXY(10, $sectionY + 3);
        $pdf->SetTextColor(37, 99, 235);
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->Cell($usableWidth - 2, 4, $this->text(($data['sheet_variant'] ?? 'blank') === 'entered' ? 'DETAILED ENTERED MARKS SCORESHEET SUMMARY' : 'DETAILED SCORESHEET SUMMARY'), 0, 1, 'L');
        $pdf->Ln(3);

        $pdf->SetX(8);
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->SetFillColor(0, 51, 102);
        $pdf->SetTextColor(255, 255, 255);
        $pdf->Cell($usableWidth, 8, $this->text(($data['sheet_variant'] ?? 'blank') === 'entered' ? 'ENTERED MARKS SCORESHEET OPERATIONAL SUMMARY' : 'SCORESHEET OPERATIONAL SUMMARY'), 1, 1, 'L', true);

        $pdf->SetFont('Helvetica', '', 8);
        $leftWidth = 52;
        $rightWidth = $usableWidth - $leftWidth;

        $rows = ($data['sheet_variant'] ?? 'blank') === 'entered'
            ? [
                ['School', ($data['school']->code ?? '-') . ' - ' . ($data['school']->name ?? '-')],
                ['Council / Region', ($data['school']->district?->name ?? '-') . ' / ' . ($data['school']->region?->name ?? '-')],
                ['Subject', ($data['subject']->code ?? '-') . ' - ' . ($data['subject']->name ?? '-')],
                ['Entered Candidates', (string) ($summary['candidate_count'] ?? 0)],
                ['Numeric / INC', ($summary['numeric_mark_count'] ?? 0) . ' / ' . ($summary['inc_count'] ?? 0)],
                ['Sex Distribution', 'M: ' . ($summary['male_count'] ?? 0) . ' / F: ' . ($summary['female_count'] ?? 0)],
                ['Latest Batch', (string) ($summary['latest_batch_code'] ?? '-')],
                ['Document Mode', ($data['mode'] ?? 'approved') === 'all' ? 'Preview copy' : 'Approved / locked verification copy'],
            ]
            : [
                ['School', ($data['school']->code ?? '-') . ' - ' . ($data['school']->name ?? '-')],
                ['Council / Region', ($data['school']->district?->name ?? '-') . ' / ' . ($data['school']->region?->name ?? '-')],
                ['Subject', ($data['subject']->code ?? '-') . ' - ' . ($data['subject']->name ?? '-')],
                ['Registered Candidates', (string) ($summary['candidate_count'] ?? 0)],
                ['Sex Distribution', 'M: ' . ($summary['male_count'] ?? 0) . ' / F: ' . ($summary['female_count'] ?? 0)],
                ['Marking Centre', 'Regional Central Marking Centre'],
                ['Roster Source', (string) ($summary['source'] ?? 'Registered PSLE candidate roster')],
                ['Document Mode', ($data['mode'] ?? 'approved') === 'all' ? 'Preview copy' : 'Official print copy'],
            ];

        foreach ($rows as $index => [$label, $value]) {
            $baseFill = $index % 2 === 0 ? [255, 255, 224] : [248, 250, 252];

            $pdf->SetFillColor($baseFill[0], $baseFill[1], $baseFill[2]);
            $pdf->SetTextColor(8, 39, 109);
            $pdf->SetFont('Helvetica', 'B', 8);
            $pdf->Cell($leftWidth, 7, $this->text($label), 1, 0, 'L', true);

            $pdf->SetFillColor(255, 255, 255);
            $pdf->SetTextColor(30, 41, 59);
            $pdf->SetFont('Helvetica', '', 8);
            $pdf->Cell($rightWidth, 7, $this->text((string) $value), 1, 1, 'L', true);
        }

        $pdf->Ln(2);
    }

    private function renderTableHeader(\FPDF $pdf, array $data): void
    {
        $usableWidth = $pdf->GetPageWidth() - 16;
        $sectionY = $pdf->GetY();
        $pdf->SetFillColor(255, 255, 255);
        $pdf->SetDrawColor(203, 213, 225);
        $pdf->Rect(8, $sectionY, $usableWidth, 9, 'DF');
        $pdf->SetXY(10, $sectionY + 2.5);
        $pdf->SetTextColor(37, 99, 235);
        $pdf->SetFont('Helvetica', 'B', 8);
        $pdf->Cell($usableWidth - 2, 4, $this->text(($data['sheet_variant'] ?? 'blank') === 'entered' ? 'DETAILED ENTERED MARKS SCORESHEET TABLE' : 'DETAILED SCORESHEET TABLE'), 0, 1, 'L');
        $pdf->Ln(2.5);

        $pdf->SetX(8);
        $pdf->SetFillColor(244, 241, 177);
        $pdf->SetDrawColor(100, 116, 139);
        $pdf->SetTextColor(8, 39, 109);
        $pdf->SetFont('Helvetica', 'B', 8.2);
        $columns = $this->columns($data['sheet_variant'] ?? 'blank');
        foreach ($columns as $column) {
            $pdf->Cell($column['width'], 8, $this->text($column['label']), 1, 0, $column['header_align'] ?? 'C', true);
        }
        $pdf->Ln();
    }

    private function renderRows(\FPDF $pdf, array $rows, string $variant = 'blank', string $layout = 'formal'): void
    {
        $pdf->SetFont('Helvetica', '', 8);
        $pdf->SetTextColor(30, 41, 59);
        $lineHeight = ($variant === 'blank' && $layout === 'condensed') ? 5.4 : 6.2;
        $columns = $this->columns($variant);

        foreach ($rows as $row) {
            $pageBreakThreshold = ($variant === 'blank' && $layout === 'condensed') ? 276 : 268;
            if ($pdf->GetY() > $pageBreakThreshold) {
                $pdf->AddPage();
            }

            $fill = ((int) $row['position'] % 2) === 0;
            $baseFill = $fill ? [248, 250, 252] : [255, 255, 255];

            $values = $variant === 'entered'
                ? [
                    ['value' => (string) $row['position'], 'align' => 'C'],
                    ['value' => (string) $row['candidate_number'], 'align' => 'L'],
                    ['value' => (string) $row['prem_no'], 'align' => 'L'],
                    ['value' => (string) $row['sex'], 'align' => 'C'],
                    ['value' => (string) $row['mark_display'], 'align' => 'C'],
                    ['value' => (string) ($row['batch_code'] ?? ''), 'align' => 'L'],
                    ['value' => (string) ($row['status'] ?? ''), 'align' => 'C'],
                    ['value' => (string) $row['remarks'], 'align' => 'L'],
                ]
                : [
                    ['value' => (string) $row['position'], 'align' => 'C'],
                    ['value' => (string) $row['candidate_number'], 'align' => 'L'],
                    ['value' => (string) $row['prem_no'], 'align' => 'L'],
                    ['value' => (string) $row['sex'], 'align' => 'C'],
                    ['value' => (string) ($row['initials'] ?? ''), 'align' => 'C'],
                    ['value' => (string) $row['mark_display'], 'align' => 'C'],
                    ['value' => (string) $row['remarks'], 'align' => 'L'],
                ];

            foreach ($columns as $index => $column) {
                $pdf->SetFillColor($baseFill[0], $baseFill[1], $baseFill[2]);
                
                // Highlight the entered mark to draw verify officer's focus
                if ($variant === 'entered' && $index === 4) {
                    $pdf->SetFont('Helvetica', 'B', 8.5);
                    $pdf->SetTextColor(30, 64, 175); // Premium royal blue
                } else {
                    $pdf->SetFont('Helvetica', '', 8);
                    $pdf->SetTextColor(30, 41, 59);
                }

                $text = $this->truncateToWidth($pdf, $values[$index]['value'], $column['width'] - 2);
                $pdf->Cell($column['width'], $lineHeight, $text, 1, 0, $values[$index]['align'], true);
            }

            $pdf->Ln();
        }
    }

    private function columns(string $variant = 'blank'): array
    {
        if ($variant === 'entered') {
            return [
                ['label' => '#', 'width' => 8, 'header_align' => 'C'],
                ['label' => 'Candidate No', 'width' => 35, 'header_align' => 'L'],
                ['label' => 'PReM No', 'width' => 25, 'header_align' => 'L'],
                ['label' => 'Sex', 'width' => 10, 'header_align' => 'C'],
                ['label' => 'Entered Mark', 'width' => 24, 'header_align' => 'C'],
                ['label' => 'Batch Code', 'width' => 32, 'header_align' => 'L'],
                ['label' => 'Status', 'width' => 20, 'header_align' => 'C'],
                ['label' => 'Remarks', 'width' => 40, 'header_align' => 'L'],
            ];
        }

        return [
            ['label' => '#', 'width' => 8, 'header_align' => 'C'],
            ['label' => 'Candidate No', 'width' => 35, 'header_align' => 'L'],
            ['label' => 'PReM No', 'width' => 25, 'header_align' => 'L'],
            ['label' => 'Sex', 'width' => 10, 'header_align' => 'C'],
            ['label' => 'Signature', 'width' => 49, 'header_align' => 'C'],
            ['label' => 'Marks', 'width' => 18, 'header_align' => 'C'],
            ['label' => 'Remarks', 'width' => 49, 'header_align' => 'C'],
        ];
    }

    private function truncateToWidth(\FPDF $pdf, string $value, float $width): string
    {
        $value = $this->text($value);
        if ($value === '') {
            return '';
        }

        if ($pdf->GetStringWidth($value) <= $width) {
            return $value;
        }

        $trimmed = $value;
        while (strlen($trimmed) > 1 && $pdf->GetStringWidth($trimmed . '...') > $width) {
            $trimmed = substr($trimmed, 0, -1);
        }

        return $trimmed . '...';
    }

    private function getSchoolsWithMarks(string $examYearLabel, int $districtId, string $mode = 'approved'): Collection
    {
        $examYear = $this->resolveExamYear($examYearLabel);
        $psle = $this->resolvePsleExamType();

        return CandidateExamRegistration::query()
            ->join('candidates', 'candidates.id', '=', 'candidate_exam_registrations.candidate_id')
            ->join('schools', 'schools.id', '=', 'candidates.school_id')
            ->where('schools.district_id', $districtId)
            ->where('candidate_exam_registrations.exam_type_id', $psle->id)
            ->where(function ($query) use ($examYear) {
                $query->where('candidate_exam_registrations.exam_year_id', $examYear->id)
                    ->orWhere('candidate_exam_registrations.year', (int) $examYear->year_label);
            })
            ->select('schools.id', 'schools.code', 'schools.name')
            ->groupBy('schools.id', 'schools.code', 'schools.name')
            ->orderBy('schools.name')
            ->get();
    }

    private function getSchoolsWithEnteredMarks(string $examYearLabel, int $districtId, string $mode = 'approved'): Collection
    {
        return RawMark::query()
            ->join('mark_import_batches', 'mark_import_batches.id', '=', 'raw_marks.mark_import_batch_id')
            ->join('schools', 'schools.id', '=', 'mark_import_batches.school_id')
            ->where('mark_import_batches.exam_type_id', $this->resolvePsleExamType()->id)
            ->where('mark_import_batches.exam_year', (string) $this->resolveExamYear($examYearLabel)->year_label)
            ->where('mark_import_batches.district_id', $districtId)
            ->whereIn('mark_import_batches.status', $this->allowedStatuses($mode))
            ->where('raw_marks.has_errors', false)
            ->select('schools.id', 'schools.code', 'schools.name')
            ->groupBy('schools.id', 'schools.code', 'schools.name')
            ->orderBy('schools.name')
            ->get();
    }

    private function getRegionSchoolsWithEnteredMarks(string $examYearLabel, int $regionId, string $mode = 'approved'): Collection
    {
        $examYear = $this->resolveExamYear($examYearLabel);

        return RawMark::query()
            ->join('schools', 'schools.id', '=', 'raw_marks.school_id')
            ->where('raw_marks.exam_year_id', $examYear->id)
            ->where('schools.region_id', $regionId)
            ->where('raw_marks.has_errors', false)
            ->where(function ($query) {
                $query->whereNotNull('raw_marks.paper_1_marks')
                    ->orWhereNotNull('raw_marks.paper_2_marks')
                    ->orWhereNotNull('raw_marks.paper_3_marks')
                    ->orWhereNotNull('raw_marks.practical_marks')
                    ->orWhereNotNull('raw_marks.project_marks');
            })
            ->select('schools.id', 'schools.code', 'schools.name', 'schools.district_id')
            ->groupBy('schools.id', 'schools.code', 'schools.name', 'schools.district_id')
            ->orderBy('schools.name')
            ->get();
    }

    private function allowedStatuses(string $mode): array
    {
        if ($mode === 'all') {
            return [
                MarkImportBatch::STATUS_DRAFT,
                MarkImportBatch::STATUS_VALIDATED,
                MarkImportBatch::STATUS_SUBMITTED,
                MarkImportBatch::STATUS_APPROVED,
                MarkImportBatch::STATUS_LOCKED,
                MarkImportBatch::STATUS_PROCESSED,
            ];
        }

        if ($mode === 'locked') {
            return [
                MarkImportBatch::STATUS_LOCKED,
                MarkImportBatch::STATUS_PROCESSED,
            ];
        }

        return [
            MarkImportBatch::STATUS_APPROVED,
            MarkImportBatch::STATUS_LOCKED,
            MarkImportBatch::STATUS_PROCESSED,
        ];
    }

    private function registeredCandidateIds(int $schoolId, ExamYear $examYear, ExamType $psle): Collection
    {
        return CandidateExamRegistration::query()
            ->where('exam_type_id', $psle->id)
            ->where(function ($query) use ($examYear) {
                $query->where('exam_year_id', $examYear->id)
                    ->orWhere('year', (int) $examYear->year_label);
            })
            ->whereHas('candidate', fn ($query) => $query->where('school_id', $schoolId))
            ->pluck('candidate_id')
            ->filter()
            ->unique()
            ->values();
    }

    private function resolveExamYear(string $examYearLabel): ExamYear
    {
        return ExamYear::query()->where('year_label', (string) $examYearLabel)->firstOrFail();
    }

    private function resolvePsleExamType(): ExamType
    {
        return ExamType::query()->where('code', 'PSLE')->firstOrFail();
    }

    private function ensureTempDirectory(): void
    {
        $directory = base_path(self::TEMP_DIR);
        if (!is_dir($directory)) {
            mkdir($directory, 0755, true);
        }
    }

    private function fileLabel(?string $value, string $fallback = 'file'): string
    {
        $value = strtolower($this->text($value ?: ''));
        $value = preg_replace('/[^a-z0-9]+/', '_', $value ?? '') ?? '';
        $value = trim($value, '_');

        return $value !== '' ? $value : $fallback;
    }

    private function displayFileLabel(?string $value, string $fallback = 'FILE'): string
    {
        $value = strtoupper($this->text($value ?: ''));
        $value = preg_replace('/[\\\\\\/:"*?<>|]+/', ' ', $value ?? '') ?? '';
        $value = preg_replace('/\s+/', ' ', $value ?? '') ?? '';
        $value = trim($value);

        return $value !== '' ? $value : $fallback;
    }

    private function formatMark($value): string
    {
        if ($value === null || $value === '') {
            return '-';
        }

        $float = (float) $value;
        if (abs($float - round($float)) < 0.00001) {
            return (string) (int) round($float);
        }

        return number_format($float, 2, '.', '');
    }

    private function logExport(string $action, array $data, ?int $actorUserId, array $context = []): void
    {
        SystemEventLog::record(
            SystemEventLog::CAT_EXPORT,
            $action,
            SystemEventLog::STATUS_SUCCESS,
            'PSLE scoresheet export completed.',
            array_merge([
                'exam_type' => 'PSLE',
                'exam_year' => $data['exam_year']->year_label ?? null,
                'school_code' => $data['school']->code ?? null,
                'school_name' => $data['school']->name ?? null,
                'district_name' => $data['school']->district?->name ?? null,
                'region_name' => $data['school']->region?->name ?? null,
                'subject_code' => $data['subject']->code ?? null,
                'subject_name' => $data['subject']->name ?? null,
                'candidate_count' => $data['summary']['candidate_count'] ?? null,
                'sheet_variant' => $data['sheet_variant'] ?? null,
                'print_layout' => $data['print_layout'] ?? null,
                'generated_at' => isset($data['generated_at']) ? $data['generated_at']->toDateTimeString() : null,
                'generated_by' => $data['generated_by'] ?? null,
            ], $context),
            actorUserId: $actorUserId
        );
    }
}
