
import api from './api';

const authService = {
  // Inscription
  register: async (userData) => {
    try {
      const response = await api.post('/auth/register', userData);
      if (response.data.success) {
        const { token, user } = response.data.data;
        localStorage.setItem('token', token);
        localStorage.setItem('user', JSON.stringify(user));
        return { success: true, user, token };
      }
      return { success: false, message: response.data.message };
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Erreur lors de l\'inscription',
      };
    }
  },

  // Connexion - Support flexible avec email ou username
  login: async (login, password) => {
    try {
      const response = await api.post('/auth/login', { login, password });
      if (response.data.success) {
        const { token, user } = response.data.data;
        localStorage.setItem('token', token);
        localStorage.setItem('user', JSON.stringify(user));
        return { success: true, user, token };
      }
      return { success: false, message: response.data.message };
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Erreur lors de la connexion',
      };
    }
  },

  // Déconnexion
  logout: async () => {
    try {
      await api.post('/auth/logout');
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      return { success: true };
    } catch (error) {
      // Même en cas d'erreur, on déconnecte localement
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      return { success: true };
    }
  },

  // Obtenir l'utilisateur actuel
  getCurrentUser: () => {
    const userStr = localStorage.getItem('user');
    return userStr ? JSON.parse(userStr) : null;
  },

  // Vérifier si l'utilisateur est connecté
  isAuthenticated: () => {
    return !!localStorage.getItem('token');
  },

  // Mot de passe oublié
  forgotPassword: async (email) => {
    try {
      const response = await api.post('/auth/forgot-password', { email });
      return { success: response.data.success, message: response.data.message };
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Erreur lors de la demande',
      };
    }
  },

  // Réinitialiser le mot de passe
  resetPassword: async (token, password, passwordConfirmation) => {
    try {
      const response = await api.post('/auth/reset-password', {
        token,
        password,
        password_confirmation: passwordConfirmation,
      });
      return { success: response.data.success, message: response.data.message };
    } catch (error) {
      return {
        success: false,
        message: error.response?.data?.message || 'Erreur lors de la réinitialisation',
      };
    }
  },
};

export default authService;