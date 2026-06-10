<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

$logFile = __DIR__ . '/../storage/logs/laravel.log';
if (file_exists($logFile)) {
    echo "<h3>Laravel Log tail:</h3>";
    $content = file_get_contents($logFile);
    $tail = substr($content, -15000); // last 15kb
    echo "<pre>" . htmlspecialchars($tail) . "</pre>";
} else {
    echo "Log file not found at $logFile";
}
