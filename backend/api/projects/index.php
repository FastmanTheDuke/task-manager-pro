<?php
/**
 * API Endpoints for Projects
 * Handles CRUD operations for collaborative projects
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../../Bootstrap.php';

use Models\Project;
use Models\User;
use Middleware\AuthMiddleware;
use Utils\Response;
use Utils\Validator;

try {
    // Initialize authentication middleware
    $authMiddleware = new AuthMiddleware();
    $user = $authMiddleware->authenticate();
    
    $method = $_SERVER['REQUEST_METHOD'];
    $path = $_SERVER['REQUEST_URI'];
    $pathParts = explode('/', trim(parse_url($path, PHP_URL_PATH), '/'));
    
    // Extract project ID if present
    $projectId = null;
    $action = null;
    
    // Parse URL: /api/projects/{id}/{action}
    if (isset($pathParts[2]) && is_numeric($pathParts[2])) {
        $projectId = (int)$pathParts[2];
        $action = $pathParts[3] ?? null;
    } elseif (isset($pathParts[2]) && !is_numeric($pathParts[2])) {
        $action = $pathParts[2];
    }
    
    $project = new Project();
    
    switch ($method) {
        case 'GET':
            if ($projectId) {
                handleGetProject($project, $projectId, $user);
            } else {
                handleGetProjects($project, $user);
            }
            break;
            
        case 'POST':
            if ($action === 'favorite' && $projectId) {
                handleToggleFavorite($project, $projectId, $user);
            } elseif ($action === 'archive' && $projectId) {
                handleToggleArchive($project, $projectId, $user);
            } else {
                handleCreateProject($project, $user);
            }
            break;
            
        case 'PUT':
            if ($projectId) {
                handleUpdateProject($project, $projectId, $user);
            } else {
                Response::error('Project ID required', 400);
            }
            break;
            
        case 'DELETE':
            if ($projectId) {
                handleDeleteProject($project, $projectId, $user);
            } else {
                Response::error('Project ID required', 400);
            }
            break;
            
        default:
            Response::error('Method not allowed', 405);
    }
    
} catch (Exception $e) {
    error_log("Projects API Error: " . $e->getMessage());
    Response::error('Internal server error: ' . $e->getMessage(), 500);
}

function handleGetProjects($project, $user) {
    $filters = $_GET;
    $page = (int)($_GET['page'] ?? 1);
    $limit = min((int)($_GET['limit'] ?? 50), 100);
    
    $result = $project->getProjectsForUser($user['id'], $filters, $page, $limit);
    
    if ($result['success']) {
        // Get project statistics
        $stats = $project->getProjectStats($user['id']);
        
        Response::success([
            'projects' => $result['data'],
            'pagination' => $result['pagination'],
            'stats' => $stats
        ]);
    } else {
        Response::error($result['message'], 400);
    }
}

function handleGetProject($project, $projectId, $user) {
    $result = $project->getProjectById($projectId, $user['id']);
    
    if ($result['success']) {
        Response::success($result['data']);
    } else {
        Response::error($result['message'], 404);
    }
}

function handleCreateProject($project, $user) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        Response::error('Invalid JSON input', 400);
    }
    
    // Validate required fields
    $validator = new Validator();
    $validator->required($input, ['name']);
    $validator->string($input, 'name', 1, 255);
    
    if (!empty($input['description'])) {
        $validator->string($input, 'description', 0, 1000);
    }
    
    if (!empty($input['due_date'])) {
        $validator->date($input, 'due_date');
    }
    
    if ($validator->hasErrors()) {
        Response::error('Validation failed', 400, $validator->getErrors());
    }
    
    // Set defaults
    $projectData = [
        'name' => $input['name'],
        'description' => $input['description'] ?? null,
        'status' => $input['status'] ?? 'active',
        'priority' => $input['priority'] ?? 'medium',
        'due_date' => $input['due_date'] ?? null,
        'color' => $input['color'] ?? '#3B82F6',
        'is_public' => (bool)($input['is_public'] ?? false),
        'created_by' => $user['id']
    ];
    
    $result = $project->createProject($projectData, $user['id']);
    
    if ($result['success']) {
        // Add members if provided
        if (!empty($input['members']) && is_array($input['members'])) {
            foreach ($input['members'] as $member) {
                if (isset($member['user_id']) && isset($member['role'])) {
                    $project->addMember($result['data']['id'], $member['user_id'], $member['role']);
                }
            }
        }
        
        Response::success($result['data'], 201);
    } else {
        Response::error($result['message'], 400);
    }
}

function handleUpdateProject($project, $projectId, $user) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        Response::error('Invalid JSON input', 400);
    }
    
    // Check if user has permission to update this project
    $projectData = $project->getProjectById($projectId, $user['id']);
    if (!$projectData['success']) {
        Response::error('Project not found', 404);
    }
    
    // Validate input
    $validator = new Validator();
    
    if (isset($input['name'])) {
        $validator->string($input, 'name', 1, 255);
    }
    
    if (isset($input['description'])) {
        $validator->string($input, 'description', 0, 1000);
    }
    
    if (isset($input['due_date']) && !empty($input['due_date'])) {
        $validator->date($input, 'due_date');
    }
    
    if ($validator->hasErrors()) {
        Response::error('Validation failed', 400, $validator->getErrors());
    }
    
    $result = $project->updateProject($projectId, $input, $user['id']);
    
    if ($result['success']) {
        // Update members if provided
        if (isset($input['members']) && is_array($input['members'])) {
            // Remove existing members and add new ones
            $project->removeAllMembers($projectId);
            foreach ($input['members'] as $member) {
                if (isset($member['user_id']) && isset($member['role'])) {
                    $project->addMember($projectId, $member['user_id'], $member['role']);
                }
            }
        }
        
        Response::success($result['data']);
    } else {
        Response::error($result['message'], 400);
    }
}

function handleDeleteProject($project, $projectId, $user) {
    $result = $project->deleteProject($projectId, $user['id']);
    
    if ($result['success']) {
        Response::success(['message' => 'Project deleted successfully']);
    } else {
        Response::error($result['message'], $result['success'] === false ? 403 : 400);
    }
}

function handleToggleFavorite($project, $projectId, $user) {
    $result = $project->toggleFavorite($projectId, $user['id']);
    
    if ($result['success']) {
        Response::success($result['data']);
    } else {
        Response::error($result['message'], 400);
    }
}

function handleToggleArchive($project, $projectId, $user) {
    $result = $project->toggleArchive($projectId, $user['id']);
    
    if ($result['success']) {
        Response::success($result['data']);
    } else {
        Response::error($result['message'], 400);
    }
}
?>