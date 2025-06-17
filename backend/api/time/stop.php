<?php
require_once '../../vendor/autoload.php';

use TaskManager\Models\TimeEntry;
use TaskManager\Utils\Response;
use TaskManager\Middleware\CorsMiddleware;
use TaskManager\Middleware\AuthMiddleware;

CorsMiddleware::handle();
AuthMiddleware::handle();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Méthode non autorisée', 405);
}

$id = $_GET['id'] ?? null;

if (!$id) {
    // Si pas d'ID fourni, arrêter l'entrée active de l'utilisateur
    $timeEntryModel = new TimeEntry();
    $userId = AuthMiddleware::getCurrentUserId();
    $activeEntry = $timeEntryModel->getActiveEntry($userId);
    
    if (!$activeEntry) {
        Response::error('Aucune entrée de temps active', 400);
    }
    
    $id = $activeEntry['id'];
}

$timeEntryModel = new TimeEntry();
$entry = $timeEntryModel->findById($id);

if (!$entry) {
    Response::error('Entrée de temps non trouvée', 404);
}

// Vérifier que l'entrée appartient à l'utilisateur
$userId = AuthMiddleware::getCurrentUserId();
if ($entry['user_id'] != $userId) {
    Response::error('Accès non autorisé', 403);
}

// Vérifier que l'entrée n'est pas déjà arrêtée
if ($entry['end_time']) {
    Response::error('Cette entrée de temps est déjà arrêtée', 400);
}

$endTime = date('Y-m-d H:i:s');
$result = $timeEntryModel->stop($id, $endTime);

if ($result['success']) {
    $entry = $timeEntryModel->findById($id);
    Response::success('Chronomètre arrêté', $entry);
}

Response::error('Erreur lors de l\'arrêt du chronomètre', 400);