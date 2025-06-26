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
use TaskManager\Controllers\DiagnosticController;
use TaskManager\Models\Task;
use TaskManager\Models\User;
use TaskManager\Models\Project;
use TaskManager\Models\Tag; // <-- Add the Tag model
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
        // ... (keep all your existing cases for health, diagnostic, auth, tasks, projects, etc.)

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
        case $path === '/api/debug' && ($requestMethod === 'POST' || $requestMethod === 'GET'):
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

        case preg_match('#^/api/tasks/(\\d+)$#', $path, $matches) && $requestMethod === 'DELETE':
            AuthMiddleware::handle();
            handleDeleteTask($matches[1]);
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

        // ** NEW: Tag routes (require authentication) **
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

        // Application info
        case $path === '/api/info' && $requestMethod === 'GET':
            handleAppInfo();
            break;

        // Root API endpoint - show available routes
        case $path === '/api' && $requestMethod === 'GET':
            handleApiInfo();
            break;

        default:
            // ... (keep your existing default case)
    }

} catch (\Exception $e) {
    // ... (keep your existing catch block)
}

// ... (keep all your existing handler functions: handleHealthCheck, handleLogin, etc.)


// ** ADD THESE NEW HANDLER FUNCTIONS FOR TAGS **

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
