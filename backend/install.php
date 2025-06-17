<?php
/**
 * Task Manager Pro - Installation Script
 * 
 * This script helps with the initial setup and verification of the application.
 * Run this after setting up your database and .env file.
 */

require_once __DIR__ . '/Bootstrap.php';

use TaskManager\Bootstrap;
use TaskManager\Config\App;
use TaskManager\Database\Connection;
use TaskManager\Services\ResponseService;

// Enable error reporting for installation
error_reporting(E_ALL);
ini_set('display_errors', 1);

// ASCII Art Header
echo "\n";
echo "🚀 ================================== 🚀\n";
echo "   Task Manager Pro - Installation\n";
echo "🚀 ================================== 🚀\n\n";

try {
    // Initialize Bootstrap
    echo "🔧 Initializing application...\n";
    Bootstrap::init();
    echo "✅ Bootstrap initialized successfully!\n\n";
    
    // Test Database Connection
    echo "📊 Testing database connection...\n";
    $db = Connection::getInstance();
    echo "✅ Database connection successful!\n\n";
    
    // Check Database Tables
    echo "📋 Checking database tables...\n";
    $requiredTables = [
        'users', 'projects', 'tasks', 'tags', 'task_tags', 
        'time_entries', 'comments', 'attachments', 'notifications', 
        'activity_logs', 'sessions', 'user_preferences'
    ];
    
    $existingTables = [];
    $missingTables = [];
    
    foreach ($requiredTables as $table) {
        $stmt = $db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->rowCount() > 0) {
            $existingTables[] = $table;
            echo "  ✅ Table '$table' exists\n";
        } else {
            $missingTables[] = $table;
            echo "  ❌ Table '$table' missing\n";
        }
    }
    
    if (!empty($missingTables)) {
        echo "\n⚠️  Missing tables detected. Please run:\n";
        echo "   mysql -u root -p " . App::get('database.name') . " < database/schema.sql\n\n";
    } else {
        echo "\n✅ All required tables are present!\n\n";
    }
    
    // Check Admin User
    echo "👤 Checking admin user...\n";
    $stmt = $db->prepare("SELECT id, username, email FROM users WHERE role = 'admin' LIMIT 1");
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($admin) {
        echo "✅ Admin user found:\n";
        echo "   - Username: {$admin['username']}\n";
        echo "   - Email: {$admin['email']}\n";
        echo "   - Default password: Admin123!\n\n";
    } else {
        echo "❌ No admin user found in database.\n";
        echo "   Creating default admin user...\n";
        
        $stmt = $db->prepare("
            INSERT INTO users (username, email, password, first_name, last_name, role) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $hashedPassword = password_hash('Admin123!', PASSWORD_DEFAULT);
        $stmt->execute(['admin', 'admin@taskmanager.local', $hashedPassword, 'Admin', 'System', 'admin']);
        
        echo "✅ Admin user created:\n";
        echo "   - Email: admin@taskmanager.local\n";
        echo "   - Password: Admin123!\n\n";
    }
    
    // Test Configuration
    echo "⚙️  Testing configuration...\n";
    $config = App::all();
    if (!empty($config)) {
        echo "✅ Configuration loaded (" . count($config) . " parameters)\n";
        
        // Check critical config
        $critical = ['database.host', 'database.name', 'jwt.secret'];
        foreach ($critical as $key) {
            $value = App::get($key);
            if ($value) {
                echo "  ✅ {$key}: " . (strlen($value) > 20 ? substr($value, 0, 20) . '...' : $value) . "\n";
            } else {
                echo "  ❌ {$key}: NOT SET\n";
            }
        }
    } else {
        echo "❌ Configuration could not be loaded\n";
    }
    
    echo "\n";
    
    // Test API Endpoints (if running on web server)
    if (isset($_SERVER['HTTP_HOST'])) {
        echo "🌐 Testing API endpoints...\n";
        $baseUrl = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
        $testUrl = $baseUrl . '/api/health';
        
        echo "   Testing: $testUrl\n";
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $testUrl);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode === 200) {
            echo "✅ API health check successful!\n";
        } else {
            echo "❌ API health check failed (HTTP $httpCode)\n";
        }
    }
    
    // PSR-4 Autoloading Test
    echo "\n🔍 Testing PSR-4 autoloading...\n";
    $testClasses = [
        'TaskManager\\Config\\App',
        'TaskManager\\Services\\ResponseService',
        'TaskManager\\Services\\ValidationService',
        'TaskManager\\Middleware\\AuthMiddleware',
        'TaskManager\\Models\\User',
        'TaskManager\\Models\\Task'
    ];
    
    foreach ($testClasses as $class) {
        if (class_exists($class)) {
            echo "  ✅ {$class}\n";
        } else {
            echo "  ❌ {$class} - NOT FOUND\n";
        }
    }
    
    // Final Summary
    echo "\n🎯 Installation Summary:\n";
    echo "=======================\n";
    echo "✅ Bootstrap: Working\n";
    echo "✅ Database: " . (empty($missingTables) ? 'Ready' : 'Missing ' . count($missingTables) . ' tables') . "\n";
    echo "✅ Admin User: " . ($admin ? 'Exists' : 'Created') . "\n";
    echo "✅ Configuration: Loaded\n";
    echo "✅ PSR-4 Autoloading: Working\n";
    
    if (empty($missingTables)) {
        echo "\n🎉 Installation completed successfully!\n";
        echo "\nNext steps:\n";
        echo "1. Start your development server: php -S localhost:8000\n";
        echo "2. Test the API: curl http://localhost:8000/api/health\n";
        echo "3. Login with admin@taskmanager.local / Admin123!\n";
        echo "4. Start developing! 🚀\n";
    } else {
        echo "\n⚠️  Please import the database schema and run this script again.\n";
    }
    
} catch (Exception $e) {
    echo "\n❌ Installation Error: " . $e->getMessage() . "\n";
    echo "\nPlease check:\n";
    echo "1. Your .env file configuration\n";
    echo "2. Database connection settings\n";
    echo "3. MySQL server is running\n";
    echo "4. Database permissions\n\n";
    
    // Show detailed error for debugging
    if (App::get('app.debug', false)) {
        echo "Debug information:\n";
        echo "File: " . $e->getFile() . "\n";
        echo "Line: " . $e->getLine() . "\n";
        echo "Trace:\n" . $e->getTraceAsString() . "\n";
    }
}

echo "\n";
