<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Candidate;
use App\Models\School;
use App\Models\ExamType;
use App\Models\ExamYear;
use App\Models\RawMark;
use App\Models\MarkImportBatch;

class AdminController extends Controller
{
    public function dashboard()
    {
        $stats = [
            'users_count' => User::count(),
            'candidates_count' => Candidate::count(),
            'schools_count' => School::count(),
            'exam_types_count' => ExamType::count(),
            'exam_years_count' => ExamYear::count(),
            'total_marks_count' => RawMark::count(),
            'import_batches_count' => MarkImportBatch::count(),
        ];

        return view('admin.dashboard', compact('stats'));
    }
}
