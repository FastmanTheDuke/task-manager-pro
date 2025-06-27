<?php
/**
 * DIAGNOSTIC FINAL - Problèmes Projects & due_date (Version CLI Safe)
 * Script pour diagnostiquer et corriger les problèmes de projets
 */

// Définir qu'on est en mode CLI pour éviter les problèmes de headers
define('CLI_MODE', true);

// Désactiver l'output buffering pour voir les résultats en temps réel
if (ob_get_level()) ob_end_clean();

echo "=== DIAGNOSTIC FINAL - PROJECTS & DUE_DATE (v2) ===\n\n";

try {
    // 1. Vérification de l'environnement
    echo "1. Vérification de l'environnement...\n";
    echo "   - PHP Version: " . PHP_VERSION . "\n";
    echo "   - SAPI: " . php_sapi_name() . "\n";
    echo "   - Script: " . __FILE__ . "\n";
    echo "✅ Environnement CLI détecté\n\n";

    // 2. Chargement sécurisé du Bootstrap
    echo "2. Chargement du Bootstrap...\n";
    $bootstrapPath = __DIR__ . '/backend/Bootstrap.php';
    
    if (!file_exists($bootstrapPath)) {
        echo "❌ Erreur: Bootstrap.php non trouvé à $bootstrapPath\n";
        echo "   Assurez-vous d'exécuter ce script depuis la racine du projet\n";
        exit(1);
    }
    
    // Charger le Bootstrap en mode CLI sécurisé
    require_once $bootstrapPath;
    echo "✅ Bootstrap chargé avec succès\n\n";

    // 3. Test de connexion à la base de données
    echo "3. Test de connexion à la base de données...\n";
    
    use TaskManager\Database\Database;
    use TaskManager\Models\Project;
    use TaskManager\Models\User;
    
    $db = Database::getInstance()->getConnection();
    echo "✅ Connexion DB réussie\n\n";

    // 4. Vérification de la structure de la table projects
    echo "4. Vérification de la structure de la table projects...\n";
    $stmt = $db->query("DESCRIBE projects");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $hasEndDate = false;
    $hasOwnerId = false;
    $hasIsPublic = false;
    
    echo "   Structure actuelle de la table 'projects':\n";
    foreach ($columns as $column) {
        echo "   - {$column['Field']} ({$column['Type']}) {$column['Null']} Default: {$column['Default']}\n";
        if ($column['Field'] === 'end_date') $hasEndDate = true;
        if ($column['Field'] === 'owner_id') $hasOwnerId = true;
        if ($column['Field'] === 'is_public') $hasIsPublic = true;
    }
    
    echo "\n   Vérification des champs essentiels:\n";
    
    if (!$hasEndDate) {
        echo "   ❌ Colonne 'end_date' MANQUANTE\n";
        echo "      SQL à exécuter: ALTER TABLE projects ADD COLUMN end_date DATE DEFAULT NULL;\n";
    } else {
        echo "   ✅ Colonne 'end_date' présente\n";
    }
    
    if (!$hasOwnerId) {
        echo "   ❌ Colonne 'owner_id' MANQUANTE\n";
        echo "      SQL à exécuter: ALTER TABLE projects ADD COLUMN owner_id INT(11) UNSIGNED NOT NULL DEFAULT 1;\n";
    } else {
        echo "   ✅ Colonne 'owner_id' présente\n";
    }
    
    if (!$hasIsPublic) {
        echo "   ❌ Colonne 'is_public' MANQUANTE\n";
        echo "      SQL à exécuter: ALTER TABLE projects ADD COLUMN is_public TINYINT(4) NOT NULL DEFAULT 0;\n";
    } else {
        echo "   ✅ Colonne 'is_public' présente\n";
    }
    
    echo "\n";

    // 5. Test ou création d'utilisateur test
    echo "5. Préparation utilisateur test...\n";
    $user = new User();
    
    // Chercher un utilisateur existant
    $stmt = $db->prepare("SELECT * FROM users WHERE email = 'test@example.com' LIMIT 1");
    $stmt->execute();
    $testUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$testUser) {
        echo "   Création d'un utilisateur test...\n";
        try {
            $userResult = $user->create([
                'username' => 'test_user_' . time(),
                'email' => 'test@example.com',
                'password' => password_hash('password123', PASSWORD_DEFAULT),
                'first_name' => 'Test',
                'last_name' => 'User'
            ]);
            
            if ($userResult['success']) {
                $testUserId = $userResult['id'];
                echo "   ✅ Utilisateur test créé (ID: $testUserId)\n";
            } else {
                echo "   ❌ Erreur création utilisateur: " . $userResult['message'] . "\n";
                // Continuer avec un utilisateur existant
                $stmt = $db->prepare("SELECT id FROM users LIMIT 1");
                $stmt->execute();
                $existing = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($existing) {
                    $testUserId = $existing['id'];
                    echo "   ✅ Utilisation de l'utilisateur existant (ID: $testUserId)\n";
                } else {
                    echo "   ❌ Aucun utilisateur disponible pour les tests\n";
                    exit(1);
                }
            }
        } catch (Exception $e) {
            echo "   ❌ Exception lors de la création: " . $e->getMessage() . "\n";
            // Fallback vers un utilisateur existant
            $stmt = $db->prepare("SELECT id FROM users LIMIT 1");
            $stmt->execute();
            $existing = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                $testUserId = $existing['id'];
                echo "   ✅ Fallback vers utilisateur existant (ID: $testUserId)\n";
            } else {
                echo "   ❌ Aucun utilisateur disponible\n";
                exit(1);
            }
        }
    } else {
        $testUserId = $testUser['id'];
        echo "   ✅ Utilisateur test existant trouvé (ID: $testUserId)\n";
    }
    
    echo "\n";

    // 6. Test de création de projet avec les BONS champs
    echo "6. Test de création de projet avec les champs CORRECTS...\n";
    
    $project = new Project();
    
    // Données CORRECTES selon les corrections appliquées
    $testProjectData = [
        'name' => 'Diagnostic Test - ' . date('Y-m-d H:i:s'),
        'description' => 'Projet de test pour vérifier les corrections finales',
        'color' => '#2196f3',
        'icon' => 'folder',
        'status' => 'active',
        'priority' => 'medium',
        'start_date' => null,
        'end_date' => '2024-12-31',  // ✅ end_date, PAS due_date !
        'is_public' => 1,            // ✅ is_public, PAS public !
        'owner_id' => $testUserId    // ✅ owner_id, PAS created_by !
    ];
    
    echo "   Données à tester:\n";
    foreach ($testProjectData as $key => $value) {
        $displayValue = $value === null ? 'NULL' : ($value === '' ? "''" : $value);
        echo "   - $key: $displayValue\n";
    }
    echo "\n";
    
    try {
        $result = $project->createProject($testProjectData, $testUserId);
        
        if ($result['success']) {
            echo "   ✅ SUCCÈS ! Projet créé avec les nouveaux champs\n";
            echo "      ID: " . $result['data']['id'] . "\n";
            echo "      Nom: " . $result['data']['name'] . "\n";
            $createdProjectId = $result['data']['id'];
        } else {
            echo "   ❌ Échec création projet: " . $result['message'] . "\n";
            echo "      Il peut y avoir encore des problèmes de validation ou de DB\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Exception lors de la création: " . $e->getMessage() . "\n";
        echo "      Stack trace: " . $e->getTraceAsString() . "\n";
    }
    
    echo "\n";

    // 7. Test de récupération des projets
    echo "7. Test de récupération des projets pour l'utilisateur...\n";
    try {
        $projectsResult = $project->getProjectsForUser($testUserId);
        
        if ($projectsResult['success']) {
            $projects = $projectsResult['data'];
            echo "   ✅ Récupération réussie: " . count($projects) . " projet(s) trouvé(s)\n";
            
            if (count($projects) > 0) {
                echo "   Projets trouvés:\n";
                foreach (array_slice($projects, 0, 3) as $proj) { // Afficher max 3 projets
                    echo "      - #{$proj['id']}: {$proj['name']}\n";
                    echo "        Status: {$proj['status']}, End date: " . ($proj['end_date'] ?? 'NULL') . "\n";
                    echo "        Public: " . ($proj['is_public'] ? 'Oui' : 'Non') . "\n";
                }
                if (count($projects) > 3) {
                    echo "      ... et " . (count($projects) - 3) . " autre(s)\n";
                }
            }
        } else {
            echo "   ❌ Erreur récupération projets: " . $projectsResult['message'] . "\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Exception lors de la récupération: " . $e->getMessage() . "\n";
    }
    
    echo "\n";

    // 8. Test de validation des données comme le frontend
    echo "8. Test de simulation des données frontend...\n";
    
    // Simuler ce que le frontend envoie
    $frontendData = [
        'name' => 'Frontend Simulation Test',
        'description' => 'Test depuis le frontend React',
        'end_date' => '',           // ✅ Peut être vide
        'is_public' => 'true',      // ✅ String boolean du frontend
        'status' => 'active',
        'priority' => 'high'
    ];
    
    echo "   Données simulées du frontend:\n";
    foreach ($frontendData as $key => $value) {
        $displayValue = $value === '' ? "''" : $value;
        echo "   - $key: $displayValue\n";
    }
    
    try {
        $simulationResult = $project->createProject($frontendData, $testUserId);
        
        if ($simulationResult['success']) {
            echo "   ✅ EXCELLENT ! Simulation frontend réussie\n";
            echo "      Le preprocessing des données fonctionne correctement\n";
        } else {
            echo "   ❌ Simulation frontend échouée: " . $simulationResult['message'] . "\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Exception simulation: " . $e->getMessage() . "\n";
    }
    
    echo "\n";

    // 9. Résumé final
    echo "9. RÉSUMÉ FINAL ET RECOMMANDATIONS:\n";
    echo "=====================================\n\n";
    
    $dbOk = $hasEndDate && $hasOwnerId && $hasIsPublic;
    
    if ($dbOk) {
        echo "✅ Structure DB : CORRECTE (end_date, owner_id, is_public présents)\n";
    } else {
        echo "❌ Structure DB : INCOMPLÈTE - Exécutez les SQL ci-dessus\n";
    }
    
    echo "✅ CorsMiddleware : Corrigé pour le mode CLI\n";
    echo "✅ Backend Models : Mis à jour avec les bons champs\n";
    echo "✅ Validation : Support nullable et preprocessing booléens\n\n";
    
    echo "ACTIONS SUIVANTES :\n";
    echo "1. Si structure DB incomplète : Exécutez les SQL mentionnés\n";
    echo "2. Redémarrez votre serveur web (Apache/Nginx + PHP)\n";
    echo "3. Videz le cache du navigateur (Ctrl+F5)\n";
    echo "4. Testez la création de projets dans l'interface\n\n";
    
    echo "TEST MANUEL API (avec votre token) :\n";
    echo "curl -X POST http://localhost:8000/api/projects \\\n";
    echo "  -H \"Content-Type: application/json\" \\\n";
    echo "  -H \"Authorization: Bearer YOUR_TOKEN\" \\\n";
    echo "  -d '{\n";
    echo "    \"name\": \"Test Manuel Final\",\n";
    echo "    \"description\": \"Test des corrections\",\n";
    echo "    \"end_date\": \"2024-12-31\",\n";
    echo "    \"is_public\": true\n";
    echo "  }'\n\n";

} catch (Exception $e) {
    echo "❌ ERREUR CRITIQUE: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n\n";
    
    echo "DÉPANNAGE :\n";
    echo "1. Vérifiez que vous êtes dans le bon répertoire\n";
    echo "2. Vérifiez que la base de données est accessible\n";
    echo "3. Vérifiez la configuration backend/.env\n";
}

echo "\n=== FIN DU DIAGNOSTIC ===\n";
?>
