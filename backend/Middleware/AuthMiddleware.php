<?php
namespace TaskManager\Middleware;

use TaskManager\Config\JWTManager;
use TaskManager\Services\ResponseService;

class AuthMiddleware {
    private static $publicRoutes = [
        'POST:/api/auth/login',
        'POST:/api/auth/register',
        'POST:/api/auth/forgot-password',
        'GET:/api/health',
        'GET:/api/info',
        'GET:/api',
        'GET:/api/debug',
        'POST:/api/debug'
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
        if (isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG'] === 'true') {
            error_log("AuthMiddleware - Route: $route");
            error_log("AuthMiddleware - Public routes: " . implode(', ', self::$publicRoutes));
        }
        
        // Vérifier si la route est publique
        if (in_array($route, self::$publicRoutes)) {
            return true;
        }
        
        // Récupérer le token
        $token = self::getTokenFromHeader();
        
        if (!$token) {
            error_log("AuthMiddleware - No token found in headers");
            ResponseService::error('Token manquant', 401);
            return false;
        }
        
        // Valider le token
        $validation = JWTManager::validateToken($token);
        
        if (!$validation['valid']) {
            error_log("AuthMiddleware - Token validation failed: " . $validation['error']);
            ResponseService::error('Token invalide: ' . $validation['error'], 401);
            return false;
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
    
    /**
     * Récupérer le token JWT depuis les headers HTTP
     */
    private static function getTokenFromHeader() {
        $headers = getallheaders();
        
        // Debug log
        if (isset($_ENV['APP_DEBUG']) && $_ENV['APP_DEBUG'] === 'true') {
            error_log("AuthMiddleware - Headers: " . json_encode($headers));
        }
        
        // Vérifier différentes variantes de l'header Authorization
        $authHeader = null;
        
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
        } elseif (isset($headers['authorization'])) {
            $authHeader = $headers['authorization'];
        } elseif (isset($_SERVER['HTTP_AUTHORIZATION'])) {
            $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        }
        
        if (!$authHeader) {
            return null;
        }
        
        // Extraire le token du format "Bearer <token>"
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
}