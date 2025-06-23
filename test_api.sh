#!/bin/bash

# Script de test pour les endpoints d'authentification
# Usage: ./test_api.sh

echo "=== TEST ENDPOINTS TASK MANAGER PRO ==="
echo ""

# Configuration - Ajustez l'URL selon votre setup
BASE_URL="http://localhost/backend/api"
# Alternatives possibles :
# BASE_URL="http://localhost:8000/api"  # Si serveur PHP intégré
# BASE_URL="http://localhost/task-manager-pro/backend/api"  # Si sous-dossier

echo "🔗 URL de base: $BASE_URL"
echo ""

# Test 1: Health Check
echo "1️⃣ Test Health Check..."
curl -s -w "HTTP %{http_code}" "$BASE_URL/health" | head -1
echo ""
echo ""

# Test 2: Login avec email admin
echo "2️⃣ Test Login avec email admin..."
curl -s -w "HTTP %{http_code}" -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"login":"admin@taskmanager.local","password":"Admin123!"}' | head -1
echo ""
echo ""

# Test 3: Login avec username admin
echo "3️⃣ Test Login avec username admin..."
curl -s -w "HTTP %{http_code}" -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"login":"admin","password":"Admin123!"}' | head -1
echo ""
echo ""

# Test 4: Login avec mauvais identifiants
echo "4️⃣ Test Login avec mauvais identifiants..."
curl -s -w "HTTP %{http_code}" -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"login":"wrong@email.com","password":"wrong"}' | head -1
echo ""
echo ""

echo "=== RÉSULTATS ==="
echo "✅ HTTP 200 = Succès"
echo "❌ HTTP 404 = Endpoint non trouvé (problème de routing)"
echo "⚠️  HTTP 401 = Authentification échouée (normal pour mauvais identifiants)"
echo "💥 HTTP 500 = Erreur serveur"
echo ""

echo "Si vous voyez du code 404, vérifiez :"
echo "1. L'URL de base dans ce script"
echo "2. Que votre serveur web est démarré"
echo "3. Que mod_rewrite est activé (Apache)"
echo "4. Que le fichier .htaccess est dans /backend/"
echo ""

echo "Pour tester manuellement :"
echo "curl -X GET $BASE_URL/health"
echo "curl -X POST $BASE_URL/auth/login -H 'Content-Type: application/json' -d '{\"login\":\"admin\",\"password\":\"Admin123!\"}'"
