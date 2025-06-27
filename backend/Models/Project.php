<?php

namespace TaskManager\Models;

use PDO;
use Exception;

class Project extends BaseModel
{
    protected string $table = 'projects';

    // Champs correspondant EXACTEMENT à la structure DB
    protected array $fillable = [
        'name',
        'description',
        'color',
        'icon',
        'status',
        'priority',
        'start_date',
        'end_date',
        'is_public',
        'owner_id'
    ];

    public function createProject(array $data, int $userId): array
    {
        try {
            $this->db->beginTransaction();

            // Correspondance EXACTE avec la structure DB
            $projectData = [
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'color' => $data['color'] ?? '#4361ee',      // Valeur par défaut DB
                'icon' => $data['icon'] ?? 'folder',         // Valeur par défaut DB
                'status' => $data['status'] ?? 'active',     // Valeur par défaut DB
                'priority' => $data['priority'] ?? 'medium', // Valeur par défaut DB
                'start_date' => $data['start_date'] ?? null,
                'end_date' => $data['end_date'] ?? null,     // Plus de conversion depuis due_date!
                'is_public' => isset($data['is_public']) ? (int)$data['is_public'] : 0,
                'owner_id' => $userId  // Correspond à la DB
            ];

            error_log("Project Model - Creating with data: " . json_encode($projectData));

            $result = $this->create($projectData);

            if (!$result['success']) {
                $this->db->rollBack();
                return $result;
            }

            $projectId = $result['id'];
            $this->addMember($projectId, $userId, 'owner');
            $this->db->commit();
            return $this->getProjectById($projectId, $userId);

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error creating project: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la création du projet: ' . $e->getMessage()];
        }
    }
    
    public function getProjectsForUser(int $userId, array $filters = [], int $page = 1, int $limit = 50): array
    {
        try {
            $offset = ($page - 1) * $limit;
            
            // CORRECTION: Seuls les projets où l'utilisateur est membre sont visibles
            $whereConditions = ["pm.user_id = :user_id"];
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

            // Correction pour le tri avec les vrais noms de champs
            $sortBy = 'p.updated_at';
            if (!empty($filters['sortBy'])) {
                $validSorts = ['name', 'created_at', 'updated_at', 'end_date', 'completion_percentage'];
                $sortField = $filters['sortBy'];
                if (in_array($sortField, $validSorts)) {
                    $sortBy = "p.$sortField";
                }
            }
            
            $sortOrder = 'DESC';
            if (!empty($filters['sortOrder']) && in_array(strtolower($filters['sortOrder']), ['asc', 'desc'])) {
                $sortOrder = strtoupper($filters['sortOrder']);
            }
            $orderByClause = "$sortBy $sortOrder";
            
            // SQL avec les vrais noms de champs
            $sql = "SELECT DISTINCT p.*, 
                           pm.role as user_role,
                           u.username as owner_username,
                           (SELECT COUNT(*) FROM project_members pm2 WHERE pm2.project_id = p.id) as members_count,
                           (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id) as tasks_total,
                           (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.status = 'completed') as tasks_completed,
                           COALESCE((SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.status = 'completed') * 100.0 / 
                                   NULLIF((SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id), 0), 0) as completion_percentage
                    FROM projects p
                    INNER JOIN project_members pm ON p.id = pm.project_id
                    LEFT JOIN users u ON p.owner_id = u.id
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
            
            $countSql = "SELECT COUNT(DISTINCT p.id) FROM projects p INNER JOIN project_members pm ON p.id = pm.project_id WHERE $whereClause";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();

            foreach ($projects as &$project) {
                $project['members'] = $this->getProjectMembers($project['id']);
            }

            return [
                'success' => true,
                'data' => $projects,
                'pagination' => [
                    'page' => $page,
                    'limit' => $limit,
                    'total' => (int)$total,
                    'pages' => ceil($total / $limit)
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Error fetching projects: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur serveur lors de la récupération des projets: ' . $e->getMessage()];
        }
    }

    public function getProjectById(int $projectId, int $userId): array
    {
        try {
            // CORRECTION SQL : Vérifier que l'utilisateur est membre du projet
            $sql = "SELECT p.*, 
                           pm.role as user_role,
                           u.username as owner_username,
                           (SELECT COUNT(*) FROM project_members pm2 WHERE pm2.project_id = p.id) as members_count,
                           (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id) as tasks_total,
                           (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.status = 'completed') as tasks_completed,
                           COALESCE((SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.status = 'completed') * 100.0 / 
                                   NULLIF((SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id), 0), 0) as completion_percentage
                    FROM projects p
                    INNER JOIN project_members pm ON p.id = pm.project_id AND pm.user_id = :user_id
                    LEFT JOIN users u ON p.owner_id = u.id
                    WHERE p.id = :project_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':project_id' => $projectId, ':user_id' => $userId]);
            
            $project = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$project) {
                return ['success' => false, 'message' => 'Projet non trouvé ou accès non autorisé'];
            }
            
            $project['members'] = $this->getProjectMembers($projectId);
            $project['recent_tasks'] = $this->getRecentProjectTasks($projectId, 5);
            
            return ['success' => true, 'data' => $project];
            
        } catch (Exception $e) {
            error_log("Error fetching project by ID: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la récupération du projet: ' . $e->getMessage()];
        }
    }

    /**
     * Récupère les projets récents pour un utilisateur donné
     */
    public function getRecentProjects(int $userId, int $limit = 5): array
    {
        try {
            $sql = "SELECT DISTINCT p.id, p.name, p.description, p.status, p.priority, 
                           p.color, p.icon, p.created_at, p.updated_at, p.end_date,
                           pm.role as user_role,
                           u.username as owner_username,
                           (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id) as tasks_total,
                           (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.status = 'completed') as tasks_completed,
                           COALESCE((SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.status = 'completed') * 100.0 / 
                                   NULLIF((SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id), 0), 0) as completion_percentage
                    FROM projects p
                    INNER JOIN project_members pm ON p.id = pm.project_id
                    LEFT JOIN users u ON p.owner_id = u.id
                    WHERE pm.user_id = :user_id
                    AND p.status != 'archived'
                    ORDER BY p.updated_at DESC
                    LIMIT :limit";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bindParam(':user_id', $userId, PDO::PARAM_INT);
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();
            
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Ajouter des informations supplémentaires pour chaque projet
            foreach ($projects as &$project) {
                // Calculer les pourcentages et formater les données
                $project['completion_percentage'] = (float)$project['completion_percentage'];
                $project['tasks_total'] = (int)$project['tasks_total'];
                $project['tasks_completed'] = (int)$project['tasks_completed'];
                
                // Ajouter le statut de progression
                if ($project['completion_percentage'] == 100) {
                    $project['progress_status'] = 'completed';
                } elseif ($project['completion_percentage'] > 0) {
                    $project['progress_status'] = 'in_progress';
                } else {
                    $project['progress_status'] = 'not_started';
                }
            }
            
            return $projects;
            
        } catch (Exception $e) {
            error_log("Error getting recent projects: " . $e->getMessage());
            return [];
        }
    }

    public function updateProject(int $projectId, array $data, int $userId): array
    {
        try {
            if (!$this->hasProjectPermission($projectId, $userId, ['owner', 'admin'])) {
                return ['success' => false, 'message' => 'Permissions insuffisantes pour mettre à jour ce projet.'];
            }
            
            // Plus de conversion due_date → end_date, on garde les données telles quelles
            error_log("Project Model - Updating with data: " . json_encode($data));

            return $this->update($projectId, $data);
            
        } catch (Exception $e) {
            error_log("Error updating project: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la mise à jour du projet: ' . $e->getMessage()];
        }
    }

    public function deleteProject(int $projectId, int $userId): array
    {
        try {
            // Récupérer le projet pour vérifier le propriétaire
            $project = $this->findById($projectId);
    
            if (!$project) {
                return ['success' => false, 'message' => 'Projet non trouvé.'];
            }
    
            // Vérifier si l'userId correspond à l'owner_id du projet
            if ($project['owner_id'] != $userId) {
                return ['success' => false, 'message' => 'Seul le propriétaire peut supprimer ce projet.'];
            }
            
            return $this->delete($projectId);
            
        } catch (Exception $e) {
            error_log("Error deleting project: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la suppression du projet: ' . $e->getMessage()];
        }
    }

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

    public function removeMember(int $projectId, int $userId): bool
    {
        try {
            $sql = "DELETE FROM project_members WHERE project_id = ? AND user_id = ?";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$projectId, $userId]);
            
        } catch (Exception $e) {
            error_log("Error removing member from project: " . $e->getMessage());
            return false;
        }
    }

    public function removeAllMembers(int $projectId): bool
    {
        try {
            // Ne pas supprimer le propriétaire
            $sql = "DELETE pm FROM project_members pm 
                    INNER JOIN projects p ON pm.project_id = p.id 
                    WHERE pm.project_id = ? AND pm.user_id != p.owner_id";
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$projectId]);
            
        } catch (Exception $e) {
            error_log("Error removing all members: " . $e->getMessage());
            return false;
        }
    }

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

    public function getProjectStats(int $userId): array
    {
        try {
            $sql = "SELECT 
                        COUNT(DISTINCT p.id) as total,
                        SUM(CASE WHEN p.status = 'active' THEN 1 ELSE 0 END) as active,
                        SUM(CASE WHEN p.status = 'completed' THEN 1 ELSE 0 END) as completed,
                        SUM(CASE WHEN p.end_date < CURDATE() AND p.status != 'completed' THEN 1 ELSE 0 END) as overdue
                    FROM projects p
                    INNER JOIN project_members pm ON p.id = pm.project_id
                    WHERE pm.user_id = ?
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

    public function toggleFavorite(int $projectId, int $userId): array
    {
        try {
            // Vérifier si le projet est déjà en favori
            $sql = "SELECT COUNT(*) FROM project_favorites WHERE project_id = ? AND user_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$projectId, $userId]);
            $isFavorite = $stmt->fetchColumn() > 0;

            if ($isFavorite) {
                // Retirer des favoris
                $sql = "DELETE FROM project_favorites WHERE project_id = ? AND user_id = ?";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$projectId, $userId]);
                $message = 'Projet retiré des favoris';
                $favoriteStatus = false;
            } else {
                // Ajouter aux favoris
                $sql = "INSERT INTO project_favorites (project_id, user_id) VALUES (?, ?)";
                $stmt = $this->db->prepare($sql);
                $stmt->execute([$projectId, $userId]);
                $message = 'Projet ajouté aux favoris';
                $favoriteStatus = true;
            }

            return [
                'success' => true,
                'message' => $message,
                'data' => ['is_favorite' => $favoriteStatus]
            ];

        } catch (Exception $e) {
            error_log("Error toggling favorite: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la modification des favoris'];
        }
    }

    public function toggleArchive(int $projectId, int $userId): array
    {
        try {
            if (!$this->hasProjectPermission($projectId, $userId, ['owner', 'admin'])) {
                return ['success' => false, 'message' => 'Permissions insuffisantes'];
            }

            $project = $this->findById($projectId);
            $newStatus = ($project['status'] === 'archived') ? 'active' : 'archived';

            $result = $this->update($projectId, ['status' => $newStatus]);
            
            if ($result['success']) {
                $message = ($newStatus === 'archived') ? 'Projet archivé' : 'Projet désarchivé';
                return [
                    'success' => true,
                    'message' => $message,
                    'data' => ['status' => $newStatus]
                ];
            }

            return $result;

        } catch (Exception $e) {
            error_log("Error toggling archive: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de l\'archivage'];
        }
    }
}
