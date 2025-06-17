<?php
namespace TaskManager\Utils;

class Validator {
    private $data;
    private $errors = [];
    
    public function __construct($data) {
        $this->data = $data;
    }
    
    public function validateField($field, $rule, $value = null) {
        $fieldValue = $this->data[$field] ?? null;
        
        switch ($rule) {
            case 'required':
                if (empty($fieldValue) && $fieldValue !== '0') {
                    return "Le champ $field est obligatoire";
                }
                break;
                
            case 'email':
                if ($fieldValue && !filter_var($fieldValue, FILTER_VALIDATE_EMAIL)) {
                    return "Le champ $field doit être une adresse email valide";
                }
                break;
                
            case 'min':
                if ($fieldValue && strlen($fieldValue) < $value) {
                    return "Le champ $field doit contenir au moins $value caractères";
                }
                break;
                
            case 'max':
                if ($fieldValue && strlen($fieldValue) > $value) {
                    return "Le champ $field ne doit pas dépasser $value caractères";
                }
                break;
                
            case 'numeric':
                if ($fieldValue && !is_numeric($fieldValue)) {
                    return "Le champ $field doit être numérique";
                }
                break;
                
            case 'integer':
                if ($fieldValue && !filter_var($fieldValue, FILTER_VALIDATE_INT)) {
                    return "Le champ $field doit être un nombre entier";
                }
                break;
                
            case 'date':
                if ($fieldValue && !strtotime($fieldValue)) {
                    return "Le champ $field doit être une date valide";
                }
                break;
                
            case 'in':
                if ($fieldValue && !in_array($fieldValue, $value)) {
                    return "Le champ $field doit être parmi : " . implode(', ', $value);
                }
                break;
                
            case 'unique':
                // Nécessite une vérification en base de données
                // Implémenté dans les modèles
                break;
                
            case 'confirmed':
                $confirmField = $field . '_confirmation';
                if ($fieldValue !== ($this->data[$confirmField] ?? null)) {
                    return "Le champ $field doit être confirmé";
                }
                break;
                
            case 'regex':
                if ($fieldValue && !preg_match($value, $fieldValue)) {
                    return "Le champ $field n'est pas au bon format";
                }
                break;
                
            case 'boolean':
                if ($fieldValue !== null && !in_array($fieldValue, [true, false, 1, 0, '1', '0'], true)) {
                    return "Le champ $field doit être vrai ou faux";
                }
                break;
                
            case 'array':
                if ($fieldValue && !is_array($fieldValue)) {
                    return "Le champ $field doit être un tableau";
                }
                break;
                
            case 'json':
                if ($fieldValue && !$this->isJson($fieldValue)) {
                    return "Le champ $field doit être du JSON valide";
                }
                break;
        }
        
        return true;
    }
    
    public function validate($rules) {
        foreach ($rules as $field => $fieldRules) {
            if (!is_array($fieldRules)) {
                $fieldRules = [$fieldRules];
            }
            
            foreach ($fieldRules as $rule) {
                if (is_string($rule)) {
                    $ruleName = $rule;
                    $ruleValue = null;
                } else {
                    $ruleName = $rule[0];
                    $ruleValue = $rule[1] ?? null;
                }
                
                $result = $this->validateField($field, $ruleName, $ruleValue);
                
                if ($result !== true) {
                    if (!isset($this->errors[$field])) {
                        $this->errors[$field] = [];
                    }
                    $this->errors[$field][] = $result;
                }
            }
        }
        
        return empty($this->errors);
    }
    
    public function getErrors() {
        return $this->errors;
    }
    
    public function getData() {
        return $this->data;
    }
    
    public function getSafeData($fields) {
        $safeData = [];
        
        foreach ($fields as $field) {
            if (isset($this->data[$field])) {
                $safeData[$field] = $this->sanitize($this->data[$field]);
            }
        }
        
        return $safeData;
    }
    
    private function sanitize($value) {
        if (is_string($value)) {
            return htmlspecialchars(trim($value), ENT_QUOTES, 'UTF-8');
        }
        
        if (is_array($value)) {
            return array_map([$this, 'sanitize'], $value);
        }
        
        return $value;
    }
    
    private function isJson($string) {
        json_decode($string);
        return json_last_error() === JSON_ERROR_NONE;
    }
}