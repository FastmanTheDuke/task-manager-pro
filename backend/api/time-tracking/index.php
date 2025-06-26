<?php
/**
 * API Endpoints for Time Tracking
 * Handles timer operations and time entries
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

use Models\TimeTracking;
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
    
    // Extract action from URL
    $action = $pathParts[2] ?? null;
    $entryId = null;
    
    if (isset($pathParts[3]) && is_numeric($pathParts[3])) {
        $entryId = (int)$pathParts[3];
    }
    
    $timeTracking = new TimeTracking();
    
    switch ($method) {
        case 'GET':
            switch ($action) {
                case 'active':
                    handleGetActiveTimer($timeTracking, $user);
                    break;
                case 'entries':
                    handleGetTimeEntries($timeTracking, $user);
                    break;
                case 'stats':
                    handleGetTimeStats($timeTracking, $user);
                    break;
                default:
                    handleGetTimeEntries($timeTracking, $user);
            }
            break;
            
        case 'POST':
            switch ($action) {
                case 'start':
                    handleStartTimer($timeTracking, $user);
                    break;
                case 'pause':
                    handlePauseTimer($timeTracking, $user);
                    break;
                case 'stop':
                    handleStopTimer($timeTracking, $user);
                    break;
                case 'manual':
                    handleCreateManualEntry($timeTracking, $user);
                    break;
                default:
                    Response::error('Invalid action', 400);
            }
            break;
            
        case 'PUT':
            if ($entryId) {
                handleUpdateTimeEntry($timeTracking, $entryId, $user);
            } else {
                Response::error('Entry ID required', 400);
            }
            break;
            
        case 'DELETE':
            if ($entryId) {
                handleDeleteTimeEntry($timeTracking, $entryId, $user);
            } else {
                Response::error('Entry ID required', 400);
            }
            break;
            
        default:
            Response::error('Method not allowed', 405);
    }
    
} catch (Exception $e) {
    error_log("Time Tracking API Error: " . $e->getMessage());
    Response::error('Internal server error: ' . $e->getMessage(), 500);
}

function handleGetActiveTimer($timeTracking, $user) {
    $result = $timeTracking->getActiveTimer($user['id']);
    
    if ($result['success']) {
        Response::success($result['data']);
    } else {
        Response::success(null); // No active timer
    }
}

function handleStartTimer($timeTracking, $user) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input || !isset($input['task_id'])) {
        Response::error('Task ID is required', 400);
    }
    
    $result = $timeTracking->startTimer($user['id'], $input['task_id'], $input['description'] ?? null);
    
    if ($result['success']) {
        Response::success($result['data'], 201);
    } else {
        Response::error($result['message'], 400);
    }
}

function handlePauseTimer($timeTracking, $user) {
    $result = $timeTracking->pauseTimer($user['id']);
    
    if ($result['success']) {
        Response::success($result['data']);
    } else {
        Response::error($result['message'], 400);
    }
}

function handleStopTimer($timeTracking, $user) {
    $result = $timeTracking->stopTimer($user['id']);
    
    if ($result['success']) {
        Response::success($result['data']);
    } else {
        Response::error($result['message'], 400);
    }
}

function handleCreateManualEntry($timeTracking, $user) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        Response::error('Invalid JSON input', 400);
    }
    
    // Validate required fields
    $validator = new Validator();
    $validator->required($input, ['task_id', 'duration']);
    
    if (isset($input['task_id'])) {
        $validator->integer($input, 'task_id', 1);
    }
    
    if (isset($input['duration'])) {
        $validator->integer($input, 'duration', 1);
    }
    
    if (isset($input['date'])) {
        $validator->date($input, 'date');
    }
    
    if (!empty($input['description'])) {
        $validator->string($input, 'description', 0, 1000);
    }
    
    if ($validator->hasErrors()) {
        Response::error('Validation failed', 400, $validator->getErrors());
    }
    
    $entryData = [
        'user_id' => $user['id'],
        'task_id' => $input['task_id'],
        'project_id' => $input['project_id'] ?? null,
        'description' => $input['description'] ?? null,
        'duration' => $input['duration'],
        'date' => $input['date'] ?? date('Y-m-d')
    ];
    
    $result = $timeTracking->createManualEntry($entryData);
    
    if ($result['success']) {
        Response::success($result['data'], 201);
    } else {
        Response::error($result['message'], 400);
    }
}

function handleGetTimeEntries($timeTracking, $user) {
    $filters = $_GET;
    $page = (int)($_GET['page'] ?? 1);
    $limit = min((int)($_GET['limit'] ?? 50), 100);
    
    $result = $timeTracking->getTimeEntries($user['id'], $filters, $page, $limit);
    
    if ($result['success']) {
        Response::success($result['data']);
    } else {
        Response::error($result['message'], 400);
    }
}

function handleUpdateTimeEntry($timeTracking, $entryId, $user) {
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!$input) {
        Response::error('Invalid JSON input', 400);
    }
    
    // Validate input
    $validator = new Validator();
    
    if (isset($input['duration'])) {
        $validator->integer($input, 'duration', 1);
    }
    
    if (isset($input['date'])) {
        $validator->date($input, 'date');
    }
    
    if (isset($input['description'])) {
        $validator->string($input, 'description', 0, 1000);
    }
    
    if ($validator->hasErrors()) {
        Response::error('Validation failed', 400, $validator->getErrors());
    }
    
    $result = $timeTracking->updateTimeEntry($entryId, $input, $user['id']);
    
    if ($result['success']) {
        Response::success($result['data']);
    } else {
        Response::error($result['message'], $result['code'] ?? 400);
    }
}

function handleDeleteTimeEntry($timeTracking, $entryId, $user) {
    $result = $timeTracking->deleteTimeEntry($entryId, $user['id']);
    
    if ($result['success']) {
        Response::success(['message' => 'Time entry deleted successfully']);
    } else {
        Response::error($result['message'], $result['code'] ?? 400);
    }
}

function handleGetTimeStats($timeTracking, $user) {
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-30 days'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d');
    $groupBy = $_GET['group_by'] ?? 'day'; // day, week, month
    
    $result = $timeTracking->getTimeStats($user['id'], $startDate, $endDate, $groupBy);
    
    if ($result['success']) {
        Response::success($result['data']);
    } else {
        Response::error($result['message'], 400);
    }
}
?>