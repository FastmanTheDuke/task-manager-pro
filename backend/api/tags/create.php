<?php
require_once '../../vendor/autoload.php';

use TaskManager\Models\Tag;
use TaskManager\Utils\Response;
use TaskManager\Middleware\CorsMiddleware;
use TaskManager\Middleware\AuthMiddleware;
use TaskManager\Middleware\ValidationMiddleware;

CorsMiddleware::handle();
AuthMiddleware::handle();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Méthode non autorisée', 405);
}

$rules = [
    'name' => ['required', ['max', 50]],
    'color' => [['regex', '/^#[0-9A-Fa-f]{6}$/']],
    'icon' => [['max', 50]],
    'project_id' => ['integer']
];

$data = ValidationMiddleware::validate($rules);
$data['user_id'] = AuthMiddleware::getCurrentUserId();

// Seuls les admins peuvent créer des tags globaux
$user = AuthMiddleware::getCurrentUser();
if (isset($data['is_global']) && $data['is_global'] && $user->role !== 'admin') {
    Response::error('Seuls les administrateurs peuvent créer des tags globaux', 403);
}

$tagModel = new Tag();
$result = $tagModel->create($data);

if ($result['success']) {
    $tag = $tagModel->findById($result['id']);
    Response::success('Tag créé avec succès', $tag, 201);
}

Response::error($result['message'] ?? 'Erreur lors de la création du tag', 400);