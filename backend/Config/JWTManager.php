<?php
namespace TaskManager\Config;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JWTManager
{
    private static string $secretKey;
    private static int $expiry;
    
    public static function init(): void
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
        
        self::$secretKey = $_ENV['JWT_SECRET'] ?? 'default-secret-key';
        self::$expiry = (int)($_ENV['JWT_EXPIRY'] ?? 3600);
    }
    
    /**
     * Generate JWT token
     */
    public static function generateToken(array $userData): string
    {
        self::init();
        
        $payload = [
            'iat' => time(),
            'exp' => time() + self::$expiry,
            'id' => $userData['id'],
            'email' => $userData['email'],
            'username' => $userData['username'],
            'role' => $userData['role'] ?? 'user'
        ];
        
        return JWT::encode($payload, self::$secretKey, 'HS256');
    }
    
    /**
     * Validate JWT token
     */
    public static function validateToken(string $token): array
    {
        try {
            self::init();
            
            $decoded = JWT::decode($token, new Key(self::$secretKey, 'HS256'));
            
            return [
                'valid' => true,
                'data' => $decoded,
                'error' => null
            ];
            
        } catch (Exception $e) {
            return [
                'valid' => false,
                'data' => null,
                'error' => 'Token invalide: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Get token from Authorization header
     */
    public static function getTokenFromHeader(): ?string
    {
        $headers = getallheaders();
        
        if (!$headers) {
            return null;
        }
        
        // Check for Authorization header (case-insensitive)
        $authHeader = null;
        foreach ($headers as $key => $value) {
            if (strtolower($key) === 'authorization') {
                $authHeader = $value;
                break;
            }
        }
        
        if (!$authHeader) {
            return null;
        }
        
        // Extract token from "Bearer <token>" format
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Refresh token (generate new token with updated expiry)
     */
    public static function refreshToken(string $token): ?string
    {
        $validation = self::validateToken($token);
        
        if (!$validation['valid']) {
            return null;
        }
        
        $userData = (array)$validation['data'];
        
        return self::generateToken($userData);
    }
    
    /**
     * Decode token without validation (for debugging)
     */
    public static function decodeToken(string $token): ?object
    {
        try {
            $parts = explode('.', $token);
            if (count($parts) !== 3) {
                return null;
            }
            
            $payload = json_decode(base64_decode($parts[1]));
            return $payload;
            
        } catch (Exception $e) {
            return null;
        }
    }
    
    /**
     * Check if token is expired
     */
    public static function isTokenExpired(string $token): bool
    {
        $decoded = self::decodeToken($token);
        
        if (!$decoded || !isset($decoded->exp)) {
            return true;
        }
        
        return time() > $decoded->exp;
    }
    
    /**
     * Get user ID from token
     */
    public static function getUserIdFromToken(string $token): ?int
    {
        $validation = self::validateToken($token);
        
        if (!$validation['valid']) {
            return null;
        }
        
        return $validation['data']->id ?? null;
    }
    
    /**
     * Get user role from token
     */
    public static function getUserRoleFromToken(string $token): ?string
    {
        $validation = self::validateToken($token);
        
        if (!$validation['valid']) {
            return null;
        }
        
        return $validation['data']->role ?? null;
    }
}
