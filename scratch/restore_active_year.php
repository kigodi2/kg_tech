<?php
require __DIR__ . '/../vendor/autoload.php';
$app = require_once __DIR__ . '/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Helpers\SystemSettingsHelper;

DB::table('exam_years')->where('id', 1)->update(['is_active' => true]);
SystemSettingsHelper::clearCache();

echo "Exam Year 2026 (ID: 1) has been set to ACTIVE.\n";
