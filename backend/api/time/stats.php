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
$projectId = $_GET['project_id'] ?? null;
$period = $_GET['period'] ?? 'month'; // day, week, month, year

$stats = $timeEntryModel->getStats($userId, $projectId, $period);

// Calculer des statistiques supplémentaires
$totalTime = 0;
$totalEntries = 0;

foreach ($stats as $stat) {
    $totalTime += $stat['total_duration'];
    $totalEntries += $stat['entries_count'];
}

$response = [
    'stats' => $stats,
    'summary' => [
        'total_time' => $totalTime,
        'total_entries' => $totalEntries,
        'average_time_per_entry' => $totalEntries > 0 ? round($totalTime / $totalEntries) : 0,
        'periods_count' => count($stats)
    ]
];

Response::success('Statistiques récupérées', $response);