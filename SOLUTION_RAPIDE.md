# 🔥 SOLUTION RAPIDE - Erreur Composer

## ❌ Erreur actuelle
```
Error: Composer autoload file not found. Please run "composer install".
```

## ✅ Solution en 3 étapes

### **Étape 1 : Installer les dépendances Composer**
```bash
cd backend
composer install
```

### **Étape 2 : Démarrer le serveur backend**
```bash
# (Toujours dans le dossier backend)
php -S localhost:8000 router.php
```

### **Étape 3 : Démarrer le frontend (nouveau terminal)**
```bash
cd frontend
npm start
```

## 🧪 **Test rapide**

Une fois les 3 étapes faites, testez :

1. **Backend API :** http://localhost:8000/api/health
2. **Frontend :** http://localhost:3000

**Résultat attendu backend :**
```json
{
  "success": true,
  "data": {
    "status": "ok",
    "message": "API is running"
  }
}
```

## ⚡ **Script automatique (alternative)**

Si vous préférez, utilisez les scripts automatiques :

```bash
# Linux/Mac
chmod +x install.sh start.sh
./install.sh
./start.sh

# Windows
install.bat
start.bat
```

## 🔧 **En cas de problème**

1. **Vérifiez que Composer est installé :**
   ```bash
   composer --version
   ```

2. **Vérifiez que PHP fonctionne :**
   ```bash
   php --version
   ```

3. **Nettoyez et réinstallez :**
   ```bash
   cd backend
   rm -rf vendor composer.lock
   composer install
   ```

## ✅ **Résultat final**

Après ces étapes :
- ✅ Backend API : http://localhost:8000/api/health
- ✅ Frontend : http://localhost:3000
- ✅ Plus d'erreur Composer !

---

**L'erreur venait du fait que les dépendances PHP n'étaient pas installées dans le dossier `backend`. Maintenant c'est corrigé !**
