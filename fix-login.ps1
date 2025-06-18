# Script de correction directe des fichiers locaux
Write-Host "=== CORRECTION DIRECTE DES FICHIERS ===" -ForegroundColor Green
Write-Host ""

# 1. Vérifier et corriger login.php
Write-Host "1. Correction de backend\api\auth\login.php..." -ForegroundColor Yellow

$loginFile = "backend\api\auth\login.php"
if (Test-Path $loginFile) {
    $content = Get-Content $loginFile -Raw
    
    # Vérifier le contenu actuel
    if ($content -match "'email'\s*=>\s*\['required'") {
        Write-Host "❌ Le fichier utilise encore l'ancien format" -ForegroundColor Red
        Write-Host "   Correction en cours..." -ForegroundColor Yellow
        
        # Remplacer les règles de validation
        $newContent = $content -replace "'email'\s*=>\s*\['required'[^\]]*\]", "'login' => ['required']"
        $newContent = $newContent -replace "\\\$data\['email'\]", "`$data['login']"
        $newContent = $newContent -replace "authenticate\(\\\$data\['email'\]", "authenticateByLogin(`$data['login']"
        
        # Sauvegarder
        Set-Content -Path $loginFile -Value $newContent -Encoding UTF8
        Write-Host "✅ Fichier login.php corrigé" -ForegroundColor Green
    } else {
        Write-Host "✅ Le fichier semble déjà utiliser le bon format" -ForegroundColor Green
    }
} else {
    Write-Host "❌ Fichier login.php non trouvé" -ForegroundColor Red
}

Write-Host ""

# 2. Créer un nouveau login.php correct
Write-Host "2. Création d'un nouveau login.php correct..." -ForegroundColor Yellow

$newLoginContent = @'
<?php
/**
 * Login API Endpoint
 * 
 * Authenticates a user and returns a JWT token
 * Supports login with either email or username
 */

require_once '../../Bootstrap.php';

use TaskManager\Bootstrap;
use TaskManager\Models\User;
use TaskManager\Config\JWTManager;
use TaskManager\Utils\Response;
use TaskManager\Middleware\ValidationMiddleware;

// Initialize application
Bootstrap::init();

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error('Méthode non autorisée', 405);
}

try {
    // Validation rules - accept 'login' field that can be email or username
    $rules = [
        'login' => ['required'],
        'password' => ['required']
    ];
    
    // Validate request data
    $data = ValidationMiddleware::validate($rules);
    
    // Create user model
    $userModel = new User();
    
    // Attempt flexible authentication (email or username)
    $user = $userModel->authenticateByLogin($data['login'], $data['password']);
    
    if (!$user) {
        Response::error('Email/nom d\'utilisateur ou mot de passe incorrect', 401);
    }
    
    // Generate JWT token
    $token = JWTManager::generateToken($user);
    
    // Log successful login
    if (class_exists('\\TaskManager\\Middleware\\LoggerMiddleware')) {
        \TaskManager\Middleware\LoggerMiddleware::logActivity(
            'login',
            'user',
            $user['id'],
            null,
            ['ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown']
        );
    }
    
    Response::success([
        'user' => [
            'id' => $user['id'],
            'username' => $user['username'],
            'email' => $user['email'],
            'first_name' => $user['first_name'],
            'last_name' => $user['last_name'],
            'avatar' => $user['avatar'],
            'role' => $user['role'],
            'theme' => $user['theme'],
            'language' => $user['language'],
            'timezone' => $user['timezone']
        ],
        'token' => $token,
        'expires_in' => 3600
    ], 'Connexion réussie');
    
} catch (\Exception $e) {
    // CORRECTION : Meilleure gestion des erreurs
    error_log('Login error: ' . $e->getMessage());
    error_log('Login error trace: ' . $e->getTraceAsString());
    
    if (Bootstrap::getAppInfo()['environment'] === 'development') {
        // En développement, on montre les détails de l'erreur
        Response::error('Erreur interne: ' . $e->getMessage(), 500);
    } else {
        // En production, on masque les détails
        Response::error('Erreur interne du serveur', 500);
    }
}
'@

# Écraser le fichier avec le nouveau contenu
Set-Content -Path $loginFile -Value $newLoginContent -Encoding UTF8
Write-Host "✅ Nouveau fichier login.php créé" -ForegroundColor Green

Write-Host ""

# 3. Vérifier ResponseService.php (erreur ligne 73)
Write-Host "3. Vérification de ResponseService.php..." -ForegroundColor Yellow
$responseFile = "backend\Services\ResponseService.php"

if (Test-Path $responseFile) {
    $lines = Get-Content $responseFile
    if ($lines.Count -gt 72) {
        Write-Host "   Ligne 73: $($lines[72])" -ForegroundColor Gray
        Write-Host "⚠️  Si erreur 'Array to string conversion', vérifiez cette ligne" -ForegroundColor Yellow
    }
} else {
    Write-Host "❌ Fichier ResponseService.php non trouvé" -ForegroundColor Red
}

Write-Host ""

# 4. Instructions finales
Write-Host "4. Étapes suivantes..." -ForegroundColor Yellow
Write-Host "   1. Fermez le serveur backend (Ctrl+C)" -ForegroundColor Cyan
Write-Host "   2. Relancez: cd backend && php -S localhost:8000 router.php" -ForegroundColor Cyan
Write-Host "   3. Testez: .\test-formats.ps1" -ForegroundColor Cyan

Write-Host ""
Write-Host "=== CORRECTION TERMINÉE ===" -ForegroundColor Green
Write-Host "Le fichier login.php utilise maintenant le champ 'login' au lieu de 'email'" -ForegroundColor White
'@

# Sauvegarder le script
Set-Content -Path "fix-login.ps1" -Value $fixScript -Encoding UTF8
Write-Host "Script fix-login.ps1 créé" -ForegroundColor Green
