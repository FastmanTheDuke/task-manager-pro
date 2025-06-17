<?php
require_once '../../vendor/autoload.php';

use TaskManager\Config\JWTManager;
use TaskManager\Utils\Response;
use TaskManager\Middleware\CorsMiddleware;

CorsMiddleware::handle();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Méthode non autorisée', 405);
}

$token = JWTManager::getTokenFromHeader();

if (!$token) {
    Response::error('Token manquant', 401);
}

$newToken = JWTManager::refreshToken($token);

if ($newToken) {
    Response::success('Token rafraîchi', ['token' => $newToken]);
}

Response::error('Token invalide ou expiré', 401);