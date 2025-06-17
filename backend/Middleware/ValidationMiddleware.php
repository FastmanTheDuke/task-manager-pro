<?php
namespace TaskManager\Middleware;

use TaskManager\Services\ResponseService as Response;
use TaskManager\Services\ValidationService;

class ValidationMiddleware
{
    /**
     * Validate request data against rules
     */
    public static function validate(array $rules): array
    {
        $data = self::getRequestData();
        
        if (empty($data)) {
            Response::error('Aucune donnée fournie', 400);
        }
        
        // Use new ValidationService
        if (!ValidationService::validate($data, $rules)) {
            Response::error('Erreur de validation', 422, ValidationService::getErrors());
        }
        
        return $data;
    }
    
    /**
     * Get request data (JSON or form data)
     */
    private static function getRequestData(): array
    {
        $contentType = $_SERVER['CONTENT_TYPE'] ?? '';
        
        if (strpos($contentType, 'application/json') !== false) {
            $input = file_get_contents('php://input');
            $data = json_decode($input, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                Response::error('JSON invalide: ' . json_last_error_msg(), 400);
            }
            
            return $data ?: [];
        }
        
        // Form data (POST, PUT, etc.)
        return $_POST ?: [];
    }
    
    /**
     * Validate specific field with custom rules
     */
    public static function validateField(string $field, $value, array $rules): bool
    {
        return ValidationService::validate([$field => $value], [$field => $rules]);
    }
    
    /**
     * Sanitize input data
     */
    public static function sanitize(array $data): array
    {
        return ValidationService::sanitize($data);
    }
    
    /**
     * Validate file upload
     */
    public static function validateFile(string $fieldName, array $rules = []): ?array
    {
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
            
            Response::error($errors[$file['error']] ?? 'Erreur de téléchargement', 400);
        }
        
        // Validate file size
        $maxSize = $rules['max_size'] ?? 10485760; // 10MB default
        if ($file['size'] > $maxSize) {
            Response::error('Le fichier est trop volumineux', 400);
        }
        
        // Validate file type
        if (isset($rules['allowed_types'])) {
            $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($extension, $rules['allowed_types'])) {
                Response::error('Type de fichier non autorisé', 400);
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
            Response::error('Type MIME du fichier non valide', 400);
        }
        
        return $file;
    }
    
    /**
     * Validate required fields
     */
    public static function requireFields(array $data, array $requiredFields): void
    {
        $missing = [];
        
        foreach ($requiredFields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }
        
        if (!empty($missing)) {
            Response::error('Champs obligatoires manquants: ' . implode(', ', $missing), 400);
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
        $d = \DateTime::createFromFormat($format, $date);
        return $d && $d->format($format) === $date;
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
}