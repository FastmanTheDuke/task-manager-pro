import React, { createContext, useState, useContext, useEffect } from 'react';
import authService from '../services/authService';
import notificationService from '../services/notificationService';

const AuthContext = createContext(null);

export const useAuth = () => {
  const context = useContext(AuthContext);
  if (!context) {
    throw new Error('useAuth must be used within an AuthProvider');
  }
  return context;
};

export const AuthProvider = ({ children }) => {
  const [user, setUser] = useState(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    // Vérifier si l'utilisateur est déjà connecté
    const currentUser = authService.getCurrentUser();
    if (currentUser && authService.isAuthenticated()) {
      setUser(currentUser);
      
      // Connecter aux notifications WebSocket seulement si activé
      // Pour l'instant, on désactive les WebSockets jusqu'à ce que le serveur soit configuré
      if (process.env.REACT_APP_ENABLE_WEBSOCKET === 'true') {
        notificationService.enable();
        notificationService.connect(currentUser.id);
      } else {
        console.log('WebSocket notifications disabled in configuration');
      }
    }
    setLoading(false);
  }, []);

  const login = async (login, password) => {
    const result = await authService.login(login, password);
    if (result.success) {
      setUser(result.user);
      
      // Connecter aux notifications seulement si activé
      if (process.env.REACT_APP_ENABLE_WEBSOCKET === 'true') {
        notificationService.enable();
        notificationService.connect(result.user.id);
      }
    }
    return result;
  };

  const register = async (userData) => {
    const result = await authService.register(userData);
    if (result.success) {
      setUser(result.user);
      
      // Connecter aux notifications seulement si activé
      if (process.env.REACT_APP_ENABLE_WEBSOCKET === 'true') {
        notificationService.enable();
        notificationService.connect(result.user.id);
      }
    }
    return result;
  };

  const logout = async () => {
    const result = await authService.logout();
    if (result.success) {
      setUser(null);
      notificationService.disconnect();
    }
    return result;
  };

  const updateUser = (updatedUser) => {
    setUser(updatedUser);
    localStorage.setItem('user', JSON.stringify(updatedUser));
  };

  const value = {
    user,
    loading,
    login,
    register,
    logout,
    updateUser,
    isAuthenticated: !!user,
  };

  return <AuthContext.Provider value={value}>{children}</AuthContext.Provider>;
};