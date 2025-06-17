<?php
require_once '../../vendor/autoload.php';

use TaskManager\Utils\Response;
use TaskManager\Middleware\CorsMiddleware;
use TaskManager\Middleware\AuthMiddleware;

CorsMiddleware::handle();
AuthMiddleware::handle();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Méthode non autorisée', 405);
}

// Dans une vraie application, on pourrait invalider le token côté serveur
// Pour l'instant, on retourne simplement un succès
Response::success('Déconnexion réussie');