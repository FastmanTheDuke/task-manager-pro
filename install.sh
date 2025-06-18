#!/bin/bash

echo "=== Installation du Task Manager Pro ==="
echo ""

# Vérifier si PHP est installé
if ! command -v php &> /dev/null; then
    echo "❌ PHP n'est pas installé. Veuillez installer PHP 8.0 ou supérieur."
    exit 1
fi

# Vérifier la version de PHP
PHP_VERSION=$(php -r "echo PHP_VERSION;" 2>/dev/null)
echo "✓ PHP version: $PHP_VERSION"

# Vérifier si Composer est installé
if ! command -v composer &> /dev/null; then
    echo "❌ Composer n'est pas installé. Veuillez installer Composer."
    echo "   Téléchargez-le depuis: https://getcomposer.org/download/"
    exit 1
fi

echo "✓ Composer est installé"

# Installation des dépendances PHP
echo ""
echo "📦 Installation des dépendances PHP..."
cd backend
composer install --no-dev --optimize-autoloader

if [ $? -eq 0 ]; then
    echo "✓ Dépendances PHP installées avec succès"
else
    echo "❌ Erreur lors de l'installation des dépendances PHP"
    exit 1
fi

# Vérifier/créer le fichier .env
if [ ! -f ".env" ]; then
    echo "📝 Création du fichier .env..."
    cp .env.example .env 2>/dev/null || echo "DB_HOST=localhost
DB_NAME=task_manager_pro
DB_USER=root
DB_PASS=
DB_PORT=3306

JWT_SECRET=your-super-secret-jwt-key-change-in-production
JWT_EXPIRES_IN=3600

CORS_ORIGIN=http://localhost:3000" > .env
    echo "✓ Fichier .env créé"
fi

cd ..

# Installation des dépendances Node.js
echo ""
echo "📦 Installation des dépendances Node.js..."
cd frontend

if command -v npm &> /dev/null; then
    npm install
    if [ $? -eq 0 ]; then
        echo "✓ Dépendances Node.js installées avec succès"
    else
        echo "❌ Erreur lors de l'installation des dépendances Node.js"
        exit 1
    fi
else
    echo "❌ npm n'est pas installé. Veuillez installer Node.js et npm."
    exit 1
fi

cd ..

echo ""
echo "🎉 Installation terminée avec succès!"
echo ""
echo "📚 Prochaines étapes:"
echo "1. Configurer votre base de données dans backend/.env"
echo "2. Importer le schéma de base de données depuis database/"
echo "3. Démarrer le serveur PHP: cd backend && php -S localhost:8000"
echo "4. Démarrer le serveur React: cd frontend && npm start"
echo ""
echo "🔗 URLs d'accès:"
echo "   Frontend: http://localhost:3000"
echo "   Backend API: http://localhost:8000/task-manager-pro/backend/api"
echo "   Health Check: http://localhost:8000/task-manager-pro/backend/api/health"
