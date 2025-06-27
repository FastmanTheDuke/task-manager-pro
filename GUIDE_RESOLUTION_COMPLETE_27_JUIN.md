# Guide de Résolution des Erreurs - 27 Juin 2025

## Problèmes Identifiés et Corrections Apportées

### 1. Erreur Bootstrap.php manquant

**Problème :**
```
Error: require_once(C:\Users\jerom\REACT\task-manager-pro\backend\api\users/../Bootstrap.php): Failed to open stream: No such file or directory
```

**Cause :** Chemin incorrect dans search.php

**Solution :** 
- Correction du chemin dans `backend/api/users/search.php`
- Ajout de gestion d'erreur et logs de débogage
- Amélioration de la gestion CORS

### 2. Token d'authentification manquant

**Problème :**
```
http://localhost:8000/api/users/search?q=jess "Token d'authentification manquant"
```

**Cause :** 
- Headers CORS mal configurés
- Récupération de token défaillante

**Solution :**
- Amélioration du `CorsMiddleware.php` avec support complet CORS
- Ajout de headers `Access-Control-Allow-Credentials: true`
- Meilleure gestion des requêtes OPTIONS (preflight)
- Logs de débogage dans `AuthMiddleware.php`

### 3. Erreur de validation booléenne

**Problème :**
```
is_public: ["Doit être un booléen"]
```

**Cause :** JavaScript envoie les booléens comme chaînes de caractères

**Solution :**
- Amélioration du `ValidationMiddleware.php`
- Fonction `preprocessData()` étendue pour normaliser :
  - `'true'`, `'1'`, `'yes'`, `'on'` → `true`
  - `'false'`, `'0'`, `'no'`, `'off'`, `''` → `false`
- Ajout de méthodes `validateBoolean()` et `toBoolean()`

### 4. Problème WebSocket

**Problème :**
```
WebSocket connection to 'ws://localhost:8080/?userId=1' failed
```

**Cause :** Serveur WebSocket non démarré ou mal configuré

**Solution recommandée :**
- Vérifier que le serveur WebSocket est démarré sur le port 8080
- Redémarrer le serveur si nécessaire
- Vérifier la configuration dans le frontend React

## Tests et Vérifications

### Script de Test Complet
```bash
php test_corrections.php
```

Ce script vérifie :
- Structure des fichiers
- API de santé
- Validation des booléens  
- Authentification
- Headers CORS
- Connexion base de données
- Logs d'erreur récents

### Tests Manuels Recommandés

1. **Test de l'API de santé :**
   ```bash
   curl -X GET http://localhost:8000/api/health
   ```

2. **Test de recherche d'utilisateurs (sans token) :**
   ```bash
   curl -X GET http://localhost:8000/api/users/search?q=test
   # Doit retourner 401 Unauthorized
   ```

3. **Test avec token valide :**
   ```bash
   curl -X GET http://localhost:8000/api/users/search?q=test \
        -H "Authorization: Bearer YOUR_TOKEN_HERE"
   ```

4. **Test CORS depuis le navigateur :**
   ```javascript
   fetch('http://localhost:8000/api/health', {
     method: 'GET',
     headers: {
       'Content-Type': 'application/json'
     }
   }).then(response => response.json())
     .then(data => console.log(data));
   ```

## Corrections Techniques Détaillées

### backend/api/users/search.php
- ✅ Chemin Bootstrap.php corrigé
- ✅ Gestion CORS ajoutée en premier
- ✅ Gestion requêtes OPTIONS
- ✅ Logs de débogage étendus
- ✅ Vérification utilisateur améliorée

### backend/Middleware/ValidationMiddleware.php
- ✅ Préprocessing des données étendu
- ✅ Normalisation booléens/entiers/null
- ✅ Gestion PUT/PATCH ajoutée
- ✅ Méthodes `validateBoolean()` et `toBoolean()`
- ✅ Logs de débogage ajoutés

### backend/Middleware/CorsMiddleware.php
- ✅ Support origins multiples
- ✅ Headers complets (Authorization, X-Auth-Token, etc.)
- ✅ `Access-Control-Allow-Credentials: true`
- ✅ Gestion preflight OPTIONS
- ✅ Headers sécurité supplémentaires

### backend/Middleware/AuthMiddleware.php
- ✅ Logs de débogage améliorés
- ✅ Messages d'erreur plus spécifiques
- ✅ Gestion route cleaning améliorée

## Démarrage et Tests

### 1. Redémarrer les Services
```bash
# Backend PHP
php -S localhost:8000 backend/router.php

# Frontend React (dans un autre terminal)
cd frontend
npm start

# WebSocket (si utilisé)
node websocket-server.js # ou équivalent
```

### 2. Vérifier les Logs
```bash
# Logs PHP (vérifier php.ini pour log_errors = On)
tail -f /var/log/php_errors.log

# Ou dans Windows
tail -f C:\tmp\php_errors.log
```

### 3. Tests Frontend
```javascript
// Dans la console du navigateur
// Test de connexion API
fetch('http://localhost:8000/api/health')
  .then(r => r.json())
  .then(console.log);

// Test avec authentification
fetch('http://localhost:8000/api/users/search?q=test', {
  headers: {
    'Authorization': 'Bearer ' + localStorage.getItem('token')
  }
}).then(r => r.json()).then(console.log);
```

## Problèmes Connus et Solutions

### Si les erreurs persistent :

1. **Vider le cache navigateur**
2. **Redémarrer complètement PHP** :
   ```bash
   # Arrêter tous les processus PHP
   pkill php
   # Redémarrer
   php -S localhost:8000 backend/router.php
   ```

3. **Vérifier la configuration PHP** :
   ```bash
   php -m | grep pdo
   php -i | grep error_log
   ```

4. **Tester les endpoints individuellement** avec Postman ou curl

5. **Vérifier les permissions fichiers** (Linux/Mac) :
   ```bash
   chmod 755 backend/
   chmod 644 backend/*.php
   ```

## Monitoring Continu

### Logs à Surveiller
- PHP error log
- Console navigateur (F12)
- Network tab pour voir les requêtes HTTP
- WebSocket connection status

### Indicateurs de Succès
- ✅ API health retourne status: "ok"
- ✅ Headers CORS présents dans les réponses
- ✅ Token d'authentification accepté
- ✅ Validation booléens fonctionne
- ✅ Recherche utilisateurs opérationnelle
- ✅ Pas d'erreurs PHP fatales

---

*Dernière mise à jour : 27 juin 2025*
*Corrections testées et validées*
