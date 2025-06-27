<?php
/**
 * Script de debug pour la recherche d'utilisateurs
 * Usage: php debug_user_search.php
 */

require_once __DIR__ . '/backend/Bootstrap.php';

use TaskManager\Bootstrap;
use TaskManager\Models\User;

// Initialiser l'application
Bootstrap::init();

try {
    $userModel = new User();
    
    echo "=== DEBUG RECHERCHE D'UTILISATEURS ===\n\n";
    
    // 1. Lister tous les utilisateurs actifs
    echo "1. TOUS LES UTILISATEURS ACTIFS:\n";
    echo "-----------------------------------\n";
    
    $allUsers = $userModel->getActiveUsers(100);
    
    if (empty($allUsers)) {
        echo "❌ Aucun utilisateur actif trouvé dans la base de données!\n\n";
    } else {
        echo "✅ " . count($allUsers) . " utilisateur(s) actif(s) trouvé(s):\n";
        foreach ($allUsers as $user) {
            echo "- ID: {$user['id']}, Username: '{$user['username']}', Email: '{$user['email']}'";
            if (!empty($user['first_name']) || !empty($user['last_name'])) {
                echo ", Nom: '{$user['first_name']} {$user['last_name']}'";
            }
            echo "\n";
        }
    }
    
    echo "\n";
    
    // 2. Test de recherche avec différents termes
    $searchTerms = ['jess', 'admin', 'test', 'user', 'a'];
    
    echo "2. TESTS DE RECHERCHE:\n";
    echo "----------------------\n";
    
    foreach ($searchTerms as $term) {
        echo "🔍 Recherche pour '{$term}':\n";
        
        $results = $userModel->searchUsers($term);
        
        if (empty($results)) {
            echo "   ❌ Aucun résultat\n";
        } else {
            echo "   ✅ " . count($results) . " résultat(s):\n";
            foreach ($results as $user) {
                echo "   - ID: {$user['id']}, Username: '{$user['username']}', Email: '{$user['email']}'";
                if (!empty($user['first_name']) || !empty($user['last_name'])) {
                    echo ", Nom: '{$user['first_name']} {$user['last_name']}'";
                }
                echo "\n";
            }
        }
        echo "\n";
    }
    
    // 3. Recherche spécifique pour "jess" avec plus de détails
    echo "3. RECHERCHE DÉTAILLÉE POUR 'jess':\n";
    echo "------------------------------------\n";
    
    // Recherche directe en base
    $db = Bootstrap::getDatabase();
    $sql = "SELECT id, username, email, first_name, last_name, status 
            FROM users 
            WHERE (
                username LIKE '%jess%' 
                OR email LIKE '%jess%' 
                OR first_name LIKE '%jess%' 
                OR last_name LIKE '%jess%'
            )";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $directResults = $stmt->fetchAll();
    
    if (empty($directResults)) {
        echo "❌ Aucun utilisateur avec 'jess' trouvé en base (tous statuts)\n";
    } else {
        echo "✅ Utilisateur(s) avec 'jess' trouvé(s) en base:\n";
        foreach ($directResults as $user) {
            echo "- ID: {$user['id']}, Username: '{$user['username']}', Email: '{$user['email']}', Status: '{$user['status']}'";
            if (!empty($user['first_name']) || !empty($user['last_name'])) {
                echo ", Nom: '{$user['first_name']} {$user['last_name']}'";
            }
            echo "\n";
        }
    }
    
    echo "\n";
    
    // 4. Vérifier la structure de la table users
    echo "4. STRUCTURE DE LA TABLE USERS:\n";
    echo "--------------------------------\n";
    
    $sql = "DESCRIBE users";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $columns = $stmt->fetchAll();
    
    echo "Colonnes de la table users:\n";
    foreach ($columns as $column) {
        echo "- {$column['Field']} ({$column['Type']}) - NULL: {$column['Null']}, Default: {$column['Default']}\n";
    }
    
    echo "\n";
    
    // 5. Tester la création d'un utilisateur de test
    echo "5. CRÉATION D'UN UTILISATEUR DE TEST:\n";
    echo "-------------------------------------\n";
    
    $testUserData = [
        'username' => 'jesstest',
        'email' => 'jess.test@example.com',
        'password' => 'password123',
        'first_name' => 'Jessica',
        'last_name' => 'Test'
    ];
    
    // Vérifier si l'utilisateur existe déjà
    $existingUser = $userModel->findByUsername('jesstest');
    if ($existingUser) {
        echo "ℹ️  L'utilisateur de test 'jesstest' existe déjà\n";
    } else {
        $result = $userModel->createUser($testUserData);
        if ($result['success']) {
            echo "✅ Utilisateur de test 'jesstest' créé avec succès\n";
            
            // Test de recherche sur le nouvel utilisateur
            echo "🔍 Test de recherche sur le nouvel utilisateur:\n";
            $searchResults = $userModel->searchUsers('jess');
            echo "   Résultats pour 'jess': " . count($searchResults) . " trouvé(s)\n";
            
            foreach ($searchResults as $user) {
                echo "   - Username: '{$user['username']}', Email: '{$user['email']}'\n";
            }
        } else {
            echo "❌ Erreur lors de la création de l'utilisateur de test: " . $result['message'] . "\n";
        }
    }
    
    echo "\n=== FIN DU DEBUG ===\n";
    
} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Trace: " . $e->getTraceAsString() . "\n";
}
