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

// Debug info (only in development)
if (Bootstrap::getAppInfo()['environment'] === 'development') {
    error_log("=== ROUTING DEBUG ===");
    error_log("REQUEST_URI: " . $requestUri);
    error_log("SCRIPT_NAME: " . ($_SERVER['SCRIPT_NAME'] ?? 'not set'));
    error_log("REQUEST_METHOD: " . $requestMethod);
}

// Parse the path and remove query string
$path = parse_url($requestUri, PHP_URL_PATH);

// Remove base path intelligently
// Get the directory where index.php is located
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$scriptDir = dirname($scriptName);

// If we're in a subdirectory, remove it from the path
if ($scriptDir !== '/' && !empty($scriptDir)) {
    $path = substr($path, strlen($scriptDir));
}

// Ensure path starts with /
if (substr($path, 0, 1) !== '/') {
    $path = '/' . $path;
}

// Remove trailing slashes except for root
if ($path !== '/' && substr($path, -1) === '/') {
    $path = rtrim($path, '/');
}

// Debug the cleaned path
if (Bootstrap::getAppInfo()['environment'] === 'development') {
    error_log("SCRIPT_DIR: " . $scriptDir);
    error_log("CLEANED_PATH: " . $path);
    error_log("==================");
}

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
            
        case preg_match('#^/api/tasks/(\\d+)$#', $path, $matches) && $requestMethod === 'GET':
            AuthMiddleware::handle();
            handleGetTask($matches[1]);
            break;
            
        case preg_match('#^/api/tasks/(\\d+)$#', $path, $matches) && $requestMethod === 'PUT':
            AuthMiddleware::handle();
            handleUpdateTask($matches[1]);
            break;
            
        case preg_match('#^/api/tasks/(\\d+)$#', $path, $matches) && $requestMethod === 'DELETE':
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
            
        // Root API endpoint - show available routes
        case $path === '/api' && $requestMethod === 'GET':
            handleApiInfo();
            break;
            
        default:
            // CORRECTION: Passer le message comme string et les détails comme $errors
            ResponseService::error(
                'Endpoint not found', 
                404, 
                [
                    'path' => $path,
                    'method' => $requestMethod,
                    'debug_info' => [
                        'original_uri' => $requestUri,
                        'script_name' => $scriptName,
                        'script_dir' => $scriptDir
                    ],
                    'available_endpoints' => [
                        'GET /api/health',
                        'GET /api/info', 
                        'GET /api (liste des endpoints)',
                        'POST /api/auth/login',
                        'POST /api/auth/register',
                        'POST /api/auth/logout',
                        'GET /api/tasks',
                        'POST /api/tasks',
                        'POST /api/debug'
                    ]
                ]
            );
    }
    
} catch (\Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    error_log('API Error Trace: ' . $e->getTraceAsString());
    
    if (Bootstrap::getAppInfo()['environment'] === 'development') {
        // CORRECTION: Message simple et détails dans $errors
        ResponseService::error(
            'Internal server error',
            500,
            [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => array_slice($e->getTrace(), 0, 5) // Limit trace for readability
            ]
        );
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
        'version' => Bootstrap::getAppInfo()['version'],
        'environment' => Bootstrap::getAppInfo()['environment'],
        'debug_info' => [
            'request_uri' => $_SERVER['REQUEST_URI'],
            'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'not set',
            'server_name' => $_SERVER['SERVER_NAME'] ?? 'not set',
            'server_port' => $_SERVER['SERVER_PORT'] ?? 'not set'
        ]
    ]);
}

function handleApiInfo(): void
{
    ResponseService::success([
        'name' => 'Task Manager Pro API',
        'version' => Bootstrap::getAppInfo()['version'],
        'message' => 'Bienvenue sur l\'API Task Manager Pro',
        'endpoints' => [
            'health' => 'GET /api/health - Vérification de l\'état de l\'API',
            'info' => 'GET /api/info - Informations sur l\'application',
            'auth' => [
                'login' => 'POST /api/auth/login - Connexion (email ou username)',
                'register' => 'POST /api/auth/register - Inscription', 
                'logout' => 'POST /api/auth/logout - Déconnexion',
                'refresh' => 'POST /api/auth/refresh - Renouvellement de token'
            ],
            'tasks' => [
                'list' => 'GET /api/tasks - Liste des tâches',
                'create' => 'POST /api/tasks - Créer une tâche',
                'get' => 'GET /api/tasks/{id} - Détails d\'une tâche',
                'update' => 'PUT /api/tasks/{id} - Modifier une tâche',
                'delete' => 'DELETE /api/tasks/{id} - Supprimer une tâche'
            ],
            'users' => [
                'profile' => 'GET /api/users/profile - Profil utilisateur',
                'update_profile' => 'PUT /api/users/profile - Modifier le profil'
            ],
            'debug' => 'POST /api/debug - Informations de débogage'
        ],
        'authentication' => [
            'type' => 'JWT Bearer Token',
            'header' => 'Authorization: Bearer <token>',
            'test_credentials' => [
                'login' => 'admin ou admin@taskmanager.local',
                'password' => 'Admin123!'
            ]
        ]
    ]);
}

function handleDebug(): void
{
    // Get raw input
    $rawInput = file_get_contents('php://input');
    $headers = getallheaders();
    
    ResponseService::success([
        'request' => [
            'method' => $_SERVER['REQUEST_METHOD'],
            'uri' => $_SERVER['REQUEST_URI'],
            'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'not set',
            'path_info' => $_SERVER['PATH_INFO'] ?? 'not set',
            'query_string' => $_SERVER['QUERY_STRING'] ?? 'not set'
        ],
        'content' => [
            'type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
            'raw_input' => $rawInput,
            'json_decoded' => json_decode($rawInput, true),
            'json_error' => json_last_error_msg(),
            'post_data' => $_POST
        ],
        'headers' => $headers,
        'server_vars' => [
            'DOCUMENT_ROOT' => $_SERVER['DOCUMENT_ROOT'] ?? 'not set',
            'SCRIPT_FILENAME' => $_SERVER['SCRIPT_FILENAME'] ?? 'not set',
            'SERVER_NAME' => $_SERVER['SERVER_NAME'] ?? 'not set',
            'SERVER_PORT' => $_SERVER['SERVER_PORT'] ?? 'not set',
            'REQUEST_SCHEME' => $_SERVER['REQUEST_SCHEME'] ?? 'not set'
        ]
    ]);
}

function handleLogin(): void
{
    try {
        // CORRECTION: Support flexible login (email OR username)
        $rules = [
            'login' => 'required',  // Can be email or username
            'password' => 'required'
        ];
        
        $data = ValidationMiddleware::validate($rules);
        
        $userModel = new User();
        
        // Use flexible authentication method
        $user = $userModel->authenticateByLogin($data['login'], $data['password']);
        
        if (!$user) {
            ResponseService::error('Email/nom d\'utilisateur ou mot de passe incorrect', 401);
        }
        
        $token = JWTManager::generateToken($user);
        
        // Log successful login
        if (class_exists('\\TaskManager\\Services\\LoggerService')) {
            \TaskManager\Services\LoggerService::log(
                'info',
                'User login successful',
                [
                    'user_id' => $user['id'],
                    'username' => $user['username'],
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]
            );
        }
        
        ResponseService::success([
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'avatar' => $user['avatar'],
                'role' => $user['role'],
                'theme' => $user['theme'],
                'language' => $user['language'],
                'timezone' => $user['timezone']
            ],
            'token' => $token,
            'expires_in' => 3600
        ], 'Connexion réussie');
        
    } catch (\Exception $e) {
        error_log('Login error: ' . $e->getMessage());
        ResponseService::error('Login error: ' . $e->getMessage(), 500);
    }
}

function handleRegister(): void
{
    try {
        $rules = [
            'email' => 'required|email',
            'password' => 'required|min:6',
            'username' => 'min:3|max:50',
            'first_name' => 'max:50',
            'last_name' => 'max:50',
            'language' => 'in:fr,en',
            'timezone' => 'max:50'
        ];
        
        $data = ValidationMiddleware::validate($rules);
        
        $userModel = new User();
        
        // Additional validation
        $errors = $userModel->validateUserData($data);
        if (!empty($errors)) {
            ResponseService::validation($errors, 'Erreur de validation');
        }
        
        // Check if email already exists
        if ($userModel->emailExists($data['email'])) {
            ResponseService::error('Cette adresse email est déjà utilisée', 409);
        }
        
        // Check if username already exists (if provided)
        if (!empty($data['username']) && $userModel->usernameExists($data['username'])) {
            ResponseService::error('Ce nom d\'utilisateur est déjà utilisé', 409);
        }
        
        $result = $userModel->createUser($data);
        
        if (!$result['success']) {
            ResponseService::error($result['message'], 400);
        }
        
        $user = $result['data'];
        $token = JWTManager::generateToken($user);
        
        // Log successful registration
        if (class_exists('\\TaskManager\\Services\\LoggerService')) {
            \TaskManager\Services\LoggerService::log(
                'info',
                'User registration successful',
                [
                    'user_id' => $user['id'],
                    'username' => $user['username'],
                    'email' => $user['email'],
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]
            );
        }
        
        ResponseService::success([
            'user' => [
                'id' => $user['id'],
                'username' => $user['username'],
                'email' => $user['email'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'avatar' => $user['avatar'],
                'role' => $user['role'],
                'theme' => $user['theme'],
                'language' => $user['language'],
                'timezone' => $user['timezone']
            ],
            'token' => $token,
            'expires_in' => 3600
        ], 'Compte créé avec succès', 201);
        
    } catch (\Exception $e) {
        error_log('Register error: ' . $e->getMessage());
        ResponseService::error('Register error: ' . $e->getMessage(), 500);
    }
}

function handleLogout(): void
{
    try {
        // Log successful logout
        if (class_exists('\\TaskManager\\Services\\LoggerService')) {
            \TaskManager\Services\LoggerService::log(
                'info',
                'User logout successful',
                [
                    'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
                ]
            );
        }
        
        // For JWT, logout is handled client-side by removing the token
        // Here we could implement token blacklisting if needed
        ResponseService::success(null, 'Déconnexion réussie');
        
    } catch (\Exception $e) {
        error_log('Logout error: ' . $e->getMessage());
        ResponseService::error('Erreur lors de la déconnexion', 500);
    }
}

function handleTokenRefresh(): void
{
    $authHeader = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    
    if (!preg_match('/Bearer\\s+(.*)$/i', $authHeader, $matches)) {
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
        ResponseService::validation($errors, 'Erreur de validation');
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
