<?php

namespace App\Http\Controllers\MarkEntry\Entry;

use App\Http\Controllers\Controller;
use App\Models\Region;
use App\Models\District;
use App\Models\School;
use App\Models\Subject;
use App\Models\ExamYear;
use Illuminate\Http\Request;

class MarkEntryApiController extends Controller {
    
    public function regions() {
        $regions = Region::active()->get(['id', 'code', 'name']);
        return response()->json(['data' => $regions]);
    }

    public function districts(Request $request) {
        $validated = $request->validate(['region_id' => 'required|integer|exists:regions,id']);
        $districts = District::where('region_id', $validated['region_id'])->get(['id', 'code', 'name']);
        return response()->json(['data' => $districts]);
    }

    public function schools(Request $request) {
        $validated = $request->validate(['district_id' => 'required|integer|exists:districts,id']);
        $schools = School::where('district_id', $validated['district_id'])->get(['id', 'code', 'name']);
        return response()->json(['data' => $schools]);
    }

    public function subjects(Request $request) {
        $subjects = Subject::active()->get(['id', 'code', 'name']);
        return response()->json(['data' => $subjects]);
    }

    public function examYears() {
        $years = ExamYear::orderByDesc('year_label')->get(['id', 'year_label']);
        return response()->json(['data' => $years]);
    }
}
