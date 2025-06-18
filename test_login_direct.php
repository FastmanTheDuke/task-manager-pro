<?php
/**
 * Test direct de l'API de login sans ValidationMiddleware
 * Pour identifier si le problème vient de la validation ou de l'authentification
 */

require_once './backend/Bootstrap.php';

use TaskManager\Models\User;
use TaskManager\Config\JWTManager;

// Forcer les headers JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Gérer OPTIONS pour CORS
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Seulement POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

try {
    // Récupérer les données POST directement
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'JSON invalide']);
        exit;
    }
    
    // Log des données reçues
    error_log('Test login direct - Données reçues: ' . json_encode($data));
    
    // Validation manuelle simple
    if (empty($data['login'])) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Champ login requis']);
        exit;
    }
    
    if (empty($data['password'])) {
        http_response_code(422);
        echo json_encode(['success' => false, 'message' => 'Champ password requis']);
        exit;
    }
    
    // Test avec le modèle User
    $userModel = new User();
    
    // Test de l'authentification flexible
    $user = $userModel->authenticateByLogin($data['login'], $data['password']);
    
    if (!$user) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Identifiants incorrects']);
        exit;
    }
    
    // Générer le token JWT
    $token = JWTManager::generateToken($user);
    
    // Réponse de succès
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'message' => 'Connexion réussie (test direct)',
        'data' => [
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'role' => $user['role']
            ],
            'token' => $token,
            'expires_in' => 3600
        ]
    ]);
    
} catch (Exception $e) {
    error_log('Test login direct - Erreur: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Erreur interne: ' . $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
}
