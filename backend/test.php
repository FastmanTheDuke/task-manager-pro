<?php
// Test simple pour vérifier que le serveur fonctionne
echo "🚀 Task Manager Pro - Server Test\n";
echo "=================================\n";
echo "✅ PHP Version: " . PHP_VERSION . "\n";
echo "✅ Server running from: " . __DIR__ . "\n";
echo "✅ Current time: " . date('Y-m-d H:i:s') . "\n";
echo "✅ Request URI: " . ($_SERVER['REQUEST_URI'] ?? 'unknown') . "\n";
echo "✅ Request Method: " . ($_SERVER['REQUEST_METHOD'] ?? 'unknown') . "\n";

// Test des extensions
echo "\n📦 Extensions:\n";
$extensions = ['pdo', 'pdo_mysql', 'json'];
foreach ($extensions as $ext) {
    $status = extension_loaded($ext) ? '✅' : '❌';
    echo "   $status $ext\n";
}

// Test de la base de données
echo "\n🗄️ Database test:\n";
try {
    require_once __DIR__ . '/Bootstrap.php';
    echo "   ✅ Bootstrap loaded\n";
    
    $config = [
        'host' => $_ENV['DB_HOST'] ?? 'localhost',
        'dbname' => $_ENV['DB_NAME'] ?? 'task_manager_pro',
        'username' => $_ENV['DB_USER'] ?? 'root',
        'password' => $_ENV['DB_PASS'] ?? ''
    ];
    
    $dsn = "mysql:host={$config['host']};dbname={$config['dbname']}";
    $pdo = new PDO($dsn, $config['username'], $config['password']);
    echo "   ✅ Database connection OK\n";
} catch (Exception $e) {
    echo "   ❌ Database error: " . $e->getMessage() . "\n";
}

echo "\n🔗 Links to test:\n";
echo "   🌐 http://localhost:8000/test.php (this file)\n";
echo "   🌐 http://localhost:8000/api/health (API health)\n";
echo "   🌐 http://localhost:8000/api (API info)\n";
echo "   🌐 http://localhost:8000/index.php (main index)\n";
