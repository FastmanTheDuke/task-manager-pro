# 🔧 RÉSOLUTION RAPIDE - Problème de connexion

## 📋 **Étapes de diagnostic**

### 1. **Test du backend directement**
```bash
# Exécutez le script de diagnostic
php debug_login.php
```

### 2. **Test de l'API via curl**
```bash
# Test avec email
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"login":"admin@taskmanager.local","password":"Admin123!"}'

# Test avec username
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"login":"admin","password":"Admin123!"}'
```

### 3. **Test avec le script PHP**
```bash
php test_api_login.php
```

## 🔍 **Problèmes fréquents et solutions**

### ❌ **"Base de données non connectée"**
```bash
# Vérifiez MySQL
sudo systemctl status mysql
# OU
sudo service mysql status

# Redémarrez si nécessaire
sudo systemctl restart mysql
```

### ❌ **"User not found"**
```bash
# Vérifiez si l'utilisateur admin existe
mysql -u root -p task_manager_pro -e "SELECT * FROM users WHERE role='admin';"

# Si pas d'utilisateur, relancez l'installation
./install-db.sh
```

### ❌ **"Autoload error"**
```bash
# Réinstallez les dépendances Composer
cd backend
composer install
```

### ❌ **"ValidationMiddleware error"**
```bash
# Vérifiez les namespaces et classes
php -l backend/Middleware/ValidationMiddleware.php
php -l backend/Services/ValidationService.php
```

## 🎯 **Solutions spécifiques par erreur**

### **Erreur: "Call to undefined method"**
- ✅ Vérifiez que `authenticateByLogin()` existe dans `User.php`
- ✅ Videz le cache d'autoloading : `composer dump-autoload`

### **Erreur: "JSON invalide"**  
- ✅ Vérifiez le Content-Type : `application/json`
- ✅ Testez avec Postman ou curl

### **Erreur: "Validation failed"**
- ✅ Assurez-vous d'envoyer `login` et `password`
- ✅ Pas de champs supplémentaires inattendus

### **Erreur: "Class not found"**
- ✅ Lancez `composer install` dans `/backend`
- ✅ Vérifiez que `/backend/vendor/` existe

## 🚀 **Test complet en 5 commandes**

```bash
# 1. Backend
cd backend && php -S localhost:8000 router.php &

# 2. Base de données  
mysql -u root -p task_manager_pro -e "SELECT COUNT(*) as users FROM users;"

# 3. Diagnostic backend
php debug_login.php

# 4. Test API
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{"login":"admin","password":"Admin123!"}'

# 5. Frontend
cd frontend && npm start
```

## 📞 **Si le problème persiste**

1. **Activez le mode debug** dans `backend/.env` :
   ```
   APP_DEBUG=true
   ```

2. **Consultez les logs** :
   ```bash
   tail -f backend/logs/errors_$(date +%Y-%m-%d).log
   ```

3. **Partagez les résultats** :
   - Sortie de `debug_login.php`
   - Réponse du test curl
   - Messages d'erreur des logs

---

**Ces scripts vous donneront un diagnostic précis du problème ! 🔍**