<?php
require_once '../../vendor/autoload.php';

use TaskManager\Models\Task;
use TaskManager\Models\Notification;
use TaskManager\Utils\Response;
use TaskManager\Middleware\CorsMiddleware;
use TaskManager\Middleware\AuthMiddleware;
use TaskManager\Middleware\ValidationMiddleware;

CorsMiddleware::handle();
AuthMiddleware::handle();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Méthode non autorisée', 405);
}

$id = $_GET['id'] ?? null;

if (!$id) {
    Response::error('ID de la tâche manquant', 400);
}

$rules = [
    'user_ids' => ['required', 'array'],
    'message' => [['max', 500]]
];

$data = ValidationMiddleware::validate($rules);

$taskModel = new Task();
$task = $taskModel->findById($id);

if (!$task) {
    Response::error('Tâche non trouvée', 404);
}

// Vérifier les permissions
$userId = AuthMiddleware::getCurrentUserId();
if (!$taskModel->canUserAccess($id, $userId)) {
    Response::error('Accès non autorisé', 403);
}

// Créer des notifications pour les utilisateurs partagés
$notificationModel = new Notification();
$user = AuthMiddleware::getCurrentUser();

foreach ($data['user_ids'] as $sharedUserId) {
    $notificationModel->create([
        'user_id' => $sharedUserId,
        'type' => 'task_shared',
        'title' => 'Tâche partagée',
        'message' => "{$user->username} a partagé la tâche \"{$task['title']}\" avec vous",
        'data' => [
            'task_id' => $id,
            'sharer_id' => $userId,
            'custom_message' => $data['message'] ?? null
        ]
    ]);
}

Response::success('Tâche partagée avec succès');