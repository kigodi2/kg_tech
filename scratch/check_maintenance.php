<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Helpers\SystemSettingsHelper;
use Illuminate\Support\Facades\Route;
use App\Models\User;

echo "Maintenance Mode Setting: " . var_export(SystemSettingsHelper::getSetting('maintenance_mode', false), true) . "\n";

// Find user officer@test.com (created by verification script) or similar
$officer = User::where('email', 'officer@test.com')->first();
if (!$officer) {
    // If not found, let's look for any user or create a temporary one
    $officer = User::first();
}
echo "Officer Email: " . ($officer ? $officer->email : 'NONE') . "\n";

// Test 1: Guest to /results/2099/psle
auth()->logout();
$req1 = \Illuminate\Http\Request::create('/results/2099/psle', 'GET');
$res1 = app(\Illuminate\Contracts\Http\Kernel::class)->handle($req1);
echo "Guest -> /results/2099/psle: HTTP " . $res1->getStatusCode() . "\n";

// Test 2: Officer to /evaluations/psle
if ($officer) {
    auth()->login($officer);
}
$req2 = \Illuminate\Http\Request::create('/evaluations/psle', 'GET');
$res2 = app(\Illuminate\Contracts\Http\Kernel::class)->handle($req2);
echo "Officer -> /evaluations/psle: HTTP " . $res2->getStatusCode() . "\n";

// Test 3: Guest to /evaluations/psle
auth()->logout();
$req3 = \Illuminate\Http\Request::create('/evaluations/psle', 'GET');
$res3 = app(\Illuminate\Contracts\Http\Kernel::class)->handle($req3);
echo "Guest -> /evaluations/psle: HTTP " . $res3->getStatusCode() . "\n";
