<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\PsleMarkEntryController;

try {
    // 1. Find user Dickson Lemson
    $user = \App\Models\User::where('name', 'like', '%DICKSON%LEMSON%')->first();
    if (!$user) {
        $user = \App\Models\User::where('portal_role', 'mark_officer')->first();
    }
    if (!$user) {
        throw new Exception("Dickson Lemson or MEO user not found");
    }
    Auth::login($user);
    echo "Logged in as: " . Auth::user()->name . " (ID: " . Auth::user()->id . ")<br>";

    // 2. Find candidate Martha Msafiri Witike
    $candidate = \App\Models\Candidate::where('school_id', 7849)
        ->where('full_name', 'like', '%MARTHA%MSAFIRI%')
        ->first();
    if (!$candidate) {
        $candidate = \App\Models\Candidate::where('school_id', 7849)->first();
    }
    if (!$candidate) {
        throw new Exception("Candidate not found under school 7849");
    }
    echo "Target Candidate: " . $candidate->full_name . " (ID: " . $candidate->id . ", Code: " . $candidate->candidate_id . ")<br>";

    // 3. Prepare payload
    $payload = [
        'candidate_id' => $candidate->id,
        'school_id' => 7849,
        'subject_id' => 130,
        'exam_year_id' => 1,
        'score' => 'ABS'
    ];

    $request = Request::create('/api/mark-entry/psle/marks/save', 'POST', $payload);
    $request->setUserResolver(function () use ($user) {
        return $user;
    });

    echo "Invoking saveMark...<br>";
    $controller = app(PsleMarkEntryController::class);
    $response = $controller->saveMark($request);
    
    echo "Response status: " . $response->status() . "<br>";
    echo "Response content:<br>";
    echo "<pre>";
    print_r(json_decode($response->content(), true));
    echo "</pre>";

} catch (\Throwable $e) {
    echo "<h3>Failed!</h3>";
    echo "<b>Exception:</b> " . get_class($e) . "<br>";
    echo "<b>Message:</b> " . $e->getMessage() . "<br>";
    echo "<b>File:</b> " . $e->getFile() . " on line " . $e->getLine() . "<br>";
    echo "<pre><b>Trace:</b><br>" . $e->getTraceAsString() . "</pre>";
}

