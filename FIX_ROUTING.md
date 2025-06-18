# 🔧 Fix Problème de Routing - URLs API

## ❌ Problème identifié

L'erreur venait d'une incohérence entre :
- La configuration des URLs dans le frontend 
- Le démarrage du serveur PHP
- Le routage dans l'API backend

**Symptômes :**
- ✅ `http://localhost:8000/api/health.php` fonctionnait
- ❌ `http://localhost:8000/task-manager-pro/backend/api/health` ne fonctionnait pas
- ✅ Frontend React se chargeait correctement

## ✅ Solution mise en place

### 1. Scripts de démarrage corrigés
- **`start.sh`** (Linux/Mac) - Démarre le serveur PHP depuis le dossier `backend`
- **`start.bat`** (Windows) - Version Windows du script

### 2. Routing PHP amélioré
- **`backend/index.php`** - Détection automatique du basePath selon la configuration
- Support de plusieurs modes de démarrage (racine ou backend)

### 3. Configuration frontend mise à jour
- **`frontend/.env`** - URL API: `http://localhost:8000/api`
- **`frontend/.env.example`** - Exemple mis à jour

## 🚀 Comment utiliser maintenant

### Option 1: Scripts automatiques (Recommandé)

**Linux/Mac:**
```bash
chmod +x start.sh
./start.sh
```

**Windows:**
```cmd
start.bat
```

### Option 2: Démarrage manuel
```bash
# Terminal 1 - Backend
cd backend
php -S localhost:8000 -t .

# Terminal 2 - Frontend  
cd frontend
npm start
```

## 🔗 URLs de test

- **Frontend :** http://localhost:3000
- **API Health Check :** http://localhost:8000/api/health
- **API Base :** http://localhost:8000/api

## 📋 Ce qui a été corrigé

1. **Routage automatique** : Le `index.php` détecte automatiquement le bon basePath
2. **Scripts de démarrage** : Démarrent les serveurs depuis les bons dossiers
3. **URLs cohérentes** : Frontend et backend utilisent maintenant les mêmes URLs
4. **Support multi-environnement** : Fonctionne que le serveur soit démarré depuis la racine ou depuis backend

## ✅ Résultat

Maintenant l'API est accessible avec des URLs propres et cohérentes :
- `http://localhost:8000/api/health` ✅
- `http://localhost:8000/api/auth/login` ✅
- `http://localhost:8000/api/tasks` ✅

Le problème de path est complètement résolu !
