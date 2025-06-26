<?php
// backend/api/users/index.php
require_once '../../Bootstrap.php';

use TaskManager\Models\User;
use TaskManager\Services\ResponseService;
use TaskManager\Middleware\AuthMiddleware;

AuthMiddleware::handle();
AuthMiddleware::requireRole(['admin']); // Seuls les admins peuvent accéder

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    ResponseService::error('Méthode non autorisée', 405);
}

try {
    $userModel = new User();
    $users = $userModel->findAll();
    
    // Retirer les mots de passe avant d'envoyer la réponse
    foreach ($users as &$user) {
        unset($user['password']);
    }
    
    ResponseService::success($users);

} catch (Exception $e) {
    ResponseService::error('Erreur lors de la récupération des utilisateurs', 500);
}
