<?php
/**
 * Alias pour ResponseService
 * Permet de maintenir la compatibilité avec les imports existants
 */

namespace TaskManager\Utils;

use TaskManager\Services\ResponseService;

class Response {
    public static function json($data, $status = 200, $headers = []) {
        return ResponseService::json($data, $status, $headers);
    }
    
    public static function success($data = null, $message = 'Succès', $status = 200) {
        return ResponseService::success($data, $message, $status);
    }
    
    public static function error($message = 'Erreur', $status = 400, $errors = null) {
        return ResponseService::error($message, $status, $errors);
    }
    
    public static function notFound($message = 'Ressource non trouvée') {
        return ResponseService::notFound($message);
    }
    
    public static function unauthorized($message = 'Non autorisé') {
        return ResponseService::unauthorized($message);
    }
    
    public static function forbidden($message = 'Accès interdit') {
        return ResponseService::forbidden($message);
    }
    
    public static function validation($errors, $message = 'Erreur de validation') {
        return ResponseService::validation($errors, $message);
    }
    
    public static function paginated($data, $total, $page, $perPage, $message = 'Données récupérées avec succès') {
        return ResponseService::paginated($data, $total, $page, $perPage, $message);
    }
}