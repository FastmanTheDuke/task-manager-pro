# 🚀 Task Manager Pro

Un gestionnaire de tâches collaboratif moderne développé avec **PHP 8+** et **React.js**, conçu pour une gestion efficace des projets et des équipes.

## ✨ Fonctionnalités

### 🎯 Gestion des Tâches
- ✅ Création, modification et suppression de tâches
- 🏷️ Tags personnalisables avec couleurs
- 📊 Priorités et statuts configurables
- 📅 Dates d'échéance et suivi du temps
- 👥 Assignation aux membres de l'équipe
- 💬 Système de commentaires

### 👥 Collaboration
- 🔐 Authentification sécurisée avec JWT
- 👤 Gestion des utilisateurs et des rôles
- 📁 Projets collaboratifs
- 🔔 Notifications temps réel
- 📈 Tableaux de bord analytiques

### 🛠️ Fonctionnalités Techniques
- 🌐 API RESTful complète
- 🔒 Sécurité renforcée (CSRF, XSS, JWT)
- 📱 Interface responsive
- 🌙 Mode sombre/clair
- 🌍 Multi-langues (FR/EN)
- 📊 Suivi des performances

## 🏗️ Architecture

### Backend (PHP 8+)
```
backend/
├── 🔧 Bootstrap.php              # Initialisation de l'application
├── 📊 index.php                  # Point d'entrée API principal
├── ⚙️ Config/                    # Configuration de l'application
│   ├── App.php                   # Configuration générale
│   └── JWTManager.php           # Gestion des tokens JWT
├── 🗄️ Database/                 # Gestion de la base de données
│   └── Connection.php           # Connexion PDO sécurisée
├── 📝 Models/                    # Modèles de données
│   ├── BaseModel.php           # Modèle de base
│   ├── User.php                # Gestion des utilisateurs
│   ├── Task.php                # Gestion des tâches
│   ├── Tag.php                 # Gestion des tags
│   └── Project.php             # Gestion des projets
├── 🛡️ Middleware/               # Middlewares de sécurité
│   ├── AuthMiddleware.php      # Authentification
│   ├── ValidationMiddleware.php # Validation des données
│   └── CorsMiddleware.php      # Gestion CORS
├── 🔌 api/                      # Endpoints API
│   ├── auth/                   # Authentification
│   │   ├── login.php          # Connexion
│   │   └── register.php       # Inscription
│   └── tasks/                  # Gestion des tâches
│       ├── index.php          # Liste des tâches
│       ├── create.php         # Création
│       ├── update.php         # Modification
│       └── delete.php         # Suppression
└── 🔧 utils/                   # Utilitaires
    ├── Response.php           # Réponses JSON standardisées
    └── Validator.php          # Validation des données
```

### Frontend (React.js)
```
frontend/
├── 📄 public/
│   └── index.html
├── ⚛️ src/
│   ├── 🧩 components/          # Composants React
│   │   ├── Auth/              # Authentification
│   │   ├── Tasks/             # Gestion des tâches
│   │   ├── Projects/          # Gestion des projets
│   │   └── Common/            # Composants réutilisables
│   ├── 🔄 services/           # Services API
│   │   ├── api.js            # Client HTTP
│   │   ├── auth.js           # Service d'authentification
│   │   └── tasks.js          # Service des tâches
│   ├── 📱 pages/              # Pages de l'application
│   ├── 🎨 styles/             # Feuilles de style
│   └── ⚙️ utils/              # Utilitaires
└── 📦 package.json
```

## 🚀 Installation

### Prérequis
- **PHP** 8.0 ou supérieur
- **MySQL** 8.0 ou supérieur
- **Node.js** 16 ou supérieur
- **Composer** 2.0 ou supérieur
- Serveur web (Apache/Nginx)

### 🔧 Configuration Backend

1. **Cloner le repository**
```bash
git clone https://github.com/FastmanTheDuke/task-manager-pro.git
cd task-manager-pro
```

2. **Installer les dépendances PHP**
```bash
cd backend
composer install
```

3. **Configurer la base de données**
```sql
CREATE DATABASE task_manager_pro CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

4. **Exécuter les migrations**
```bash
mysql -u root -p task_manager_pro < ../database/schema.sql
```

5. **Configurer l'environnement**
```bash
cp .env.example .env
```

Éditer `.env` :
```env
# Base de données
DB_HOST=localhost
DB_NAME=task_manager_pro
DB_USER=root
DB_PASS=votre_mot_de_passe

# JWT
JWT_SECRET=votre-clé-secrète-super-sécurisée
JWT_EXPIRY=3600

# Application
APP_URL=http://localhost
APP_DEBUG=true

# CORS
CORS_ORIGINS=http://localhost:3000
```

6. **Configurer le serveur web**

**Apache** - Assurer que le fichier `.htaccess` est activé et que `mod_rewrite` est disponible.

**Nginx** - Configuration exemple :
```nginx
server {
    listen 80;
    server_name taskmanager.local;
    root /path/to/task-manager-pro/backend;
    
    location /api {
        try_files $uri $uri/ /index.php?$query_string;
        
        location ~ \.php$ {
            fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
            fastcgi_index index.php;
            include fastcgi_params;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        }
    }
}
```

### ⚛️ Configuration Frontend

1. **Installer les dépendances Node.js**
```bash
cd ../frontend
npm install
```

2. **Configurer l'environnement**
```bash
cp .env.example .env
```

Éditer `.env` :
```env
REACT_APP_API_URL=http://localhost/task-manager-pro/backend/api
REACT_APP_ENV=development
```

3. **Démarrer l'application**
```bash
npm start
```

L'application sera accessible à `http://localhost:3000`

## 🔑 Utilisation de l'API

### Authentification

**Inscription**
```bash
POST /api/auth/register
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "motdepasse123",
  "username": "utilisateur",
  "first_name": "Prénom",
  "last_name": "Nom"
}
```

**Connexion**
```bash
POST /api/auth/login
Content-Type: application/json

{
  "email": "user@example.com",
  "password": "motdepasse123"
}
```

**Réponse**
```json
{
  "success": true,
  "message": "Connexion réussie",
  "data": {
    "user": {
      "id": 1,
      "username": "utilisateur",
      "email": "user@example.com",
      "role": "user"
    },
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9...",
    "expires_in": 3600
  }
}
```

### Gestion des Tâches

**Toutes les requêtes nécessitent l'en-tête d'autorisation :**
```
Authorization: Bearer YOUR_JWT_TOKEN
```

**Créer une tâche**
```bash
POST /api/tasks
Content-Type: application/json

{
  "title": "Nouvelle tâche",
  "description": "Description de la tâche",
  "priority": "high",
  "status": "pending",
  "due_date": "2024-12-31"
}
```

**Lister les tâches**
```bash
GET /api/tasks?page=1&limit=10&status=pending&priority=high
```

**Mettre à jour une tâche**
```bash
PUT /api/tasks/1
Content-Type: application/json

{
  "title": "Tâche mise à jour",
  "status": "in_progress",
  "completion_percentage": 50
}
```

## 🛠️ Fonctionnalités Avancées

### 🔒 Sécurité
- **JWT Authentication** avec expiration automatique
- **Validation des données** côté serveur
- **Protection CSRF** et **XSS**
- **Hashage sécurisé** des mots de passe (bcrypt)
- **Rate limiting** sur les endpoints sensibles

### 📊 Monitoring
- **Logs d'activité** utilisateur
- **Logs d'erreurs** détaillés
- **Métriques de performance**
- **Tableaux de bord** analytiques

### 🎨 Interface Utilisateur
- **Design moderne** et intuitif
- **Mode sombre/clair** automatique
- **Interface responsive** (mobile-first)
- **Raccourcis clavier** configurables
- **Notifications temps réel**

## 🐛 Débogage

### Vérifier la configuration
```bash
GET /api/info
```

Retourne l'état de l'application et les vérifications de configuration.

### Logs
- **Backend** : `backend/logs/`
- **Frontend** : Console du navigateur

### Erreurs communes

1. **Erreur CORS**
   - Vérifier `CORS_ORIGINS` dans `.env`
   - S'assurer que le fichier `.htaccess` est actif

2. **Erreur de connexion à la base de données**
   - Vérifier les identifiants dans `.env`
   - S'assurer que MySQL est démarré

3. **Erreur JWT**
   - Vérifier `JWT_SECRET` dans `.env`
   - S'assurer que le token n'est pas expiré

## 🧪 Tests

### Backend
```bash
cd backend
composer test
```

### Frontend
```bash
cd frontend
npm test
```

## 📈 Performance

### Optimisations Backend
- **Cache des requêtes** fréquentes
- **Compression gzip** activée
- **Index de base de données** optimisés
- **Pagination** pour les grandes listes

### Optimisations Frontend
- **Lazy loading** des composants
- **Minification** automatique
- **Cache des assets** statiques
- **Bundle splitting** pour de meilleures performances

## 🤝 Contribution

1. Fork le projet
2. Créer une branche pour votre fonctionnalité (`git checkout -b feature/AmazingFeature`)
3. Commit vos changements (`git commit -m 'Add some AmazingFeature'`)
4. Push vers la branche (`git push origin feature/AmazingFeature`)
5. Ouvrir une Pull Request

## 📝 Changelog

### v1.0.0 (2024-06-17)
- 🎉 Version initiale
- ✅ Architecture Backend complète avec PHP 8+
- ⚛️ Frontend React.js fonctionnel
- 🔐 Système d'authentification JWT
- 📊 Gestion complète des tâches et projets
- 🛡️ Sécurité renforcée et validation
- 📱 Interface responsive et moderne

## 📄 Licence

Ce projet est sous licence MIT. Voir le fichier `LICENSE` pour plus de détails.

## 📧 Support

Pour toute question ou support :
- **Email** : support@mdxp.io
- **Issues GitHub** : [Créer une issue](https://github.com/FastmanTheDuke/task-manager-pro/issues)

---

**Développé avec ❤️ par FastmanTheDuke**
