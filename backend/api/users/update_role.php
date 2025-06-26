<?php
// backend/api/users/update_role.php
require_once '../../Bootstrap.php';

use TaskManager\Models\User;
use TaskManager\Services\ResponseService;
use TaskManager\Middleware\AuthMiddleware;
use TaskManager\Middleware\ValidationMiddleware;

AuthMiddleware::handle();
AuthMiddleware::requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ResponseService::error('Méthode non autorisée', 405);
}

$rules = [
    'user_id' => 'required|integer',
    'role' => 'required|in:user,manager,admin'
];

$data = ValidationMiddleware::validate($rules);

try {
    $userModel = new User();
    
    // Empêcher un admin de changer son propre rôle pour éviter de se bloquer
    if ($data['user_id'] == AuthMiddleware::getCurrentUserId()) {
        ResponseService::error('Vous ne pouvez pas modifier votre propre rôle.', 403);
    }
    
    $result = $userModel->update($data['user_id'], ['role' => $data['role']]);

    if ($result['success']) {
        ResponseService::success($result['data'], 'Rôle mis à jour avec succès.');
    } else {
        ResponseService::error($result['message'] ?? 'Erreur lors de la mise à jour du rôle.', 400);
    }
} catch (Exception $e) {
    ResponseService::error('Erreur serveur lors de la mise à jour du rôle.', 500);
}
