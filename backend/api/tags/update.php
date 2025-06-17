<?php

require_once '../../vendor/autoload.php';

use TaskManager\Models\Tag;
use TaskManager\Utils\Response;
use TaskManager\Middleware\CorsMiddleware;
use TaskManager\Middleware\AuthMiddleware;
use TaskManager\Middleware\ValidationMiddleware;

CorsMiddleware::handle();
AuthMiddleware::handle();

if ($_SERVER['REQUEST_METHOD'] !== 'PUT') {
    Response::error('Méthode non autorisée', 405);
}

$id = $_GET['id'] ?? null;

if (!$id) {
    Response::error('ID du tag manquant', 400);
}

$rules = [
    'name' => [['max', 50]],
    'color' => [['regex', '/^#[0-9A-Fa-f]{6}$/']],
    'icon' => [['max', 50]]
];

$data = ValidationMiddleware::validate($rules);

$tagModel = new Tag();
$tag = $tagModel->findById($id);

if (!$tag) {
    Response::error('Tag non trouvé', 404);
}

// Vérifier les permissions
$userId = AuthMiddleware::getCurrentUserId();
$user = AuthMiddleware::getCurrentUser();

if ($tag['user_id'] != $userId && !$tag['is_global']) {
    Response::error('Accès non autorisé', 403);
}

if ($tag['is_global'] && $user->role !== 'admin') {
    Response::error('Seuls les administrateurs peuvent modifier les tags globaux', 403);
}

$result = $tagModel->update($id, $data);

if ($result['success']) {
    $tag = $tagModel->findById($id);
    Response::success('Tag mis à jour', $tag);
}

Response::error('Erreur lors de la mise à jour', 400);