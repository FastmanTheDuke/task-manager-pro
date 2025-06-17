<?php
require_once '../../vendor/autoload.php';

use TaskManager\Models\User;
use TaskManager\Utils\Response;
use TaskManager\Utils\Validator;
use TaskManager\Middleware\CorsMiddleware;
use TaskManager\Middleware\ValidationMiddleware;

// Gérer CORS
CorsMiddleware::handle();

// Valider la méthode HTTP
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Méthode non autorisée', 405);
}

// Définir les règles de validation
$rules = [
    'username' => ['required', ['min', 3], ['max', 50]],
    'email' => ['required', 'email'],
    'password' => ['required', ['min', 8]],
    'password_confirmation' => ['required', 'confirmed'],
    'first_name' => [['max', 50]],
    'last_name' => [['max', 50]]
];

// Valider les données
$data = ValidationMiddleware::validate($rules);

// Créer l'utilisateur
$userModel = new User();
$result = $userModel->create($data);

if ($result['success']) {
    // Authentifier automatiquement l'utilisateur
    $authResult = $userModel->authenticate($data['username'], $data['password']);
    
    if ($authResult['success']) {
        Response::success('Inscription réussie', [
            'user' => $authResult['user'],
            'token' => $authResult['token']
        ], 201);
    }
}

Response::error($result['message'] ?? 'Erreur lors de l\'inscription', 400);