<?php
/**
 * Test API Login - Test direct de l'endpoint de connexion
 * 
 * Teste l'API login.php directement sans passer par le frontend
 */

// Configuration de test
$backendUrl = 'http://localhost:8000';
$loginEndpoint = $backendUrl . '/api/auth/login';

echo "=== TEST API LOGIN ===\n\n";

// Test 1: Connexion avec email
echo "1. Test connexion avec email...\n";
$emailData = [
    'login' => 'admin@taskmanager.local',
    'password' => 'Admin123!'
];

$response1 = testLogin($loginEndpoint, $emailData);
echo "Réponse: " . $response1 . "\n\n";

// Test 2: Connexion avec username
echo "2. Test connexion avec username...\n";
$usernameData = [
    'login' => 'admin',
    'password' => 'Admin123!'
];

$response2 = testLogin($loginEndpoint, $usernameData);
echo "Réponse: " . $response2 . "\n\n";

// Test 3: Connexion avec mauvais identifiants
echo "3. Test connexion avec mauvais identifiants...\n";
$badData = [
    'login' => 'wronguser',
    'password' => 'wrongpass'
];

$response3 = testLogin($loginEndpoint, $badData);
echo "Réponse: " . $response3 . "\n\n";

// Test 4: Test avec champ manquant
echo "4. Test avec champ manquant...\n";
$incompleteData = [
    'login' => 'admin'
    // password manquant
];

$response4 = testLogin($loginEndpoint, $incompleteData);
echo "Réponse: " . $response4 . "\n\n";

function testLogin($url, $data) {
    $ch = curl_init();
    
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error) {
        return "❌ Erreur cURL: " . $error;
    }
    
    $result = "HTTP $httpCode - ";
    
    if ($response) {
        $decoded = json_decode($response, true);
        if ($decoded) {
            if ($decoded['success'] ?? false) {
                $result .= "✅ Succès: " . ($decoded['message'] ?? 'OK');
                if (isset($decoded['data']['user']['username'])) {
                    $result .= " (User: " . $decoded['data']['user']['username'] . ")";
                }
            } else {
                $result .= "❌ Échec: " . ($decoded['message'] ?? 'Erreur inconnue');
            }
        } else {
            $result .= "❌ Réponse JSON invalide: " . substr($response, 0, 200);
        }
    } else {
        $result .= "❌ Aucune réponse";
    }
    
    return $result;
}

echo "=== COMMANDES CURL ÉQUIVALENTES ===\n\n";

echo "Test avec email:\n";
echo "curl -X POST http://localhost:8000/api/auth/login \\\n";
echo "  -H \"Content-Type: application/json\" \\\n";
echo "  -d '{\"login\":\"admin@taskmanager.local\",\"password\":\"Admin123!\"}'\n\n";

echo "Test avec username:\n";
echo "curl -X POST http://localhost:8000/api/auth/login \\\n";
echo "  -H \"Content-Type: application/json\" \\\n";
echo "  -d '{\"login\":\"admin\",\"password\":\"Admin123!\"}'\n\n";

echo "=== FIN DU TEST API ===\n";
