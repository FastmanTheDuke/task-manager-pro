#!/usr/bin/env php
<?php
/**
 * Script de démarrage du serveur de développement
 * 
 * Usage: php start_server.php [port]
 */

$port = $argv[1] ?? 8000;
$host = 'localhost';

echo "🚀 Task Manager Pro - Serveur de développement\n";
echo "=============================================\n\n";

// Vérifications préalables
echo "🔍 Vérifications préalables...\n";

// Vérifier qu'on est dans le bon répertoire
if (!file_exists(__DIR__ . '/index.php')) {
    echo "❌ Erreur: Fichier index.php non trouvé\n";
    echo "   Assurez-vous d'être dans le répertoire backend/\n";
    exit(1);
}

if (!file_exists(__DIR__ . '/router.php')) {
    echo "❌ Erreur: Fichier router.php non trouvé\n";
    exit(1);
}

echo "✅ Fichiers trouvés: index.php, router.php\n";

// Vérifier les extensions PHP
$extensions = ['pdo', 'pdo_mysql', 'json'];
foreach ($extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✅ Extension $ext: activée\n";
    } else {
        echo "❌ Extension $ext: manquante\n";
    }
}

// Test rapide de la base de données
echo "\n🗄️ Test de la base de données...\n";
try {
    if (file_exists(__DIR__ . '/.env')) {
        $envLines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES);
        foreach ($envLines as $line) {
            if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
                list($key, $value) = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value);
            }
        }
        echo "✅ Configuration .env chargée\n";
    }
    
    $host_db = $_ENV['DB_HOST'] ?? 'localhost';
    $dbname = $_ENV['DB_NAME'] ?? 'task_manager_pro';
    $username = $_ENV['DB_USER'] ?? 'root';
    $password = $_ENV['DB_PASS'] ?? '';
    
    $dsn = "mysql:host=$host_db;dbname=$dbname";
    $pdo = new PDO($dsn, $username, $password, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    echo "✅ Connexion base de données: OK\n";
    
} catch (Exception $e) {
    echo "⚠️ Base de données: " . $e->getMessage() . "\n";
    echo "   Le serveur démarrera quand même, mais l'API pourrait ne pas fonctionner\n";
}

echo "\n📋 Configuration du serveur:\n";
echo "   🌐 Host: $host\n";
echo "   🔌 Port: $port\n";
echo "   📁 Document root: " . __DIR__ . "\n";
echo "   🔄 Router: router.php\n";

echo "\n🔗 URLs de test:\n";
echo "   🏠 http://$host:$port/ (racine)\n";
echo "   🧪 http://$host:$port/test.php (test serveur)\n";
echo "   📊 http://$host:$port/api (informations API)\n";
echo "   💚 http://$host:$port/api/health (santé API)\n";
echo "   🔐 http://$host:$port/api/auth/login (test login)\n";

echo "\n🚀 Démarrage du serveur...\n";
echo "   Pour arrêter le serveur: Ctrl+C\n";
echo "   Logs du router visible dans la console\n\n";

// Construire la commande
$command = "php -S $host:$port router.php";

echo "💻 Commande: $command\n";
echo str_repeat("=", 50) . "\n\n";

// Démarrer le serveur
passthru($command);
