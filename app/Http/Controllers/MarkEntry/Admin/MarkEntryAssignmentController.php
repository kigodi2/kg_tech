<?php

namespace App\Http\Controllers\MarkEntry\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MarkEntryAssignment;

class MarkEntryAssignmentController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', MarkEntryAssignment::class);
        return view('mark-entry.admin.assignments.index');
    }
}
