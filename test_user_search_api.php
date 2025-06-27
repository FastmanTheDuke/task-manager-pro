<?php
/**
 * Script de test pour l'API de recherche d'utilisateurs
 * Usage: php test_user_search_api.php
 */

require_once __DIR__ . '/backend/Bootstrap.php';

use TaskManager\Bootstrap;
use TaskManager\Models\User;
use TaskManager\Config\JWTManager;

// Vérifier qu'on est bien en CLI
if (php_sapi_name() !== 'cli') {
    die("Ce script doit être exécuté en ligne de commande uniquement.\n");
}

// Initialiser l'application
Bootstrap::init();

try {
    echo "=== TEST API RECHERCHE D'UTILISATEURS ===\n\n";
    
    // 1. Récupérer ou créer un utilisateur de test
    $userModel = new User();
    
    // Essayer de trouver l'utilisateur admin
    $adminUser = $userModel->findByUsername('admin');
    if (!$adminUser) {
        // Essayer par email
        $adminUser = $userModel->findByEmail('admin@taskmanager.local');
    }
    
    if (!$adminUser) {
        echo "❌ Aucun utilisateur admin trouvé. Création d'un utilisateur de test...\n";
        
        $testUserData = [
            'username' => 'testuser',
            'email' => 'test@example.com',
            'password' => 'password123',
            'first_name' => 'Test',
            'last_name' => 'User'
        ];
        
        $result = $userModel->createUser($testUserData);
        if ($result['success']) {
            $adminUser = $userModel->findById($result['id']);
            echo "✅ Utilisateur de test créé\n";
        } else {
            die("❌ Impossible de créer un utilisateur de test: " . $result['message'] . "\n");
        }
    }
    
    echo "✅ Utilisateur trouvé: {$adminUser['username']} (ID: {$adminUser['id']})\n\n";
    
    // 2. Générer un token JWT
    $token = JWTManager::generateToken($adminUser);
    echo "✅ Token JWT généré\n\n";
    
    // 3. Simuler un appel API
    echo "2. SIMULATION D'APPEL API:\n";
    echo "---------------------------\n";
    
    $testQueries = ['jess', 'admin', 'test', 'user'];
    
    foreach ($testQueries as $query) {
        echo "🔍 Test pour '{$query}':\n";
        
        // Simuler les variables de session pour l'API
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REQUEST_URI'] = "/api/users/search?q={$query}";
        $_SERVER['HTTP_AUTHORIZATION'] = "Bearer {$token}";
        $_GET['q'] = $query;
        
        // Capturer la sortie
        ob_start();
        
        try {
            // Inclure directement le fichier API
            include __DIR__ . '/backend/api/users/search.php';
        } catch (Exception $e) {
            echo "   ❌ Erreur: " . $e->getMessage() . "\n";
        }
        
        $output = ob_get_clean();
        
        if ($output) {
            $response = json_decode($output, true);
            if ($response && isset($response['success'])) {
                if ($response['success']) {
                    echo "   ✅ Succès: " . count($response['data']) . " résultat(s)\n";
                    foreach ($response['data'] as $user) {
                        echo "   - {$user['username']} ({$user['email']})\n";
                    }
                } else {
                    echo "   ❌ Échec: " . $response['message'] . "\n";
                }
            } else {
                echo "   ❌ Réponse invalide: " . substr($output, 0, 100) . "...\n";
            }
        } else {
            echo "   ❌ Aucune réponse\n";
        }
        
        echo "\n";
    }
    
    // 4. Test d'authentification
    echo "3. TEST D'AUTHENTIFICATION:\n";
    echo "----------------------------\n";
    
    // Test sans token
    unset($_SERVER['HTTP_AUTHORIZATION']);
    $_GET['q'] = 'test';
    
    echo "🔒 Test sans token:\n";
    ob_start();
    
    try {
        include __DIR__ . '/backend/api/users/search.php';
    } catch (Exception $e) {
        echo "   ❌ Erreur attendue: " . $e->getMessage() . "\n";
    }
    
    $output = ob_get_clean();
    
    if ($output) {
        $response = json_decode($output, true);
        if ($response && !$response['success']) {
            echo "   ✅ Authentification requise (attendu): " . $response['message'] . "\n";
        } else {
            echo "   ❌ L'authentification devrait être requise\n";
        }
    }
    
    echo "\n";
    
    // 5. Test de curl pour vérification
    echo "4. COMMANDES CURL POUR TEST MANUEL:\n";
    echo "------------------------------------\n";
    
    echo "# Test avec authentification:\n";
    echo "curl -H \"Authorization: Bearer {$token}\" \\\n";
    echo "     \"http://localhost:8000/api/users/search?q=jess\"\n\n";
    
    echo "# Test sans authentification (devrait échouer):\n";
    echo "curl \"http://localhost:8000/api/users/search?q=jess\"\n\n";
    
    echo "# Login pour obtenir un token:\n";
    echo "curl -X POST \\\n";
    echo "     -H \"Content-Type: application/json\" \\\n";
    echo "     -d '{\"login\":\"{$adminUser['username']}\",\"password\":\"VOTRE_MOT_DE_PASSE\"}' \\\n";
    echo "     \"http://localhost:8000/api/auth/login\"\n\n";
    
    echo "=== FIN DU TEST ===\n";
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . "\n";
    echo "Ligne: " . $e->getLine() . "\n";
}
