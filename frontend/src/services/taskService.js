
import api from './api';

const taskService = {
  // Récupérer toutes les tâches
  getAllTasks: async (filters = {}, page = 1, limit = 20) => {
    try {
      const params = new URLSearchParams({
        page,
        limit,
        ...filters,
      });

      if (filters.tags && Array.isArray(filters.tags)) {
        params.delete('tags');
        params.append('tags', filters.tags.join(','));
      }

      const response = await api.get(`/tasks?${params}`);
      return {
        success: true,
        data: response.data.data,
        pagination: response.data.pagination,
      };
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Erreur lors de la récupération des tâches',
      };
    }
  },

  // Récupérer une tâche par ID
  getTaskById: async (id, includes = []) => {
    try {
      const params = includes.length > 0 ? `?include=${includes.join(',')}` : '';
      const response = await api.get(`/tasks/${id}${params}`);
      return {
        success: true,
        data: response.data.data,
      };
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Erreur lors de la récupération de la tâche',
      };
    }
  },

  // Créer une tâche (correction: utilise POST /tasks au lieu de /tasks/create)
  createTask: async (taskData) => {
    try {
      const response = await api.post('/tasks', taskData);
      return {
        success: true,
        data: response.data.data,
        message: response.data.message,
      };
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Erreur lors de la création de la tâche',
        errors: error.response?.data?.errors,
      };
    }
  },

  // Mettre à jour une tâche (correction: utilise PUT /tasks/{id} au lieu de /tasks/update)
  updateTask: async (id, taskData) => {
    try {
      const response = await api.put(`/tasks/${id}`, taskData);
      return {
        success: true,
        data: response.data.data,
        message: response.data.message,
      };
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Erreur lors de la mise à jour de la tâche',
        errors: error.response?.data?.errors,
      };
    }
  },

  // Supprimer une tâche (correction: utilise DELETE /tasks/{id} au lieu de /tasks/delete)
  deleteTask: async (id) => {
    try {
      const response = await api.delete(`/tasks/${id}`);
      return {
        success: true,
        message: response.data.message,
      };
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Erreur lors de la suppression de la tâche',
      };
    }
  },

  // Partager une tâche
  shareTask: async (id, userIds, message = '') => {
    try {
      const response = await api.post(`/tasks/share?id=${id}`, {
        user_ids: userIds,
        message,
      });
      return {
        success: true,
        message: response.data.message,
      };
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Erreur lors du partage de la tâche',
      };
    }
  },
};

export default taskService;