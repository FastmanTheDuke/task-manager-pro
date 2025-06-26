<?php

namespace TaskManager\Models;

use TaskManager\Database\Connection;
use PDO;

class TimeTracking extends BaseModel {
    
    public function __construct() {
        parent::__construct();
        $this->table = 'time_entries'; // Assigner la valeur dans le constructeur
    }

    /**
     * Start a new timer for a task
     */
    public function startTimer($userId, $taskId, $description = null) {
        try {
            // Check if there's already an active timer
            $activeTimer = $this->getActiveTimer($userId);
            if ($activeTimer['success'] && $activeTimer['data']) {
                return [
                    'success' => false,
                    'message' => 'There is already an active timer. Please stop or pause it first.'
                ];
            }
            
            // Verify task exists and user has access
            $taskModel = new Task();
            $task = $taskModel->findById($taskId);
            if (!$task) {
                return [
                    'success' => false,
                    'message' => 'Task not found or access denied'
                ];
            }
            
            $sql = "INSERT INTO time_entries (user_id, task_id, description, start_time, created_at) 
                    VALUES (:user_id, :task_id, :description, NOW(), NOW())";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':user_id' => $userId,
                ':task_id' => $taskId,
                ':description' => $description
            ]);
            
            if (!$result) {
                throw new \Exception('Failed to start timer');
            }
            
            $entryId = $this->db->lastInsertId();
            
            return [
                'success' => true,
                'data' => $this->getTimeEntryById($entryId, $userId)['data']
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error starting timer: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Stop the active timer
     */
    public function stopTimer($userId) {
        try {
            $activeTimer = $this->getActiveTimer($userId);
            if (!$activeTimer['success'] || !$activeTimer['data']) {
                return [
                    'success' => false,
                    'message' => 'No active timer found'
                ];
            }
            
            $entry = $activeTimer['data'];
            $startTime = new \DateTime($entry['start_time']);
            $now = new \DateTime();
            $duration = $now->getTimestamp() - $startTime->getTimestamp();
            
            $sql = "UPDATE time_entries 
                    SET end_time = NOW(), duration = :duration, updated_at = NOW()
                    WHERE id = :entry_id AND user_id = :user_id";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':duration' => $duration,
                ':entry_id' => $entry['id'],
                ':user_id' => $userId
            ]);
            
            if (!$result) {
                throw new \Exception('Failed to stop timer');
            }
            
            return [
                'success' => true,
                'data' => $this->getTimeEntryById($entry['id'], $userId)['data']
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error stopping timer: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get the active timer for a user
     */
    public function getActiveTimer($userId) {
        try {
            $sql = "SELECT te.*, t.title as task_title
                    FROM time_entries te
                    LEFT JOIN tasks t ON te.task_id = t.id
                    WHERE te.user_id = :user_id AND te.end_time IS NULL
                    ORDER BY te.start_time DESC
                    LIMIT 1";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([':user_id' => $userId]);
            
            $timer = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$timer) {
                return [
                    'success' => true,
                    'data' => null
                ];
            }
            
            // Add task object for frontend compatibility
            $timer['task'] = [
                'id' => $timer['task_id'],
                'title' => $timer['task_title']
            ];
            
            return [
                'success' => true,
                'data' => $timer
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching active timer: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Create a manual time entry
     */
    public function createManualEntry($data) {
        try {
            // Verify task exists and user has access
            $taskModel = new Task();
            $task = $taskModel->findById($data['task_id']);
            if (!$task) {
                return [
                    'success' => false,
                    'message' => 'Task not found or access denied'
                ];
            }
            
            $sql = "INSERT INTO time_entries (user_id, task_id, description, duration, start_time, end_time, created_at) 
                    VALUES (:user_id, :task_id, :description, :duration, :start_time, :end_time, NOW())";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute([
                ':user_id' => $data['user_id'],
                ':task_id' => $data['task_id'],
                ':description' => $data['description'],
                ':duration' => $data['duration'],
                ':start_time' => $data['start_time'],
                ':end_time' => $data['end_time']
            ]);
            
            if (!$result) {
                throw new \Exception('Failed to create time entry');
            }
            
            $entryId = $this->db->lastInsertId();
            
            return [
                'success' => true,
                'data' => $this->getTimeEntryById($entryId, $data['user_id'])['data']
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error creating time entry: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get time entries for a user with filters
     */
    public function getTimeEntries($userId, $filters = [], $page = 1, $limit = 50) {
        try {
            $offset = ($page - 1) * $limit;
            
            // Build WHERE clause
            $whereConditions = ['te.user_id = :user_id'];
            $params = [':user_id' => $userId];
            
            if (!empty($filters['startDate'])) {
                $whereConditions[] = "DATE(te.created_at) >= :start_date";
                $params[':start_date'] = $filters['startDate'];
            }
            
            if (!empty($filters['endDate'])) {
                $whereConditions[] = "DATE(te.created_at) <= :end_date";
                $params[':end_date'] = $filters['endDate'];
            }
            
            if (!empty($filters['taskId'])) {
                $whereConditions[] = "te.task_id = :task_id";
                $params[':task_id'] = $filters['taskId'];
            }
            
            $whereClause = implode(' AND ', $whereConditions);
            
            // Main query
            $sql = "SELECT te.*, 
                           t.title as task_title
                    FROM time_entries te
                    LEFT JOIN tasks t ON te.task_id = t.id
                    WHERE $whereClause
                    ORDER BY te.created_at DESC
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $entries = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get total count
            $countSql = "SELECT COUNT(*) as total FROM time_entries te WHERE $whereClause";
            $countStmt = $this->db->prepare($countSql);
            foreach ($params as $key => $value) {
                $countStmt->bindValue($key, $value);
            }
            $countStmt->execute();
            $total = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Process entries
            foreach ($entries as &$entry) {
                $entry['task'] = [
                    'id' => $entry['task_id'],
                    'title' => $entry['task_title']
                ];
            }
            
            return [
                'success' => true,
                'data' => [
                    'entries' => $entries,
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
                'message' => 'Error fetching time entries: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get a specific time entry by ID
     */
    public function getTimeEntryById($entryId, $userId) {
        try {
            $sql = "SELECT te.*, 
                           t.title as task_title
                    FROM time_entries te
                    LEFT JOIN tasks t ON te.task_id = t.id
                    WHERE te.id = :entry_id AND te.user_id = :user_id";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':entry_id' => $entryId,
                ':user_id' => $userId
            ]);
            
            $entry = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$entry) {
                return [
                    'success' => false,
                    'message' => 'Time entry not found or access denied'
                ];
            }
            
            $entry['task'] = [
                'id' => $entry['task_id'],
                'title' => $entry['task_title']
            ];
            
            return [
                'success' => true,
                'data' => $entry
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching time entry: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Update a time entry
     */
    public function updateTimeEntry($entryId, $data, $userId) {
        try {
            // Check if entry exists and belongs to user
            $entry = $this->getTimeEntryById($entryId, $userId);
            if (!$entry['success']) {
                return [
                    'success' => false,
                    'message' => 'Time entry not found or access denied',
                    'code' => 404
                ];
            }
            
            // Don't allow updating active timers
            if ($entry['data']['end_time'] === null) {
                return [
                    'success' => false,
                    'message' => 'Cannot update active timer. Stop it first.',
                    'code' => 400
                ];
            }
            
            $updateFields = [];
            $params = [':entry_id' => $entryId, ':user_id' => $userId];
            
            $allowedFields = ['duration', 'description'];
            
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
            
            $sql = "UPDATE time_entries SET " . implode(', ', $updateFields) . " 
                    WHERE id = :entry_id AND user_id = :user_id";
            
            $stmt = $this->db->prepare($sql);
            $result = $stmt->execute($params);
            
            if (!$result) {
                throw new \Exception('Failed to update time entry');
            }
            
            return [
                'success' => true,
                'data' => $this->getTimeEntryById($entryId, $userId)['data']
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error updating time entry: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Delete a time entry
     */
    public function deleteTimeEntry($entryId, $userId) {
        try {
            // Check if entry exists and belongs to user
            $entry = $this->getTimeEntryById($entryId, $userId);
            if (!$entry['success']) {
                return [
                    'success' => false,
                    'message' => 'Time entry not found or access denied',
                    'code' => 404
                ];
            }
            
            // Don't allow deleting active timers
            if ($entry['data']['end_time'] === null) {
                return [
                    'success' => false,
                    'message' => 'Cannot delete active timer. Stop it first.',
                    'code' => 400
                ];
            }
            
            $stmt = $this->db->prepare("DELETE FROM time_entries WHERE id = ? AND user_id = ?");
            $result = $stmt->execute([$entryId, $userId]);
            
            if (!$result) {
                throw new \Exception('Failed to delete time entry');
            }
            
            return ['success' => true];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error deleting time entry: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get time tracking statistics
     */
    public function getTimeStats($userId, $startDate, $endDate) {
        try {
            $sql = "SELECT DATE(te.created_at) as date,
                           SUM(te.duration) as total_seconds,
                           COUNT(*) as entries_count,
                           COUNT(DISTINCT te.task_id) as unique_tasks
                    FROM time_entries te
                    WHERE te.user_id = :user_id 
                    AND te.duration IS NOT NULL
                    AND DATE(te.created_at) BETWEEN :start_date AND :end_date
                    GROUP BY DATE(te.created_at)
                    ORDER BY date ASC";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                ':user_id' => $userId,
                ':start_date' => $startDate,
                ':end_date' => $endDate
            ]);
            
            $stats = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            // Get overall totals
            $totalSql = "SELECT SUM(te.duration) as total_seconds,
                               COUNT(*) as total_entries,
                               COUNT(DISTINCT te.task_id) as total_tasks
                        FROM time_entries te
                        WHERE te.user_id = :user_id 
                        AND te.duration IS NOT NULL
                        AND DATE(te.created_at) BETWEEN :start_date AND :end_date";
            
            $totalStmt = $this->db->prepare($totalSql);
            $totalStmt->execute([
                ':user_id' => $userId,
                ':start_date' => $startDate,
                ':end_date' => $endDate
            ]);
            
            $totals = $totalStmt->fetch(PDO::FETCH_ASSOC);
            
            return [
                'success' => true,
                'data' => [
                    'stats' => $stats,
                    'totals' => $totals,
                    'period' => [
                        'start_date' => $startDate,
                        'end_date' => $endDate
                    ]
                ]
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => 'Error fetching time stats: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get total time tracked for a user
     */
    public function getTotalTimeForUser($userId, $startDate = null, $endDate = null) {
        try {
            $sql = "SELECT SUM(duration) as total_seconds FROM time_entries 
                    WHERE user_id = :user_id AND duration IS NOT NULL";
            $params = [':user_id' => $userId];
            
            if ($startDate) {
                $sql .= " AND DATE(created_at) >= :start_date";
                $params[':start_date'] = $startDate;
            }
            
            if ($endDate) {
                $sql .= " AND DATE(created_at) <= :end_date";
                $params[':end_date'] = $endDate;
            }
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['total_seconds'] ?? 0);
            
        } catch (\Exception $e) {
            return 0;
        }
    }
}
?>
