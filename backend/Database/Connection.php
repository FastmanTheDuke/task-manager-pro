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
            $dsn = sprintf(
                'mysql:host=%s;dbname=%s;charset=%s',
                self::$config['host'],
                self::$config['dbname'],
                self::$config['charset']
            );
            
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES {self::$config['charset']}"
            ];
            
            self::$instance = new PDO(
                $dsn,
                self::$config['username'],
                self::$config['password'],
                $options
            );
            
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }
    
    public static function testConnection(): bool
    {
        try {
            $pdo = self::getInstance();
            $pdo->query("SELECT 1");
            return true;
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
}
