<?php
namespace TaskManager\Middleware;

use TaskManager\Config\App;

class CorsMiddleware {
    public static function handle() {
        // Vérifier si on est dans un contexte HTTP (pas CLI)
        if (php_sapi_name() === 'cli' || !isset($_SERVER['REQUEST_METHOD'])) {
            return true; // Ignorer CORS en CLI
        }
        
        $corsConfig = App::get('cors');
        
        // Récupérer l'origine de la requête
        $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
        
        // Vérifier si l'origine est autorisée
        if (in_array('*', $corsConfig['allowed_origins']) || in_array($origin, $corsConfig['allowed_origins'])) {
            header("Access-Control-Allow-Origin: $origin");
        }
        
        // Définir les autres headers CORS
        header('Access-Control-Allow-Methods: ' . implode(', ', $corsConfig['allowed_methods']));
        header('Access-Control-Allow-Headers: ' . implode(', ', $corsConfig['allowed_headers']));
        
        if ($corsConfig['allow_credentials']) {
            header('Access-Control-Allow-Credentials: true');
        }
        
        header('Access-Control-Max-Age: ' . $corsConfig['max_age']);
        
        // Si c'est une requête OPTIONS, retourner une réponse vide
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            http_response_code(200);
            exit();
        }
        
        return true;
    }
}
