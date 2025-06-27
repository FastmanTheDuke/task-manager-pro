<?php
/**
 * Script de test pour vérifier la correction du problème de visibilité des projets
 * Usage: php test_projects_visibility_fix.php
 */

echo "=== TEST DE LA CORRECTION VISIBILITÉ PROJETS ===\n\n";

try {
    require_once 'backend/Bootstrap.php';
    
    use TaskManager\Database\DatabaseManager;
    use TaskManager\Models\Project;
    
    $db = DatabaseManager::getInstance();
    $pdo = $db->getConnection();
    
    if (!$pdo) {
        echo "❌ Erreur connexion base de données\n";
        exit;
    }
    
    echo "✅ Connexion DB réussie\n\n";
    
    // Récupérer l'ID utilisateur
    $userId = 1;
    echo "🔍 Test pour userId: $userId\n\n";
    
    $projectModel = new Project();
    
    // 1. Test requête getProjectStats (utilisée par le dashboard)
    echo "1. TEST REQUÊTE STATS (DASHBOARD)\n";
    echo str_repeat("-", 50) . "\n";
    
    $stats = $projectModel->getProjectStats($userId);
    echo "Stats dashboard:\n";
    echo "  • Total: {$stats['total']}\n";
    echo "  • Actifs: {$stats['active']}\n";
    echo "  • Complétés: {$stats['completed']}\n";
    echo "  • En retard: {$stats['overdue']}\n";
    
    // 2. Test requête getProjectsForUser (utilisée par la page projets)
    echo "\n2. TEST REQUÊTE LISTE PROJETS (PAGE PROJETS)\n";
    echo str_repeat("-", 50) . "\n";
    
    $result = $projectModel->getProjectsForUser($userId);
    if ($result['success']) {
        $projects = $result['data'];
        echo "Projets dans la liste: " . count($projects) . "\n";
        foreach ($projects as $project) {
            echo "  • ID:{$project['id']} - {$project['name']} - Status:{$project['status']} - Role:{$project['user_role']}\n";
        }
    } else {
        echo "❌ Erreur: {$result['message']}\n";
    }
    
    // 3. Test de la correction de l'API dashboard
    echo "\n3. TEST SIMULATION API DASHBOARD (APRÈS CORRECTION)\n";
    echo str_repeat("-", 50) . "\n";
    
    // Simulation de la fonction getRecentProjects corrigée
    $dashboardResult = $projectModel->getProjectsForUser($userId, [], 1, 6);
    $recentProjects = $dashboardResult['success'] ? $dashboardResult['data'] : [];
    
    echo "Projets récents (dashboard): " . count($recentProjects) . "\n";
    foreach ($recentProjects as $project) {
        echo "  • ID:{$project['id']} - {$project['name']} - Status:{$project['status']}\n";
    }
    
    // 4. Comparaison finale
    echo "\n4. COMPARAISON FINALE\n";
    echo str_repeat("=", 50) . "\n";
    
    $dashboardCount = $stats['total'];
    $pageCount = $result['success'] ? count($result['data']) : 0;
    $dashboardRecentCount = count($recentProjects);
    
    echo "Dashboard annonce: $dashboardCount projets\n";
    echo "Page projets montre: $pageCount projets\n";
    echo "Dashboard projets récents: $dashboardRecentCount projets\n";
    
    if ($dashboardCount == $pageCount && $dashboardRecentCount > 0) {
        echo "\n✅ CORRECTION RÉUSSIE !\n";
        echo "• Les données sont maintenant cohérentes\n";
        echo "• Le dashboard et la page projets affichent le même nombre de projets\n";
        echo "• Les projets récents sont correctement récupérés\n";
    } elseif ($dashboardCount == $pageCount) {
        echo "\n⚠️  DONNÉES COHÉRENTES MAIS VÉRIFICATION NÉCESSAIRE\n";
        echo "• Vérifiez le frontend pour s'assurer que les données sont bien affichées\n";
    } else {
        echo "\n❌ PROBLÈME PERSISTANT\n";
        echo "• Différence entre dashboard ($dashboardCount) et page projets ($pageCount)\n";
        echo "• Investigation supplémentaire nécessaire\n";
    }
    
    // 5. Test d'une simulation complète d'appel API
    echo "\n5. TEST SIMULATION APPEL API COMPLET\n";
    echo str_repeat("-", 50) . "\n";
    
    // Simulation de l'appel API dashboard
    try {
        $dashboardData = [
            'stats' => $stats,
            'recentProjects' => $recentProjects
        ];
        
        echo "✅ API Dashboard : " . json_encode($dashboardData['stats']) . "\n";
        echo "✅ Projets récents : " . count($dashboardData['recentProjects']) . " projets récupérés\n";
        
        // Simulation de l'appel API projets
        $projectsApiData = [
            'projects' => $result['data'],
            'pagination' => $result['pagination'] ?? null
        ];
        
        echo "✅ API Projets : " . count($projectsApiData['projects']) . " projets récupérés\n";
        
    } catch (Exception $e) {
        echo "❌ Erreur simulation API: " . $e->getMessage() . "\n";
    }
    
    // 6. Vérification des orphelins (si nécessaire)
    echo "\n6. VÉRIFICATION PROJETS ORPHELINS\n";
    echo str_repeat("-", 50) . "\n";
    
    $sql = "SELECT COUNT(*) as count FROM projects p 
            LEFT JOIN project_members pm ON p.id = pm.project_id AND pm.user_id = p.owner_id 
            WHERE pm.project_id IS NULL";
    $stmt = $pdo->query($sql);
    $orphanCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($orphanCount == 0) {
        echo "✅ Aucun projet orphelin\n";
    } else {
        echo "⚠️  $orphanCount projet(s) orphelin(s) détecté(s)\n";
    }
    
    echo "\n" . str_repeat("=", 50) . "\n";
    echo "TEST TERMINÉ - Vérifiez maintenant votre application web\n";
    echo str_repeat("=", 50) . "\n";
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}
