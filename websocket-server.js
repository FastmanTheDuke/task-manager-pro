const WebSocket = require('ws');
const url = require('url');

// Configuration
const PORT = 8080;
const HOST = 'localhost';

// Créer le serveur WebSocket
const wss = new WebSocket.Server({ 
    port: PORT,
    host: HOST,
    verifyClient: (info) => {
        console.log(`[${new Date().toISOString()}] Nouvelle tentative de connexion depuis:`, info.origin);
        
        // En développement, accepter toutes les connexions
        // En production, vérifier l'origine
        const allowedOrigins = [
            'http://localhost:3000',
            'http://localhost:3001', 
            'http://localhost:8000',
            'http://127.0.0.1:3000',
            'http://127.0.0.1:3001',
            'http://127.0.0.1:8000'
        ];
        
        if (process.env.NODE_ENV === 'production') {
            return allowedOrigins.includes(info.origin);
        }
        
        return true; // Accepter toutes les connexions en dev
    }
});

// Map pour stocker les connexions par userId
const userConnections = new Map();

console.log(`[${new Date().toISOString()}] 🚀 Serveur WebSocket démarré sur ws://${HOST}:${PORT}`);
console.log(`[${new Date().toISOString()}] Environnement: ${process.env.NODE_ENV || 'development'}`);

// Gestion des nouvelles connexions
wss.on('connection', function connection(ws, req) {
    try {
        // Extraire userId de l'URL
        const parsedUrl = url.parse(req.url, true);
        const userId = parsedUrl.query.userId;
        
        if (!userId) {
            console.log(`[${new Date().toISOString()}] ⚠️  Connexion rejetée: userId manquant`);
            ws.close(1008, 'userId requis');
            return;
        }
        
        // Stocker la connexion
        ws.userId = userId;
        userConnections.set(userId, ws);
        
        console.log(`[${new Date().toISOString()}] ✅ Nouvelle connexion WebSocket pour userId: ${userId}`);
        console.log(`[${new Date().toISOString()}] 📊 Connexions actives: ${userConnections.size}`);
        
        // Envoyer un message de bienvenue
        const welcomeMessage = {
            type: 'connection_established',
            data: { 
                message: 'Connexion WebSocket réussie',
                userId: userId,
                timestamp: new Date().toISOString(),
                server: 'TaskManager WebSocket Server v1.0'
            }
        };
        
        ws.send(JSON.stringify(welcomeMessage));
        
        // Heartbeat pour maintenir la connexion
        ws.isAlive = true;
        ws.on('pong', () => {
            ws.isAlive = true;
        });
        
        // Gestion des messages reçus
        ws.on('message', function incoming(message) {
            try {
                console.log(`[${new Date().toISOString()}] 📨 Message reçu de userId ${userId}:`, message.toString());
                
                let parsedMessage;
                try {
                    parsedMessage = JSON.parse(message.toString());
                } catch (e) {
                    // Si ce n'est pas du JSON, traiter comme texte simple
                    parsedMessage = {
                        type: 'text_message',
                        data: { message: message.toString() }
                    };
                }
                
                // Traitement des différents types de messages
                switch (parsedMessage.type) {
                    case 'ping':
                        // Répondre au ping
                        ws.send(JSON.stringify({
                            type: 'pong',
                            data: { 
                                message: 'Serveur actif',
                                timestamp: new Date().toISOString()
                            }
                        }));
                        break;
                        
                    case 'task_update':
                        // Diffuser les mises à jour de tâches aux autres utilisateurs
                        broadcastToOthers(userId, {
                            type: 'task_updated',
                            data: parsedMessage.data
                        });
                        break;
                        
                    case 'project_update':
                        // Diffuser les mises à jour de projets
                        broadcastToOthers(userId, {
                            type: 'project_updated',
                            data: parsedMessage.data
                        });
                        break;
                        
                    case 'user_status':
                        // Diffuser le statut utilisateur
                        broadcastToAll({
                            type: 'user_status_changed',
                            data: {
                                userId: userId,
                                status: parsedMessage.data.status,
                                timestamp: new Date().toISOString()
                            }
                        });
                        break;
                        
                    default:
                        // Echo par défaut pour les messages non reconnus
                        ws.send(JSON.stringify({
                            type: 'echo',
                            data: { 
                                original: parsedMessage,
                                timestamp: new Date().toISOString()
                            }
                        }));
                        break;
                }
                
            } catch (error) {
                console.error(`[${new Date().toISOString()}] ❌ Erreur traitement message userId ${userId}:`, error);
                ws.send(JSON.stringify({
                    type: 'error',
                    data: { message: 'Erreur de traitement du message' }
                }));
            }
        });
        
        // Gestion de la fermeture de connexion
        ws.on('close', function close(code, reason) {
            console.log(`[${new Date().toISOString()}] 🔴 Connexion fermée pour userId ${userId}. Code: ${code}, Raison: ${reason}`);
            userConnections.delete(userId);
            console.log(`[${new Date().toISOString()}] 📊 Connexions actives: ${userConnections.size}`);
            
            // Notifier les autres utilisateurs
            broadcastToAll({
                type: 'user_disconnected',
                data: {
                    userId: userId,
                    timestamp: new Date().toISOString()
                }
            });
        });
        
        // Gestion des erreurs de connexion
        ws.on('error', function error(err) {
            console.error(`[${new Date().toISOString()}] ❌ Erreur WebSocket userId ${userId}:`, err);
            userConnections.delete(userId);
        });
        
        // Notifier les autres utilisateurs de la nouvelle connexion
        broadcastToOthers(userId, {
            type: 'user_connected',
            data: {
                userId: userId,
                timestamp: new Date().toISOString()
            }
        });
        
    } catch (error) {
        console.error(`[${new Date().toISOString()}] ❌ Erreur lors de l'établissement de la connexion:`, error);
        ws.close(1011, 'Erreur serveur');
    }
});

// Fonction pour diffuser un message à tous les utilisateurs connectés
function broadcastToAll(message) {
    const messageStr = JSON.stringify(message);
    let sentCount = 0;
    
    userConnections.forEach((ws, userId) => {
        if (ws.readyState === WebSocket.OPEN) {
            try {
                ws.send(messageStr);
                sentCount++;
            } catch (error) {
                console.error(`[${new Date().toISOString()}] ❌ Erreur envoi à userId ${userId}:`, error);
                userConnections.delete(userId);
            }
        } else {
            userConnections.delete(userId);
        }
    });
    
    console.log(`[${new Date().toISOString()}] 📤 Message diffusé à ${sentCount} utilisateurs`);
}

// Fonction pour diffuser un message à tous sauf l'expéditeur
function broadcastToOthers(senderUserId, message) {
    const messageStr = JSON.stringify(message);
    let sentCount = 0;
    
    userConnections.forEach((ws, userId) => {
        if (userId !== senderUserId && ws.readyState === WebSocket.OPEN) {
            try {
                ws.send(messageStr);
                sentCount++;
            } catch (error) {
                console.error(`[${new Date().toISOString()}] ❌ Erreur envoi à userId ${userId}:`, error);
                userConnections.delete(userId);
            }
        }
    });
    
    console.log(`[${new Date().toISOString()}] 📤 Message diffusé à ${sentCount} autres utilisateurs`);
}

// Heartbeat pour détecter les connexions fermées
const heartbeat = setInterval(() => {
    let activeConnections = 0;
    
    userConnections.forEach((ws, userId) => {
        if (ws.readyState === WebSocket.OPEN) {
            if (ws.isAlive === false) {
                console.log(`[${new Date().toISOString()}] 💔 Connexion morte détectée pour userId ${userId}`);
                ws.terminate();
                userConnections.delete(userId);
                return;
            }
            
            ws.isAlive = false;
            ws.ping();
            activeConnections++;
        } else {
            userConnections.delete(userId);
        }
    });
    
    if (activeConnections > 0) {
        console.log(`[${new Date().toISOString()}] 💓 Heartbeat: ${activeConnections} connexions actives`);
    }
}, 30000); // Toutes les 30 secondes

// Gestion des erreurs du serveur
wss.on('error', function error(err) {
    console.error(`[${new Date().toISOString()}] ❌ Erreur serveur WebSocket:`, err);
});

// Gestion de l'arrêt propre
process.on('SIGTERM', () => {
    console.log(`[${new Date().toISOString()}] 🛑 Arrêt du serveur WebSocket...`);
    clearInterval(heartbeat);
    
    // Fermer toutes les connexions
    userConnections.forEach((ws) => {
        ws.close(1001, 'Serveur en arrêt');
    });
    
    wss.close(() => {
        console.log(`[${new Date().toISOString()}] ✅ Serveur WebSocket arrêté proprement`);
        process.exit(0);
    });
});

process.on('SIGINT', () => {
    console.log(`[${new Date().toISOString()}] 🛑 Interruption reçue, arrêt du serveur...`);
    process.exit(0);
});

// Log du statut toutes les 5 minutes
setInterval(() => {
    console.log(`[${new Date().toISOString()}] 📊 Statut: ${userConnections.size} connexions actives`);
}, 5 * 60 * 1000);
