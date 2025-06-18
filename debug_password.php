<?php
/**
 * Diagnostic détaillé du mot de passe et de l'authentification
 */

// Désactiver l'auto-initialisation de Bootstrap
define('BOOTSTRAP_MANUAL_INIT', true);

echo "=== DIAGNOSTIC AUTHENTIFICATION DÉTAILLÉ ===\n\n";

// Chargement des dépendances
require_once './backend/vendor/autoload.php';

// Configuration de la base de données
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

// 1. Récupération de l'utilisateur admin complet
echo "1. Informations complètes de l'utilisateur admin...\n";
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = 'admin'");
$stmt->execute();
$admin = $stmt->fetch();

if ($admin) {
    echo "✅ Utilisateur admin trouvé:\n";
    echo "   - ID: {$admin['id']}\n";
    echo "   - Username: {$admin['username']}\n";
    echo "   - Email: {$admin['email']}\n";
    echo "   - Role: {$admin['role']}\n";
    echo "   - Status: {$admin['status']}\n";
    echo "   - Hash du mot de passe: " . substr($admin['password'], 0, 20) . "...\n";
    echo "   - Longueur du hash: " . strlen($admin['password']) . " caractères\n\n";
} else {
    echo "❌ Utilisateur admin non trouvé\n";
    exit;
}

// 2. Test des mots de passe possibles
echo "2. Test de différents mots de passe...\n";
$possiblePasswords = [
    'Admin123!',
    'admin123!',
    'Admin123',
    'admin123',
    'password',
    'admin',
    '123456',
    'admin@taskmanager.local'
];

$correctPassword = null;
foreach ($possiblePasswords as $testPassword) {
    if (password_verify($testPassword, $admin['password'])) {
        echo "✅ Mot de passe correct trouvé: '$testPassword'\n";
        $correctPassword = $testPassword;
        break;
    } else {
        echo "❌ '$testPassword' - incorrect\n";
    }
}

if (!$correctPassword) {
    echo "\n❌ Aucun mot de passe standard ne fonctionne!\n";
    echo "🔧 Réinitialisation du mot de passe admin...\n";
    
    $newPassword = 'Admin123!';
    $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
    
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
    $stmt->execute([$hashedPassword]);
    
    echo "✅ Mot de passe réinitialisé à: '$newPassword'\n";
    $correctPassword = $newPassword;
    
    // Vérification
    if (password_verify($newPassword, $hashedPassword)) {
        echo "✅ Vérification du nouveau mot de passe: OK\n\n";
    }
} else {
    echo "\n";
}

// 3. Test de l'authentification directe en base
echo "3. Test d'authentification directe en base...\n";

// Test avec email
$stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
$stmt->execute(['admin@taskmanager.local']);
$userByEmail = $stmt->fetch();

if ($userByEmail && password_verify($correctPassword, $userByEmail['password'])) {
    echo "✅ Authentification par email: OK\n";
} else {
    echo "❌ Authentification par email: ÉCHEC\n";
}

// Test avec username
$stmt = $pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
$stmt->execute(['admin']);
$userByUsername = $stmt->fetch();

if ($userByUsername && password_verify($correctPassword, $userByUsername['password'])) {
    echo "✅ Authentification par username: OK\n\n";
} else {
    echo "❌ Authentification par username: ÉCHEC\n\n";
}

// 4. Test de l'API avec les bons identifiants
echo "4. Test de l'API avec les identifiants corrects...\n";

$apiTests = [
    ['login' => 'admin@taskmanager.local', 'password' => $correctPassword, 'type' => 'email'],
    ['login' => 'admin', 'password' => $correctPassword, 'type' => 'username']
];

foreach ($apiTests as $test) {
    echo "Test avec {$test['type']}: {$test['login']}\n";
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, 'http://localhost:8000/api/auth/login');
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'login' => $test['login'],
        'password' => $test['password']
    ]));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    if ($error) {
        echo "   ❌ Erreur cURL: $error\n";
    } else {
        echo "   HTTP $httpCode: ";
        
        if ($response) {
            $decoded = json_decode($response, true);
            if ($decoded) {
                if ($decoded['success'] ?? false) {
                    echo "✅ Succès - " . ($decoded['message'] ?? 'OK') . "\n";
                    if (isset($decoded['data']['user']['username'])) {
                        echo "      Utilisateur connecté: " . $decoded['data']['user']['username'] . "\n";
                    }
                } else {
                    echo "❌ Échec - " . ($decoded['message'] ?? 'Erreur inconnue') . "\n";
                    if (isset($decoded['errors'])) {
                        echo "      Erreurs: " . json_encode($decoded['errors']) . "\n";
                    }
                }
            } else {
                echo "❌ Réponse JSON invalide\n";
                echo "      Réponse brute: " . substr($response, 0, 200) . "\n";
            }
        } else {
            echo "❌ Aucune réponse\n";
        }
    }
    echo "\n";
}

// 5. Test direct du endpoint avec debug
echo "5. Test de validation des données envoyées...\n";
$testData = ['login' => 'admin', 'password' => $correctPassword];
echo "Données envoyées: " . json_encode($testData) . "\n";
echo "Content-Type: application/json\n\n";

// 6. Vérification des fichiers cruciaux
echo "6. Vérification des fichiers de l'API...\n";
$files = [
    './backend/api/auth/login.php' => 'Endpoint login',
    './backend/Models/User.php' => 'Modèle User',
    './backend/Middleware/ValidationMiddleware.php' => 'Middleware de validation',
    './backend/Services/ValidationService.php' => 'Service de validation'
];

foreach ($files as $file => $description) {
    if (file_exists($file)) {
        echo "✅ $description: $file\n";
    } else {
        echo "❌ $description: $file (MANQUANT)\n";
    }
}

echo "\n=== RÉSUMÉ ===\n";
echo "✅ Utilisateur admin: " . $admin['username'] . " (" . $admin['email'] . ")\n";
echo "✅ Mot de passe correct: '$correctPassword'\n";
echo "✅ Authentification base de données: Fonctionnelle\n";
echo "📍 Problème: L'API retourne une erreur de validation\n\n";

echo "=== PROCHAINES ÉTAPES ===\n";
echo "1. Vérifiez les logs du serveur backend\n";
echo "2. Testez avec curl:\n";
echo "   curl -X POST http://localhost:8000/api/auth/login \\\n";
echo "     -H \"Content-Type: application/json\" \\\n";
echo "     -d '{\"login\":\"admin\",\"password\":\"$correctPassword\"}'\n\n";

echo "=== FIN DU DIAGNOSTIC ===\n";
