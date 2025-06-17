<?php
namespace TaskManager\Config;

class App {
    private static $config = [];
    
    public static function init() {
        self::$config = [
            'name' => 'Task Manager Pro',
            'version' => '2.0.0',
            'url' => $_ENV['APP_URL'] ?? 'http://localhost',
            'debug' => $_ENV['APP_DEBUG'] === 'true',
            'timezone' => 'Europe/Paris',
            'locale' => 'fr_FR',
            'upload_dir' => dirname(__DIR__) . '/uploads',
            'max_upload_size' => 10 * 1024 * 1024, // 10MB
            'allowed_extensions' => ['jpg', 'jpeg', 'png', 'gif', 'pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip'],
            'rate_limit' => [
                'enabled' => true,
                'requests_per_minute' => 60,
                'requests_per_hour' => 1000
            ],
            'cors' => [
                'allowed_origins' => ['*'],
                'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
                'allowed_headers' => ['Content-Type', 'Authorization', 'X-Requested-With'],
                'allow_credentials' => true,
                'max_age' => 86400
            ],
            'pagination' => [
                'default_limit' => 20,
                'max_limit' => 100
            ],
            'cache' => [
                'enabled' => false,
                'driver' => 'file',
                'ttl' => 3600
            ]
        ];
        
        // Définir le fuseau horaire
        date_default_timezone_set(self::$config['timezone']);
        
        // Définir la locale
        setlocale(LC_ALL, self::$config['locale']);
        
        // Créer le dossier uploads s'il n'existe pas
        if (!is_dir(self::$config['upload_dir'])) {
            mkdir(self::$config['upload_dir'], 0755, true);
        }
    }
    
    public static function get($key, $default = null) {
        if (empty(self::$config)) {
            self::init();
        }
        
        $keys = explode('.', $key);
        $value = self::$config;
        
        foreach ($keys as $k) {
            if (isset($value[$k])) {
                $value = $value[$k];
            } else {
                return $default;
            }
        }
        
        return $value;
    }
    
    public static function set($key, $value) {
        if (empty(self::$config)) {
            self::init();
        }
        
        $keys = explode('.', $key);
        $config = &self::$config;
        
        foreach ($keys as $i => $k) {
            if ($i === count($keys) - 1) {
                $config[$k] = $value;
            } else {
                if (!isset($config[$k]) || !is_array($config[$k])) {
                    $config[$k] = [];
                }
                $config = &$config[$k];
            }
        }
    }
    
    public static function all() {
        if (empty(self::$config)) {
            self::init();
        }
        
        return self::$config;
    }
}

// Initialiser la configuration au chargement
App::init();