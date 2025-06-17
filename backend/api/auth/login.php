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
    'username' => 'required',
    'password' => 'required'
];

$data = ValidationMiddleware::validate($rules);

$userModel = new User();
$result = $userModel->authenticate($data['username'], $data['password']);

if ($result['success']) {
    Response::success('Connexion réussie', [
        'user' => $result['user'],
        'token' => $result['token']
    ]);
}

Response::error($result['message'], 401);