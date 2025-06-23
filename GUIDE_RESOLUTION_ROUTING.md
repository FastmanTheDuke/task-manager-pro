# GUIDE DE RÉSOLUTION - Problème "URL not found"

## ✅ Problème corrigé !

J'ai identifié et corrigé le conflit de routing. Le système utilise maintenant **un seul point d'entrée unifié** via `index.php`.

## 🔧 Changements apportés

### **1. Harmonisation du système de connexion**
- ✅ `index.php` utilise maintenant le champ `login` (email OU username)
- ✅ Compatible avec les identifiants de test : `admin@taskmanager.local` ou `admin`
- ✅ Mot de passe : `Admin123!`

### **2. Routing unifié**
- ✅ Toutes les requêtes passent par `index.php` 
- ✅ Le .htaccess redirige correctement vers le routeur central
- ✅ Gestion d'erreurs améliorée avec logging

## 🧪 Comment tester

### **Option 1: Script automatique**
```bash
php test_auth_endpoints.php
```

### **Option 2: Tests manuels avec curl**

```bash
# Test 1: Health check
curl -X GET http://localhost/backend/api/health

# Test 2: Login avec email
curl -X POST http://localhost/backend/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"login":"admin@taskmanager.local","password":"Admin123!"}'

# Test 3: Login avec username
curl -X POST http://localhost/backend/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"login":"admin","password":"Admin123!"}'
```

### **Option 3: Test via navigateur**
- Allez sur : `http://localhost/backend/api/health`
- Vous devriez voir un JSON avec `"status": "ok"`

## 🔍 Diagnostics si ça ne marche toujours pas

### **1. Vérifier l'URL de base**
Adaptez selon votre configuration :
- `http://localhost/backend/api/` (si projet dans /htdocs/task-manager-pro/)
- `http://localhost:8000/api/` (si serveur PHP intégré)
- `http://localhost/task-manager-pro/backend/api/` (si sous-dossier)

### **2. Vérifier que le serveur web fonctionne**
```bash
# Si vous utilisez le serveur PHP intégré
php -S localhost:8000 -t backend/

# Puis testez sur http://localhost:8000/api/health
```

### **3. Vérifier les prérequis**
- ✅ PHP >= 7.4
- ✅ mod_rewrite activé (Apache)
- ✅ Fichier .htaccess présent dans `/backend/`
- ✅ Base de données configurée

### **4. Vérifier les logs d'erreur**
```bash
# Logs Apache (selon votre installation)
tail -f /var/log/apache2/error.log

# Ou dans XAMPP/WAMP
tail -f C:\xampp\apache\logs\error.log
```

## 📋 URLs disponibles

| Endpoint | Méthode | Description |
|----------|---------|-------------|
| `/api/health` | GET | Vérification de l'état |
| `/api/auth/login` | POST | Connexion (email ou username) |
| `/api/auth/register` | POST | Inscription |
| `/api/auth/logout` | POST | Déconnexion |
| `/api/tasks` | GET/POST | Gestion des tâches |
| `/api/users/profile` | GET/PUT | Profil utilisateur |

## 🎯 Identifiants de test

| Login | Mot de passe | Rôle |
|-------|-------------|------|
| `admin@taskmanager.local` | `Admin123!` | admin |
| `admin` | `Admin123!` | admin |

## 🚨 Si le problème persiste

1. **Vérifiez le fichier .htaccess** dans `/backend/`
2. **Testez d'abord** : `http://votre-url/backend/api/health`
3. **Vérifiez que Bootstrap.php** se charge correctement
4. **Consultez les logs PHP** pour voir les erreurs

Le système devrait maintenant fonctionner parfaitement ! 🎉
