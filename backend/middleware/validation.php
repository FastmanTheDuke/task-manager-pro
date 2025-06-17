<?php
namespace TaskManager\Middleware;

use TaskManager\Database\Connection;
use TaskManager\Middleware\AuthMiddleware;

class LoggerMiddleware 
{
    private static $db;
    
    public static function handle() 
    {
        self::$db = Connection::getInstance();
        
        // Enregistrer la requête (optionnel en production)
        if ($_ENV['APP_DEBUG'] === 'true') {
            self::logRequest();
        }
        
        return true;
    }
    
    public static function logActivity($action, $entityType, $entityId, $oldValues = null, $newValues = null) 
    {
        $userId = AuthMiddleware::getCurrentUserId();
        
        if (!$userId || !self::$db) {
            return;
        }
        
        try {
            // Check if activity_logs table exists
            $checkTable = "SHOW TABLES LIKE 'activity_logs'";
            $result = self::$db->query($checkTable);
            
            if ($result->rowCount() === 0) {
                // Create activity_logs table if it doesn't exist
                self::createActivityLogsTable();
            }
            
            $sql = "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent, created_at) 
                    VALUES (:user_id, :action, :entity_type, :entity_id, :old_values, :new_values, :ip_address, :user_agent, NOW())";
            
            $stmt = self::$db->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':action' => $action,
                ':entity_type' => $entityType,
                ':entity_id' => $entityId,
                ':old_values' => $oldValues ? json_encode($oldValues) : null,
                ':new_values' => $newValues ? json_encode($newValues) : null,
                ':ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
                ':user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (\Exception $e) {
            error_log("Logger error: " . $e->getMessage());
        }
    }
    
    private static function createActivityLogsTable()
    {
        $sql = "CREATE TABLE IF NOT EXISTS activity_logs (
            id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id INT(11) UNSIGNED NOT NULL,
            action VARCHAR(50) NOT NULL,
            entity_type VARCHAR(50) NOT NULL,
            entity_id INT(11) UNSIGNED DEFAULT NULL,
            old_values JSON DEFAULT NULL,
            new_values JSON DEFAULT NULL,
            ip_address VARCHAR(45) DEFAULT NULL,
            user_agent TEXT DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_user_id (user_id),
            KEY idx_entity (entity_type, entity_id),
            KEY idx_action (action),
            KEY idx_created_at (created_at)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        self::$db->exec($sql);
    }
    
    private static function logRequest() 
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => $_SERVER['REQUEST_METHOD'],
            'uri' => $_SERVER['REQUEST_URI'],
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_id' => AuthMiddleware::getCurrentUserId(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ];
        
        $logFile = dirname(__DIR__) . '/logs/requests_' . date('Y-m-d') . '.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
    }
    
    public static function logError($message, $context = [])
    {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => 'ERROR',
            'message' => $message,
            'context' => $context,
            'user_id' => AuthMiddleware::getCurrentUserId(),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'uri' => $_SERVER['REQUEST_URI'] ?? 'unknown'
        ];
        
        $logFile = dirname(__DIR__) . '/logs/errors_' . date('Y-m-d') . '.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
    }
    
    public static function logInfo($message, $context = [])
    {
        if ($_ENV['APP_DEBUG'] !== 'true') {
            return;
        }
        
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'level' => 'INFO',
            'message' => $message,
            'context' => $context
        ];
        
        $logFile = dirname(__DIR__) . '/logs/info_' . date('Y-m-d') . '.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
    }
}
