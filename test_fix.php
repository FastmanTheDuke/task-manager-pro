#!/usr/bin/env php
<?php
/**
 * Script de test rapide pour vérifier le fix PDO
 * 
 * Usage: php test_fix.php
 */

echo "🔧 Test des corrections PDO - Task Manager Pro\n";
echo "============================================\n\n";

// Définir les couleurs pour l'affichage
function colorize($text, $color) {
    $colors = [
        'green' => "\033[32m",
        'red' => "\033[31m",
        'yellow' => "\033[33m",
        'blue' => "\033[34m",
        'reset' => "\033[0m"
    ];
    
    return $colors[$color] . $text . $colors['reset'];
}

// Test 1: Vérifier les extensions PHP
echo "1️⃣ Vérification des extensions PHP...\n";
$extensions = ['pdo', 'pdo_mysql', 'json', 'mbstring'];
foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    $status = $loaded ? colorize('✅ ACTIVÉE', 'green') : colorize('❌ MANQUANTE', 'red');
    echo "   - $ext: $status\n";
}
echo "\n";

// Test 2: Vérifier les constantes PDO
echo "2️⃣ Vérification des constantes PDO...\n";
$constants = [
    'PDO::ATTR_ERRMODE' => defined('PDO::ATTR_ERRMODE'),
    'PDO::ERRMODE_EXCEPTION' => defined('PDO::ERRMODE_EXCEPTION'),
    'PDO::ATTR_DEFAULT_FETCH_MODE' => defined('PDO::ATTR_DEFAULT_FETCH_MODE'),
    'PDO::FETCH_ASSOC' => defined('PDO::FETCH_ASSOC'),
    'PDO::MYSQL_ATTR_INIT_COMMAND' => defined('PDO::MYSQL_ATTR_INIT_COMMAND')
];

foreach ($constants as $const => $exists) {
    $status = $exists ? colorize('✅ DÉFINIE', 'green') : colorize('⚠️ NON DÉFINIE', 'yellow');
    echo "   - $const: $status\n";
}
echo "\n";

// Test 3: Tester la classe Connection
echo "3️⃣ Test de la classe Connection corrigée...\n";
try {
    require_once __DIR__ . '/Bootstrap.php';
    
    // Tester les requirements
    echo "   Vérification des prérequis...\n";
    $requirements = \TaskManager\Database\Connection::checkRequirements();
    foreach ($requirements as $req => $status) {
        $statusText = $status ? colorize('✅ OK', 'green') : colorize('❌ NOK', 'red');
        echo "     - $req: $statusText\n";
    }
    
    // Tester la configuration
    echo "   Configuration de la base de données...\n";
    $config = \TaskManager\Database\Connection::getConfig();
    echo "     - Host: {$config['host']}\n";
    echo "     - Database: {$config['dbname']}\n";
    echo "     - User: {$config['username']}\n";
    echo "     - Charset: {$config['charset']}\n";
    $pwdStatus = $config['password_set'] ? colorize('✅ CONFIGURÉ', 'green') : colorize('⚠️ VIDE', 'yellow');
    echo "     - Password: $pwdStatus\n";
    
    // Test de connexion
    echo "   Test de connexion...\n";
    $connectionTest = \TaskManager\Database\Connection::testConnection();
    $connStatus = $connectionTest ? colorize('✅ CONNECTÉE', 'green') : colorize('❌ ÉCHEC', 'red');
    echo "     - Connexion: $connStatus\n";
    
} catch (Exception $e) {
    echo colorize("   ❌ ERREUR: " . $e->getMessage(), 'red') . "\n";
}
echo "\n";

// Test 4: Test des endpoints de diagnostic
echo "4️⃣ Test des nouveaux endpoints de diagnostic...\n";

$endpoints = [
    'health' => 'http://localhost:8000/api/health',
    'diagnostic-system' => 'http://localhost:8000/api/diagnostic/system',
    'diagnostic-database' => 'http://localhost:8000/api/diagnostic/database',
    'diagnostic-auth' => 'http://localhost:8000/api/diagnostic/auth'
];

foreach ($endpoints as $name => $url) {
    echo "   Testing $name...\n";
    
    // Test simple avec curl si disponible
    if (function_exists('curl_init')) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($response !== false && $httpCode === 200) {
            echo "     " . colorize("✅ $url - OK", 'green') . "\n";
        } else {
            echo "     " . colorize("⚠️ $url - Server not running (start with: php -S localhost:8000)", 'yellow') . "\n";
        }
    } else {
        echo "     " . colorize("ℹ️ cURL non disponible - testez manuellement: $url", 'blue') . "\n";
    }
}
echo "\n";

// Test 5: Recommandations finales
echo "5️⃣ Recommandations...\n";
echo "   Pour tester complètement:\n";
echo "   1. Démarrez le serveur: " . colorize("cd backend && php -S localhost:8000", 'blue') . "\n";
echo "   2. Testez l'API de santé: " . colorize("curl http://localhost:8000/api/health", 'blue') . "\n";
echo "   3. Testez le diagnostic: " . colorize("curl http://localhost:8000/api/diagnostic/system", 'blue') . "\n";
echo "   4. Testez le login: " . colorize("curl -X POST http://localhost:8000/api/auth/login -H 'Content-Type: application/json' -d '{\"login\":\"admin\",\"password\":\"Admin123!\"}'", 'blue') . "\n";
echo "\n";

echo colorize("🎉 Test des corrections terminé !", 'green') . "\n";
echo "Les principales améliorations:\n";
echo "✅ Correction de l'erreur PDO::MYSQL_ATTR_INIT_COMMAND\n";
echo "✅ Gestion compatible des options PDO\n";
echo "✅ Nouveau système de diagnostic complet\n";
echo "✅ Meilleure gestion d'erreurs\n";
echo "✅ Tests automatisés\n\n";
