<?php
namespace TaskManager\Middleware;

use TaskManager\Config\JWTManager;
use TaskManager\Services\ResponseService;

class AuthMiddleware {
    private static $publicRoutes = [
        'POST:/api/auth/login',
        'POST:/api/auth/register',
        'POST:/api/auth/forgot-password',
        'POST:/api/auth/refresh',
        'GET:/api/health',
        'GET:/api/info',
        'GET:/api',
        'GET:/api/debug',
        'POST:/api/debug',
        'GET:/api/diagnostic',
        'GET:/api/diagnostic/system',
        'GET:/api/diagnostic/database',
        'GET:/api/diagnostic/auth',
        'GET:/api/diagnostic/api'
    ];
    
    public static function handle() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];
        
        // Nettoyer l'URI pour la comparaison
        $path = parse_url($uri, PHP_URL_PATH);
        
        // Nettoyer le path de la même manière que dans le routeur principal
        if (strpos($path, '/api') !== false) {
            $apiPos = strpos($path, '/api');
            $path = substr($path, $apiPos);
        }
        
        $route = $method . ':' . $path;
        
        // Debug log
        error_log("AuthMiddleware - Route: $route");
        error_log("AuthMiddleware - Available headers: " . json_encode(getallheaders()));
        
        // Vérifier si la route est publique
        if (in_array($route, self::$publicRoutes)) {
            error_log("AuthMiddleware - Route is public, allowing access");
            return true;
        }
        
        // Récupérer le token
        $token = JWTManager::getTokenFromHeader();
        
        if (!$token) {
            error_log("AuthMiddleware - No token found in headers");
            ResponseService::error('Token manquant', 401);
            return false;
        }
        
        error_log("AuthMiddleware - Token found: " . substr($token, 0, 20) . "...");
        
        // Valider le token
        $validation = JWTManager::validateToken($token);
        
        if (!$validation['valid']) {
            error_log("AuthMiddleware - Token validation failed: " . $validation['error']);
            ResponseService::error($validation['error'], 401);
            return false;
        }
        
        error_log("AuthMiddleware - Token validation successful for user: " . $validation['data']->id);
        
        // Stocker les données utilisateur pour utilisation ultérieure
        $GLOBALS['auth_user'] = $validation['data'];
        
        return true;
    }
    
    public static function requireRole($roles) {
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        
        $user = $GLOBALS['auth_user'] ?? null;
        
        if (!$user || !in_array($user->role, $roles)) {
            ResponseService::error('Accès non autorisé', 403);
            return false;
        }
        
        return true;
    }
    
    public static function getCurrentUser() {
        return $GLOBALS['auth_user'] ?? null;
    }
    
    public static function getCurrentUserId() {
        $user = self::getCurrentUser();
        return $user ? $user->id : null;
    }
}