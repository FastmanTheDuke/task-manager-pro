<?php
require_once '../../vendor/autoload.php';

use TaskManager\Models\Tag;
use TaskManager\Utils\Response;
use TaskManager\Middleware\CorsMiddleware;
use TaskManager\Middleware\AuthMiddleware;

CorsMiddleware::handle();
AuthMiddleware::handle();

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    Response::error('Méthode non autorisée', 405);
}

$id = $_GET['id'] ?? null;

if (!$id) {
    Response::error('ID du tag manquant', 400);
}

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
    Response::error('Seuls les administrateurs peuvent supprimer les tags globaux', 403);
}

$result = $tagModel->delete($id);

if ($result['success']) {
    Response::success('Tag supprimé');
}

Response::error('Erreur lors de la suppression', 400);