<?php

namespace TaskManager\Models;

use TaskManager\Database\Connection;
use PDO;
use Exception;

class Project extends BaseModel {
    protected string $table = 'projects';
    
    protected array $fillable = [
        'name',
        'description', 
        'status',
        'priority',
        'due_date',
        'color',
        'is_public',
        'created_by'
    ];

    /**
     * Create a new project
     */
    public function createProject($data, $userId) {
        try {
            $this->db->beginTransaction();
            
            $sql = "INSERT INTO projects (name, description, status, priority, due_date, color, is_public, created_by, created_at) 
                    VALUES (:name, :description, :status, :priority, :due_date, :color, :is_public, :created_by, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':name' => $data['name'],
                ':description' => $data['description'],
                ':status' => $data['status'],
                ':priority' => $data['priority'],
                ':due_date' => $data['due_date'],
                ':color' => $data['color'],
                ':is_public' => $data['is_public'] ? 1 : 0,
                ':created_by' => $data['created_by']
            ]);
            
            if (!$result) {
                throw new Exception('Failed to create project');
            }
            
            $projectId = $this->db->lastInsertId();
            
            // Add creator as project owner
            $this->addMember($projectId, $userId, 'owner');
            
            $this->db->commit();
            
            return [
                'success' => true,
                'data' => $this->getProjectById($projectId, $userId)['data']
            ];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => 'Error creating project: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get projects for a specific user with filters
     */
    public function getProjectsForUser($userId, $filters = [], $page = 1, $limit = 50) {
        try {
            $offset = ($page - 1) * $limit;
            
            // Build WHERE clause
            $whereConditions = [];
            $params = [':user_id' => $userId];
            
            // Base condition - user must be a member or project is public
            $whereConditions[] = "(pm.user_id = :user_id OR p.is_public = 1)";
            
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
            
            if (!empty($filters['is_archived'])) {
                $whereConditions[] = "p.is_archived = :is_archived";
                $params[':is_archived'] = $filters['is_archived'];
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            // Build ORDER BY clause
            $orderBy = 'p.updated_at DESC';
            if (!empty($filters['sortBy'])) {
                $sortOrder = $filters['sortOrder'] ?? 'desc';
                $validSorts = ['name', 'created_at', 'updated_at', 'due_date', 'completion_percentage'];
                
                if (in_array($filters['sortBy'], $validSorts)) {
                    $orderBy = "p.{$filters['sortBy']} " . strtoupper($sortOrder);
                }
            }
            
            // Main query
            $sql = "SELECT DISTINCT p.*, 
                           pm.role as user_role,
                           u.username as created_by_username,
                           pf.id as is_favorite,
                           (SELECT COUNT(*) FROM project_members pm2 WHERE pm2.project_id = p.id) as members_count,
                           (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id) as tasks_total,
                           (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.status = 'completed') as tasks_completed,
                           COALESCE((SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.status = 'completed') * 100.0 / 
                                   NULLIF((SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id), 0), 0) as completion_percentage
                    FROM projects p
                    LEFT JOIN project_members pm ON p.id = pm.project_id
                    LEFT JOIN users u ON p.created_by = u.id
                    LEFT JOIN project_favorites pf ON p.id = pf.project_id AND pf.user_id = :user_id
                    WHERE $whereClause
                    ORDER BY $orderBy
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            
            // Bind parameters
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get total count for pagination
            $countSql = "SELECT COUNT(DISTINCT p.id) as total
                        FROM projects p
                        LEFT JOIN project_members pm ON p.id = pm.project_id
                        WHERE $whereClause";
            
            $countStmt = $this->db->prepare($countSql);
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Process projects to add additional data
            foreach ($projects as &$project) {
                $project['is_favorite'] = !empty($project['is_favorite']);
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
            return [
                'success' => false,
                'message' => 'Error fetching projects: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get a specific project by ID
     */
    public function getProjectById($projectId, $userId) {
        try {
            $sql = "SELECT p.*, 
                           pm.role as user_role,
                           u.username as created_by_username,
                           pf.id as is_favorite,
                           (SELECT COUNT(*) FROM project_members pm2 WHERE pm2.project_id = p.id) as members_count,
                           (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id) as tasks_total,
                           (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.status = 'completed') as tasks_completed,
                           COALESCE((SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.status = 'completed') * 100.0 / 
                                   NULLIF((SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id), 0), 0) as completion_percentage
                    FROM projects p
                    LEFT JOIN project_members pm ON p.id = pm.project_id AND pm.user_id = :user_id
                    LEFT JOIN users u ON p.created_by = u.id
                    LEFT JOIN project_favorites pf ON p.id = pf.project_id AND pf.user_id = :user_id
                    WHERE p.id = :project_id 
                    AND (pm.user_id = :user_id OR p.is_public = 1)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':project_id' => $projectId,
                ':user_id' => $userId
            ]);
            
            $project = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$project) {
                return [
                    'success' => false,
                    'message' => 'Project not found or access denied'
                ];
            }
            
            $project['is_favorite'] = !empty($project['is_favorite']);
            $project['members'] = $this->getProjectMembers($projectId);
            $project['recent_tasks'] = $this->getRecentProjectTasks($projectId, 5);
            
            return [
                'success' => true,
                'data' => $project
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching project: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update a project
     */
    public function updateProject($projectId, $data, $userId) {
        try {
            // Check if user has permission to update
            if (!$this->hasProjectPermission($projectId, $userId, ['owner', 'admin'])) {
                return [
                    'success' => false,
                    'message' => 'Insufficient permissions to update project'
                ];
            }
            
            $updateFields = [];
            $params = [':project_id' => $projectId];
            
            $allowedFields = ['name', 'description', 'status', 'priority', 'due_date', 'color', 'is_public'];
            
            foreach ($allowedFields as $field) {
                if (array_key_exists($field, $data)) {
                    $updateFields[] = "$field = :$field";
                    $params[":$field"] = $data[$field];
                }
            }
            
            if (empty($updateFields)) {
                return [
                    'success' => false,
                    'message' => 'No valid fields to update'
                ];
            }
            
            $updateFields[] = "updated_at = NOW()";
            
            $sql = "UPDATE projects SET " . implode(', ', $updateFields) . " WHERE id = :project_id";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);
            
            if (!$result) {
                throw new Exception('Failed to update project');
            }
            
            return [
                'success' => true,
                'data' => $this->getProjectById($projectId, $userId)['data']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error updating project: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete a project
     */
    public function deleteProject($projectId, $userId) {
        try {
            // Check if user is the owner
            if (!$this->hasProjectPermission($projectId, $userId, ['owner'])) {
                return [
                    'success' => false,
                    'message' => 'Only project owners can delete projects'
                ];
            }
            
            $this->db->beginTransaction();
            
            // Delete related data
            $this->db->prepare("DELETE FROM project_favorites WHERE project_id = ?")->execute([$projectId]);
            $this->db->prepare("DELETE FROM project_members WHERE project_id = ?")->execute([$projectId]);
            $this->db->prepare("UPDATE tasks SET project_id = NULL WHERE project_id = ?")->execute([$projectId]);
            
            // Delete project
            $stmt = $this->db->prepare("DELETE FROM projects WHERE id = ?");
            $result = $stmt->execute([$projectId]);
            
            if (!$result) {
                throw new Exception('Failed to delete project');
            }
            
            $this->db->commit();
            
            return ['success' => true];
            
        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => 'Error deleting project: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Add a member to a project
     */
    public function addMember($projectId, $userId, $role = 'member') {
        try {
            $sql = "INSERT INTO project_members (project_id, user_id, role, joined_at) 
                    VALUES (?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE role = VALUES(role)";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$projectId, $userId, $role]);
            
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Remove all members from a project
     */
    public function removeAllMembers($projectId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM project_members WHERE project_id = ? AND role != 'owner'");
            return $stmt->execute([$projectId]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get project members
     */
    public function getProjectMembers($projectId) {
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
            return [];
        }
    }

    /**
     * Toggle project favorite status
     */
    public function toggleFavorite($projectId, $userId) {
        try {
            // Check if already favorited
            $stmt = $this->db->prepare("SELECT id FROM project_favorites WHERE project_id = ? AND user_id = ?");
            $stmt->execute([$projectId, $userId]);
            $favorite = $stmt->fetch();
            
            if ($favorite) {
                // Remove from favorites
                $stmt = $this->db->prepare("DELETE FROM project_favorites WHERE project_id = ? AND user_id = ?");
                $stmt->execute([$projectId, $userId]);
                $isFavorite = false;
            } else {
                // Add to favorites
                $stmt = $this->db->prepare("INSERT INTO project_favorites (project_id, user_id, created_at) VALUES (?, ?, NOW())");
                $stmt->execute([$projectId, $userId]);
                $isFavorite = true;
            }
            
            return [
                'success' => true,
                'data' => $this->getProjectById($projectId, $userId)['data']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error toggling favorite: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Toggle project archive status
     */
    public function toggleArchive($projectId, $userId) {
        try {
            if (!$this->hasProjectPermission($projectId, $userId, ['owner', 'admin'])) {
                return [
                    'success' => false,
                    'message' => 'Insufficient permissions to archive project'
                ];
            }
            
            // Get current archive status
            $stmt = $this->db->prepare("SELECT is_archived FROM projects WHERE id = ?");
            $stmt->execute([$projectId]);
            $project = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$project) {
                return ['success' => false, 'message' => 'Project not found'];
            }
            
            $newStatus = !$project['is_archived'];
            
            $stmt = $this->db->prepare("UPDATE projects SET is_archived = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newStatus, $projectId]);
            
            return [
                'success' => true,
                'data' => $this->getProjectById($projectId, $userId)['data']
            ];
            
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error toggling archive: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get project statistics for a user
     */
    public function getProjectStats($userId) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total,
                        SUM(CASE WHEN p.status = 'active' THEN 1 ELSE 0 END) as active,
                        SUM(CASE WHEN p.status = 'completed' THEN 1 ELSE 0 END) as completed,
                        SUM(CASE WHEN p.due_date < NOW() AND p.status != 'completed' THEN 1 ELSE 0 END) as overdue,
                        SUM(CASE WHEN p.is_archived = 0 THEN 1 ELSE 0 END) as not_archived
                    FROM projects p
                    LEFT JOIN project_members pm ON p.id = pm.project_id
                    WHERE (pm.user_id = ? OR p.is_public = 1)
                    AND p.is_archived = 0";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: [
                'total' => 0,
                'active' => 0,
                'completed' => 0,
                'overdue' => 0,
                'not_archived' => 0
            ];
            
        } catch (Exception $e) {
            error_log('Project stats error: ' . $e->getMessage());
            return [
                'total' => 0,
                'active' => 0,
                'completed' => 0,
                'overdue' => 0,
                'not_archived' => 0
            ];
        }
    }

    /**
     * Get recent projects for a user
     */
    public function getRecentProjects($userId, $limit = 5) {
        try {
            $sql = "SELECT p.id, p.name, p.status, p.color, p.updated_at,
                           (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id) as tasks_total,
                           (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.status = 'completed') as tasks_completed
                    FROM projects p
                    LEFT JOIN project_members pm ON p.id = pm.project_id
                    WHERE (pm.user_id = ? OR p.is_public = 1)
                    AND p.is_archived = 0
                    ORDER BY p.updated_at DESC
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            error_log('Recent projects error: ' . $e->getMessage());
            return [];
        }
    }

    /**
     * Get recent tasks for a project
     */
    public function getRecentProjectTasks($projectId, $limit = 5) {
        try {
            $sql = "SELECT t.id, t.title, t.status, t.priority, t.due_date, t.created_at,
                           u.username as assigned_to_username
                    FROM tasks t
                    LEFT JOIN users u ON t.assignee_id = u.id
                    WHERE t.project_id = ?
                    ORDER BY t.updated_at DESC
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$projectId, $limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (Exception $e) {
            return [];
        }
    }

    /**
     * Check if user has specific permission on project
     */
    public function hasProjectPermission($projectId, $userId, $requiredRoles = []) {
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
            return false;
        }
    }
}
