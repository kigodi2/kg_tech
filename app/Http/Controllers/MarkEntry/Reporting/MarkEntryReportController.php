<?php

namespace App\Http\Controllers\MarkEntry\Reporting;

use App\Http\Controllers\Controller;
use App\Models\MarkImportBatch;
use Illuminate\Http\Request;

class MarkEntryReportController extends Controller {
    
    public function scoresheet(MarkImportBatch $batch) {
        return response()->json(['batch_id' => $batch->id, 'message' => 'Scoresheet']);
    }
}
