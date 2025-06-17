# 🧹 Plan de Réorganisation - Task Manager Pro

## 🚨 **Problèmes Identifiés**

### **Doublons de Dossiers**
❌ **Structure Mixte Détectée :**
```
backend/
├── config/          # ❌ Ancienne structure (minuscules)
├── Config/          # ✅ Nouvelle structure (PascalCase)
├── middleware/      # ❌ Ancienne structure 
├── Middleware/      # ✅ Nouvelle structure
├── utils/           # ❌ Ancienne structure
└── Models/          # ✅ Nouvelle structure (créée)
```

### **Fichiers en Double**
- ❌ `.htaccess.txt` (inactif) + ✅ `.htaccess` (actif)
- ❌ `composer.lock` (ne doit pas être versionné)
- ❌ `.vs/` (dossier Visual Studio, doit être ignoré)

## 🎯 **Plan de Nettoyage**

### **Phase 1 : Adoption Structure PascalCase (PSR-4)**
✅ **Garder ces dossiers (nouvelle structure) :**
- `backend/Config/` - Configuration centralisée
- `backend/Database/` - Connexions BDD
- `backend/Models/` - Modèles de données
- `backend/Middleware/` - Middlewares sécurisés

### **Phase 2 : Migration du Contenu Utile**
🔄 **Migrer et nettoyer :**
- `backend/config/app.php` → `backend/Config/App.php` (✅ déjà fait)
- `backend/config/jwt.php` → `backend/Config/JWTManager.php` (✅ déjà fait)
- Contenu de `backend/middleware/` → `backend/Middleware/` (✅ déjà fait)
- Contenu de `backend/utils/` → Classes appropriées (✅ déjà fait)

### **Phase 3 : Suppression des Doublons**
🗑️ **À supprimer :**
- `backend/config/` (remplacé par `backend/Config/`)
- `backend/middleware/` (remplacé par `backend/Middleware/`)
- `backend/utils/` (fonctionnalités intégrées ailleurs)
- `backend/.htaccess.txt` (remplacé par `backend/.htaccess`)
- `.vs/` (dossier IDE)

## 🏗️ **Structure Finale Cible**

```
task-manager-pro/
├── 📁 backend/
│   ├── 🔧 Bootstrap.php
│   ├── 📍 index.php
│   ├── 📄 .htaccess
│   ├── ⚙️ .env
│   ├── 📦 composer.json
│   ├── 📁 Config/           # ✅ PascalCase
│   │   ├── App.php
│   │   └── JWTManager.php
│   ├── 📁 Database/         # ✅ PascalCase
│   │   └── Connection.php
│   ├── 📁 Models/           # ✅ PascalCase
│   │   ├── BaseModel.php
│   │   ├── User.php
│   │   ├── Task.php
│   │   ├── Tag.php
│   │   └── Project.php
│   ├── 📁 Middleware/       # ✅ PascalCase
│   │   └── ValidationMiddleware.php
│   └── 📁 api/
│       ├── auth/
│       └── tasks/
├── 📁 frontend/
├── 📁 database/
├── 📄 README.md
├── 📄 CORRECTIONS.md
└── 📄 .gitignore
```

## ✅ **Avantages de cette Structure**

1. **PSR-4 Compliant** - Namespaces cohérents
2. **Plus Lisible** - Noms de dossiers clairs
3. **Standards PHP** - Conventions respectées
4. **Autoloading Optimisé** - Composer plus efficace
5. **Maintenance Facilitée** - Structure logique

---

**Status: 🔄 En cours de nettoyage...**
