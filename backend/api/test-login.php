<?php
/**
 * Test direct de l'API de login sans ValidationMiddleware
 * Endpoint: /api/test-login
 */

require_once '../../Bootstrap.php';

use TaskManager\Models\User;
use TaskManager\Config\JWTManager;
use TaskManager\Utils\Response;

// Seulement POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Méthode non autorisée', 405);
}

try {
    // Récupérer les données POST directement
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        Response::error('JSON invalide', 400);
    }
    
    // Log des données reçues
    error_log('Test login direct - Données reçues: ' . json_encode($data));
    
    // Validation manuelle simple
    if (empty($data['login'])) {
        Response::error('Champ login requis', 422);
    }
    
    if (empty($data['password'])) {
        Response::error('Champ password requis', 422);
    }
    
    // Test avec le modèle User
    $userModel = new User();
    
    // Test de l'authentification flexible
    $user = $userModel->authenticateByLogin($data['login'], $data['password']);
    
    if (!$user) {
        Response::error('Identifiants incorrects', 401);
    }
    
    // Générer le token JWT
    $token = JWTManager::generateToken($user);
    
    // Réponse de succès
    Response::success([
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'role' => $user['role']
        ],
        'token' => $token,
        'expires_in' => 3600
    ], 'Connexion réussie (test direct)');
    
} catch (Exception $e) {
    error_log('Test login direct - Erreur: ' . $e->getMessage());
    Response::error('Erreur interne: ' . $e->getMessage(), 500);
}
