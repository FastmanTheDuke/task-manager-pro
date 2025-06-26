
import axios from 'axios';

const API_URL = process.env.REACT_APP_API_URL || 'http://localhost/task-manager-pro/backend/api';

// Créer une instance axios avec configuration de base
const api = axios.create({
  baseURL: API_URL,
  headers: {
    'Content-Type': 'application/json',
  },
  withCredentials: true,
});

// Variable pour éviter les rafraîchissements multiples simultanés
let isRefreshing = false;
let failedQueue = [];

const processQueue = (error, token = null) => {
  failedQueue.forEach(prom => {
    if (error) {
      prom.reject(error);
    } else {
      prom.resolve(token);
    }
  });
  
  failedQueue = [];
};

// Fonction pour vérifier si le token est expiré
const isTokenExpired = (token) => {
  if (!token) return true;
  
  try {
    const base64Url = token.split('.')[1];
    const base64 = base64Url.replace(/-/g, '+').replace(/_/g, '/');
    const jsonPayload = decodeURIComponent(atob(base64).split('').map(function(c) {
      return '%' + ('00' + c.charCodeAt(0).toString(16)).slice(-2);
    }).join(''));
    
    const decoded = JSON.parse(jsonPayload);
    const currentTime = Date.now() / 1000;
    
    // Considérer le token comme expiré s'il expire dans moins de 5 minutes
    return decoded.exp < (currentTime + 300);
  } catch (error) {
    console.error('Error decoding token:', error);
    return true;
  }
};

// Intercepteur pour ajouter le token à chaque requête
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token');
    if (token) {
      // Vérifier si le token est expiré avant de l'envoyer
      if (isTokenExpired(token)) {
        console.log('Token is expired, will attempt refresh');
      }
      config.headers.Authorization = `Bearer ${token}`;
    }
    return config;
  },
  (error) => {
    return Promise.reject(error);
  }
);

// Intercepteur pour gérer les erreurs de réponse
api.interceptors.response.use(
  (response) => response,
  async (error) => {
    const originalRequest = error.config;

    console.log('API Error:', error.response?.status, error.response?.data?.message);

    // Si token expiré ou invalide
    if (error.response?.status === 401 && !originalRequest._retry) {
      if (isRefreshing) {
        // Si on est déjà en train de rafraîchir, ajouter à la queue
        return new Promise((resolve, reject) => {
          failedQueue.push({ resolve, reject });
        }).then(token => {
          originalRequest.headers.Authorization = `Bearer ${token}`;
          return api(originalRequest);
        }).catch(err => {
          return Promise.reject(err);
        });
      }

      originalRequest._retry = true;
      isRefreshing = true;

      const token = localStorage.getItem('token');
      
      if (token) {
        try {
          console.log('Attempting to refresh token...');
          
          // Essayer de rafraîchir le token
          const response = await axios.post(`${API_URL}/auth/refresh`, {}, {
            headers: {
              'Authorization': `Bearer ${token}`,
              'Content-Type': 'application/json'
            }
          });
          
          console.log('Token refresh response:', response.data);
          
          if (response.data.success) {
            const { token: newToken } = response.data.data;
            localStorage.setItem('token', newToken);
            
            console.log('Token refreshed successfully');
            
            // Traiter la queue des requêtes en attente
            processQueue(null, newToken);
            
            // Réessayer la requête originale
            originalRequest.headers.Authorization = `Bearer ${newToken}`;
            return api(originalRequest);
          }
        } catch (refreshError) {
          console.error('Token refresh failed:', refreshError.response?.data || refreshError.message);
          processQueue(refreshError, null);
          
          // Token invalide, déconnecter l'utilisateur
          localStorage.removeItem('token');
          localStorage.removeItem('user');
          
          // Afficher un message à l'utilisateur
          if (typeof window !== 'undefined' && window.alert) {
            window.alert('Votre session a expiré. Vous allez être redirigé vers la page de connexion.');
          }
          
          // Rediriger vers login seulement si on n'y est pas déjà
          if (!window.location.pathname.includes('/login')) {
            window.location.href = '/login';
          }
          
          return Promise.reject(refreshError);
        } finally {
          isRefreshing = false;
        }
      } else {
        // Pas de token, rediriger vers login
        console.log('No token found, redirecting to login');
        if (!window.location.pathname.includes('/login')) {
          window.location.href = '/login';
        }
        isRefreshing = false;
        return Promise.reject(error);
      }
    }

    // Pour les autres erreurs, vérifier si c'est un problème d'authentification
    if (error.response?.status === 401) {
      console.log('401 error, clearing auth and redirecting');
      localStorage.removeItem('token');
      localStorage.removeItem('user');
      if (!window.location.pathname.includes('/login')) {
        window.location.href = '/login';
      }
    }

    return Promise.reject(error);
  }
);

export default api;