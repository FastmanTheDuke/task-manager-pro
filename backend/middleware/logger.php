<?php
namespace TaskManager\Middleware;

use TaskManager\Config\App;
use TaskManager\Config\Database;
use TaskManager\Utils\Response;

class RateLimitMiddleware {
    private static $db;
    
    public static function handle() {
        $rateLimitConfig = App::get('rate_limit');
        
        if (!$rateLimitConfig['enabled']) {
            return true;
        }
        
        self::$db = Database::getInstance();
        
        $ip = self::getClientIp();
        $userId = AuthMiddleware::getCurrentUserId();
        $identifier = $userId ? "user:$userId" : "ip:$ip";
        
        // Vérifier les limites
        if (!self::checkLimit($identifier, 'minute', $rateLimitConfig['requests_per_minute'], 60)) {
            Response::error('Trop de requêtes. Limite par minute dépassée.', 429);
        }
        
        if (!self::checkLimit($identifier, 'hour', $rateLimitConfig['requests_per_hour'], 3600)) {
            Response::error('Trop de requêtes. Limite par heure dépassée.', 429);
        }
        
        return true;
    }
    
    private static function checkLimit($identifier, $window, $limit, $ttl) {
        $key = "rate_limit:$identifier:$window";
        $now = time();
        
        // Nettoyer les anciennes entrées
        self::cleanOldEntries($key, $now - $ttl);
        
        // Compter les requêtes actuelles
        $count = self::getRequestCount($key, $now - $ttl);
        
        if ($count >= $limit) {
            return false;
        }
        
        // Ajouter la nouvelle requête
        self::addRequest($key, $now);
        
        return true;
    }
    
    private static function getRequestCount($key, $since) {
        // Utilisation d'une table temporaire en mémoire pour le rate limiting
        // En production, utiliser Redis ou Memcached
        
        $sessionKey = "rate_limit_$key";
        $requests = $_SESSION[$sessionKey] ?? [];
        
        $count = 0;
        foreach ($requests as $timestamp) {
            if ($timestamp >= $since) {
                $count++;
            }
        }
        
        return $count;
    }
    
    private static function addRequest($key, $timestamp) {
        $sessionKey = "rate_limit_$key";
        
        if (!isset($_SESSION[$sessionKey])) {
            $_SESSION[$sessionKey] = [];
        }
        
        $_SESSION[$sessionKey][] = $timestamp;
    }
    
    private static function cleanOldEntries($key, $before) {
        $sessionKey = "rate_limit_$key";
        
        if (isset($_SESSION[$sessionKey])) {
            $_SESSION[$sessionKey] = array_filter($_SESSION[$sessionKey], function($timestamp) use ($before) {
                return $timestamp >= $before;
            });
        }
    }
    
    private static function getClientIp() {
        $ipKeys = ['HTTP_CF_CONNECTING_IP', 'HTTP_CLIENT_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}