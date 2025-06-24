<?php
namespace TaskManager\Database;

use PDO;
use PDOException;
use Exception;

class Connection
{
    private static ?PDO $instance = null;
    private static array $config = [];
    
    public static function getInstance(): PDO
    {
        if (self::$instance === null) {
            self::loadConfig();
            self::createConnection();
        }
        
        return self::$instance;
    }
    
    private static function loadConfig(): void
    {
        // Load environment variables
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
        
        self::$config = [
            'host' => $_ENV['DB_HOST'] ?? 'localhost',
            'dbname' => $_ENV['DB_NAME'] ?? 'task_manager_pro',
            'username' => $_ENV['DB_USER'] ?? 'root',
            'password' => $_ENV['DB_PASS'] ?? '',
            'charset' => 'utf8mb4'
        ];
    }
    
    private static function createConnection(): void
    {
        try {
            // Vérifier si PDO MySQL est disponible
            if (!extension_loaded('pdo_mysql')) {
                throw new Exception("PDO MySQL extension is not loaded. Please install or enable php-pdo-mysql extension.");
            }
            
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                self::$config['host'],
                self::$config['dbname'],
                self::$config['charset']
            );
            
            // Options PDO de base (compatibles avec toutes les versions)
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];
            
            // Créer la connexion
            self::$instance = new PDO(
                $dsn,
                self::$config['username'],
                self::$config['password'],
                $options
            );
            
            // Définir le charset avec une requête SQL standard (plus compatible)
            self::$instance->exec("SET NAMES " . self::$config['charset'] . " COLLATE " . self::$config['charset'] . "_unicode_ci");
            self::$instance->exec("SET CHARACTER SET " . self::$config['charset']);
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public static function testConnection(): bool
    {
        try {
            $pdo = self::getInstance();
            $stmt = $pdo->query("SELECT 1");
            return $stmt !== false;
        } catch (Exception $e) {
            error_log("Database test failed: " . $e->getMessage());
            return false;
        }
    }
    
    public static function beginTransaction(): bool
    {
        return self::getInstance()->beginTransaction();
    }
    
    public static function commit(): bool
    {
        return self::getInstance()->commit();
    }
    
    public static function rollBack(): bool
    {
        return self::getInstance()->rollBack();
    }
    
    /**
     * Get connection configuration for debugging
     */
    public static function getConfig(): array
    {
        self::loadConfig();
        
        // Return safe config (without password)
        return [
            'host' => self::$config['host'],
            'dbname' => self::$config['dbname'],
            'username' => self::$config['username'],
            'charset' => self::$config['charset'],
            'password_set' => !empty(self::$config['password']),
            'pdo_extension' => extension_loaded('pdo'),
            'pdo_mysql_extension' => extension_loaded('pdo_mysql'),
            'mysql_attr_constant' => defined('PDO::MYSQL_ATTR_INIT_COMMAND')
        ];
    }
    
    /**
     * Check if MySQL connection requirements are met
     */
    public static function checkRequirements(): array
    {
        $checks = [
            'pdo_extension' => extension_loaded('pdo'),
            'pdo_mysql_extension' => extension_loaded('pdo_mysql'),
            'mysql_attr_constant' => defined('PDO::MYSQL_ATTR_INIT_COMMAND')
        ];
        
        return $checks;
    }
    
    /**
     * Get detailed diagnostics for debugging
     */
    public static function getDiagnostics(): array
    {
        $diagnostics = [
            'php_version' => PHP_VERSION,
            'extensions' => [
                'pdo' => extension_loaded('pdo'),
                'pdo_mysql' => extension_loaded('pdo_mysql'),
                'mysqli' => extension_loaded('mysqli')
            ],
            'constants' => [
                'PDO::MYSQL_ATTR_INIT_COMMAND' => defined('PDO::MYSQL_ATTR_INIT_COMMAND')
            ]
        ];
        
        try {
            self::loadConfig();
            $diagnostics['config'] = [
                'host' => self::$config['host'],
                'dbname' => self::$config['dbname'],
                'username' => self::$config['username'],
                'charset' => self::$config['charset'],
                'password_set' => !empty(self::$config['password'])
            ];
            
            // Test de connexion
            $testPdo = new PDO(
                sprintf('mysql:host=%s;dbname=%s', self::$config['host'], self::$config['dbname']),
                self::$config['username'],
                self::$config['password'],
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
            $diagnostics['connection_test'] = 'SUCCESS';
            
        } catch (Exception $e) {
            $diagnostics['connection_test'] = 'FAILED: ' . $e->getMessage();
        }
        
        return $diagnostics;
    }
}
