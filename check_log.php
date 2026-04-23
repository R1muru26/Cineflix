<?php
$logFile = 'C:\\xampp\\php\\logs\\php_error_log';
if (!file_exists($logFile)) {
    $logFile = 'C:\\xampp\\apache\\logs\\error.log';
}
if (file_exists($logFile)) {
    $lines = file($logFile);
    $last_lines = array_slice($lines, -20);
    echo implode("", $last_lines);
} else {
    echo "No log found.";
}
