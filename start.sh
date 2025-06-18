#!/bin/bash

echo "🚀 Démarrage du Task Manager Pro"
echo ""

# Vérifier que nous sommes dans le bon dossier
if [ ! -d "backend" ] || [ ! -d "frontend" ]; then
    echo "❌ Erreur: Vous devez être dans le dossier racine du projet task-manager-pro"
    exit 1
fi

# Function pour tuer les processus en arrière-plan à la sortie
cleanup() {
    echo ""
    echo "🛑 Arrêt des serveurs..."
    kill $PHP_PID $REACT_PID 2>/dev/null
    exit 0
}

# Capturer Ctrl+C pour nettoyer
trap cleanup SIGINT

# Démarrer le serveur PHP depuis le dossier backend
echo "📡 Démarrage du serveur PHP..."
cd backend
php -S localhost:8000 -t . &
PHP_PID=$!
echo "✓ Serveur PHP démarré sur http://localhost:8000"
cd ..

# Attendre un peu pour que le serveur PHP démarre
sleep 2

# Tester l'API
echo "🧪 Test de l'API..."
HTTP_CODE=$(curl -s -o /dev/null -w "%{http_code}" http://localhost:8000/api/health 2>/dev/null || echo "000")

if [ "$HTTP_CODE" = "200" ]; then
    echo "✅ API Health Check: OK"
else
    echo "⚠️  API Health Check: Erreur ($HTTP_CODE)"
fi

# Démarrer le serveur React
echo "⚛️  Démarrage du serveur React..."
cd frontend

# Vérifier si les dépendances sont installées
if [ ! -d "node_modules" ]; then
    echo "📦 Installation des dépendances Node.js..."
    npm install
fi

# Démarrer React en arrière-plan
npm start &
REACT_PID=$!
echo "✓ Serveur React en cours de démarrage..."
cd ..

echo ""
echo "🎉 Les deux serveurs sont démarrés !"
echo ""
echo "🔗 URLs d'accès:"
echo "   Frontend:     http://localhost:3000"
echo "   Backend API:  http://localhost:8000/api"
echo "   Health Check: http://localhost:8000/api/health"
echo ""
echo "💡 Appuyez sur Ctrl+C pour arrêter les serveurs"
echo ""

# Attendre que l'utilisateur arrête les serveurs
wait
