<?php
/**
 * Router principal pour le serveur de développement PHP
 * 
 * Ce fichier redirige toutes les requêtes vers le backend
 * Usage: php -S localhost:8000 router.php (depuis la racine du projet)
 */

// Debug mode
$debugMode = true;

if ($debugMode) {
    error_log("Root Router: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI']);
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Nettoyer l'URI
$uri = '/' . trim($uri, '/');

if ($debugMode) {
    error_log("Root Router: Cleaned URI = " . $uri);
}

// Toutes les requêtes API ou routes principales vont vers le backend
if (strpos($uri, '/api') === 0 || $uri === '/' || $uri === '') {
    if ($debugMode) {
        error_log("Root Router: Delegating to backend router");
    }
    // Déléguer au router du backend
    require_once __DIR__ . '/backend/router.php';
    return true;
}

// Servir les fichiers statiques du frontend (si nécessaire)
$frontendFile = __DIR__ . '/frontend/build' . $uri;
if (file_exists($frontendFile) && is_file($frontendFile)) {
    if ($debugMode) {
        error_log("Root Router: Serving frontend static file: " . $frontendFile);
    }
    return false; // Laisser PHP servir le fichier
}

// Fichiers statiques du backend
$backendFile = __DIR__ . '/backend' . $uri;
if (file_exists($backendFile) && is_file($backendFile)) {
    if ($debugMode) {
        error_log("Root Router: Serving backend static file: " . $backendFile);
    }
    return false; // Laisser PHP servir le fichier
}

// Par défaut, déléguer au backend
if ($debugMode) {
    error_log("Root Router: Default delegation to backend");
}
require_once __DIR__ . '/backend/router.php';
return true;
