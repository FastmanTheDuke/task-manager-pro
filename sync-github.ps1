# Script pour copier exactement le fichier login.php depuis GitHub
Write-Host "=== SYNCHRONISATION AVEC GITHUB ===" -ForegroundColor Green
Write-Host ""

# Contenu exact du fichier login.php depuis GitHub
$correctLoginContent = @'
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

# Sauvegarder le fichier avec le contenu exact de GitHub
$loginPath = "backend\api\auth\login.php"

Write-Host "1. Sauvegarde de l'ancien fichier..." -ForegroundColor Yellow
if (Test-Path $loginPath) {
    Copy-Item $loginPath "$loginPath.backup.$(Get-Date -Format 'yyyyMMdd-HHmmss')"
    Write-Host "✅ Sauvegarde créée" -ForegroundColor Green
}

Write-Host "2. Écriture du nouveau fichier depuis GitHub..." -ForegroundColor Yellow
Set-Content -Path $loginPath -Value $correctLoginContent -Encoding UTF8
Write-Host "✅ Fichier login.php mis à jour avec le contenu GitHub" -ForegroundColor Green

Write-Host "3. Vérification du contenu..." -ForegroundColor Yellow
$content = Get-Content $loginPath -Raw
if ($content -match "'login'\s*=>\s*\['required'\]") {
    Write-Host "✅ Le fichier utilise maintenant le champ 'login'" -ForegroundColor Green
} else {
    Write-Host "❌ Problème : le fichier n'a pas le bon format" -ForegroundColor Red
}

if ($content -match "authenticateByLogin") {
    Write-Host "✅ Le fichier utilise la méthode authenticateByLogin()" -ForegroundColor Green
} else {
    Write-Host "❌ Problème : méthode authenticateByLogin manquante" -ForegroundColor Red
}

Write-Host ""
Write-Host "4. Prochaines étapes..." -ForegroundColor Yellow
Write-Host "   1. Fermez le serveur backend (Ctrl+C)" -ForegroundColor Cyan
Write-Host "   2. Relancez: cd backend && php -S localhost:8000 router.php" -ForegroundColor Cyan
Write-Host "   3. Testez: .\test-formats.ps1" -ForegroundColor Cyan

Write-Host ""
Write-Host "=== SYNCHRONISATION TERMINÉE ===" -ForegroundColor Green
Write-Host "Le fichier login.php est maintenant identique à celui de GitHub" -ForegroundColor White