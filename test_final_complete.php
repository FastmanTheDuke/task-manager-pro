<?php
/**
 * Test Final Complet - Vérification de toutes les corrections
 * Usage: php test_final_complete.php
 */

echo "=== TEST FINAL COMPLET - 27 JUIN 2025 ===\n\n";

// Configuration
$baseUrl = 'http://localhost:8000';
$apiUrl = $baseUrl . '/api';
$testResults = [];

function logTest($testName, $success, $message = '') {
    global $testResults;
    $testResults[] = ['name' => $testName, 'success' => $success, 'message' => $message];
    $status = $success ? '✅' : '❌';
    echo "$status $testName";
    if ($message) echo " - $message";
    echo "\n";
}

// Test 1: Vérification des fichiers corrigés
echo "1. VERIFICATION DES FICHIERS CORRIGES\n";
echo str_repeat("-", 50) . "\n";

$criticalFiles = [
    'backend/api/users/search.php' => 'Recherche utilisateurs',
    'backend/api/projects/index.php' => 'API Projets',
    'backend/Middleware/ValidationMiddleware.php' => 'Validation améliorée',
    'backend/Services/ValidationService.php' => 'Service validation',
    'backend/Middleware/CorsMiddleware.php' => 'CORS amélioré',
    'websocket-server.js' => 'Serveur WebSocket',
    'package.json' => 'Dépendances WebSocket'
];

foreach ($criticalFiles as $file => $description) {
    if (file_exists($file)) {
        $content = file_get_contents($file);
        if (strpos($content, 'TaskManager') !== false || strpos($file, '.js') !== false || strpos($file, '.json') !== false) {
            logTest("Fichier $description", true);
        } else {
            logTest("Fichier $description", false, "Contenu obsolète");
        }
    } else {
        logTest("Fichier $description", false, "Fichier manquant");
    }
}

// Test 2: API de santé et CORS
echo "\n2. TEST API DE SANTE ET CORS\n";
echo str_repeat("-", 50) . "\n";

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
    logTest("API Health accessible", true, "Status: " . ($data['status'] ?? 'unknown'));
    
    // Vérifier les headers CORS
    $headers = $http_response_header ?? [];
    $corsHeaders = array_filter($headers, function($header) {
        return stripos($header, 'access-control') !== false;
    });
    
    logTest("Headers CORS présents", !empty($corsHeaders), count($corsHeaders) . " headers CORS");
} else {
    logTest("API Health accessible", false, "Impossible de se connecter");
}

// Test 3: Test de validation des booléens (simulation)
echo "\n3. TEST VALIDATION BOOLEENS\n";
echo str_repeat("-", 50) . "\n";

try {
    require_once 'backend/Bootstrap.php';
    
    // Test preprocessing des booléens
    $reflection = new ReflectionClass('TaskManager\Middleware\ValidationMiddleware');
    $method = $reflection->getMethod('preprocessData');
    $method->setAccessible(true);
    
    $testData = [
        'is_public_true' => 'true',
        'is_public_false' => 'false',
        'is_public_1' => '1',
        'is_public_0' => '0',
        'normal_string' => 'hello',
        'number_string' => '123'
    ];
    
    $processed = $method->invoke(null, $testData);
    
    $tests = [
        ['is_public_true', true, $processed['is_public_true'] === true],
        ['is_public_false', false, $processed['is_public_false'] === false],
        ['is_public_1', true, $processed['is_public_1'] === true],
        ['is_public_0', false, $processed['is_public_0'] === false],
        ['normal_string', 'hello', $processed['normal_string'] === 'hello'],
        ['number_string', 123, $processed['number_string'] === 123]
    ];
    
    foreach ($tests as [$key, $expected, $success]) {
        logTest("Conversion $key -> " . json_encode($expected), $success);
    }
    
} catch (Exception $e) {
    logTest("Test preprocessing", false, $e->getMessage());
}

// Test 4: Test de validation nullable
echo "\n4. TEST VALIDATION NULLABLE\n";
echo str_repeat("-", 50) . "\n";

try {
    use TaskManager\Services\ValidationService;
    
    // Test avec due_date nullable
    $testValidation = [
        // Test 1: due_date vide mais nullable
        [
            'data' => ['name' => 'Test Project', 'due_date' => ''],
            'rules' => ['name' => 'required', 'due_date' => 'nullable|date'],
            'should_pass' => true,
            'description' => 'Due date vide avec nullable'
        ],
        // Test 2: due_date null mais nullable  
        [
            'data' => ['name' => 'Test Project', 'due_date' => null],
            'rules' => ['name' => 'required', 'due_date' => 'nullable|date'],
            'should_pass' => true,
            'description' => 'Due date null avec nullable'
        ],
        // Test 3: is_public nullable avec booléen
        [
            'data' => ['name' => 'Test Project', 'is_public' => true],
            'rules' => ['name' => 'required', 'is_public' => 'nullable|boolean'],
            'should_pass' => true,
            'description' => 'Is_public booléen avec nullable'
        ],
        // Test 4: due_date avec date valide
        [
            'data' => ['name' => 'Test Project', 'due_date' => '2024-12-31'],
            'rules' => ['name' => 'required', 'due_date' => 'nullable|date'],
            'should_pass' => true,
            'description' => 'Due date valide'
        ]
    ];
    
    foreach ($testValidation as $test) {
        $result = ValidationService::validate($test['data'], $test['rules']);
        $success = $result === $test['should_pass'];
        
        if (!$success && $test['should_pass']) {
            $errors = ValidationService::getErrors();
            $errorMsg = "Erreurs: " . json_encode($errors);
        } else {
            $errorMsg = '';
        }
        
        logTest($test['description'], $success, $errorMsg);
        ValidationService::clearErrors();
    }
    
} catch (Exception $e) {
    logTest("Test validation nullable", false, $e->getMessage());
}

// Test 5: Test authentification API
echo "\n5. TEST AUTHENTIFICATION API\n";
echo str_repeat("-", 50) . "\n";

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
    if (isset($data['error']) && (stripos($data['error'], 'token') !== false || stripos($data['error'], 'authentification') !== false)) {
        logTest("Authentification requise détectée", true, "Message: " . $data['error']);
    } else {
        logTest("Authentification requise détectée", false, "Réponse inattendue: " . substr($response, 0, 100));
    }
} else {
    // Vérifier le code de statut
    $headers = get_headers($searchUrl, 1);
    if (is_array($headers) && isset($headers[0])) {
        if (strpos($headers[0], '401') !== false) {
            logTest("Authentification requise (401)", true, "Code HTTP 401 retourné");
        } else {
            logTest("Authentification requise", false, "Code HTTP: " . $headers[0]);
        }
    } else {
        logTest("Endpoint recherche accessible", false, "Impossible de se connecter");
    }
}

// Test 6: Test WebSocket
echo "\n6. TEST WEBSOCKET\n";
echo str_repeat("-", 50) . "\n";

$websocketHost = 'localhost';
$websocketPort = 8080;

// Test de connexion au port
$connection = @fsockopen($websocketHost, $websocketPort, $errno, $errstr, 3);
if ($connection) {
    logTest("Port WebSocket ouvert", true, "Port 8080 accessible");
    fclose($connection);
} else {
    logTest("Port WebSocket ouvert", false, "Port 8080 fermé - $errstr");
}

// Vérifier si Node.js est installé
$nodeVersion = @shell_exec('node --version 2>&1');
if ($nodeVersion && strpos($nodeVersion, 'v') === 0) {
    logTest("Node.js installé", true, "Version: " . trim($nodeVersion));
    
    // Vérifier si ws est installé
    if (file_exists('node_modules/ws/package.json')) {
        logTest("Dépendance 'ws' installée", true);
    } else {
        logTest("Dépendance 'ws' installée", false, "Exécuter: npm install");
    }
} else {
    logTest("Node.js installé", false, "Node.js requis pour WebSocket");
}

// Test 7: Test connexion base de données
echo "\n7. TEST BASE DE DONNEES\n";
echo str_repeat("-", 50) . "\n";

try {
    use TaskManager\Database\DatabaseManager;
    
    $db = DatabaseManager::getInstance();
    $pdo = $db->getConnection();
    
    if ($pdo) {
        logTest("Connexion base de données", true);
        
        // Test requête simple
        $stmt = $pdo->query("SELECT 1 as test");
        if ($stmt && $stmt->fetch()) {
            logTest("Requête test base de données", true);
        } else {
            logTest("Requête test base de données", false);
        }
        
        // Vérifier les tables principales
        $tables = ['users', 'projects', 'tasks'];
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
                $count = $stmt->fetchColumn();
                logTest("Table $table", true, "$count enregistrements");
            } catch (Exception $e) {
                logTest("Table $table", false, "Table manquante ou erreur");
            }
        }
    } else {
        logTest("Connexion base de données", false);
    }
} catch (Exception $e) {
    logTest("Test base de données", false, $e->getMessage());
}

// Test 8: Résumé et recommandations
echo "\n8. RESUME ET RECOMMANDATIONS\n";
echo str_repeat("=", 50) . "\n";

$totalTests = count($testResults);
$passedTests = array_filter($testResults, function($test) { return $test['success']; });
$failedTests = array_filter($testResults, function($test) { return !$test['success']; });

$successRate = ($totalTests > 0) ? (count($passedTests) / $totalTests) * 100 : 0;

echo "Tests réussis: " . count($passedTests) . "/$totalTests (" . number_format($successRate, 1) . "%)\n";

if (!empty($failedTests)) {
    echo "\n❌ TESTS ÉCHOUÉS:\n";
    foreach ($failedTests as $test) {
        echo "   • {$test['name']}";
        if ($test['message']) echo " - {$test['message']}";
        echo "\n";
    }
}

echo "\n📋 ÉTAPES DE DÉMARRAGE:\n";
echo "1. Installation des dépendances WebSocket:\n";
echo "   npm install\n\n";

echo "2. Démarrage des serveurs:\n";
echo "   Terminal 1: php -S localhost:8000 backend/router.php\n";
echo "   Terminal 2: node websocket-server.js\n";
echo "   Terminal 3: cd frontend && npm start\n\n";

echo "3. Test des corrections:\n";
echo "   • Recherche d'utilisateurs: http://localhost:3000/projects/new\n";
echo "   • Création projet avec is_public: true/false\n";
echo "   • Due_date optionnelle (peut être vide)\n";
echo "   • WebSocket dans console navigateur\n\n";

if ($successRate >= 80) {
    echo "🎉 STATUT: CORRECTIONS RÉUSSIES\n";
    echo "Votre application devrait maintenant fonctionner correctement !\n";
} elseif ($successRate >= 60) {
    echo "⚠️  STATUT: CORRECTIONS PARTIELLES\n";
    echo "Quelques problèmes subsistent, vérifiez les tests échoués.\n";
} else {
    echo "🚨 STATUT: CORRECTIONS INSUFFISANTES\n";
    echo "Plusieurs problèmes critiques persistent.\n";
}

echo "\n🔍 DEBUGGING TIPS:\n";
echo "• Logs PHP: tail -f " . (ini_get('error_log') ?: '/tmp/php_errors.log') . "\n";
echo "• Console navigateur (F12) pour erreurs frontend\n";
echo "• WebSocket logs: node websocket-server.js (mode verbose)\n";
echo "• Test API: curl -H 'Origin: http://localhost:3000' http://localhost:8000/api/health\n\n";

echo "Test terminé le " . date('Y-m-d H:i:s') . "\n";
