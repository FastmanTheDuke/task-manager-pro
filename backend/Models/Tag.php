<?php
namespace TaskManager\Models;

use Exception;

class Tag extends BaseModel
{
    protected string $table = 'tags';
    protected array $fillable = [
        'name',
        'color',
        'icon',
        'user_id',
        'project_id',
        'is_global'
    ];
    
    /**
     * Get tags for a specific user or global tags
     */
    public function getUserTags(int $userId, int $projectId = null): array
    {
        $sql = "SELECT * FROM {$this->table} 
                WHERE (user_id = ? OR is_global = 1)";
        
        $params = [$userId];
        
        if ($projectId) {
            $sql .= " AND (project_id = ? OR project_id IS NULL)";
            $params[] = $projectId;
        } else {
            $sql .= " AND project_id IS NULL";
        }
        
        $sql .= " ORDER BY name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Create a new tag with validation
     */
    public function createTag(array $data, int $userId): array
    {
        try {
            // Validate required fields
            if (empty($data['name'])) {
                return ['success' => false, 'message' => 'Le nom du tag est obligatoire'];
            }
            
            // Check if tag already exists for this user/project
            if ($this->tagExists($data['name'], $userId, $data['project_id'] ?? null)) {
                return ['success' => false, 'message' => 'Ce tag existe déjà'];
            }
            
            // Set default values
            $data['user_id'] = $userId;
            $data['color'] = $data['color'] ?? '#cccccc';
            $data['is_global'] = $data['is_global'] ?? false;
            
            // Only admins can create global tags
            if ($data['is_global'] && !$this->isUserAdmin($userId)) {
                $data['is_global'] = false;
            }
            
            return $this->create($data);
            
        } catch (Exception $e) {
            error_log("Create tag error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Check if a tag exists for a user/project
     */
    public function tagExists(string $name, int $userId, int $projectId = null): bool
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table} 
                WHERE name = ? AND user_id = ?";
        
        $params = [$name, $userId];
        
        if ($projectId) {
            $sql .= " AND project_id = ?";
            $params[] = $projectId;
        } else {
            $sql .= " AND project_id IS NULL";
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $result = $stmt->fetch();
        return (int)$result['count'] > 0;
    }
    
    /**
     * Get tags assigned to a specific task
     */
    public function getTaskTags(int $taskId): array
    {
        $sql = "SELECT t.* FROM {$this->table} t
                INNER JOIN task_tags tt ON t.id = tt.tag_id
                WHERE tt.task_id = ?
                ORDER BY t.name ASC";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$taskId]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Assign tags to a task
     */
    public function assignTagsToTask(int $taskId, array $tagIds): array
    {
        try {
            // First, remove existing tags
            $this->removeTagsFromTask($taskId);
            
            // Then assign new tags
            if (!empty($tagIds)) {
                $sql = "INSERT INTO task_tags (task_id, tag_id) VALUES (?, ?)";
                $stmt = $this->db->prepare($sql);
                
                foreach ($tagIds as $tagId) {
                    $stmt->execute([$taskId, $tagId]);
                }
            }
            
            return ['success' => true, 'message' => 'Tags assignés avec succès'];
            
        } catch (Exception $e) {
            error_log("Assign tags error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Remove all tags from a task
     */
    public function removeTagsFromTask(int $taskId): array
    {
        try {
            $sql = "DELETE FROM task_tags WHERE task_id = ?";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$taskId]);
            
            return ['success' => true, 'message' => 'Tags supprimés'];
            
        } catch (Exception $e) {
            error_log("Remove tags error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get popular tags (most used)
     */
    public function getPopularTags(int $userId, int $limit = 10): array
    {
        $sql = "SELECT t.*, COUNT(tt.tag_id) as usage_count
                FROM {$this->table} t
                LEFT JOIN task_tags tt ON t.id = tt.tag_id
                LEFT JOIN tasks ts ON tt.task_id = ts.id
                WHERE (t.user_id = ? OR t.is_global = 1)
                AND (ts.creator_id = ? OR ts.assignee_id = ? OR ts.creator_id IS NULL)
                GROUP BY t.id
                ORDER BY usage_count DESC, t.name ASC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $userId, $userId, $limit]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Search tags by name
     */
    public function searchTags(string $query, int $userId, int $limit = 20): array
    {
        $searchTerm = "%{$query}%";
        
        $sql = "SELECT * FROM {$this->table}
                WHERE (user_id = ? OR is_global = 1)
                AND name LIKE ?
                ORDER BY 
                    CASE WHEN name LIKE ? THEN 1 ELSE 2 END,
                    name ASC
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId, $searchTerm, "{$query}%", $limit]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Update tag with validation
     */
    public function updateTag(int $tagId, array $data, int $userId): array
    {
        try {
            $tag = $this->findById($tagId);
            
            if (!$tag) {
                return ['success' => false, 'message' => 'Tag non trouvé'];
            }
            
            // Check if user owns this tag or if it's global and user is admin
            if ($tag['user_id'] != $userId && !($tag['is_global'] && $this->isUserAdmin($userId))) {
                return ['success' => false, 'message' => 'Accès non autorisé'];
            }
            
            // Check for duplicate name if name is being changed
            if (isset($data['name']) && $data['name'] !== $tag['name']) {
                if ($this->tagExists($data['name'], $tag['user_id'], $tag['project_id'])) {
                    return ['success' => false, 'message' => 'Ce nom de tag existe déjà'];
                }
            }
            
            // Only admins can modify global status
            if (isset($data['is_global']) && !$this->isUserAdmin($userId)) {
                unset($data['is_global']);
            }
            
            return $this->update($tagId, $data);
            
        } catch (Exception $e) {
            error_log("Update tag error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Delete tag with validation
     */
    public function deleteTag(int $tagId, int $userId): array
    {
        try {
            $tag = $this->findById($tagId);
            
            if (!$tag) {
                return ['success' => false, 'message' => 'Tag non trouvé'];
            }
            
            // Check if user owns this tag or if it's global and user is admin
            if ($tag['user_id'] != $userId && !($tag['is_global'] && $this->isUserAdmin($userId))) {
                return ['success' => false, 'message' => 'Accès non autorisé'];
            }
            
            // Remove tag from all tasks first
            $this->db->prepare("DELETE FROM task_tags WHERE tag_id = ?")->execute([$tagId]);
            
            return $this->delete($tagId);
            
        } catch (Exception $e) {
            error_log("Delete tag error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Check if user is admin (simplified check)
     */
    private function isUserAdmin(int $userId): bool
    {
        $sql = "SELECT role FROM users WHERE id = ?";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        
        $user = $stmt->fetch();
        return $user && $user['role'] === 'admin';
    }
    
    /**
     * Validate tag data
     */
    public function validateTagData(array $data): array
    {
        $errors = [];
        
        // Name validation
        if (empty($data['name'])) {
            $errors['name'] = 'Le nom est obligatoire';
        } elseif (strlen($data['name']) > 50) {
            $errors['name'] = 'Le nom ne peut pas dépasser 50 caractères';
        } elseif (!preg_match('/^[a-zA-Z0-9\s\-_éèêëàâäôöîïûüÿç]+$/u', $data['name'])) {
            $errors['name'] = 'Le nom contient des caractères non autorisés';
        }
        
        // Color validation
        if (isset($data['color']) && !preg_match('/^#[0-9A-Fa-f]{6}$/', $data['color'])) {
            $errors['color'] = 'Format de couleur invalide (utilisez #RRGGBB)';
        }
        
        // Icon validation
        if (isset($data['icon']) && strlen($data['icon']) > 50) {
            $errors['icon'] = 'L\'icône ne peut pas dépasser 50 caractères';
        }
        
        return $errors;
    }
    
    /**
     * Get tag statistics
     */
    public function getTagStatistics(int $userId): array
    {
        $sql = "SELECT 
                    COUNT(*) as total_tags,
                    COUNT(CASE WHEN is_global = 1 THEN 1 END) as global_tags,
                    COUNT(CASE WHEN is_global = 0 THEN 1 END) as personal_tags
                FROM {$this->table}
                WHERE user_id = ? OR is_global = 1";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$userId]);
        
        return $stmt->fetch() ?: [];
    }
}
