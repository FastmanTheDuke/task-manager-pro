<?php
/**
 * Users Search API Endpoint
 * Permet de rechercher des utilisateurs pour les ajouter aux projets
 */

require_once __DIR__ . '/../../Bootstrap.php';

use TaskManager\Services\ResponseService;
use TaskManager\Models\User;
use TaskManager\Middleware\AuthMiddleware;

try {
    // Vérifier l'authentification
    AuthMiddleware::handle();
    
    // Récupérer le paramètre de recherche
    $search = $_GET['q'] ?? '';
    $limit = min((int)($_GET['limit'] ?? 20), 50); // Maximum 50 résultats
    
    // Debug log
    error_log("User search API - Query: '$search', Limit: $limit");
    
    if (strlen(trim($search)) < 2) {
        ResponseService::success([], 'Minimum 2 caractères requis pour la recherche');
        return;
    }
    
    $userModel = new User();
    $currentUserId = AuthMiddleware::getCurrentUserId();
    
    // Rechercher les utilisateurs
    $users = $userModel->searchUsers($search, $currentUserId, $limit);
    
    // Debug log
    error_log("User search API - Found: " . count($users) . " users");
    
    ResponseService::success($users, 'Utilisateurs trouvés');
    
} catch (Exception $e) {
    error_log('Users search API error: ' . $e->getMessage());
    error_log('Stack trace: ' . $e->getTraceAsString());
    ResponseService::error('Erreur lors de la recherche d\'utilisateurs: ' . $e->getMessage(), 500);
}
