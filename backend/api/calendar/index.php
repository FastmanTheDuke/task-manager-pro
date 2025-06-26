<?php
/**
 * API Endpoints for Calendar
 * Handles calendar events from tasks, projects and deadlines
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../../../Bootstrap.php';

use Models\Task;
use Models\Project;
use Models\TimeTracking;
use Middleware\AuthMiddleware;
use Utils\Response;

try {
    // Initialize authentication middleware
    $authMiddleware = new AuthMiddleware();
    $user = $authMiddleware->authenticate();
    
    $method = $_SERVER['REQUEST_METHOD'];
    $path = $_SERVER['REQUEST_URI'];
    $pathParts = explode('/', trim(parse_url($path, PHP_URL_PATH), '/'));
    
    $action = $pathParts[2] ?? 'events';
    
    if ($method !== 'GET') {
        Response::error('Method not allowed', 405);
    }
    
    switch ($action) {
        case 'events':
            handleGetCalendarEvents($user);
            break;
        default:
            Response::error('Invalid action', 400);
    }
    
} catch (Exception $e) {
    error_log("Calendar API Error: " . $e->getMessage());
    Response::error('Internal server error: ' . $e->getMessage(), 500);
}

function handleGetCalendarEvents($user) {
    $startDate = $_GET['start_date'] ?? date('Y-m-d', strtotime('-1 month'));
    $endDate = $_GET['end_date'] ?? date('Y-m-d', strtotime('+2 months'));
    $types = isset($_GET['types']) ? explode(',', $_GET['types']) : ['tasks', 'projects', 'deadlines'];
    
    $events = [];
    
    // Get tasks with due dates
    if (in_array('tasks', $types)) {
        $events = array_merge($events, getTaskEvents($user['id'], $startDate, $endDate));
    }
    
    // Get project deadlines
    if (in_array('projects', $types)) {
        $events = array_merge($events, getProjectEvents($user['id'], $startDate, $endDate));
    }
    
    // Get time tracking entries
    if (in_array('time_entries', $types)) {
        $events = array_merge($events, getTimeTrackingEvents($user['id'], $startDate, $endDate));
    }
    
    // Sort events by date
    usort($events, function($a, $b) {
        $dateA = $a['due_date'] ?? $a['start_date'] ?? $a['date'];
        $dateB = $b['due_date'] ?? $b['start_date'] ?? $b['date'];
        return strcmp($dateA, $dateB);
    });
    
    Response::success($events);
}

function getTaskEvents($userId, $startDate, $endDate) {
    try {
        $taskModel = new Task();
        
        // Get tasks with due dates in the specified range
        $db = $taskModel->getDb();
        $sql = "SELECT t.id, t.title, t.description, t.status, t.priority, t.due_date,
                       t.created_at, t.updated_at,
                       p.name as project_name, p.color as project_color,
                       u.username as assigned_to_username
                FROM tasks t
                LEFT JOIN projects p ON t.project_id = p.id
                LEFT JOIN users u ON t.assigned_to = u.id
                LEFT JOIN project_members pm ON p.id = pm.project_id
                WHERE (t.created_by = :user_id OR t.assigned_to = :user_id OR pm.user_id = :user_id OR p.is_public = 1)
                AND t.due_date IS NOT NULL
                AND DATE(t.due_date) BETWEEN :start_date AND :end_date
                AND t.status != 'deleted'
                ORDER BY t.due_date ASC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        
        $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $events = [];
        foreach ($tasks as $task) {
            $events[] = [
                'id' => 'task_' . $task['id'],
                'type' => 'task',
                'title' => $task['title'],
                'description' => $task['description'],
                'due_date' => $task['due_date'],
                'start_date' => $task['due_date'],
                'status' => $task['status'],
                'priority' => $task['priority'],
                'project' => $task['project_name'] ? [
                    'name' => $task['project_name'],
                    'color' => $task['project_color']
                ] : null,
                'assigned_to' => $task['assigned_to_username'],
                'url' => '/tasks/' . $task['id']
            ];
        }
        
        return $events;
        
    } catch (Exception $e) {
        error_log("Error getting task events: " . $e->getMessage());
        return [];
    }
}

function getProjectEvents($userId, $startDate, $endDate) {
    try {
        $projectModel = new Project();
        
        // Get projects with due dates in the specified range
        $db = $projectModel->getDb();
        $sql = "SELECT p.id, p.name, p.description, p.status, p.priority, p.due_date,
                       p.color, p.created_at, p.updated_at,
                       pm.role as user_role,
                       u.username as created_by_username
                FROM projects p
                LEFT JOIN project_members pm ON p.id = pm.project_id
                LEFT JOIN users u ON p.created_by = u.id
                WHERE (pm.user_id = :user_id OR p.is_public = 1)
                AND p.due_date IS NOT NULL
                AND DATE(p.due_date) BETWEEN :start_date AND :end_date
                AND p.is_archived = 0
                ORDER BY p.due_date ASC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        
        $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $events = [];
        foreach ($projects as $project) {
            $events[] = [
                'id' => 'project_' . $project['id'],
                'type' => 'project',
                'title' => $project['name'],
                'description' => $project['description'],
                'due_date' => $project['due_date'],
                'start_date' => $project['due_date'],
                'status' => $project['status'],
                'priority' => $project['priority'],
                'color' => $project['color'],
                'created_by' => $project['created_by_username'],
                'user_role' => $project['user_role'],
                'url' => '/projects/' . $project['id']
            ];
        }
        
        return $events;
        
    } catch (Exception $e) {
        error_log("Error getting project events: " . $e->getMessage());
        return [];
    }
}

function getTimeTrackingEvents($userId, $startDate, $endDate) {
    try {
        $timeTrackingModel = new TimeTracking();
        
        // Get time entries in the specified range
        $db = $timeTrackingModel->getDb();
        $sql = "SELECT te.id, te.description, te.duration, te.start_time, te.end_time,
                       COALESCE(te.date, DATE(te.created_at)) as date,
                       t.title as task_title,
                       p.name as project_name, p.color as project_color
                FROM time_entries te
                LEFT JOIN tasks t ON te.task_id = t.id
                LEFT JOIN projects p ON te.project_id = p.id
                WHERE te.user_id = :user_id
                AND te.status IN ('completed', 'paused')
                AND DATE(COALESCE(te.date, te.created_at)) BETWEEN :start_date AND :end_date
                ORDER BY COALESCE(te.date, te.created_at) ASC";
        
        $stmt = $db->prepare($sql);
        $stmt->execute([
            ':user_id' => $userId,
            ':start_date' => $startDate,
            ':end_date' => $endDate
        ]);
        
        $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        $events = [];
        foreach ($entries as $entry) {
            $events[] = [
                'id' => 'time_entry_' . $entry['id'],
                'type' => 'time_entry',
                'title' => 'Time: ' . ($entry['task_title'] ?? 'Unknown Task'),
                'description' => $entry['description'],
                'date' => $entry['date'],
                'start_date' => $entry['date'],
                'start_time' => $entry['start_time'] ? date('H:i', strtotime($entry['start_time'])) : null,
                'duration' => (int)$entry['duration'],
                'duration_formatted' => formatDuration((int)$entry['duration']),
                'project' => $entry['project_name'] ? [
                    'name' => $entry['project_name'],
                    'color' => $entry['project_color']
                ] : null,
                'task_title' => $entry['task_title']
            ];
        }
        
        return $events;
        
    } catch (Exception $e) {
        error_log("Error getting time tracking events: " . $e->getMessage());
        return [];
    }
}

function formatDuration($seconds) {
    $hours = floor($seconds / 3600);
    $minutes = floor(($seconds % 3600) / 60);
    
    if ($hours > 0) {
        return $hours . 'h ' . $minutes . 'm';
    }
    return $minutes . 'm';
}
?>