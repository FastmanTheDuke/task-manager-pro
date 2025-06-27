<?php
/**
 * SCRIPT DE RÉPARATION - Projets sans membres
 * Répare les projets créés mais non visibles (problème project_members)
 */

define('CLI_MODE', true);

echo "=== RÉPARATION PROJETS SANS MEMBRES ===\n\n";

try {
    require_once __DIR__ . '/backend/Bootstrap.php';
    
    use TaskManager\Database\Database;
    
    $db = Database::getInstance()->getConnection();
    echo "✅ Connexion DB établie\n\n";

    // 1. Identifier les projets orphelins (sans membres)
    echo "1. Identification des projets orphelins...\n";
    
    $sql = "SELECT p.id, p.name, p.owner_id, u.username 
            FROM projects p 
            LEFT JOIN project_members pm ON p.id = pm.project_id 
            LEFT JOIN users u ON p.owner_id = u.id
            WHERE pm.project_id IS NULL";
    
    $stmt = $db->prepare($sql);
    $stmt->execute();
    $orphanProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "   Projets sans membres trouvés: " . count($orphanProjects) . "\n";
    
    if (count($orphanProjects) == 0) {
        echo "   ✅ Aucun projet orphelin ! Le problème est ailleurs.\n\n";
    } else {
        echo "   ❌ Projets orphelins identifiés:\n";
        foreach ($orphanProjects as $project) {
            echo "      - #{$project['id']}: {$project['name']} (propriétaire: {$project['username']} #{$project['owner_id']})\n";
        }
        echo "\n";
        
        // 2. Réparer en ajoutant les propriétaires comme membres
        echo "2. Réparation des projets orphelins...\n";
        
        $repaired = 0;
        $insertSql = "INSERT INTO project_members (project_id, user_id, role, joined_at) VALUES (?, ?, 'owner', NOW())";
        $insertStmt = $db->prepare($insertSql);
        
        foreach ($orphanProjects as $project) {
            try {
                $insertStmt->execute([$project['id'], $project['owner_id']]);
                echo "   ✅ Projet #{$project['id']} réparé (propriétaire ajouté comme membre)\n";
                $repaired++;
            } catch (Exception $e) {
                echo "   ❌ Erreur pour projet #{$project['id']}: " . $e->getMessage() . "\n";
            }
        }
        
        echo "\n   ✅ $repaired projets réparés sur " . count($orphanProjects) . "\n\n";
    }

    // 3. Vérification globale des relations
    echo "3. Vérification globale...\n";
    
    $totalProjects = $db->query("SELECT COUNT(*) FROM projects")->fetchColumn();
    $totalMembers = $db->query("SELECT COUNT(*) FROM project_members")->fetchColumn();
    $projectsWithMembers = $db->query("SELECT COUNT(DISTINCT project_id) FROM project_members")->fetchColumn();
    
    echo "   - Total projets: $totalProjects\n";
    echo "   - Total relations project_members: $totalMembers\n";
    echo "   - Projets avec au moins un membre: $projectsWithMembers\n";
    
    if ($totalProjects == $projectsWithMembers) {
        echo "   ✅ Tous les projets ont des membres !\n";
    } else {
        $orphans = $totalProjects - $projectsWithMembers;
        echo "   ❌ $orphans projet(s) encore orphelin(s)\n";
    }
    
    echo "\n";

    // 4. Test de récupération pour un utilisateur
    echo "4. Test de récupération des projets...\n";
    
    $stmt = $db->query("SELECT id, username FROM users LIMIT 1");
    $testUser = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($testUser) {
        $userId = $testUser['id'];
        $username = $testUser['username'];
        
        // Test avec la même requête que le modèle Project
        $testSql = "SELECT DISTINCT p.*, pm.role as user_role
                    FROM projects p
                    INNER JOIN project_members pm ON p.id = pm.project_id
                    WHERE pm.user_id = ?
                    ORDER BY p.updated_at DESC";
        
        $testStmt = $db->prepare($testSql);
        $testStmt->execute([$userId]);
        $userProjects = $testStmt->fetchAll(PDO::FETCH_ASSOC);
        
        echo "   Test pour utilisateur $username (#$userId):\n";
        echo "   - Projets visibles: " . count($userProjects) . "\n";
        
        if (count($userProjects) > 0) {
            echo "   ✅ L'utilisateur peut voir des projets !\n";
            foreach (array_slice($userProjects, 0, 3) as $project) {
                echo "      - #{$project['id']}: {$project['name']} (rôle: {$project['user_role']})\n";
            }
        } else {
            echo "   ❌ L'utilisateur ne voit aucun projet\n";
        }
    }
    
    echo "\n";

    // 5. Recommandations finales
    echo "5. INSTRUCTIONS FINALES:\n";
    echo "========================\n\n";
    
    if ($totalProjects == $projectsWithMembers && $totalProjects > 0) {
        echo "✅ PROJETS RÉPARÉS ! Maintenant :\n\n";
        echo "1. Videz le cache du navigateur (Ctrl+F5)\n";
        echo "2. Reconnectez-vous à l'application si nécessaire\n";
        echo "3. Allez sur la page /projects\n";
        echo "4. Vous devriez maintenant voir vos projets !\n\n";
        
        echo "Si la liste est encore vide :\n";
        echo "- Ouvrez la console navigateur (F12)\n";
        echo "- Allez dans l'onglet Network\n";
        echo "- Rechargez la page /projects\n";
        echo "- Vérifiez la requête GET /api/projects\n";
        echo "- Vérifiez la réponse JSON\n\n";
    } else {
        echo "❌ PROBLÈME PERSISTANT\n\n";
        echo "Actions recommandées :\n";
        echo "1. Exécutez : php diagnostic_liste_projets.php\n";
        echo "2. Vérifiez la structure de la table project_members\n";
        echo "3. Créez un nouveau projet pour tester\n\n";
    }

    echo "TEST MANUEL API (remplacez YOUR_TOKEN) :\n";
    echo "curl -H \"Authorization: Bearer YOUR_TOKEN\" \\\n";
    echo "     http://localhost:8000/api/projects\n\n";

} catch (Exception $e) {
    echo "❌ ERREUR: " . $e->getMessage() . "\n";
    echo "Fichier: " . $e->getFile() . " ligne " . $e->getLine() . "\n";
}

echo "=== FIN DE LA RÉPARATION ===\n";
?>
