@echo off
echo 🚀 Démarrage du Task Manager Pro
echo.

REM Vérifier que nous sommes dans le bon dossier
if not exist "backend" (
    echo ❌ Erreur: Vous devez être dans le dossier racine du projet task-manager-pro
    pause
    exit /b 1
)

if not exist "frontend" (
    echo ❌ Erreur: Vous devez être dans le dossier racine du projet task-manager-pro  
    pause
    exit /b 1
)

REM Démarrer le serveur PHP depuis le dossier backend
echo 📡 Démarrage du serveur PHP...
cd backend
start "PHP Server" php -S localhost:8000 -t .
echo ✓ Serveur PHP démarré sur http://localhost:8000
cd ..

REM Attendre un peu pour que le serveur PHP démarre
timeout /t 3 /nobreak >nul

REM Tester l'API
echo 🧪 Test de l'API...
curl -s -o nul -w "%%{http_code}" http://localhost:8000/api/health >temp_status.txt 2>nul
set /p HTTP_CODE=<temp_status.txt
del temp_status.txt

if "%HTTP_CODE%"=="200" (
    echo ✅ API Health Check: OK
) else (
    echo ⚠️  API Health Check: Erreur ^(%HTTP_CODE%^)
)

REM Démarrer le serveur React
echo ⚛️  Démarrage du serveur React...
cd frontend

REM Vérifier si les dépendances sont installées
if not exist "node_modules" (
    echo 📦 Installation des dépendances Node.js...
    npm install
)

REM Démarrer React
start "React Server" npm start
echo ✓ Serveur React en cours de démarrage...
cd ..

echo.
echo 🎉 Les deux serveurs sont démarrés !
echo.
echo 🔗 URLs d'accès:
echo    Frontend:     http://localhost:3000
echo    Backend API:  http://localhost:8000/api  
echo    Health Check: http://localhost:8000/api/health
echo.
echo 💡 Fermez les fenêtres du serveur pour les arrêter
echo    Ou appuyez sur une touche pour fermer cette fenêtre
echo.
pause
