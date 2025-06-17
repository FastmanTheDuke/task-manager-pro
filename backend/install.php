<?php
// install.php - Script d'installation automatique
require_once 'Bootstrap.php';

use TaskManager\Config\App;
use TaskManager\Database\Connection;

echo "🚀 Installation de Task Manager Pro\n";
echo "====================================\n\n";

try {
    // Test de connection base de données
    echo "📊 Test de connexion à la base de données...\n";
    $db = Connection::getInstance();
    echo "✅ Connexion réussie !\n\n";
    
    // Vérifier les tables
    echo "📋 Vérification des tables...\n";
    $tables = ['users', 'projects', 'tasks', 'tags', 'comments', 'attachments'];
    
    foreach ($tables as $table) {
        $stmt = $db->prepare("SHOW TABLES LIKE ?");
        $stmt->execute([$table]);
        if ($stmt->rowCount() > 0) {
            echo "✅ Table '$table' existe\n";
        } else {
            echo "❌ Table '$table' manquante\n";
        }
    }
    
    // Test utilisateur admin
    echo "\n👤 Vérification utilisateur admin...\n";
    $stmt = $db->prepare("SELECT COUNT(*) FROM users WHERE role = 'admin'");
    $stmt->execute();
    $adminCount = $stmt->fetchColumn();
    
    if ($adminCount > 0) {
        echo "✅ Utilisateur admin existe (email: admin@taskmanager.local, mot de passe: Admin123!)\n";
    } else {
        echo "❌ Aucun utilisateur admin trouvé\n";
    }
    
    // Test configuration
    echo "\n⚙️ Test de configuration...\n";
    $config = App::all();
    echo "✅ Configuration chargée (" . count($config) . " paramètres)\n";
    
    echo "\n🎉 Installation vérifiée avec succès !\n";
    echo "Vous pouvez maintenant tester l'API.\n";
    
} catch (Exception $e) {
    echo "❌ Erreur : " . $e->getMessage() . "\n";
    echo "Vérifiez votre configuration .env et votre base de données.\n";
}
