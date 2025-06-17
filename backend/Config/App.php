<?php
namespace TaskManager\Config;

class App
{
    private static array $config = [];
    private static bool $initialized = false;
    
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }
        
        // Load environment variables
        self::loadEnv();
        
        // Set default configuration
        self::$config = [
            'app' => [
                'name' => 'Task Manager Pro',
                'version' => '1.0.0',
                'debug' => filter_var($_ENV['APP_DEBUG'] ?? false, FILTER_VALIDATE_BOOLEAN),
                'url' => $_ENV['APP_URL'] ?? 'http://localhost',
                'timezone' => $_ENV['APP_TIMEZONE'] ?? 'Europe/Paris'
            ],
            'database' => [
                'host' => $_ENV['DB_HOST'] ?? 'localhost',
                'name' => $_ENV['DB_NAME'] ?? 'task_manager_pro',
                'user' => $_ENV['DB_USER'] ?? 'root',
                'password' => $_ENV['DB_PASS'] ?? '',
                'charset' => 'utf8mb4'
            ],
            'jwt' => [
                'secret' => $_ENV['JWT_SECRET'] ?? 'default-secret-key',
                'expiry' => (int)($_ENV['JWT_EXPIRY'] ?? 3600),
                'algorithm' => 'HS256'
            ],
            'cors' => [
                'allowed_origins' => explode(',', $_ENV['CORS_ORIGINS'] ?? 'http://localhost:3000,*'),
                'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
                'allowed_headers' => [
                    'Content-Type',
                    'Authorization',
                    'X-Requested-With',
                    'Accept',
                    'Origin'
                ],
                'allow_credentials' => true,
                'max_age' => 86400
            ],
            'mail' => [
                'smtp_host' => $_ENV['SMTP_HOST'] ?? 'localhost',
                'smtp_port' => (int)($_ENV['SMTP_PORT'] ?? 587),
                'smtp_user' => $_ENV['SMTP_USER'] ?? '',
                'smtp_pass' => $_ENV['SMTP_PASS'] ?? '',
                'from_email' => $_ENV['MAIL_FROM'] ?? 'noreply@taskmanager.local',
                'from_name' => $_ENV['MAIL_FROM_NAME'] ?? 'Task Manager Pro'
            ],
            'upload' => [
                'max_size' => (int)($_ENV['UPLOAD_MAX_SIZE'] ?? 10485760), // 10MB
                'allowed_types' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'txt'],
                'upload_path' => __DIR__ . '/../../uploads/',
                'public_path' => '/uploads/'
            ],
            'security' => [
                'password_min_length' => 6,
                'session_lifetime' => 3600,
                'max_login_attempts' => 5,
                'lockout_time' => 900, // 15 minutes
                'csrf_token_lifetime' => 3600
            ]
        ];
        
        self::$initialized = true;
    }
    
    private static function loadEnv(): void
    {
        $envFile = __DIR__ . '/../../.env';
        if (file_exists($envFile)) {
            $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            foreach ($lines as $line) {
                if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                    list($key, $value) = explode('=', $line, 2);
                    $_ENV[trim($key)] = trim($value);
                }
            }
        }
    }
    
    /**
     * Get configuration value
     */
    public static function get(string $key, $default = null)
    {
        if (!self::$initialized) {
            self::init();
        }
        
        $keys = explode('.', $key);
        $value = self::$config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return $default;
            }
            $value = $value[$k];
        }
        
        return $value;
    }
    
    /**
     * Set configuration value
     */
    public static function set(string $key, $value): void
    {
        if (!self::$initialized) {
            self::init();
        }
        
        $keys = explode('.', $key);
        $config = &self::$config;
        
        foreach ($keys as $k) {
            if (!isset($config[$k]) || !is_array($config[$k])) {
                $config[$k] = [];
            }
            $config = &$config[$k];
        }
        
        $config = $value;
    }
    
    /**
     * Check if configuration key exists
     */
    public static function has(string $key): bool
    {
        if (!self::$initialized) {
            self::init();
        }
        
        $keys = explode('.', $key);
        $value = self::$config;
        
        foreach ($keys as $k) {
            if (!isset($value[$k])) {
                return false;
            }
            $value = $value[$k];
        }
        
        return true;
    }
    
    /**
     * Get all configuration
     */
    public static function all(): array
    {
        if (!self::$initialized) {
            self::init();
        }
        
        return self::$config;
    }
    
    /**
     * Get environment variable
     */
    public static function env(string $key, $default = null)
    {
        return $_ENV[$key] ?? $default;
    }
    
    /**
     * Check if application is in debug mode
     */
    public static function isDebug(): bool
    {
        return self::get('app.debug', false);
    }
    
    /**
     * Get application URL
     */
    public static function url(string $path = ''): string
    {
        $baseUrl = rtrim(self::get('app.url'), '/');
        return $baseUrl . '/' . ltrim($path, '/');
    }
    
    /**
     * Get upload URL
     */
    public static function uploadUrl(string $filename = ''): string
    {
        $publicPath = rtrim(self::get('upload.public_path'), '/');
        return self::url($publicPath . '/' . ltrim($filename, '/'));
    }
}
