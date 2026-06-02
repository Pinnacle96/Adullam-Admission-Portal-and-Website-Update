<?php
/**
 * Centralized Logging Utility
 * Logs errors, warnings, and custom messages to files in the logs directory
 */

// Define log directory
define('LOG_DIR', __DIR__ . '/../logs');

// Create log directory if it doesn't exist
if (!is_dir(LOG_DIR)) {
    mkdir(LOG_DIR, 0777, true);
}

/**
 * Log a message to a specified log file
 * @param string $message The message to log
 * @param string $level The log level (INFO, WARNING, ERROR, DEBUG, etc.)
 * @param string $logFile The name of the log file (without extension, e.g., 'app' for app.log)
 */
function log_message($message, $level = 'INFO', $logFile = 'app') {
    // Ensure the log directory exists
    if (!is_dir(LOG_DIR)) {
        mkdir(LOG_DIR, 0777, true);
    }

    $timestamp = date('Y-m-d H:i:s T');
    $logEntry = "[{$timestamp}] [{$level}] {$message}" . PHP_EOL;
    $logFilePath = LOG_DIR . '/' . $logFile . '.log';
    
    // Write to log file
    file_put_contents($logFilePath, $logEntry, FILE_APPEND | LOCK_EX);

    // Also log to PHP's error log for server-level visibility
    error_log("[{$level}] {$message}");
}

/**
 * Custom error handler
 */
function custom_error_handler($errno, $errstr, $errfile, $errline) {
    $message = "Error [{$errno}]: {$errstr} in {$errfile} on line {$errline}";
    log_message($message, 'ERROR', 'errors');
    return false; // Let PHP handle the error as well
}

/**
 * Custom exception handler
 */
function custom_exception_handler($exception) {
    $message = "Uncaught Exception: " . $exception->getMessage() . " in " . $exception->getFile() . " on line " . $exception->getLine();
    $message .= PHP_EOL . "Stack trace: " . $exception->getTraceAsString();
    log_message($message, 'CRITICAL', 'exceptions');
}

// Set error and exception handlers
set_error_handler('custom_error_handler');
set_exception_handler('custom_exception_handler');

// Log PHP shutdown errors (fatal errors)
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== null && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
        $message = "Fatal Error: {$error['message']} in {$error['file']} on line {$error['line']}";
        log_message($message, 'FATAL', 'errors');
    }
});
