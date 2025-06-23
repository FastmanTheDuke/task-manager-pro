<?php
namespace TaskManager\Services;

class ValidationService {
    private static $errors = [];
    
    /**
     * Main validation method
     */
    public static function validate($data, $rules) {
        self::$errors = [];
        
        try {
            foreach ($rules as $field => $fieldRules) {
                $value = $data[$field] ?? null;
                
                // Convert rules to array if it's a string
                if (is_string($fieldRules)) {
                    $fieldRules = explode('|', $fieldRules);
                }
                
                if (!is_array($fieldRules)) {
                    $fieldRules = [$fieldRules];
                }
                
                foreach ($fieldRules as $rule) {
                    if (empty($rule)) continue;
                    self::applyRule($field, $value, $rule, $data);
                }
            }
            
            return empty(self::$errors);
            
        } catch (\Exception $e) {
            error_log('ValidationService error: ' . $e->getMessage());
            self::addError('validation', 'Erreur de validation: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get validation errors
     */
    public static function getErrors() {
        return self::$errors;
    }
    
    /**
     * Clear errors
     */
    public static function clearErrors() {
        self::$errors = [];
    }
    
    /**
     * Apply a single validation rule
     */
    private static function applyRule($field, $value, $rule, $allData) {
        try {
            $parameters = [];
            
            // Parse rule and parameters
            if (strpos($rule, ':') !== false) {
                list($rule, $parameterStr) = explode(':', $rule, 2);
                $parameters = explode(',', $parameterStr);
            }
            
            $rule = trim($rule);
            
            switch ($rule) {
                case 'required':
                    if (self::isEmpty($value)) {
                        self::addError($field, 'Le champ est requis');
                    }
                    break;
                    
                case 'email':
                    if (!self::isEmpty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                        self::addError($field, 'Le format email est invalide');
                    }
                    break;
                    
                case 'min':
                    $min = isset($parameters[0]) ? (int)$parameters[0] : 0;
                    if (!self::isEmpty($value) && strlen((string)$value) < $min) {
                        self::addError($field, "Minimum {$min} caractères requis");
                    }
                    break;
                    
                case 'max':
                    $max = isset($parameters[0]) ? (int)$parameters[0] : 255;
                    if (!self::isEmpty($value) && strlen((string)$value) > $max) {
                        self::addError($field, "Maximum {$max} caractères autorisés");
                    }
                    break;
                    
                case 'numeric':
                    if (!self::isEmpty($value) && !is_numeric($value)) {
                        self::addError($field, 'Doit être un nombre');
                    }
                    break;
                    
                case 'integer':
                    if (!self::isEmpty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
                        self::addError($field, 'Doit être un entier');
                    }
                    break;
                    
                case 'alpha':
                    if (!self::isEmpty($value) && !ctype_alpha((string)$value)) {
                        self::addError($field, 'Doit contenir uniquement des lettres');
                    }
                    break;
                    
                case 'alpha_num':
                    if (!self::isEmpty($value) && !ctype_alnum((string)$value)) {
                        self::addError($field, 'Doit contenir uniquement des lettres et chiffres');
                    }
                    break;
                    
                case 'in':
                    if (!self::isEmpty($value) && !in_array($value, $parameters)) {
                        $allowed = implode(', ', $parameters);
                        self::addError($field, "Valeur autorisée: {$allowed}");
                    }
                    break;
                    
                case 'url':
                    if (!self::isEmpty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                        self::addError($field, 'URL invalide');
                    }
                    break;
                    
                case 'date':
                    if (!self::isEmpty($value) && !strtotime($value)) {
                        self::addError($field, 'Date invalide');
                    }
                    break;
                    
                case 'confirmed':
                    $confirmField = $field . '_confirmation';
                    if (!self::isEmpty($value) && $value !== ($allData[$confirmField] ?? null)) {
                        self::addError($field, 'Les mots de passe ne correspondent pas');
                    }
                    break;
                    
                case 'string':
                    if (!self::isEmpty($value) && !is_string($value)) {
                        self::addError($field, 'Doit être une chaîne de caractères');
                    }
                    break;
                    
                case 'array':
                    if (!self::isEmpty($value) && !is_array($value)) {
                        self::addError($field, 'Doit être un tableau');
                    }
                    break;
                    
                case 'boolean':
                    if (!self::isEmpty($value) && !is_bool($value)) {
                        self::addError($field, 'Doit être un booléen');
                    }
                    break;
                    
                default:
                    // Custom validation rule - ignore if not recognized
                    break;
            }
            
        } catch (\Exception $e) {
            error_log("ValidationService rule '{$rule}' error: " . $e->getMessage());
            self::addError($field, 'Erreur de validation');
        }
    }
    
    /**
     * Check if value is empty
     */
    private static function isEmpty($value) {
        if ($value === null || $value === '') {
            return true;
        }
        
        if (is_array($value) && empty($value)) {
            return true;
        }
        
        if (is_string($value) && trim($value) === '') {
            return true;
        }
        
        return false;
    }
    
    /**
     * Add validation error
     */
    private static function addError($field, $message) {
        if (!isset(self::$errors[$field])) {
            self::$errors[$field] = [];
        }
        
        // Ensure message is always a string
        if (is_array($message)) {
            $message = implode(', ', $message);
        } elseif (!is_string($message)) {
            $message = (string)$message;
        }
        
        self::$errors[$field][] = $message;
    }
    
    /**
     * Sanitize input data
     */
    public static function sanitize($data, $rules = []) {
        try {
            $sanitized = [];
            
            foreach ($data as $key => $value) {
                if (is_string($value)) {
                    $value = trim($value);
                    $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
                } elseif (is_array($value)) {
                    $value = self::sanitize($value); // Recursive sanitization
                }
                
                $sanitized[$key] = $value;
            }
            
            return $sanitized;
            
        } catch (\Exception $e) {
            error_log('ValidationService sanitize error: ' . $e->getMessage());
            return $data; // Return original data if sanitization fails
        }
    }
    
    /**
     * Validate and sanitize data in one call
     */
    public static function validateAndSanitize($data, $rules) {
        $isValid = self::validate($data, $rules);
        $sanitizedData = self::sanitize($data);
        
        return [
            'valid' => $isValid,
            'data' => $sanitizedData,
            'errors' => self::getErrors()
        ];
    }
    
    /**
     * Custom validation for specific fields
     */
    public static function validatePassword($password) {
        $errors = [];
        
        if (strlen($password) < 6) {
            $errors[] = 'Le mot de passe doit contenir au moins 6 caractères';
        }
        
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins une majuscule';
        }
        
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins une minuscule';
        }
        
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Le mot de passe doit contenir au moins un chiffre';
        }
        
        return $errors;
    }
    
    /**
     * Validate username format
     */
    public static function validateUsername($username) {
        $errors = [];
        
        if (strlen($username) < 3) {
            $errors[] = 'Le nom d\'utilisateur doit contenir au moins 3 caractères';
        }
        
        if (strlen($username) > 50) {
            $errors[] = 'Le nom d\'utilisateur ne peut pas dépasser 50 caractères';
        }
        
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'Le nom d\'utilisateur ne peut contenir que des lettres, chiffres et underscores';
        }
        
        return $errors;
    }
}
