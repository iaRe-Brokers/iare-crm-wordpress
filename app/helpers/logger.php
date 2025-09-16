<?php

namespace IareCrm\Helpers;

defined('ABSPATH') || exit;

/**
 * Logger utility for debugging plugin operations
 */
class Logger {

    private $log_file;
    
    private $max_lines;

    public function __construct() {
        $this->log_file = IARE_CRM_PLUGIN_PATH . 'debug.log';
        $this->max_lines = 2000;
    }

    /**
     * Get the log file path
     * 
     * @return string Path to the log file
     */
    public function get_log_file_path() {
        return $this->log_file;
    }

    /**
     * Check if debug mode is enabled
     * 
     * @return bool True if debug mode is enabled
     */
    public function is_debug_enabled() {
        $settings = get_option(IARE_CRM_OPTION_SETTINGS, []);
        return isset($settings['enable_debug']) ? (bool) $settings['enable_debug'] : false;
    }

    /**
     * Log an error message
     * 
     * @param string $message Error message to log
     * @param array $context Additional context data
     */
    public function error($message, $context = []) {
        if (!$this->is_debug_enabled()) {
            return;
        }
        
        $this->write_log('ERROR', $message, $context);
    }

    /**
     * Log a warning message
     * 
     * @param string $message Warning message to log
     * @param array $context Additional context data
     */
    public function warning($message, $context = []) {
        if (!$this->is_debug_enabled()) {
            return;
        }
        
        $this->write_log('WARNING', $message, $context);
    }

    /**
     * Log an info message
     * 
     * @param string $message Info message to log
     * @param array $context Additional context data
     */
    public function info($message, $context = []) {
        if (!$this->is_debug_enabled()) {
            return;
        }
        
        $this->write_log('INFO', $message, $context);
    }

    /**
     * Write a log entry
     * 
     * @param string $level Log level (ERROR, WARNING, INFO)
     * @param string $message Log message
     * @param array $context Additional context data
     */
    private function write_log($level, $message, $context = []) {
        // Format the log entry
        $timestamp = date('Y-m-d H:i:s');
        $log_entry = "[{$timestamp}] [{$level}] {$message}";
        
        // Add context if provided
        if (!empty($context)) {
            $log_entry .= ' | Context: ' . json_encode($context);
        }
        
        $log_entry .= PHP_EOL;
        
        // Write to file
        file_put_contents($this->log_file, $log_entry, FILE_APPEND | LOCK_EX);
        
        // Maintain file size limit
        $this->maintain_log_size();
    }

    /**
     * Maintain log file size within limit
     */
    private function maintain_log_size() {
        // Check if file exists and get line count
        if (!file_exists($this->log_file)) {
            return;
        }
        
        $lines = file($this->log_file, FILE_IGNORE_NEW_LINES);
        $line_count = count($lines);
        
        // If within limit, no action needed
        if ($line_count <= $this->max_lines) {
            return;
        }
        
        // Keep only the last N lines
        $lines_to_keep = array_slice($lines, -$this->max_lines);
        
        // Write back to file
        file_put_contents($this->log_file, implode(PHP_EOL, $lines_to_keep) . PHP_EOL);
    }

    /**
     * Get error logs from the log file
     * 
     * @return array Array of error log entries
     */
    public function get_error_logs() {
        if (!file_exists($this->log_file)) {
            return [];
        }
        
        $lines = file($this->log_file, FILE_IGNORE_NEW_LINES);
        $error_lines = [];
        
        foreach ($lines as $line) {
            // Check if line contains [ERROR]
            if (strpos($line, '[ERROR]') !== false) {
                $error_lines[] = $line;
            }
        }
        
        // Return the most recent errors first (last 100)
        return array_slice(array_reverse($error_lines), 0, 100);
    }

    /**
     * Clear the log file
     */
    public function clear_logs() {
        if (file_exists($this->log_file)) {
            file_put_contents($this->log_file, '');
        }
    }
}