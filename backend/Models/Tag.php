<?php

namespace TaskManager\Models; // Assurez-vous que le namespace est TaskManager\Models

use TaskManager\Database\Connection; // Si vous utilisez la connexion directement
use PDO;

class Tag extends BaseModel { // Assurez-vous que "extends BaseModel" est présent
    protected $table = 'tags';
    
    public function __construct() {
        parent::__construct();
    }

    /**
     * Create a new tag
     */
    public function createTag($data) {
        try {
            $sql = "INSERT INTO tags (name, description, color, created_by, created_at) 
                    VALUES (:name, :description, :color, :created_by, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':name' => $data['name'],
                ':description' => $data['description'],
                ':color' => $data['color'],
                ':created_by' => $data['created_by']
            ]);
            
            if (!$result) {
                throw new \Exception('Failed to create tag');
            }
            
            $tagId = $this->db->lastInsertId();
            
            return [
                'success' => true,
                'data' => $this->getTagById($tagId, $data['created_by'])['data']
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error creating tag: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get tags for a specific user with optional filtering
     */
    public function getTagsForUser($userId, $search = '', $sortBy = 'name', $sortOrder = 'asc', $page = 1, $limit = 50) {
        try {
            $offset = ($page - 1) * $limit;
            
            // Build WHERE clause
            $whereConditions = ['t.created_by = :user_id'];
            $params = [':user_id' => $userId];
            
            if (!empty($search)) {
                $whereConditions[] = "(t.name LIKE :search OR t.description LIKE :search)";
                $params[':search'] = '%' . $search . '%';
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            // Validate sort parameters
            $validSorts = ['name', 'created_at', 'updated_at', 'color'];
            $sortBy = in_array($sortBy, $validSorts) ? $sortBy : 'name';
            $sortOrder = in_array(strtolower($sortOrder), ['asc', 'desc']) ? strtoupper($sortOrder) : 'ASC';
            
            // Main query with task count
            $sql = "SELECT t.*, 
                           (SELECT COUNT(*) FROM task_tags tt WHERE tt.tag_id = t.id) as tasks_count,
                           u.username as created_by_username
                    FROM tags t
                    LEFT JOIN users u ON t.created_by = u.id
                    WHERE $whereClause
                    ORDER BY t.$sortBy $sortOrder
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            
            // Bind parameters
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $tags = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get total count for pagination
            $countSql = "SELECT COUNT(*) as total FROM tags t WHERE $whereClause";
            $countStmt = $this->db->prepare($countSql);
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            return [
                'success' => true,
                'data' => [
                    'tags' => $tags,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => (int)$total,
                        'pages' => ceil($total / $limit)
                    ]
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching tags: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get a specific tag by ID
     */
    public function getTagById($tagId, $userId = null) {
        try {
            $sql = "SELECT t.*, 
                           (SELECT COUNT(*) FROM task_tags tt WHERE tt.tag_id = t.id) as tasks_count,
                           u.username as created_by_username
                    FROM tags t
                    LEFT JOIN users u ON t.created_by = u.id
                    WHERE t.id = :tag_id";
            
            $params = [':tag_id' => $tagId];
            
            // If userId is provided, only return tags created by that user
            if ($userId !== null) {
                $sql .= " AND t.created_by = :user_id";
                $params[':user_id'] = $userId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $tag = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$tag) {
                return [
                    'success' => false,
                    'message' => 'Tag not found or access denied'
                ];
            }
            
            // Get recent tasks using this tag
            $tag['recent_tasks'] = $this->getRecentTasksForTag($tagId, 5);
            
            return [
                'success' => true,
                'data' => $tag
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching tag: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update a tag
     */
    public function updateTag($tagId, $data, $userId) {
        try {
            $updateFields = [];
            $params = [':tag_id' => $tagId, ':user_id' => $userId];
            
            $allowedFields = ['name', 'description', 'color'];
            
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
            
            $sql = "UPDATE tags SET " . implode(', ', $updateFields) . " 
                    WHERE id = :tag_id AND created_by = :user_id";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);
            
            if (!$result || $stmt->rowCount() === 0) {
                return [
                    'success' => false,
                    'message' => 'Tag not found or no changes made'
                ];
            }
            
            return [
                'success' => true,
                'data' => $this->getTagById($tagId, $userId)['data']
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error updating tag: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete a tag
     */
    public function deleteTag($tagId, $userId) {
        try {
            $this->db->beginTransaction();
            
            // Remove tag from all tasks first
            $this->db->prepare("DELETE FROM task_tags WHERE tag_id = ?")->execute([$tagId]);
            
            // Delete the tag
            $stmt = $this->db->prepare("DELETE FROM tags WHERE id = ? AND created_by = ?");
            $result = $stmt->execute([$tagId, $userId]);
            
            if (!$result || $stmt->rowCount() === 0) {
                $this->db->rollBack();
                return [
                    'success' => false,
                    'message' => 'Tag not found or access denied'
                ];
            }
            
            $this->db->commit();
            
            return ['success' => true];
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => 'Error deleting tag: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Check if a tag name already exists for a user
     */
    public function tagExistsForUser($name, $userId, $excludeTagId = null) {
        try {
            $sql = "SELECT id FROM tags WHERE name = :name AND created_by = :user_id";
            $params = [':name' => $name, ':user_id' => $userId];
            
            if ($excludeTagId !== null) {
                $sql .= " AND id != :exclude_id";
                $params[':exclude_id'] = $excludeTagId;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            return $stmt->rowCount() > 0;
            
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get all tags for a user (simplified for dropdowns)
     */
    public function getTagsForDropdown($userId) {
        try {
            $sql = "SELECT id, name, color FROM tags 
                    WHERE created_by = :user_id 
                    ORDER BY name ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Get recent tasks for a specific tag
     */
    public function getRecentTasksForTag($tagId, $limit = 5) {
        try {
            $sql = "SELECT t.id, t.title, t.status, t.priority, t.created_at,
                           u.username as assigned_to_username
                    FROM tasks t
                    INNER JOIN task_tags tt ON t.id = tt.task_id
                    LEFT JOIN users u ON t.assigned_to = u.id
                    WHERE tt.tag_id = ?
                    ORDER BY t.updated_at DESC
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$tagId, $limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Add tag to a task
     */
    public function addTagToTask($taskId, $tagId) {
        try {
            $sql = "INSERT IGNORE INTO task_tags (task_id, tag_id, created_at) 
                    VALUES (?, ?, NOW())";
            
            $stmt = $this->db->prepare($sql);
            return $stmt->execute([$taskId, $tagId]);
            
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Remove tag from a task
     */
    public function removeTagFromTask($taskId, $tagId) {
        try {
            $stmt = $this->db->prepare("DELETE FROM task_tags WHERE task_id = ? AND tag_id = ?");
            return $stmt->execute([$taskId, $tagId]);
            
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Get tags for a specific task
     */
    public function getTagsForTask($taskId) {
        try {
            $sql = "SELECT t.id, t.name, t.color, t.description
                    FROM tags t
                    INNER JOIN task_tags tt ON t.id = tt.tag_id
                    WHERE tt.task_id = ?
                    ORDER BY t.name ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$taskId]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            return [];
        }
    }

    /**
     * Set tags for a task (replaces existing tags)
     */
    public function setTagsForTask($taskId, $tagIds) {
        try {
            $this->db->beginTransaction();
            
            // Remove existing tags
            $this->db->prepare("DELETE FROM task_tags WHERE task_id = ?")->execute([$taskId]);
            
            // Add new tags
            if (!empty($tagIds)) {
                $sql = "INSERT INTO task_tags (task_id, tag_id, created_at) VALUES ";
                $values = [];
                $params = [];
                
                foreach ($tagIds as $index => $tagId) {
                    $values[] = "(?, ?, NOW())";
                    $params[] = $taskId;
                    $params[] = $tagId;
                }
                
                $sql .= implode(', ', $values);
                $stmt = $this->db->prepare($sql);
                $stmt->execute($params);
            }
            
            $this->db->commit();
            return true;
            
        } catch (\Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    /**
     * Get tag statistics for a user
     */
    public function getTagStats($userId) {
        try {
            $sql = "SELECT 
                        COUNT(*) as total_tags,
                        (SELECT COUNT(*) FROM task_tags tt 
                         INNER JOIN tags t ON tt.tag_id = t.id 
                         WHERE t.created_by = ?) as total_tag_usages,
                        (SELECT COUNT(DISTINCT tt.task_id) FROM task_tags tt 
                         INNER JOIN tags t ON tt.tag_id = t.id 
                         WHERE t.created_by = ?) as tagged_tasks
                    FROM tags 
                    WHERE created_by = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $userId, $userId]);
            
            return $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            return [
                'total_tags' => 0,
                'total_tag_usages' => 0,
                'tagged_tasks' => 0
            ];
        }
    }

    /**
     * Get most used tags for a user
     */
    public function getMostUsedTags($userId, $limit = 10) {
        try {
            $sql = "SELECT t.id, t.name, t.color, COUNT(tt.task_id) as usage_count
                    FROM tags t
                    LEFT JOIN task_tags tt ON t.id = tt.tag_id
                    WHERE t.created_by = ?
                    GROUP BY t.id, t.name, t.color
                    ORDER BY usage_count DESC, t.name ASC
                    LIMIT ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([$userId, $limit]);
            
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
            
        } catch (\Exception $e) {
            return [];
        }
    }
}
?>
