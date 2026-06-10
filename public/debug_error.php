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

echo "Memory before: " . memory_get_usage(true) . "<br>";

try {
    $user = \App\Models\User::find(2107);
    if (!$user) {
        throw new Exception("User 2107 not found");
    }
    Auth::login($user);
    echo "Logged in as: " . Auth::user()->name . "<br>";
    echo "Memory after login: " . memory_get_usage(true) . "<br>";

    $payload = [
        'candidate_id' => 49567,
        'school_id' => 7829,
        'subject_id' => 132,
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
