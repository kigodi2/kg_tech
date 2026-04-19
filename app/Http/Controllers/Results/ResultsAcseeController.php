<?php

namespace App\Http\Controllers\Results;

use App\Http\Controllers\Controller;
use App\Models\CandidateResult;
use App\Models\GradingProfile;
use Illuminate\Support\Facades\Gate;

class ResultsAcseeController extends Controller
{
    public function index()
    {
        $this->authorize('viewResults', CandidateResult::class);

        return view('results.acsee.index', [
            'canComputeValidate' => Gate::allows('publishLock', CandidateResult::class),
            'canAdminUnlock' => Gate::allows('adminUnlock', CandidateResult::class),
            'canManageGradingConfig' => Gate::allows('create', GradingProfile::class),
            'canPreviewImpact' => Gate::allows('publishLock', CandidateResult::class),
        ]);
    }
}
