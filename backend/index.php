<?php
/**
 * Task Manager Pro - Main API Entry Point
 * 
 * This file serves as the main entry point for all API requests.
 * It handles routing, authentication, and error handling.
 */

require_once __DIR__ . '/Bootstrap.php';

use TaskManager\Bootstrap;
use TaskManager\Services\ResponseService;
use TaskManager\Services\ValidationService;
use TaskManager\Middleware\AuthMiddleware;
use TaskManager\Middleware\CorsMiddleware;
use TaskManager\Middleware\ValidationMiddleware;
use TaskManager\Models\Task;
use TaskManager\Models\User;
use TaskManager\Config\JWTManager;

// Initialize application
Bootstrap::init();

// Handle CORS first
CorsMiddleware::handle();

// Get request info
$requestMethod = $_SERVER['REQUEST_METHOD'];
$requestUri = $_SERVER['REQUEST_URI'];
$path = parse_url($requestUri, PHP_URL_PATH);

// Determine base path automatically
// If the server is started from the backend directory, no base path needed
// If started from project root, we need to remove the backend path
$scriptName = $_SERVER['SCRIPT_NAME'];
$basePath = '';

// If we're accessed through a subdirectory, remove it
if (strpos($scriptName, '/task-manager-pro/backend/') !== false) {
    $basePath = '/task-manager-pro/backend';
} elseif (strpos($scriptName, '/backend/') !== false) {
    $basePath = '/backend';
}

$path = str_replace($basePath, '', $path);

// Simple router
try {
    switch (true) {
        // Health check
        case $path === '/api/health' && $requestMethod === 'GET':
            handleHealthCheck();
            break;
            
        // Debug endpoint
        case $path === '/api/debug' && $requestMethod === 'POST':
            handleDebug();
            break;
            
        // Authentication routes
        case $path === '/api/auth/login' && $requestMethod === 'POST':
            handleLogin();
            break;
            
        case $path === '/api/auth/register' && $requestMethod === 'POST':
            handleRegister();
            break;
            
        case $path === '/api/auth/logout' && $requestMethod === 'POST':
            handleLogout();
            break;
            
        case $path === '/api/auth/refresh' && $requestMethod === 'POST':
            handleTokenRefresh();
            break;
            
        // Task routes (require authentication)
        case $path === '/api/tasks' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            handleGetTasks();
            break;
            
        case $path === '/api/tasks' && $requestMethod === 'POST':
            AuthMiddleware::handle();
            handleCreateTask();
            break;
            
        case preg_match('#^/api/tasks/(\d+)$#', $path, $matches) && $requestMethod === 'GET':
            AuthMiddleware::handle();
            handleGetTask($matches[1]);
            break;
            
        case preg_match('#^/api/tasks/(\d+)$#', $path, $matches) && $requestMethod === 'PUT':
            AuthMiddleware::handle();
            handleUpdateTask($matches[1]);
            break;
            
        case preg_match('#^/api/tasks/(\d+)$#', $path, $matches) && $requestMethod === 'DELETE':
            AuthMiddleware::handle();
            handleDeleteTask($matches[1]);
            break;
            
        // User routes
        case $path === '/api/users/profile' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            handleGetProfile();
            break;
            
        case $path === '/api/users/profile' && $requestMethod === 'PUT':
            AuthMiddleware::handle();
            handleUpdateProfile();
            break;
            
        // Application info
        case $path === '/api/info' && $requestMethod === 'GET':
            handleAppInfo();
            break;
            
        default:
            ResponseService::error('Endpoint not found', 404);
    }
    
} catch (\Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    
    if (Bootstrap::getAppInfo()['environment'] === 'development') {
        ResponseService::error('Internal server error: ' . $e->getMessage(), 500);
    } else {
        ResponseService::error('Internal server error', 500);
    }
}

// Route handlers

function handleHealthCheck(): void
{
    ResponseService::success([
        'status' => 'ok',
        'message' => 'API is running',
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => Bootstrap::getAppInfo()['version']
    ]);
}

function handleDebug(): void
{
    // Get raw input
    $rawInput = file_get_contents('php://input');
    $headers = getallheaders();
    
    ResponseService::success([
        'method' => $_SERVER['REQUEST_METHOD'],
        'path' => $_SERVER['REQUEST_URI'],
        'content_type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
        'raw_input' => $rawInput,
        'json_decoded' => json_decode($rawInput, true),
        'json_error' => json_last_error_msg(),
        'post_data' => $_POST,
        'headers' => $headers
    ]);
}

function handleLogin(): void
{
    try {
        $rules = [
            'email' => 'required|email',
            'password' => 'required'
        ];
        
        $data = ValidationMiddleware::validate($rules);
        
        $userModel = new User();
        $user = $userModel->authenticate($data['email'], $data['password']);
        
        if (!$user) {
            ResponseService::error('Email ou mot de passe incorrect', 401);
        }
        
        $token = JWTManager::generateToken($user);
        
        ResponseService::success([
            'user' => $user,
            'token' => $token,
            'expires_in' => 3600
        ], 'Connexion réussie');
        
    } catch (\Exception $e) {
        ResponseService::error('Login error: ' . $e->getMessage(), 500);
    }
}

function handleRegister(): void
{
    $rules = [
        'email' => 'required|email',
        'password' => 'required|min:6',
        'username' => 'min:3',
        'first_name' => 'max:50',
        'last_name' => 'max:50'
    ];
    
    $data = ValidationMiddleware::validate($rules);
    
    $userModel = new User();
    
    // Validate user data
    $errors = $userModel->validateUserData($data);
    if (!empty($errors)) {
        ResponseService::error('Erreur de validation', 422, $errors);
    }
    
    $result = $userModel->createUser($data);
    
    if (!$result['success']) {
        ResponseService::error($result['message'], 400);
    }
    
    $user = $result['data'];
    $token = JWTManager::generateToken($user);
    
    ResponseService::success([
        'user' => $user,
        'token' => $token,
        'expires_in' => 3600
    ], 'Compte créé avec succès', 201);
}

function handleLogout(): void
{
    // For JWT, logout is handled client-side by removing the token
    // Here we could implement token blacklisting if needed
    ResponseService::success(null, 'Déconnexion réussie');
}

function handleTokenRefresh(): void
{
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    
    if (!preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
        ResponseService::error('Token manquant', 401);
    }
    
    $token = $matches[1];
    $newToken = JWTManager::refreshToken($token);
    
    if (!$newToken) {
        ResponseService::error('Token invalide ou expiré', 401);
    }
    
    ResponseService::success([
        'token' => $newToken,
        'expires_in' => 3600
    ], 'Token renouvelé');
}

function handleGetTasks(): void
{
    $userId = AuthMiddleware::getCurrentUserId();
    $taskModel = new Task();
    
    $filters = $_GET;
    $page = (int)($_GET['page'] ?? 1);
    $limit = min((int)($_GET['limit'] ?? 10), 100);
    
    $options = [
        'limit' => $limit,
        'offset' => ($page - 1) * $limit,
        'order_by' => $_GET['sort'] ?? 'created_at',
        'order_dir' => $_GET['order'] ?? 'DESC'
    ];
    
    $tasks = $taskModel->getUserTasks($userId, $filters, $options);
    $total = $taskModel->count(['creator_id' => $userId]);
    
    ResponseService::success([
        'tasks' => $tasks,
        'pagination' => [
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'pages' => ceil($total / $limit)
        ]
    ]);
}

function handleCreateTask(): void
{
    $rules = [
        'title' => 'required|max:200',
        'description' => 'max:1000',
        'project_id' => 'integer',
        'assignee_id' => 'integer',
        'status' => 'in:pending,in_progress,completed,archived,cancelled',
        'priority' => 'in:low,medium,high,urgent',
        'due_date' => 'date',
        'start_date' => 'date',
        'estimated_hours' => 'numeric'
    ];
    
    $data = ValidationMiddleware::validate($rules);
    $data['creator_id'] = AuthMiddleware::getCurrentUserId();
    
    $taskModel = new Task();
    
    // Validate task data
    $errors = $taskModel->validateTaskData($data);
    if (!empty($errors)) {
        ResponseService::error('Erreur de validation', 422, $errors);
    }
    
    $result = $taskModel->create($data);
    
    if (!$result['success']) {
        ResponseService::error($result['message'] ?? 'Erreur lors de la création', 400);
    }
    
    ResponseService::success($result['data'], 'Tâche créée avec succès', 201);
}

function handleGetTask(int $taskId): void
{
    $taskModel = new Task();
    $task = $taskModel->getTaskDetails($taskId);
    
    if (!$task) {
        ResponseService::error('Tâche non trouvée', 404);
    }
    
    // Check if user has access to this task
    $userId = AuthMiddleware::getCurrentUserId();
    if ($task['creator_id'] != $userId && $task['assignee_id'] != $userId) {
        ResponseService::error('Accès non autorisé', 403);
    }
    
    ResponseService::success($task);
}

function handleUpdateTask(int $taskId): void
{
    $rules = [
        'title' => 'max:200',
        'description' => 'max:1000',
        'status' => 'in:pending,in_progress,completed,archived,cancelled',
        'priority' => 'in:low,medium,high,urgent',
        'due_date' => 'date',
        'completion_percentage' => 'integer'
    ];
    
    $data = ValidationMiddleware::validate($rules);
    
    $taskModel = new Task();
    $task = $taskModel->findById($taskId);
    
    if (!$task) {
        ResponseService::error('Tâche non trouvée', 404);
    }
    
    // Check if user has access to this task
    $userId = AuthMiddleware::getCurrentUserId();
    if ($task['creator_id'] != $userId && $task['assignee_id'] != $userId) {
        ResponseService::error('Accès non autorisé', 403);
    }
    
    $result = $taskModel->update($taskId, $data);
    
    if (!$result['success']) {
        ResponseService::error($result['message'] ?? 'Erreur lors de la mise à jour', 400);
    }
    
    ResponseService::success($result['data'], 'Tâche mise à jour avec succès');
}

function handleDeleteTask(int $taskId): void
{
    $taskModel = new Task();
    $task = $taskModel->findById($taskId);
    
    if (!$task) {
        ResponseService::error('Tâche non trouvée', 404);
    }
    
    // Check if user has access to this task
    $userId = AuthMiddleware::getCurrentUserId();
    if ($task['creator_id'] != $userId) {
        ResponseService::error('Seul le créateur peut supprimer cette tâche', 403);
    }
    
    $result = $taskModel->delete($taskId);
    
    if (!$result['success']) {
        ResponseService::error($result['message'] ?? 'Erreur lors de la suppression', 400);
    }
    
    ResponseService::success(null, 'Tâche supprimée avec succès');
}

function handleGetProfile(): void
{
    $userId = AuthMiddleware::getCurrentUserId();
    $userModel = new User();
    $user = $userModel->getUserWithStats($userId);
    
    if (!$user) {
        ResponseService::error('Utilisateur non trouvé', 404);
    }
    
    ResponseService::success($user);
}

function handleUpdateProfile(): void
{
    $rules = [
        'username' => 'min:3',
        'email' => 'email',
        'first_name' => 'max:50',
        'last_name' => 'max:50',
        'theme' => 'in:light,dark,auto',
        'language' => 'in:fr,en',
        'timezone' => 'string'
    ];
    
    $data = ValidationMiddleware::validate($rules);
    $userId = AuthMiddleware::getCurrentUserId();
    
    $userModel = new User();
    $result = $userModel->updateProfile($userId, $data);
    
    if (!$result['success']) {
        ResponseService::error($result['message'] ?? 'Erreur lors de la mise à jour', 400);
    }
    
    ResponseService::success($result['data'], 'Profil mis à jour avec succès');
}

function handleAppInfo(): void
{
    $info = Bootstrap::getAppInfo();
    $checks = Bootstrap::checkConfiguration();
    
    ResponseService::success([
        'app' => $info,
        'health' => $checks
    ]);
}
