<?php
namespace TaskManager\Services;

use TaskManager\Config\App;

class LoggerService {
    private static $logPath;
    private static $levels = ['DEBUG', 'INFO', 'WARNING', 'ERROR', 'CRITICAL'];
    
    public static function init() {
        self::$logPath = App::get('log_path', __DIR__ . '/../../logs/');
        
        if (!is_dir(self::$logPath)) {
            mkdir(self::$logPath, 0755, true);
        }
    }
    
    public static function log($level, $message, $context = []) {
        if (!self::$logPath) {
            self::init();
        }
        
        $level = strtoupper($level);
        
        if (!in_array($level, self::$levels)) {
            $level = 'INFO';
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $contextStr = !empty($context) ? ' ' . json_encode($context, JSON_UNESCAPED_UNICODE) : '';
        
        $logMessage = "[$timestamp] [$level] $message$contextStr" . PHP_EOL;
        
        $logFile = self::$logPath . date('Y-m-d') . '.log';
        
        file_put_contents($logFile, $logMessage, FILE_APPEND | LOCK_EX);
    }
    
    public static function debug($message, $context = []) {
        self::log('DEBUG', $message, $context);
    }
    
    public static function info($message, $context = []) {
        self::log('INFO', $message, $context);
    }
    
    public static function warning($message, $context = []) {
        self::log('WARNING', $message, $context);
    }
    
    public static function error($message, $context = []) {
        self::log('ERROR', $message, $context);
    }
    
    public static function critical($message, $context = []) {
        self::log('CRITICAL', $message, $context);
    }
    
    public static function getLogs($date = null, $level = null) {
        if (!self::$logPath) {
            self::init();
        }
        
        $date = $date ?: date('Y-m-d');
        $logFile = self::$logPath . $date . '.log';
        
        if (!file_exists($logFile)) {
            return [];
        }
        
        $logs = file($logFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        if ($level) {
            $level = strtoupper($level);
            $logs = array_filter($logs, function($line) use ($level) {
                return strpos($line, "[$level]") !== false;
            });
        }
        
        return array_reverse($logs); // Plus récents en premier
    }
}