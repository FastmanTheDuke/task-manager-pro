<?php
namespace TaskManager\Controllers;

use TaskManager\Database\Connection;
use TaskManager\Services\ResponseService;

class DiagnosticController
{
    /**
     * Endpoint de diagnostic système
     * GET /api/diagnostic/system
     */
    public static function systemCheck(): void
    {
        try {
            $diagnostics = [
                'timestamp' => date('Y-m-d H:i:s'),
                'php' => [
                    'version' => PHP_VERSION,
                    'sapi' => php_sapi_name(),
                    'os' => PHP_OS,
                    'memory_limit' => ini_get('memory_limit'),
                    'max_execution_time' => ini_get('max_execution_time')
                ],
                'extensions' => [
                    'pdo' => extension_loaded('pdo'),
                    'pdo_mysql' => extension_loaded('pdo_mysql'),
                    'mysqli' => extension_loaded('mysqli'),
                    'json' => extension_loaded('json'),
                    'mbstring' => extension_loaded('mbstring'),
                    'openssl' => extension_loaded('openssl')
                ],
                'pdo_constants' => [
                    'MYSQL_ATTR_INIT_COMMAND' => defined('PDO::MYSQL_ATTR_INIT_COMMAND'),
                    'ATTR_ERRMODE' => defined('PDO::ATTR_ERRMODE'),
                    'ERRMODE_EXCEPTION' => defined('PDO::ERRMODE_EXCEPTION')
                ],
                'database' => Connection::getDiagnostics(),
                'environment' => [
                    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'CLI',
                    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? getcwd(),
                    'request_method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI'
                ]
            ];
            
            // Test de connexion base de données
            try {
                $connectionTest = Connection::testConnection();
                $diagnostics['database']['connection_status'] = $connectionTest ? 'SUCCESS' : 'FAILED';
            } catch (Exception $e) {
                $diagnostics['database']['connection_status'] = 'ERROR: ' . $e->getMessage();
            }
            
            ResponseService::success('Diagnostic système complet', $diagnostics);
            
        } catch (Exception $e) {
            ResponseService::error('Erreur lors du diagnostic', 500, [
                'error' => $e->getMessage(),
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
        }
    }
    
    /**
     * Diagnostic spécifique de la base de données
     * GET /api/diagnostic/database
     */
    public static function databaseCheck(): void
    {
        try {
            $db = Connection::getInstance();
            
            // Informations de base
            $info = [
                'connection_status' => 'Connected',
                'config' => Connection::getConfig(),
                'requirements' => Connection::checkRequirements()
            ];
            
            // Test des tables principales
            $tables = ['users', 'projects', 'tasks', 'tags', 'comments', 'attachments'];
            $tableStatus = [];
            
            foreach ($tables as $table) {
                try {
                    $stmt = $db->prepare("SHOW TABLES LIKE ?");
                    $stmt->execute([$table]);
                    $exists = $stmt->rowCount() > 0;
                    
                    if ($exists) {
                        // Compter les enregistrements
                        $countStmt = $db->prepare("SELECT COUNT(*) as count FROM `{$table}`");
                        $countStmt->execute();
                        $count = $countStmt->fetch()['count'];
                        
                        $tableStatus[$table] = [
                            'exists' => true,
                            'records' => $count
                        ];
                    } else {
                        $tableStatus[$table] = [
                            'exists' => false,
                            'records' => 0
                        ];
                    }
                } catch (Exception $e) {
                    $tableStatus[$table] = [
                        'exists' => false,
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            $info['tables'] = $tableStatus;
            
            // Test de performance
            $start = microtime(true);
            $db->query("SELECT 1");
            $queryTime = round((microtime(true) - $start) * 1000, 2);
            $info['performance'] = [
                'simple_query_time_ms' => $queryTime
            ];
            
            ResponseService::success('Diagnostic base de données', $info);
            
        } catch (Exception $e) {
            ResponseService::error('Erreur de diagnostic DB: ' . $e->getMessage(), 500, [
                'config' => Connection::getConfig(),
                'requirements' => Connection::checkRequirements()
            ]);
        }
    }
    
    /**
     * Test spécifique de l'authentification
     * GET /api/diagnostic/auth
     */
    public static function authCheck(): void
    {
        try {
            $db = Connection::getInstance();
            
            // Vérifier la table users
            $stmt = $db->prepare("SHOW TABLES LIKE 'users'");
            $stmt->execute();
            
            if ($stmt->rowCount() === 0) {
                ResponseService::error('Table users introuvable', 500);
                return;
            }
            
            // Compter les utilisateurs
            $stmt = $db->query("SELECT COUNT(*) as total FROM users");
            $userCount = $stmt->fetch()['total'];
            
            // Vérifier l'admin
            $stmt = $db->prepare("SELECT id, username, email, role, status FROM users WHERE role = 'admin' LIMIT 1");
            $stmt->execute();
            $admin = $stmt->fetch();
            
            $authInfo = [
                'users_table_exists' => true,
                'total_users' => $userCount,
                'admin_user' => $admin ? [
                    'id' => $admin['id'],
                    'username' => $admin['username'],
                    'email' => $admin['email'],
                    'role' => $admin['role'],
                    'status' => $admin['status']
                ] : null,
                'password_functions' => [
                    'password_hash_available' => function_exists('password_hash'),
                    'password_verify_available' => function_exists('password_verify')
                ]
            ];
            
            ResponseService::success('Diagnostic authentification', $authInfo);
            
        } catch (Exception $e) {
            ResponseService::error('Erreur de diagnostic auth: ' . $e->getMessage(), 500);
        }
    }
    
    /**
     * Test rapide de l'API
     * GET /api/diagnostic/api
     */
    public static function apiCheck(): void
    {
        $apiInfo = [
            'timestamp' => date('Y-m-d H:i:s'),
            'status' => 'operational',
            'endpoints' => [
                'health' => '/api/health',
                'auth_login' => '/api/auth/login',
                'auth_register' => '/api/auth/register',
                'users' => '/api/users',
                'projects' => '/api/projects',
                'tasks' => '/api/tasks'
            ],
            'server_info' => [
                'method' => $_SERVER['REQUEST_METHOD'] ?? 'CLI',
                'uri' => $_SERVER['REQUEST_URI'] ?? 'CLI',
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'CLI'
            ]
        ];
        
        ResponseService::success('API opérationnelle', $apiInfo);
    }
}
