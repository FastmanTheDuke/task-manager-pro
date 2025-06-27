<?php
/**
 * Users Search API Endpoint
 * Permet de rechercher des utilisateurs pour les ajouter aux projets
 */

// Chemin corrigé vers Bootstrap.php
require_once __DIR__ . '/../../Bootstrap.php';

use TaskManager\Services\ResponseService;
use TaskManager\Models\User;
use TaskManager\Middleware\AuthMiddleware;
use TaskManager\Middleware\CorsMiddleware;

// Gérer CORS en premier
CorsMiddleware::handle();

// Si c'est une requête OPTIONS (preflight), arrêter ici
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Debug - logger les headers reçus
    error_log("Search API - Headers received: " . json_encode(getallheaders()));
    error_log("Search API - Request method: " . $_SERVER['REQUEST_METHOD']);
    error_log("Search API - Request URI: " . $_SERVER['REQUEST_URI']);
    
    // Vérifier l'authentification
    if (!AuthMiddleware::handle()) {
        error_log("Search API - Authentication failed");
        exit; // AuthMiddleware::handle() déjà envoyé la réponse d'erreur
    }
    
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
    
    if (!$currentUserId) {
        ResponseService::error('Utilisateur non identifié', 401);
        return;
    }
    
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
