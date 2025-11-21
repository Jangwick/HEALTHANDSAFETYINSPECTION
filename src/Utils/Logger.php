<?php

declare(strict_types=1);

namespace HealthSafety\Utils;

/**
 * Logger Utility
 * Health & Safety Inspections System
 */
class Logger
{
    private const LOG_LEVELS = [
        'DEBUG' => 0,
        'INFO' => 1,
        'WARNING' => 2,
        'ERROR' => 3,
        'CRITICAL' => 4,
    ];
    
    private static string $logPath = LOG_PATH;
    private static string $logLevel = 'INFO';
    
    /**
     * Set log level
     */
    public static function setLogLevel(string $level): void
    {
        $level = strtoupper($level);
        if (isset(self::LOG_LEVELS[$level])) {
            self::$logLevel = $level;
        }
    }
    
    /**
     * Log debug message
     */
    public static function debug(string $message, array $context = []): void
    {
        self::log('DEBUG', $message, $context);
    }
    
    /**
     * Log info message
     */
    public static function info(string $message, array $context = []): void
    {
        self::log('INFO', $message, $context);
    }
    
    /**
     * Log warning message
     */
    public static function warning(string $message, array $context = []): void
    {
        self::log('WARNING', $message, $context);
    }
    
    /**
     * Log error message
     */
    public static function error(string $message, array $context = []): void
    {
        self::log('ERROR', $message, $context);
    }
    
    /**
     * Log critical message
     */
    public static function critical(string $message, array $context = []): void
    {
        self::log('CRITICAL', $message, $context);
    }
    
    /**
     * Log authentication events
     */
    public static function auth(string $action, string $username, bool $success, array $context = []): void
    {
        $message = sprintf(
            'Auth %s: %s - %s',
            $action,
            $username,
            $success ? 'SUCCESS' : 'FAILED'
        );
        
        self::log('INFO', $message, array_merge($context, [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        ]), 'auth.log');
    }
    
    /**
     * Log API requests
     */
    public static function api(string $method, string $endpoint, int $statusCode, array $context = []): void
    {
        $message = sprintf(
            'API %s %s - Status: %d',
            $method,
            $endpoint,
            $statusCode
        );
        
        self::log('INFO', $message, array_merge($context, [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'duration' => $context['duration'] ?? 0,
        ]), 'api.log');
    }
    
    /**
     * Log database queries (for debugging)
     */
    public static function query(string $query, array $params = [], float $duration = 0): void
    {
        if (APP_ENV !== 'production') {
            $message = sprintf('Query executed in %.4f seconds', $duration);
            self::log('DEBUG', $message, [
                'query' => $query,
                'params' => $params,
            ], 'database.log');
        }
    }
    
    /**
     * Log security events
     */
    public static function security(string $event, string $severity, array $context = []): void
    {
        self::log(strtoupper($severity), "Security Event: $event", array_merge($context, [
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown',
        ]), 'security.log');
    }
    
    /**
     * Main log method
     */
    private static function log(string $level, string $message, array $context = [], string $filename = 'app.log'): void
    {
        // Check if should log based on level
        if (self::LOG_LEVELS[$level] < self::LOG_LEVELS[self::$logLevel]) {
            return;
        }
        
        // Format log message
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context) : '';
        $logMessage = sprintf(
            "[%s] [%s] %s%s\n",
            $timestamp,
            $level,
            $message,
            $contextStr
        );
        
        // Write to file
        $logFile = self::$logPath . '/' . $filename;
        
        // Create log directory if it doesn't exist
        if (!file_exists(self::$logPath)) {
            mkdir(self::$logPath, 0755, true);
        }
        
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
        
        // Also log errors to PHP error log
        if (in_array($level, ['ERROR', 'CRITICAL'])) {
            error_log($logMessage);
        }
    }
    
    /**
     * Read recent log entries
     */
    public static function readLogs(string $filename = 'app.log', int $lines = 100): array
    {
        $logFile = self::$logPath . '/' . $filename;
        
        if (!file_exists($logFile)) {
            return [];
        }
        
        $file = new \SplFileObject($logFile);
        $file->seek(PHP_INT_MAX);
        $totalLines = $file->key();
        
        $start = max(0, $totalLines - $lines);
        $file->seek($start);
        
        $logs = [];
        while (!$file->eof()) {
            $line = trim($file->fgets());
            if (!empty($line)) {
                $logs[] = $line;
            }
        }
        
        return $logs;
    }
    
    /**
     * Clear old log files
     */
    public static function clearOldLogs(int $daysToKeep = 30): int
    {
        $cleared = 0;
        $threshold = time() - ($daysToKeep * 86400);
        
        $files = glob(self::$logPath . '/*.log');
        
        foreach ($files as $file) {
            if (filemtime($file) < $threshold) {
                unlink($file);
                $cleared++;
            }
        }
        
        return $cleared;
    }
}
