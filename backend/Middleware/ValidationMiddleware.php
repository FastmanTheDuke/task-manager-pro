<?php
namespace TaskManager\Middleware;

use TaskManager\Services\ResponseService;
use TaskManager\Services\ValidationService;

class ValidationMiddleware
{
    /**
     * Validate request data against rules
     */
    public static function validate(array $rules): array
    {
        try {
            $data = self::getRequestData();
            
            if (empty($data)) {
                ResponseService::error('Aucune donnée fournie', 400);
            }
            
            // Préprocesser les données pour normaliser les booléens et autres types
            $data = self::preprocessData($data);
            
            // Debug log pour voir les données après preprocessing
            error_log('ValidationMiddleware - Data after preprocessing: ' . json_encode($data));
            
            // Use ValidationService with better error handling
            if (!ValidationService::validate($data, $rules)) {
                $errors = ValidationService::getErrors();
                
                // Ensure errors is always an array
                if (!is_array($errors)) {
                    $errors = ['validation' => 'Erreur de validation inconnue'];
                }
                
                // Debug log pour voir les erreurs de validation
                error_log('ValidationMiddleware - Validation errors: ' . json_encode($errors));
                
                ResponseService::validation($errors, 'Erreur de validation');
            }
            
            return $data;
            
        } catch (\Exception $e) {
            error_log('ValidationMiddleware error: ' . $e->getMessage());
            ResponseService::error('Erreur de validation: ' . $e->getMessage(), 422);
        }
    }
    
    /**
     * Préprocesser les données pour normaliser les types
     */
    private static function preprocessData(array $data): array
    {
        foreach ($data as $key => $value) {
            // Normaliser les booléens envoyés comme chaînes
            if (is_string($value)) {
                $lowerValue = strtolower($value);
                
                // Conversion booléenne étendue
                if (in_array($lowerValue, ['true', '1', 'yes', 'on'])) {
                    $data[$key] = true;
                } elseif (in_array($lowerValue, ['false', '0', 'no', 'off', ''])) {
                    $data[$key] = false;
                } elseif ($value === 'null' || $value === '') {
                    // Gérer les valeurs null envoyées comme chaînes
                    $data[$key] = null;
                }
            }
            
            // Traiter les tableaux récursivement
            if (is_array($value)) {
                $data[$key] = self::preprocessData($value);
            }
            
            // Gérer les entiers envoyés comme chaînes
            if (is_string($value) && is_numeric($value) && !isset($data[$key])) {
                if (strpos($value, '.') !== false) {
                    $data[$key] = (float)$value;
                } else {
                    $data[$key] = (int)$value;
                }
            }
        }
        
        return $data;
    }
    
    /**
     * Get request data (JSON or form data)
     */
    private static function getRequestData(): array
    {
        try {
            $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
            
            if (strpos($contentType, 'application/json') !== false) {
                $input = file_get_contents('php://input');
                
                if (empty($input)) {
                    return [];
                }
                
                $data = json_decode($input, true);
                
                if (json_last_error() !== JSON_ERROR_NONE) {
                    ResponseService::error('JSON invalide: ' . json_last_error_msg(), 400);
                }
                
                return is_array($data) ? $data : [];
            }
            
            // Form data (POST, PUT, etc.)
            $postData = $_POST ?: [];
            $putData = [];
            
            // Gérer les données PUT/PATCH
            if ($_SERVER['REQUEST_METHOD'] === 'PUT' || $_SERVER['REQUEST_METHOD'] === 'PATCH') {
                parse_str(file_get_contents('php://input'), $putData);
            }
            
            return array_merge($postData, $putData);
            
        } catch (\Exception $e) {
            error_log('getRequestData error: ' . $e->getMessage());
            ResponseService::error('Erreur lors de la lecture des données', 400);
        }
    }
    
    /**
     * Validate specific field with custom rules
     */
    public static function validateField(string $field, $value, array $rules): bool
    {
        try {
            return ValidationService::validate([$field => $value], [$field => $rules]);
        } catch (\Exception $e) {
            error_log('validateField error: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Sanitize input data
     */
    public static function sanitize(array $data): array
    {
        try {
            return ValidationService::sanitize($data);
        } catch (\Exception $e) {
            error_log('sanitize error: ' . $e->getMessage());
            return $data; // Return original data if sanitization fails
        }
    }
    
    /**
     * Validate file upload
     */
    public static function validateFile(string $fieldName, array $rules = []): ?array
    {
        try {
            if (!isset($_FILES[$fieldName])) {
                return null;
            }
            
            $file = $_FILES[$fieldName];
            
            // Check for upload errors
            if ($file['error'] !== UPLOAD_ERR_OK) {
                $errors = [
                    UPLOAD_ERR_INI_SIZE => 'Le fichier dépasse la taille maximale autorisée',
                    UPLOAD_ERR_FORM_SIZE => 'Le fichier dépasse la taille maximale du formulaire',
                    UPLOAD_ERR_PARTIAL => 'Le fichier n\'a été que partiellement téléchargé',
                    UPLOAD_ERR_NO_FILE => 'Aucun fichier n\'a été téléchargé',
                    UPLOAD_ERR_NO_TMP_DIR => 'Dossier temporaire manquant',
                    UPLOAD_ERR_CANT_WRITE => 'Impossible d\'écrire le fichier',
                    UPLOAD_ERR_EXTENSION => 'Extension PHP bloquée'
                ];
                
                $errorMessage = $errors[$file['error']] ?? 'Erreur de téléchargement';
                ResponseService::error($errorMessage, 400);
            }
            
            // Validate file size
            $maxSize = $rules['max_size'] ?? 10485760; // 10MB default
            if ($file['size'] > $maxSize) {
                ResponseService::error('Le fichier est trop volumineux', 400);
            }
            
            // Validate file type
            if (isset($rules['allowed_types'])) {
                $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
                if (!in_array($extension, $rules['allowed_types'])) {
                    ResponseService::error('Type de fichier non autorisé', 400);
                }
            }
            
            // Validate MIME type for security
            $allowedMimes = [
                'jpg' => 'image/jpeg',
                'jpeg' => 'image/jpeg',
                'png' => 'image/png',
                'gif' => 'image/gif',
                'pdf' => 'application/pdf',
                'doc' => 'application/msword',
                'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                'txt' => 'text/plain'
            ];
            
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $expectedMime = $allowedMimes[$extension] ?? null;
            
            if ($expectedMime && $file['type'] !== $expectedMime) {
                ResponseService::error('Type MIME du fichier non valide', 400);
            }
            
            return $file;
            
        } catch (\Exception $e) {
            error_log('validateFile error: ' . $e->getMessage());
            ResponseService::error('Erreur lors de la validation du fichier', 400);
        }
    }
    
    /**
     * Validate required fields
     */
    public static function requireFields(array $data, array $requiredFields): void
    {
        try {
            $missing = [];
            
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || 
                    (is_string($data[$field]) && trim($data[$field]) === '') ||
                    (is_array($data[$field]) && empty($data[$field])) ||
                    $data[$field] === null) {
                    $missing[] = $field;
                }
            }
            
            if (!empty($missing)) {
                ResponseService::error('Champs obligatoires manquants: ' . implode(', ', $missing), 400);
            }
            
        } catch (\Exception $e) {
            error_log('requireFields error: ' . $e->getMessage());
            ResponseService::error('Erreur lors de la validation des champs obligatoires', 400);
        }
    }
    
    /**
     * Validate email format
     */
    public static function validateEmail(string $email): bool
    {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate URL format
     */
    public static function validateUrl(string $url): bool
    {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
    
    /**
     * Validate date format
     */
    public static function validateDate(string $date, string $format = 'Y-m-d'): bool
    {
        try {
            $d = \DateTime::createFromFormat($format, $date);
            return $d && $d->format($format) === $date;
        } catch (\Exception $e) {
            return false;
        }
    }
    
    /**
     * Custom validation for task status
     */
    public static function validateTaskStatus(string $status): bool
    {
        $validStatuses = ['pending', 'in_progress', 'completed', 'archived', 'cancelled'];
        return in_array($status, $validStatuses);
    }
    
    /**
     * Custom validation for task priority
     */
    public static function validateTaskPriority(string $priority): bool
    {
        $validPriorities = ['low', 'medium', 'high', 'urgent'];
        return in_array($priority, $validPriorities);
    }
    
    /**
     * Validation spécifique pour les booléens
     */
    public static function validateBoolean($value): bool
    {
        if (is_bool($value)) {
            return true;
        }
        
        if (is_string($value)) {
            return in_array(strtolower($value), ['true', 'false', '1', '0', 'yes', 'no', 'on', 'off']);
        }
        
        if (is_numeric($value)) {
            return in_array($value, [0, 1]);
        }
        
        return false;
    }
    
    /**
     * Convertir une valeur en booléen
     */
    public static function toBoolean($value): bool
    {
        if (is_bool($value)) {
            return $value;
        }
        
        if (is_string($value)) {
            return in_array(strtolower($value), ['true', '1', 'yes', 'on']);
        }
        
        if (is_numeric($value)) {
            return (bool)(int)$value;
        }
        
        return false;
    }
}
