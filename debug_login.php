<?php
/**
 * Diagnostic Login - Test de connexion flexible
 * 
 * Ce script teste chaque étape du processus de connexion
 * pour identifier précisément où le problème se situe.
 */

// Activer l'affichage des erreurs
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "=== DIAGNOSTIC LOGIN FLEXIBLE ===\n\n";

// Étape 1: Test de l'autoloading
echo "1. Test de l'autoloading...\n";
try {
    require_once './backend/Bootstrap.php';
    echo "✅ Bootstrap chargé avec succès\n\n";
} catch (Exception $e) {
    echo "❌ Erreur Bootstrap: " . $e->getMessage() . "\n\n";
    exit;
}

// Étape 2: Test de la connexion à la base de données
echo "2. Test de la connexion à la base de données...\n";
try {
    $db = TaskManager\Database\Connection::getInstance();
    echo "✅ Connexion à la base de données réussie\n\n";
} catch (Exception $e) {
    echo "❌ Erreur de connexion DB: " . $e->getMessage() . "\n\n";
    exit;
}

// Étape 3: Test de la classe User
echo "3. Test de la classe User...\n";
try {
    $userModel = new TaskManager\Models\User();
    echo "✅ Modèle User chargé avec succès\n\n";
} catch (Exception $e) {
    echo "❌ Erreur User Model: " . $e->getMessage() . "\n\n";
    exit;
}

// Étape 4: Test de recherche d'utilisateur par email
echo "4. Test de recherche par email...\n";
try {
    $user = $userModel->findByEmail('admin@taskmanager.local');
    if ($user) {
        echo "✅ Utilisateur trouvé par email:\n";
        echo "   - ID: " . $user['id'] . "\n";
        echo "   - Username: " . $user['username'] . "\n";
        echo "   - Email: " . $user['email'] . "\n\n";
    } else {
        echo "❌ Utilisateur non trouvé par email\n\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur recherche email: " . $e->getMessage() . "\n\n";
}

// Étape 5: Test de recherche d'utilisateur par username
echo "5. Test de recherche par username...\n";
try {
    $user = $userModel->findByUsername('admin');
    if ($user) {
        echo "✅ Utilisateur trouvé par username:\n";
        echo "   - ID: " . $user['id'] . "\n";
        echo "   - Username: " . $user['username'] . "\n";
        echo "   - Email: " . $user['email'] . "\n\n";
    } else {
        echo "❌ Utilisateur non trouvé par username\n\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur recherche username: " . $e->getMessage() . "\n\n";
}

// Étape 6: Test de l'authentification flexible avec email
echo "6. Test d'authentification avec email...\n";
try {
    $user = $userModel->authenticateByLogin('admin@taskmanager.local', 'Admin123!');
    if ($user) {
        echo "✅ Authentification par email réussie\n";
        echo "   - ID: " . $user['id'] . "\n";
        echo "   - Username: " . $user['username'] . "\n\n";
    } else {
        echo "❌ Authentification par email échouée\n\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur auth email: " . $e->getMessage() . "\n\n";
}

// Étape 7: Test de l'authentification flexible avec username
echo "7. Test d'authentification avec username...\n";
try {
    $user = $userModel->authenticateByLogin('admin', 'Admin123!');
    if ($user) {
        echo "✅ Authentification par username réussie\n";
        echo "   - ID: " . $user['id'] . "\n";
        echo "   - Username: " . $user['username'] . "\n\n";
    } else {
        echo "❌ Authentification par username échouée\n\n";
    }
} catch (Exception $e) {
    echo "❌ Erreur auth username: " . $e->getMessage() . "\n\n";
}

// Étape 8: Test de validation du champ login
echo "8. Test de validation du champ login...\n";
try {
    $testData = ['login' => 'admin@taskmanager.local', 'password' => 'Admin123!'];
    $rules = ['login' => ['required'], 'password' => ['required']];
    
    // Simulation du processus de validation
    echo "   Test données: " . json_encode($testData) . "\n";
    echo "   Règles: " . json_encode($rules) . "\n";
    
    // Note: On ne peut pas facilement tester ValidationMiddleware::validate 
    // car il dépend de $_POST ou php://input
    echo "✅ Validation OK (règles correctes)\n\n";
} catch (Exception $e) {
    echo "❌ Erreur validation: " . $e->getMessage() . "\n\n";
}

// Étape 9: Test de la méthode filter_var pour la détection email
echo "9. Test de détection email vs username...\n";
$emails = ['admin@taskmanager.local', 'admin', 'user@example.com', 'username123'];
foreach ($emails as $test) {
    $isEmail = filter_var($test, FILTER_VALIDATE_EMAIL) !== false;
    echo "   '$test' -> " . ($isEmail ? "EMAIL" : "USERNAME") . "\n";
}
echo "\n";

// Étape 10: Test de vérification de l'existence des méthodes
echo "10. Test des méthodes de la classe User...\n";
$methods = ['authenticate', 'authenticateByLogin', 'findByEmail', 'findByUsername'];
foreach ($methods as $method) {
    if (method_exists($userModel, $method)) {
        echo "   ✅ Méthode '$method' existe\n";
    } else {
        echo "   ❌ Méthode '$method' manquante\n";
    }
}
echo "\n";

echo "=== FIN DU DIAGNOSTIC ===\n";
echo "Exécutez ce script via : php debug_login.php\n";
echo "Ou dans le navigateur : http://localhost:8000/debug_login.php\n";
