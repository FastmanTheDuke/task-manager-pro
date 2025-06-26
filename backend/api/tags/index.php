<?php
/**
 * API Endpoints for Tags
 * Handles CRUD operations for task tags
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

use Models\Tag;
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
    
    // Extract tag ID if present
    $tagId = null;
    if (isset($pathParts[2]) && is_numeric($pathParts[2])) {
        $tagId = (int)$pathParts[2];
    }
    
    $tag = new Tag();
    
    switch ($method) {
        case 'GET':
            if ($tagId) {
                handleGetTag($tag, $tagId, $user);
            } else {
                handleGetTags($tag, $user);
            }
            break;
            
        case 'POST':
            handleCreateTag($tag, $user);
            break;
            
        case 'PUT':
            if ($tagId) {
                handleUpdateTag($tag, $tagId, $user);
            } else {
                Response::error('Tag ID required', 400);
            }
            break;
            
        case 'DELETE':
            if ($tagId) {
                handleDeleteTag($tag, $tagId, $user);
            } else {
                Response::error('Tag ID required', 400);
            }
            break;
            
        default:
            Response::error('Method not allowed', 405);
    }
    
} catch (Exception $e) {
    error_log("Tags API Error: " . $e->getMessage());
    Response::error('Internal server error: ' . $e->getMessage(), 500);
}

function handleGetTags($tag, $user) {
    $search = $_GET['search'] ?? '';
    $sortBy = $_GET['sort_by'] ?? 'name';
    $sortOrder = $_GET['sort_order'] ?? 'asc';
    $page = (int)($_GET['page'] ?? 1);
    $limit = min((int)($_GET['limit'] ?? 50), 100);
    
    $result = $tag->getTagsForUser($user['id'], $search, $sortBy, $sortOrder, $page, $limit);
    
    if ($result['success']) {
        Response::success($result['data']);
    } else {
        Response::error($result['message'], 400);
    }
}

function handleGetTag($tag, $tagId, $user) {
    $result = $tag->getTagById($tagId, $user['id']);
    
    if ($result['success']) {
        Response::success($result['data']);
    } else {
        Response::error($result['message'], 404);
    }
}

function handleCreateTag($tag, $user) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        Response::error('Invalid JSON input', 400);
    }
    
    // Validate required fields
    $validator = new Validator();
    $validator->required($input, ['name']);
    $validator->string($input, 'name', 1, 100);
    
    if (!empty($input['description'])) {
        $validator->string($input, 'description', 0, 500);
    }
    
    if (!empty($input['color'])) {
        $validator->string($input, 'color', 6, 7);
        // Validate hex color format
        if (!preg_match('/^#[a-fA-F0-9]{6}$/', $input['color'])) {
            $validator->addError('color', 'Color must be a valid hex color');
        }
    }
    
    if ($validator->hasErrors()) {
        Response::error('Validation failed', 400, $validator->getErrors());
    }
    
    // Check if tag name already exists for this user
    if ($tag->tagExistsForUser($input['name'], $user['id'])) {
        Response::error('A tag with this name already exists', 409);
    }
    
    $tagData = [
        'name' => $input['name'],
        'description' => $input['description'] ?? null,
        'color' => $input['color'] ?? '#3B82F6',
        'created_by' => $user['id']
    ];
    
    $result = $tag->createTag($tagData);
    
    if ($result['success']) {
        Response::success($result['data'], 201);
    } else {
        Response::error($result['message'], 400);
    }
}

function handleUpdateTag($tag, $tagId, $user) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        Response::error('Invalid JSON input', 400);
    }
    
    // Check if user owns this tag
    $existingTag = $tag->getTagById($tagId, $user['id']);
    if (!$existingTag['success']) {
        Response::error('Tag not found or access denied', 404);
    }
    
    if ($existingTag['data']['created_by'] !== $user['id']) {
        Response::error('You can only edit your own tags', 403);
    }
    
    // Validate input
    $validator = new Validator();
    
    if (isset($input['name'])) {
        $validator->string($input, 'name', 1, 100);
        
        // Check if new name conflicts with existing tags (excluding current tag)
        if ($tag->tagExistsForUser($input['name'], $user['id'], $tagId)) {
            Response::error('A tag with this name already exists', 409);
        }
    }
    
    if (isset($input['description'])) {
        $validator->string($input, 'description', 0, 500);
    }
    
    if (isset($input['color']) && !empty($input['color'])) {
        $validator->string($input, 'color', 6, 7);
        if (!preg_match('/^#[a-fA-F0-9]{6}$/', $input['color'])) {
            $validator->addError('color', 'Color must be a valid hex color');
        }
    }
    
    if ($validator->hasErrors()) {
        Response::error('Validation failed', 400, $validator->getErrors());
    }
    
    $result = $tag->updateTag($tagId, $input, $user['id']);
    
    if ($result['success']) {
        Response::success($result['data']);
    } else {
        Response::error($result['message'], 400);
    }
}

function handleDeleteTag($tag, $tagId, $user) {
    // Check if user owns this tag
    $existingTag = $tag->getTagById($tagId, $user['id']);
    if (!$existingTag['success']) {
        Response::error('Tag not found or access denied', 404);
    }
    
    if ($existingTag['data']['created_by'] !== $user['id']) {
        Response::error('You can only delete your own tags', 403);
    }
    
    $result = $tag->deleteTag($tagId, $user['id']);
    
    if ($result['success']) {
        Response::success(['message' => 'Tag deleted successfully']);
    } else {
        Response::error($result['message'], 400);
    }
}
?>