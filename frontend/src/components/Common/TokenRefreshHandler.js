import React, { useEffect } from 'react';
import { useAuth } from '../contexts/AuthContext';

const TokenRefreshHandler = () => {
  const { logout } = useAuth();

  useEffect(() => {
    // Fonction pour vérifier si le token est expiré
    const checkTokenExpiration = () => {
      const token = localStorage.getItem('token');
      if (!token) return;

      try {
        const base64Url = token.split('.')[1];
        const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
        const jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
          return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
        }).join(''));
        
        const decoded = JSON.parse(jsonPayload);
        const currentTime = Date.now() / 1000;
        
        // Si le token expire dans moins de 2 minutes, demander une reconnexion
        if (decoded.exp < (currentTime + 120)) {
          console.log('Token will expire soon, please login again');
          
          // Afficher une notification à l'utilisateur
          if (window.confirm('Votre session va expirer. Voulez-vous vous reconnecter maintenant ?')) {
            logout();
          }
        }
      } catch (error) {
        console.error('Error checking token expiration:', error);
      }
    };

    // Vérifier l'expiration du token toutes les minutes
    const interval = setInterval(checkTokenExpiration, 60000);
    
    // Vérifier immédiatement
    checkTokenExpiration();

    return () => clearInterval(interval);
  }, [logout]);

  return null; // Ce composant ne rend rien
};

export default TokenRefreshHandler;