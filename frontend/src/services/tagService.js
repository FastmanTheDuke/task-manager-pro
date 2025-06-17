import api from './api';

const tagService = {
  // Récupérer tous les tags
  getAllTags: async (projectId = null) => {
    try {
      const params = projectId ? `?project_id=${projectId}` : '';
      const response = await api.get(`/tags${params}`);
      return {
        success: true,
        data: response.data.data,
      };
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Erreur lors de la récupération des tags',
      };
    }
  },

  // Créer un tag
  createTag: async (tagData) => {
    try {
      const response = await api.post('/tags/create', tagData);
      return {
        success: true,
        data: response.data.data,
        message: response.data.message,
      };
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Erreur lors de la création du tag',
        errors: error.response?.data?.errors,
      };
    }
  },

  // Mettre à jour un tag
  updateTag: async (id, tagData) => {
    try {
      const response = await api.put(`/tags/update?id=${id}`, tagData);
      return {
        success: true,
        data: response.data.data,
        message: response.data.message,
      };
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Erreur lors de la mise à jour du tag',
        errors: error.response?.data?.errors,
      };
    }
  },

  // Supprimer un tag
  deleteTag: async (id) => {
    try {
      const response = await api.delete(`/tags/delete?id=${id}`);
      return {
        success: true,
        message: response.data.message,
      };
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Erreur lors de la suppression du tag',
      };
    }
  },
};

export default tagService;