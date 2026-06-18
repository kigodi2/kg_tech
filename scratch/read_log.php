<?php
$logFile = __DIR__ . '/../storage/logs/laravel.log';
if (!file_exists($logFile)) {
    echo "Log file does not exist.\n";
    exit(0);
}

$lines = file($logFile);
$count = count($lines);
$start = max(0, $count - 100);

echo "Last 100 lines of laravel.log:\n";
for ($i = $start; $i < $count; $i++) {
    echo $lines[$i];
}
