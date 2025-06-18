# Script de mise à jour et vérification du code
Write-Host "=== MISE À JOUR DU CODE LOCAL ===" -ForegroundColor Green
Write-Host ""

# 1. Vérifier le statut Git
Write-Host "1. Vérification du statut Git..." -ForegroundColor Yellow
try {
    $status = git status --porcelain
    if ($status) {
        Write-Host "⚠️  Modifications locales détectées:" -ForegroundColor Yellow
        git status --short
        Write-Host ""
    } else {
        Write-Host "✅ Répertoire propre" -ForegroundColor Green
    }
} catch {
    Write-Host "❌ Erreur Git: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""

# 2. Récupérer les dernières modifications
Write-Host "2. Récupération des modifications depuis GitHub..." -ForegroundColor Yellow
try {
    git fetch origin
    git pull origin main
    Write-Host "✅ Code mis à jour depuis GitHub" -ForegroundColor Green
} catch {
    Write-Host "❌ Erreur lors de la mise à jour: $($_.Exception.Message)" -ForegroundColor Red
}

Write-Host ""

# 3. Vérifier le contenu du fichier login.php
Write-Host "3. Vérification du fichier login.php..." -ForegroundColor Yellow
$loginFile = "backend\api\auth\login.php"

if (Test-Path $loginFile) {
    $content = Get-Content $loginFile -Raw
    
    if ($content -match "login.*required") {
        Write-Host "✅ Le fichier utilise le nouveau format (champ 'login')" -ForegroundColor Green
    } elseif ($content -match "email.*required") {
        Write-Host "❌ Le fichier utilise encore l'ancien format (champ 'email')" -ForegroundColor Red
        Write-Host "   Le code local n'est pas à jour" -ForegroundColor Red
    } else {
        Write-Host "⚠️  Format non détecté" -ForegroundColor Yellow
    }
    
    # Afficher les règles de validation
    $rules = ($content | Select-String -Pattern '\$rules\s*=.*?\];' -AllMatches).Matches[0].Value
    if ($rules) {
        Write-Host "   Règles de validation actuelles:" -ForegroundColor Cyan
        Write-Host "   $rules" -ForegroundColor Gray
    }
} else {
    Write-Host "❌ Fichier login.php non trouvé" -ForegroundColor Red
}

Write-Host ""

# 4. Redémarrer le serveur backend
Write-Host "4. Redémarrage recommandé du serveur..." -ForegroundColor Yellow
Write-Host "   1. Fermez le serveur actuel (Ctrl+C)" -ForegroundColor Cyan
Write-Host "   2. Relancez: cd backend && php -S localhost:8000 router.php" -ForegroundColor Cyan

Write-Host ""

# 5. Test rapide après mise à jour
Write-Host "5. Test après mise à jour..." -ForegroundColor Yellow
Write-Host "   Après avoir redémarré le serveur, testez:" -ForegroundColor Cyan
Write-Host "   .\test-formats.ps1" -ForegroundColor Gray

Write-Host ""
Write-Host "=== COMMANDES MANUELLES SI NÉCESSAIRE ===" -ForegroundColor Magenta
Write-Host "git stash                    # Sauvegarder les modifications locales"
Write-Host "git pull origin main         # Récupérer les dernières modifications"
Write-Host "git stash pop               # Restaurer les modifications locales"
Write-Host ""
Write-Host "=== FIN DE LA MISE À JOUR ===" -ForegroundColor Green
