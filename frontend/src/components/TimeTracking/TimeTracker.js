import React, { useState, useEffect } from 'react';
import {
  PlayIcon,
  PauseIcon,
  StopIcon,
  ClockIcon,
  CalendarIcon,
  ChartBarIcon,
  DocumentTextIcon,
  PlusIcon
} from '@heroicons/react/24/outline';
import { useAuth } from '../../contexts/AuthContext';
import { useTheme } from '../../contexts/ThemeContext';
import LoadingSpinner from '../Common/LoadingSpinner';
import ErrorMessage from '../Common/ErrorMessage';

const TimeTracker = () => {
  const { user } = useAuth();
  const { isDark } = useTheme();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [activeTimer, setActiveTimer] = useState(null);
  const [timeEntries, setTimeEntries] = useState([]);
  const [tasks, setTasks] = useState([]);
  const [projects, setProjects] = useState([]);
  const [currentTime, setCurrentTime] = useState(0);
  const [timerInterval, setTimerInterval] = useState(null);
  const [showManualEntry, setShowManualEntry] = useState(false);
  const [filters, setFilters] = useState({
    startDate: new Date().toISOString().split('T')[0],
    endDate: new Date().toISOString().split('T')[0],
    projectId: '',
    taskId: ''
  });
  const [manualEntry, setManualEntry] = useState({
    task_id: '',
    project_id: '',
    description: '',
    duration: 0,
    hours: 0,
    minutes: 0,
    date: new Date().toISOString().split('T')[0]
  });

  useEffect(() => {
    fetchInitialData();
    return () => {
      if (timerInterval) {
        clearInterval(timerInterval);
      }
    };
  }, []);

  useEffect(() => {
    fetchTimeEntries();
  }, [filters]);

  const fetchInitialData = async () => {
    try {
      setLoading(true);
      const token = localStorage.getItem('token');
      
      // Récupérer les données en parallèle
      const [timerResponse, tasksResponse, projectsResponse] = await Promise.all([
        fetch(`${process.env.REACT_APP_API_URL}/time-tracking/active`, {
          headers: { 'Authorization': `Bearer ${token}` }
        }),
        fetch(`${process.env.REACT_APP_API_URL}/tasks?limit=100`, {
          headers: { 'Authorization': `Bearer ${token}` }
        }),
        fetch(`${process.env.REACT_APP_API_URL}/projects?limit=100`, {
          headers: { 'Authorization': `Bearer ${token}` }
        })
      ]);

      // Traiter le timer actif
      if (timerResponse.ok) {
        const timerData = await timerResponse.json();
        if (timerData.success && timerData.data) {
          setActiveTimer(timerData.data);
          startTimer(timerData.data);
        }
      }

      // Traiter les tâches
      if (tasksResponse.ok) {
        const tasksData = await tasksResponse.json();
        if (tasksData.success) {
          setTasks(tasksData.data.tasks || []);
        }
      }

      // Traiter les projets
      if (projectsResponse.ok) {
        const projectsData = await projectsResponse.json();
        if (projectsData.success) {
          setProjects(projectsData.data.projects || []);
        }
      }

      await fetchTimeEntries();
    } catch (err) {
      setError('Erreur lors du chargement des données');
    } finally {
      setLoading(false);
    }
  };

  const fetchTimeEntries = async () => {
    try {
      const token = localStorage.getItem('token');
      const queryParams = new URLSearchParams({
        ...filters,
        limit: 50
      });

      const response = await fetch(`${process.env.REACT_APP_API_URL}/time-tracking/entries?${queryParams}`, {
        headers: { 'Authorization': `Bearer ${token}` }
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success) {
          setTimeEntries(data.data.entries || []);
        }
      }
    } catch (err) {
      console.error('Erreur lors du chargement des entrées:', err);
    }
  };

  const startTimer = (timer) => {
    const startTime = new Date(timer.start_time).getTime();
    const currentTimestamp = Date.now();
    const elapsedSeconds = Math.floor((currentTimestamp - startTime) / 1000);
    setCurrentTime(elapsedSeconds);

    const interval = setInterval(() => {
      setCurrentTime(prev => prev + 1);
    }, 1000);
    setTimerInterval(interval);
  };

  const handleStartTimer = async (taskId) => {
    try {
      const token = localStorage.getItem('token');
      
      const response = await fetch(`${process.env.REACT_APP_API_URL}/time-tracking/start`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({ task_id: taskId })
      });

      const data = await response.json();
      if (data.success) {
        setActiveTimer(data.data);
        startTimer(data.data);
        setError(null);
      } else {
        throw new Error(data.message);
      }
    } catch (err) {
      setError(err.message);
    }
  };

  const handlePauseTimer = async () => {
    try {
      const token = localStorage.getItem('token');
      
      const response = await fetch(`${process.env.REACT_APP_API_URL}/time-tracking/pause`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      const data = await response.json();
      if (data.success) {
        setActiveTimer(null);
        setCurrentTime(0);
        if (timerInterval) {
          clearInterval(timerInterval);
          setTimerInterval(null);
        }
        await fetchTimeEntries();
      } else {
        throw new Error(data.message);
      }
    } catch (err) {
      setError(err.message);
    }
  };

  const handleStopTimer = async () => {
    try {
      const token = localStorage.getItem('token');
      
      const response = await fetch(`${process.env.REACT_APP_API_URL}/time-tracking/stop`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      const data = await response.json();
      if (data.success) {
        setActiveTimer(null);
        setCurrentTime(0);
        if (timerInterval) {
          clearInterval(timerInterval);
          setTimerInterval(null);
        }
        await fetchTimeEntries();
      } else {
        throw new Error(data.message);
      }
    } catch (err) {
      setError(err.message);
    }
  };

  const handleManualEntry = async (e) => {
    e.preventDefault();
    
    try {
      const duration = (manualEntry.hours * 3600) + (manualEntry.minutes * 60);
      if (duration <= 0) {
        setError('La durée doit être supérieure à 0');
        return;
      }

      const token = localStorage.getItem('token');
      
      const response = await fetch(`${process.env.REACT_APP_API_URL}/time-tracking/manual`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify({
          ...manualEntry,
          duration
        })
      });

      const data = await response.json();
      if (data.success) {
        setManualEntry({
          task_id: '',
          project_id: '',
          description: '',
          duration: 0,
          hours: 0,
          minutes: 0,
          date: new Date().toISOString().split('T')[0]
        });
        setShowManualEntry(false);
        await fetchTimeEntries();
        setError(null);
      } else {
        throw new Error(data.message);
      }
    } catch (err) {
      setError(err.message);
    }
  };

  const formatTime = (seconds) => {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;
    return `${hours.toString().padStart(2, '0')}:${minutes.toString().padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
  };

  const formatDuration = (seconds) => {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    if (hours > 0) {
      return `${hours}h ${minutes}m`;
    }
    return `${minutes}m`;
  };

  const getTotalTime = () => {
    return timeEntries.reduce((total, entry) => total + (entry.duration || 0), 0);
  };

  if (loading) return <LoadingSpinner />;

  return (
    <div className={`min-h-screen ${isDark ? 'bg-gray-900' : 'bg-gray-50'}`}>
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Header */}
        <div className="mb-8">
          <h1 className={`text-3xl font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}>
            Suivi du Temps
          </h1>
          <p className={`mt-2 ${isDark ? 'text-gray-400' : 'text-gray-600'}`}>
            Mesurez précisément le temps passé sur vos tâches et projets
          </p>
        </div>

        {/* Error Message */}
        {error && (
          <ErrorMessage 
            message={error} 
            onClose={() => setError(null)}
            className="mb-6"
          />
        )}

        {/* Active Timer */}
        <div className={`mb-8 p-6 rounded-lg shadow-sm border ${isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'}`}>
          <div className="flex flex-col lg:flex-row items-center justify-between gap-4">
            <div className="flex items-center space-x-4">
              <div className={`
                p-3 rounded-full
                ${activeTimer ? 'bg-green-100 text-green-600' : isDark ? 'bg-gray-700 text-gray-400' : 'bg-gray-100 text-gray-400'}
              `}>
                <ClockIcon className="h-8 w-8" />
              </div>
              <div>
                <h3 className={`text-lg font-medium ${isDark ? 'text-white' : 'text-gray-900'}`}>
                  {activeTimer ? 'Timer Actif' : 'Aucun timer actif'}
                </h3>
                {activeTimer && (
                  <p className={`text-sm ${isDark ? 'text-gray-400' : 'text-gray-600'}`}>
                    {activeTimer.task?.title || 'Tâche inconnue'}
                  </p>
                )}
              </div>
            </div>

            <div className="flex items-center space-x-4">
              {activeTimer && (
                <div className={`text-3xl font-mono font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}>
                  {formatTime(currentTime)}
                </div>
              )}
              
              <div className="flex items-center space-x-2">
                {!activeTimer ? (
                  <select
                    onChange={(e) => e.target.value && handleStartTimer(e.target.value)}
                    className={`
                      px-4 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                      ${isDark 
                        ? 'bg-gray-700 border-gray-600 text-white' 
                        : 'bg-white border-gray-300 text-gray-900'
                      }
                    `}
                  >
                    <option value="">Sélectionner une tâche</option>
                    {tasks.map(task => (
                      <option key={task.id} value={task.id}>
                        {task.title}
                      </option>
                    ))}
                  </select>
                ) : (
                  <>
                    <button
                      onClick={handlePauseTimer}
                      className="flex items-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white font-medium rounded-lg transition-colors"
                    >
                      <PauseIcon className="h-5 w-5 mr-2" />
                      Pause
                    </button>
                    <button
                      onClick={handleStopTimer}
                      className="flex items-center px-4 py-2 bg-red-600 hover:bg-red-700 text-white font-medium rounded-lg transition-colors"
                    >
                      <StopIcon className="h-5 w-5 mr-2" />
                      Stop
                    </button>
                  </>
                )}
              </div>
            </div>
          </div>
        </div>

        {/* Stats */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
          <div className={`p-6 rounded-lg shadow-sm border ${isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'}`}>
            <div className="flex items-center">
              <CalendarIcon className="h-8 w-8 text-blue-500" />
              <div className="ml-3">
                <p className={`text-sm font-medium ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
                  Aujourd'hui
                </p>
                <p className={`text-2xl font-semibold ${isDark ? 'text-white' : 'text-gray-900'}`}>
                  {formatDuration(getTotalTime())}
                </p>
              </div>
            </div>
          </div>

          <div className={`p-6 rounded-lg shadow-sm border ${isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'}`}>
            <div className="flex items-center">
              <ChartBarIcon className="h-8 w-8 text-green-500" />
              <div className="ml-3">
                <p className={`text-sm font-medium ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
                  Entrées
                </p>
                <p className={`text-2xl font-semibold ${isDark ? 'text-white' : 'text-gray-900'}`}>
                  {timeEntries.length}
                </p>
              </div>
            </div>
          </div>

          <div className={`p-6 rounded-lg shadow-sm border ${isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'}`}>
            <div className="flex items-center">
              <DocumentTextIcon className="h-8 w-8 text-purple-500" />
              <div className="ml-3">
                <p className={`text-sm font-medium ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
                  Tâches suivies
                </p>
                <p className={`text-2xl font-semibold ${isDark ? 'text-white' : 'text-gray-900'}`}>
                  {new Set(timeEntries.map(entry => entry.task_id)).size}
                </p>
              </div>
            </div>
          </div>
        </div>

        {/* Filters and Actions */}
        <div className={`mb-6 p-4 rounded-lg shadow-sm border ${isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'}`}>
          <div className="flex flex-col lg:flex-row gap-4 items-center justify-between">
            <div className="flex flex-col sm:flex-row gap-4">
              <input
                type="date"
                value={filters.startDate}
                onChange={(e) => setFilters(prev => ({ ...prev, startDate: e.target.value }))}
                className={`
                  px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                  ${isDark 
                    ? 'bg-gray-700 border-gray-600 text-white' 
                    : 'bg-white border-gray-300 text-gray-900'
                  }
                `}
              />
              <input
                type="date"
                value={filters.endDate}
                onChange={(e) => setFilters(prev => ({ ...prev, endDate: e.target.value }))}
                className={`
                  px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                  ${isDark 
                    ? 'bg-gray-700 border-gray-600 text-white' 
                    : 'bg-white border-gray-300 text-gray-900'
                  }
                `}
              />
              <select
                value={filters.projectId}
                onChange={(e) => setFilters(prev => ({ ...prev, projectId: e.target.value }))}
                className={`
                  px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                  ${isDark 
                    ? 'bg-gray-700 border-gray-600 text-white' 
                    : 'bg-white border-gray-300 text-gray-900'
                  }
                `}
              >
                <option value="">Tous les projets</option>
                {projects.map(project => (
                  <option key={project.id} value={project.id}>
                    {project.name}
                  </option>
                ))}
              </select>
            </div>

            <button
              onClick={() => setShowManualEntry(!showManualEntry)}
              className="flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
            >
              <PlusIcon className="h-5 w-5 mr-2" />
              Ajouter manuellement
            </button>
          </div>
        </div>

        {/* Manual Entry Form */}
        {showManualEntry && (
          <div className={`mb-6 p-6 rounded-lg shadow-sm border ${isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'}`}>
            <h3 className={`text-lg font-medium mb-4 ${isDark ? 'text-white' : 'text-gray-900'}`}>
              Ajouter une entrée manuelle
            </h3>
            
            <form onSubmit={handleManualEntry} className="space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className={`block text-sm font-medium mb-2 ${isDark ? 'text-gray-300' : 'text-gray-700'}`}>
                    Tâche *
                  </label>
                  <select
                    value={manualEntry.task_id}
                    onChange={(e) => setManualEntry(prev => ({ ...prev, task_id: e.target.value }))}
                    required
                    className={`
                      block w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                      ${isDark 
                        ? 'bg-gray-700 border-gray-600 text-white' 
                        : 'bg-white border-gray-300 text-gray-900'
                      }
                    `}
                  >
                    <option value="">Sélectionner une tâche</option>
                    {tasks.map(task => (
                      <option key={task.id} value={task.id}>
                        {task.title}
                      </option>
                    ))}
                  </select>
                </div>

                <div>
                  <label className={`block text-sm font-medium mb-2 ${isDark ? 'text-gray-300' : 'text-gray-700'}`}>
                    Date
                  </label>
                  <input
                    type="date"
                    value={manualEntry.date}
                    onChange={(e) => setManualEntry(prev => ({ ...prev, date: e.target.value }))}
                    className={`
                      block w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                      ${isDark 
                        ? 'bg-gray-700 border-gray-600 text-white' 
                        : 'bg-white border-gray-300 text-gray-900'
                      }
                    `}
                  />
                </div>
              </div>

              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className={`block text-sm font-medium mb-2 ${isDark ? 'text-gray-300' : 'text-gray-700'}`}>
                    Heures
                  </label>
                  <input
                    type="number"
                    min="0"
                    max="23"
                    value={manualEntry.hours}
                    onChange={(e) => setManualEntry(prev => ({ ...prev, hours: parseInt(e.target.value) || 0 }))}
                    className={`
                      block w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                      ${isDark 
                        ? 'bg-gray-700 border-gray-600 text-white' 
                        : 'bg-white border-gray-300 text-gray-900'
                      }
                    `}
                  />
                </div>

                <div>
                  <label className={`block text-sm font-medium mb-2 ${isDark ? 'text-gray-300' : 'text-gray-700'}`}>
                    Minutes
                  </label>
                  <input
                    type="number"
                    min="0"
                    max="59"
                    value={manualEntry.minutes}
                    onChange={(e) => setManualEntry(prev => ({ ...prev, minutes: parseInt(e.target.value) || 0 }))}
                    className={`
                      block w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                      ${isDark 
                        ? 'bg-gray-700 border-gray-600 text-white' 
                        : 'bg-white border-gray-300 text-gray-900'
                      }
                    `}
                  />
                </div>
              </div>

              <div>
                <label className={`block text-sm font-medium mb-2 ${isDark ? 'text-gray-300' : 'text-gray-700'}`}>
                  Description (optionnelle)
                </label>
                <textarea
                  value={manualEntry.description}
                  onChange={(e) => setManualEntry(prev => ({ ...prev, description: e.target.value }))}
                  rows={2}
                  placeholder="Description du travail effectué..."
                  className={`
                    block w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                    ${isDark 
                      ? 'bg-gray-700 border-gray-600 text-white placeholder-gray-400' 
                      : 'bg-white border-gray-300 text-gray-900 placeholder-gray-500'
                    }
                  `}
                />
              </div>

              <div className="flex justify-end space-x-3">
                <button
                  type="button"
                  onClick={() => setShowManualEntry(false)}
                  className={`
                    px-4 py-2 border rounded-lg font-medium transition-colors
                    ${isDark 
                      ? 'border-gray-600 text-gray-300 hover:bg-gray-700' 
                      : 'border-gray-300 text-gray-700 hover:bg-gray-50'
                    }
                  `}
                >
                  Annuler
                </button>
                <button
                  type="submit"
                  className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors"
                >
                  Ajouter
                </button>
              </div>
            </form>
          </div>
        )}

        {/* Time Entries */}
        <div className={`rounded-lg shadow-sm border ${isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'}`}>
          <div className="px-6 py-4 border-b border-gray-200">
            <h3 className={`text-lg font-medium ${isDark ? 'text-white' : 'text-gray-900'}`}>
              Entrées de temps
            </h3>
          </div>
          
          <div className="overflow-x-auto">
            <table className="min-w-full divide-y divide-gray-200">
              <thead className={isDark ? 'bg-gray-700' : 'bg-gray-50'}>
                <tr>
                  <th className={`px-6 py-3 text-left text-xs font-medium uppercase tracking-wider ${isDark ? 'text-gray-300' : 'text-gray-500'}`}>
                    Tâche
                  </th>
                  <th className={`px-6 py-3 text-left text-xs font-medium uppercase tracking-wider ${isDark ? 'text-gray-300' : 'text-gray-500'}`}>
                    Projet
                  </th>
                  <th className={`px-6 py-3 text-left text-xs font-medium uppercase tracking-wider ${isDark ? 'text-gray-300' : 'text-gray-500'}`}>
                    Date
                  </th>
                  <th className={`px-6 py-3 text-left text-xs font-medium uppercase tracking-wider ${isDark ? 'text-gray-300' : 'text-gray-500'}`}>
                    Durée
                  </th>
                  <th className={`px-6 py-3 text-left text-xs font-medium uppercase tracking-wider ${isDark ? 'text-gray-300' : 'text-gray-500'}`}>
                    Description
                  </th>
                </tr>
              </thead>
              <tbody className={`divide-y ${isDark ? 'divide-gray-700' : 'divide-gray-200'}`}>
                {timeEntries.map(entry => (
                  <tr key={entry.id} className={isDark ? 'bg-gray-800' : 'bg-white'}>
                    <td className={`px-6 py-4 whitespace-nowrap text-sm font-medium ${isDark ? 'text-white' : 'text-gray-900'}`}>
                      {entry.task?.title || 'Tâche supprimée'}
                    </td>
                    <td className={`px-6 py-4 whitespace-nowrap text-sm ${isDark ? 'text-gray-300' : 'text-gray-500'}`}>
                      {entry.project?.name || '-'}
                    </td>
                    <td className={`px-6 py-4 whitespace-nowrap text-sm ${isDark ? 'text-gray-300' : 'text-gray-500'}`}>
                      {new Date(entry.date || entry.created_at).toLocaleDateString('fr-FR')}
                    </td>
                    <td className={`px-6 py-4 whitespace-nowrap text-sm font-mono ${isDark ? 'text-white' : 'text-gray-900'}`}>
                      {formatDuration(entry.duration)}
                    </td>
                    <td className={`px-6 py-4 text-sm ${isDark ? 'text-gray-300' : 'text-gray-500'}`}>
                      {entry.description || '-'}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
            
            {timeEntries.length === 0 && (
              <div className="text-center py-12">
                <ClockIcon className={`mx-auto h-12 w-12 ${isDark ? 'text-gray-600' : 'text-gray-400'}`} />
                <h3 className={`mt-2 text-sm font-medium ${isDark ? 'text-gray-300' : 'text-gray-900'}`}>
                  Aucune entrée de temps
                </h3>
                <p className={`mt-1 text-sm ${isDark ? 'text-gray-500' : 'text-gray-500'}`}>
                  Commencez à suivre votre temps en démarrant un timer.
                </p>
              </div>
            )}
          </div>
        </div>
      </div>
    </div>
  );
};

export default TimeTracker;