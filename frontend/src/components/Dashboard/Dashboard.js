import React, { useState, useEffect } from 'react';
import {
  CheckCircleIcon,
  ClockIcon,
  ExclamationTriangleIcon,
  FolderIcon,
  UserGroupIcon,
  CalendarIcon,
  TrendingUpIcon,
  PlusIcon,
  ArrowRightIcon
} from '@heroicons/react/24/outline';
import { Link } from 'react-router-dom';
import { useAuth } from '../../contexts/AuthContext';
import { useTheme } from '../../contexts/ThemeContext';
import LoadingSpinner from '../Common/LoadingSpinner';
import ErrorMessage from '../Common/ErrorMessage';

const Dashboard = () => {
  const { user } = useAuth();
  const { isDark } = useTheme();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [dashboardData, setDashboardData] = useState({
    stats: {
      totalTasks: 0,
      completedTasks: 0,
      pendingTasks: 0,
      overdueTasks: 0,
      totalProjects: 0,
      activeProjects: 0,
      totalTimeTracked: 0,
      tasksCompletedThisWeek: 0
    },
    recentTasks: [],
    recentProjects: [],
    upcomingDeadlines: [],
    timeTracking: [],
    productivity: {
      labels: [],
      completedTasks: [],
      timeSpent: []
    }
  });

  useEffect(() => {
    fetchDashboardData();
  }, []);

  const fetchDashboardData = async () => {
    try {
      setLoading(true);
      const token = localStorage.getItem('token');
      
      const response = await fetch(`${process.env.REACT_APP_API_URL}/dashboard`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      if (!response.ok) {
        throw new Error('Erreur lors du chargement du dashboard');
      }

      const data = await response.json();
      if (data.success) {
        // S'assurer que les arrays sont toujours définis
        const sanitizedData = {
          ...data.data,
          recentTasks: Array.isArray(data.data.recentTasks) ? data.data.recentTasks : [],
          recentProjects: Array.isArray(data.data.recentProjects) ? data.data.recentProjects : [],
          upcomingDeadlines: Array.isArray(data.data.upcomingDeadlines) ? data.data.upcomingDeadlines : [],
          timeTracking: Array.isArray(data.data.timeTracking) ? data.data.timeTracking : [],
          stats: {
            totalTasks: data.data.stats?.totalTasks || 0,
            completedTasks: data.data.stats?.completedTasks || 0,
            pendingTasks: data.data.stats?.pendingTasks || 0,
            overdueTasks: data.data.stats?.overdueTasks || 0,
            totalProjects: data.data.stats?.totalProjects || 0,
            activeProjects: data.data.stats?.activeProjects || 0,
            totalTimeTracked: data.data.stats?.totalTimeTracked || 0,
            tasksCompletedThisWeek: data.data.stats?.tasksCompletedThisWeek || 0
          },
          productivity: {
            labels: Array.isArray(data.data.productivity?.labels) ? data.data.productivity.labels : [],
            completedTasks: Array.isArray(data.data.productivity?.completedTasks) ? data.data.productivity.completedTasks : [],
            timeSpent: Array.isArray(data.data.productivity?.timeSpent) ? data.data.productivity.timeSpent : []
          }
        };
        setDashboardData(sanitizedData);
      } else {
        throw new Error(data.message || 'Erreur inconnue');
      }
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const formatTime = (seconds) => {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    if (hours > 0) {
      return `${hours}h ${minutes}m`;
    }
    return `${minutes}m`;
  };

  const getCompletionPercentage = () => {
    const { totalTasks, completedTasks } = dashboardData.stats;
    return totalTasks > 0 ? Math.round((completedTasks / totalTasks) * 100) : 0;
  };

  const getProductivityTrend = () => {
    const thisWeek = dashboardData.stats.tasksCompletedThisWeek;
    const lastWeek = dashboardData.productivity?.lastWeekTasks || 0;
    if (lastWeek === 0) return { trend: 'stable', percentage: 0 };
    
    const change = ((thisWeek - lastWeek) / lastWeek) * 100;
    return {
      trend: change > 0 ? 'up' : change < 0 ? 'down' : 'stable',
      percentage: Math.abs(Math.round(change))
    };
  };

  const getPriorityColor = (priority) => {
    const colors = {
      urgent: 'text-red-500 bg-red-50 border-red-200',
      high: 'text-orange-500 bg-orange-50 border-orange-200',
      medium: 'text-blue-500 bg-blue-50 border-blue-200',
      low: 'text-green-500 bg-green-50 border-green-200'
    };
    return colors[priority] || colors.medium;
  };

  const getStatusColor = (status) => {
    const colors = {
      completed: 'text-green-600 bg-green-100',
      in_progress: 'text-blue-600 bg-blue-100',
      pending: 'text-yellow-600 bg-yellow-100',
      overdue: 'text-red-600 bg-red-100'
    };
    return colors[status] || colors.pending;
  };

  // S'assurer que les arrays sont toujours définis avant utilisation
  const recentTasks = Array.isArray(dashboardData.recentTasks) ? dashboardData.recentTasks : [];
  const recentProjects = Array.isArray(dashboardData.recentProjects) ? dashboardData.recentProjects : [];
  const upcomingDeadlines = Array.isArray(dashboardData.upcomingDeadlines) ? dashboardData.upcomingDeadlines : [];

  const productivity = getProductivityTrend();

  if (loading) return <LoadingSpinner />;

  return (
    <div className={`min-h-screen ${isDark ? 'bg-gray-900' : 'bg-gray-50'}`}>
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Header */}
        <div className="mb-8">
          <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
              <h1 className={`text-3xl font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}>
                Tableau de Bord
              </h1>
              <p className={`mt-2 ${isDark ? 'text-gray-400' : 'text-gray-600'}`}>
                Bonjour {user?.username}, voici un aperçu de votre activité
              </p>
            </div>
            
            <div className="flex items-center space-x-3">
              <Link
                to="/tasks/new"
                className="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200"
              >
                <PlusIcon className="h-5 w-5 mr-2" />
                Nouvelle Tâche
              </Link>
              <Link
                to="/projects/new"
                className="inline-flex items-center px-4 py-2 border border-gray-300 hover:bg-gray-50 text-gray-700 font-medium rounded-lg transition-colors duration-200"
              >
                <PlusIcon className="h-5 w-5 mr-2" />
                Nouveau Projet
              </Link>
            </div>
          </div>
        </div>

        {/* Error Message */}
        {error && (
          <ErrorMessage 
            message={error} 
            onClose={() => setError(null)}
            className="mb-6"
          />
        )}

        {/* Stats Cards */}
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
          <div className={`p-6 rounded-lg shadow-sm border ${isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'}`}>
            <div className="flex items-center">
              <CheckCircleIcon className="h-10 w-10 text-green-500" />
              <div className="ml-4">
                <p className={`text-sm font-medium ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
                  Tâches Terminées
                </p>
                <p className={`text-2xl font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}>
                  {dashboardData.stats.completedTasks}
                </p>
                <p className={`text-xs ${isDark ? 'text-gray-500' : 'text-gray-500'}`}>
                  {getCompletionPercentage()}% de completion
                </p>
              </div>
            </div>
          </div>

          <div className={`p-6 rounded-lg shadow-sm border ${isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'}`}>
            <div className="flex items-center">
              <ClockIcon className="h-10 w-10 text-blue-500" />
              <div className="ml-4">
                <p className={`text-sm font-medium ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
                  Tâches en Cours
                </p>
                <p className={`text-2xl font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}>
                  {dashboardData.stats.pendingTasks}
                </p>
                <p className={`text-xs ${isDark ? 'text-gray-500' : 'text-gray-500'}`}>
                  En attente de traitement
                </p>
              </div>
            </div>
          </div>

          <div className={`p-6 rounded-lg shadow-sm border ${isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'}`}>
            <div className="flex items-center">
              <ExclamationTriangleIcon className="h-10 w-10 text-red-500" />
              <div className="ml-4">
                <p className={`text-sm font-medium ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
                  Tâches en Retard
                </p>
                <p className={`text-2xl font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}>
                  {dashboardData.stats.overdueTasks}
                </p>
                <p className={`text-xs ${isDark ? 'text-gray-500' : 'text-gray-500'}`}>
                  Nécessitent attention
                </p>
              </div>
            </div>
          </div>

          <div className={`p-6 rounded-lg shadow-sm border ${isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'}`}>
            <div className="flex items-center">
              <FolderIcon className="h-10 w-10 text-purple-500" />
              <div className="ml-4">
                <p className={`text-sm font-medium ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
                  Projets Actifs
                </p>
                <p className={`text-2xl font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}>
                  {dashboardData.stats.activeProjects}
                </p>
                <p className={`text-xs ${isDark ? 'text-gray-500' : 'text-gray-500'}`}>
                  Sur {dashboardData.stats.totalProjects} projets
                </p>
              </div>
            </div>
          </div>
        </div>

        {/* Productivity and Time Tracking */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
          {/* Productivity Card */}
          <div className={`p-6 rounded-lg shadow-sm border ${isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'}`}>
            <div className="flex items-center justify-between mb-4">
              <h3 className={`text-lg font-semibold ${isDark ? 'text-white' : 'text-gray-900'}`}>
                Productivité
              </h3>
              <div className={`
                flex items-center text-sm
                ${productivity.trend === 'up' ? 'text-green-600' : productivity.trend === 'down' ? 'text-red-600' : 'text-gray-500'}
              `}>
                <TrendingUpIcon className={`h-4 w-4 mr-1 ${productivity.trend === 'down' ? 'rotate-180' : ''}`} />
                {productivity.percentage > 0 && `${productivity.percentage}%`}
                {productivity.trend === 'stable' ? 'Stable' : productivity.trend === 'up' ? 'En hausse' : 'En baisse'}
              </div>
            </div>
            
            <div className="space-y-4">
              <div>
                <div className="flex justify-between items-center mb-2">
                  <span className={`text-sm ${isDark ? 'text-gray-400' : 'text-gray-600'}`}>
                    Tâches cette semaine
                  </span>
                  <span className={`text-lg font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}>
                    {dashboardData.stats.tasksCompletedThisWeek}
                  </span>
                </div>
                <div className={`w-full bg-gray-200 rounded-full h-2 ${isDark ? 'bg-gray-700' : ''}`}>
                  <div 
                    className="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                    style={{ width: `${Math.min((dashboardData.stats.tasksCompletedThisWeek / 20) * 100, 100)}%` }}
                  />
                </div>
              </div>
              
              <div>
                <div className="flex justify-between items-center mb-2">
                  <span className={`text-sm ${isDark ? 'text-gray-400' : 'text-gray-600'}`}>
                    Temps tracké
                  </span>
                  <span className={`text-lg font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}>
                    {formatTime(dashboardData.stats.totalTimeTracked)}
                  </span>
                </div>
                <div className={`w-full bg-gray-200 rounded-full h-2 ${isDark ? 'bg-gray-700' : ''}`}>
                  <div 
                    className="bg-green-600 h-2 rounded-full transition-all duration-300" 
                    style={{ width: `${Math.min((dashboardData.stats.totalTimeTracked / 144000) * 100, 100)}%` }}
                  />
                </div>
              </div>
            </div>
          </div>

          {/* Quick Actions */}
          <div className={`p-6 rounded-lg shadow-sm border ${isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'}`}>
            <h3 className={`text-lg font-semibold mb-4 ${isDark ? 'text-white' : 'text-gray-900'}`}>
              Actions Rapides
            </h3>
            
            <div className="space-y-3">
              <Link
                to="/tasks"
                className={`
                  flex items-center justify-between p-3 rounded-lg border transition-colors
                  ${isDark 
                    ? 'border-gray-700 hover:bg-gray-700 text-gray-300' 
                    : 'border-gray-200 hover:bg-gray-50 text-gray-700'
                  }
                `}
              >
                <div className="flex items-center">
                  <CheckCircleIcon className="h-5 w-5 mr-3 text-blue-500" />
                  <span>Voir toutes les tâches</span>
                </div>
                <ArrowRightIcon className="h-4 w-4" />
              </Link>
              
              <Link
                to="/projects"
                className={`
                  flex items-center justify-between p-3 rounded-lg border transition-colors
                  ${isDark 
                    ? 'border-gray-700 hover:bg-gray-700 text-gray-300' 
                    : 'border-gray-200 hover:bg-gray-50 text-gray-700'
                  }
                `}
              >
                <div className="flex items-center">
                  <FolderIcon className="h-5 w-5 mr-3 text-purple-500" />
                  <span>Gérer les projets</span>
                </div>
                <ArrowRightIcon className="h-4 w-4" />
              </Link>
              
              <Link
                to="/time-tracking"
                className={`
                  flex items-center justify-between p-3 rounded-lg border transition-colors
                  ${isDark 
                    ? 'border-gray-700 hover:bg-gray-700 text-gray-300' 
                    : 'border-gray-200 hover:bg-gray-50 text-gray-700'
                  }
                `}
              >
                <div className="flex items-center">
                  <ClockIcon className="h-5 w-5 mr-3 text-green-500" />
                  <span>Suivi du temps</span>
                </div>
                <ArrowRightIcon className="h-4 w-4" />
              </Link>
              
              <Link
                to="/calendar"
                className={`
                  flex items-center justify-between p-3 rounded-lg border transition-colors
                  ${isDark 
                    ? 'border-gray-700 hover:bg-gray-700 text-gray-300' 
                    : 'border-gray-200 hover:bg-gray-50 text-gray-700'
                  }
                `}
              >
                <div className="flex items-center">
                  <CalendarIcon className="h-5 w-5 mr-3 text-orange-500" />
                  <span>Voir le calendrier</span>
                </div>
                <ArrowRightIcon className="h-4 w-4" />
              </Link>
            </div>
          </div>
        </div>

        {/* Recent Items and Upcoming Deadlines */}
        <div className="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8">
          {/* Recent Tasks */}
          <div className={`p-6 rounded-lg shadow-sm border ${isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'}`}>
            <div className="flex items-center justify-between mb-4">
              <h3 className={`text-lg font-semibold ${isDark ? 'text-white' : 'text-gray-900'}`}>
                Tâches Récentes
              </h3>
              <Link 
                to="/tasks"
                className={`text-sm hover:underline ${isDark ? 'text-blue-400' : 'text-blue-600'}`}
              >
                Voir tout
              </Link>
            </div>
            
            <div className="space-y-3">
              {recentTasks.slice(0, 5).map(task => (
                <div key={task.id} className={`flex items-center justify-between p-3 rounded-lg ${isDark ? 'bg-gray-750' : 'bg-gray-50'}`}>
                  <div className="flex items-center space-x-3">
                    <div className={`
                      w-2 h-2 rounded-full
                      ${task.status === 'completed' ? 'bg-green-500' : 
                        task.status === 'in_progress' ? 'bg-blue-500' : 
                        'bg-gray-400'}
                    `} />
                    <div>
                      <Link 
                        to={`/tasks/${task.id}`}
                        className={`font-medium hover:underline ${isDark ? 'text-white' : 'text-gray-900'}`}
                      >
                        {task.title}
                      </Link>
                      <p className={`text-xs ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
                        {task.project?.name || 'Aucun projet'}
                      </p>
                    </div>
                  </div>
                  <span className={`
                    text-xs px-2 py-1 rounded-full
                    ${getStatusColor(task.status)}
                  `}>
                    {task.status}
                  </span>
                </div>
              ))}
              
              {recentTasks.length === 0 && (
                <p className={`text-sm text-center py-4 ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
                  Aucune tâche récente
                </p>
              )}
            </div>
          </div>

          {/* Upcoming Deadlines */}
          <div className={`p-6 rounded-lg shadow-sm border ${isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'}`}>
            <div className="flex items-center justify-between mb-4">
              <h3 className={`text-lg font-semibold ${isDark ? 'text-white' : 'text-gray-900'}`}>
                Échéances Prochaines
              </h3>
              <Link 
                to="/calendar"
                className={`text-sm hover:underline ${isDark ? 'text-blue-400' : 'text-blue-600'}`}
              >
                Voir calendrier
              </Link>
            </div>
            
            <div className="space-y-3">
              {upcomingDeadlines.slice(0, 5).map(task => (
                <div key={task.id} className={`flex items-center justify-between p-3 rounded-lg ${isDark ? 'bg-gray-750' : 'bg-gray-50'}`}>
                  <div className="flex items-center space-x-3">
                    <CalendarIcon className={`h-4 w-4 ${isDark ? 'text-gray-400' : 'text-gray-500'}`} />
                    <div>
                      <Link 
                        to={`/tasks/${task.id}`}
                        className={`font-medium hover:underline ${isDark ? 'text-white' : 'text-gray-900'}`}
                      >
                        {task.title}
                      </Link>
                      <p className={`text-xs ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
                        {new Date(task.due_date).toLocaleDateString('fr-FR')}
                      </p>
                    </div>
                  </div>
                  <span className={`
                    text-xs px-2 py-1 rounded-full border
                    ${getPriorityColor(task.priority)}
                  `}>
                    {task.priority}
                  </span>
                </div>
              ))}
              
              {upcomingDeadlines.length === 0 && (
                <p className={`text-sm text-center py-4 ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
                  Aucune échéance prochaine
                </p>
              )}
            </div>
          </div>
        </div>

        {/* Recent Projects */}
        <div className={`p-6 rounded-lg shadow-sm border ${isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'}`}>
          <div className="flex items-center justify-between mb-4">
            <h3 className={`text-lg font-semibold ${isDark ? 'text-white' : 'text-gray-900'}`}>
              Projets Récents
            </h3>
            <Link 
              to="/projects"
              className={`text-sm hover:underline ${isDark ? 'text-blue-400' : 'text-blue-600'}`}
            >
              Voir tous les projets
            </Link>
          </div>
          
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {recentProjects.slice(0, 6).map(project => (
              <div key={project.id} className={`p-4 rounded-lg border ${isDark ? 'bg-gray-750 border-gray-600' : 'bg-gray-50 border-gray-200'}`}>
                <div className="flex items-center space-x-3 mb-3">
                  <div 
                    className="w-3 h-3 rounded-full"
                    style={{ backgroundColor: project.color || '#3B82F6' }}
                  />
                  <Link 
                    to={`/projects/${project.id}`}
                    className={`font-medium hover:underline ${isDark ? 'text-white' : 'text-gray-900'}`}
                  >
                    {project.name}
                  </Link>
                </div>
                
                <div className="flex items-center justify-between text-sm">
                  <div className={`flex items-center ${isDark ? 'text-gray-400' : 'text-gray-600'}`}>
                    <UserGroupIcon className="h-4 w-4 mr-1" />
                    <span>{project.members_count || 0}</span>
                  </div>
                  <span className={`${isDark ? 'text-gray-300' : 'text-gray-700'}`}>
                    {Math.round(project.completion_percentage || 0)}%
                  </span>
                </div>
                
                <div className={`w-full bg-gray-200 rounded-full h-2 mt-2 ${isDark ? 'bg-gray-600' : ''}`}>
                  <div 
                    className="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                    style={{ width: `${project.completion_percentage || 0}%` }}
                  />
                </div>
              </div>
            ))}
            
            {recentProjects.length === 0 && (
              <div className="col-span-full">
                <p className={`text-sm text-center py-8 ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
                  Aucun projet récent
                </p>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default Dashboard;