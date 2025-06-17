import api from './api';
import { toast } from 'react-hot-toast';

const notificationService = {
  // WebSocket connection for real-time notifications
  ws: null,

  // Initialize WebSocket connection
  connect: (userId) => {
    if (notificationService.ws) {
      return;
    }

    const wsUrl = process.env.REACT_APP_WEBSOCKET_URL || 'ws://localhost:8080';
    notificationService.ws = new WebSocket(`${wsUrl}?userId=${userId}`);

    notificationService.ws.onmessage = (event) => {
      const notification = JSON.parse(event.data);
      notificationService.showNotification(notification);
    };

    notificationService.ws.onerror = (error) => {
      console.error('WebSocket error:', error);
    };

    notificationService.ws.onclose = () => {
      // Reconnect after 5 seconds
      setTimeout(() => {
        notificationService.ws = null;
        notificationService.connect(userId);
      }, 5000);
    };
  },

  // Disconnect WebSocket
  disconnect: () => {
    if (notificationService.ws) {
      notificationService.ws.close();
      notificationService.ws = null;
    }
  },

  // Show notification toast
  showNotification: (notification) => {
    const { type, title, message } = notification;

    switch (type) {
      case 'success':
        toast.success(message || title);
        break;
      case 'error':
        toast.error(message || title);
        break;
      case 'info':
        toast(message || title, { icon: 'ℹ️' });
        break;
      case 'task_assigned':
        toast(message || title, { icon: '📋' });
        break;
      case 'comment_added':
        toast(message || title, { icon: '💬' });
        break;
      default:
        toast(message || title);
    }
  },

  // Get all notifications
  getNotifications: async (unreadOnly = false) => {
    try {
      const params = unreadOnly ? '?unread=true' : '';
      const response = await api.get(`/notifications${params}`);
      return {
        success: true,
        data: response.data.data,
      };
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Erreur lors de la récupération',
      };
    }
  },

  // Mark notification as read
  markAsRead: async (id) => {
    try {
      const response = await api.put(`/notifications/read?id=${id}`);
      return {
        success: true,
        message: response.data.message,
      };
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Erreur',
      };
    }
  },

  // Mark all notifications as read
  markAllAsRead: async () => {
    try {
      const response = await api.put('/notifications/read-all');
      return {
        success: true,
        message: response.data.message,
      };
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Erreur',
      };
    }
  },
};

export default notificationService;