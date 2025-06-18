<?php
namespace TaskManager\Models;

use Exception;

class User extends BaseModel
{
    protected string $table = 'users';
    protected array $fillable = [
        'username',
        'email',
        'password',
        'first_name',
        'last_name',
        'avatar',
        'role',
        'status',
        'theme',
        'language',
        'timezone'
    ];
    
    const ROLE_USER = 'user';
    const ROLE_ADMIN = 'admin';
    const ROLE_MANAGER = 'manager';
    
    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_SUSPENDED = 'suspended';
    
    /**
     * Create user with password hashing
     */
    public function createUser(array $data): array
    {
        try {
            // Validate required fields
            if (empty($data['email']) || empty($data['password'])) {
                return ['success' => false, 'message' => 'Email and password are required'];
            }
            
            // Check if email already exists
            if ($this->emailExists($data['email'])) {
                return ['success' => false, 'message' => 'Email already exists'];
            }
            
            // Check if username already exists (if provided)
            if (!empty($data['username']) && $this->usernameExists($data['username'])) {
                return ['success' => false, 'message' => 'Username already exists'];
            }
            
            // Generate username if not provided
            if (empty($data['username'])) {
                $data['username'] = $this->generateUsername($data['email']);
            }
            
            // Hash password
            $data['password'] = password_hash($data['password'], PASSWORD_DEFAULT);
            
            // Set default values
            $data['role'] = $data['role'] ?? self::ROLE_USER;
            $data['status'] = $data['status'] ?? self::STATUS_ACTIVE;
            $data['theme'] = $data['theme'] ?? 'light';
            $data['language'] = $data['language'] ?? 'fr';
            $data['timezone'] = $data['timezone'] ?? 'Europe/Paris';
            
            return $this->create($data);
            
        } catch (Exception $e) {
            error_log("Create user error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Authenticate user (legacy method - only by email)
     */
    public function authenticate(string $email, string $password): ?array
    {
        try {
            $user = $this->findByEmail($email);
            
            if (!$user) {
                return null;
            }
            
            if (!password_verify($password, $user['password'])) {
                return null;
            }
            
            if ($user['status'] !== self::STATUS_ACTIVE) {
                return null;
            }
            
            // Update last login
            $this->update($user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);
            
            // Remove password from returned data
            unset($user['password']);
            
            return $user;
            
        } catch (Exception $e) {
            error_log("Authentication error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Flexible authentication - supports both email and username
     */
    public function authenticateByLogin(string $login, string $password): ?array
    {
        try {
            // Try to find user by email first (if it contains @)
            if (filter_var($login, FILTER_VALIDATE_EMAIL)) {
                $user = $this->findByEmail($login);
            } else {
                // Otherwise, try to find by username
                $user = $this->findByUsername($login);
            }
            
            if (!$user) {
                return null;
            }
            
            if (!password_verify($password, $user['password'])) {
                return null;
            }
            
            if ($user['status'] !== self::STATUS_ACTIVE) {
                return null;
            }
            
            // Update last login
            $this->update($user['id'], ['last_login_at' => date('Y-m-d H:i:s')]);
            
            // Remove password from returned data
            unset($user['password']);
            
            return $user;
            
        } catch (Exception $e) {
            error_log("Flexible authentication error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Find user by email
     */
    public function findByEmail(string $email): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE email = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$email]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Find user by username
     */
    public function findByUsername(string $username): ?array
    {
        $sql = "SELECT * FROM {$this->table} WHERE username = ? LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$username]);
        
        $result = $stmt->fetch();
        return $result ?: null;
    }
    
    /**
     * Check if email exists
     */
    public function emailExists(string $email): bool
    {
        return $this->findByEmail($email) !== null;
    }
    
    /**
     * Check if username exists
     */
    public function usernameExists(string $username): bool
    {
        return $this->findByUsername($username) !== null;
    }
    
    /**
     * Generate unique username from email
     */
    private function generateUsername(string $email): string
    {
        $baseUsername = strtolower(explode('@', $email)[0]);
        $baseUsername = preg_replace('/[^a-z0-9_]/', '', $baseUsername);
        
        $username = $baseUsername;
        $counter = 1;
        
        while ($this->usernameExists($username)) {
            $username = $baseUsername . $counter;
            $counter++;
        }
        
        return $username;
    }
    
    /**
     * Update user password
     */
    public function updatePassword(int $userId, string $oldPassword, string $newPassword): array
    {
        try {
            $user = $this->findById($userId);
            
            if (!$user) {
                return ['success' => false, 'message' => 'User not found'];
            }
            
            if (!password_verify($oldPassword, $user['password'])) {
                return ['success' => false, 'message' => 'Current password is incorrect'];
            }
            
            $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
            
            return $this->update($userId, ['password' => $hashedPassword]);
            
        } catch (Exception $e) {
            error_log("Update password error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Update user profile (excluding password)
     */
    public function updateProfile(int $userId, array $data): array
    {
        try {
            // Remove password and sensitive fields from profile update
            unset($data['password'], $data['role'], $data['status']);
            
            // Validate email uniqueness if being changed
            if (isset($data['email'])) {
                $currentUser = $this->findById($userId);
                if ($currentUser && $data['email'] !== $currentUser['email']) {
                    if ($this->emailExists($data['email'])) {
                        return ['success' => false, 'message' => 'Email already exists'];
                    }
                }
            }
            
            // Validate username uniqueness if being changed
            if (isset($data['username'])) {
                $currentUser = $this->findById($userId);
                if ($currentUser && $data['username'] !== $currentUser['username']) {
                    if ($this->usernameExists($data['username'])) {
                        return ['success' => false, 'message' => 'Username already exists'];
                    }
                }
            }
            
            return $this->update($userId, $data);
            
        } catch (Exception $e) {
            error_log("Update profile error: " . $e->getMessage());
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
    
    /**
     * Get user with tasks statistics
     */
    public function getUserWithStats(int $userId): ?array
    {
        $user = $this->findById($userId);
        
        if (!$user) {
            return null;
        }
        
        // Remove password
        unset($user['password']);
        
        // Get task statistics
        $taskModel = new Task();
        $user['task_stats'] = $taskModel->getStatistics($userId);
        
        return $user;
    }
    
    /**
     * Search users by name or email
     */
    public function searchUsers(string $query, int $limit = 10): array
    {
        $searchTerm = "%{$query}%";
        
        $sql = "SELECT id, username, email, first_name, last_name, avatar, role, status 
                FROM {$this->table} 
                WHERE (username LIKE ? OR email LIKE ? OR first_name LIKE ? OR last_name LIKE ?) 
                AND status = ?
                ORDER BY username ASC 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([$searchTerm, $searchTerm, $searchTerm, $searchTerm, self::STATUS_ACTIVE, $limit]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Get active users for team collaboration
     */
    public function getActiveUsers(int $limit = 50): array
    {
        $sql = "SELECT id, username, email, first_name, last_name, avatar, role
                FROM {$this->table} 
                WHERE status = ? 
                ORDER BY username ASC 
                LIMIT ?";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute([self::STATUS_ACTIVE, $limit]);
        
        return $stmt->fetchAll();
    }
    
    /**
     * Validate user data
     */
    public function validateUserData(array $data, bool $isUpdate = false): array
    {
        $errors = [];
        
        // Email validation
        if (!$isUpdate || isset($data['email'])) {
            if (empty($data['email'])) {
                $errors['email'] = 'Email is required';
            } elseif (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                $errors['email'] = 'Invalid email format';
            }
        }
        
        // Password validation (only for creation or password change)
        if (!$isUpdate && empty($data['password'])) {
            $errors['password'] = 'Password is required';
        } elseif (isset($data['password']) && strlen($data['password']) < 6) {
            $errors['password'] = 'Password must be at least 6 characters';
        }
        
        // Username validation
        if (isset($data['username'])) {
            if (strlen($data['username']) < 3) {
                $errors['username'] = 'Username must be at least 3 characters';
            } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $data['username'])) {
                $errors['username'] = 'Username can only contain letters, numbers, and underscores';
            }
        }
        
        // Role validation
        if (isset($data['role'])) {
            $validRoles = [self::ROLE_USER, self::ROLE_ADMIN, self::ROLE_MANAGER];
            if (!in_array($data['role'], $validRoles)) {
                $errors['role'] = 'Invalid role';
            }
        }
        
        // Status validation
        if (isset($data['status'])) {
            $validStatuses = [self::STATUS_ACTIVE, self::STATUS_INACTIVE, self::STATUS_SUSPENDED];
            if (!in_array($data['status'], $validStatuses)) {
                $errors['status'] = 'Invalid status';
            }
        }
        
        return $errors;
    }
    
    /**
     * Get user's public profile (safe for external use)
     */
    public function getPublicProfile(int $userId): ?array
    {
        $user = $this->findById($userId);
        
        if (!$user || $user['status'] !== self::STATUS_ACTIVE) {
            return null;
        }
        
        return [
            'id' => $user['id'],
            'username' => $user['username'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'avatar' => $user['avatar']
        ];
    }
}
