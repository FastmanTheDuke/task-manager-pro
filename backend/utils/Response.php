<?php
namespace TaskManager\Utils;

class Response {
    public static function json($data, $statusCode = 200) {
        http_response_code($statusCode);
        header('Content-Type: application/json; charset=utf-8');
        
        echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit();
    }
    
    public static function success($message = 'Succès', $data = null, $statusCode = 200) {
        $response = [
            'success' => true,
            'message' => $message
        ];
        
        if ($data !== null) {
            $response['data'] = $data;
        }
        
        self::json($response, $statusCode);
    }
    
    public static function error($message = 'Erreur', $statusCode = 400, $errors = null) {
        $response = [
            'success' => false,
            'message' => $message
        ];
        
        if ($errors !== null) {
            $response['errors'] = $errors;
        }
        
        self::json($response, $statusCode);
    }
    
    public static function paginated($data, $total, $page, $limit) {
        $totalPages = ceil($total / $limit);
        
        $response = [
            'success' => true,
            'data' => $data,
            'pagination' => [
                'total' => $total,
                'page' => $page,
                'limit' => $limit,
                'total_pages' => $totalPages,
                'has_previous' => $page > 1,
                'has_next' => $page < $totalPages
            ]
        ];
        
        self::json($response);
    }
    
    public static function noContent() {
        http_response_code(204);
        exit();
    }
    
    public static function redirect($url, $statusCode = 302) {
        header("Location: $url", true, $statusCode);
        exit();
    }
    
    public static function file($filepath, $filename = null) {
        if (!file_exists($filepath)) {
            self::error('Fichier non trouvé', 404);
        }
        
        if ($filename === null) {
            $filename = basename($filepath);
        }
        
        $mimeType = mime_content_type($filepath);
        $filesize = filesize($filepath);
        
        header('Content-Type: ' . $mimeType);
        header('Content-Length: ' . $filesize);
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Cache-Control: private');
        
        readfile($filepath);
        exit();
    }
}