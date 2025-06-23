<?php
/**
 * Register API Endpoint
 * 
 * Creates a new user account and returns a JWT token
 */

require_once '../../Bootstrap.php';

use TaskManager\Bootstrap;
use TaskManager\Models\User;
use TaskManager\Config\JWTManager;
use TaskManager\Services\ResponseService;
use TaskManager\Middleware\ValidationMiddleware;

// Initialize application
Bootstrap::init();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ResponseService::error('Méthode non autorisée', 405);
}

try {
    // Validation rules
    $rules = [
        'email' => 'required|email',
        'password' => 'required|min:6',
        'username' => 'min:3|max:50',
        'first_name' => 'max:50',
        'last_name' => 'max:50',
        'language' => 'in:fr,en',
        'timezone' => 'max:50'
    ];
    
    // Validate request data
    $data = ValidationMiddleware::validate($rules);
    
    // Create user model
    $userModel = new User();
    
    // Additional validation
    $errors = $userModel->validateUserData($data);
    if (!empty($errors)) {
        ResponseService::validation($errors, 'Erreur de validation');
    }
    
    // Check if email already exists
    if ($userModel->emailExists($data['email'])) {
        ResponseService::error('Cette adresse email est déjà utilisée', 409);
    }
    
    // Check if username already exists (if provided)
    if (!empty($data['username']) && $userModel->usernameExists($data['username'])) {
        ResponseService::error('Ce nom d\'utilisateur est déjà utilisé', 409);
    }
    
    // Create the user
    $result = $userModel->createUser($data);
    
    if (!$result['success']) {
        ResponseService::error($result['message'], 400);
    }
    
    $user = $result['data'];
    
    // Generate JWT token
    $token = JWTManager::generateToken($user);
    
    // Log successful registration
    if (class_exists('\\TaskManager\\Services\\LoggerService')) {
        \TaskManager\Services\LoggerService::log(
            'info',
            'User registration successful',
            [
                'user_id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]
        );
    }
    
    ResponseService::success([
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
    ], 'Compte créé avec succès', 201);
    
} catch (\Exception $e) {
    // Log error for debugging
    error_log('Register error: ' . $e->getMessage());
    error_log('Register error trace: ' . $e->getTraceAsString());
    
    if (Bootstrap::getAppInfo()['environment'] === 'development') {
        // En développement, on montre les détails de l'erreur
        ResponseService::error('Erreur interne: ' . $e->getMessage(), 500);
    } else {
        // En production, on masque les détails
        ResponseService::error('Erreur interne du serveur', 500);
    }
}
