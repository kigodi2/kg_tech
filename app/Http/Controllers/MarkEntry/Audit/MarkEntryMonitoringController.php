<?php

namespace App\Http\Controllers\MarkEntry\Audit;

use App\Http\Controllers\Controller;
use App\Models\MarkImportBatch;
use Illuminate\Http\Request;

class MarkEntryMonitoringController extends Controller {
    
    public function lifecycleDashboard() {
        $batches = MarkImportBatch::all();
        return view('mark-entry.monitoring.dashboard', ['batches' => $batches]);
    }

    public function auditTrail() {
        return view('mark-entry.monitoring.audit-trail');
    }
}
