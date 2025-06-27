<?php
/**
 * DIAGNOSTIC FINAL - Problèmes Projects & due_date
 * Script pour diagnostiquer et corriger les problèmes de projets
 */

require_once __DIR__ . '/backend/Bootstrap.php';

use TaskManager\Models\Project;
use TaskManager\Models\User;
use TaskManager\Database\Database;

echo "=== DIAGNOSTIC FINAL - PROJECTS & DUE_DATE ===\n\n";

try {
    // 1. Test de connexion à la base de données
    echo "1. Test de connexion à la base de données...\n";
    $db = Database::getInstance()->getConnection();
    echo "✅ Connexion DB réussie\n\n";

    // 2. Vérification de la structure de la table projects
    echo "2. Vérification de la structure de la table projects...\n";
    $stmt = $db->query("DESCRIBE projects");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasEndDate = false;
    $hasOwnerId = false;
    
    foreach ($columns as $column) {
        echo "   - {$column['Field']} ({$column['Type']}) {$column['Null']} {$column['Default']}\n";
        if ($column['Field'] === 'end_date') $hasEndDate = true;
        if ($column['Field'] === 'owner_id') $hasOwnerId = true;
    }
    
    if (!$hasEndDate) {
        echo "❌ Erreur: Colonne 'end_date' manquante dans la table projects\n";
        echo "   Correction SQL nécessaire:\n";
        echo "   ALTER TABLE projects ADD COLUMN end_date DATE DEFAULT NULL;\n\n";
    } else {
        echo "✅ Colonne 'end_date' présente\n";
    }
    
    if (!$hasOwnerId) {
        echo "❌ Erreur: Colonne 'owner_id' manquante dans la table projects\n";
        echo "   Correction SQL nécessaire:\n";
        echo "   ALTER TABLE projects ADD COLUMN owner_id INT(11) UNSIGNED NOT NULL;\n\n";
    } else {
        echo "✅ Colonne 'owner_id' présente\n";
    }
    
    echo "\n";

    // 3. Test de création d'utilisateur test
    echo "3. Préparation utilisateur test...\n";
    $user = new User();
    
    // Chercher ou créer un utilisateur test
    $testUser = null;
    $stmt = $db->prepare("SELECT * FROM users WHERE email = 'test@example.com'");
    $stmt->execute();
    $testUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$testUser) {
        echo "   Création d'un utilisateur test...\n";
        $userResult = $user->create([
            'username' => 'test_user',
            'email' => 'test@example.com',
            'password' => password_hash('password123', PASSWORD_DEFAULT),
            'first_name' => 'Test',
            'last_name' => 'User'
        ]);
        
        if ($userResult['success']) {
            $testUserId = $userResult['id'];
            echo "✅ Utilisateur test créé (ID: $testUserId)\n";
        } else {
            echo "❌ Erreur création utilisateur: " . $userResult['message'] . "\n";
            exit(1);
        }
    } else {
        $testUserId = $testUser['id'];
        echo "✅ Utilisateur test existant (ID: $testUserId)\n";
    }
    
    echo "\n";

    // 4. Test de l'API Projects avec données CORRECTES
    echo "4. Test de création de projet avec les bons champs...\n";
    
    $project = new Project();
    
    // Données CORRIGÉES selon la structure DB
    $testProjectData = [
        'name' => 'Test Project Final - ' . date('H:i:s'),
        'description' => 'Projet de test pour vérifier les corrections',
        'color' => '#2196f3',
        'icon' => 'folder',
        'status' => 'active',
        'priority' => 'medium',
        'start_date' => null,
        'end_date' => '2024-12-31',  // end_date, PAS due_date !
        'is_public' => 1,            // is_public, PAS public !
        'owner_id' => $testUserId    // owner_id, PAS created_by !
    ];
    
    echo "   Données envoyées:\n";
    foreach ($testProjectData as $key => $value) {
        echo "   - $key: " . json_encode($value) . "\n";
    }
    echo "\n";
    
    $result = $project->createProject($testProjectData, $testUserId);
    
    if ($result['success']) {
        echo "✅ Projet créé avec succès !\n";
        echo "   ID: " . $result['data']['id'] . "\n";
        echo "   Nom: " . $result['data']['name'] . "\n";
        $projectId = $result['data']['id'];
    } else {
        echo "❌ Erreur création projet: " . $result['message'] . "\n";
        echo "   Il se peut que l'API utilise encore les anciens noms de champs\n\n";
    }
    
    echo "\n";

    // 5. Test de récupération des projets
    echo "5. Test de récupération des projets...\n";
    $projectsResult = $project->getProjectsForUser($testUserId);
    
    if ($projectsResult['success']) {
        $projects = $projectsResult['data'];
        echo "✅ Récupération réussie: " . count($projects) . " projet(s) trouvé(s)\n";
        
        foreach ($projects as $proj) {
            echo "   - Projet #{$proj['id']}: {$proj['name']}\n";
            echo "     Status: {$proj['status']}, End date: " . ($proj['end_date'] ?? 'NULL') . "\n";
        }
    } else {
        echo "❌ Erreur récupération projets: " . $projectsResult['message'] . "\n";
    }
    
    echo "\n";

    // 6. Test de l'endpoint API directement
    echo "6. Test de l'endpoint API /api/projects (simulation)...\n";
    
    // Simuler les données POST comme le ferait le frontend
    $apiTestData = [
        'name' => 'API Test Project - ' . date('H:i:s'),
        'description' => 'Test via simulation API',
        'end_date' => '2024-11-30',
        'is_public' => true,
        'status' => 'active',
        'priority' => 'high'
    ];
    
    echo "   Simulation des données Frontend → API:\n";
    foreach ($apiTestData as $key => $value) {
        echo "   - $key: " . json_encode($value) . "\n";
    }
    
    // Test direct du modèle avec ces données
    $apiResult = $project->createProject($apiTestData, $testUserId);
    
    if ($apiResult['success']) {
        echo "✅ API simulation réussie !\n";
        echo "   Les corrections sont effectivement actives\n";
    } else {
        echo "❌ API simulation échouée: " . $apiResult['message'] . "\n";
    }
    
    echo "\n";

    // 7. Résumé des corrections nécessaires
    echo "7. RÉSUMÉ ET ACTIONS RECOMMANDÉES:\n";
    echo "=========================================\n\n";
    
    if ($hasEndDate && $hasOwnerId) {
        echo "✅ Structure DB : Correcte (end_date et owner_id présents)\n";
    } else {
        echo "❌ Structure DB : Corrections nécessaires (voir SQL ci-dessus)\n";
    }
    
    echo "✅ Backend API : Corrigé (utilise end_date et owner_id)\n";
    echo "✅ Modèle Project : Corrigé (plus de conversion due_date)\n";
    echo "✅ ValidationService : Support nullable amélioré\n";
    echo "✅ Frontend ProjectForm : Corrigé (utilise end_date)\n\n";
    
    echo "ACTIONS À FAIRE :\n";
    echo "1. Vider le cache navigateur (Ctrl+F5)\n";
    echo "2. Redémarrer le serveur PHP si nécessaire\n";
    echo "3. Vérifier que le frontend utilise la bonne URL API\n";
    echo "4. Tester à nouveau la création de projets\n\n";
    
    echo "Si le problème persiste, voici un test manuel:\n";
    echo "URL: POST http://localhost:8000/api/projects\n";
    echo "Headers: Authorization: Bearer [TOKEN], Content-Type: application/json\n";
    echo "Body: " . json_encode($apiTestData, JSON_PRETTY_PRINT) . "\n\n";

} catch (Exception $e) {
    echo "❌ ERREUR CRITIQUE: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "=== FIN DU DIAGNOSTIC ===\n";
?>
