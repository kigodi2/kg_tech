<?php

namespace App\Http\Controllers\MarkEntry\Entry;

use App\Http\Controllers\Controller;
use App\Models\MarkImportBatch;
use Illuminate\Http\Request;

class MarkEntryUploadController extends Controller {
    
    public function index() {
        return view('mark-entry.index');
    }

    public function downloadTemplate(Request $request) {
        return response()->json(['message' => 'Template download']);
    }

    public function upload(Request $request) {
        return response()->json(['success' => true]);
    }

    public function batchDetails($batchId) {
        $batch = MarkImportBatch::findOrFail($batchId);
        return response()->json([
            'batch_id' => $batch->id,
            'state' => $batch->lifecycle_state,
            'status' => $batch->status,
        ]);
    }
}
