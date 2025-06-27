<?php
/**
 * API Endpoints for Projects
 * Handles CRUD operations for collaborative projects
 */

require_once __DIR__ . '/../../Bootstrap.php';

use TaskManager\Services\ResponseService;
use TaskManager\Models\Project;
use TaskManager\Middleware\AuthMiddleware;
use TaskManager\Middleware\ValidationMiddleware;
use TaskManager\Middleware\CorsMiddleware;

// Gérer CORS en premier
CorsMiddleware::handle();

// Si c'est une requête OPTIONS (preflight), arrêter ici
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    // Vérifier l'authentification
    if (!AuthMiddleware::handle()) {
        exit; // AuthMiddleware::handle() déjà envoyé la réponse d'erreur
    }
    
    $currentUser = AuthMiddleware::getCurrentUser();
    $currentUserId = AuthMiddleware::getCurrentUserId();
    
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
                handleGetProject($project, $projectId, $currentUserId);
            } else {
                handleGetProjects($project, $currentUserId);
            }
            break;
            
        case 'POST':
            if ($action === 'favorite' && $projectId) {
                handleToggleFavorite($project, $projectId, $currentUserId);
            } elseif ($action === 'archive' && $projectId) {
                handleToggleArchive($project, $projectId, $currentUserId);
            } else {
                handleCreateProject($project, $currentUserId);
            }
            break;
            
        case 'PUT':
            if ($projectId) {
                handleUpdateProject($project, $projectId, $currentUserId);
            } else {
                ResponseService::error('Project ID required', 400);
            }
            break;
            
        case 'DELETE':
            if ($projectId) {
                handleDeleteProject($project, $projectId, $currentUserId);
            } else {
                ResponseService::error('Project ID required', 400);
            }
            break;
            
        default:
            ResponseService::error('Method not allowed', 405);
    }
    
} catch (Exception $e) {
    error_log("Projects API Error: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    ResponseService::error('Internal server error: ' . $e->getMessage(), 500);
}

function handleGetProjects($project, $userId) {
    try {
        $filters = $_GET;
        $page = (int)($_GET['page'] ?? 1);
        $limit = min((int)($_GET['limit'] ?? 50), 100);
        
        $result = $project->getProjectsForUser($userId, $filters, $page, $limit);
        
        if ($result['success']) {
            ResponseService::success([
                'projects' => $result['data'],
                'pagination' => $result['pagination'] ?? null
            ]);
        } else {
            ResponseService::error($result['message'], 400);
        }
    } catch (Exception $e) {
        error_log("handleGetProjects error: " . $e->getMessage());
        ResponseService::error('Erreur lors de la récupération des projets', 500);
    }
}

function handleGetProject($project, $projectId, $userId) {
    try {
        $result = $project->getProjectById($projectId, $userId);
        
        if ($result['success']) {
            ResponseService::success($result['data']);
        } else {
            ResponseService::error($result['message'], 404);
        }
    } catch (Exception $e) {
        error_log("handleGetProject error: " . $e->getMessage());
        ResponseService::error('Erreur lors de la récupération du projet', 500);
    }
}

function handleCreateProject($project, $userId) {
    try {
        // Règles de validation basées sur la VRAIE structure DB
        $rules = [
            'name' => 'required|string|min:1|max:100',           // varchar(100) NOT NULL
            'description' => 'nullable|string|max:1000',         // text DEFAULT NULL
            'color' => 'nullable|string|max:7',                  // varchar(7) DEFAULT '#4361ee'
            'icon' => 'nullable|string|max:50',                  // varchar(50) DEFAULT 'folder'
            'status' => 'nullable|string|in:active,archived,completed', // enum avec ces valeurs
            'priority' => 'nullable|string|in:low,medium,high,urgent',  // enum avec ces valeurs
            'start_date' => 'nullable|date',                     // date DEFAULT NULL
            'end_date' => 'nullable|date',                       // date DEFAULT NULL (pas due_date!)
            'is_public' => 'nullable|boolean'                    // tinyint(4) NOT NULL DEFAULT 0
        ];
        
        // Valider les données avec notre middleware amélioré
        $validatedData = ValidationMiddleware::validate($rules);
        
        // Préparer les données selon la VRAIE structure DB
        $projectData = [
            'name' => $validatedData['name'],
            'description' => $validatedData['description'] ?? null,
            'color' => $validatedData['color'] ?? '#4361ee',      // Valeur par défaut DB
            'icon' => $validatedData['icon'] ?? 'folder',         // Valeur par défaut DB  
            'status' => $validatedData['status'] ?? 'active',     // Valeur par défaut DB
            'priority' => $validatedData['priority'] ?? 'medium', // Valeur par défaut DB
            'start_date' => !empty($validatedData['start_date']) ? $validatedData['start_date'] : null,
            'end_date' => !empty($validatedData['end_date']) ? $validatedData['end_date'] : null,  // Pas due_date!
            'is_public' => isset($validatedData['is_public']) ? (int)(bool)$validatedData['is_public'] : 0,
            'owner_id' => $userId  // Pas created_by mais owner_id!
        ];
        
        // Debug log avec les bons noms de champs
        error_log("Creating project with CORRECTED data: " . json_encode($projectData));
        
        $result = $project->createProject($projectData, $userId);
        
        if ($result['success']) {
            // Add members if provided
            if (!empty($validatedData['members']) && is_array($validatedData['members'])) {
                foreach ($validatedData['members'] as $member) {
                    if (isset($member['user_id']) && isset($member['role'])) {
                        $project->addMember($result['data']['id'], $member['user_id'], $member['role']);
                    }
                }
            }
            
            ResponseService::success($result['data'], 'Projet créé avec succès', 201);
        } else {
            ResponseService::error($result['message'], 400);
        }
        
    } catch (Exception $e) {
        error_log("handleCreateProject error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        ResponseService::error('Erreur lors de la création du projet: ' . $e->getMessage(), 500);
    }
}

function handleUpdateProject($project, $projectId, $userId) {
    try {
        // Règles de validation pour la mise à jour (tous les champs optionnels)
        $rules = [
            'name' => 'nullable|string|min:1|max:100',
            'description' => 'nullable|string|max:1000',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:50',
            'status' => 'nullable|string|in:active,archived,completed',
            'priority' => 'nullable|string|in:low,medium,high,urgent',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',      // Pas due_date!
            'is_public' => 'nullable|boolean'
        ];
        
        // Valider les données
        $validatedData = ValidationMiddleware::validate($rules);
        
        // Traitement spécial pour les dates - permettre de les vider
        if (array_key_exists('start_date', $validatedData)) {
            if (empty($validatedData['start_date']) || $validatedData['start_date'] === '') {
                $validatedData['start_date'] = null;
            }
        }
        
        if (array_key_exists('end_date', $validatedData)) {
            if (empty($validatedData['end_date']) || $validatedData['end_date'] === '') {
                $validatedData['end_date'] = null;
            }
        }
        
        // Traitement spécial pour is_public - conversion en tinyint
        if (array_key_exists('is_public', $validatedData)) {
            $validatedData['is_public'] = (int)(bool)$validatedData['is_public'];
        }
        
        // Debug log avec les bons noms de champs
        error_log("Updating project $projectId with CORRECTED data: " . json_encode($validatedData));
        
        // Check if user has permission to update this project
        $projectData = $project->getProjectById($projectId, $userId);
        if (!$projectData['success']) {
            ResponseService::error('Projet non trouvé', 404);
            return;
        }
        
        $result = $project->updateProject($projectId, $validatedData, $userId);
        
        if ($result['success']) {
            // Update members if provided
            if (isset($validatedData['members']) && is_array($validatedData['members'])) {
                // Remove existing members and add new ones
                $project->removeAllMembers($projectId);
                foreach ($validatedData['members'] as $member) {
                    if (isset($member['user_id']) && isset($member['role'])) {
                        $project->addMember($projectId, $member['user_id'], $member['role']);
                    }
                }
            }
            
            ResponseService::success($result['data'], 'Projet mis à jour avec succès');
        } else {
            ResponseService::error($result['message'], 400);
        }
        
    } catch (Exception $e) {
        error_log("handleUpdateProject error: " . $e->getMessage());
        error_log("Stack trace: " . $e->getTraceAsString());
        ResponseService::error('Erreur lors de la mise à jour du projet: ' . $e->getMessage(), 500);
    }
}

function handleDeleteProject($project, $projectId, $userId) {
    try {
        $result = $project->deleteProject($projectId, $userId);
        
        if ($result['success']) {
            ResponseService::success(['message' => 'Projet supprimé avec succès']);
        } else {
            ResponseService::error($result['message'], $result['success'] === false ? 403 : 400);
        }
    } catch (Exception $e) {
        error_log("handleDeleteProject error: " . $e->getMessage());
        ResponseService::error('Erreur lors de la suppression du projet', 500);
    }
}

function handleToggleFavorite($project, $projectId, $userId) {
    try {
        $result = $project->toggleFavorite($projectId, $userId);
        
        if ($result['success']) {
            ResponseService::success($result['data']);
        } else {
            ResponseService::error($result['message'], 400);
        }
    } catch (Exception $e) {
        error_log("handleToggleFavorite error: " . $e->getMessage());
        ResponseService::error('Erreur lors de la modification des favoris', 500);
    }
}

function handleToggleArchive($project, $projectId, $userId) {
    try {
        $result = $project->toggleArchive($projectId, $userId);
        
        if ($result['success']) {
            ResponseService::success($result['data']);
        } else {
            ResponseService::error($result['message'], 400);
        }
    } catch (Exception $e) {
        error_log("handleToggleArchive error: " . $e->getMessage());
        ResponseService::error('Erreur lors de l\'archivage du projet', 500);
    }
}
