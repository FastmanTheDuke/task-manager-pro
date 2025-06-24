#!/usr/bin/env php
<?php
/**
 * Diagnostic simple de la base de données - Compatible MariaDB
 * 
 * Usage: php debug_database.php
 */

echo "🔍 Diagnostic de la base de données - Task Manager Pro\n";
echo "==================================================\n\n";

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

echo "🔧 Configuration DB:\n";
echo "   - Host: {$config['host']}\n";
echo "   - Database: {$config['dbname']}\n";
echo "   - User: {$config['username']}\n";
echo "   - Password: " . (empty($config['password']) ? 'VIDE' : 'CONFIGURÉ') . "\n\n";

try {
    // Connexion avec gestion détaillée des erreurs
    echo "🔌 Test de connexion MySQL/MariaDB...\n";
    
    // Test 1: Connexion au serveur MySQL
    try {
        $dsn = "mysql:host={$config['host']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        echo "   ✅ Connexion au serveur MySQL/MariaDB réussie\n";
        
        // Détecter le type et la version
        $stmt = $pdo->query("SELECT VERSION() as version");
        $version = $stmt->fetch()['version'];
        $isMariaDB = stripos($version, 'mariadb') !== false;
        echo "   📊 Base de données: " . ($isMariaDB ? 'MariaDB' : 'MySQL') . " $version\n";
        
    } catch (PDOException $e) {
        echo "   ❌ Erreur de connexion au serveur: " . $e->getMessage() . "\n";
        exit(1);
    }
    
    // Test 2: Vérifier que la base existe
    try {
        $stmt = $pdo->query("SHOW DATABASES");
        $databases = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (in_array($config['dbname'], $databases)) {
            echo "   ✅ Base de données '{$config['dbname']}' existe\n";
        } else {
            echo "   ❌ Base de données '{$config['dbname']}' n'existe pas\n";
            echo "   📋 Bases disponibles: " . implode(', ', $databases) . "\n";
            exit(1);
        }
    } catch (PDOException $e) {
        echo "   ❌ Erreur lors de la vérification de la base: " . $e->getMessage() . "\n";
        exit(1);
    }
    
    // Test 3: Se connecter à la base spécifique
    try {
        $dsn = "mysql:host={$config['host']};dbname={$config['dbname']};charset=utf8mb4";
        $pdo = new PDO($dsn, $config['username'], $config['password'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
        echo "   ✅ Connexion à la base '{$config['dbname']}' réussie\n\n";
    } catch (PDOException $e) {
        echo "   ❌ Erreur de connexion à la base: " . $e->getMessage() . "\n";
        exit(1);
    }
    
    // Test 4: Lister toutes les tables (compatible MariaDB)
    echo "📋 Tables présentes dans la base:\n";
    try {
        $stmt = $pdo->query("SHOW TABLES");
        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        if (empty($tables)) {
            echo "   ❌ Aucune table trouvée\n";
        } else {
            foreach ($tables as $table) {
                echo "   📊 $table\n";
            }
        }
        echo "\n";
    } catch (PDOException $e) {
        echo "   ❌ Erreur lors du listage des tables: " . $e->getMessage() . "\n";
    }
    
    // Test 5: Vérifier chaque table importante
    echo "🔍 Analyse détaillée des tables principales:\n";
    $importantTables = ['users', 'projects', 'tasks', 'tags', 'comments', 'attachments'];
    
    foreach ($importantTables as $table) {
        echo "\n📊 Table: $table\n";
        
        try {
            // Vérifier existence (compatible MariaDB)
            $exists = in_array($table, $tables);
            
            if (!$exists) {
                echo "   ❌ Table n'existe pas\n";
                continue;
            }
            
            echo "   ✅ Table existe\n";
            
            // Compter les enregistrements
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
            $count = $stmt->fetch()['count'];
            echo "   📝 Enregistrements: $count\n";
            
            // Si c'est la table users et qu'elle a des enregistrements, montrer les détails
            if ($table === 'users' && $count > 0) {
                echo "   👤 Utilisateurs:\n";
                $stmt = $pdo->query("SELECT id, username, email, role FROM users LIMIT 5");
                $users = $stmt->fetchAll();
                foreach ($users as $user) {
                    echo "      - ID {$user['id']}: {$user['username']} ({$user['email']}) - {$user['role']}\n";
                }
            }
            
            // Structure de la table
            $stmt = $pdo->query("DESCRIBE `$table`");
            $columns = $stmt->fetchAll();
            echo "   🏗️ Colonnes: " . count($columns) . " (" . implode(', ', array_column($columns, 'Field')) . ")\n";
            
        } catch (PDOException $e) {
            echo "   ❌ Erreur lors de l'analyse: " . $e->getMessage() . "\n";
        }
    }
    
    // Test 6: Test d'insertion simple
    echo "\n🧪 Test de l'utilisateur admin...\n";
    try {
        // Vérifier si admin existe déjà
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users WHERE username = 'admin'");
        $adminCount = $stmt->fetch()['count'];
        
        if ($adminCount > 0) {
            echo "   ✅ Utilisateur admin existe déjà\n";
            
            // Afficher les détails de l'admin
            $stmt = $pdo->query("SELECT id, username, email, role, created_at FROM users WHERE username = 'admin'");
            $admin = $stmt->fetch();
            echo "   👤 Détails admin:\n";
            echo "      - ID: {$admin['id']}\n";
            echo "      - Username: {$admin['username']}\n";
            echo "      - Email: {$admin['email']}\n";
            echo "      - Role: {$admin['role']}\n";
            echo "      - Créé: {$admin['created_at']}\n";
            
        } else {
            echo "   ⚠️ Utilisateur admin manquant - tentative de création...\n";
            
            $hashedPassword = password_hash('Admin123!', PASSWORD_DEFAULT);
            $insertStmt = $pdo->prepare("
                INSERT INTO users (username, email, password, first_name, last_name, role) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            
            $result = $insertStmt->execute([
                'admin',
                'admin@taskmanager.local', 
                $hashedPassword,
                'Admin',
                'System',
                'admin'
            ]);
            
            if ($result) {
                echo "   ✅ Utilisateur admin créé avec succès!\n";
                echo "      Username: admin\n";
                echo "      Email: admin@taskmanager.local\n";
                echo "      Password: Admin123!\n";
            } else {
                echo "   ❌ Échec de la création de l'utilisateur admin\n";
            }
        }
    } catch (PDOException $e) {
        echo "   ❌ Erreur lors du test admin: " . $e->getMessage() . "\n";
    }
    
    // Test 7: Vérifier les données de base
    echo "\n📋 Vérification des données de base...\n";
    try {
        if (in_array('tags', $tables)) {
            $stmt = $pdo->query("SELECT COUNT(*) as count FROM tags");
            $tagCount = $stmt->fetch()['count'];
            echo "   🏷️ Tags: $tagCount enregistrements\n";
            
            if ($tagCount > 0) {
                $stmt = $pdo->query("SELECT name FROM tags LIMIT 5");
                $tags = $stmt->fetchAll(PDO::FETCH_COLUMN);
                echo "      Exemples: " . implode(', ', $tags) . "\n";
            }
        }
    } catch (PDOException $e) {
        echo "   ❌ Erreur lors de la vérification des données: " . $e->getMessage() . "\n";
    }
    
    echo "\n🎯 RÉSUMÉ:\n";
    echo "✅ Connexion MySQL/MariaDB: OK\n";
    echo "✅ Base de données: OK\n";
    echo "✅ Tables: " . (empty($tables) ? "MANQUANTES" : count($tables) . " présentes") . "\n";
    
    // Compteur des tables importantes présentes
    $presentTables = array_intersect($importantTables, $tables);
    echo "✅ Tables principales: " . count($presentTables) . "/" . count($importantTables) . " présentes\n";
    
    if (count($presentTables) == count($importantTables)) {
        echo "🎉 Toutes les tables principales sont présentes!\n";
    } else {
        $missingTables = array_diff($importantTables, $presentTables);
        echo "⚠️ Tables manquantes: " . implode(', ', $missingTables) . "\n";
    }
    
    // Test final de connexion depuis l'API
    echo "\n🔧 TESTS RECOMMANDÉS:\n";
    echo "1. Relancez: php test_fix.php\n";
    echo "2. Démarrez l'API: cd backend && php -S localhost:8000\n";
    echo "3. Testez le login:\n";
    echo "   curl -X POST http://localhost:8000/api/auth/login \\\n";
    echo "        -H 'Content-Type: application/json' \\\n";
    echo "        -d '{\"login\":\"admin\",\"password\":\"Admin123!\"}'\n";
    
} catch (Exception $e) {
    echo "❌ Erreur générale: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
