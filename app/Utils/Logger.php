<?php
// /cryptotrade/app/Utils/Logger.php
namespace App\Utils;
use App\Core\Session; // To get user ID

class Logger {
    // Define the path relative to this file's location
    private static $logFilePath = __DIR__ . '/../../storage/logs/audit.log';

    /**
     * Logs an action to the audit log file.
     *
     * @param string $action Short description of the action (e.g., 'login_success', 'buy_crypto').
     * @param array $details Optional associative array of relevant details (e.g., ['currency_id' => 5, 'quantity' => 0.1]).
     */
    public static function logAction(string $action, array $details = []) {
        // Ensure the log directory exists
        $logDir = dirname(self::$logFilePath);
        if (!is_dir($logDir)) {
            // Attempt to create the directory recursively
            // Suppress errors in case of permission issues, but log them if possible later
            @mkdir($logDir, 0775, true);
        }

        // Gather context information
        $timestamp = date('Y-m-d H:i:s');
        $userId = Session::get('user_id') ?? 'Guest'; // Default to 'Guest' if no session
        $ipAddress = $_SERVER['REMOTE_ADDR'] ?? 'Unknown IP';
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Unknown User Agent';

        // Format details into a readable string (JSON is good for structure)
        $detailsString = !empty($details) ? json_encode($details) : '-';

        // Construct the log message
        $logMessage = sprintf(
            "[%s] UserID: %s | IP: %s | Action: %s | Details: %s | UserAgent: %s%s",
            $timestamp,
            $userId,
            $ipAddress,
            $action,
            $detailsString,
            $userAgent,
            PHP_EOL // Ensures a new line for each entry
        );

        // Append the message to the log file with exclusive lock
        // Use error suppression (@) for file_put_contents in case of permission errors
        // In a production app, more robust error handling/permission checks would be ideal
        $writeSuccess = @file_put_contents(self::$logFilePath, $logMessage, FILE_APPEND | LOCK_EX);

        // Optional: Log an error if writing failed (e.g., to PHP error log)
        if ($writeSuccess === false) {
            error_log("Logger Error: Failed to write to audit log file: " . self::$logFilePath . " - Check permissions.");
        }
    }
}
?> 