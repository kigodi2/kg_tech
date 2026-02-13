<?php

namespace App\Http\Controllers\MarkEntry\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class MarkEntryAdminController extends Controller {
    
    public function configuration() {
        return view('mark-entry.admin.configuration');
    }
}
