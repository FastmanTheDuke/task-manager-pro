# Test rapide des deux formats PowerShell
Write-Host "=== TEST RAPIDE - DEUX FORMATS ===" -ForegroundColor Green

$headers = @{ "Content-Type" = "application/json" }

# Test 1: Format actuel (email)
Write-Host "1. Test avec champ 'email'..." -ForegroundColor Yellow
$bodyEmail = '{"email":"admin@taskmanager.local","password":"Admin123!"}'

try {
    $response = Invoke-RestMethod -Uri "http://localhost:8000/api/auth/login" -Method POST -Headers $headers -Body $bodyEmail
    Write-Host "✅ SUCCÈS avec champ 'email'" -ForegroundColor Green
    Write-Host ($response | ConvertTo-Json -Depth 3) -ForegroundColor White
} catch {
    Write-Host "❌ ÉCHEC avec champ 'email'" -ForegroundColor Red
    if ($_.Exception.Response) {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $responseBody = $reader.ReadToEnd()
        Write-Host $responseBody -ForegroundColor Red
    }
}

Write-Host ""

# Test 2: Format nouveau (login) 
Write-Host "2. Test avec champ 'login'..." -ForegroundColor Yellow
$bodyLogin = '{"login":"admin@taskmanager.local","password":"Admin123!"}'

try {
    $response = Invoke-RestMethod -Uri "http://localhost:8000/api/auth/login" -Method POST -Headers $headers -Body $bodyLogin
    Write-Host "✅ SUCCÈS avec champ 'login'" -ForegroundColor Green
    Write-Host ($response | ConvertTo-Json -Depth 3) -ForegroundColor White
} catch {
    Write-Host "❌ ÉCHEC avec champ 'login'" -ForegroundColor Red
    if ($_.Exception.Response) {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $responseBody = $reader.ReadToEnd()
        Write-Host $responseBody -ForegroundColor Red
    }
}

Write-Host ""

# Test 3: Username avec email
Write-Host "3. Test username avec champ 'email'..." -ForegroundColor Yellow
$bodyUsernameEmail = '{"email":"admin","password":"Admin123!"}'

try {
    $response = Invoke-RestMethod -Uri "http://localhost:8000/api/auth/login" -Method POST -Headers $headers -Body $bodyUsernameEmail
    Write-Host "✅ SUCCÈS username avec 'email'" -ForegroundColor Green
    Write-Host ($response | ConvertTo-Json -Depth 3) -ForegroundColor White
} catch {
    Write-Host "❌ ÉCHEC username avec 'email'" -ForegroundColor Red
    if ($_.Exception.Response) {
        $reader = New-Object System.IO.StreamReader($_.Exception.Response.GetResponseStream())
        $responseBody = $reader.ReadToEnd()
        Write-Host $responseBody -ForegroundColor Red
    }
}

Write-Host ""
Write-Host "=== CONCLUSION ===" -ForegroundColor Cyan
Write-Host "Si le test 1 fonctionne : L'API utilise encore l'ancien format"
Write-Host "Si le test 2 fonctionne : L'API utilise le nouveau format"
Write-Host "Si aucun ne fonctionne : Problème d'authentification"
