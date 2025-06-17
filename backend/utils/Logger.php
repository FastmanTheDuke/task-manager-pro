<?php
namespace TaskManager\Utils;

class Logger {
    private static $logDir;
    private static $levels = [
        'DEBUG' => 0,
        'INFO' => 1,
        'WARNING' => 2,
        'ERROR' => 3,
        'CRITICAL' => 4
    ];
    
    public static function init() {
        self::$logDir = dirname(__DIR__) . '/logs';
        
        if (!is_dir(self::$logDir)) {
            mkdir(self::$logDir, 0755, true);
        }
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
    
    private static function log($level, $message, $context = []) {
        if (!self::$logDir) {
            self::init();
        }
        
        $timestamp = date('Y-m-d H:i:s');
        $logFile = self::$logDir . '/' . strtolower($level) . '_' . date('Y-m-d') . '.log';
        
        $logEntry = [
            'timestamp' => $timestamp,
            'level' => $level,
            'message' => $message
        ];
        
        if (!empty($context)) {
            $logEntry['context'] = $context;
        }
        
        // Ajouter des informations supplémentaires en mode debug
        if ($_ENV['APP_DEBUG'] === 'true') {
            $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 2);
            if (isset($backtrace[1])) {
                $logEntry['file'] = $backtrace[1]['file'] ?? 'unknown';
                $logEntry['line'] = $backtrace[1]['line'] ?? 0;
            }
        }
        
        $logLine = json_encode($logEntry, JSON_UNESCAPED_UNICODE) . "\n";
        
        file_put_contents($logFile, $logLine, FILE_APPEND | LOCK_EX);
        
        // En mode debug, afficher aussi dans la sortie d'erreur
        if ($_ENV['APP_DEBUG'] === 'true' && self::$levels[$level] >= self::$levels['ERROR']) {
            error_log($logLine);
        }
    }
    
    public static function exception(\Exception $e) {
        self::error($e->getMessage(), [
            'exception' => get_class($e),
            'file' => $e->getFile(),
            'line' => $e->getLine(),
            'trace' => $e->getTraceAsString()
        ]);
    }
}