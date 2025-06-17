# 🚀 Guide Complet d'Installation et de Test - Task Manager Pro

## 🔍 **Étape 1 : Vérification et Correction des Imports**

### **A. Problèmes d'imports détectés**

Votre fichier `backend/index.php` utilise encore des anciens namespaces qui doivent être corrigés :

```php
// ❌ Anciens imports à corriger dans index.php
use TaskManager\Utils\Response;
use TaskManager\Middleware\ValidationMiddleware;

// ✅ Nouveaux imports à utiliser
use TaskManager\Services\ResponseService;
use TaskManager\Services\ValidationService;
```

### **B. Script de vérification des imports**

Créez ce script pour vérifier tous les imports :

```bash
#!/bin/bash
# check_imports.sh - Script de vérification des imports

echo "🔍 Vérification des imports PSR-4..."
echo "=================================="

echo "📁 Recherche des anciens imports..."
grep -r "use TaskManager\\Utils\\" backend/ --include="*.php" || echo "✅ Aucun import Utils trouvé"
grep -r "use TaskManager\\Config\\Database" backend/ --include="*.php" || echo "✅ Aucun import Database trouvé"
grep -r "namespace TaskManager\\Utils" backend/ --include="*.php" || echo "✅ Aucun namespace Utils trouvé"

echo ""
echo "📁 Vérification de la structure PSR-4..."
echo "✅ Config/     : $(ls -1 backend/Config/ 2>/dev/null | wc -l) fichiers"
echo "✅ Services/   : $(ls -1 backend/Services/ 2>/dev/null | wc -l) fichiers"
echo "✅ Middleware/ : $(ls -1 backend/Middleware/ 2>/dev/null | wc -l) fichiers"
echo "✅ Models/     : $(ls -1 backend/Models/ 2>/dev/null | wc -l) fichiers"
echo "✅ Database/   : $(ls -1 backend/Database/ 2>/dev/null | wc -l) fichiers"

echo ""
echo "🧹 Vérification des anciens dossiers..."
if [ -d "backend/config" ]; then echo "❌ Ancien dossier backend/config/ existe encore"; fi
if [ -d "backend/utils" ]; then echo "❌ Ancien dossier backend/utils/ existe encore"; fi
if [ -d "backend/middleware" ]; then echo "❌ Ancien dossier backend/middleware/ existe encore"; fi

echo ""
echo "🔧 Test de l'autoloading Composer..."
cd backend/
composer dump-autoload -o
echo "✅ Autoloading mis à jour"
```

### **C. Corrections à appliquer**

Vous devez corriger `backend/index.php` avec ces changements :

```php
// Remplacer en haut du fichier :
use TaskManager\Services\ResponseService as Response;
use TaskManager\Services\ValidationService;
use TaskManager\Middleware\AuthMiddleware;
use TaskManager\Models\Task;
use TaskManager\Models\User;
use TaskManager\Config\JWTManager;

// ET dans la fonction handleLogin(), remplacer :
$data = ValidationService::validate($_POST, $rules);

// ET dans toutes les autres fonctions, remplacer ValidationMiddleware par ValidationService
```

## 🗄️ **Étape 2 : Installation de la Base de Données**

### **A. Configuration MySQL**

1. **Créer la base de données** :
```sql
CREATE DATABASE task_manager_pro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

2. **Importer le schéma** :
```bash
mysql -u root -p task_manager_pro < database/schema.sql
```

3. **Vérifier l'installation** :
```sql
USE task_manager_pro;
SHOW TABLES;
SELECT COUNT(*) as total_tables FROM information_schema.tables WHERE table_schema = 'task_manager_pro';
```

### **B. Configuration de l'environnement**

Vérifiez votre fichier `backend/.env` :

```env
# Database
DB_HOST=localhost
DB_NAME=task_manager_pro
DB_USER=root
DB_PASS=votre_mot_de_passe_mysql

# JWT
JWT_SECRET=your_super_secret_key_here_change_in_production
JWT_EXPIRY=3600

# App
APP_URL=http://localhost
APP_DEBUG=true
```

### **C. Script d'installation automatique**

Créez `install.php` dans le dossier `backend/` :

```php
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
```

## 🧪 **Étape 3 : Tests et Vérification**

### **A. Test du serveur de développement**

1. **Démarrer le serveur** :
```bash
cd backend/
php -S localhost:8000
```

2. **Tester l'API** :
```bash
# Test de santé
curl http://localhost:8000/api/health

# Test d'informations
curl http://localhost:8000/api/info
```

### **B. Tests d'API avec cURL**

Créez `test_api.sh` :

```bash
#!/bin/bash
API_URL="http://localhost:8000"

echo "🧪 Tests API Task Manager Pro"
echo "============================="

echo ""
echo "1️⃣ Test de santé..."
curl -s "$API_URL/api/health" | jq '.' || echo "❌ Erreur health check"

echo ""
echo "2️⃣ Test d'inscription..."
curl -s -X POST "$API_URL/api/auth/register" \
  -H "Content-Type: application/json" \
  -d '{
    "username": "testuser",
    "email": "test@example.com",
    "password": "Test123!",
    "first_name": "Test",
    "last_name": "User"
  }' | jq '.' || echo "❌ Erreur inscription"

echo ""
echo "3️⃣ Test de connexion..."
TOKEN=$(curl -s -X POST "$API_URL/api/auth/login" \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "Test123!"
  }' | jq -r '.data.token')

if [ "$TOKEN" != "null" ] && [ "$TOKEN" != "" ]; then
  echo "✅ Connexion réussie, token obtenu"
  
  echo ""
  echo "4️⃣ Test création de tâche..."
  curl -s -X POST "$API_URL/api/tasks" \
    -H "Content-Type: application/json" \
    -H "Authorization: Bearer $TOKEN" \
    -d '{
      "title": "Ma première tâche",
      "description": "Test de création de tâche",
      "priority": "medium",
      "status": "pending"
    }' | jq '.' || echo "❌ Erreur création tâche"
    
  echo ""
  echo "5️⃣ Test récupération des tâches..."
  curl -s "$API_URL/api/tasks" \
    -H "Authorization: Bearer $TOKEN" | jq '.' || echo "❌ Erreur récupération tâches"
    
else
  echo "❌ Impossible d'obtenir le token"
fi
```

### **C. Test avec un client REST (optionnel)**

Importez cette collection Postman/Insomnia :

```json
{
  "name": "Task Manager Pro API",
  "requests": [
    {
      "name": "Health Check",
      "method": "GET",
      "url": "{{base_url}}/api/health"
    },
    {
      "name": "Register",
      "method": "POST",
      "url": "{{base_url}}/api/auth/register",
      "body": {
        "username": "testuser",
        "email": "test@example.com",
        "password": "Test123!",
        "first_name": "Test",
        "last_name": "User"
      }
    },
    {
      "name": "Login",
      "method": "POST",
      "url": "{{base_url}}/api/auth/login",
      "body": {
        "email": "test@example.com",
        "password": "Test123!"
      }
    },
    {
      "name": "Get Tasks",
      "method": "GET",
      "url": "{{base_url}}/api/tasks",
      "headers": {
        "Authorization": "Bearer {{token}}"
      }
    },
    {
      "name": "Create Task",
      "method": "POST",
      "url": "{{base_url}}/api/tasks",
      "headers": {
        "Authorization": "Bearer {{token}}"
      },
      "body": {
        "title": "Ma première tâche",
        "description": "Test de création",
        "priority": "medium",
        "status": "pending"
      }
    }
  ],
  "variables": {
    "base_url": "http://localhost:8000"
  }
}
```

## 🔧 **Étape 4 : Installation Composer et Dépendances**

```bash
cd backend/
composer install
composer dump-autoload -o
```

## 🎯 **Checklist de Validation**

- [ ] ✅ Nettoyage des anciens dossiers effectué
- [ ] ✅ Imports PSR-4 corrigés dans index.php
- [ ] ✅ Base de données créée et schema importé
- [ ] ✅ Configuration .env adaptée
- [ ] ✅ Composer install exécuté
- [ ] ✅ Autoloading mis à jour
- [ ] ✅ Serveur de développement démarré
- [ ] ✅ Tests API passent
- [ ] ✅ Authentification fonctionne
- [ ] ✅ CRUD tâches opérationnel

## 🚀 **Frontend React (Prochaine étape)**

Une fois le backend validé, pour le frontend :

```bash
cd frontend/
npm install
npm start
```

## 📋 **Comptes de test disponibles**

- **Admin** : `admin@taskmanager.local` / `Admin123!`
- **Test** : `test@example.com` / `Test123!` (après inscription)

---

**Votre Task Manager Pro est maintenant prêt pour le développement ! 🎉**