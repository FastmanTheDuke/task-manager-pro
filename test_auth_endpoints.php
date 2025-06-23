<?php
/**
 * Script de test rapide pour les endpoints d'authentification
 * Usage: php test_auth_endpoints.php
 */

// Configuration de base
$baseUrl = 'http://localhost/backend/api'; // Ajustez selon votre configuration

// Identifiants de test
$testCredentials = [
    'admin_email' => [
        'login' => 'admin@taskmanager.local',
        'password' => 'Admin123!'
    ],
    'admin_username' => [
        'login' => 'admin',
        'password' => 'Admin123!'
    ]
];

echo "=== TEST DES ENDPOINTS D'AUTHENTIFICATION ===\n\n";

// Fonction pour faire une requête POST
function makeRequest($url, $data = null, $headers = []) {
    $ch = curl_init();
    
    curl_setopt_array($ch, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_HTTPHEADER => array_merge([
            'Content-Type: application/json',
            'Accept: application/json'
        ], $headers)
    ]);
    
    if ($data !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    }
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    return [
        'code' => $httpCode,
        'response' => $response,
        'error' => $error
    ];
}

// Test 1: Health Check
echo "1. Test Health Check...\n";
$result = makeRequest($baseUrl . '/health');
echo "   Code: {$result['code']}\n";
echo "   Response: " . substr($result['response'], 0, 100) . "...\n\n";

// Test 2: Login avec email
echo "2. Test Login avec email...\n";
$result = makeRequest($baseUrl . '/auth/login', $testCredentials['admin_email']);
echo "   Code: {$result['code']}\n";
echo "   Response: " . substr($result['response'], 0, 200) . "...\n\n";

// Test 3: Login avec username
echo "3. Test Login avec username...\n";
$result = makeRequest($baseUrl . '/auth/login', $testCredentials['admin_username']);
echo "   Code: {$result['code']}\n";
echo "   Response: " . substr($result['response'], 0, 200) . "...\n\n";

// Test 4: Login avec des identifiants incorrects
echo "4. Test Login avec identifiants incorrects...\n";
$result = makeRequest($baseUrl . '/auth/login', [
    'login' => 'wrong@email.com',
    'password' => 'wrongpassword'
]);
echo "   Code: {$result['code']}\n";
echo "   Response: " . substr($result['response'], 0, 200) . "...\n\n";

// Test 5: Debug endpoint
echo "5. Test Debug endpoint...\n";
$result = makeRequest($baseUrl . '/debug', ['test' => 'data']);
echo "   Code: {$result['code']}\n";
echo "   Response: " . substr($result['response'], 0, 300) . "...\n\n";

// Test 6: Test d'enregistrement
echo "6. Test Register...\n";
$testUser = [
    'email' => 'test' . time() . '@example.com',
    'password' => 'Test123!',
    'username' => 'testuser' . time(),
    'first_name' => 'Test',
    'last_name' => 'User'
];
$result = makeRequest($baseUrl . '/auth/register', $testUser);
echo "   Code: {$result['code']}\n";
echo "   Response: " . substr($result['response'], 0, 200) . "...\n\n";

echo "=== FIN DES TESTS ===\n\n";

// Instructions
echo "INSTRUCTIONS:\n";
echo "- Code 200: Succès\n";
echo "- Code 404: Endpoint non trouvé (problème de routing)\n";
echo "- Code 500: Erreur serveur\n";
echo "- Code 401: Authentification échouée (normal pour les mauvais identifiants)\n\n";

echo "Si vous voyez des codes 404, vérifiez:\n";
echo "1. L'URL de base (\$baseUrl dans ce script)\n";
echo "2. Que le serveur web est démarré\n";
echo "3. Que mod_rewrite est activé\n";
echo "4. Que le fichier .htaccess est bien présent dans /backend/\n\n";

echo "URL testées:\n";
echo "- GET  {$baseUrl}/health\n";
echo "- POST {$baseUrl}/auth/login\n";
echo "- POST {$baseUrl}/auth/register\n";
echo "- POST {$baseUrl}/debug\n";
