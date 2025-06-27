<?php
/**
 * DIAGNOSTIC SPÉCIFIQUE - Problème liste des projets
 * Script pour diagnostiquer pourquoi la liste des projets ne s'affiche pas
 */

// Éviter les problèmes de headers en CLI
define('CLI_MODE', true);

echo "=== DIAGNOSTIC LISTE PROJETS ===\n\n";

try {
    // 1. Chargement du Bootstrap
    echo "1. Chargement du système...\n";
    require_once __DIR__ . '/backend/Bootstrap.php';
    
    use TaskManager\Models\Project;
    use TaskManager\Models\User;
    use TaskManager\Database\Database;
    
    echo "✅ Système chargé\n\n";

    // 2. Test de connexion DB
    echo "2. Test base de données...\n";
    $db = Database::getInstance()->getConnection();
    echo "✅ DB connectée\n\n";

    // 3. Compter les projets dans la DB
    echo "3. Vérification des projets en base...\n";
    $stmt = $db->query("SELECT COUNT(*) as total FROM projects");
    $totalProjects = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "   Total projets en DB: $totalProjects\n";
    
    if ($totalProjects == 0) {
        echo "   ❌ Aucun projet en base ! C'est normal si vous venez de commencer.\n";
    } else {
        echo "   ✅ Des projets existent en base\n";
        
        // Afficher quelques projets
        $stmt = $db->query("SELECT id, name, status, owner_id, is_public FROM projects LIMIT 3");
        $sampleProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "   Exemples de projets:\n";
        foreach ($sampleProjects as $p) {
            echo "     - #{$p['id']}: {$p['name']} (status: {$p['status']}, owner: {$p['owner_id']}, public: {$p['is_public']})\n";
        }
    }
    echo "\n";

    // 4. Test des members de projets
    echo "4. Vérification des membres de projets...\n";
    $stmt = $db->query("SELECT COUNT(*) as total FROM project_members");
    $totalMembers = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    echo "   Total relations project_members: $totalMembers\n";
    
    if ($totalMembers == 0) {
        echo "   ❌ Aucune relation project_members ! Les projets ne seront pas visibles.\n";
        echo "   PROBLÈME IDENTIFIÉ: Les projets créés ne sont pas liés aux utilisateurs.\n";
    } else {
        echo "   ✅ Des relations project_members existent\n";
        
        // Afficher quelques relations
        $stmt = $db->query("SELECT pm.project_id, pm.user_id, pm.role, u.username FROM project_members pm LEFT JOIN users u ON pm.user_id = u.id LIMIT 5");
        $sampleMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "   Exemples de membres:\n";
        foreach ($sampleMembers as $m) {
            echo "     - Projet #{$m['project_id']} → User #{$m['user_id']} ({$m['username']}) role: {$m['role']}\n";
        }
    }
    echo "\n";

    // 5. Obtenir un utilisateur test
    echo "5. Obtention d'un utilisateur pour les tests...\n";
    $stmt = $db->query("SELECT id, username FROM users LIMIT 1");
    $testUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$testUser) {
        echo "   ❌ Aucun utilisateur en base\n";
        exit(1);
    }
    
    $testUserId = $testUser['id'];
    echo "   ✅ Utilisateur test: #{$testUserId} ({$testUser['username']})\n\n";

    // 6. Test du modèle Project::getProjectsForUser
    echo "6. Test de la méthode getProjectsForUser()...\n";
    $project = new Project();
    $result = $project->getProjectsForUser($testUserId);
    
    echo "   Résultat de getProjectsForUser($testUserId):\n";
    echo "   - Success: " . ($result['success'] ? 'true' : 'false') . "\n";
    
    if ($result['success']) {
        $projects = $result['data'];
        echo "   - Nombre de projets retournés: " . count($projects) . "\n";
        
        if (count($projects) > 0) {
            echo "   ✅ Des projets sont retournés par le modèle\n";
            echo "   Exemple de projet retourné:\n";
            $firstProject = $projects[0];
            foreach ($firstProject as $key => $value) {
                $displayValue = $value === null ? 'NULL' : $value;
                echo "     - $key: $displayValue\n";
            }
        } else {
            echo "   ❌ Aucun projet retourné par le modèle\n";
            echo "   PROBLÈME: L'utilisateur #{$testUserId} n'est membre d'aucun projet\n";
        }
    } else {
        echo "   ❌ Erreur dans getProjectsForUser: " . $result['message'] . "\n";
    }
    echo "\n";

    // 7. Simulation de l'API GET /projects
    echo "7. Simulation de l'API GET /projects...\n";
    
    // Simuler la logique de l'API
    try {
        $filters = [];
        $page = 1;
        $limit = 50;
        
        $apiResult = $project->getProjectsForUser($testUserId, $filters, $page, $limit);
        
        echo "   Simulation API avec pagination:\n";
        echo "   - Success: " . ($apiResult['success'] ? 'true' : 'false') . "\n";
        
        if ($apiResult['success']) {
            echo "   - Structure de réponse:\n";
            echo "     └─ data: " . count($apiResult['data']) . " projets\n";
            
            if (isset($apiResult['pagination'])) {
                $pagination = $apiResult['pagination'];
                echo "     └─ pagination:\n";
                echo "       - page: " . $pagination['page'] . "\n";
                echo "       - total: " . $pagination['total'] . "\n";
                echo "       - pages: " . $pagination['pages'] . "\n";
            }
            
            // Structure attendue par le frontend
            echo "\n   ✅ Structure de réponse API correcte\n";
            echo "   Structure pour le frontend:\n";
            echo "   {\n";
            echo "     \"success\": true,\n";
            echo "     \"data\": {\n";
            echo "       \"projects\": [...], // " . count($apiResult['data']) . " projets\n";
            echo "       \"pagination\": {...}\n";
            echo "     }\n";
            echo "   }\n";
        } else {
            echo "   ❌ Erreur API simulation: " . $apiResult['message'] . "\n";
        }
    } catch (Exception $e) {
        echo "   ❌ Exception simulation API: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 8. Vérifier la structure de réponse attendue par le frontend
    echo "8. Analyse du problème frontend...\n";
    echo "   Le frontend (ProjectList.js ligne 61) attend:\n";
    echo "   data.data.projects\n\n";
    
    echo "   Mais l'API retourne probablement:\n";
    echo "   data.data (directement un array de projets)\n\n";
    
    echo "   DIAGNOSTIC:\n";
    if ($totalMembers == 0) {
        echo "   ❌ PROBLÈME PRINCIPAL: Pas de relations project_members\n";
        echo "      → Les projets créés ne sont pas liés aux utilisateurs\n";
        echo "      → Même si des projets existent, ils ne sont pas visibles\n\n";
        
        echo "   SOLUTION: Corriger la création de projets pour ajouter l'utilisateur comme membre\n";
    } else {
        echo "   ❌ PROBLÈME PROBABLE: Structure de réponse API vs Frontend\n";
        echo "      → Le frontend attend data.data.projects\n";
        echo "      → Mais l'API retourne data.data (array direct)\n\n";
        
        echo "   SOLUTION: Corriger soit l'API soit le frontend\n";
    }

    // 9. Recommandations
    echo "9. RECOMMANDATIONS IMMÉDIATES:\n";
    echo "=========================================\n\n";
    
    if ($totalMembers == 0) {
        echo "1. CORRIGER LA CRÉATION DE PROJETS:\n";
        echo "   - Vérifier que addMember() est appelé après createProject()\n";
        echo "   - S'assurer que l'utilisateur créateur devient membre du projet\n\n";
        
        echo "2. SCRIPT DE RÉPARATION:\n";
        echo "   - Lier tous les projets existants à leurs propriétaires\n";
        echo "   - Exécuter: php fix_project_members.php\n\n";
    }
    
    echo "3. TEST MANUEL API:\n";
    echo "   curl -H \"Authorization: Bearer YOUR_TOKEN\" \\\n";
    echo "        http://localhost:8000/api/projects\n\n";
    
    echo "4. VÉRIFICATION FRONTEND:\n";
    echo "   - Ouvrir la console navigateur (F12)\n";
    echo "   - Aller sur /projects\n";
    echo "   - Vérifier la requête dans l'onglet Network\n";
    echo "   - Vérifier la réponse JSON\n\n";
    
    echo "5. DEBUG RECOMMANDÉ:\n";
    echo "   - Ajouter console.log() dans ProjectList.js ligne 62\n";
    echo "   - console.log('API Response:', data);\n";
    echo "   - Voir la structure exacte de la réponse\n\n";

} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "\n";
}

echo "=== FIN DIAGNOSTIC LISTE PROJETS ===\n";
?>
