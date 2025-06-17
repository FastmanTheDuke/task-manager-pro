<?php
namespace TaskManager\Config;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;
use Firebase\JWT\SignatureInvalidException;
use Firebase\JWT\BeforeValidException;

class JWTManager {
    private static $secret;
    private static $algorithm = 'HS256';
    private static $expiry;
    
    public static function init() {
        self::$secret = $_ENV['JWT_SECRET'] ?? 'default-secret-change-this';
        self::$expiry = $_ENV['JWT_EXPIRY'] ?? 3600;
    }
    
    public static function generateToken($userId, $username, $role = 'user') {
        self::init();
        
        $issuedAt = time();
        $expire = $issuedAt + self::$expiry;
        
        $payload = [
            'iat' => $issuedAt,
            'exp' => $expire,
            'iss' => $_ENV['APP_URL'] ?? 'http://localhost',
            'data' => [
                'id' => $userId,
                'username' => $username,
                'role' => $role
            ]
        ];
        
        return JWT::encode($payload, self::$secret, self::$algorithm);
    }
    
    public static function validateToken($token) {
        self::init();
        
        try {
            $decoded = JWT::decode($token, new Key(self::$secret, self::$algorithm));
            return [
                'valid' => true,
                'data' => $decoded->data
            ];
        } catch (ExpiredException $e) {
            return [
                'valid' => false,
                'error' => 'Token expiré'
            ];
        } catch (SignatureInvalidException $e) {
            return [
                'valid' => false,
                'error' => 'Signature invalide'
            ];
        } catch (BeforeValidException $e) {
            return [
                'valid' => false,
                'error' => 'Token pas encore valide'
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => 'Token invalide'
            ];
        }
    }
    
    public static function refreshToken($token) {
        $validation = self::validateToken($token);
        
        if ($validation['valid']) {
            $data = $validation['data'];
            return self::generateToken($data->id, $data->username, $data->role);
        }
        
        return null;
    }
    
    public static function getTokenFromHeader() {
        $headers = getallheaders();
        
        if (isset($headers['Authorization'])) {
            $authHeader = $headers['Authorization'];
            
            if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
                return $matches[1];
            }
        }
        
        return null;
    }
}