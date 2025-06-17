<?php
namespace TaskManager\Models;

use Exception;

class Project extends BaseModel
{
    protected string $table = 'projects';
    protected array $fillable = [
        'name',
        'description',
        'color',
        'icon',
        'owner_id',
        'status',
        'start_date',
        'end_date'
    ];
    
    const STATUS_ACTIVE = 'active';
    const STATUS_ARCHIVED = 'archived';
    const STATUS_COMPLETED = 'completed';
    
    /**
     * Get projects for a specific user (owned or member)
     */
    public function getUserProjects(int $userId, array $filters = []): array
    {
        $sql = "SELECT p.*, 
                       u.username as owner_name,
                       COUNT(DISTINCT t.id) as task_count,
                       COUNT(DISTINCT CASE WHEN t.status = 'completed' THEN t.id END) as completed_tasks,
                       COUNT(DISTINCT pm.user_id) as member_count
                FROM {$this->table} p
                LEFT JOIN users u ON p.owner_id = u.id
                LEFT JOIN tasks t ON p.id = t.project_id
                LEFT JOIN project_members pm ON p.id = pm.project_id
                WHERE (p.owner_id = ? OR pm.user_id = ?)";
        
        $params = [$userId, $userId];
        
        // Add filters
        if (isset($filters['status'])) {
            $sql .= " AND p.status = ?";
            $params[] = $filters['status'];
        }
        
        if (isset($filters['search'])) {
            $sql .= " AND (p.name LIKE ? OR p.description LIKE ?)";
            $searchTerm = "%{$filters['search']}%";
            $params[] = $searchTerm;
            $params[] = $searchTerm;
        }
        
        $sql .= " GROUP BY p.id ORDER BY p.created_at DESC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Create a new project
     */
    public function createProject(array $data, int $userId): array
    {
        try {
            // Validate required fields
            if (empty($data['name'])) {
                return ['success' => false, 'message' => 'Le nom du projet est obligatoire'];
            }
            
            // Set default values
            $data['owner_id'] = $userId;
            $data['status'] = $data['status'] ?? self::STATUS_ACTIVE;
            $data['color'] = $data['color'] ?? '#4361ee';
            $data['icon'] = $data['icon'] ?? 'folder';
            
            $result = $this->create($data);
            
            if ($result['success']) {
                // Add owner as project member
                $this->addMember($result['id'], $userId, 'owner');
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Create project error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get project details with members and statistics
     */
    public function getProjectDetails(int $projectId, int $userId): ?array
    {
        // Check if user has access to this project
        if (!$this->hasAccess($projectId, $userId)) {
            return null;
        }
        
        $project = $this->findById($projectId);
        
        if (!$project) {
            return null;
        }
        
        // Get project members
        $project['members'] = $this->getProjectMembers($projectId);
        
        // Get project statistics
        $project['stats'] = $this->getProjectStatistics($projectId);
        
        // Get recent tasks
        $project['recent_tasks'] = $this->getRecentTasks($projectId, 5);
        
        return $project;
    }
    
    /**
     * Get project members
     */
    public function getProjectMembers(int $projectId): array
    {
        $sql = "SELECT u.id, u.username, u.email, u.first_name, u.last_name, u.avatar,
                       pm.role, pm.joined_at
                FROM project_members pm
                JOIN users u ON pm.user_id = u.id
                WHERE pm.project_id = ?
                ORDER BY pm.role DESC, u.username ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$projectId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Add member to project
     */
    public function addMember(int $projectId, int $userId, string $role = 'member'): array
    {
        try {
            // Check if user is already a member
            if ($this->isMember($projectId, $userId)) {
                return ['success' => false, 'message' => 'L\'utilisateur est déjà membre du projet'];
            }
            
            // Create project_members table if it doesn't exist
            $this->createProjectMembersTableIfNotExists();
            
            $sql = "INSERT INTO project_members (project_id, user_id, role, joined_at) 
                    VALUES (?, ?, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$projectId, $userId, $role]);
            
            return ['success' => true, 'message' => 'Membre ajouté avec succès'];
            
        } catch (Exception $e) {
            error_log("Add member error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Remove member from project
     */
    public function removeMember(int $projectId, int $userId): array
    {
        try {
            $sql = "DELETE FROM project_members WHERE project_id = ? AND user_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$projectId, $userId]);
            
            return ['success' => true, 'message' => 'Membre supprimé avec succès'];
            
        } catch (Exception $e) {
            error_log("Remove member error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Check if user is member of project
     */
    public function isMember(int $projectId, int $userId): bool
    {
        $sql = "SELECT COUNT(*) as count FROM project_members 
                WHERE project_id = ? AND user_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$projectId, $userId]);
        
        $result = $stmt->fetch();
        return (int)$result['count'] > 0;
    }
    
    /**
     * Check if user has access to project (owner or member)
     */
    public function hasAccess(int $projectId, int $userId): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} p
                LEFT JOIN project_members pm ON p.id = pm.project_id
                WHERE p.id = ? AND (p.owner_id = ? OR pm.user_id = ?)";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$projectId, $userId, $userId]);
        
        $result = $stmt->fetch();
        return (int)$result['count'] > 0;
    }
    
    /**
     * Get project statistics
     */
    public function getProjectStatistics(int $projectId): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_tasks,
                    COUNT(CASE WHEN status = 'pending' THEN 1 END) as pending_tasks,
                    COUNT(CASE WHEN status = 'in_progress' THEN 1 END) as in_progress_tasks,
                    COUNT(CASE WHEN status = 'completed' THEN 1 END) as completed_tasks,
                    COUNT(CASE WHEN due_date < NOW() AND status != 'completed' THEN 1 END) as overdue_tasks,
                    AVG(completion_percentage) as avg_completion
                FROM tasks 
                WHERE project_id = ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$projectId]);
        
        return $stmt->fetch() ?: [];
    }
    
    /**
     * Get recent tasks for project
     */
    public function getRecentTasks(int $projectId, int $limit = 10): array
    {
        $sql = "SELECT t.*, u.username as creator_name, a.username as assignee_name
                FROM tasks t
                LEFT JOIN users u ON t.creator_id = u.id
                LEFT JOIN users a ON t.assignee_id = a.id
                WHERE t.project_id = ?
                ORDER BY t.created_at DESC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$projectId, $limit]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Update project with validation
     */
    public function updateProject(int $projectId, array $data, int $userId): array
    {
        try {
            $project = $this->findById($projectId);
            
            if (!$project) {
                return ['success' => false, 'message' => 'Projet non trouvé'];
            }
            
            // Check if user is owner or has admin role in project
            if ($project['owner_id'] != $userId && !$this->hasAdminRole($projectId, $userId)) {
                return ['success' => false, 'message' => 'Accès non autorisé'];
            }
            
            // Validate data
            $errors = $this->validateProjectData($data);
            if (!empty($errors)) {
                return ['success' => false, 'message' => 'Erreur de validation', 'errors' => $errors];
            }
            
            return $this->update($projectId, $data);
            
        } catch (Exception $e) {
            error_log("Update project error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Check if user has admin role in project
     */
    public function hasAdminRole(int $projectId, int $userId): bool
    {
        $sql = "SELECT role FROM project_members 
                WHERE project_id = ? AND user_id = ? AND role IN ('owner', 'admin')";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$projectId, $userId]);
        
        return $stmt->fetch() !== false;
    }
    
    /**
     * Archive project
     */
    public function archiveProject(int $projectId, int $userId): array
    {
        return $this->updateProject($projectId, ['status' => self::STATUS_ARCHIVED], $userId);
    }
    
    /**
     * Complete project
     */
    public function completeProject(int $projectId, int $userId): array
    {
        return $this->updateProject($projectId, ['status' => self::STATUS_COMPLETED], $userId);
    }
    
    /**
     * Delete project with all related data
     */
    public function deleteProject(int $projectId, int $userId): array
    {
        try {
            $project = $this->findById($projectId);
            
            if (!$project) {
                return ['success' => false, 'message' => 'Projet non trouvé'];
            }
            
            // Only owner can delete project
            if ($project['owner_id'] != $userId) {
                return ['success' => false, 'message' => 'Seul le propriétaire peut supprimer le projet'];
            }
            
            // Delete related data
            $this->db->prepare("DELETE FROM project_members WHERE project_id = ?")->execute([$projectId]);
            $this->db->prepare("DELETE FROM task_tags WHERE task_id IN (SELECT id FROM tasks WHERE project_id = ?)")->execute([$projectId]);
            $this->db->prepare("DELETE FROM tasks WHERE project_id = ?")->execute([$projectId]);
            $this->db->prepare("DELETE FROM tags WHERE project_id = ?")->execute([$projectId]);
            
            return $this->delete($projectId);
            
        } catch (Exception $e) {
            error_log("Delete project error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Create project_members table if it doesn't exist
     */
    private function createProjectMembersTableIfNotExists(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS project_members (
            id INT(11) UNSIGNED NOT NULL AUTO_INCREMENT,
            project_id INT(11) UNSIGNED NOT NULL,
            user_id INT(11) UNSIGNED NOT NULL,
            role ENUM('owner', 'admin', 'member') DEFAULT 'member',
            joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY unique_member (project_id, user_id),
            KEY idx_project (project_id),
            KEY idx_user (user_id),
            CONSTRAINT fk_pm_project FOREIGN KEY (project_id) REFERENCES projects (id) ON DELETE CASCADE,
            CONSTRAINT fk_pm_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        
        $this->db->exec($sql);
    }
    
    /**
     * Validate project data
     */
    public function validateProjectData(array $data): array
    {
        $errors = [];
        
        // Name validation
        if (isset($data['name'])) {
            if (empty($data['name'])) {
                $errors['name'] = 'Le nom est obligatoire';
            } elseif (strlen($data['name']) > 100) {
                $errors['name'] = 'Le nom ne peut pas dépasser 100 caractères';
            }
        }
        
        // Status validation
        if (isset($data['status'])) {
            $validStatuses = [self::STATUS_ACTIVE, self::STATUS_ARCHIVED, self::STATUS_COMPLETED];
            if (!in_array($data['status'], $validStatuses)) {
                $errors['status'] = 'Statut invalide';
            }
        }
        
        // Color validation
        if (isset($data['color']) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $data['color'])) {
            $errors['color'] = 'Format de couleur invalide (utilisez #RRGGBB)';
        }
        
        // Date validation
        if (isset($data['start_date']) && !empty($data['start_date'])) {
            if (!strtotime($data['start_date'])) {
                $errors['start_date'] = 'Format de date de début invalide';
            }
        }
        
        if (isset($data['end_date']) && !empty($data['end_date'])) {
            if (!strtotime($data['end_date'])) {
                $errors['end_date'] = 'Format de date de fin invalide';
            }
        }
        
        // Check if end date is after start date
        if (isset($data['start_date']) && isset($data['end_date']) && 
            !empty($data['start_date']) && !empty($data['end_date'])) {
            if (strtotime($data['end_date']) < strtotime($data['start_date'])) {
                $errors['end_date'] = 'La date de fin doit être postérieure à la date de début';
            }
        }
        
        return $errors;
    }
}
