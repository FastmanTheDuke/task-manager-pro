
class NotificationService {
  constructor() {
    this.ws = null;
    this.retryCount = 0;
    this.maxRetries = 3;
    this.retryDelay = 5000; // 5 secondes
    this.isEnabled = false; // Désactivé par défaut jusqu'à configuration
  }

  // Méthode pour activer les WebSockets (à appeler quand le serveur WS est prêt)
  enable() {
    this.isEnabled = true;
  }

  // Méthode pour désactiver les WebSockets
  disable() {
    this.isEnabled = false;
    this.disconnect();
  }

  connect(userId) {
    // Ne pas essayer de se connecter si les WebSockets sont désactivés
    if (!this.isEnabled) {
      console.log('WebSocket notifications disabled');
      return;
    }

    if (this.ws && this.ws.readyState === WebSocket.OPEN) {
      return; // Déjà connecté
    }

    try {
      const wsUrl = process.env.REACT_APP_WS_URL || 'ws://localhost:8080';
      this.ws = new WebSocket(`${wsUrl}/?userId=${userId}`);

      this.ws.onopen = () => {
        console.log('WebSocket connected');
        this.retryCount = 0; // Reset retry count on successful connection
      };

      this.ws.onmessage = (event) => {
        try {
          const notification = JSON.parse(event.data);
          this.handleNotification(notification);
        } catch (error) {
          console.error('Error parsing notification:', error);
        }
      };

      this.ws.onerror = (error) => {
        console.log('WebSocket error (notifications disabled):', error.type);
        // Ne pas afficher d'erreur critique si les WebSockets ne sont pas essentiels
      };

      this.ws.onclose = (event) => {
        console.log('WebSocket closed:', event.code, event.reason);
        
        // Tentative de reconnexion seulement si activé et si ce n'est pas une fermeture intentionnelle
        if (this.isEnabled && event.code !== 1000 && this.retryCount < this.maxRetries) {
          this.retryCount++;
          console.log(`Attempting to reconnect WebSocket (${this.retryCount}/${this.maxRetries})...`);
          setTimeout(() => this.connect(userId), this.retryDelay);
        } else if (this.retryCount >= this.maxRetries) {
          console.log('WebSocket max retries reached. Notifications disabled.');
          this.isEnabled = false;
        }
      };
    } catch (error) {
      console.log('WebSocket connection failed (notifications will be disabled):', error.message);
    }
  }

  disconnect() {
    if (this.ws) {
      this.ws.close(1000, 'Disconnecting');
      this.ws = null;
    }
  }

  handleNotification(notification) {
    // Créer une notification système si supportée
    if ('Notification' in window && Notification.permission === 'granted') {
      new Notification(notification.title || 'Task Manager Pro', {
        body: notification.message,
        icon: '/favicon.ico'
      });
    }

    // Émettre un événement personnalisé pour les composants React
    const event = new CustomEvent('taskManagerNotification', { 
      detail: notification 
    });
    window.dispatchEvent(event);

    // Log pour debug
    console.log('Notification received:', notification);
  }

  // Demander la permission pour les notifications
  async requestPermission() {
    if ('Notification' in window) {
      const permission = await Notification.requestPermission();
      return permission === 'granted';
    }
    return false;
  }

  // Envoyer une notification manuelle (fallback)
  showNotification(title, message, type = 'info') {
    const notification = { title, message, type, timestamp: new Date() };
    this.handleNotification(notification);
  }

  // Vérifier si les WebSockets sont disponibles et connectés
  isConnected() {
    return this.ws && this.ws.readyState === WebSocket.OPEN;
  }

  // Obtenir le statut de la connexion
  getStatus() {
    if (!this.isEnabled) return 'disabled';
    if (!this.ws) return 'disconnected';
    
    switch (this.ws.readyState) {
      case WebSocket.CONNECTING: return 'connecting';
      case WebSocket.OPEN: return 'connected';
      case WebSocket.CLOSING: return 'closing';
      case WebSocket.CLOSED: return 'closed';
      default: return 'unknown';
    }
  }
}

// Export d'une instance singleton
const notificationService = new NotificationService();

export default notificationService;