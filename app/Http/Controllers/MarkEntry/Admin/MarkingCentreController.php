<?php

namespace App\Http\Controllers\MarkEntry\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\MarkingCentre;

class MarkingCentreController extends Controller
{
    public function index()
    {
        $this->authorize('viewAny', MarkingCentre::class);
        return view('mark-entry.admin.marking-centres.index');
    }

    public function create()
    {
        // ...
    }

    public function store(Request $request)
    {
        // ...
    }
}
