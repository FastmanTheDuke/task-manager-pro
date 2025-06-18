<?php
/**
 * Router pour le serveur de développement PHP
 * 
 * Ce fichier gère le routage pour php -S
 * Toutes les requêtes API sont redirigées vers index.php
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Si c'est une requête vers l'API, on redirige vers index.php
if (strpos($uri, '/api/') === 0) {
    // Charger index.php pour traiter l'API
    require_once __DIR__ . '/index.php';
    return true;
}

// Pour les autres fichiers, vérifier s'ils existent
if ($uri !== '/' && file_exists(__DIR__ . $uri)) {
    // Servir le fichier statique
    return false;
}

// Par défaut, charger index.php
require_once __DIR__ . '/index.php';
return true;
