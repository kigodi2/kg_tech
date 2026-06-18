<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\ExamYear;
use App\Models\ExamType;
use App\Models\Region;
use App\Models\School;

$activeYear = ExamYear::where('is_active', true)->first();
$psleType = ExamType::where('code', 'PSLE')->first();
$tasidoRegions = Region::whereIn(DB::raw('upper(name)'), ['TABORA', 'SINGIDA', 'IRINGA', 'DODOMA'])->get();
$tasidoRegionIds = $tasidoRegions->pluck('id')->toArray();
$schoolIds = School::whereIn('region_id', $tasidoRegionIds)->where('education_level', 'PRIMARY')->pluck('id')->toArray();

// Fetch orphans
$orphanMarks = DB::table('raw_marks as rm')
    ->leftJoin('candidate_exam_registrations as cer', function ($join) use ($psleType, $activeYear) {
        $join->on('cer.candidate_id', '=', 'rm.candidate_id')
            ->where('cer.exam_type_id', '=', $psleType->id)
            ->where('cer.year', '=', $activeYear->year_label);
    })
    ->whereIn('rm.school_id', $schoolIds)
    ->where('rm.exam_year_id', $activeYear->id)
    ->whereNull('cer.candidate_id')
    ->select('rm.*')
    ->get();

echo "Starting healing process for " . $orphanMarks->count() . " orphan marks...\n";

DB::transaction(function() use ($orphanMarks, $activeYear) {
    $linkedCount = 0;
    $deletedDuplicateCount = 0;
    $typoFixedCount = 0;
    $deletedSpuriousCount = 0;

    foreach ($orphanMarks as $rm) {
        $idx = $rm->candidate_index_number;
        
        // Case 1: candidate_id is null, but index number matches a candidate exactly
        if (is_null($rm->candidate_id)) {
            $candidate = DB::table('candidates')->where('candidate_id', $idx)->first();
            if ($candidate) {
                // Check if there is already a mark record for this candidate and subject
                $exists = DB::table('raw_marks')
                    ->where('candidate_id', $candidate->id)
                    ->where('subject_id', $rm->subject_id)
                    ->where('exam_year_id', $activeYear->id)
                    ->where('id', '!=', $rm->id)
                    ->first();
                    
                if ($exists) {
                    DB::table('raw_marks')->where('id', $rm->id)->delete();
                    $deletedDuplicateCount++;
                } else {
                    DB::table('raw_marks')->where('id', $rm->id)->update(['candidate_id' => $candidate->id]);
                    $linkedCount++;
                }
                continue;
            }
        }
        
        // Case 2: Index has typo starting with PS94 instead of PS04
        if (strpos($idx, 'PS94') === 0) {
            $correctedIdx = 'PS04' . substr($idx, 4);
            $candidate = DB::table('candidates')->where('candidate_id', $correctedIdx)->first();
            if ($candidate) {
                $exists = DB::table('raw_marks')
                    ->where('candidate_id', $candidate->id)
                    ->where('subject_id', $rm->subject_id)
                    ->where('exam_year_id', $activeYear->id)
                    ->where('id', '!=', $rm->id)
                    ->first();
                    
                if ($exists) {
                    DB::table('raw_marks')->where('id', $rm->id)->delete();
                    $deletedDuplicateCount++;
                } else {
                    DB::table('raw_marks')->where('id', $rm->id)->update([
                        'candidate_index_number' => $correctedIdx,
                        'candidate_id' => $candidate->id
                    ]);
                    $typoFixedCount++;
                }
                continue;
            }
        }

        // Case 3: Index has school code digit mismatch, e.g. PS0404116-002 instead of PS0404116-0002
        if (!is_null($idx)) {
            $parts = explode('-', $idx);
            $suffix = end($parts);
            if ($suffix && strlen($suffix) < 4 && is_numeric($suffix)) {
                // Pad with zeros to 4 digits
                $paddedSuffix = str_pad($suffix, 4, '0', STR_PAD_LEFT);
                // Try to construct corrected index
                array_pop($parts);
                $parts[] = $paddedSuffix;
                $correctedIdx = implode('-', $parts);
                
                $candidate = DB::table('candidates')->where('candidate_id', $correctedIdx)->first();
                if ($candidate) {
                    $exists = DB::table('raw_marks')
                        ->where('candidate_id', $candidate->id)
                        ->where('subject_id', $rm->subject_id)
                        ->where('exam_year_id', $activeYear->id)
                        ->where('id', '!=', $rm->id)
                        ->first();
                        
                    if ($exists) {
                        DB::table('raw_marks')->where('id', $rm->id)->delete();
                        $deletedDuplicateCount++;
                    } else {
                        DB::table('raw_marks')->where('id', $rm->id)->update([
                            'candidate_index_number' => $correctedIdx,
                            'candidate_id' => $candidate->id
                        ]);
                        $typoFixedCount++;
                    }
                    continue;
                }
            }
        }

        // Case 4: No matching candidate at all (spurious/extraneous mark entry)
        DB::table('raw_marks')->where('id', $rm->id)->delete();
        $deletedSpuriousCount++;
    }

    echo "Healing results:\n";
    echo "  - Linked to existing candidates: {$linkedCount}\n";
    echo "  - Deleted duplicate mark records: {$deletedDuplicateCount}\n";
    echo "  - Corrected typos and linked: {$typoFixedCount}\n";
    echo "  - Deleted spurious/unregistered candidate marks: {$deletedSpuriousCount}\n";
});
