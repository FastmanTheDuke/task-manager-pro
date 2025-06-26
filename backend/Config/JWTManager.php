<?php
namespace TaskManager\Config;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Exception;

class JWTManager
{
    private static ?string $secretKey = null;
    private static ?int $expiry = null;
    private static bool $initialized = false;
    
    public static function init(): void
    {
        if (self::$initialized) {
            return;
        }
        
        // Load environment variables from multiple possible paths
        $envPaths = [
            __DIR__ . '/../.env',
            __DIR__ . '/../../.env',
            __DIR__ . '/../../../.env'
        ];
        
        foreach ($envPaths as $envFile) {
            if (file_exists($envFile)) {
                $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
                if ($lines) {
                    foreach ($lines as $line) {
                        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                            list($key, $value) = explode('=', $line, 2);
                            $key = trim($key);
                            $value = trim($value, " \t\n\r\0\x0B\"'");
                            $_ENV[$key] = $value;
                        }
                    }
                }
                break;
            }
        }
        
        // Set default values with better security
        self::$secretKey = $_ENV['JWT_SECRET'] ?? self::generateSecretKey();
        self::$expiry = (int)($_ENV['JWT_EXPIRY'] ?? 3600);
        
        // Validate minimum requirements
        if (strlen(self::$secretKey) < 32) {
            self::$secretKey = self::generateSecretKey();
            error_log("Warning: JWT secret key too short, generated a new one");
        }
        
        self::$initialized = true;
    }
    
    /**
     * Generate a secure secret key if none is provided
     */
    private static function generateSecretKey(): string
    {
        return bin2hex(random_bytes(32));
    }
    
    /**
     * Generate JWT token
     */
    public static function generateToken(array $userData): string
    {
        self::init();
        
        $now = time();
        $payload = [
            'iat' => $now,
            'exp' => $now + self::$expiry,
            'nbf' => $now,
            'jti' => uniqid(),
            'id' => (int)$userData['id'],
            'email' => $userData['email'],
            'username' => $userData['username'],
            'role' => $userData['role'] ?? 'user'
        ];
        
        try {
            return JWT::encode($payload, self::$secretKey, 'HS256');
        } catch (Exception $e) {
            error_log("JWT generation error: " . $e->getMessage());
            throw new Exception("Token generation failed");
        }
    }
    
    /**
     * Validate JWT token
     */
    public static function validateToken(string $token): array
    {
        try {
            self::init();
            
            if (empty($token)) {
                return [
                    'valid' => false,
                    'data' => null,
                    'error' => 'Token vide'
                ];
            }
            
            $decoded = JWT::decode($token, new Key(self::$secretKey, 'HS256'));
            
            // Additional validation
            if (!isset($decoded->id) || !isset($decoded->exp)) {
                return [
                    'valid' => false,
                    'data' => null,
                    'error' => 'Token invalide: données manquantes'
                ];
            }
            
            return [
                'valid' => true,
                'data' => $decoded,
                'error' => null
            ];
            
        } catch (Exception $e) {
            $errorMsg = 'Token invalide';
            
            // Provide more specific error messages
            if (strpos($e->getMessage(), 'Expired') !== false) {
                $errorMsg = 'Token expiré';
            } elseif (strpos($e->getMessage(), 'Signature') !== false) {
                $errorMsg = 'Signature du token invalide';
            } elseif (strpos($e->getMessage(), 'malformed') !== false) {
                $errorMsg = 'Format du token invalide';
            }
            
            return [
                'valid' => false,
                'data' => null,
                'error' => $errorMsg
            ];
        }
    }
    
    /**
     * Get token from Authorization header with better compatibility
     */
    public static function getTokenFromHeader(): ?string
    {
        // Try multiple methods to get headers
        $headers = null;
        
        if (function_exists('getallheaders')) {
            $headers = getallheaders();
        } else {
            // Fallback for environments where getallheaders() doesn't exist
            $headers = [];
            foreach ($_SERVER as $key => $value) {
                if (strpos($key, 'HTTP_') === 0) {
                    $headerName = str_replace(' ', '-', ucwords(str_replace('_', ' ', strtolower(substr($key, 5)))));
                    $headers[$headerName] = $value;
                }
            }
        }
        
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
            return trim($matches[1]);
        }
        
        return null;
    }
    
    /**
     * Refresh token with better validation
     */
    public static function refreshToken(string $token): ?string
    {
        try {
            // First, try to decode the token even if expired
            $decoded = self::decodeToken($token);
            
            if (!$decoded || !isset($decoded->id)) {
                return null;
            }
            
            // Check if token is not too old (allow refresh within 24 hours of expiry)
            $maxAge = time() - (24 * 3600); // 24 hours ago
            if (isset($decoded->exp) && $decoded->exp < $maxAge) {
                return null;
            }
            
            // Create new token with the user data
            $userData = [
                'id' => $decoded->id,
                'email' => $decoded->email,
                'username' => $decoded->username,
                'role' => $decoded->role ?? 'user'
            ];
            
            return self::generateToken($userData);
            
        } catch (Exception $e) {
            error_log("Token refresh error: " . $e->getMessage());
            return null;
        }
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
            
            // Add padding if necessary
            $payload = $parts[1];
            $remainder = strlen($payload) % 4;
            if ($remainder) {
                $payload .= str_repeat('=', 4 - $remainder);
            }
            
            $decoded = base64_decode($payload);
            if ($decoded === false) {
                return null;
            }
            
            $json = json_decode($decoded);
            return $json;
            
        } catch (Exception $e) {
            error_log("Token decode error: " . $e->getMessage());
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
        
        return isset($validation['data']->id) ? (int)$validation['data']->id : null;
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
    
    /**
     * Get user data from token
     */
    public static function getUserFromToken(string $token): ?array
    {
        $validation = self::validateToken($token);
        
        if (!$validation['valid']) {
            return null;
        }
        
        $userData = $validation['data'];
        return [
            'id' => (int)$userData->id,
            'email' => $userData->email,
            'username' => $userData->username,
            'role' => $userData->role ?? 'user'
        ];
    }
    
    /**
     * Check if token will expire soon (within next 5 minutes)
     */
    public static function tokenExpiresSoon(string $token): bool
    {
        $decoded = self::decodeToken($token);
        
        if (!$decoded || !isset($decoded->exp)) {
            return true;
        }
        
        $fiveMinutes = 5 * 60;
        return ($decoded->exp - time()) < $fiveMinutes;
    }
    
    /**
     * Get token expiry time
     */
    public static function getTokenExpiry(string $token): ?int
    {
        $decoded = self::decodeToken($token);
        
        if (!$decoded || !isset($decoded->exp)) {
            return null;
        }
        
        return $decoded->exp;
    }
}
