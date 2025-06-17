<?php
namespace TaskManager\Services;

class ValidationService {
    private static $errors = [];
    
    public static function validate($data, $rules) {
        self::$errors = [];
        
        foreach ($rules as $field => $fieldRules) {
            $value = $data[$field] ?? null;
            
            if (is_string($fieldRules)) {
                $fieldRules = explode('|', $fieldRules);
            }
            
            foreach ($fieldRules as $rule) {
                self::applyRule($field, $value, $rule, $data);
            }
        }
        
        return empty(self::$errors);
    }
    
    public static function getErrors() {
        return self::$errors;
    }
    
    private static function applyRule($field, $value, $rule, $allData) {
        $parameters = [];
        
        if (strpos($rule, ':') !== false) {
            list($rule, $parameterStr) = explode(':', $rule, 2);
            $parameters = explode(',', $parameterStr);
        }
        
        switch ($rule) {
            case 'required':
                if (empty($value) && $value !== '0') {
                    self::addError($field, 'Le champ est requis');
                }
                break;
                
            case 'email':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    self::addError($field, 'Le format email est invalide');
                }
                break;
                
            case 'min':
                $min = (int)$parameters[0];
                if (!empty($value) && strlen($value) < $min) {
                    self::addError($field, "Minimum $min caractères requis");
                }
                break;
                
            case 'max':
                $max = (int)$parameters[0];
                if (!empty($value) && strlen($value) > $max) {
                    self::addError($field, "Maximum $max caractères autorisés");
                }
                break;
                
            case 'numeric':
                if (!empty($value) && !is_numeric($value)) {
                    self::addError($field, 'Doit être un nombre');
                }
                break;
                
            case 'integer':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_INT)) {
                    self::addError($field, 'Doit être un entier');
                }
                break;
                
            case 'alpha':
                if (!empty($value) && !ctype_alpha($value)) {
                    self::addError($field, 'Doit contenir uniquement des lettres');
                }
                break;
                
            case 'alpha_num':
                if (!empty($value) && !ctype_alnum($value)) {
                    self::addError($field, 'Doit contenir uniquement des lettres et chiffres');
                }
                break;
                
            case 'in':
                if (!empty($value) && !in_array($value, $parameters)) {
                    $allowed = implode(', ', $parameters);
                    self::addError($field, "Valeur autorisée: $allowed");
                }
                break;
                
            case 'url':
                if (!empty($value) && !filter_var($value, FILTER_VALIDATE_URL)) {
                    self::addError($field, 'URL invalide');
                }
                break;
                
            case 'date':
                if (!empty($value) && !strtotime($value)) {
                    self::addError($field, 'Date invalide');
                }
                break;
                
            case 'confirmed':
                $confirmField = $field . '_confirmation';
                if (!empty($value) && $value !== ($allData[$confirmField] ?? null)) {
                    self::addError($field, 'Les mots de passe ne correspondent pas');
                }
                break;
        }
    }
    
    private static function addError($field, $message) {
        if (!isset(self::$errors[$field])) {
            self::$errors[$field] = [];
        }
        self::$errors[$field][] = $message;
    }
    
    public static function sanitize($data, $rules = []) {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $value = trim($value);
                $value = htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
            }
            
            $sanitized[$key] = $value;
        }
        
        return $sanitized;
    }
}