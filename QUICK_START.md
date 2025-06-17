# 🚀 Guide de Démarrage Rapide - Task Manager Pro

## ✅ **Ce qui vient d'être fait**

1. ✅ **Migration PSR-4 complète** - Structure moderne adoptée
2. ✅ **Imports corrigés** - `ResponseService`, `ValidationService`, etc.
3. ✅ **Script d'installation** - Vérification automatique ajoutée
4. ✅ **Documentation** - Guides complets disponibles

## 🛠️ **Installation en 5 étapes**

### **Étape 1 : Nettoyage final**
```bash
# Supprimer les anciens dossiers (si pas encore fait)
rm -rf backend/config/ backend/middleware/ backend/utils/
rm -f backend/.htaccess.txt backend/composer.lock
rm -rf .vs/

echo "✅ Nettoyage terminé"
```

### **Étape 2 : Installation des dépendances**
```bash
cd backend/
composer install
composer dump-autoload -o

echo "✅ Dépendances installées"
```

### **Étape 3 : Configuration de la base de données**
```bash
# Créer la base de données
mysql -u root -p -e "CREATE DATABASE task_manager_pro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"

# Importer le schéma
mysql -u root -p task_manager_pro < database/schema.sql

echo "✅ Base de données configurée"
```

### **Étape 4 : Vérification avec le script d'installation**
```bash
# Lancer le script de vérification
php backend/install.php
```

**Résultat attendu :**
```
🚀 ==================================== 🚀
   Task Manager Pro - Installation
🚀 ==================================== 🚀

✅ Bootstrap initialized successfully!
✅ Database connection successful!
✅ All required tables are present!
✅ Admin user found: admin@taskmanager.local
✅ Configuration loaded
✅ PSR-4 autoloading: Working

🎉 Installation completed successfully!
```

### **Étape 5 : Test de l'API**
```bash
# Démarrer le serveur de développement
cd backend/
php -S localhost:8000

# Dans un autre terminal, tester l'API
curl http://localhost:8000/api/health
```

**Réponse attendue :**
```json
{
  "success": true,
  "message": "Succès",
  "data": {
    "status": "ok",
    "message": "API is running",
    "timestamp": "2025-06-17 17:00:00",
    "version": "1.0.0"
  },
  "timestamp": "2025-06-17T17:00:00+00:00"
}
```

## 🧪 **Tests complets de l'API**

### **1. Test d'inscription**
```bash
curl -X POST http://localhost:8000/api/auth/register \
  -H "Content-Type: application/json" \
  -d '{
    "username": "testuser",
    "email": "test@example.com",
    "password": "Test123!",
    "first_name": "Test",
    "last_name": "User"
  }'
```

### **2. Test de connexion**
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "test@example.com",
    "password": "Test123!"
  }'
```

### **3. Test avec compte admin**
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@taskmanager.local",
    "password": "Admin123!"
  }'
```

### **4. Test création de tâche**
```bash
# Récupérer le token depuis la réponse de login, puis :
curl -X POST http://localhost:8000/api/tasks \
  -H "Content-Type: application/json" \
  -H "Authorization: Bearer YOUR_TOKEN_HERE" \
  -d '{
    "title": "Ma première tâche",
    "description": "Test de création de tâche via API",
    "priority": "medium",
    "status": "pending"
  }'
```

## 📋 **Comptes disponibles**

| Email | Mot de passe | Rôle |
|-------|--------------|------|
| `admin@taskmanager.local` | `Admin123!` | Admin |
| `test@example.com` | `Test123!` | User (après inscription) |

## 🎯 **Vérification que tout fonctionne**

### ✅ **Checklist de validation**
- [ ] Anciens dossiers supprimés
- [ ] `composer install` exécuté
- [ ] Base de données créée et schéma importé
- [ ] Script `install.php` passe tous les tests
- [ ] Serveur PHP démarre sans erreur
- [ ] API `/health` répond correctement
- [ ] Connexion admin fonctionne
- [ ] Création d'utilisateur fonctionne
- [ ] Création de tâche fonctionne

### 🔍 **Comment vérifier les imports**

Si vous voulez vérifier manuellement que tous les imports sont corrects :

```bash
# Chercher d'anciens imports qui auraient pu être oubliés
grep -r "use TaskManager\\Utils\\" backend/ --include="*.php"
grep -r "use TaskManager\\Config\\Database" backend/ --include="*.php"

# Ces commandes ne doivent retourner AUCUN résultat
# Si elles retournent des fichiers, ces fichiers contiennent encore des anciens imports
```

## 🚀 **Prochaines étapes de développement**

### **1. Frontend React (optionnel)**
```bash
cd frontend/
npm install
npm start
# L'interface sera disponible sur http://localhost:3000
```

### **2. Développement de nouvelles fonctionnalités**
- Ajout de nouvelles routes API
- Implémentation de nouveaux modèles
- Extension des middlewares
- Tests unitaires avec PHPUnit

### **3. Déploiement**
- Configuration serveur web (Apache/Nginx)
- Configuration base de données production
- Variables d'environnement production
- SSL/HTTPS

## 🆘 **Dépannage**

### **Erreur "Class not found"**
```bash
cd backend/
composer dump-autoload -o
```

### **Erreur de connexion base de données**
Vérifiez votre fichier `backend/.env` :
```env
DB_HOST=localhost
DB_NAME=task_manager_pro
DB_USER=root
DB_PASS=votre_mot_de_passe
```

### **Erreur 500 sur l'API**
Activez le debug dans `.env` :
```env
APP_DEBUG=true
```

### **Problème avec les anciens imports**
Si vous voyez encore des erreurs d'imports :
1. Vérifiez que les anciens dossiers sont supprimés
2. Relancez `composer dump-autoload -o`
3. Redémarrez le serveur PHP

## 🎉 **Félicitations !**

Votre **Task Manager Pro** est maintenant :
- ✅ **Structuré** selon les standards PSR-4
- ✅ **Fonctionnel** avec API complète
- ✅ **Prêt** pour le développement
- ✅ **Documenté** avec guides complets

**Bon développement ! 🚀**