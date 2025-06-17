<?php
require_once '../../vendor/autoload.php';

use TaskManager\Models\TimeEntry;
use TaskManager\Utils\Response;
use TaskManager\Middleware\CorsMiddleware;
use TaskManager\Middleware\AuthMiddleware;

CorsMiddleware::handle();
AuthMiddleware::handle();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Méthode non autorisée', 405);
}

$timeEntryModel = new TimeEntry();
$userId = AuthMiddleware::getCurrentUserId();

// Filtres optionnels
$filters = [];

if (isset($_GET['start_date'])) {
    $filters['start_date'] = $_GET['start_date'];
}

if (isset($_GET['end_date'])) {
    $filters['end_date'] = $_GET['end_date'];
}

if (isset($_GET['task_id'])) {
    $entries = $timeEntryModel->findByTask($_GET['task_id']);
} else {
    $entries = $timeEntryModel->findByUser($userId, $filters);
}

Response::success('Entrées de temps récupérées', $entries);