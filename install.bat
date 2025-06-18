@echo off
echo === Installation du Task Manager Pro ===
echo.

REM Vérifier si PHP est installé
php --version >nul 2>&1
if errorlevel 1 (
    echo ❌ PHP n'est pas installé. Veuillez installer PHP 8.0 ou supérieur.
    pause
    exit /b 1
)

echo ✓ PHP est installé

REM Vérifier si Composer est installé
composer --version >nul 2>&1
if errorlevel 1 (
    echo ❌ Composer n'est pas installé. Veuillez installer Composer.
    echo    Téléchargez-le depuis: https://getcomposer.org/download/
    pause
    exit /b 1
)

echo ✓ Composer est installé

REM Installation des dépendances PHP
echo.
echo 📦 Installation des dépendances PHP...
cd backend
composer install --no-dev --optimize-autoloader

if errorlevel 1 (
    echo ❌ Erreur lors de l'installation des dépendances PHP
    pause
    exit /b 1
)

echo ✓ Dépendances PHP installées avec succès

REM Vérifier/créer le fichier .env
if not exist ".env" (
    echo 📝 Création du fichier .env...
    if exist ".env.example" (
        copy ".env.example" ".env" >nul
    ) else (
        echo DB_HOST=localhost > .env
        echo DB_NAME=task_manager_pro >> .env
        echo DB_USER=root >> .env
        echo DB_PASS= >> .env
        echo DB_PORT=3306 >> .env
        echo. >> .env
        echo JWT_SECRET=your-super-secret-jwt-key-change-in-production >> .env
        echo JWT_EXPIRES_IN=3600 >> .env
        echo. >> .env
        echo CORS_ORIGIN=http://localhost:3000 >> .env
    )
    echo ✓ Fichier .env créé
)

cd ..

REM Installation des dépendances Node.js
echo.
echo 📦 Installation des dépendances Node.js...
cd frontend

npm --version >nul 2>&1
if errorlevel 1 (
    echo ❌ npm n'est pas installé. Veuillez installer Node.js et npm.
    pause
    exit /b 1
)

npm install
if errorlevel 1 (
    echo ❌ Erreur lors de l'installation des dépendances Node.js
    pause
    exit /b 1
)

echo ✓ Dépendances Node.js installées avec succès

cd ..

echo.
echo 🎉 Installation terminée avec succès!
echo.
echo 📚 Prochaines étapes:
echo 1. Configurer votre base de données dans backend\.env
echo 2. Importer le schéma de base de données depuis database\
echo 3. Démarrer le serveur PHP: cd backend ^&^& php -S localhost:8000
echo 4. Démarrer le serveur React: cd frontend ^&^& npm start
echo.
echo 🔗 URLs d'accès:
echo    Frontend: http://localhost:3000
echo    Backend API: http://localhost:8000/task-manager-pro/backend/api
echo    Health Check: http://localhost:8000/task-manager-pro/backend/api/health
echo.
pause
