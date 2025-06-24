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
        // CORRECTION : S'assurer que le message est toujours une string
        $cleanMessage = self::sanitizeMessage($message);
        
        $response = [
            'success' => false,
            'message' => $cleanMessage,
            'timestamp' => date('c')
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        // Log l'erreur si c'est une erreur serveur
        if ($status >= 500) {
            error_log("HTTP $status: " . $cleanMessage);
            if ($errors) {
                error_log("Errors: " . json_encode($errors));
            }
        }
        
        self::json($response, $status);
    }
    
    /**
     * MÉTHODE CORRIGÉE : Nettoie et convertit le message en string
     */
    private static function sanitizeMessage($message) {
        if (is_string($message)) {
            return $message;
        }
        
        if (is_array($message)) {
            // CORRECTION : Gestion récursive des arrays imbriqués
            $flattenedMessages = [];
            
            foreach ($message as $key => $value) {
                if (is_array($value)) {
                    // Si c'est un array imbriqué, on le traite récursivement
                    foreach ($value as $nestedMessage) {
                        if (is_string($nestedMessage)) {
                            $flattenedMessages[] = $nestedMessage;
                        } elseif (is_array($nestedMessage)) {
                            $flattenedMessages[] = implode(', ', array_filter($nestedMessage, 'is_string'));
                        } else {
                            $flattenedMessages[] = (string)$nestedMessage;
                        }
                    }
                } elseif (is_string($value)) {
                    $flattenedMessages[] = $value;
                } else {
                    $flattenedMessages[] = (string)$value;
                }
            }
            
            if (!empty($flattenedMessages)) {
                return 'Erreur de validation: ' . implode('; ', $flattenedMessages);
            } else {
                return 'Erreur de validation inconnue';
            }
        }
        
        if (is_object($message)) {
            // Si c'est un objet (comme une Exception), on utilise sa représentation string
            if (method_exists($message, '__toString')) {
                return (string) $message;
            }
            if (method_exists($message, 'getMessage')) {
                return $message->getMessage();
            }
            return 'Erreur objet: ' . get_class($message);
        }
        
        if (is_bool($message)) {
            return $message ? 'true' : 'false';
        }
        
        if (is_numeric($message)) {
            return (string) $message;
        }
        
        // Pour tout autre type, on force la conversion
        return 'Erreur interne: ' . gettype($message);
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
        // CORRECTION : S'assurer que $message est une string propre
        $cleanMessage = self::sanitizeMessage($message);
        self::error($cleanMessage, 422, $errors);
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
