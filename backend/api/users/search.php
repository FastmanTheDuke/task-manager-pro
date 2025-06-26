<?php
/**
 * Users Search API Endpoint
 * Permet de rechercher des utilisateurs pour les ajouter aux projets
 */

require_once __DIR__ . '/../Bootstrap.php';

use TaskManager\Services\ResponseService;
use TaskManager\Models\User;
use TaskManager\Middleware\AuthMiddleware;

// Vérifier l'authentification
AuthMiddleware::handle();

// Récupérer le paramètre de recherche
$search = $_GET['q'] ?? '';
$limit = min((int)($_GET['limit'] ?? 20), 50); // Maximum 50 résultats

try {
    $userModel = new User();
    $currentUserId = AuthMiddleware::getCurrentUserId();
    
    // Rechercher les utilisateurs
    $users = $userModel->searchUsers($search, $currentUserId, $limit);
    
    ResponseService::success($users, 'Utilisateurs trouvés');
    
} catch (Exception $e) {
    error_log('Users search error: ' . $e->getMessage());
    ResponseService::error('Erreur lors de la recherche d\'utilisateurs', 500);
}
