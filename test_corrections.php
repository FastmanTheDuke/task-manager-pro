<?php
/**
 * Script de test complet pour vérifier les corrections apportées
 * Usage: php test_corrections.php
 */

// Configuration
$baseUrl = 'http://localhost:8000';
$apiUrl = $baseUrl . '/api';

echo "=== Test des corrections apportées ===\n\n";

// Test 1: Vérifier la structure des fichiers
echo "1. Vérification de la structure des fichiers...\n";

$requiredFiles = [
    'backend/Bootstrap.php',
    'backend/api/users/search.php',
    'backend/Middleware/AuthMiddleware.php',
    'backend/Middleware/ValidationMiddleware.php',
    'backend/Middleware/CorsMiddleware.php',
    'backend/Config/JWTManager.php'
];

$missingFiles = [];
foreach ($requiredFiles as $file) {
    if (!file_exists($file)) {
        $missingFiles[] = $file;
    }
}

if (empty($missingFiles)) {
    echo "✓ Tous les fichiers requis sont présents\n";
} else {
    echo "✗ Fichiers manquants: " . implode(', ', $missingFiles) . "\n";
}

// Test 2: Vérifier l'API de santé
echo "\n2. Test de l'API de santé...\n";

$healthUrl = $apiUrl . '/health';
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'Content-Type: application/json',
            'Origin: http://localhost:3000'
        ],
        'timeout' => 10
    ]
]);

$response = @file_get_contents($healthUrl, false, $context);
if ($response !== false) {
    $data = json_decode($response, true);
    if ($data && isset($data['status'])) {
        echo "✓ API de santé accessible\n";
        echo "  Status: " . $data['status'] . "\n";
    } else {
        echo "✗ Réponse API invalide\n";
    }
} else {
    echo "✗ Impossible d'accéder à l'API de santé\n";
}

// Test 3: Test de validation des booléens
echo "\n3. Test de validation des booléens...\n";

// Simuler des données avec différents formats de booléens
$testData = [
    'test_boolean_true' => 'true',
    'test_boolean_false' => 'false',
    'test_boolean_1' => '1',
    'test_boolean_0' => '0',
    'test_number' => '123',
    'test_string' => 'hello'
];

// Test de préprocessing (simulation)
require_once 'backend/Bootstrap.php';

use TaskManager\Middleware\ValidationMiddleware;

$reflection = new ReflectionClass('TaskManager\Middleware\ValidationMiddleware');
$method = $reflection->getMethod('preprocessData');
$method->setAccessible(true);

try {
    $processed = $method->invoke(null, $testData);
    
    if ($processed['test_boolean_true'] === true) {
        echo "✓ Conversion 'true' vers booléen fonctionne\n";
    } else {
        echo "✗ Conversion 'true' vers booléen échoue\n";
    }
    
    if ($processed['test_boolean_false'] === false) {
        echo "✓ Conversion 'false' vers booléen fonctionne\n";
    } else {
        echo "✗ Conversion 'false' vers booléen échoue\n";
    }
    
    if ($processed['test_boolean_1'] === true) {
        echo "✓ Conversion '1' vers booléen fonctionne\n";
    } else {
        echo "✗ Conversion '1' vers booléen échoue\n";
    }
    
    if ($processed['test_boolean_0'] === false) {
        echo "✓ Conversion '0' vers booléen fonctionne\n";
    } else {
        echo "✗ Conversion '0' vers booléen échoue\n";
    }
    
} catch (Exception $e) {
    echo "✗ Erreur lors du test de préprocessing: " . $e->getMessage() . "\n";
}

// Test 4: Test de l'authentification (sans token)
echo "\n4. Test de l'endpoint de recherche d'utilisateurs (sans token)...\n";

$searchUrl = $apiUrl . '/users/search?q=test';
$context = stream_context_create([
    'http' => [
        'method' => 'GET',
        'header' => [
            'Content-Type: application/json',
            'Origin: http://localhost:3000'
        ],
        'timeout' => 10
    ]
]);

$response = @file_get_contents($searchUrl, false, $context);
if ($response !== false) {
    $data = json_decode($response, true);
    if ($data && isset($data['error']) && strpos($data['error'], 'Token') !== false) {
        echo "✓ Authentification requise détectée correctement\n";
    } else {
        echo "✗ Authentification ne fonctionne pas correctement\n";
        echo "  Réponse: " . $response . "\n";
    }
} else {
    // Vérifier si c'est une erreur 401
    $headers = get_headers($searchUrl, 1);
    if (strpos($headers[0], '401') !== false) {
        echo "✓ Authentification requise (401) retournée correctement\n";
    } else {
        echo "✗ Endpoint de recherche inaccessible\n";
    }
}

// Test 5: Test des headers CORS
echo "\n5. Test des headers CORS...\n";

$headers = get_headers($healthUrl, 1);
$corsHeaders = array_filter(array_keys($headers), function($header) {
    return strpos(strtolower($header), 'access-control') !== false;
});

if (!empty($corsHeaders)) {
    echo "✓ Headers CORS présents\n";
    foreach ($corsHeaders as $header) {
        echo "  " . $header . ": " . $headers[$header] . "\n";
    }
} else {
    echo "✗ Headers CORS manquants\n";
}

// Test 6: Test de la connexion à la base de données
echo "\n6. Test de connexion à la base de données...\n";

try {
    use TaskManager\Database\DatabaseManager;
    
    $db = DatabaseManager::getInstance();
    $pdo = $db->getConnection();
    
    if ($pdo) {
        echo "✓ Connexion à la base de données réussie\n";
        
        // Test d'une requête simple
        $stmt = $pdo->query("SELECT 1 as test");
        if ($stmt && $stmt->fetch()) {
            echo "✓ Requête de test réussie\n";
        } else {
            echo "✗ Requête de test échouée\n";
        }
    } else {
        echo "✗ Connexion à la base de données échouée\n";
    }
} catch (Exception $e) {
    echo "✗ Erreur de base de données: " . $e->getMessage() . "\n";
}

// Test 7: Vérification des logs d'erreur
echo "\n7. Vérification des logs récents...\n";

$logFile = ini_get('error_log');
if (!$logFile) {
    $logFile = '/tmp/php_errors.log';
}

if (file_exists($logFile)) {
    $logContent = file_get_contents($logFile);
    $recentErrors = array_filter(explode("\n", $logContent), function($line) {
        return strpos($line, date('Y-m-d')) !== false && 
               (strpos($line, 'Fatal') !== false || strpos($line, 'Error') !== false);
    });
    
    if (empty($recentErrors)) {
        echo "✓ Aucune erreur fatale récente trouvée\n";
    } else {
        echo "⚠ Erreurs récentes trouvées:\n";
        foreach (array_slice($recentErrors, -5) as $error) {
            echo "  " . trim($error) . "\n";
        }
    }
} else {
    echo "ℹ Fichier de log non trouvé\n";
}

echo "\n=== Résumé ===\n";
echo "Les corrections principales apportées:\n";
echo "• Correction du chemin Bootstrap.php dans search.php\n";
echo "• Amélioration de la gestion des booléens dans ValidationMiddleware\n";
echo "• Amélioration du middleware CORS\n";
echo "• Ajout de logs de débogage pour l'authentification\n";
echo "• Gestion améliorée des headers d'authentification\n\n";

echo "Prochaines étapes recommandées:\n";
echo "1. Redémarrer le serveur PHP (php -S localhost:8000 backend/router.php)\n";
echo "2. Tester la connexion depuis le frontend React\n";
echo "3. Vérifier les logs en temps réel pendant les tests\n";
echo "4. Tester la création/modification de projets avec is_public\n\n";

echo "Pour diagnostiquer les problèmes WebSocket:\n";
echo "• Vérifier que le serveur WebSocket est démarré sur le port 8080\n";
echo "• Vérifier la configuration dans le frontend React\n";
echo "• Tester avec un client WebSocket simple\n\n";

echo "Test terminé.\n";
