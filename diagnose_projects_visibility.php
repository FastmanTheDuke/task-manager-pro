<?php
/**
 * Diagnostic du problème : projets visibles dashboard mais pas dans la page projets
 * Usage: php diagnose_projects_visibility.php
 */

echo "=== DIAGNOSTIC VISIBILITÉ PROJETS ===\n\n";

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
    
    // Récupérer l'ID utilisateur (supposons ID 1 pour test)
    $userId = 1;
    echo "🔍 Diagnostic pour userId: $userId\n\n";
    
    // 1. Vérifier les projets dans la table projects
    echo "1. PROJETS DANS LA TABLE 'projects'\n";
    echo str_repeat("-", 50) . "\n";
    
    $sql = "SELECT p.id, p.name, p.status, p.owner_id, p.created_at 
            FROM projects p 
            ORDER BY p.created_at DESC";
    $stmt = $pdo->query($sql);
    $allProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total projets en base: " . count($allProjects) . "\n";
    foreach ($allProjects as $project) {
        $ownerStatus = ($project['owner_id'] == $userId) ? '👑 OWNER' : '   other';
        echo "  • ID:{$project['id']} - {$project['name']} - Status:{$project['status']} - $ownerStatus\n";
    }
    
    // 2. Vérifier les membres dans project_members
    echo "\n2. MEMBRES DANS LA TABLE 'project_members'\n";
    echo str_repeat("-", 50) . "\n";
    
    $sql = "SELECT pm.project_id, pm.user_id, pm.role, p.name 
            FROM project_members pm 
            LEFT JOIN projects p ON pm.project_id = p.id 
            ORDER BY pm.project_id";
    $stmt = $pdo->query($sql);
    $allMembers = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Total relations membres: " . count($allMembers) . "\n";
    foreach ($allMembers as $member) {
        $userStatus = ($member['user_id'] == $userId) ? '👤 YOU' : "   user {$member['user_id']}";
        echo "  • Projet ID:{$member['project_id']} ({$member['name']}) - Role:{$member['role']} - $userStatus\n";
    }
    
    // 3. Projets où l'utilisateur est membre
    echo "\n3. PROJETS OÙ VOUS ÊTES MEMBRE\n";
    echo str_repeat("-", 50) . "\n";
    
    $sql = "SELECT p.id, p.name, p.status, pm.role, p.owner_id
            FROM projects p
            INNER JOIN project_members pm ON p.id = pm.project_id
            WHERE pm.user_id = ?
            ORDER BY p.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $userProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Projets accessibles: " . count($userProjects) . "\n";
    foreach ($userProjects as $project) {
        echo "  • ID:{$project['id']} - {$project['name']} - Status:{$project['status']} - Role:{$project['role']}\n";
    }
    
    // 4. Test requête STATS (dashboard)
    echo "\n4. TEST REQUÊTE STATS (DASHBOARD)\n";
    echo str_repeat("-", 50) . "\n";
    
    $sql = "SELECT 
                COUNT(DISTINCT p.id) as total,
                SUM(CASE WHEN p.status = 'active' THEN 1 ELSE 0 END) as active,
                SUM(CASE WHEN p.status = 'completed' THEN 1 ELSE 0 END) as completed,
                SUM(CASE WHEN p.end_date < CURDATE() AND p.status != 'completed' THEN 1 ELSE 0 END) as overdue
            FROM projects p
            INNER JOIN project_members pm ON p.id = pm.project_id
            WHERE pm.user_id = ?
            AND p.status != 'archived'";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Stats dashboard:\n";
    echo "  • Total: {$stats['total']}\n";
    echo "  • Actifs: {$stats['active']}\n";
    echo "  • Complétés: {$stats['completed']}\n";
    echo "  • En retard: {$stats['overdue']}\n";
    
    // 5. Test requête LISTE PROJETS (page projets)
    echo "\n5. TEST REQUÊTE LISTE PROJETS (PAGE PROJETS)\n";
    echo str_repeat("-", 50) . "\n";
    
    $sql = "SELECT DISTINCT p.*, 
                   pm.role as user_role,
                   u.username as owner_username
            FROM projects p
            INNER JOIN project_members pm ON p.id = pm.project_id
            LEFT JOIN users u ON p.owner_id = u.id
            WHERE pm.user_id = ?
            ORDER BY p.updated_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$userId]);
    $listProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "Projets dans la liste: " . count($listProjects) . "\n";
    foreach ($listProjects as $project) {
        echo "  • ID:{$project['id']} - {$project['name']} - Status:{$project['status']} - Role:{$project['user_role']}\n";
    }
    
    // 6. Test avec modèle Project
    echo "\n6. TEST AVEC MODÈLE PROJECT\n";
    echo str_repeat("-", 50) . "\n";
    
    $projectModel = new Project();
    
    // Test getProjectStats
    $modelStats = $projectModel->getProjectStats($userId);
    echo "Stats via modèle: Total={$modelStats['total']}, Actifs={$modelStats['active']}\n";
    
    // Test getProjectsForUser
    $modelProjects = $projectModel->getProjectsForUser($userId);
    if ($modelProjects['success']) {
        echo "Projets via modèle: " . count($modelProjects['data']) . "\n";
        foreach ($modelProjects['data'] as $project) {
            echo "  • ID:{$project['id']} - {$project['name']} - Status:{$project['status']}\n";
        }
    } else {
        echo "❌ Erreur modèle: {$modelProjects['message']}\n";
    }
    
    // 7. Vérifier les projets orphelins
    echo "\n7. VÉRIFICATION PROJETS ORPHELINS\n";
    echo str_repeat("-", 50) . "\n";
    
    $sql = "SELECT p.id, p.name, p.owner_id 
            FROM projects p 
            LEFT JOIN project_members pm ON p.id = pm.project_id AND pm.user_id = p.owner_id 
            WHERE pm.project_id IS NULL";
    $stmt = $pdo->query($sql);
    $orphanProjects = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($orphanProjects)) {
        echo "✅ Aucun projet orphelin trouvé\n";
    } else {
        echo "⚠️  Projets orphelins (propriétaire pas membre): " . count($orphanProjects) . "\n";
        foreach ($orphanProjects as $orphan) {
            echo "  • ID:{$orphan['id']} - {$orphan['name']} - Owner:{$orphan['owner_id']}\n";
            
            // Réparer automatiquement
            echo "    🔧 Réparation: Ajout du propriétaire comme membre...\n";
            $repairSql = "INSERT INTO project_members (project_id, user_id, role) VALUES (?, ?, 'owner')";
            $repairStmt = $pdo->prepare($repairSql);
            if ($repairStmt->execute([$orphan['id'], $orphan['owner_id']])) {
                echo "    ✅ Réparé\n";
            } else {
                echo "    ❌ Erreur réparation\n";
            }
        }
    }
    
    // 8. Diagnostic spécifique au problème
    echo "\n8. DIAGNOSTIC FINAL\n";
    echo str_repeat("=", 50) . "\n";
    
    $dashboardCount = $stats['total'];
    $pageCount = count($listProjects);
    
    echo "Dashboard annonce: $dashboardCount projets\n";
    echo "Page projets montre: $pageCount projets\n";
    
    if ($dashboardCount > $pageCount) {
        echo "\n🔍 PROBLÈME IDENTIFIÉ:\n";
        echo "Le dashboard compte plus de projets que la page n'en affiche.\n";
        echo "Causes possibles:\n";
        echo "• Projets archivés comptés dans stats mais filtrés dans la liste\n";
        echo "• Problème de jointure ou permissions\n";
        echo "• Problème frontend d'affichage\n";
        
        // Vérifier si des projets archivés
        $archivedSql = "SELECT COUNT(*) FROM projects p 
                       INNER JOIN project_members pm ON p.id = pm.project_id 
                       WHERE pm.user_id = ? AND p.status = 'archived'";
        $archivedStmt = $pdo->prepare($archivedSql);
        $archivedStmt->execute([$userId]);
        $archivedCount = $archivedStmt->fetchColumn();
        
        echo "• Projets archivés: $archivedCount\n";
        
    } else if ($dashboardCount == $pageCount) {
        echo "\n✅ DONNÉES COHÉRENTES\n";
        echo "Le problème vient probablement du frontend React.\n";
        echo "Vérifiez:\n";
        echo "• Console navigateur (F12) pour erreurs JavaScript\n";
        echo "• Network tab pour voir si l'API est appelée\n";
        echo "• Données reçues par le frontend\n";
    } else {
        echo "\n⚠️  ANOMALIE\n";
        echo "La page affiche plus de projets que le dashboard en compte.\n";
    }
    
} catch (Exception $e) {
    echo "❌ Erreur: " . $e->getMessage() . "\n";
    echo "Stack trace: " . $e->getTraceAsString() . "\n";
}

echo "\n=== DIAGNOSTIC TERMINÉ ===\n";
