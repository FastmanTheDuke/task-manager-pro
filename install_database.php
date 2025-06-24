#!/usr/bin/env php
<?php
/**
 * Script d'installation automatique de la base de données
 * 
 * Usage: php install_database.php
 */

echo "🗄️ Installation de la base de données - Task Manager Pro\n";
echo "====================================================\n\n";

// Chargement du fichier .env
$envFile = __DIR__ . '/backend/.env';
$config = [
    'host' => 'localhost',
    'dbname' => 'task_manager_pro',
    'username' => 'root',
    'password' => '',
];

if (file_exists($envFile)) {
    echo "📄 Chargement de la configuration .env...\n";
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
    echo "✅ Configuration chargée\n";
} else {
    echo "⚠️ .env non trouvé - utilisation des valeurs par défaut\n";
}

echo "🔧 Configuration:\n";
echo "   - Host: {$config['host']}\n";
echo "   - Database: {$config['dbname']}\n";
echo "   - User: {$config['username']}\n";
echo "   - Password: " . (empty($config['password']) ? 'VIDE' : 'CONFIGURÉ') . "\n\n";

// Vérifier le fichier schema.sql
$schemaFile = __DIR__ . '/database/schema.sql';
if (!file_exists($schemaFile)) {
    echo "❌ Erreur: Fichier schema.sql non trouvé à: $schemaFile\n";
    echo "   Assurez-vous d'être dans le bon répertoire.\n";
    exit(1);
}

echo "📁 Fichier schema.sql trouvé (" . number_format(filesize($schemaFile)) . " octets)\n\n";

try {
    // Connexion à MySQL
    echo "🔌 Connexion à MySQL...\n";
    $dsn = "mysql:host={$config['host']};charset=utf8mb4";
    $pdo = new PDO($dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    echo "✅ Connexion réussie\n\n";
    
    // Lire et exécuter le fichier schema.sql
    echo "📖 Lecture du fichier schema.sql...\n";
    $sql = file_get_contents($schemaFile);
    
    if (empty($sql)) {
        throw new Exception("Le fichier schema.sql est vide");
    }
    
    echo "🚀 Exécution du script SQL...\n";
    
    // Exécution du script complet
    $pdo->exec($sql);
    
    echo "✅ Script SQL exécuté avec succès\n\n";
    
    // Vérifier que les tables principales existent
    echo "🔍 Vérification des tables...\n";
    $tables = ['users', 'projects', 'tasks', 'tags', 'comments', 'attachments'];
    
    $pdo->exec("USE {$config['dbname']}");
    
    foreach ($tables as $table) {
        $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->rowCount() > 0) {
            // Compter les enregistrements
            $countStmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
            $count = $countStmt->fetch()['count'];
            echo "   ✅ $table ($count enregistrements)\n";
        } else {
            echo "   ❌ $table - manquante\n";
        }
    }
    
    echo "\n🔐 Compte admin créé:\n";
    echo "   Username: admin\n";
    echo "   Email: admin@taskmanager.local\n";
    echo "   Mot de passe: Admin123!\n";
    
    echo "\n🚀 Prochaines étapes:\n";
    echo "   1. Relancez le test: php test_fix.php\n";
    echo "   2. Démarrez le serveur: cd backend && php -S localhost:8000\n";
    echo "   3. Testez l'application:\n";
    echo "      curl -X POST http://localhost:8000/api/auth/login \\\n";
    echo "           -H 'Content-Type: application/json' \\\n";
    echo "           -d '{\"login\":\"admin\",\"password\":\"Admin123!\"}'\n";
    
    echo "\n🎉 Installation terminée avec succès!\n";
    
} catch (PDOException $e) {
    echo "❌ Erreur de base de données: " . $e->getMessage() . "\n";
    echo "\n🔧 Vérifications à faire:\n";
    echo "   1. MySQL est-il démarré?\n";
    echo "   2. Les identifiants sont-ils corrects?\n";
    echo "   3. L'utilisateur a-t-il les permissions nécessaires?\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
