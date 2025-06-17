<?php
require_once '../../vendor/autoload.php';

use TaskManager\Models\Task;
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
    Response::error('ID de la tâche manquant', 400);
}

$taskModel = new Task();

// Vérifier les permissions (seul le créateur ou un admin peut supprimer)
$task = $taskModel->findById($id);
if (!$task) {
    Response::error('Tâche non trouvée', 404);
}

$userId = AuthMiddleware::getCurrentUserId();
$user = AuthMiddleware::getCurrentUser();

if ($task['creator_id'] != $userId && $user->role !== 'admin') {
    Response::error('Accès non autorisé', 403);
}

$result = $taskModel->delete($id);

if ($result['success']) {
    Response::success('Tâche supprimée');
}

Response::error('Erreur lors de la suppression', 400);