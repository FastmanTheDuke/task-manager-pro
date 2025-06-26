<?php
/**
 * Create Task API Endpoint
 * * Creates a new task with validation and proper error handling
 */

require_once '../../Bootstrap.php';

use TaskManager\Bootstrap;
use TaskManager\Models\Task;
use TaskManager\Utils\Response;
use TaskManager\Middleware\AuthMiddleware;
use TaskManager\Middleware\ValidationMiddleware;

// Initialize application
Bootstrap::init();

// Handle CORS and authentication
AuthMiddleware::handle();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Méthode non autorisée', 405);
}

try {
    // Validation rules
    $rules = [
        'title' => ['required', ['max', 200]],
        'description' => [['max', 1000]],
        'project_id' => ['integer'],
        'assignee_id' => ['integer'],
        'status' => [['in', ['pending', 'in_progress', 'completed', 'archived', 'cancelled']]],
        'priority' => [['in', ['low', 'medium', 'high', 'urgent']]],
        'due_date' => ['date'],
        'start_date' => ['date'],
        'estimated_hours' => ['numeric'],
        'tags' => ['array']
    ];
    
    // Validate request data
    $data = ValidationMiddleware::validate($rules);
    
    // Add current user as creator
    $data['creator_id'] = AuthMiddleware::getCurrentUserId();
    
    // ** FIX: Convert empty date strings to null **
    if (isset($data['due_date']) && $data['due_date'] === '') {
        $data['due_date'] = null;
    }
    if (isset($data['start_date']) && $data['start_date'] === '') {
        $data['start_date'] = null;
    }
    
    // Set default values
    $data['status'] = $data['status'] ?? 'pending';
    $data['priority'] = $data['priority'] ?? 'medium';
    $data['completion_percentage'] = 0;
    
    // Create task instance
    $taskModel = new Task();
    
    // Additional validation
    $errors = $taskModel->validateTaskData($data);
    if (!empty($errors)) {
        Response::error('Erreur de validation', 422, $errors);
    }
    
    // Create the task
    $result = $taskModel->create($data);
    
    if (!$result['success']) {
        Response::error($result['message'] ?? 'Erreur lors de la création de la tâche', 400);
    }
    
    // Get the created task with full details
    $task = $taskModel->getTaskDetails($result['id']);
    
    // Handle tags if provided
    if (!empty($data['tags'])) {
        $tagModel = new \TaskManager\Models\Tag();
        $tagModel->assignTagsToTask($result['id'], $data['tags']);
        
        // Refresh task details to include tags
        $task = $taskModel->getTaskDetails($result['id']);
    }
    
    Response::success($task, 'Tâche créée avec succès', 201);
    
} catch (\Exception $e) {
    error_log('Create task error: ' . $e->getMessage());
    
    if (Bootstrap::getAppInfo()['environment'] === 'development') {
        Response::error('Erreur interne: ' . $e->getMessage(), 500);
    } else {
        Response::error('Erreur interne du serveur', 500);
    }
}
