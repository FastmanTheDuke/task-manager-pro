<?php
require_once '../../vendor/autoload.php';

use TaskManager\Models\Task;
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
    'title' => ['required', ['max', 200]],
    'description' => [['max', 1000]],
    'project_id' => ['integer'],
    'assignee_id' => ['integer'],
    'status' => [['in', ['pending', 'in_progress', 'completed', 'archived', 'cancelled']]],
    'priority' => [['in', ['low', 'medium', 'high', 'urgent']]],
    'due_date' => ['date'],
    'start_date' => ['date'],
    'estimated_hours' => ['numeric'],
    'tags' => ['array']
];

$data = ValidationMiddleware::validate($rules);
$data['creator_id'] = AuthMiddleware::getCurrentUserId();

$taskModel = new Task();
$result = $taskModel->create($data);

if ($result['success']) {
    $task = $taskModel->findById($result['id']);
    Response::success('Tâche créée avec succès', $task, 201);
}

Response::error($result['message'] ?? 'Erreur lors de la création de la tâche', 400);