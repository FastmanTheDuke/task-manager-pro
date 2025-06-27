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
                
                // Check if field is nullable
                $isNullable = in_array('nullable', $fieldRules);
                
                // If field is nullable and empty, skip other validations
                if ($isNullable && self::isEmpty($value)) {
                    continue;
                }
                
                foreach ($fieldRules as $rule) {
                    if (empty($rule) || $rule === 'nullable') continue;
                    self::applyRule($field, $value, $rule, $data, $isNullable);
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
    private static function applyRule($field, $value, $rule, $allData, $isNullable = false) {
        try {
            $parameters = [];
            
            // Parse rule and parameters
            if (strpos($rule, ':') !== false) {
                list($rule, $parameterStr) = explode(':', $rule, 2);
                $parameters = explode(',', $parameterStr);
            }
            
            $rule = trim($rule);
            
            // Si le champ est nullable et vide, ne pas valider sauf pour required
            if ($isNullable && self::isEmpty($value) && $rule !== 'required') {
                return;
            }
            
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
                    if (!self::isEmpty($value)) {
                        if (!self::isValidDate($value)) {
                            self::addError($field, 'Date invalide');
                        }
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
                    // Validation améliorée pour les booléens après preprocessing
                    if (!self::isEmpty($value) && !self::isValidBoolean($value)) {
                        self::addError($field, 'Doit être un booléen');
                    }
                    break;
                    
                case 'nullable':
                    // Cette règle est gérée en amont, ne rien faire ici
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
     * Validate boolean value (including after preprocessing)
     */
    private static function isValidBoolean($value) {
        // Après preprocessing, on devrait avoir un vrai booléen
        if (is_bool($value)) {
            return true;
        }
        
        // Mais on accepte aussi les valeurs qui peuvent être converties
        if (is_string($value)) {
            $lowValue = strtolower($value);
            return in_array($lowValue, ['true', 'false', '1', '0', 'yes', 'no', 'on', 'off']);
        }
        
        if (is_numeric($value)) {
            return in_array($value, [0, 1]);
        }
        
        return false;
    }
    
    /**
     * Validate date value with better parsing
     */
    private static function isValidDate($value) {
        if (empty($value) || $value === null) {
            return false;
        }
        
        // Try different date formats
        $formats = [
            'Y-m-d',          // 2024-12-31
            'Y-m-d H:i:s',    // 2024-12-31 23:59:59
            'Y/m/d',          // 2024/12/31
            'd/m/Y',          // 31/12/2024
            'd-m-Y',          // 31-12-2024
            'Y-m-d\TH:i:s',   // ISO format
            'Y-m-d\TH:i:s\Z', // ISO with Z
        ];
        
        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date && $date->format($format) === $value) {
                return true;
            }
        }
        
        // Fallback: try strtotime
        $timestamp = strtotime($value);
        return $timestamp !== false && $timestamp !== -1;
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
    
    /**
     * Helper method to check if a field has a specific rule
     */
    public static function hasRule($rules, $ruleName) {
        if (is_string($rules)) {
            $rules = explode('|', $rules);
        }
        
        foreach ($rules as $rule) {
            if (strpos($rule, ':') !== false) {
                list($rule, $params) = explode(':', $rule, 2);
            }
            if (trim($rule) === $ruleName) {
                return true;
            }
        }
        
        return false;
    }
}
