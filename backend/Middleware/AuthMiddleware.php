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
        
        // Debug log avec gestion d'erreur
        error_log("AuthMiddleware - Route: $route");
        
        // Vérifier si la route est publique
        if (in_array($route, self::$publicRoutes)) {
            error_log("AuthMiddleware - Route is public, allowing access");
            return true;
        }
        
        // Récupérer le token avec la méthode améliorée
        $token = JWTManager::getTokenFromHeader();
        
        if (!$token) {
            error_log("AuthMiddleware - No token found in headers");
            ResponseService::error('Token d\'authentification manquant', 401);
            return false;
        }
        
        error_log("AuthMiddleware - Token found: " . substr($token, 0, 20) . "...");
        
        // Valider le token
        $validation = JWTManager::validateToken($token);
        
        if (!$validation['valid']) {
            error_log("AuthMiddleware - Token validation failed: " . $validation['error']);
            
            // Fournir un message d'erreur plus spécifique
            $errorMessage = $validation['error'];
            $errorCode = 401;
            
            if (strpos($errorMessage, 'expiré') !== false) {
                $errorMessage = 'Votre session a expiré. Veuillez vous reconnecter.';
            } elseif (strpos($errorMessage, 'invalide') !== false) {
                $errorMessage = 'Token d\'authentification invalide.';
            }
            
            ResponseService::error($errorMessage, $errorCode);
            return false;
        }
        
        error_log("AuthMiddleware - Token validation successful for user: " . $validation['data']->id);
        
        // Vérifier si le token expire bientôt et log un avertissement
        if (JWTManager::tokenExpiresSoon($token)) {
            error_log("AuthMiddleware - Token will expire soon for user: " . $validation['data']->id);
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
        
        if (!$user) {
            ResponseService::error('Authentification requise', 401);
            return false;
        }
        
        if (!in_array($user->role, $roles)) {
            ResponseService::error('Permissions insuffisantes pour accéder à cette ressource', 403);
            return false;
        }
        
        return true;
    }
    
    public static function getCurrentUser() {
        return $GLOBALS['auth_user'] ?? null;
    }
    
    public static function getCurrentUserId(): ?int {
        $user = self::getCurrentUser();
        return $user ? (int)$user->id : null;
    }
    
    public static function getCurrentUserRole(): ?string {
        $user = self::getCurrentUser();
        return $user->role ?? null;
    }
    
    public static function isAuthenticated(): bool {
        return self::getCurrentUser() !== null;
    }
    
    /**
     * Vérifier si l'utilisateur actuel a une permission spécifique
     */
    public static function hasPermission(string $permission): bool {
        $user = self::getCurrentUser();
        if (!$user) {
            return false;
        }
        
        // Logique de permissions basée sur les rôles
        $rolePermissions = [
            'admin' => ['*'], // Admin a toutes les permissions
            'manager' => ['manage_projects', 'view_reports', 'manage_users'],
            'user' => ['view_own_tasks', 'edit_own_tasks', 'view_own_projects']
        ];
        
        $userRole = $user->role ?? 'user';
        $permissions = $rolePermissions[$userRole] ?? [];
        
        return in_array('*', $permissions) || in_array($permission, $permissions);
    }
    
    /**
     * Middleware pour vérifier les permissions
     */
    public static function requirePermission(string $permission): bool {
        if (!self::hasPermission($permission)) {
            ResponseService::error('Permission refusée: ' . $permission, 403);
            return false;
        }
        return true;
    }
}
