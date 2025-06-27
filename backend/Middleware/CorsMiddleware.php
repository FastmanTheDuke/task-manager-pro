<?php
namespace TaskManager\Middleware;

class CorsMiddleware 
{
    /**
     * Handle CORS for all requests
     */
    public static function handle(): void 
    {
        // Ne pas exécuter CORS en mode CLI (ligne de commande)
        if (php_sapi_name() === 'cli' || !isset($_SERVER['REQUEST_METHOD'])) {
            return;
        }
        
        // Get the origin of the request
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
        
        // Define allowed origins (in production, replace with specific domains)
        $allowedOrigins = [
            'http://localhost:3000',
            'http://localhost:3001',
            'http://localhost:8000',
            'http://localhost:8080',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:3001',
            'http://127.0.0.1:8000',
            'http://127.0.0.1:8080'
        ];
        
        // Check if the origin is allowed
        if (in_array($origin, $allowedOrigins)) {
            header("Access-Control-Allow-Origin: $origin");
        } else {
            // For development, allow all origins. In production, be more restrictive
            header("Access-Control-Allow-Origin: *");
        }
        
        // Allow credentials (important for authentication)
        header("Access-Control-Allow-Credentials: true");
        
        // Allow these headers
        header("Access-Control-Allow-Headers: Origin, X-Requested-With, Content-Type, Accept, Authorization, X-Auth-Token, X-API-Key");
        
        // Allow these methods
        header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, PATCH, OPTIONS");
        
        // Cache preflight response for 1 hour
        header("Access-Control-Max-Age: 3600");
        
        // Handle preflight OPTIONS requests (seulement si REQUEST_METHOD existe)
        if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit(0);
        }
    }
    
    /**
     * Set specific CORS headers for API responses
     */
    public static function setApiHeaders(): void
    {
        self::handle();
        
        // Ne pas définir les headers HTTP en mode CLI
        if (php_sapi_name() === 'cli') {
            return;
        }
        
        // Additional headers for API responses
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
    }
    
    /**
     * Check if the request origin is allowed
     */
    public static function isOriginAllowed(string $origin): bool
    {
        $allowedOrigins = [
            'http://localhost:3000',
            'http://localhost:3001',
            'http://localhost:8000',
            'http://localhost:8080',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:3001',
            'http://127.0.0.1:8000',
            'http://127.0.0.1:8080'
        ];
        
        return in_array($origin, $allowedOrigins);
    }
    
    /**
     * Check if we're running in CLI mode
     */
    public static function isCli(): bool
    {
        return php_sapi_name() === 'cli' || !isset($_SERVER['REQUEST_METHOD']);
    }
}
