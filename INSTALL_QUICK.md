# 🚀 Installation Rapide - Task Manager Pro

## ❌ Résoudre l'erreur "Composer autoload file not found"

Si vous obtenez cette erreur, cela signifie que les dépendances PHP ne sont pas installées.

### 🔧 Solution Rapide

#### Option 1: Scripts d'installation automatique (Recommandé)

**Linux/Mac :**
```bash
chmod +x install.sh
./install.sh
```

**Windows :**
```cmd
install.bat
```

#### Option 2: Installation manuelle

1. **Installer les dépendances PHP :**
   ```bash
   cd backend
   composer install --no-dev --optimize-autoloader
   ```

2. **Créer le fichier de configuration :**
   ```bash
   cp .env.example .env
   ```

3. **Installer les dépendances Node.js :**
   ```bash
   cd ../frontend
   npm install
   ```

### 🏃‍♂️ Démarrage rapide après installation

1. **Démarrer le serveur PHP (Terminal 1) :**
   ```bash
   cd backend
   php -S localhost:8000
   ```

2. **Démarrer le serveur React (Terminal 2) :**
   ```bash
   cd frontend
   npm start
   ```

### 🔗 URLs de test

- **Frontend :** http://localhost:3000
- **API Health Check :** http://localhost:8000/task-manager-pro/backend/api/health
- **API Base :** http://localhost:8000/task-manager-pro/backend/api

### 📋 Prérequis

- PHP 8.0+
- Composer
- Node.js 16+
- MySQL/MariaDB (optionnel pour les tests)

### 🆘 Dépannage

Si vous avez encore des problèmes :

1. Vérifiez que PHP et Composer sont installés :
   ```bash
   php --version
   composer --version
   ```

2. Vérifiez les permissions :
   ```bash
   sudo chmod -R 755 backend/
   ```

3. Effacez le cache Composer :
   ```bash
   composer clear-cache
   cd backend && composer install --no-cache
   ```

---

✅ **Problème résolu !** Les scripts d'installation configurent automatiquement tout ce qui est nécessaire.
