<?php
require_once '../../vendor/autoload.php';

use TaskManager\Models\User;
use TaskManager\Utils\Response;
use TaskManager\Middleware\CorsMiddleware;
use TaskManager\Middleware\ValidationMiddleware;

CorsMiddleware::handle();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Méthode non autorisée', 405);
}

$rules = [
    'token' => 'required',
    'password' => ['required', ['min', 8]],
    'password_confirmation' => ['required', 'confirmed']
];

$data = ValidationMiddleware::validate($rules);

// Vérifier le token (à implémenter avec une table password_resets)
// Pour la démo, on simule
Response::error('Fonctionnalité en cours de développement', 501);