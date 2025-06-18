<?php
/**
 * Login API Endpoint
 * 
 * Authenticates a user and returns a JWT token
 */

require_once '../../Bootstrap.php';

use TaskManager\Bootstrap;
use TaskManager\Models\User;
use TaskManager\Config\JWTManager;
use TaskManager\Utils\Response;
use TaskManager\Middleware\ValidationMiddleware;

// Initialize application
Bootstrap::init();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Méthode non autorisée', 405);
}

try {
    // Validation rules
    $rules = [
        'email' => ['required', 'email'],
        'password' => ['required']
    ];
    
    // Validate request data
    $data = ValidationMiddleware::validate($rules);
    
    // Create user model
    $userModel = new User();
    
    // Attempt authentication
    $user = $userModel->authenticate($data['email'], $data['password']);
    
    if (!$user) {
        Response::error('Email ou mot de passe incorrect', 401);
    }
    
    // Generate JWT token
    $token = JWTManager::generateToken($user);
    
    // Log successful login
    if (class_exists('\\TaskManager\\Middleware\\LoggerMiddleware')) {
        \TaskManager\Middleware\LoggerMiddleware::logActivity(
            'login',
            'user',
            $user['id'],
            null,
            ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']
        );
    }
    
    Response::success([
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'avatar' => $user['avatar'],
            'role' => $user['role'],
            'theme' => $user['theme'],
            'language' => $user['language'],
            'timezone' => $user['timezone']
        ],
        'token' => $token,
        'expires_in' => 3600
    ], 'Connexion réussie');
    
} catch (\Exception $e) {
    // CORRECTION : Meilleure gestion des erreurs
    error_log('Login error: ' . $e->getMessage());
    error_log('Login error trace: ' . $e->getTraceAsString());
    
    if (Bootstrap::getAppInfo()['environment'] === 'development') {
        // En développement, on montre les détails de l'erreur
        Response::error('Erreur interne: ' . $e->getMessage(), 500);
    } else {
        // En production, on masque les détails
        Response::error('Erreur interne du serveur', 500);
    }
}