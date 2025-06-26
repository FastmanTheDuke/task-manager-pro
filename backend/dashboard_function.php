function handleDashboard(): void
{
    try {
        $userId = AuthMiddleware::getCurrentUserId();
        
        // Données de base pour le dashboard
        $dashboardData = [
            'stats' => [
                'totalTasks' => 0,
                'completedTasks' => 0,
                'pendingTasks' => 0,
                'overdueTasks' => 0,
                'totalProjects' => 0,
                'activeProjects' => 0,
                'totalTimeTracked' => 0,
                'tasksCompletedThisWeek' => 0
            ],
            'recentTasks' => [],
            'recentProjects' => [],
            'upcomingDeadlines' => [],
            'productivity' => [
                'labels' => [],
                'completedTasks' => [],
                'timeSpent' => [],
                'lastWeekTasks' => 0
            ],
            'timeTracking' => []
        ];
        
        // Essayer de récupérer les vraies statistiques
        try {
            $taskModel = new Task();
            
            // Obtenir les statistiques complètes des tâches
            $stats = $taskModel->getStatistics($userId);
            
            // Obtenir les tâches récentes
            $recentTasks = $taskModel->getUserTasks($userId, [], [
                'limit' => 5,
                'order_by' => 'created_at',
                'order_dir' => 'DESC'
            ]);
            
            // Obtenir les échéances prochaines (tâches avec due_date dans les 7 prochains jours)
            $upcomingDeadlines = $taskModel->getUserTasks($userId, [
                'due_date_from' => date('Y-m-d'),
                'due_date_to' => date('Y-m-d', strtotime('+7 days'))
            ], [
                'limit' => 5,
                'order_by' => 'due_date',
                'order_dir' => 'ASC'
            ]);
            
            // Mettre à jour les données avec les vraies statistiques
            $dashboardData['stats'] = [
                'totalTasks' => (int)($stats['total_tasks'] ?? 0),
                'completedTasks' => (int)($stats['completed_tasks'] ?? 0),
                'pendingTasks' => (int)(($stats['pending_tasks'] ?? 0) + ($stats['in_progress_tasks'] ?? 0)),
                'overdueTasks' => (int)($stats['overdue_tasks'] ?? 0),
                'totalProjects' => 0, // À implémenter quand le modèle Project sera disponible
                'activeProjects' => 0,
                'totalTimeTracked' => 0, // À implémenter
                'tasksCompletedThisWeek' => (int)($stats['completed_tasks'] ?? 0) // Approximation
            ];
            
            $dashboardData['recentTasks'] = is_array($recentTasks) ? $recentTasks : [];
            $dashboardData['upcomingDeadlines'] = is_array($upcomingDeadlines) ? $upcomingDeadlines : [];
            
        } catch (\Exception $e) {
            error_log('Dashboard stats error: ' . $e->getMessage());
            // Garder les valeurs par défaut en cas d'erreur
        }
        
        ResponseService::success($dashboardData, 'Dashboard data retrieved successfully');
        
    } catch (\Exception $e) {
        error_log('Dashboard error: ' . $e->getMessage());
        ResponseService::error('Erreur lors du chargement du dashboard: ' . $e->getMessage(), 500);
    }
}