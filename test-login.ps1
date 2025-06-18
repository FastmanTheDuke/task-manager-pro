# Script PowerShell pour tester l'API de login
# Usage: .\test-login.ps1

Write-Host "=== TEST API LOGIN - PowerShell ===" -ForegroundColor Green
Write-Host ""

# Configuration
$baseUrl = "http://localhost:8000"
$username = "admin"
$email = "admin@taskmanager.local"
$password = "Admin123!"

# Headers
$headers = @{
    "Content-Type" = "application/json"
    "Accept" = "application/json"
}

Write-Host "1. Test de l'endpoint direct (sans ValidationMiddleware)..." -ForegroundColor Yellow

# Test 1: Endpoint direct avec username
$body1 = @{
    login = $username
    password = $password
} | ConvertTo-Json

try {
    $response1 = Invoke-RestMethod -Uri "$baseUrl/api/test-login" -Method POST -Headers $headers -Body $body1
    Write-Host "✅ Test direct (username): SUCCÈS" -ForegroundColor Green
    Write-Host "Réponse: $($response1 | ConvertTo-Json -Depth 3)" -ForegroundColor White
} catch {
    Write-Host "❌ Test direct (username): ÉCHEC" -ForegroundColor Red
    Write-Host "Erreur: $($_.Exception.Message)" -ForegroundColor Red
    if ($_.Exception.Response) {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $responseBody = $reader.ReadToEnd()
        Write-Host "Réponse: $responseBody" -ForegroundColor Red
    }
}

Write-Host ""

# Test 2: Endpoint direct avec email
$body2 = @{
    login = $email
    password = $password
} | ConvertTo-Json

try {
    $response2 = Invoke-RestMethod -Uri "$baseUrl/api/test-login" -Method POST -Headers $headers -Body $body2
    Write-Host "✅ Test direct (email): SUCCÈS" -ForegroundColor Green
    Write-Host "Réponse: $($response2 | ConvertTo-Json -Depth 3)" -ForegroundColor White
} catch {
    Write-Host "❌ Test direct (email): ÉCHEC" -ForegroundColor Red
    Write-Host "Erreur: $($_.Exception.Message)" -ForegroundColor Red
    if ($_.Exception.Response) {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $responseBody = $reader.ReadToEnd()
        Write-Host "Réponse: $responseBody" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "2. Test de l'endpoint normal (avec ValidationMiddleware)..." -ForegroundColor Yellow

# Test 3: Endpoint normal avec username
try {
    $response3 = Invoke-RestMethod -Uri "$baseUrl/api/auth/login" -Method POST -Headers $headers -Body $body1
    Write-Host "✅ Test normal (username): SUCCÈS" -ForegroundColor Green
    Write-Host "Réponse: $($response3 | ConvertTo-Json -Depth 3)" -ForegroundColor White
} catch {
    Write-Host "❌ Test normal (username): ÉCHEC" -ForegroundColor Red
    Write-Host "Erreur: $($_.Exception.Message)" -ForegroundColor Red
    if ($_.Exception.Response) {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $responseBody = $reader.ReadToEnd()
        Write-Host "Réponse: $responseBody" -ForegroundColor Red
    }
}

Write-Host ""

# Test 4: Endpoint normal avec email
try {
    $response4 = Invoke-RestMethod -Uri "$baseUrl/api/auth/login" -Method POST -Headers $headers -Body $body2
    Write-Host "✅ Test normal (email): SUCCÈS" -ForegroundColor Green
    Write-Host "Réponse: $($response4 | ConvertTo-Json -Depth 3)" -ForegroundColor White
} catch {
    Write-Host "❌ Test normal (email): ÉCHEC" -ForegroundColor Red
    Write-Host "Erreur: $($_.Exception.Message)" -ForegroundColor Red
    if ($_.Exception.Response) {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $responseBody = $reader.ReadToEnd()
        Write-Host "Réponse: $responseBody" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "3. Test avec l'ancien format (champ email)..." -ForegroundColor Yellow

# Test 5: Ancien format avec champ "email"
$body5 = @{
    email = $email
    password = $password
} | ConvertTo-Json

try {
    $response5 = Invoke-RestMethod -Uri "$baseUrl/api/auth/login" -Method POST -Headers $headers -Body $body5
    Write-Host "✅ Test ancien format: SUCCÈS" -ForegroundColor Green
    Write-Host "Réponse: $($response5 | ConvertTo-Json -Depth 3)" -ForegroundColor White
} catch {
    Write-Host "❌ Test ancien format: ÉCHEC" -ForegroundColor Red
    Write-Host "Erreur: $($_.Exception.Message)" -ForegroundColor Red
    if ($_.Exception.Response) {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $responseBody = $reader.ReadToEnd()
        Write-Host "Réponse: $responseBody" -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "=== FIN DES TESTS ===" -ForegroundColor Green
Write-Host ""
Write-Host "COMMANDES CURL ÉQUIVALENTES POUR POWERSHELL:" -ForegroundColor Cyan
Write-Host "curl -X POST `"$baseUrl/api/test-login`" -H `"Content-Type: application/json`" -d `'{`"login`":`"$username`",`"password`":`"$password`"}`'" -ForegroundColor Gray
Write-Host "curl -X POST `"$baseUrl/api/auth/login`" -H `"Content-Type: application/json`" -d `'{`"login`":`"$username`",`"password`":`"$password`"}`'" -ForegroundColor Gray
