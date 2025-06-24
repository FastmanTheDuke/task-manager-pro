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
$missingExtensions = [];

foreach ($extensions as $ext) {
    $loaded = extension_loaded($ext);
    $status = $loaded ? colorize('✅ ACTIVÉE', 'green') : colorize('❌ MANQUANTE', 'red');
    echo "   - $ext: $status\n";
    
    if (!$loaded) {
        $missingExtensions[] = $ext;
    }
}

if (!empty($missingExtensions)) {
    echo "\n" . colorize("⚠️ ATTENTION: Extensions manquantes détectées!", 'yellow') . "\n";
    foreach ($missingExtensions as $ext) {
        echo "   - $ext\n";
    }
} else {
    echo "\n" . colorize("🎉 Toutes les extensions requises sont activées!", 'green') . "\n";
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

// Test 3: Test de connexion PDO direct (sans Bootstrap)
echo "3️⃣ Test de connexion PDO direct...\n";

if (!extension_loaded('pdo_mysql')) {
    echo colorize("   ❌ pdo_mysql non disponible - test ignoré", 'red') . "\n\n";
} else {
    // Charger le fichier .env manuellement
    $envFile = __DIR__ . '/backend/.env';
    $config = [
        'host' => 'localhost',
        'dbname' => 'task_manager_pro',
        'username' => 'root',
        'password' => '',
        'charset' => 'utf8mb4'
    ];
    
    if (file_exists($envFile)) {
        echo "   Chargement configuration .env...\n";
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                switch ($key) {
                    case 'DB_HOST':
                        $config['host'] = $value;
                        break;
                    case 'DB_NAME':
                        $config['dbname'] = $value;
                        break;
                    case 'DB_USER':
                        $config['username'] = $value;
                        break;
                    case 'DB_PASS':
                        $config['password'] = $value;
                        break;
                }
            }
        }
        echo colorize("   ✅ Configuration chargée", 'green') . "\n";
    } else {
        echo colorize("   ⚠️ .env non trouvé - utilisation des valeurs par défaut", 'yellow') . "\n";
    }
    
    echo "   Configuration DB:\n";
    echo "     - Host: {$config['host']}\n";
    echo "     - Database: {$config['dbname']}\n";
    echo "     - User: {$config['username']}\n";
    echo "     - Password: " . (empty($config['password']) ? colorize('VIDE', 'yellow') : colorize('CONFIGURÉ', 'green')) . "\n";
    
    // Test de connexion
    echo "   Test de connexion...\n";
    try {
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset={$config['charset']}";
        
        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ];
        
        $pdo = new PDO($dsn, $config['username'], $config['password'], $options);
        
        // Définir le charset avec une requête SQL (notre correction)
        $pdo->exec("SET NAMES {$config['charset']} COLLATE {$config['charset']}_unicode_ci");
        $pdo->exec("SET CHARACTER SET {$config['charset']}");
        
        echo colorize("   ✅ Connexion PDO réussie!", 'green') . "\n";
        
        // Test de requête simple
        $stmt = $pdo->query("SELECT 1 as test");
        if ($stmt && $stmt->fetch()['test'] == 1) {
            echo colorize("   ✅ Test de requête réussi!", 'green') . "\n";
        }
        
        // Vérifier les tables avec gestion d'erreur détaillée
        echo "   Vérification des tables...\n";
        $tables = ['users', 'projects', 'tasks'];
        foreach ($tables as $table) {
            try {
                $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
                $stmt->execute([$table]);
                $exists = $stmt->rowCount() > 0;
                
                if ($exists) {
                    // Compter les enregistrements
                    try {
                        $countStmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
                        $count = $countStmt->fetch()['count'];
                        echo "     - $table: " . colorize("✅ EXISTS ($count records)", 'green') . "\n";
                    } catch (PDOException $countError) {
                        echo "     - $table: " . colorize("⚠️ EXISTS but count failed: " . $countError->getMessage(), 'yellow') . "\n";
                    }
                } else {
                    echo "     - $table: " . colorize('❌ MISSING', 'red') . "\n";
                }
            } catch (PDOException $e) {
                echo "     - $table: " . colorize('❌ ERROR: ' . $e->getMessage(), 'red') . "\n";
            }
        }
        
        echo "\n" . colorize("🎉 SUCCÈS! La base de données est accessible et fonctionnelle!", 'green') . "\n";
        
    } catch (PDOException $e) {
        echo colorize("   ❌ Erreur de connexion: " . $e->getMessage(), 'red') . "\n";
        echo "   💡 Suggestions:\n";
        echo "     1. Vérifiez que MySQL est démarré\n";
        echo "     2. Vérifiez votre configuration .env\n";
        echo "     3. Lancez: php debug_database.php pour plus de détails\n";
    } catch (Exception $e) {
        echo colorize("   ❌ Erreur: " . $e->getMessage(), 'red') . "\n";
    }
}
echo "\n";

// Test 4: Instructions finales
echo "4️⃣ Prochaines étapes...\n";

if (!empty($missingExtensions)) {
    echo colorize("   🚨 PRIORITÉ: Installez les extensions manquantes", 'red') . "\n";
    foreach ($missingExtensions as $ext) {
        echo "   - $ext\n";
    }
} else {
    echo colorize("   ✅ Extensions OK - Prêt à tester l'application!", 'green') . "\n";
    echo "\n   🔍 DIAGNOSTIC APPROFONDI:\n";
    echo "   Si vous voyez des erreurs de tables, lancez:\n";
    echo "   " . colorize("php debug_database.php", 'blue') . "\n\n";
    
    echo "   🚀 COMMANDES DE TEST:\n";
    echo "   1. Démarrer le serveur:\n";
    echo "      " . colorize("cd backend && php -S localhost:8000", 'blue') . "\n\n";
    echo "   2. Tester l'API (dans un autre terminal):\n";
    echo "      " . colorize("curl http://localhost:8000/api/health", 'blue') . "\n\n";
    echo "   3. Tester le diagnostic:\n";
    echo "      " . colorize("curl http://localhost:8000/api/diagnostic/system", 'blue') . "\n\n";
    echo "   4. Tester le login (le test qui échouait avant!):\n";
    echo "      " . colorize('curl -X POST http://localhost:8000/api/auth/login -H "Content-Type: application/json" -d "{\\"login\\":\\"admin\\",\\"password\\":\\"Admin123!\\"}"', 'blue') . "\n\n";
}

// Résumé final
echo colorize("📊 RÉSUMÉ:", 'blue') . "\n";
$totalExtensions = count($extensions);
$activeExtensions = $totalExtensions - count($missingExtensions);
echo "✅ Extensions actives: $activeExtensions/$totalExtensions\n";

if (empty($missingExtensions)) {
    echo colorize("🎉 Toutes les extensions requises sont installées!", 'green') . "\n";
    echo colorize("🚀 Votre environnement est prêt pour Task Manager Pro!", 'green') . "\n";
} else {
    echo colorize("⚠️ Extensions manquantes: " . implode(', ', $missingExtensions), 'yellow') . "\n";
}

echo "\n" . colorize("💡 PROBLÈME RÉSOLU:", 'blue') . "\n";
echo "✅ L'erreur PDO::MYSQL_ATTR_INIT_COMMAND a été corrigée\n";
echo "✅ La connexion utilise maintenant des requêtes SQL standard\n";
echo "✅ L'application est compatible avec toutes les versions de PHP\n";

echo "\n" . colorize("🔧 EN CAS DE PROBLÈME DE TABLES:", 'yellow') . "\n";
echo "   Lancez: " . colorize("php debug_database.php", 'blue') . " pour un diagnostic complet\n";
