<?php
namespace TaskManager\Models;

use TaskManager\Database\Connection;
use PDO;
use Exception;

abstract class BaseModel
{
    protected PDO $db;
    protected string $table;
    protected string $primaryKey = 'id';
    protected array $fillable = [];
    protected array $timestamps = ['created_at', 'updated_at'];
    
    public function __construct()
    {
        $this->db = Connection::getInstance();
    }
    
    /**
     * Find a record by ID
     */
    public function findById($id): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE {$this->primaryKey} = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Find all records with optional conditions
     */
    public function findAll(array $conditions = [], array $options = []): array
    {
        $sql = "SELECT * FROM {$this->table}";
        $params = [];
        
        // Add WHERE conditions
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
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
            $sql .= " ORDER BY {$options['order_by']}";
            if (isset($options['order_dir'])) {
                $sql .= " {$options['order_dir']}";
            }
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
     * Create a new record
     */
    public function create(array $data): array
    {
        try {
            // Filter only fillable fields
            $filteredData = $this->filterFillable($data);
            
            // Add timestamps
            if (in_array('created_at', $this->timestamps)) {
                $filteredData['created_at'] = date('Y-m-d H:i:s');
            }
            if (in_array('updated_at', $this->timestamps)) {
                $filteredData['updated_at'] = date('Y-m-d H:i:s');
            }
            
            $fields = array_keys($filteredData);
            $placeholders = ':' . implode(', :', $fields);
            
            $sql = "INSERT INTO {$this->table} (" . implode(', ', $fields) . ") VALUES ({$placeholders})";
            
            $stmt = $this->db->prepare($sql);
            
            if ($stmt->execute($filteredData)) {
                $id = $this->db->lastInsertId();
                return [
                    'success' => true,
                    'id' => $id,
                    'data' => $this->findById($id)
                ];
            }
            
            return ['success' => false, 'message' => 'Failed to create record'];
            
        } catch (Exception $e) {
            error_log("Create error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Update a record
     */
    public function update($id, array $data): array
    {
        try {
            // Filter only fillable fields
            $filteredData = $this->filterFillable($data);
            
            // Add updated timestamp
            if (in_array('updated_at', $this->timestamps)) {
                $filteredData['updated_at'] = date('Y-m-d H:i:s');
            }
            
            $fields = array_keys($filteredData);
            $setClause = implode(' = ?, ', $fields) . ' = ?';
            
            $sql = "UPDATE {$this->table} SET {$setClause} WHERE {$this->primaryKey} = ?";
            
            $values = array_values($filteredData);
            $values[] = $id;
            
            $stmt = $this->db->prepare($sql);
            
            if ($stmt->execute($values)) {
                return [
                    'success' => true,
                    'data' => $this->findById($id)
                ];
            }
            
            return ['success' => false, 'message' => 'Failed to update record'];
            
        } catch (Exception $e) {
            error_log("Update error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Delete a record
     */
    public function delete($id): array
    {
        try {
            $sql = "DELETE FROM {$this->table} WHERE {$this->primaryKey} = ?";
            $stmt = $this->db->prepare($sql);
            
            if ($stmt->execute([$id])) {
                return ['success' => true, 'message' => 'Record deleted successfully'];
            }
            
            return ['success' => false, 'message' => 'Failed to delete record'];
            
        } catch (Exception $e) {
            error_log("Delete error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Filter data to only include fillable fields
     */
    protected function filterFillable(array $data): array
    {
        if (empty($this->fillable)) {
            return $data;
        }
        
        return array_intersect_key($data, array_flip($this->fillable));
    }
    
    /**
     * Count records with optional conditions
     */
    public function count(array $conditions = []): int
    {
        $sql = "SELECT COUNT(*) as count FROM {$this->table}";
        $params = [];
        
        if (!empty($conditions)) {
            $whereClause = [];
            foreach ($conditions as $field => $value) {
                $whereClause[] = "{$field} = ?";
                $params[] = $value;
            }
            $sql .= " WHERE " . implode(' AND ', $whereClause);
        }
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        
        $result = $stmt->fetch();
        return (int)$result['count'];
    }
}
