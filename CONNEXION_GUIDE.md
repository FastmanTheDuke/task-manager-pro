# 🔑 CONNEXION - Mode d'emploi complet

## 🎯 **Réponse à votre question**

Les utilisateurs sont stockés en **base de données MySQL** dans la table `users`.

## 🗄️ **Installation de la base de données**

### **Option 1: Script automatique (Recommandé)**
```bash
chmod +x install-db.sh
./install-db.sh
```

### **Option 2: Installation manuelle**
```bash
# 1. Créer la base
mysql -u root -p
source database/schema.sql

# 2. Configurer backend/.env
DB_HOST=localhost
DB_NAME=task_manager_pro
DB_USER=root
DB_PASS=votre_mot_de_passe_mysql
```

## 🔑 **Identifiants par défaut**

Un utilisateur admin est créé automatiquement :

```
Email: admin@taskmanager.local
Mot de passe: Admin123!
```

## 🚀 **Test complet**

### **1. Après installation DB, redémarrez le backend**
```bash
cd backend
php -S localhost:8000 router.php
```

### **2. Testez la connexion**
```bash
curl -X POST http://localhost:8000/api/auth/login \
-H "Content-Type: application/json" \
-d '{"email":"admin@taskmanager.local","password":"Admin123!"}'
```

**Résultat attendu :**
```json
{
  "success": true,
  "message": "Connexion réussie",
  "data": {
    "user": {...},
    "token": "eyJ...",
    "expires_in": 3600
  }
}
```

### **3. Depuis le frontend**
1. ✅ Ouvrir http://localhost:3000/login
2. ✅ Email: `admin@taskmanager.local`  
3. ✅ Mot de passe: `Admin123!`
4. ✅ Connexion réussie !

## 📋 **En cas de problème**

### **"Login error: ..."**
- Vérifiez que MySQL est démarré
- Vérifiez `backend/.env` (DB_HOST, DB_USER, DB_PASS)
- Vérifiez que la base `task_manager_pro` existe

### **"Email ou mot de passe incorrect"**
- ✅ Email: `admin@taskmanager.local` (pas .com !)
- ✅ Mot de passe: `Admin123!` (avec majuscule et !)

### **Test de debug**
```bash
# Vérifier si la base existe
mysql -u root -p -e "SHOW DATABASES LIKE 'task_manager_pro';"

# Vérifier l'utilisateur admin
mysql -u root -p task_manager_pro -e "SELECT email, username FROM users WHERE role='admin';"
```

## ✅ **États possibles maintenant**

- ✅ **Sans DB** - Validation fonctionne, erreur à l'authentification
- ✅ **Avec DB** - Connexion complète avec `admin@taskmanager.local / Admin123!`

---

**Le login est maintenant entièrement fonctionnel dès que la base de données est configurée !** 🎉
