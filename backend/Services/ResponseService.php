<?php
namespace TaskManager\Services;

class ResponseService {
    public static function json($data, $status = 200, $headers = []) {
        http_response_code($status);
        
        // Headers par défaut
        header('Content-Type: application/json; charset=utf-8');
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: DENY');
        header('X-XSS-Protection: 1; mode=block');
        
        // Headers personnalisés
        foreach ($headers as $name => $value) {
            header("$name: $value");
        }
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }
    
    public static function success($data = null, $message = 'Succès', $status = 200) {
        $response = [
            'success' => true,
            'message' => $message,
            'timestamp' => date('c')
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        self::json($response, $status);
    }
    
    public static function error($message = 'Erreur', $status = 400, $errors = null) {
        // Ensure message is always a string
        if (is_array($message)) {
            $message = 'Erreur de validation: ' . implode(', ', array_values($message));
        } elseif (!is_string($message)) {
            $message = 'Erreur interne';
        }
        
        $response = [
            'success' => false,
            'message' => $message,
            'timestamp' => date('c')
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        // Log l'erreur si c'est une erreur serveur
        if ($status >= 500) {
            error_log("HTTP $status: $message");
            if ($errors) {
                error_log("Errors: " . json_encode($errors));
            }
        }
        
        self::json($response, $status);
    }
    
    public static function notFound($message = 'Ressource non trouvée') {
        self::error($message, 404);
    }
    
    public static function unauthorized($message = 'Non autorisé') {
        self::error($message, 401);
    }
    
    public static function forbidden($message = 'Accès interdit') {
        self::error($message, 403);
    }
    
    public static function validation($errors, $message = 'Erreur de validation') {
        self::error($message, 422, $errors);
    }
    
    public static function paginated($data, $total, $page, $perPage, $message = 'Données récupérées avec succès') {
        $response = [
            'success' => true,
            'message' => $message,
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'per_page' => $perPage,
                'total_pages' => ceil($total / $perPage),
                'has_next' => ($page * $perPage) < $total,
                'has_prev' => $page > 1
            ],
            'timestamp' => date('c')
        ];
        
        self::json($response);
    }
}
