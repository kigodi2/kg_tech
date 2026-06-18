<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use App\Helpers\SystemSettingsHelper;
use Illuminate\Support\Facades\Route;

echo "Maintenance Mode Setting: " . var_export(SystemSettingsHelper::getSetting('maintenance_mode', false), true) . "\n";

$request = \Illuminate\Http\Request::create('/results/2099/psle', 'GET');
try {
    $route = Route::getRoutes()->match($request);
    echo "Matched Route Name: " . $route->getName() . "\n";
    echo "Matched Controller: " . get_class($route->getController()) . "\n";
    echo "Matched Middleware: " . implode(', ', $route->middleware()) . "\n";
} catch (\Exception $e) {
    echo "Route match error: " . $e->getMessage() . "\n";
}

// Simulate the request as guest
auth()->logout();
$response = app(\Illuminate\Contracts\Http\Kernel::class)->handle($request);
echo "Response status code for guest: " . $response->getStatusCode() . "\n";
if ($response->getStatusCode() === 503) {
    echo "Response Content (first 200 chars): " . substr($response->getContent(), 0, 200) . "\n";
}
