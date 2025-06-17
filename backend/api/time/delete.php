<?php
require_once '../../vendor/autoload.php';

use TaskManager\Models\TimeEntry;
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
    Response::error('ID de l\'entrée de temps manquant', 400);
}

$timeEntryModel = new TimeEntry();
$entry = $timeEntryModel->findById($id);

if (!$entry) {
    Response::error('Entrée de temps non trouvée', 404);
}

// Vérifier les permissions
$userId = AuthMiddleware::getCurrentUserId();
$user = AuthMiddleware::getCurrentUser();

if ($entry['user_id'] != $userId && $user->role !== 'admin') {
    Response::error('Accès non autorisé', 403);
}

$result = $timeEntryModel->delete($id);

if ($result['success']) {
    Response::success('Entrée de temps supprimée');
}

Response::error('Erreur lors de la suppression', 400);