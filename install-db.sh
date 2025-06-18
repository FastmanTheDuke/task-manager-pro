#!/bin/bash

echo "🗄️  Installation de la base de données Task Manager Pro"
echo ""

# Vérifier si MySQL est installé
if ! command -v mysql &> /dev/null; then
    echo "❌ MySQL n'est pas installé. Veuillez installer MySQL ou MariaDB."
    exit 1
fi

echo "✓ MySQL détecté"

# Demander les informations de connexion
read -p "Utilisateur MySQL (root): " DB_USER
DB_USER=${DB_USER:-root}

read -s -p "Mot de passe MySQL: " DB_PASS
echo ""

read -p "Hôte MySQL (localhost): " DB_HOST
DB_HOST=${DB_HOST:-localhost}

read -p "Port MySQL (3306): " DB_PORT
DB_PORT=${DB_PORT:-3306}

# Tester la connexion
echo "🔌 Test de connexion à MySQL..."
mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" -e "SELECT 1;" > /dev/null 2>&1

if [ $? -ne 0 ]; then
    echo "❌ Impossible de se connecter à MySQL. Vérifiez vos identifiants."
    exit 1
fi

echo "✓ Connexion MySQL réussie"

# Créer la base de données
echo "📊 Création de la base de données..."
mysql -h"$DB_HOST" -P"$DB_PORT" -u"$DB_USER" -p"$DB_PASS" < database/schema.sql

if [ $? -eq 0 ]; then
    echo "✅ Base de données créée avec succès"
else
    echo "❌ Erreur lors de la création de la base de données"
    exit 1
fi

# Mettre à jour le fichier .env
echo "📝 Mise à jour du fichier .env..."
cd backend

# Backup du .env existant
if [ -f ".env" ]; then
    cp .env .env.backup
fi

# Créer/mettre à jour .env
cat > .env << EOF
# Configuration de la base de données
DB_HOST=$DB_HOST
DB_NAME=task_manager_pro
DB_USER=$DB_USER
DB_PASS=$DB_PASS
DB_PORT=$DB_PORT

# Configuration JWT
JWT_SECRET=your-super-secret-jwt-key-change-in-production
JWT_EXPIRES_IN=3600

# Configuration CORS
CORS_ORIGIN=http://localhost:3000

# Configuration de l'environnement
APP_ENV=development
APP_DEBUG=true

# Configuration des emails (optionnel)
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@taskmanager.local
MAIL_FROM_NAME="Task Manager Pro"

# Configuration des uploads
MAX_FILE_SIZE=10485760
UPLOAD_PATH=uploads/

# Configuration des logs
LOG_LEVEL=debug
LOG_PATH=logs/
EOF

echo "✅ Fichier .env mis à jour"

cd ..

echo ""
echo "🎉 Installation de la base de données terminée !"
echo ""
echo "🔑 Identifiants par défaut :"
echo "   Email: admin@taskmanager.local"
echo "   Mot de passe: Admin123!"
echo ""
echo "🚀 Vous pouvez maintenant :"
echo "1. Démarrer le serveur: cd backend && php -S localhost:8000 router.php"
echo "2. Ouvrir http://localhost:3000/login"
echo "3. Vous connecter avec admin@taskmanager.local / Admin123!"
