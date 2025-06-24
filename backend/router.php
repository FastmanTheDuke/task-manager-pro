<?php
/**
 * Router pour le serveur de développement PHP
 * 
 * Ce fichier gère le routage pour php -S
 * Toutes les requêtes API sont redirigées vers index.php
 */

// Debug en mode développement
$debugMode = true;

if ($debugMode) {
    error_log("Router: " . $_SERVER['REQUEST_METHOD'] . " " . $_SERVER['REQUEST_URI']);
}

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Nettoyer l'URI
$uri = '/' . trim($uri, '/');

if ($debugMode) {
    error_log("Router: Cleaned URI = " . $uri);
}

// Routes API - toutes les requêtes commençant par /api
if (strpos($uri, '/api') === 0) {
    if ($debugMode) {
        error_log("Router: API route detected, loading index.php");
    }
    require_once __DIR__ . '/index.php';
    return true;
}

// Route racine - rediriger vers l'API info
if ($uri === '/' || $uri === '') {
    if ($debugMode) {
        error_log("Router: Root route, redirecting to API");
    }
    // Simuler une requête vers /api
    $_SERVER['REQUEST_URI'] = '/api';
    require_once __DIR__ . '/index.php';
    return true;
}

// Fichiers statiques existants
$filePath = __DIR__ . $uri;
if (file_exists($filePath) && is_file($filePath)) {
    if ($debugMode) {
        error_log("Router: Serving static file: " . $filePath);
    }
    // Laisser PHP servir le fichier
    return false;
}

// Fichiers PHP spécifiques
if (pathinfo($uri, PATHINFO_EXTENSION) === 'php') {
    $phpFile = __DIR__ . $uri;
    if (file_exists($phpFile)) {
        if ($debugMode) {
            error_log("Router: Serving PHP file: " . $phpFile);
        }
        require_once $phpFile;
        return true;
    }
}

// Par défaut, tout le reste va vers l'API
if ($debugMode) {
    error_log("Router: Default route, loading index.php");
}
require_once __DIR__ . '/index.php';
return true;
