<?php
/**
 * Get Tasks API Endpoint
 * 
 * Retrieves tasks for the authenticated user with filtering and pagination
 */

require_once '../../Bootstrap.php';

use TaskManager\Bootstrap;
use TaskManager\Models\Task;
use TaskManager\Utils\Response;
use TaskManager\Middleware\AuthMiddleware;

// Initialize application
Bootstrap::init();

// Handle CORS and authentication
AuthMiddleware::handle();

// Only allow GET requests
if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    Response::error('Méthode non autorisée', 405);
}

try {
    $userId = AuthMiddleware::getCurrentUserId();
    $taskModel = new Task();
    
    // Get filters from query parameters
    $filters = [];
    
    if (isset($_GET['status'])) {
        $filters['status'] = $_GET['status'];
    }
    
    if (isset($_GET['priority'])) {
        $filters['priority'] = $_GET['priority'];
    }
    
    if (isset($_GET['project_id'])) {
        $filters['project_id'] = (int)$_GET['project_id'];
    }
    
    if (isset($_GET['assignee_id'])) {
        $filters['assignee_id'] = (int)$_GET['assignee_id'];
    }
    
    if (isset($_GET['due_date_from'])) {
        $filters['due_date_from'] = $_GET['due_date_from'];
    }
    
    if (isset($_GET['due_date_to'])) {
        $filters['due_date_to'] = $_GET['due_date_to'];
    }
    
    if (isset($_GET['search'])) {
        $filters['search'] = $_GET['search'];
    }
    
    // Pagination parameters
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = min(100, max(1, (int)($_GET['limit'] ?? 10)));
    $offset = ($page - 1) * $limit;
    
    // Sort parameters
    $orderBy = $_GET['sort'] ?? 'created_at';
    $orderDir = strtoupper($_GET['order'] ?? 'DESC');
    
    // Validate sort direction
    if (!in_array($orderDir, ['ASC', 'DESC'])) {
        $orderDir = 'DESC';
    }
    
    // Validate sort field
    $allowedSortFields = ['id', 'title', 'status', 'priority', 'due_date', 'created_at', 'updated_at'];
    if (!in_array($orderBy, $allowedSortFields)) {
        $orderBy = 'created_at';
    }
    
    $options = [
        'limit' => $limit,
        'offset' => $offset,
        'order_by' => $orderBy,
        'order_dir' => $orderDir
    ];
    
    // Get tasks for the user
    $tasks = $taskModel->getUserTasks($userId, $filters, $options);
    
    // Get total count for pagination
    $totalTasks = $taskModel->count([
        'OR' => [
            'creator_id' => $userId,
            'assignee_id' => $userId
        ]
    ]);
    
    // Calculate pagination info
    $totalPages = ceil($totalTasks / $limit);
    
    // Get task statistics
    $statistics = $taskModel->getStatistics($userId);
    
    Response::success([
        'tasks' => $tasks,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $totalTasks,
            'pages' => $totalPages,
            'has_next' => $page < $totalPages,
            'has_prev' => $page > 1
        ],
        'statistics' => $statistics,
        'filters_applied' => $filters
    ]);
    
} catch (\Exception $e) {
    error_log('Get tasks error: ' . $e->getMessage());
    
    if (Bootstrap::getAppInfo()['environment'] === 'development') {
        Response::error('Erreur interne: ' . $e->getMessage(), 500);
    } else {
        Response::error('Erreur interne du serveur', 500);
    }
}
