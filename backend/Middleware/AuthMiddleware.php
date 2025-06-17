<?php
namespace TaskManager\Middleware;

use TaskManager\Config\JWTManager;
use TaskManager\Utils\Response;

class AuthMiddleware {
    private static $publicRoutes = [
        'POST:/api/auth/login',
        'POST:/api/auth/register',
        'POST:/api/auth/forgot-password',
        'GET:/api/health'
    ];
    
    public static function handle() {
        $method = $_SERVER['REQUEST_METHOD'];
        $uri = $_SERVER['REQUEST_URI'];
        $route = $method . ':' . parse_url($uri, PHP_URL_PATH);
        
        // Vérifier si la route est publique
        if (in_array($route, self::$publicRoutes)) {
            return true;
        }
        
        // Récupérer le token
        $token = JWTManager::getTokenFromHeader();
        
        if (!$token) {
            Response::error('Token manquant', 401);
        }
        
        // Valider le token
        $validation = JWTManager::validateToken($token);
        
        if (!$validation['valid']) {
            Response::error($validation['error'], 401);
        }
        
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
            Response::error('Accès non autorisé', 403);
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