<?php
require_once '../../vendor/autoload.php';

use TaskManager\Models\Tag;
use TaskManager\Utils\Response;
use TaskManager\Middleware\CorsMiddleware;
use TaskManager\Middleware\AuthMiddleware;

CorsMiddleware::handle();
AuthMiddleware::handle();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Méthode non autorisée', 405);
}

$tagModel = new Tag();
$userId = AuthMiddleware::getCurrentUserId();
$projectId = $_GET['project_id'] ?? null;

$tags = $tagModel->findAll($userId, $projectId);

Response::success('Tags récupérés', $tags);