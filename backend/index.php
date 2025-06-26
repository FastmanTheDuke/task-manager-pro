<?php
/**
 * Task Manager Pro - Main API Entry Point
 * * This file serves as the main entry point for all API requests.
 * It handles routing, authentication, and error handling.
 */

require_once __DIR__ . '/Bootstrap.php';

use TaskManager\Bootstrap;
use TaskManager\Services\ResponseService;
use TaskManager\Services\ValidationService;
use TaskManager\Middleware\AuthMiddleware;
use TaskManager\Middleware\CorsMiddleware;
use TaskManager\Middleware\ValidationMiddleware;
use TaskManager\Controllers\DiagnosticController;
use TaskManager\Models\Task;
use TaskManager\Models\User;
use TaskManager\Models\Project;
use TaskManager\Models\Tag;
use TaskManager\Config\JWTManager;
use TaskManager\Database\Connection;

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

// CORRECTION: Intelligent path cleaning for subdirectory installations
// Remove everything before '/api' to get the clean API path
if (strpos($path, '/api') !== false) {
    $apiPos = strpos($path, '/api');
    $path = substr($path, $apiPos);
} else {
    // If no '/api' found, maybe it's a root request to the API
    // Try to extract the last part after the base directory
    $pathParts = explode('/', trim($path, '/'));
    
    // Look for common patterns
    if (in_array('backend', $pathParts)) {
        $backendIndex = array_search('backend', $pathParts);
        $remainingParts = array_slice($pathParts, $backendIndex + 1);
        $path = '/' . implode('/', $remainingParts);
    }
}

// Ensure path starts with /
if (substr($path, 0, 1) !== '/') {
    $path = '/' . $path;
}

// Remove trailing slashes except for root
if ($path !== '/' && substr($path, -1) === '/') {
    $path = rtrim($path, '/');
}

// If we still don't have a clean API path, try to default to /api
if ($path === '/' || $path === '') {
    $path = '/api';
}

// Debug the cleaned path
if (Bootstrap::getAppInfo()['environment'] === 'development') {
    error_log("ORIGINAL_PATH: " . parse_url($requestUri, PHP_URL_PATH));
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
            
        // Enhanced diagnostic endpoints using the new controller
        case $path === '/api/diagnostic' && $requestMethod === 'GET':
        case $path === '/api/diagnostic/system' && $requestMethod === 'GET':
            DiagnosticController::systemCheck();
            break;
            
        case $path === '/api/diagnostic/database' && $requestMethod === 'GET':
            DiagnosticController::databaseCheck();
            break;
            
        case $path === '/api/diagnostic/auth' && $requestMethod === 'GET':
            DiagnosticController::authCheck();
            break;
            
        case $path === '/api/diagnostic/api' && $requestMethod === 'GET':
            DiagnosticController::apiCheck();
            break;
            
        // Debug endpoint
        case $path === '/api/debug' && $requestMethod === 'POST':
        case $path === '/api/debug' && $requestMethod === 'GET':
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
            
        // Dashboard route (requires authentication)
        case $path === '/api/dashboard' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            handleDashboard();
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
            
        // Project routes (require authentication)
        case $path === '/api/projects' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            handleGetProjects();
            break;
            
        case $path === '/api/projects' && $requestMethod === 'POST':
            AuthMiddleware::handle();
            handleCreateProject();
            break;
            
        case preg_match('#^/api/projects/(\\d+)$#', $path, $matches) && $requestMethod === 'GET':
            AuthMiddleware::handle();
            handleGetProject($matches[1]);
            break;
            
        case preg_match('#^/api/projects/(\\d+)$#', $path, $matches) && $requestMethod === 'DELETE':
            AuthMiddleware::handle();
            handleDeleteProject($matches[1]);
            break;
            
        // NOUVELLES ROUTES : Gestion des membres de projets
        case preg_match('#^/api/projects/(\\d+)/members$#', $path, $matches) && $requestMethod === 'GET':
            AuthMiddleware::handle();
            handleGetProjectMembers($matches[1]);
            break;
            
        case preg_match('#^/api/projects/(\\d+)/members$#', $path, $matches) && $requestMethod === 'POST':
            AuthMiddleware::handle();
            handleAddProjectMember($matches[1]);
            break;
            
        case preg_match('#^/api/projects/(\\d+)/members/(\\d+)$#', $path, $matches) && $requestMethod === 'DELETE':
            AuthMiddleware::handle();
            handleRemoveProjectMember($matches[1], $matches[2]);
            break;
            
        // Tag routes (require authentication)
        case $path === '/api/tags' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            handleGetTags();
            break;
            
        case $path === '/api/tags' && $requestMethod === 'POST':
            AuthMiddleware::handle();
            handleCreateTag();
            break;
            
        case preg_match('#^/api/tags/(\\d+)$#', $path, $matches) && $requestMethod === 'GET':
            AuthMiddleware::handle();
            handleGetTag($matches[1]);
            break;
            
        case preg_match('#^/api/tags/(\\d+)$#', $path, $matches) && $requestMethod === 'PUT':
            AuthMiddleware::handle();
            handleUpdateTag($matches[1]);
            break;
            
        case preg_match('#^/api/tags/(\\d+)$#', $path, $matches) && $requestMethod === 'DELETE':
            AuthMiddleware::handle();
            handleDeleteTag($matches[1]);
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
        // --- Admin User Management Routes ---
        case $path === '/api/users' && $requestMethod === 'GET':
            AuthMiddleware::handle();
            // Assurez-vous que le fichier /api/users/index.php gère la logique
            require_once __DIR__ . '/api/users/index.php';
            break;
        
        case $path === '/api/users/update-role' && $requestMethod === 'POST':
            AuthMiddleware::handle();
            // Assurez-vous que le fichier /api/users/update_role.php gère la logique
            require_once __DIR__ . '/api/users/update_role.php';
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
            // Enhanced error with routing debug info
            ResponseService::error(
                'Endpoint not found', 
                404, 
                [
                    'requested_path' => $path,
                    'method' => $requestMethod,
                    'routing_debug' => [
                        'original_uri' => $requestUri,
                        'original_path' => parse_url($requestUri, PHP_URL_PATH),
                        'cleaned_path' => $path,
                        'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'not set',
                        'path_analysis' => [
                            'contains_api' => strpos(parse_url($requestUri, PHP_URL_PATH), '/api') !== false,
                            'api_position' => strpos(parse_url($requestUri, PHP_URL_PATH), '/api'),
                            'path_parts' => explode('/', trim(parse_url($requestUri, PHP_URL_PATH), '/'))
                        ]
                    ],
                    'available_endpoints' => [
                        'GET /api/health - API status',
                        'GET /api/diagnostic - Complete system diagnostic',
                        'GET /api/diagnostic/system - System & PHP diagnostic',
                        'GET /api/diagnostic/database - Database diagnostic',
                        'GET /api/diagnostic/auth - Authentication diagnostic',
                        'GET /api/diagnostic/api - API status',
                        'GET /api/info - App information', 
                        'GET /api - List all endpoints',
                        'POST /api/auth/login - Login',
                        'POST /api/auth/register - Register',
                        'POST /api/auth/logout - Logout',
                        'GET|POST /api/debug - Debug info',
                        'GET /api/dashboard - Dashboard data',
                        'GET /api/tasks - List tasks',
                        'POST /api/tasks - Create task',
                        'GET /api/projects - List projects',
                        'POST /api/projects - Create project',
                        'GET /api/projects/{id}/members - List project members',
                        'POST /api/projects/{id}/members - Add project member',
                        'DELETE /api/projects/{id}/members/{userId} - Remove project member'
                    ],
                    'tip' => 'Make sure your requests are going to the correct backend endpoint'
                ]
            );
    }
    
} catch (\Exception $e) {
    error_log('API Error: ' . $e->getMessage());
    error_log('API Error Trace: ' . $e->getTraceAsString());
    
    if (Bootstrap::getAppInfo()['environment'] === 'development') {
        ResponseService::error(
            'Internal server error',
            500,
            [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => array_slice($e->getTrace(), 0, 5)
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
        'message' => 'API is running perfectly',
        'timestamp' => date('Y-m-d H:i:s'),
        'version' => Bootstrap::getAppInfo()['version'],
        'environment' => Bootstrap::getAppInfo()['environment'],
        'routing_info' => [
            'request_uri' => $_SERVER['REQUEST_URI'],
            'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'not set',
            'server_name' => $_SERVER['SERVER_NAME'] ?? 'not set',
            'server_port' => $_SERVER['SERVER_PORT'] ?? 'not set',
            'method' => $_SERVER['REQUEST_METHOD']
        ]
    ], 'API Health Check - All systems operational');
}

function handleApiInfo(): void
{
    ResponseService::success([
        'name' => 'Task Manager Pro API',
        'version' => Bootstrap::getAppInfo()['version'],
        'message' => 'Bienvenue sur l\'API Task Manager Pro',
        'status' => 'operational',
        'endpoints' => [
            'health' => 'GET /api/health - Vérification de l\'état de l\'API',
            'diagnostic' => [
                'system' => 'GET /api/diagnostic/system - Diagnostic système complet',
                'database' => 'GET /api/diagnostic/database - Diagnostic base de données',
                'auth' => 'GET /api/diagnostic/auth - Diagnostic authentification',
                'api' => 'GET /api/diagnostic/api - Statut de l\'API'
            ],
            'info' => 'GET /api/info - Informations sur l\'application',
            'auth' => [
                'login' => 'POST /api/auth/login - Connexion (email ou username)',
                'register' => 'POST /api/auth/register - Inscription', 
                'logout' => 'POST /api/auth/logout - Déconnexion',
                'refresh' => 'POST /api/auth/refresh - Renouvellement de token'
            ],
            'dashboard' => 'GET /api/dashboard - Données du tableau de bord',
            'tasks' => [
                'list' => 'GET /api/tasks - Liste des tâches (authentification requise)',
                'create' => 'POST /api/tasks - Créer une tâche (authentification requise)',
                'get' => 'GET /api/tasks/{id} - Détails d\'une tâche',
                'update' => 'PUT /api/tasks/{id} - Modifier une tâche',
                'delete' => 'DELETE /api/tasks/{id} - Supprimer une tâche'
            ],
            'projects' => [
                'list' => 'GET /api/projects - Liste des projets',
                'create' => 'POST /api/projects - Créer un projet',
                'get' => 'GET /api/projects/{id} - Détails d\'un projet',
                'delete' => 'DELETE /api/projects/{id} - Supprimer un projet',
                'members' => [
                    'list' => 'GET /api/projects/{id}/members - Lister les membres du projet',
                    'add' => 'POST /api/projects/{id}/members - Ajouter un membre au projet',
                    'remove' => 'DELETE /api/projects/{id}/members/{userId} - Retirer un membre du projet'
                ]
            ],
            'users' => [
                'profile' => 'GET /api/users/profile - Profil utilisateur',
                'update_profile' => 'PUT /api/users/profile - Modifier le profil'
            ],
            'debug' => 'GET|POST /api/debug - Informations de débogage'
        ],
        'authentication' => [
            'type' => 'JWT Bearer Token',
            'header' => 'Authorization: Bearer <token>',
            'test_credentials' => [
                'login' => 'admin ou admin@taskmanager.local',
                'password' => 'Admin123!'
            ]
        ],
        'current_request' => [
            'uri' => $_SERVER['REQUEST_URI'],
            'method' => $_SERVER['REQUEST_METHOD'],
            'timestamp' => date('c')
        ]
    ], 'API Documentation and Status');
}

function handleDebug(): void
{
    // Get raw input
    $rawInput = file_get_contents('php://input');
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    
    ResponseService::success([
        'message' => 'Debug endpoint working correctly',
        'request' => [
            'method' => $_SERVER['REQUEST_METHOD'],
            'uri' => $_SERVER['REQUEST_URI'],
            'script_name' => $_SERVER['SCRIPT_NAME'] ?? 'not set',
            'path_info' => $_SERVER['PATH_INFO'] ?? 'not set',
            'query_string' => $_SERVER['QUERY_STRING'] ?? 'not set'
        ],
        'content' => [
            'type' => $_SERVER['CONTENT_TYPE'] ?? 'not set',
            'length' => strlen($rawInput),
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
            'REQUEST_SCHEME' => $_SERVER['REQUEST_SCHEME'] ?? 'not set',
            'HTTP_HOST' => $_SERVER['HTTP_HOST'] ?? 'not set'
        ],
        'routing_analysis' => [
            'original_path' => parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH),
            'api_position' => strpos(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/api'),
            'path_segments' => explode('/', trim(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH), '/'))
        ]
    ], 'Debug information collected successfully');
}

function handleLogin(): void
{
    try {
        // Support flexible login (email OR username)
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

function handleDashboard(): void
{
    try {
        $userId = AuthMiddleware::getCurrentUserId();
        
        // Données de base pour le dashboard
        $dashboardData = [
            'stats' => [
                'totalTasks' => 0,
                'completedTasks' => 0,
                'pendingTasks' => 0,
                'overdueTasks' => 0,
                'totalProjects' => 0,
                'activeProjects' => 0,
                'totalTimeTracked' => 0,
                'tasksCompletedThisWeek' => 0
            ],
            'recentTasks' => [],
            'recentProjects' => [],
            'upcomingDeadlines' => [],
            'productivity' => [
                'labels' => [],
                'completedTasks' => [],
                'timeSpent' => [],
                'lastWeekTasks' => 0
            ],
            'timeTracking' => []
        ];
        
        // Récupérer les vraies statistiques des tâches
        try {
            $taskModel = new Task();
            $taskStats = $taskModel->getStatistics($userId);
            
            // Tâches récentes
            $recentTasks = $taskModel->getUserTasks($userId, [], [
                'limit' => 5,
                'order_by' => 'created_at',
                'order_dir' => 'DESC'
            ]);
            
            // Échéances prochaines (tâches avec due_date dans les 7 prochains jours)
            $upcomingDeadlines = $taskModel->getUserTasks($userId, [
                'due_date_from' => date('Y-m-d'),
                'due_date_to' => date('Y-m-d', strtotime('+7 days'))
            ], [
                'limit' => 5,
                'order_by' => 'due_date',
                'order_dir' => 'ASC'
            ]);
            
            // Tâches terminées cette semaine
            $weekStart = date('Y-m-d', strtotime('monday this week'));
            $tasksThisWeek = $taskModel->getUserTasks($userId, [
                'status' => 'completed',
                'due_date_from' => $weekStart,
                'due_date_to' => date('Y-m-d')
            ]);
            
            $dashboardData['recentTasks'] = is_array($recentTasks) ? $recentTasks : [];
            $dashboardData['upcomingDeadlines'] = is_array($upcomingDeadlines) ? $upcomingDeadlines : [];
            
        } catch (\Exception $e) {
            error_log('Dashboard task stats error: ' . $e->getMessage());
            $taskStats = [];
            $tasksThisWeek = [];
        }
        
        // Récupérer les statistiques des projets
        try {
            $projectModel = new Project();
            $projectStats = $projectModel->getProjectStats($userId);
            
            // Projets récents
            $recentProjects = $projectModel->getRecentProjects($userId, 5);
            
            $dashboardData['recentProjects'] = is_array($recentProjects) ? $recentProjects : [];
            
        } catch (\Exception $e) {
            error_log('Dashboard project stats error: ' . $e->getMessage());
            $projectStats = [
                'total' => 0,
                'active' => 0,
                'completed' => 0,
                'overdue' => 0
            ];
        }
        
        // Mettre à jour les statistiques avec les vraies données
        $dashboardData['stats'] = [
            'totalTasks' => (int)($taskStats['total_tasks'] ?? 0),
            'completedTasks' => (int)($taskStats['completed_tasks'] ?? 0),
            'pendingTasks' => (int)(($taskStats['pending_tasks'] ?? 0) + ($taskStats['in_progress_tasks'] ?? 0)),
            'overdueTasks' => (int)($taskStats['overdue_tasks'] ?? 0),
            'totalProjects' => (int)($projectStats['total'] ?? 0),
            'activeProjects' => (int)($projectStats['active'] ?? 0),
            'totalTimeTracked' => 0, // À implémenter avec TimeTracking
            'tasksCompletedThisWeek' => is_array($tasksThisWeek) ? count($tasksThisWeek) : 0
        ];
        
        // Données de productivité pour les graphiques
        $dashboardData['productivity'] = [
            'labels' => ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'],
            'completedTasks' => [2, 5, 3, 8, 4, 1, 0], // Données d'exemple - à implémenter
            'timeSpent' => [4, 6, 5, 8, 7, 2, 0], // Données d'exemple - à implémenter
            'lastWeekTasks' => is_array($tasksThisWeek) ? count($tasksThisWeek) : 0
        ];
        
        ResponseService::success($dashboardData, 'Dashboard data retrieved successfully');
        
    } catch (\Exception $e) {
        error_log('Dashboard error: ' . $e->getMessage());
        ResponseService::error('Erreur lors du chargement du dashboard: ' . $e->getMessage(), 500);
    }
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

function handleGetProjects(): void
{
    $userId = AuthMiddleware::getCurrentUserId();
    $projectModel = new Project();
    
    $filters = $_GET;
    $page = (int)($_GET['page'] ?? 1);
    $limit = min((int)($_GET['limit'] ?? 10), 100);
    
    $result = $projectModel->getProjectsForUser($userId, $filters, $page, $limit);
    
    if (!$result['success']) {
        ResponseService::error($result['message'] ?? 'Erreur lors de la récupération des projets', 500);
    }
    
    ResponseService::success($result['data'], 'Projects retrieved successfully');
}

function handleCreateProject(): void
{
    $rules = [
        'name' => 'required|max:200',
        'description' => 'max:1000',
        'status' => 'in:planning,active,completed,on_hold,cancelled',
        'priority' => 'in:low,medium,high,urgent',
        'due_date' => 'date',
        'color' => 'max:7',
        'is_public' => 'boolean'
    ];
    
    $data = ValidationMiddleware::validate($rules);
    $data['created_by'] = AuthMiddleware::getCurrentUserId();
    
    $projectModel = new Project();
    $result = $projectModel->createProject($data, $data['created_by']);
    
    if (!$result['success']) {
        ResponseService::error($result['message'] ?? 'Erreur lors de la création', 400);
    }
    
    ResponseService::success($result['data'], 'Projet créé avec succès', 201);
}

function handleGetProject(int $projectId): void
{
    $userId = AuthMiddleware::getCurrentUserId();
    $projectModel = new Project();
    
    $result = $projectModel->getProjectById($projectId, $userId);
    
    if (!$result['success']) {
        ResponseService::error($result['message'] ?? 'Projet non trouvé', 404);
    }
    
    ResponseService::success($result['data']);
}

function handleDeleteProject(int $projectId): void
{
    $projectModel = new Project();
    
    // Vérifier si le projet existe
    $project = $projectModel->findById($projectId);
    if (!$project) {
        ResponseService::error('Projet non trouvé', 404);
    }
    
    // Récupérer l'ID de l'utilisateur et vérifier les permissions
    $userId = AuthMiddleware::getCurrentUserId();
    
    // La méthode deleteProject dans le modèle vérifie déjà les permissions
    $result = $projectModel->deleteProject($projectId, $userId);
    
    if (!$result['success']) {
        // Le message d'erreur inclut la raison (ex: permission refusée)
        ResponseService::error($result['message'] ?? 'Erreur lors de la suppression', 403);
    }
    
    ResponseService::success(null, 'Projet supprimé avec succès');
}

// NOUVELLES FONCTIONS : Gestion des membres de projets

function handleGetProjectMembers(int $projectId): void
{
    $userId = AuthMiddleware::getCurrentUserId();
    $projectModel = new Project();
    
    // Vérifier que l'utilisateur a accès au projet
    $project = $projectModel->getProjectById($projectId, $userId);
    if (!$project['success']) {
        ResponseService::error($project['message'] ?? 'Projet non trouvé ou accès non autorisé', 404);
    }
    
    $members = $projectModel->getProjectMembers($projectId);
    
    ResponseService::success([
        'project_id' => $projectId,
        'members' => $members
    ], 'Membres du projet récupérés avec succès');
}

function handleAddProjectMember(int $projectId): void
{
    $rules = [
        'user_id' => 'required|integer',
        'role' => 'in:viewer,member,admin'
    ];
    
    $data = ValidationMiddleware::validate($rules);
    $currentUserId = AuthMiddleware::getCurrentUserId();
    
    $projectModel = new Project();
    $result = $projectModel->addMemberToProject(
        $projectId, 
        $data['user_id'], 
        $data['role'] ?? 'member', 
        $currentUserId
    );
    
    if (!$result['success']) {
        ResponseService::error($result['message'], 400);
    }
    
    ResponseService::success($result['data'], $result['message'], 201);
}

function handleRemoveProjectMember(int $projectId, int $memberUserId): void
{
    $currentUserId = AuthMiddleware::getCurrentUserId();
    $projectModel = new Project();
    
    $result = $projectModel->removeMemberFromProject($projectId, $memberUserId, $currentUserId);
    
    if (!$result['success']) {
        ResponseService::error($result['message'], 400);
    }
    
    ResponseService::success(null, $result['message']);
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

function handleGetTags(): void
{
    $user = AuthMiddleware::getCurrentUser();
    $tag = new Tag();

    $search = $_GET['search'] ?? '';
    $sortBy = $_GET['sort_by'] ?? 'name';
    $sortOrder = $_GET['sort_order'] ?? 'asc';
    $page = (int)($_GET['page'] ?? 1);
    $limit = min((int)($_GET['limit'] ?? 50), 100);

    $result = $tag->getTagsForUser($user->id, $search, $sortBy, $sortOrder, $page, $limit);

    if ($result['success']) {
        ResponseService::success($result['data']['tags']);
    } else {
        ResponseService::error($result['message'], 400);
    }
}

function handleGetTag(int $tagId): void
{
    $user = AuthMiddleware::getCurrentUser();
    $tag = new Tag();
    $result = $tag->getTagById($tagId, $user->id);

    if ($result['success']) {
        ResponseService::success($result['data']);
    } else {
        ResponseService::error($result['message'], 404);
    }
}

function handleCreateTag(): void
{
    $user = AuthMiddleware::getCurrentUser();
    $tag = new Tag();

    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        ResponseService::error('Invalid JSON input', 400);
    }

    $tagData = [
        'name' => $input['name'] ?? '',
        'description' => $input['description'] ?? null,
        'color' => $input['color'] ?? '#3B82F6',
        'created_by' => $user->id
    ];
    
    // Simple validation
    if (empty($tagData['name'])) {
        ResponseService::error('Tag name is required', 422);
    }

    if ($tag->tagExistsForUser($tagData['name'], $user->id)) {
        ResponseService::error('A tag with this name already exists', 409);
    }

    $result = $tag->createTag($tagData);

    if ($result['success']) {
        ResponseService::success($result['data'], 'Tag created successfully', 201);
    } else {
        ResponseService::error($result['message'], 400);
    }
}

function handleUpdateTag(int $tagId): void
{
    $user = AuthMiddleware::getCurrentUser();
    $tag = new Tag();
    $input = json_decode(file_get_contents('php://input'), true);

    if (!$input) {
        ResponseService::error('Invalid JSON input', 400);
    }

    $existingTag = $tag->getTagById($tagId, $user->id);
    if (!$existingTag['success']) {
        ResponseService::error('Tag not found or access denied', 404);
    }
    
    if (isset($input['name']) && $tag->tagExistsForUser($input['name'], $user->id, $tagId)) {
        ResponseService::error('A tag with this name already exists', 409);
    }

    $result = $tag->updateTag($tagId, $input, $user->id);

    if ($result['success']) {
        ResponseService::success($result['data']);
    } else {
        ResponseService::error($result['message'], 400);
    }
}

function handleDeleteTag(int $tagId): void
{
    $user = AuthMiddleware::getCurrentUser();
    $tag = new Tag();

    $existingTag = $tag->getTagById($tagId, $user->id);
    if (!$existingTag['success']) {
        ResponseService::error('Tag not found or access denied', 404);
    }

    $result = $tag->deleteTag($tagId, $user->id);

    if ($result['success']) {
        ResponseService::success(['message' => 'Tag deleted successfully']);
    } else {
        ResponseService::error($result['message'], 400);
    }
}
