<?php

namespace TaskManager\Models;

use PDO;
use Exception;

class Project extends BaseModel {
    protected string $table = 'projects';

    protected array $fillable = [
        'name',
        'description',
        'status',
        'priority',
        'end_date',
        'color',
        'is_public',
        'owner_id' // Le propriétaire est `owner_id` dans la DB
    ];

    /**
     * Crée un nouveau projet et assigne le créateur comme propriétaire.
     */
    public function createProject(array $data, int $userId): array
    {
        try {
            $this->db->beginTransaction();

            // Préparation des données avec les valeurs par défaut
            $projectData = [
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
                'status' => $data['status'] ?? 'active',
                'priority' => $data['priority'] ?? 'medium',
                'end_date' => $data['due_date'] ?? null, // Mapping de due_date du formulaire vers end_date de la DB
                'color' => $data['color'] ?? '#3B82F6',
                'is_public' => (isset($data['is_public']) && $data['is_public']) ? 1 : 0,
                'owner_id' => $userId
            ];

            // Création du projet en utilisant la méthode parente
            $result = $this->create($projectData);

            if (!$result['success']) {
                $this->db->rollBack();
                return $result;
            }

            $projectId = $result['id'];

            // Ajout du créateur comme membre avec le rôle 'owner'
            $this->addMember($projectId, $userId, 'owner');

            $this->db->commit();

            // Retourner les détails complets du projet créé
            return $this->getProjectById($projectId, $userId);

        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error creating project: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur lors de la création du projet.'];
        }
    }
    
    /**
     * Récupère les projets pour un utilisateur avec filtres et pagination.
     */
    public function getProjectsForUser(int $userId, array $filters = [], int $page = 1, int $limit = 50): array
    {
        try {
            $offset = ($page - 1) * $limit;
            
            $whereConditions = ["(pm.user_id = :user_id OR p.is_public = 1)"];
            $params = [':user_id' => $userId];

            if (!empty($filters['search'])) {
                $whereConditions[] = "(p.name LIKE :search OR p.description LIKE :search)";
                $params[':search'] = '%' . $filters['search'] . '%';
            }
            if (!empty($filters['status']) && $filters['status'] !== 'all') {
                $whereConditions[] = "p.status = :status";
                $params[':status'] = $filters['status'];
            }
            if (!empty($filters['role']) && $filters['role'] !== 'all') {
                $whereConditions[] = "pm.role = :role";
                $params[':role'] = $filters['role'];
            }

            $whereClause = implode(' AND ', $whereConditions);

            $sortBy = 'p.updated_at';
            $sortOrder = 'DESC';
            $validSorts = ['name', 'created_at', 'updated_at', 'end_date'];
            if (!empty($filters['sortBy']) && in_array($filters['sortBy'], $validSorts)) {
                $sortBy = 'p.' . $filters['sortBy'];
            }
            if (!empty($filters['sortOrder']) && in_array(strtolower($filters['sortOrder']), ['asc', 'desc'])) {
                $sortOrder = strtoupper($filters['sortOrder']);
            }
            $orderByClause = "$sortBy $sortOrder";
            
            // ** CORRECTION SQL ICI **
            $sql = "SELECT DISTINCT p.*, 
                           pm.role as user_role,
                           u.username as owner_username,
                           (pf.project_id IS NOT NULL) as is_favorite, -- Correction ici
                           (SELECT COUNT(*) FROM project_members pm2 WHERE pm2.project_id = p.id) as members_count,
                           (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id) as tasks_total,
                           (SELECT COUNT(*) FROM tasks t WHERE t.project_id = p.id AND t.status = 'completed') as tasks_completed
                    FROM projects p
                    LEFT JOIN project_members pm ON p.id = pm.project_id
                    LEFT JOIN users u ON p.owner_id = u.id
                    LEFT JOIN project_favorites pf ON p.id = pf.project_id AND pf.user_id = :user_id
                    WHERE $whereClause
                    ORDER BY $orderByClause
                    LIMIT :limit OFFSET :offset";
            
            $stmt = $this->db->prepare($sql);
            foreach ($params as $key => &$value) {
                $stmt->bindParam($key, $value);
            }
            $stmt->bindParam(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindParam(':offset', $offset, PDO::PARAM_INT);
            
            $stmt->execute();
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $countSql = "SELECT COUNT(DISTINCT p.id) FROM projects p LEFT JOIN project_members pm ON p.id = pm.project_id WHERE $whereClause";
            $countStmt = $this->db->prepare($countSql);
            $countStmt->execute($params);
            $total = $countStmt->fetchColumn();

            return [
                'success' => true,
                'data' => [
                    'projects' => $projects,
                    'pagination' => [
                        'page' => $page,
                        'limit' => $limit,
                        'total' => (int)$total,
                        'pages' => ceil($total / $limit)
                    ],
                    'stats' => $this->getProjectStats($userId)
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Error fetching projects: " . $e->getMessage());
            return ['success' => false, 'message' => 'Erreur serveur lors de la récupération des projets.'];
        }
    }

    // Le reste des fonctions (getProjectById, updateProject, etc.) reste ici.
    // Pour la clarté, je ne les inclus pas toutes, mais assurez-vous de bien remplacer TOUT le fichier.
    // ... (collez le reste des fonctions du fichier original ici si nécessaire)
}
