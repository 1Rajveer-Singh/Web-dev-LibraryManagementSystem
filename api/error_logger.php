<?php
// config/error_logger.php

function errorLog($message, $logFile = 'error.log') {
    // Define log directory (adjust path as needed)
    $logDirectory = dirname(__FILE__) . '/../logs/';

    // Create logs directory if it doesn't exist
    if (!is_dir($logDirectory)) {
        mkdir($logDirectory, 0755, true);
    }

    // Full path to log file
    $logPath = $logDirectory . $logFile;

    // Prepare log entry
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[{$timestamp}] " . $message . PHP_EOL;

    // Attempt to write to log file
    try {
        // Append to log file, create if not exists
        if (file_put_contents($logPath, $logEntry, FILE_APPEND) === false) {
            // Fallback error handling if file writing fails
            error_log("Failed to write to log file: {$logPath}", 0);
        }
    } catch (Exception $e) {
        // System error logging as a last resort
        error_log("Error logging failed: " . $e->getMessage(), 0);
    }
}

// Optional: Log rotation function to prevent log files from becoming too large
function rotateLogFiles($logFile, $maxSize = 5242880) { // Default 5MB
    $logPath = dirname(__FILE__) . '/../logs/' . $logFile;
    
    if (file_exists($logPath) && filesize($logPath) > $maxSize) {
        $archivePath = $logPath . '.' . date('Y-m-d-H-i-s') . '.bak';
        rename($logPath, $archivePath);
    }
}
?>