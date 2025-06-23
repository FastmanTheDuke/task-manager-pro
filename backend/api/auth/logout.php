<?php
/**
 * Logout API Endpoint
 * 
 * Handles user logout
 */

require_once '../../Bootstrap.php';

use TaskManager\Bootstrap;
use TaskManager\Services\ResponseService;
use TaskManager\Middleware\CorsMiddleware;
use TaskManager\Middleware\AuthMiddleware;

// Initialize application
Bootstrap::init();

// Handle CORS
CorsMiddleware::handle();

// Verify authentication
AuthMiddleware::handle();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ResponseService::error('Méthode non autorisée', 405);
}

try {
    // Log successful logout
    if (class_exists('\\TaskManager\\Services\\LoggerService')) {
        \TaskManager\Services\LoggerService::log(
            'info',
            'User logout successful',
            [
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]
        );
    }
    
    // Dans une vraie application, on pourrait invalider le token côté serveur
    // Pour l'instant, on retourne simplement un succès
    ResponseService::success(null, 'Déconnexion réussie');
    
} catch (\Exception $e) {
    error_log('Logout error: ' . $e->getMessage());
    ResponseService::error('Erreur lors de la déconnexion', 500);
}
