<?php
/**
 * Diagnostic WebSocket - Aide à identifier et résoudre les problèmes WebSocket
 * Usage: php diagnose_websocket.php
 */

echo "=== Diagnostic WebSocket ===\n\n";

// Configuration
$websocketHost = 'localhost';
$websocketPort = 8080;
$testUserId = 1;

// Test 1: Vérifier si le port est ouvert
echo "1. Test de connexion au port WebSocket...\n";

$connection = @fsockopen($websocketHost, $websocketPort, $errno, $errstr, 5);
if ($connection) {
    echo "✓ Port $websocketPort ouvert sur $websocketHost\n";
    fclose($connection);
} else {
    echo "✗ Port $websocketPort fermé ou inaccessible\n";
    echo "  Erreur: $errstr ($errno)\n";
    echo "  Solution: Démarrer le serveur WebSocket\n\n";
}

// Test 2: Vérifier les processus en cours
echo "\n2. Vérification des processus WebSocket...\n";

if (PHP_OS_FAMILY === 'Windows') {
    $output = shell_exec('netstat -an | findstr :8080');
} else {
    $output = shell_exec('netstat -tuln | grep :8080');
}

if ($output) {
    echo "✓ Processus trouvé sur le port 8080:\n";
    echo "  " . trim($output) . "\n";
} else {
    echo "✗ Aucun processus trouvé sur le port 8080\n";
    echo "  Le serveur WebSocket n'est probablement pas démarré\n";
}

// Test 3: Vérifier la configuration frontend
echo "\n3. Vérification de la configuration WebSocket...\n";

$frontendPath = 'frontend/src';
$configFiles = [
    'config/config.js',
    'config/index.js',
    'services/websocket.js',
    'services/WebSocketService.js',
    'utils/websocket.js'
];

$foundConfig = false;
foreach ($configFiles as $configFile) {
    $fullPath = $frontendPath . '/' . $configFile;
    if (file_exists($fullPath)) {
        $content = file_get_contents($fullPath);
        if (strpos($content, '8080') !== false || strpos($content, 'ws://') !== false) {
            echo "✓ Configuration WebSocket trouvée dans: $configFile\n";
            
            // Extraire l'URL WebSocket
            preg_match('/ws:\/\/[^\s\'"]*/', $content, $matches);
            if ($matches) {
                echo "  URL WebSocket: " . $matches[0] . "\n";
            }
            $foundConfig = true;
        }
    }
}

if (!$foundConfig) {
    echo "⚠ Configuration WebSocket non trouvée dans le frontend\n";
    echo "  Vérifier les fichiers de configuration React\n";
}

// Test 4: Créer un client WebSocket de test simple
echo "\n4. Test de connexion WebSocket basique...\n";

// Script de test JavaScript pour le navigateur
$jsTestScript = <<<'EOL'
<!DOCTYPE html>
<html>
<head>
    <title>Test WebSocket</title>
</head>
<body>
    <h1>Test WebSocket</h1>
    <div id="status">Connexion en cours...</div>
    <div id="messages"></div>
    
    <script>
        const statusDiv = document.getElementById('status');
        const messagesDiv = document.getElementById('messages');
        
        function log(message) {
            console.log(message);
            messagesDiv.innerHTML += '<p>' + new Date().toLocaleTimeString() + ': ' + message + '</p>';
        }
        
        try {
            const ws = new WebSocket('ws://localhost:8080/?userId=1');
            
            ws.onopen = function(event) {
                statusDiv.innerHTML = '✓ Connexion WebSocket réussie';
                statusDiv.style.color = 'green';
                log('Connexion WebSocket ouverte');
                
                // Test d'envoi de message
                ws.send(JSON.stringify({
                    type: 'ping',
                    data: { message: 'Test de connexion' }
                }));
            };
            
            ws.onmessage = function(event) {
                log('Message reçu: ' + event.data);
            };
            
            ws.onerror = function(error) {
                statusDiv.innerHTML = '✗ Erreur WebSocket';
                statusDiv.style.color = 'red';
                log('Erreur WebSocket: ' + error);
            };
            
            ws.onclose = function(event) {
                statusDiv.innerHTML = '⚠ Connexion WebSocket fermée';
                statusDiv.style.color = 'orange';
                log('Connexion fermée. Code: ' + event.code + ', Raison: ' + event.reason);
            };
            
            // Fermer la connexion après 10 secondes
            setTimeout(() => {
                if (ws.readyState === WebSocket.OPEN) {
                    ws.close();
                    log('Connexion fermée après test');
                }
            }, 10000);
            
        } catch (error) {
            statusDiv.innerHTML = '✗ Erreur de création WebSocket';
            statusDiv.style.color = 'red';
            log('Erreur: ' + error.message);
        }
    </script>
</body>
</html>
EOL;

file_put_contents('websocket_test.html', $jsTestScript);
echo "✓ Fichier de test créé: websocket_test.html\n";
echo "  Ouvrir ce fichier dans un navigateur pour tester la connexion\n";

// Test 5: Vérifier les dépendances Node.js
echo "\n5. Vérification des dépendances WebSocket...\n";

if (file_exists('package.json')) {
    $package = json_decode(file_get_contents('package.json'), true);
    $wsLibraries = ['ws', 'socket.io', 'websocket'];
    
    $foundLibs = [];
    foreach ($wsLibraries as $lib) {
        if (isset($package['dependencies'][$lib]) || isset($package['devDependencies'][$lib])) {
            $foundLibs[] = $lib;
        }
    }
    
    if ($foundLibs) {
        echo "✓ Bibliothèques WebSocket trouvées: " . implode(', ', $foundLibs) . "\n";
    } else {
        echo "⚠ Aucune bibliothèque WebSocket trouvée dans package.json\n";
    }
} else {
    echo "ℹ package.json non trouvé dans le répertoire courant\n";
}

// Test 6: Proposer des solutions
echo "\n6. Solutions recommandées...\n";

echo "Si WebSocket ne fonctionne pas:\n\n";

echo "A. Démarrer le serveur WebSocket:\n";
echo "   • Vérifier s'il existe un fichier websocket-server.js ou server.js\n";
echo "   • Démarrer avec: node websocket-server.js\n";
echo "   • Ou avec: npm run websocket\n\n";

echo "B. Installation des dépendances:\n";
echo "   • npm install ws\n";
echo "   • npm install socket.io (si utilisé)\n\n";

echo "C. Créer un serveur WebSocket simple:\n";
echo "   • Copier le code suivant dans websocket-server.js:\n\n";

$simpleServer = <<<'EOL'
const WebSocket = require('ws');

const wss = new WebSocket.Server({ 
    port: 8080,
    verifyClient: (info) => {
        console.log('Nouvelle connexion depuis:', info.origin);
        return true; // Accepter toutes les connexions en dev
    }
});

console.log('Serveur WebSocket démarré sur ws://localhost:8080');

wss.on('connection', function connection(ws, req) {
    const url = new URL(req.url, 'http://localhost:8080');
    const userId = url.searchParams.get('userId');
    
    console.log('Nouvelle connexion WebSocket, userId:', userId);
    
    // Envoyer un message de bienvenue
    ws.send(JSON.stringify({
        type: 'welcome',
        data: { message: 'Connexion WebSocket réussie', userId: userId }
    }));
    
    ws.on('message', function incoming(message) {
        console.log('Message reçu:', message.toString());
        
        // Echo du message
        ws.send(JSON.stringify({
            type: 'echo',
            data: { message: message.toString() }
        }));
    });
    
    ws.on('close', function close() {
        console.log('Connexion fermée pour userId:', userId);
    });
    
    ws.on('error', function error(err) {
        console.error('Erreur WebSocket:', err);
    });
});

wss.on('error', function error(err) {
    console.error('Erreur serveur WebSocket:', err);
});
EOL;

file_put_contents('websocket-server-simple.js', $simpleServer);
echo "✓ Serveur WebSocket simple créé: websocket-server-simple.js\n";
echo "  Démarrer avec: node websocket-server-simple.js\n\n";

echo "D. Test complet:\n";
echo "   1. Démarrer le serveur WebSocket: node websocket-server-simple.js\n";
echo "   2. Ouvrir websocket_test.html dans un navigateur\n";
echo "   3. Vérifier la console pour les messages\n\n";

echo "E. Intégration avec React:\n";
echo "   • Vérifier que l'URL WebSocket est correcte dans le code React\n";
echo "   • S'assurer que la connexion se fait après l'authentification\n";
echo "   • Ajouter une gestion d'erreur appropriée\n\n";

echo "=== Résumé ===\n";
echo "Fichiers créés pour le diagnostic:\n";
echo "• websocket_test.html - Test navigateur\n";
echo "• websocket-server-simple.js - Serveur simple\n\n";

echo "Prochaines étapes:\n";
echo "1. Tester la connexion avec le serveur simple\n";
echo "2. Vérifier les logs du serveur WebSocket\n";
echo "3. Adapter le serveur aux besoins de l'application\n";
echo "4. Tester l'intégration avec le frontend React\n\n";

echo "Diagnostic terminé.\n";
