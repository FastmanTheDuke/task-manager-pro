<?php
require_once '../../vendor/autoload.php';

use TaskManager\Models\User;
use TaskManager\Utils\Response;
use TaskManager\Utils\Logger;
use TaskManager\Middleware\CorsMiddleware;
use TaskManager\Middleware\ValidationMiddleware;

CorsMiddleware::handle();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Méthode non autorisée', 405);
}

$rules = [
    'email' => ['required', 'email']
];

$data = ValidationMiddleware::validate($rules);

$userModel = new User();
$user = $userModel->findByEmail($data['email']);

if ($user) {
    // Générer un token de réinitialisation
    $resetToken = bin2hex(random_bytes(32));
    
    // Enregistrer le token en base de données (à implémenter)
    // Pour l'instant, on log simplement
    Logger::info('Demande de réinitialisation de mot de passe', [
        'user_id' => $user['id'],
        'email' => $user['email'],
        'token' => $resetToken
    ]);
    
    // Dans une vraie application, envoyer un email avec le lien de réinitialisation
    // Pour la démo, on retourne juste un succès
}

// Toujours retourner un succès pour ne pas révéler si l'email existe
Response::success('Si cette adresse email existe, vous recevrez un lien de réinitialisation.');