<?php

namespace App\Http\Controllers\MarkEntry\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class ReopenRequestController extends Controller
{
    public function index()
    {
        return view('mark-entry.admin.reopen-requests.index');
    }
}
