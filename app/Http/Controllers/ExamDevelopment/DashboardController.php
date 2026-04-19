<?php

namespace App\Http\Controllers\ExamDevelopment;

use App\Http\Controllers\Controller;
use App\Models\ExamDevelopment\ExamProject;
use App\Models\ExamDevelopment\Question;
use App\Models\ExamDevelopment\SubjectFormat;

class DashboardController extends Controller
{
    public function index()
    {
        return view('exam-development.dashboard', [
            'formatCount' => SubjectFormat::query()->count(),
            'projectCount' => ExamProject::query()->count(),
            'questionCount' => Question::query()->count(),
            'approvedProjectCount' => ExamProject::query()->where('status', ExamProject::STATUS_APPROVED)->count(),
            'recentProjects' => ExamProject::query()->with(['subject', 'examType'])->latest()->limit(6)->get(),
        ]);
    }
}
