import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import { 
  FolderIcon, 
  PlusIcon, 
  UserGroupIcon, 
  CalendarIcon,
  ClockIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon
} from '@heroicons/react/24/outline';
import { useAuth } from '../../contexts/AuthContext';
import { useTheme } from '../../contexts/ThemeContext';
import ProjectCard from './ProjectCard';
import ProjectFilters from './ProjectFilters';
import LoadingSpinner from '../Common/LoadingSpinner';
import ErrorMessage from '../Common/ErrorMessage';

const ProjectList = () => {
  const { user } = useAuth();
  const { isDark } = useTheme();
  const [projects, setProjects] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [filters, setFilters] = useState({
    search: '',
    status: 'all',
    role: 'all',
    sortBy: 'updated_at',
    sortOrder: 'desc'
  });
  const [stats, setStats] = useState({
    total: 0,
    active: 0,
    completed: 0,
    overdue: 0
  });

  useEffect(() => {
    Promise.all([
      fetchProjects(),
      fetchProjectStats()
    ]);
  }, [filters]);

  const fetchProjects = async () => {
    try {
      setLoading(true);
      setError(null); // Reset error state
      
      const token = localStorage.getItem('token');
      if (!token) {
        throw new Error('Token d\'authentification manquant');
      }
      
      const queryParams = new URLSearchParams({
        ...filters,
        page: 1,
        limit: 50
      });

      console.log('🔍 Fetching projects...', `${process.env.REACT_APP_API_URL}/projects?${queryParams}`);

      const response = await fetch(`${process.env.REACT_APP_API_URL}/projects?${queryParams}`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      console.log('📡 API Response status:', response.status);

      if (!response.ok) {
        const errorText = await response.text();
        console.error('❌ API Error:', response.status, errorText);
        throw new Error(`Erreur ${response.status}: ${errorText}`);
      }

      const data = await response.json();
      console.log('📦 API Response data:', data);

      if (data.success) {
        // Gestion robuste de la structure de réponse
        let projectsArray = [];
        
        if (data.data) {
          if (Array.isArray(data.data)) {
            // Si data.data est directement un array
            projectsArray = data.data;
          } else if (data.data.projects && Array.isArray(data.data.projects)) {
            // Si data.data.projects est un array
            projectsArray = data.data.projects;
          } else {
            console.warn('⚠️ Structure de données inattendue:', data.data);
          }
        }
        
        console.log('✅ Projects loaded:', projectsArray.length, 'projects');
        setProjects(projectsArray);
        
        // Mettre à jour les stats si disponibles
        if (data.data && data.data.pagination) {
          setStats(prev => ({
            ...prev,
            total: data.data.pagination.total || projectsArray.length
          }));
        }
      } else {
        console.error('❌ API returned success=false:', data.message);
        throw new Error(data.message || 'Erreur inconnue');
      }
    } catch (err) {
      console.error('❌ Fetch projects error:', err);
      setError(err.message);
      setProjects([]); // Reset projects on error
    } finally {
      setLoading(false);
    }
  };

  const fetchProjectStats = async () => {
    try {
      const token = localStorage.getItem('token');
      if (!token) return;
      
      const response = await fetch(`${process.env.REACT_APP_API_URL}/dashboard`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success && data.data && data.data.stats) {
          setStats(prev => ({
            ...prev,
            total: data.data.stats.totalProjects || 0,
            active: data.data.stats.activeProjects || 0
          }));
        }
      }
    } catch (err) {
      console.error('⚠️ Erreur stats (non critique):', err);
      // Ne pas afficher d'erreur pour les stats, juste garder les valeurs par défaut
    }
  };

  // Calculer les stats supplémentaires à partir des projets
  useEffect(() => {
    if (projects.length > 0) {
      const completed = projects.filter(p => p.status === 'completed').length;
      const overdue = projects.filter(p => {
        if (!p.end_date || p.status === 'completed') return false;
        return new Date(p.end_date) < new Date();
      }).length;

      setStats(prev => ({
        ...prev,
        completed,
        overdue,
        total: Math.max(prev.total, projects.length) // Prendre le max entre API et count local
      }));
    }
  }, [projects]);

  const handleFilterChange = (newFilters) => {
    setFilters(prev => ({ ...prev, ...newFilters }));
  };

  const handleProjectUpdate = (updatedProject) => {
    setProjects(prev => 
      prev.map(project => 
        project.id === updatedProject.id ? updatedProject : project
      )
    );
  };

  const handleProjectDelete = (projectId) => {
    setProjects(prev => prev.filter(project => project.id !== projectId));
  };

  const handleRetry = () => {
    setError(null);
    fetchProjects();
  };

  if (loading) return <LoadingSpinner />;

  return (
    <div className={`min-h-screen ${isDark ? 'bg-gray-900' : 'bg-gray-50'}`}>
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Header */}
        <div className="mb-8">
          <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
              <h1 className={`text-3xl font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}>
                Mes Projets
              </h1>
              <p className={`mt-2 ${isDark ? 'text-gray-400' : 'text-gray-600'}`}>
                Gérez vos projets collaboratifs et suivez leur progression
              </p>
            </div>
            <Link
              to="/projects/new"
              className="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200"
            >
              <PlusIcon className="h-5 w-5 mr-2" />
              Nouveau Projet
            </Link>
          </div>

          {/* Stats Cards */}
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4 mt-6">
            <div className={`p-4 rounded-lg ${isDark ? 'bg-gray-800' : 'bg-white'} shadow-sm`}>
              <div className="flex items-center">
                <FolderIcon className="h-8 w-8 text-blue-500" />
                <div className="ml-3">
                  <p className={`text-sm font-medium ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
                    Total
                  </p>
                  <p className={`text-2xl font-semibold ${isDark ? 'text-white' : 'text-gray-900'}`}>
                    {stats.total}
                  </p>
                </div>
              </div>
            </div>

            <div className={`p-4 rounded-lg ${isDark ? 'bg-gray-800' : 'bg-white'} shadow-sm`}>
              <div className="flex items-center">
                <ClockIcon className="h-8 w-8 text-green-500" />
                <div className="ml-3">
                  <p className={`text-sm font-medium ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
                    Actifs
                  </p>
                  <p className={`text-2xl font-semibold ${isDark ? 'text-white' : 'text-gray-900'}`}>
                    {stats.active}
                  </p>
                </div>
              </div>
            </div>

            <div className={`p-4 rounded-lg ${isDark ? 'bg-gray-800' : 'bg-white'} shadow-sm`}>
              <div className="flex items-center">
                <CheckCircleIcon className="h-8 w-8 text-blue-500" />
                <div className="ml-3">
                  <p className={`text-sm font-medium ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
                    Terminés
                  </p>
                  <p className={`text-2xl font-semibold ${isDark ? 'text-white' : 'text-gray-900'}`}>
                    {stats.completed}
                  </p>
                </div>
              </div>
            </div>

            <div className={`p-4 rounded-lg ${isDark ? 'bg-gray-800' : 'bg-white'} shadow-sm`}>
              <div className="flex items-center">
                <ExclamationTriangleIcon className="h-8 w-8 text-red-500" />
                <div className="ml-3">
                  <p className={`text-sm font-medium ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
                    En retard
                  </p>
                  <p className={`text-2xl font-semibold ${isDark ? 'text-white' : 'text-gray-900'}`}>
                    {stats.overdue}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>

        {/* Filters */}
        <ProjectFilters
          filters={filters}
          onFilterChange={handleFilterChange}
          isDark={isDark}
        />

        {/* Error Message */}
        {error && (
          <div className="mb-6">
            <ErrorMessage 
              message={error} 
              onClose={() => setError(null)}
            />
            <div className="mt-4 text-center">
              <button
                onClick={handleRetry}
                className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors"
              >
                Réessayer
              </button>
            </div>
          </div>
        )}

        {/* Projects Grid */}
        {!error && projects.length === 0 ? (
          <div className={`text-center py-12 ${isDark ? 'bg-gray-800' : 'bg-white'} rounded-lg shadow-sm`}>
            <FolderIcon className={`mx-auto h-12 w-12 ${isDark ? 'text-gray-600' : 'text-gray-400'}`} />
            <h3 className={`mt-2 text-sm font-medium ${isDark ? 'text-gray-300' : 'text-gray-900'}`}>
              Aucun projet
            </h3>
            <p className={`mt-1 text-sm ${isDark ? 'text-gray-500' : 'text-gray-500'}`}>
              Commencez par créer votre premier projet collaboratif.
            </p>
            <div className="mt-6">
              <Link
                to="/projects/new"
                className="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200"
              >
                <PlusIcon className="h-5 w-5 mr-2" />
                Créer un projet
              </Link>
            </div>
          </div>
        ) : !error && projects.length > 0 ? (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {projects.map(project => (
              <ProjectCard
                key={project.id}
                project={project}
                onUpdate={handleProjectUpdate}
                onDelete={handleProjectDelete}
                isDark={isDark}
              />
            ))}
          </div>
        ) : null}
      </div>
    </div>
  );
};

export default ProjectList;
