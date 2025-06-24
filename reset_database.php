#!/usr/bin/env php
<?php
/**
 * Script de nettoyage et réinstallation de la base de données
 * 
 * Usage: php reset_database.php
 */

echo "🧹 Nettoyage et réinstallation de la base de données\n";
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
    
    // ÉTAPE 1: Nettoyage complet
    echo "🧹 ÉTAPE 1: Nettoyage de la base de données...\n";
    
    // Supprimer complètement la base de données
    try {
        $pdo->exec("DROP DATABASE IF EXISTS `{$config['dbname']}`");
        echo "   ✅ Base de données supprimée\n";
    } catch (PDOException $e) {
        echo "   ⚠️ Erreur lors de la suppression: " . $e->getMessage() . "\n";
    }
    
    // Créer une nouvelle base de données propre
    try {
        $pdo->exec("CREATE DATABASE `{$config['dbname']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");
        echo "   ✅ Nouvelle base de données créée\n";
    } catch (PDOException $e) {
        echo "   ❌ Erreur lors de la création: " . $e->getMessage() . "\n";
        throw $e;
    }
    
    // Se connecter à la nouvelle base
    $pdo->exec("USE `{$config['dbname']}`");
    echo "   ✅ Connexion à la nouvelle base\n\n";
    
    // ÉTAPE 2: Installation du schéma
    echo "🚀 ÉTAPE 2: Installation du schéma...\n";
    
    // Lire le fichier SQL
    $sql = file_get_contents($schemaFile);
    if (empty($sql)) {
        throw new Exception("Le fichier schema.sql est vide");
    }
    
    // Nettoyer le SQL et séparer les commandes
    $sql = preg_replace('/^USE\s+.*?;/im', '', $sql); // Enlever les USE
    $sql = preg_replace('/^CREATE\s+DATABASE.*?;/im', '', $sql); // Enlever CREATE DATABASE
    
    // Diviser en commandes individuelles
    $queries = [];
    $currentQuery = '';
    $inDelimiter = false;
    
    foreach (explode("\n", $sql) as $line) {
        $line = trim($line);
        
        // Ignorer les commentaires et lignes vides
        if (empty($line) || strpos($line, '--') === 0) {
            continue;
        }
        
        // Gérer les délimiteurs
        if (preg_match('/^DELIMITER\s+(.+)$/i', $line, $matches)) {
            $delimiter = $matches[1];
            $inDelimiter = true;
            continue;
        }
        
        if ($inDelimiter && trim($line) === 'DELIMITER ;') {
            if (!empty($currentQuery)) {
                $queries[] = trim($currentQuery);
                $currentQuery = '';
            }
            $inDelimiter = false;
            continue;
        }
        
        $currentQuery .= $line . "\n";
        
        // Si pas dans un délimiteur spécial, séparer sur les ;
        if (!$inDelimiter && substr(rtrim($line), -1) === ';') {
            $queries[] = trim($currentQuery);
            $currentQuery = '';
        }
    }
    
    // Ajouter la dernière requête si elle existe
    if (!empty($currentQuery)) {
        $queries[] = trim($currentQuery);
    }
    
    echo "   📝 " . count($queries) . " commandes SQL trouvées\n";
    
    // Exécuter chaque commande
    $successCount = 0;
    $errorCount = 0;
    
    foreach ($queries as $query) {
        $query = trim($query);
        if (empty($query)) continue;
        
        try {
            $pdo->exec($query);
            $successCount++;
            
            // Afficher les commandes importantes
            if (preg_match('/^\s*CREATE\s+(TABLE|VIEW|TRIGGER)/i', $query, $matches)) {
                $objectName = '';
                if (preg_match('/CREATE\s+' . $matches[1] . '\s+(?:IF\s+NOT\s+EXISTS\s+)?[`\'"]?([^\s`\'"(]+)/i', $query, $nameMatches)) {
                    $objectName = $nameMatches[1];
                }
                echo "   ✅ " . strtoupper($matches[1]) . " créé" . ($objectName ? ": $objectName" : "") . "\n";
            } elseif (preg_match('/^\s*INSERT\s+INTO/i', $query)) {
                if (preg_match('/INSERT\s+INTO\s+[`\'"]?([^\s`\'"(]+)/i', $query, $matches)) {
                    echo "   📝 Données insérées dans: {$matches[1]}\n";
                }
            }
            
        } catch (PDOException $e) {
            $errorCount++;
            echo "   ❌ Erreur: " . $e->getMessage() . "\n";
            echo "   🔍 Requête: " . substr($query, 0, 100) . "...\n";
        }
    }
    
    echo "\n📊 Résumé de l'installation:\n";
    echo "   ✅ Commandes réussies: $successCount\n";
    echo "   ❌ Erreurs: $errorCount\n\n";
    
    // ÉTAPE 3: Vérification
    echo "🔍 ÉTAPE 3: Vérification des tables...\n";
    $tables = ['users', 'projects', 'tasks', 'tags', 'comments', 'attachments'];
    
    $allTablesOK = true;
    foreach ($tables as $table) {
        try {
            $stmt = $pdo->prepare("SHOW TABLES LIKE ?");
            $stmt->execute([$table]);
            if ($stmt->rowCount() > 0) {
                // Compter les enregistrements
                $countStmt = $pdo->query("SELECT COUNT(*) as count FROM `$table`");
                $count = $countStmt->fetch()['count'];
                echo "   ✅ $table ($count enregistrements)\n";
            } else {
                echo "   ❌ $table - manquante\n";
                $allTablesOK = false;
            }
        } catch (PDOException $e) {
            echo "   ❌ $table - erreur: " . $e->getMessage() . "\n";
            $allTablesOK = false;
        }
    }
    
    if ($allTablesOK) {
        echo "\n🎉 Installation réussie!\n\n";
        
        echo "🔐 Compte admin créé:\n";
        echo "   Username: admin\n";
        echo "   Email: admin@taskmanager.local\n";
        echo "   Mot de passe: Admin123!\n\n";
        
        echo "🚀 Prochaines étapes:\n";
        echo "   1. Testez la connexion: php test_fix.php\n";
        echo "   2. Démarrez le serveur: cd backend && php -S localhost:8000\n";
        echo "   3. Testez le login:\n";
        echo "      curl -X POST http://localhost:8000/api/auth/login \\\n";
        echo "           -H 'Content-Type: application/json' \\\n";
        echo "           -d '{\"login\":\"admin\",\"password\":\"Admin123!\"}'\n";
        
    } else {
        echo "\n⚠️ Installation incomplète - certaines tables manquent\n";
        exit(1);
    }
    
} catch (PDOException $e) {
    echo "❌ Erreur de base de données: " . $e->getMessage() . "\n";
    echo "\n🔧 Solutions possibles:\n";
    echo "   1. Vérifiez que MySQL est démarré\n";
    echo "   2. Vérifiez les identifiants de connexion\n";
    echo "   3. Assurez-vous que l'utilisateur a les droits CREATE et DROP\n";
    exit(1);
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    exit(1);
}
