#!/bin/bash
# Script de test rapide pour l'API Projects

echo "=== TEST RAPIDE API PROJECTS ==="
echo ""

# Configuration
API_URL="http://localhost:8000/api"
PROJECTS_ENDPOINT="$API_URL/projects"

# Test 1: Health check
echo "1. Test de santé de l'API..."
curl -s -X GET "$API_URL/health" | echo "Health: $(cat)"
echo ""

# Test 2: Test de création de projet (sans token pour voir l'erreur d'auth)
echo "2. Test de création de projet (test structure)..."
echo "Données envoyées (format CORRECT):"

# Données CORRECTES selon les corrections
PROJECT_DATA='{
  "name": "Projet Test API",
  "description": "Test des corrections finales",
  "end_date": "2024-12-31",
  "is_public": true,
  "status": "active",
  "priority": "medium",
  "color": "#2196f3"
}'

echo "$PROJECT_DATA"
echo ""

# Test de l'endpoint (doit retourner erreur d'auth mais pas d'erreur de validation)
echo "Réponse API:"
curl -s -X POST "$PROJECTS_ENDPOINT" \
  -H "Content-Type: application/json" \
  -d "$PROJECT_DATA" | python3 -m json.tool 2>/dev/null || echo "Réponse non-JSON ou erreur"

echo ""
echo ""

# Test 3: Structure incorrecte (ancien format) pour comparaison
echo "3. Test avec ANCIEN format (doit échouer)..."
echo "Données envoyées (format INCORRECT):"

WRONG_DATA='{
  "name": "Projet Test Ancien",
  "description": "Test ancien format",
  "due_date": "2024-12-31",
  "public": true,
  "created_by": 1
}'

echo "$WRONG_DATA"
echo ""

echo "Réponse API:"
curl -s -X POST "$PROJECTS_ENDPOINT" \
  -H "Content-Type: application/json" \
  -d "$WRONG_DATA" | python3 -m json.tool 2>/dev/null || echo "Réponse non-JSON ou erreur"

echo ""
echo ""

# Instructions pour test complet
echo "=== INSTRUCTIONS POUR TEST COMPLET ==="
echo ""
echo "1. Pour tester avec un vrai token:"
echo "   - Connectez-vous à votre app"
echo "   - Récupérez le token dans localStorage"
echo "   - Ajoutez: -H \"Authorization: Bearer YOUR_TOKEN\""
echo ""
echo "2. Commandes de test avec token:"
echo "   curl -X POST $PROJECTS_ENDPOINT \\"
echo "     -H \"Content-Type: application/json\" \\"
echo "     -H \"Authorization: Bearer YOUR_TOKEN\" \\"
echo "     -d '$PROJECT_DATA'"
echo ""
echo "3. Si les corrections fonctionnent:"
echo "   ✅ Format CORRECT doit créer le projet"
echo "   ❌ Format INCORRECT doit retourner erreur de validation"
echo ""
echo "4. Test de récupération des projets:"
echo "   curl -X GET $PROJECTS_ENDPOINT \\"
echo "     -H \"Authorization: Bearer YOUR_TOKEN\""
echo ""
