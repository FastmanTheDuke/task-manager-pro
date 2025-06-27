<?php
/**
 * API Endpoint for Dashboard
 * Provides comprehensive statistics and data for the dashboard
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

require_once '../../Bootstrap.php';

use Models\Task;
use Models\Project;
use Models\TimeTracking;
use Models\Tag;
use Middleware\AuthMiddleware;
use Utils\Response;

try {
    // Initialize authentication middleware
    $authMiddleware = new AuthMiddleware();
    $user = $authMiddleware->authenticate();
    
    if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
        Response::error('Method not allowed', 405);
    }
    
    // Initialize models
    $taskModel = new Task();
    $projectModel = new Project();
    $timeTrackingModel = new TimeTracking();
    $tagModel = new Tag();
    
    // Get date ranges
    $today = date('Y-m-d');
    $weekStart = date('Y-m-d', strtotime('monday this week'));
    $weekEnd = date('Y-m-d', strtotime('sunday this week'));
    $monthStart = date('Y-m-01');
    $monthEnd = date('Y-m-t');
    
    // Collect all dashboard data
    $dashboardData = [
        'stats' => getDashboardStats($taskModel, $projectModel, $timeTrackingModel, $user['id'], $today, $weekStart, $weekEnd),
        'recentTasks' => getRecentTasks($taskModel, $user['id']),
        'recentProjects' => getRecentProjects($projectModel, $user['id']),
        'upcomingDeadlines' => getUpcomingDeadlines($taskModel, $user['id']),
        'productivity' => getProductivityData($taskModel, $timeTrackingModel, $user['id']),
        'timeTracking' => getTimeTrackingData($timeTrackingModel, $user['id'], $weekStart, $weekEnd)
    ];
    
    Response::success($dashboardData);
    
} catch (Exception $e) {
    error_log("Dashboard API Error: " . $e->getMessage());
    Response::error('Internal server error: ' . $e->getMessage(), 500);
}

function getDashboardStats($taskModel, $projectModel, $timeTrackingModel, $userId, $today, $weekStart, $weekEnd) {
    try {
        // Task statistics
        $taskStats = $taskModel->getTaskStats($userId);
        
        // Project statistics  
        $projectStats = $projectModel->getProjectStats($userId);
        
        // Time tracking statistics
        $totalTimeTracked = $timeTrackingModel->getTotalTimeForUser($userId, $weekStart, $weekEnd);
        
        // Tasks completed this week
        $tasksCompletedThisWeek = $taskModel->getTasksCompletedInPeriod($userId, $weekStart, $weekEnd);
        
        return [
            'totalTasks' => (int)($taskStats['total'] ?? 0),
            'completedTasks' => (int)($taskStats['completed'] ?? 0),
            'pendingTasks' => (int)($taskStats['pending'] ?? 0),
            'overdueTasks' => (int)($taskStats['overdue'] ?? 0),
            'totalProjects' => (int)($projectStats['total'] ?? 0),
            'activeProjects' => (int)($projectStats['active'] ?? 0),
            'totalTimeTracked' => (int)$totalTimeTracked,
            'tasksCompletedThisWeek' => (int)$tasksCompletedThisWeek
        ];
        
    } catch (Exception $e) {
        error_log("Error getting dashboard stats: " . $e->getMessage());
        return [
            'totalTasks' => 0,
            'completedTasks' => 0,
            'pendingTasks' => 0,
            'overdueTasks' => 0,
            'totalProjects' => 0,
            'activeProjects' => 0,
            'totalTimeTracked' => 0,
            'tasksCompletedThisWeek' => 0
        ];
    }
}

function getRecentTasks($taskModel, $userId) {
    try {
        $result = $taskModel->getRecentTasksForUser($userId, 10);
        return $result['success'] ? $result['data'] : [];
    } catch (Exception $e) {
        error_log("Error getting recent tasks: " . $e->getMessage());
        return [];
    }
}

function getRecentProjects($projectModel, $userId) {
    try {
        // CORRECTION: Accéder directement à $result['data'] au lieu de $result['data']['projects']
        // car getProjectsForUser retourne directement les projets dans 'data'
        $result = $projectModel->getProjectsForUser($userId, [], 1, 6);
        return $result['success'] ? $result['data'] : [];
    } catch (Exception $e) {
        error_log("Error getting recent projects: " . $e->getMessage());
        return [];
    }
}

function getUpcomingDeadlines($taskModel, $userId) {
    try {
        $result = $taskModel->getUpcomingDeadlines($userId, 10);
        return $result['success'] ? $result['data'] : [];
    } catch (Exception $e) {
        error_log("Error getting upcoming deadlines: " . $e->getMessage());
        return [];
    }
}

function getProductivityData($taskModel, $timeTrackingModel, $userId) {
    try {
        $currentWeek = date('Y-m-d', strtotime('monday this week'));
        $lastWeek = date('Y-m-d', strtotime('monday last week'));
        $lastWeekEnd = date('Y-m-d', strtotime('sunday last week'));
        
        // Get tasks completed last week for comparison
        $lastWeekTasks = $taskModel->getTasksCompletedInPeriod($userId, $lastWeek, $lastWeekEnd);
        
        // Get daily productivity for the last 7 days
        $dailyStats = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-$i days"));
            $dayTasks = $taskModel->getTasksCompletedInPeriod($userId, $date, $date);
            $dayTime = $timeTrackingModel->getTotalTimeForUser($userId, $date, $date);
            
            $dailyStats[] = [
                'date' => $date,
                'tasks_completed' => (int)$dayTasks,
                'time_spent' => (int)$dayTime
            ];
        }
        
        return [
            'lastWeekTasks' => (int)$lastWeekTasks,
            'daily' => $dailyStats,
            'labels' => array_column($dailyStats, 'date'),
            'completedTasks' => array_column($dailyStats, 'tasks_completed'),
            'timeSpent' => array_column($dailyStats, 'time_spent')
        ];
        
    } catch (Exception $e) {
        error_log("Error getting productivity data: " . $e->getMessage());
        return [
            'lastWeekTasks' => 0,
            'daily' => [],
            'labels' => [],
            'completedTasks' => [],
            'timeSpent' => []
        ];
    }
}

function getTimeTrackingData($timeTrackingModel, $userId, $weekStart, $weekEnd) {
    try {
        $result = $timeTrackingModel->getTimeEntries($userId, [
            'startDate' => $weekStart,
            'endDate' => $weekEnd
        ], 1, 10);
        
        return $result['success'] ? $result['data']['entries'] : [];
        
    } catch (Exception $e) {
        error_log("Error getting time tracking data: " . $e->getMessage());
        return [];
    }
}
?>