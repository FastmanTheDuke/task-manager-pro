<?php
namespace TaskManager\Middleware;

use TaskManager\Config\Database;
use TaskManager\Middleware\AuthMiddleware;

class LoggerMiddleware {
    private static $db;
    
    public static function handle() {
        self::$db = Database::getInstance();
        
        // Enregistrer la requête (optionnel en production)
        if ($_ENV['APP_DEBUG'] === 'true') {
            self::logRequest();
        }
        
        return true;
    }
    
    public static function logActivity($action, $entityType, $entityId, $oldValues = null, $newValues = null) {
        $userId = AuthMiddleware::getCurrentUserId();
        
        if (!$userId) {
            return;
        }
        
        $sql = "INSERT INTO activity_logs (user_id, action, entity_type, entity_id, old_values, new_values, ip_address, user_agent) 
                VALUES (:user_id, :action, :entity_type, :entity_id, :old_values, :new_values, :ip_address, :user_agent)";
        
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
    }
    
    private static function logRequest() {
        $logData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'method' => $_SERVER['REQUEST_METHOD'],
            'uri' => $_SERVER['REQUEST_URI'],
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'user_id' => AuthMiddleware::getCurrentUserId()
        ];
        
        $logFile = dirname(__DIR__) . '/logs/requests_' . date('Y-m-d') . '.log';
        $logDir = dirname($logFile);
        
        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        file_put_contents($logFile, json_encode($logData) . "\n", FILE_APPEND | LOCK_EX);
    }
}