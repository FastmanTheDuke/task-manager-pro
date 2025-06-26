<?php

namespace TaskManager\Models;

use PDO;
use Exception;

class Project extends BaseModel
{
    protected string $table = 'projects';

    // Correction des champs pour correspondre à la base de données
    protected array $fillable = [
        'name',
        'description',
        'status',
        'priority',
        'end_date', // Utiliser end_date qui correspond à due_date dans la DB
        'color',
        'is_public',
        'owner_id' // Le propriétaire est `owner_id` dans la DB
    ];

    /**
     * Crée un nouveau projet et assigne le créateur comme propriétaire.
     */
    public function createProject(array $data, int $userId): array
    {
        try {
            $this->db->beginTransaction();

            // Préparation des données avec les valeurs par défaut
            $projectData = [
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? 'active',
                'priority' => $data['priority'] ?? 'medium',
                'end_date' => $data['due_date'] ?? null, // Mapping de due_date du formulaire vers end_date de la DB
                'color' => $data['color'] ?? '#3B82F6',
                'is_public' => (isset($data['is_public']) && $data['is_public']) ? 1 : 0,
                'owner_id' => $userId
            ];

            // Création du projet en utilisant la méthode parente
            $result = $this->create($projectData);

            if (!$result['success']) {
                $this->db->rollBack();
                return $result;
            }

            $projectId = $result['id'];

            // Ajout du créateur comme membre avec le rôle 'owner'
            $this->addMember($projectId, $userId, 'owner');

            $this->db->commit();

            // Retourner les détails complets du projet créé
            return $this->getProjectById($projectId, $userId);

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error creating project: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la création du projet.'];
        }
    }
    
    /**
     * Récupère les projets pour un utilisateur avec filtres et pagination.
     */
    public function getProjectsForUser(int $userId, array $filters = [], int $page = 1, int $limit = 50): array
    {
        try {
            $offset = ($page - 1) * $limit;
            
            $whereConditions = ["(pm.user_id = :user_id OR p.is_public = 1)"];
            $params = [':user_id' => $userId];

            if (!empty($filters['search'])) {
                $whereConditions[] = "(p.name LIKE :search OR p.description LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }
            if (!empty($filters['status']) && $filters['status'] !== 'all') {
                $whereConditions[] = "p.status = :status";
                $params[':status'] = $filters['status'];
            }
            if (!empty($filters['role']) && $filters['role'] !== 'all') {
                $whereConditions[] = "pm.role = :role";
                $params[':role'] = $filters['role'];
            }

            $whereClause = implode(' AND ', $whereConditions);

            $sortBy = 'p.updated_at';
            $sortOrder = 'DESC';
            $validSorts = ['name', 'created_at', 'updated_at', 'end_date', 'completion_percentage'];
            if (!empty($filters['sortBy']) && in_array($filters['sortBy'], $validSorts)) {
                $sortBy = 'p.' . $filters['sortBy'];
            }
            if (!empty($filters['sortOrder']) && in_array(strtolower($filters['sortOrder']), ['asc', 'desc'])) {
                $sortOrder = strtoupper($filters['sortOrder']);
            }
            $orderByClause = "$sortBy $sortOrder";
            
            // ** CORRECTION SQL ICI **
            $sql = "SELECT DISTINCT p.*, 
                           pm.role as user_role,
                           u.username as owner_username,
                           (pf.project_id IS NOT NULL) as is_favorite, -- Correction ici
                           (SELECT COUNT(*) FROM project_members pm2 WHERE pm2.project_id = p.id) as members_count,
                           (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id) as tasks_total,
                           (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.status = 'completed') as tasks_completed,
                           COALESCE((SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.status = 'completed') * 100.0 / 
                                   NULLIF((SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id), 0), 0) as completion_percentage
                    FROM projects p
                    LEFT JOIN project_members pm ON p.id = pm.project_id
                    LEFT JOIN users u ON p.owner_id = u.id
                    LEFT JOIN project_favorites pf ON p.id = pf.project_id AND pf.user_id = :user_id
                    WHERE $whereClause
                    ORDER BY $orderByClause
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => &$value) {
                $stmt->bindParam($key, $value);
            }
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $countSql = "SELECT COUNT(DISTINCT p.id) FROM projects p LEFT JOIN project_members pm ON p.id = pm.project_id WHERE $whereClause";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();

            foreach ($projects as &$project) {
                $project['is_favorite'] = !empty($project['is_favorite']);
                $project['members'] = $this->getProjectMembers($project['id']);
            }

            return [
                'success' => true,
                'data' => [
                    'projects' => $projects,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => (int)$total,
                        'pages' => ceil($total / $limit)
                    ],
                    'stats' => $this->getProjectStats($userId)
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Error fetching projects: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur serveur lors de la récupération des projets.'];
        }
    }

    /**
     * Récupère un projet spécifique par son ID.
     */
    public function getProjectById(int $projectId, int $userId): array
    {
        try {
            $sql = "SELECT p.*, 
                           pm.role as user_role,
                           u.username as owner_username,
                           (pf.project_id IS NOT NULL) as is_favorite,
                           (SELECT COUNT(*) FROM project_members pm2 WHERE pm2.project_id = p.id) as members_count,
                           (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id) as tasks_total,
                           (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.status = 'completed') as tasks_completed,
                           COALESCE((SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.status = 'completed') * 100.0 / 
                                   NULLIF((SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id), 0), 0) as completion_percentage
                    FROM projects p
                    LEFT JOIN project_members pm ON p.id = pm.project_id AND pm.user_id = :user_id
                    LEFT JOIN users u ON p.owner_id = u.id
                    LEFT JOIN project_favorites pf ON p.id = pf.project_id AND pf.user_id = :user_id
                    WHERE p.id = :project_id 
                    AND (pm.user_id = :user_id OR p.is_public = 1)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':project_id' => $projectId, ':user_id' => $userId]);
            
            $project = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$project) {
                return ['success' => false, 'message' => 'Projet non trouvé ou accès non autorisé'];
            }
            
            $project['is_favorite'] = !empty($project['is_favorite']);
            $project['members'] = $this->getProjectMembers($projectId);
            $project['recent_tasks'] = $this->getRecentProjectTasks($projectId, 5);
            
            return ['success' => true, 'data' => $project];
            
        } catch (Exception $e) {
            error_log("Error fetching project by ID: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la récupération du projet.'];
        }
    }

    /**
     * Met à jour un projet.
     */
    public function updateProject(int $projectId, array $data, int $userId): array
    {
        try {
            if (!$this->hasProjectPermission($projectId, $userId, ['owner', 'admin'])) {
                return ['success' => false, 'message' => 'Permissions insuffisantes pour mettre à jour ce projet.'];
            }
            
            // Map due_date to end_date if it exists
            if (isset($data['due_date'])) {
                $data['end_date'] = $data['due_date'];
                unset($data['due_date']);
            }

            return $this->update($projectId, $data);
            
        } catch (Exception $e) {
            error_log("Error updating project: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la mise à jour du projet.'];
        }
    }

    /**
     * Supprime un projet.
     */
    public function deleteProject(int $projectId, int $userId): array
    {
        try {
            if (!$this->hasProjectPermission($projectId, $userId, ['owner'])) {
                return ['success' => false, 'message' => 'Seul le propriétaire peut supprimer un projet.'];
            }
            
            return $this->delete($projectId);
            
        } catch (Exception $e) {
            error_log("Error deleting project: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la suppression du projet.'];
        }
    }

    /**
     * Ajoute un membre à un projet.
     */
    public function addMember(int $projectId, int $userId, string $role = 'member'): bool
    {
        try {
            $sql = "INSERT INTO project_members (project_id, user_id, role, joined_at) 
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE role = VALUES(role)";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$projectId, $userId, $role]);
            
        } catch (Exception $e) {
            error_log("Error adding member to project: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Récupère les membres d'un projet.
     */
    public function getProjectMembers(int $projectId): array
    {
        try {
            $sql = "SELECT u.id, u.username, u.email, u.first_name, u.last_name, pm.role, pm.joined_at
                    FROM project_members pm
                    JOIN users u ON pm.user_id = u.id
                    WHERE pm.project_id = ?
                    ORDER BY pm.role = 'owner' DESC, pm.role = 'admin' DESC, pm.joined_at ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$projectId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error getting project members: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Vérifie si un utilisateur a une permission spécifique sur un projet.
     */
    public function hasProjectPermission(int $projectId, int $userId, array $requiredRoles = []): bool
    {
        try {
            $sql = "SELECT pm.role FROM project_members pm 
                    WHERE pm.project_id = ? AND pm.user_id = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$projectId, $userId]);
            $member = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$member) {
                return false;
            }
            
            return empty($requiredRoles) || in_array($member['role'], $requiredRoles);
            
        } catch (Exception $e) {
            error_log("Error checking project permission: " . $e->getMessage());
            return false;
        }
    }

    // --- Les autres fonctions que vous pourriez avoir perdu ---

    public function getProjectStats(int $userId): array
    {
        try {
            $sql = "SELECT 
                        COUNT(DISTINCT p.id) as total,
                        SUM(CASE WHEN p.status = 'active' THEN 1 ELSE 0 END) as active,
                        SUM(CASE WHEN p.status = 'completed' THEN 1 ELSE 0 END) as completed,
                        SUM(CASE WHEN p.end_date < CURDATE() AND p.status != 'completed' THEN 1 ELSE 0 END) as overdue
                    FROM projects p
                    LEFT JOIN project_members pm ON p.id = pm.project_id
                    WHERE (pm.user_id = ? OR p.is_public = 1)
                    AND p.status != 'archived'";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: ['total' => 0, 'active' => 0, 'completed' => 0, 'overdue' => 0];
            
        } catch (Exception $e) {
            error_log('Project stats error: ' . $e->getMessage());
            return ['total' => 0, 'active' => 0, 'completed' => 0, 'overdue' => 0];
        }
    }

    public function getRecentProjectTasks(int $projectId, int $limit = 5): array
    {
        try {
            $sql = "SELECT t.id, t.title, t.status, t.priority, t.due_date
                    FROM tasks t
                    WHERE t.project_id = ?
                    ORDER BY t.updated_at DESC
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$projectId, $limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log("Error getting recent project tasks: " . $e->getMessage());
            return [];
        }
    }
}
