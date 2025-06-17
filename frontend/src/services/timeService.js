import api from './api';

const timeService = {
  // Récupérer toutes les entrées de temps
  getAllTimeEntries: async (filters = {}) => {
    try {
      const params = new URLSearchParams(filters);
      const response = await api.get(`/time?${params}`);
      return {
        success: true,
        data: response.data.data,
      };
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Erreur lors de la récupération des entrées',
      };
    }
  },

  // Démarrer le chronomètre
  startTimer: async (taskId, description = '') => {
    try {
      const response = await api.post('/time/start', {
        task_id: taskId,
        description,
      });
      return {
        success: true,
        data: response.data.data,
        message: response.data.message,
      };
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Erreur lors du démarrage du chronomètre',
      };
    }
  },

  // Arrêter le chronomètre
  stopTimer: async (id = null) => {
    try {
      const params = id ? `?id=${id}` : '';
      const response = await api.post(`/time/stop${params}`);
      return {
        success: true,
        data: response.data.data,
        message: response.data.message,
      };
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Erreur lors de l\'arrêt du chronomètre',
      };
    }
  },

  // Supprimer une entrée de temps
  deleteTimeEntry: async (id) => {
    try {
      const response = await api.delete(`/time/delete?id=${id}`);
      return {
        success: true,
        message: response.data.message,
      };
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Erreur lors de la suppression',
      };
    }
  },

  // Obtenir les statistiques
  getStats: async (projectId = null, period = 'month') => {
    try {
      const params = new URLSearchParams();
      if (projectId) params.append('project_id', projectId);
      params.append('period', period);
      
      const response = await api.get(`/time/stats?${params}`);
      return {
        success: true,
        data: response.data.data,
      };
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Erreur lors de la récupération des stats',
      };
    }
  },
};

export default timeService;