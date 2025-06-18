<?php
/**
 * Task Manager Pro - Application Bootstrap
 * 
 * This file initializes the application with proper error handling,
 * environment loading, and autoloading.
 */

namespace TaskManager;

// Ensure we're using the correct directory
define('APP_ROOT', __DIR__);

// Load Composer autoloader from the backend directory
$autoloadFile = __DIR__ . '/vendor/autoload.php';
if (!file_exists($autoloadFile)) {
    die('Error: Composer autoload file not found. Please run "composer install" in the backend directory.');
}
require_once $autoloadFile;

use TaskManager\Config\App;
use TaskManager\Services\ResponseService;
use TaskManager\Middleware\CorsMiddleware;

class Bootstrap
{
    public static function init(): void
    {
        try {
            // Initialize application configuration
            App::init();
            
            // Set error handling
            self::setupErrorHandling();
            
            // Set timezone
            date_default_timezone_set(App::get('app.timezone', 'Europe/Paris'));
            
            // Handle CORS
            CorsMiddleware::handle();
            
            // Set JSON response headers by default
            header('Content-Type: application/json; charset=utf-8');
            
        } catch (\Exception $e) {
            self::handleBootstrapError($e);
        }
    }
    
    /**
     * Setup error handling based on environment
     */
    private static function setupErrorHandling(): void
    {
        $isDebug = App::isDebug();
        
        // Set error reporting
        if ($isDebug) {
            error_reporting(E_ALL);
            ini_set('display_errors', 1);
            ini_set('display_startup_errors', 1);
        } else {
            error_reporting(E_ERROR | E_WARNING | E_PARSE);
            ini_set('display_errors', 0);
            ini_set('display_startup_errors', 0);
        }
        
        // Set custom error handler
        set_error_handler([self::class, 'errorHandler']);
        set_exception_handler([self::class, 'exceptionHandler']);
        
        // Handle fatal errors
        register_shutdown_function([self::class, 'shutdownHandler']);
    }
    
    /**
     * Custom error handler
     */
    public static function errorHandler($severity, $message, $file, $line): void
    {
        if (!(error_reporting() & $severity)) {
            return;
        }
        
        $error = [
            'type' => 'PHP Error',
            'severity' => $severity,
            'message' => $message,
            'file' => $file,
            'line' => $line,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        self::logError($error);
        
        if (App::isDebug()) {
            ResponseService::error("Error: {$message} in {$file} on line {$line}", 500);
        } else {
            ResponseService::error('Internal server error', 500);
        }
    }
    
    /**
     * Custom exception handler
     */
    public static function exceptionHandler(\Throwable $exception): void
    {
        $error = [
            'type' => 'Uncaught Exception',
            'class' => get_class($exception),
            'message' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => App::isDebug() ? $exception->getTraceAsString() : null,
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
        self::logError($error);
        
        if (App::isDebug()) {
            ResponseService::error([
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTrace()
            ], 500);
        } else {
            ResponseService::error('Internal server error', 500);
        }
    }
    
    /**
     * Shutdown handler for fatal errors
     */
    public static function shutdownHandler(): void
    {
        $error = error_get_last();
        
        if ($error && in_array($error['type'], [E_ERROR, E_CORE_ERROR, E_COMPILE_ERROR, E_PARSE])) {
            $errorInfo = [
                'type' => 'Fatal Error',
                'message' => $error['message'],
                'file' => $error['file'],
                'line' => $error['line'],
                'timestamp' => date('Y-m-d H:i:s')
            ];
            
            self::logError($errorInfo);
            
            // Clean any output buffer
            if (ob_get_level()) {
                ob_clean();
            }
            
            header('Content-Type: application/json');
            http_response_code(500);
            
            if (App::isDebug()) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Fatal error: ' . $error['message'],
                    'details' => $errorInfo
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Internal server error'
                ]);
            }
        }
    }
    
    /**
     * Log errors to file
     */
    private static function logError(array $error): void
    {
        $logDir = APP_ROOT . '/logs';
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        $logFile = $logDir . '/errors_' . date('Y-m-d') . '.log';
        $logEntry = date('Y-m-d H:i:s') . ' - ' . json_encode($error) . PHP_EOL;
        
        file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    }
    
    /**
     * Handle bootstrap errors
     */
    private static function handleBootstrapError(\Exception $e): void
    {
        error_log('Bootstrap Error: ' . $e->getMessage());
        
        header('Content-Type: application/json');
        http_response_code(500);
        
        echo json_encode([
            'success' => false,
            'message' => 'Application initialization failed',
            'error' => $e->getMessage()
        ]);
        
        exit;
    }
    
    /**
     * Get application information
     */
    public static function getAppInfo(): array
    {
        return [
            'name' => App::get('app.name'),
            'version' => App::get('app.version'),
            'environment' => App::isDebug() ? 'development' : 'production',
            'timezone' => App::get('app.timezone'),
            'php_version' => PHP_VERSION,
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true)
        ];
    }
    
    /**
     * Check if application is properly configured
     */
    public static function checkConfiguration(): array
    {
        $checks = [
            'composer_autoload' => file_exists(APP_ROOT . '/vendor/autoload.php'),
            'env_file' => file_exists(APP_ROOT . '/.env'),
            'logs_writable' => is_writable(APP_ROOT . '/logs') || mkdir(APP_ROOT . '/logs', 0755, true),
            'php_version' => version_compare(PHP_VERSION, '8.0.0', '>='),
            'required_extensions' => [
                'pdo' => extension_loaded('pdo'),
                'pdo_mysql' => extension_loaded('pdo_mysql'),
                'json' => extension_loaded('json'),
                'mbstring' => extension_loaded('mbstring')
            ]
        ];
        
        $checks['all_passed'] = $checks['composer_autoload'] && 
                               $checks['env_file'] && 
                               $checks['logs_writable'] && 
                               $checks['php_version'] &&
                               array_reduce($checks['required_extensions'], function($carry, $item) {
                                   return $carry && $item;
                               }, true);
        
        return $checks;
    }
}

// Auto-initialize if this file is included
if (!defined('BOOTSTRAP_MANUAL_INIT')) {
    Bootstrap::init();
}
