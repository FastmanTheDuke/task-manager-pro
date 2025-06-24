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

// Test 3: Diagnostic spécifique pdo_mysql
echo "3️⃣ Diagnostic pdo_mysql...\n";
if (!extension_loaded('pdo_mysql')) {
    echo colorize("   ❌ PROBLÈME IDENTIFIÉ: L'extension pdo_mysql n'est pas installée!", 'red') . "\n";
    echo "   Cette extension est OBLIGATOIRE pour connecter PHP à MySQL.\n\n";
    
    echo "   🛠️ SOLUTIONS par environnement:\n\n";
    
    // Windows avec XAMPP
    echo colorize("   📁 XAMPP (Windows):", 'blue') . "\n";
    echo "     1. Ouvrir le fichier: C:\\xampp\\php\\php.ini\n";
    echo "     2. Rechercher: ;extension=pdo_mysql\n";
    echo "     3. Enlever le ';' pour obtenir: extension=pdo_mysql\n";
    echo "     4. Redémarrer Apache\n\n";
    
    // Windows avec WampServer
    echo colorize("   📁 WampServer (Windows):", 'blue') . "\n";
    echo "     1. Clic droit sur l'icône WampServer\n";
    echo "     2. PHP > PHP extensions > pdo_mysql (cocher)\n";
    echo "     3. Redémarrer tous les services\n\n";
    
    // Linux
    echo colorize("   🐧 Linux:", 'blue') . "\n";
    echo "     Ubuntu/Debian: sudo apt-get install php-mysql\n";
    echo "     CentOS/RHEL: sudo yum install php-mysql\n";
    echo "     Redémarrer Apache/Nginx\n\n";
    
    // macOS
    echo colorize("   🍎 macOS:", 'blue') . "\n";
    echo "     Homebrew: brew install php (inclut pdo_mysql)\n";
    echo "     MAMP: Généralement inclus par défaut\n\n";
    
    echo colorize("   ⚡ VÉRIFICATION RAPIDE:", 'yellow') . "\n";
    echo "   Après installation, testez avec: php -m | grep pdo_mysql\n\n";
    
} else {
    echo colorize("   ✅ Extension pdo_mysql correctement installée!", 'green') . "\n\n";
}

// Test 4: Test de la classe Connection (si possible)
echo "4️⃣ Test de la classe Connection...\n";
$bootstrapPath = __DIR__ . '/backend/Bootstrap.php';
if (file_exists($bootstrapPath)) {
    try {
        require_once $bootstrapPath;
        
        if (extension_loaded('pdo_mysql')) {
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
            
            if ($connectionTest) {
                echo "\n" . colorize("🎉 SUCCÈS! La connexion à la base de données fonctionne!", 'green') . "\n";
            } else {
                echo "\n" . colorize("⚠️ Problème de connexion - vérifiez votre configuration .env", 'yellow') . "\n";
            }
            
        } else {
            echo colorize("   ⚠️ Test de connexion ignoré - pdo_mysql non disponible", 'yellow') . "\n";
        }
        
    } catch (Exception $e) {
        echo colorize("   ❌ ERREUR: " . $e->getMessage(), 'red') . "\n";
        echo "   Stack trace: " . substr($e->getTraceAsString(), 0, 200) . "...\n";
    }
} else {
    echo colorize("   ⚠️ Bootstrap.php non trouvé à: $bootstrapPath", 'yellow') . "\n";
    echo "   Vérifiez que vous exécutez ce script depuis la racine du projet.\n";
    echo "   Structure attendue:\n";
    echo "   ├── test_fix.php (ce script)\n";
    echo "   ├── backend/\n";
    echo "   │   ├── Bootstrap.php\n";
    echo "   │   ├── index.php\n";
    echo "   │   └── ...\n";
}
echo "\n";

// Test 5: Instructions de résolution
echo "5️⃣ Plan d'action recommandé...\n";

if (in_array('pdo_mysql', $missingExtensions)) {
    echo colorize("   🚨 PRIORITÉ 1: Installer pdo_mysql", 'red') . "\n";
    echo "   Sans cette extension, l'application ne peut pas fonctionner.\n";
    echo "   Suivez les instructions ci-dessus selon votre environnement.\n\n";
    
    echo colorize("   📋 APRÈS installation de pdo_mysql:", 'blue') . "\n";
    echo "   1. Relancez ce test: php test_fix.php\n";
    echo "   2. Démarrez le serveur: cd backend && php -S localhost:8000\n";
    echo "   3. Testez l'API: curl http://localhost:8000/api/health\n";
    echo "   4. Testez le login: curl -X POST http://localhost:8000/api/auth/login -H 'Content-Type: application/json' -d '{\"login\":\"admin\",\"password\":\"Admin123!\"}'\n\n";
} else {
    echo colorize("   ✅ Extensions OK - Prêt à tester l'application!", 'green') . "\n";
    echo "   1. Démarrez le serveur: " . colorize("cd backend && php -S localhost:8000", 'blue') . "\n";
    echo "   2. Testez l'API: " . colorize("curl http://localhost:8000/api/health", 'blue') . "\n";
    echo "   3. Testez le diagnostic: " . colorize("curl http://localhost:8000/api/diagnostic/system", 'blue') . "\n";
    echo "   4. Testez le login: " . colorize("curl -X POST http://localhost:8000/api/auth/login -H 'Content-Type: application/json' -d '{\"login\":\"admin\",\"password\":\"Admin123!\"}'", 'blue') . "\n\n";
    
    if (file_exists($bootstrapPath)) {
        echo colorize("   🚀 DÉMARRAGE RAPIDE:", 'green') . "\n";
        echo "   Votre environnement semble prêt! Lancez directement:\n";
        echo "   " . colorize("cd backend && php -S localhost:8000", 'blue') . "\n\n";
    }
}

// Résumé final
echo colorize("📊 RÉSUMÉ:", 'blue') . "\n";
$totalExtensions = count($extensions);
$activeExtensions = $totalExtensions - count($missingExtensions);
echo "✅ Extensions actives: $activeExtensions/$totalExtensions\n";

if (empty($missingExtensions)) {
    echo colorize("🎉 Toutes les extensions requises sont installées!", 'green') . "\n";
    if (file_exists($bootstrapPath)) {
        echo colorize("🚀 Application prête à être testée!", 'green') . "\n";
    }
} else {
    echo colorize("⚠️ Extensions manquantes: " . implode(', ', $missingExtensions), 'yellow') . "\n";
}

echo "\n" . colorize("💡 L'erreur PDO::MYSQL_ATTR_INIT_COMMAND était causée par l'absence de pdo_mysql.", 'blue') . "\n";
echo colorize("   Nos corrections permettent à l'application de fonctionner même sans cette constante,", 'blue') . "\n";
echo colorize("   mais l'extension pdo_mysql reste obligatoire pour se connecter à MySQL.", 'blue') . "\n";

if (empty($missingExtensions) && file_exists($bootstrapPath)) {
    echo "\n" . colorize("🎯 PROCHAINE ÉTAPE: Lancez votre serveur et testez l'API!", 'green') . "\n";
}
