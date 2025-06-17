<?php
require_once '../../vendor/autoload.php';

use TaskManager\Models\Task;
use TaskManager\Models\Comment;
use TaskManager\Models\TimeEntry;
use TaskManager\Utils\Response;
use TaskManager\Middleware\CorsMiddleware;
use TaskManager\Middleware\AuthMiddleware;

CorsMiddleware::handle();
AuthMiddleware::handle();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Méthode non autorisée', 405);
}

$id = $_GET['id'] ?? null;

if (!$id) {
    Response::error('ID de la tâche manquant', 400);
}

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

// Ajouter des informations supplémentaires si demandées
if (isset($_GET['include'])) {
    $includes = explode(',', $_GET['include']);
    
    if (in_array('comments', $includes)) {
        $commentModel = new Comment();
        $task['comments'] = $commentModel->findByTask($id);
    }
    
    if (in_array('time_entries', $includes)) {
        $timeEntryModel = new TimeEntry();
        $task['time_entries'] = $timeEntryModel->findByTask($id);
    }
    
    if (in_array('attachments', $includes)) {
        // À implémenter
        $task['attachments'] = [];
    }
}

Response::success('Tâche récupérée', $task);