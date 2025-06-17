<?php
require_once '../../vendor/autoload.php';

use TaskManager\Models\TimeEntry;
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
    'task_id' => ['required', 'integer'],
    'description' => [['max', 500]]
];

$data = ValidationMiddleware::validate($rules);
$userId = AuthMiddleware::getCurrentUserId();

// Vérifier qu'il n'y a pas déjà une entrée active
$timeEntryModel = new TimeEntry();
$activeEntry = $timeEntryModel->getActiveEntry($userId);

if ($activeEntry) {
    Response::error('Une entrée de temps est déjà en cours. Veuillez l\'arrêter avant d\'en démarrer une nouvelle.', 400);
}

// Vérifier l'accès à la tâche
$taskModel = new Task();
if (!$taskModel->canUserAccess($data['task_id'], $userId)) {
    Response::error('Accès non autorisé à cette tâche', 403);
}

// Créer l'entrée de temps
$data['user_id'] = $userId;
$data['start_time'] = date('Y-m-d H:i:s');

$result = $timeEntryModel->create($data);

if ($result['success']) {
    $entry = $timeEntryModel->findById($result['id']);
    Response::success('Chronomètre démarré', $entry, 201);
}

Response::error('Erreur lors du démarrage du chronomètre', 400);