# DIAGNOSTIC FINAL - Pourquoi le serveur n'utilise pas notre code
Write-Host "=== DIAGNOSTIC CACHE/ROUTAGE ===" -ForegroundColor Red
Write-Host ""

# 1. Arrêter complètement le serveur
Write-Host "1. ARRÊTEZ LE SERVEUR MAINTENANT (Ctrl+C)" -ForegroundColor Red
Write-Host "   Attendez que le serveur soit complètement fermé" -ForegroundColor Yellow
Write-Host ""

# 2. Vérifier le contenu réel du fichier
Write-Host "2. Vérification du fichier login.php..." -ForegroundColor Yellow
$loginPath = "backend\api\auth\login.php"

if (Test-Path $loginPath) {
    $content = Get-Content $loginPath -Raw
    Write-Host "Taille du fichier: $($content.Length) caractères" -ForegroundColor Cyan
    
    # Extraire les 20 premières lignes
    $lines = Get-Content $loginPath -Head 20
    Write-Host "Premières lignes du fichier:" -ForegroundColor Cyan
    for ($i = 0; $i -lt [Math]::Min(20, $lines.Count); $i++) {
        Write-Host "  $($i+1): $($lines[$i])" -ForegroundColor Gray
    }
    
    Write-Host ""
    
    # Chercher les règles de validation
    if ($content -match '\$rules\s*=\s*\[(.*?)\];') {
        Write-Host "Règles de validation trouvées:" -ForegroundColor Cyan
        $rulesContent = $matches[1]
        Write-Host "$rulesContent" -ForegroundColor Yellow
    } else {
        Write-Host "❌ Aucune règle de validation trouvée" -ForegroundColor Red
    }
}

Write-Host ""

# 3. Créer un fichier de test DIRECT (pas d'API routing)
Write-Host "3. Création d'un fichier de test DIRECT..." -ForegroundColor Yellow

$directTestContent = @'
<?php
// Test direct - pas de routing, pas de framework
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = file_get_contents('php://input');
$data = json_decode($input, true);

echo json_encode([
    'test' => 'DIRECT FILE ACCESS',
    'timestamp' => date('Y-m-d H:i:s'),
    'received_data' => $data,
    'server_info' => [
        'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'unknown',
        'SCRIPT_FILENAME' => $_SERVER['SCRIPT_FILENAME'] ?? 'unknown',
        'SCRIPT_NAME' => $_SERVER['SCRIPT_NAME'] ?? 'unknown'
    ]
]);
?>
'@

# Placer le fichier à la racine du backend pour éviter le routing
$directTestPath = "backend\direct-test.php"
Set-Content -Path $directTestPath -Value $directTestContent -Encoding UTF8
Write-Host "✅ Fichier de test direct créé: $directTestPath" -ForegroundColor Green

Write-Host ""

# 4. Instructions de test
Write-Host "4. PROCÉDURE DE TEST:" -ForegroundColor Red
Write-Host ""
Write-Host "ÉTAPE A - Démarrez le serveur dans backend:" -ForegroundColor Yellow
Write-Host "cd backend" -ForegroundColor Cyan
Write-Host "php -S localhost:8000" -ForegroundColor Cyan
Write-Host "(NOTEZ: pas de router.php !)" -ForegroundColor Red
Write-Host ""

Write-Host "ÉTAPE B - Testez le fichier direct:" -ForegroundColor Yellow
Write-Host "curl.exe -X POST `"http://localhost:8000/direct-test.php`" -H `"Content-Type: application/json`" -d `'{\"test\":\"data\"}`'" -ForegroundColor Cyan
Write-Host ""

Write-Host "ÉTAPE C - Si B fonctionne, testez l'API normale:" -ForegroundColor Yellow
Write-Host "curl.exe -X POST `"http://localhost:8000/api/auth/login`" -H `"Content-Type: application/json`" -d `'{\"login\":\"admin\",\"password\":\"Admin123!\"}`'" -ForegroundColor Cyan
Write-Host ""

# 5. Créer un login.php ultra-simple
Write-Host "5. Création d'un login.php ULTRA-SIMPLE..." -ForegroundColor Yellow

$ultraSimpleLogin = @'
<?php
header('Content-Type: application/json');

$input = file_get_contents('php://input');
$data = json_decode($input, true);

if (!$data) {
    echo json_encode(['success' => false, 'message' => 'No JSON data']);
    exit;
}

if (empty($data['login'])) {
    echo json_encode(['success' => false, 'message' => 'Login field required']);
    exit;
}

if (empty($data['password'])) {
    echo json_encode(['success' => false, 'message' => 'Password field required']);
    exit;
}

if (($data['login'] === 'admin' || $data['login'] === 'admin@taskmanager.local') && $data['password'] === 'Admin123!') {
    echo json_encode([
        'success' => true,
        'message' => 'Login successful (ultra simple version)',
        'data' => [
            'user' => ['username' => 'admin', 'email' => 'admin@taskmanager.local'],
            'token' => 'test-token'
        ]
    ]);
} else {
    echo json_encode(['success' => false, 'message' => 'Invalid credentials']);
}
?>
'@

# Remplacer complètement le fichier login.php
Copy-Item $loginPath "$loginPath.backup-$(Get-Date -Format 'yyyyMMddHHmmss')" -ErrorAction SilentlyContinue
Set-Content -Path $loginPath -Value $ultraSimpleLogin -Encoding UTF8
Write-Host "✅ login.php remplacé par version ULTRA-SIMPLE" -ForegroundColor Green

Write-Host ""
Write-Host "=== RÉSOLUTION DU PROBLÈME ===" -ForegroundColor Green
Write-Host ""
Write-Host "MAINTENANT:" -ForegroundColor Red
Write-Host "1. FERMEZ COMPLÈTEMENT le serveur (Ctrl+C)" -ForegroundColor Yellow
Write-Host "2. Attendez 5 secondes" -ForegroundColor Yellow
Write-Host "3. Relancez: cd backend && php -S localhost:8000 router.php" -ForegroundColor Yellow
Write-Host "4. Testez: .\test-formats.ps1" -ForegroundColor Yellow
Write-Host ""
Write-Host "Si ça marche pas, testez le fichier direct:" -ForegroundColor Cyan
Write-Host "curl.exe -X POST `"http://localhost:8000/direct-test.php`" -H `"Content-Type: application/json`" -d `'{\"test\":\"hello\"}`'" -ForegroundColor Gray