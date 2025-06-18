<?php
/**
 * Diagnostic Login - Version CLI
 * 
 * Ce script teste chaque étape du processus de connexion
 * sans conflits avec les headers HTTP
 */

// Désactiver l'auto-initialisation de Bootstrap
define('BOOTSTRAP_MANUAL_INIT', true);

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "=== DIAGNOSTIC LOGIN FLEXIBLE ===\n\n";

// Étape 1: Test de l'autoloading de base
echo "1. Test de l'autoloading Composer...\n";
$autoloadFile = './backend/vendor/autoload.php';
if (!file_exists($autoloadFile)) {
    echo "❌ Fichier autoload manquant: $autoloadFile\n";
    echo "   Exécutez: cd backend && composer install\n\n";
    exit;
}

require_once $autoloadFile;
echo "✅ Autoload Composer chargé\n\n";

// Étape 2: Test du fichier de configuration
echo "2. Test du fichier .env...\n";
$envFile = './backend/.env';
if (!file_exists($envFile)) {
    echo "❌ Fichier .env manquant: $envFile\n";
    echo "   Copiez .env.example vers .env et configurez-le\n\n";
    exit;
}
echo "✅ Fichier .env trouvé\n\n";

// Étape 3: Test de la connexion à la base de données
echo "3. Test de la connexion à la base de données...\n";
try {
    // Chargement manuel de la configuration
    $dotenv = Dotenv\Dotenv::createImmutable('./backend');
    $dotenv->load();
    
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $dbname = $_ENV['DB_NAME'] ?? 'task_manager_pro';
    $username = $_ENV['DB_USER'] ?? 'root';
    $password = $_ENV['DB_PASS'] ?? '';
    
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $pdo = new PDO($dsn, $username, $password, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
    ]);
    
    echo "✅ Connexion à la base de données réussie\n";
    echo "   - Host: $host\n";
    echo "   - Database: $dbname\n";
    echo "   - User: $username\n\n";
} catch (Exception $e) {
    echo "❌ Erreur de connexion DB: " . $e->getMessage() . "\n\n";
    exit;
}

// Étape 4: Test de l'existence de la table users
echo "4. Test de la table users...\n";
try {
    $stmt = $pdo->query("SHOW TABLES LIKE 'users'");
    if ($stmt->rowCount() > 0) {
        echo "✅ Table 'users' existe\n";
        
        // Compter les utilisateurs
        $stmt = $pdo->query("SELECT COUNT(*) as count FROM users");
        $count = $stmt->fetch()['count'];
        echo "   - Nombre d'utilisateurs: $count\n\n";
    } else {
        echo "❌ Table 'users' n'existe pas\n";
        echo "   Exécutez: ./install-db.sh\n\n";
        exit;
    }
} catch (Exception $e) {
    echo "❌ Erreur table users: " . $e->getMessage() . "\n\n";
    exit;
}

// Étape 5: Test de l'utilisateur admin
echo "5. Test de l'utilisateur admin...\n";
try {
    $stmt = $pdo->prepare("SELECT id, username, email, role FROM users WHERE role = 'admin'");
    $stmt->execute();
    $admins = $stmt->fetchAll();
    
    if (count($admins) > 0) {
        echo "✅ Utilisateur(s) admin trouvé(s):\n";
        foreach ($admins as $admin) {
            echo "   - ID: {$admin['id']}, Username: {$admin['username']}, Email: {$admin['email']}\n";
        }
        echo "\n";
        
        // Garder le premier admin pour les tests
        $testAdmin = $admins[0];
    } else {
        echo "❌ Aucun utilisateur admin trouvé\n";
        echo "   Exécutez: ./install-db.sh\n\n";
        exit;
    }
} catch (Exception $e) {
    echo "❌ Erreur recherche admin: " . $e->getMessage() . "\n\n";
    exit;
}

// Étape 6: Test de l'authentification par email
echo "6. Test d'authentification par email...\n";
try {
    $email = $testAdmin['email'];
    $password = 'Admin123!'; // Mot de passe par défaut
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        echo "✅ Authentification par email réussie\n";
        echo "   - Email testé: $email\n";
        echo "   - Utilisateur: {$user['username']}\n\n";
    } else {
        echo "❌ Authentification par email échouée\n";
        echo "   - Email testé: $email\n";
        echo "   - Mot de passe testé: $password\n\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur auth email: " . $e->getMessage() . "\n\n";
}

// Étape 7: Test de l'authentification par username
echo "7. Test d'authentification par username...\n";
try {
    $username = $testAdmin['username'];
    $password = 'Admin123!';
    
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        echo "✅ Authentification par username réussie\n";
        echo "   - Username testé: $username\n";
        echo "   - Email: {$user['email']}\n\n";
    } else {
        echo "❌ Authentification par username échouée\n";
        echo "   - Username testé: $username\n";
        echo "   - Mot de passe testé: $password\n\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur auth username: " . $e->getMessage() . "\n\n";
}

// Étape 8: Test de détection email vs username
echo "8. Test de détection email vs username...\n";
$testValues = [$testAdmin['email'], $testAdmin['username'], 'user@example.com', 'testuser'];
foreach ($testValues as $test) {
    $isEmail = filter_var($test, FILTER_VALIDATE_EMAIL) !== false;
    echo "   '$test' -> " . ($isEmail ? "EMAIL" : "USERNAME") . "\n";
}
echo "\n";

// Étape 9: Test de l'API endpoint (si le serveur tourne)
echo "9. Test de l'endpoint API...\n";
$apiUrl = 'http://localhost:8000/api/auth/login';
$testData = [
    'login' => $testAdmin['username'],
    'password' => 'Admin123!'
];

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $apiUrl);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($testData));
curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 3);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($error) {
    echo "❌ Serveur API non accessible: $error\n";
    echo "   Démarrez le serveur: cd backend && php -S localhost:8000 router.php\n\n";
} else {
    echo "✅ Serveur API accessible (HTTP $httpCode)\n";
    if ($response) {
        $decoded = json_decode($response, true);
        if ($decoded && isset($decoded['success'])) {
            if ($decoded['success']) {
                echo "   ✅ Authentification API réussie\n";
            } else {
                echo "   ❌ Authentification API échouée: " . ($decoded['message'] ?? 'Erreur inconnue') . "\n";
            }
        } else {
            echo "   ❌ Réponse API invalide\n";
        }
    }
    echo "\n";
}

echo "=== RÉSUMÉ ===\n";
echo "✅ Base de données: Connectée\n";
echo "✅ Table users: Existe\n";
echo "✅ Utilisateur admin: " . $testAdmin['username'] . " (" . $testAdmin['email'] . ")\n";
echo "✅ Détection email/username: Fonctionnelle\n";
echo "\n";

echo "=== COMMANDES DE TEST ===\n";
echo "# Test avec curl (email):\n";
echo "curl -X POST http://localhost:8000/api/auth/login \\\n";
echo "  -H \"Content-Type: application/json\" \\\n";
echo "  -d '{\"login\":\"{$testAdmin['email']}\",\"password\":\"Admin123!\"}'\n\n";

echo "# Test avec curl (username):\n";
echo "curl -X POST http://localhost:8000/api/auth/login \\\n";
echo "  -H \"Content-Type: application/json\" \\\n";
echo "  -d '{\"login\":\"{$testAdmin['username']}\",\"password\":\"Admin123!\"}'\n\n";

echo "=== FIN DU DIAGNOSTIC ===\n";
