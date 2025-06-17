<?php
require_once '../../vendor/autoload.php';

use TaskManager\Models\Task;
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
    Response::error('ID de la tâche manquant', 400);
}

$rules = [
    'title' => [['max', 200]],
    'description' => [['max', 1000]],
    'assignee_id' => ['integer'],
    'status' => [['in', ['pending', 'in_progress', 'completed', 'archived', 'cancelled']]],
    'priority' => [['in', ['low', 'medium', 'high', 'urgent']]],
    'due_date' => ['date'],
    'start_date' => ['date'],
    'estimated_hours' => ['numeric'],
    'progress' => ['integer', ['min', 0], ['max', 100]],
    'tags' => ['array']
];

$data = ValidationMiddleware::validate($rules);

$taskModel = new Task();

// Vérifier les permissions
$userId = AuthMiddleware::getCurrentUserId();
if (!$taskModel->canUserAccess($id, $userId)) {
    Response::error('Accès non autorisé', 403);
}

$result = $taskModel->update($id, $data);

if ($result['success']) {
    $task = $taskModel->findById($id);
    Response::success('Tâche mise à jour', $task);
}

Response::error($result['message'] ?? 'Erreur lors de la mise à jour', 400);