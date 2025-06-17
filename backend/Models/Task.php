<?php
namespace TaskManager\Models;

use Exception;

class Task extends BaseModel
{
    protected string $table = 'tasks';
    protected array $fillable = [
        'title',
        'description',
        'project_id',
        'creator_id',
        'assignee_id',
        'status',
        'priority',
        'due_date',
        'start_date',
        'estimated_hours',
        'actual_hours',
        'completion_percentage',
        'parent_task_id'
    ];
    
    const STATUS_PENDING = 'pending';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_ARCHIVED = 'archived';
    const STATUS_CANCELLED = 'cancelled';
    
    const PRIORITY_LOW = 'low';
    const PRIORITY_MEDIUM = 'medium';
    const PRIORITY_HIGH = 'high';
    const PRIORITY_URGENT = 'urgent';
    
    /**
     * Get tasks with user information
     */
    public function getTasksWithUsers(array $conditions = [], array $options = []): array
    {
        $sql = "SELECT t.*, 
                       creator.username as creator_name,
                       creator.email as creator_email,
                       assignee.username as assignee_name,
                       assignee.email as assignee_email,
                       p.name as project_name,
                       p.color as project_color
                FROM {$this->table} t
                LEFT JOIN users creator ON t.creator_id = creator.id
                LEFT JOIN users assignee ON t.assignee_id = assignee.id
                LEFT JOIN projects p ON t.project_id = p.id";
        
        $params = [];
        
        // Add WHERE conditions
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                if (strpos($field, '.') === false) {
                    $field = "t.{$field}";
                }
                
                if (is_array($value)) {
                    $placeholders = implode(',', array_fill(0, count($value), '?'));
                    $whereClause[] = "{$field} IN ({$placeholders})";
                    $params = array_merge($params, $value);
                } else {
                    $whereClause[] = "{$field} = ?";
                    $params[] = $value;
                }
            }
            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }
        
        // Add ORDER BY
        if (isset($options['order_by'])) {
            $orderBy = $options['order_by'];
            if (strpos($orderBy, '.') === false) {
                $orderBy = "t.{$orderBy}";
            }
            $sql .= " ORDER BY {$orderBy}";
            if (isset($options['order_dir'])) {
                $sql .= " {$options['order_dir']}";
            }
        } else {
            $sql .= " ORDER BY t.created_at DESC";
        }
        
        // Add LIMIT
        if (isset($options['limit'])) {
            $sql .= " LIMIT {$options['limit']}";
            if (isset($options['offset'])) {
                $sql .= " OFFSET {$options['offset']}";
            }
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get tasks for a specific user (created by or assigned to)
     */
    public function getUserTasks(int $userId, array $filters = [], array $options = []): array
    {
        $conditions = [
            'OR' => [
                't.creator_id' => $userId,
                't.assignee_id' => $userId
            ]
        ];
        
        // Add filters
        if (isset($filters['status'])) {
            $conditions['t.status'] = $filters['status'];
        }
        
        if (isset($filters['priority'])) {
            $conditions['t.priority'] = $filters['priority'];
        }
        
        if (isset($filters['project_id'])) {
            $conditions['t.project_id'] = $filters['project_id'];
        }
        
        if (isset($filters['due_date_from'])) {
            $conditions['t.due_date >='] = $filters['due_date_from'];
        }
        
        if (isset($filters['due_date_to'])) {
            $conditions['t.due_date <='] = $filters['due_date_to'];
        }
        
        return $this->getTasksWithComplexConditions($conditions, $options);
    }
    
    /**
     * Handle complex WHERE conditions including OR clauses
     */
    private function getTasksWithComplexConditions(array $conditions, array $options = []): array
    {
        $sql = "SELECT t.*, 
                       creator.username as creator_name,
                       creator.email as creator_email,
                       assignee.username as assignee_name,
                       assignee.email as assignee_email,
                       p.name as project_name,
                       p.color as project_color
                FROM {$this->table} t
                LEFT JOIN users creator ON t.creator_id = creator.id
                LEFT JOIN users assignee ON t.assignee_id = assignee.id
                LEFT JOIN projects p ON t.project_id = p.id";
        
        $params = [];
        $whereClause = [];
        
        foreach ($conditions as $key => $value) {
            if ($key === 'OR' && is_array($value)) {
                $orConditions = [];
                foreach ($value as $field => $val) {
                    $orConditions[] = "{$field} = ?";
                    $params[] = $val;
                }
                $whereClause[] = "(" . implode(' OR ', $orConditions) . ")";
            } else {
                if (strpos($key, '>=') !== false) {
                    $field = str_replace(' >=', '', $key);
                    $whereClause[] = "{$field} >= ?";
                } elseif (strpos($key, '<=') !== false) {
                    $field = str_replace(' <=', '', $key);
                    $whereClause[] = "{$field} <= ?";
                } else {
                    $whereClause[] = "{$key} = ?";
                }
                $params[] = $value;
            }
        }
        
        if (!empty($whereClause)) {
            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }
        
        // Add ORDER BY
        if (isset($options['order_by'])) {
            $orderBy = $options['order_by'];
            if (strpos($orderBy, '.') === false) {
                $orderBy = "t.{$orderBy}";
            }
            $sql .= " ORDER BY {$orderBy}";
            if (isset($options['order_dir'])) {
                $sql .= " {$options['order_dir']}";
            }
        } else {
            $sql .= " ORDER BY t.created_at DESC";
        }
        
        // Add LIMIT
        if (isset($options['limit'])) {
            $sql .= " LIMIT {$options['limit']}";
            if (isset($options['offset'])) {
                $sql .= " OFFSET {$options['offset']}";
            }
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get task with all related data (tags, comments, time entries)
     */
    public function getTaskDetails(int $taskId): ?array
    {
        $task = $this->getTasksWithUsers(['t.id' => $taskId]);
        
        if (empty($task)) {
            return null;
        }
        
        $task = $task[0];
        
        // Get tags
        $tagsSql = "SELECT tg.id, tg.name, tg.color, tg.icon 
                    FROM task_tags tt 
                    JOIN tags tg ON tt.tag_id = tg.id 
                    WHERE tt.task_id = ?";
        $tagsStmt = $this->db->prepare($tagsSql);
        $tagsStmt->execute([$taskId]);
        $task['tags'] = $tagsStmt->fetchAll();
        
        // Get comments
        $commentsSql = "SELECT c.*, u.username, u.email 
                        FROM comments c 
                        JOIN users u ON c.user_id = u.id 
                        WHERE c.task_id = ? 
                        ORDER BY c.created_at ASC";
        $commentsStmt = $this->db->prepare($commentsSql);
        $commentsStmt->execute([$taskId]);
        $task['comments'] = $commentsStmt->fetchAll();
        
        // Get time entries
        $timeSql = "SELECT te.*, u.username 
                    FROM time_entries te 
                    JOIN users u ON te.user_id = u.id 
                    WHERE te.task_id = ? 
                    ORDER BY te.start_time DESC";
        $timeStmt = $this->db->prepare($timeSql);
        $timeStmt->execute([$taskId]);
        $task['time_entries'] = $timeStmt->fetchAll();
        
        return $task;
    }
    
    /**
     * Update task status and completion percentage
     */
    public function updateStatus(int $taskId, string $status, int $completionPercentage = null): array
    {
        try {
            $data = ['status' => $status];
            
            if ($completionPercentage !== null) {
                $data['completion_percentage'] = max(0, min(100, $completionPercentage));
            }
            
            // If completed, set completion percentage to 100%
            if ($status === self::STATUS_COMPLETED) {
                $data['completion_percentage'] = 100;
            }
            
            return $this->update($taskId, $data);
            
        } catch (Exception $e) {
            error_log("Update status error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get task statistics for dashboard
     */
    public function getStatistics(int $userId = null): array
    {
        $conditions = [];
        if ($userId) {
            $conditions = "WHERE (creator_id = {$userId} OR assignee_id = {$userId})";
        } else {
            $conditions = "";
        }
        
        $sql = "SELECT 
                    COUNT(*) as total_tasks,
                    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_tasks,
                    SUM(CASE WHEN status = 'in_progress' THEN 1 ELSE 0 END) as in_progress_tasks,
                    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_tasks,
                    SUM(CASE WHEN due_date < NOW() AND status != 'completed' THEN 1 ELSE 0 END) as overdue_tasks,
                    AVG(completion_percentage) as avg_completion
                FROM {$this->table} {$conditions}";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        
        return $stmt->fetch() ?: [];
    }
    
    /**
     * Validate task data
     */
    public function validateTaskData(array $data): array
    {
        $errors = [];
        
        // Required fields
        if (empty($data['title'])) {
            $errors['title'] = 'Title is required';
        } elseif (strlen($data['title']) > 200) {
            $errors['title'] = 'Title must be less than 200 characters';
        }
        
        // Status validation
        if (isset($data['status'])) {
            $validStatuses = [self::STATUS_PENDING, self::STATUS_IN_PROGRESS, self::STATUS_COMPLETED, self::STATUS_ARCHIVED, self::STATUS_CANCELLED];
            if (!in_array($data['status'], $validStatuses)) {
                $errors['status'] = 'Invalid status';
            }
        }
        
        // Priority validation
        if (isset($data['priority'])) {
            $validPriorities = [self::PRIORITY_LOW, self::PRIORITY_MEDIUM, self::PRIORITY_HIGH, self::PRIORITY_URGENT];
            if (!in_array($data['priority'], $validPriorities)) {
                $errors['priority'] = 'Invalid priority';
            }
        }
        
        // Date validation
        if (isset($data['due_date']) && !empty($data['due_date'])) {
            if (!strtotime($data['due_date'])) {
                $errors['due_date'] = 'Invalid due date format';
            }
        }
        
        if (isset($data['start_date']) && !empty($data['start_date'])) {
            if (!strtotime($data['start_date'])) {
                $errors['start_date'] = 'Invalid start date format';
            }
        }
        
        // Numeric validation
        if (isset($data['estimated_hours']) && !is_numeric($data['estimated_hours'])) {
            $errors['estimated_hours'] = 'Estimated hours must be a number';
        }
        
        if (isset($data['completion_percentage'])) {
            $percentage = (int)$data['completion_percentage'];
            if ($percentage < 0 || $percentage > 100) {
                $errors['completion_percentage'] = 'Completion percentage must be between 0 and 100';
            }
        }
        
        return $errors;
    }
}
