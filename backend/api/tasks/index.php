<?php
require_once '../../vendor/autoload.php';

use TaskManager\Models\Task;
use TaskManager\Utils\Response;
use TaskManager\Middleware\CorsMiddleware;
use TaskManager\Middleware\AuthMiddleware;
use TaskManager\Config\App;

CorsMiddleware::handle();
AuthMiddleware::handle();

$taskModel = new Task();
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Récupérer les filtres
        $filters = [];
        
        if (isset($_GET['status'])) {
            $filters['status'] = $_GET['status'];
        }
        
        if (isset($_GET['priority'])) {
            $filters['priority'] = $_GET['priority'];
        }
        
        if (isset($_GET['assignee_id'])) {
            $filters['assignee_id'] = $_GET['assignee_id'];
        }
        
        if (isset($_GET['project_id'])) {
            $filters['project_id'] = $_GET['project_id'];
        }
        
        if (isset($_GET['search'])) {
            $filters['search'] = $_GET['search'];
        }
        
        if (isset($_GET['tags'])) {
            $filters['tags'] = explode(',', $_GET['tags']);
        }
        
        // Pagination
        $page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
        $limit = isset($_GET['limit']) ? min(App::get('pagination.max_limit'), max(1, intval($_GET['limit']))) : App::get('pagination.default_limit');
        
        $result = $taskModel->findAll($filters, $page, $limit);
        
        Response::paginated($result['data'], $result['total'], $result['page'], $result['limit']);
        break;
        
    default:
        Response::error('Méthode non autorisée', 405);
}