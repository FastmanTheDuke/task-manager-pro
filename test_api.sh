#!/bin/bash

# Script de test pour les endpoints d'authentification
# Usage: ./test_api.sh [PORT]

# Configuration - Ajustez selon votre setup
PORT=${1:-80}  # Port par défaut, peut être passé en paramètre

if [ "$PORT" = "80" ]; then
    BASE_URL="http://localhost/task-manager-pro/backend/api"
else
    BASE_URL="http://localhost:$PORT/task-manager-pro/backend/api"
fi

echo "=== TEST ENDPOINTS TASK MANAGER PRO ==="
echo ""
echo "🔗 URL de base: $BASE_URL"
echo "🚪 Port: $PORT"
echo ""

# Test 1: Health Check
echo "1️⃣ Test Health Check..."
HEALTH_RESULT=$(curl -s -w "\n%{http_code}" "$BASE_URL/health")
HEALTH_CODE=$(echo "$HEALTH_RESULT" | tail -n1)
HEALTH_BODY=$(echo "$HEALTH_RESULT" | head -n -1)

echo "   Code HTTP: $HEALTH_CODE"
if [ "$HEALTH_CODE" = "200" ]; then
    echo "   ✅ SUCCESS"
    echo "   Response: $(echo $HEALTH_BODY | head -c 100)..."
else
    echo "   ❌ FAILED"
    echo "   Response: $HEALTH_BODY"
fi
echo ""

# Test 2: API Info (nouveau endpoint)
echo "2️⃣ Test API Info..."
INFO_RESULT=$(curl -s -w "\n%{http_code}" "$BASE_URL")
INFO_CODE=$(echo "$INFO_RESULT" | tail -n1)
INFO_BODY=$(echo "$INFO_RESULT" | head -n -1)

echo "   Code HTTP: $INFO_CODE"
if [ "$INFO_CODE" = "200" ]; then
    echo "   ✅ SUCCESS"
else
    echo "   ❌ FAILED"
    echo "   Response: $INFO_BODY"
fi
echo ""

# Test 3: Login avec username admin
echo "3️⃣ Test Login avec username admin..."
LOGIN_RESULT=$(curl -s -w "\n%{http_code}" -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"login":"admin","password":"Admin123!"}')
LOGIN_CODE=$(echo "$LOGIN_RESULT" | tail -n1)
LOGIN_BODY=$(echo "$LOGIN_RESULT" | head -n -1)

echo "   Code HTTP: $LOGIN_CODE"
if [ "$LOGIN_CODE" = "200" ]; then
    echo "   ✅ SUCCESS - Login réussi"
    # Extraire le token si possible
    TOKEN=$(echo $LOGIN_BODY | grep -o '"token":"[^"]*"' | cut -d'"' -f4)
    if [ ! -z "$TOKEN" ]; then
        echo "   🔑 Token récupéré: ${TOKEN:0:20}..."
    fi
else
    echo "   ❌ FAILED"
    echo "   Response: $LOGIN_BODY"
fi
echo ""

# Test 4: Login avec email admin
echo "4️⃣ Test Login avec email admin..."
LOGIN_EMAIL_RESULT=$(curl -s -w "\n%{http_code}" -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"login":"admin@taskmanager.local","password":"Admin123!"}')
LOGIN_EMAIL_CODE=$(echo "$LOGIN_EMAIL_RESULT" | tail -n1)

echo "   Code HTTP: $LOGIN_EMAIL_CODE"
if [ "$LOGIN_EMAIL_CODE" = "200" ]; then
    echo "   ✅ SUCCESS - Login par email réussi"
else
    echo "   ❌ FAILED"
    echo "   Response: $(echo "$LOGIN_EMAIL_RESULT" | head -n -1)"
fi
echo ""

# Test 5: Login avec mauvais identifiants (doit échouer)
echo "5️⃣ Test Login avec mauvais identifiants..."
BAD_LOGIN_RESULT=$(curl -s -w "\n%{http_code}" -X POST "$BASE_URL/auth/login" \
  -H "Content-Type: application/json" \
  -d '{"login":"wrong@email.com","password":"wrong"}')
BAD_LOGIN_CODE=$(echo "$BAD_LOGIN_RESULT" | tail -n1)

echo "   Code HTTP: $BAD_LOGIN_CODE"
if [ "$BAD_LOGIN_CODE" = "401" ]; then
    echo "   ✅ SUCCESS - Rejet attendu"
else
    echo "   ⚠️  Unexpected code (attendu: 401)"
    echo "   Response: $(echo "$BAD_LOGIN_RESULT" | head -n -1)"
fi
echo ""

# Test 6: Debug endpoint
echo "6️⃣ Test Debug endpoint..."
DEBUG_RESULT=$(curl -s -w "\n%{http_code}" -X POST "$BASE_URL/debug" \
  -H "Content-Type: application/json" \
  -d '{"test":"debug_data"}')
DEBUG_CODE=$(echo "$DEBUG_RESULT" | tail -n1)

echo "   Code HTTP: $DEBUG_CODE"
if [ "$DEBUG_CODE" = "200" ]; then
    echo "   ✅ SUCCESS - Debug endpoint accessible"
else
    echo "   ❌ FAILED"
    echo "   Response: $(echo "$DEBUG_RESULT" | head -n -1)"
fi
echo ""

echo "=== RÉSUMÉ ==="
echo "✅ HTTP 200 = Succès"
echo "❌ HTTP 404 = Endpoint non trouvé (problème de routing)"
echo "⚠️  HTTP 401 = Authentification échouée (normal pour mauvais identifiants)"
echo "💥 HTTP 500 = Erreur serveur"
echo ""

echo "📋 URLs testées:"
echo "- GET  $BASE_URL/health"
echo "- GET  $BASE_URL (API info)"
echo "- POST $BASE_URL/auth/login"
echo "- POST $BASE_URL/debug"
echo ""

echo "🔧 Si vous voyez des codes 404:"
echo "1. Vérifiez que votre serveur web est démarré"
echo "2. Vérifiez l'URL de base (port, chemin)"
echo "3. Consultez les logs de votre serveur web"
echo "4. Vérifiez que mod_rewrite est activé"
echo ""

echo "Usage alternatif avec port personnalisé:"
echo "./test_api.sh 8080"
echo "./test_api.sh 3000"
