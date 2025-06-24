import React, { useState, useEffect } from 'react';
import {
  ChevronLeftIcon,
  ChevronRightIcon,
  CalendarIcon,
  ViewColumnsIcon,
  ListBulletIcon,
  PlusIcon,
  EyeIcon,
  EyeSlashIcon
} from '@heroicons/react/24/outline';
import { useAuth } from '../../contexts/AuthContext';
import { useTheme } from '../../contexts/ThemeContext';
import LoadingSpinner from '../Common/LoadingSpinner';
import ErrorMessage from '../Common/ErrorMessage';

const TaskCalendar = () => {
  const { user } = useAuth();
  const { isDark } = useTheme();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [currentDate, setCurrentDate] = useState(new Date());
  const [view, setView] = useState('month'); // month, week, day
  const [tasks, setTasks] = useState([]);
  const [projects, setProjects] = useState([]);
  const [filters, setFilters] = useState({
    showCompleted: false,
    projectIds: [],
    priorities: ['low', 'medium', 'high', 'urgent']
  });

  const months = [
    'Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin',
    'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'
  ];

  const weekDays = ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'];

  useEffect(() => {
    fetchData();
  }, [currentDate, filters]);

  const fetchData = async () => {
    try {
      setLoading(true);
      const token = localStorage.getItem('token');
      
      // Calculer la plage de dates à récupérer
      const startDate = getStartDate();
      const endDate = getEndDate();
      
      // Construire les paramètres de requête
      const queryParams = new URLSearchParams({
        start_date: startDate.toISOString().split('T')[0],
        end_date: endDate.toISOString().split('T')[0],
        include_completed: filters.showCompleted,
        ...(filters.projectIds.length > 0 && { project_ids: filters.projectIds.join(',') }),
        ...(filters.priorities.length > 0 && { priorities: filters.priorities.join(',') })
      });

      const [tasksResponse, projectsResponse] = await Promise.all([
        fetch(`${process.env.REACT_APP_API_URL}/tasks/calendar?${queryParams}`, {
          headers: { 'Authorization': `Bearer ${token}` }
        }),
        fetch(`${process.env.REACT_APP_API_URL}/projects?limit=100`, {
          headers: { 'Authorization': `Bearer ${token}` }
        })
      ]);

      if (tasksResponse.ok) {
        const tasksData = await tasksResponse.json();
        if (tasksData.success) {
          setTasks(tasksData.data || []);
        }
      }

      if (projectsResponse.ok) {
        const projectsData = await projectsResponse.json();
        if (projectsData.success) {
          setProjects(projectsData.data.projects || []);
        }
      }

    } catch (err) {
      setError('Erreur lors du chargement du calendrier');
    } finally {
      setLoading(false);
    }
  };

  const getStartDate = () => {
    if (view === 'month') {
      const start = new Date(currentDate.getFullYear(), currentDate.getMonth(), 1);
      const dayOfWeek = start.getDay();
      start.setDate(start.getDate() - dayOfWeek);
      return start;
    } else if (view === 'week') {
      const start = new Date(currentDate);
      const dayOfWeek = start.getDay();
      start.setDate(start.getDate() - dayOfWeek);
      return start;
    } else {
      return new Date(currentDate);
    }
  };

  const getEndDate = () => {
    if (view === 'month') {
      const end = new Date(currentDate.getFullYear(), currentDate.getMonth() + 1, 0);
      const dayOfWeek = end.getDay();
      end.setDate(end.getDate() + (6 - dayOfWeek));
      return end;
    } else if (view === 'week') {
      const end = new Date(currentDate);
      const dayOfWeek = end.getDay();
      end.setDate(end.getDate() + (6 - dayOfWeek));
      return end;
    } else {
      return new Date(currentDate);
    }
  };

  const getDaysInMonth = () => {
    const startDate = getStartDate();
    const endDate = getEndDate();
    const days = [];
    
    for (let date = new Date(startDate); date <= endDate; date.setDate(date.getDate() + 1)) {
      days.push(new Date(date));
    }
    
    return days;
  };

  const getTasksForDate = (date) => {
    const dateStr = date.toISOString().split('T')[0];
    return tasks.filter(task => {
      const taskDate = task.due_date ? task.due_date.split('T')[0] : null;
      return taskDate === dateStr;
    });
  };

  const getPriorityColor = (priority) => {
    const colors = {
      low: 'bg-green-500',
      medium: 'bg-yellow-500',
      high: 'bg-orange-500',
      urgent: 'bg-red-500'
    };
    return colors[priority] || 'bg-gray-500';
  };

  const navigateDate = (direction) => {
    const newDate = new Date(currentDate);
    
    if (view === 'month') {
      newDate.setMonth(newDate.getMonth() + direction);
    } else if (view === 'week') {
      newDate.setDate(newDate.getDate() + (direction * 7));
    } else {
      newDate.setDate(newDate.getDate() + direction);
    }
    
    setCurrentDate(newDate);
  };

  const isToday = (date) => {
    const today = new Date();
    return date.toDateString() === today.toDateString();
  };

  const isCurrentMonth = (date) => {
    return date.getMonth() === currentDate.getMonth();
  };

  const toggleProjectFilter = (projectId) => {
    setFilters(prev => ({
      ...prev,
      projectIds: prev.projectIds.includes(projectId)
        ? prev.projectIds.filter(id => id !== projectId)
        : [...prev.projectIds, projectId]
    }));
  };

  const getDateFormat = () => {
    if (view === 'month') {
      return `${months[currentDate.getMonth()]} ${currentDate.getFullYear()}`;
    } else if (view === 'week') {
      const startDate = getStartDate();
      const endDate = getEndDate();
      return `${startDate.getDate()} ${months[startDate.getMonth()]} - ${endDate.getDate()} ${months[endDate.getMonth()]} ${endDate.getFullYear()}`;
    } else {
      return `${currentDate.getDate()} ${months[currentDate.getMonth()]} ${currentDate.getFullYear()}`;
    }
  };

  if (loading) return <LoadingSpinner />;

  return (
    <div className={`min-h-screen ${isDark ? 'bg-gray-900' : 'bg-gray-50'}`}>
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Header */}
        <div className="mb-8">
          <div className="flex flex-col lg:flex-row justify-between items-start lg:items-center gap-4">
            <div>
              <h1 className={`text-3xl font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}>
                Calendrier des Tâches
              </h1>
              <p className={`mt-2 ${isDark ? 'text-gray-400' : 'text-gray-600'}`}>
                Visualisez vos tâches et échéances dans une vue calendrier
              </p>
            </div>

            {/* View Controls */}
            <div className="flex items-center space-x-4">
              <div className={`flex rounded-lg border ${isDark ? 'border-gray-600' : 'border-gray-300'}`}>
                <button
                  onClick={() => setView('month')}
                  className={`
                    px-4 py-2 text-sm font-medium rounded-l-lg transition-colors
                    ${view === 'month'
                      ? 'bg-blue-600 text-white'
                      : isDark ? 'text-gray-300 hover:bg-gray-700' : 'text-gray-700 hover:bg-gray-50'
                    }
                  `}
                >
                  Mois
                </button>
                <button
                  onClick={() => setView('week')}
                  className={`
                    px-4 py-2 text-sm font-medium border-l transition-colors
                    ${view === 'week'
                      ? 'bg-blue-600 text-white border-blue-600'
                      : isDark ? 'text-gray-300 hover:bg-gray-700 border-gray-600' : 'text-gray-700 hover:bg-gray-50 border-gray-300'
                    }
                  `}
                >
                  Semaine
                </button>
                <button
                  onClick={() => setView('day')}
                  className={`
                    px-4 py-2 text-sm font-medium rounded-r-lg border-l transition-colors
                    ${view === 'day'
                      ? 'bg-blue-600 text-white border-blue-600'
                      : isDark ? 'text-gray-300 hover:bg-gray-700 border-gray-600' : 'text-gray-700 hover:bg-gray-50 border-gray-300'
                    }
                  `}
                >
                  Jour
                </button>
              </div>
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

        {/* Filters */}
        <div className={`mb-6 p-4 rounded-lg shadow-sm border ${isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'}`}>
          <div className="flex flex-col lg:flex-row gap-4 items-start lg:items-center justify-between">
            <div className="flex flex-wrap gap-4 items-center">
              {/* Show Completed */}
              <label className="flex items-center">
                <input
                  type="checkbox"
                  checked={filters.showCompleted}
                  onChange={(e) => setFilters(prev => ({ ...prev, showCompleted: e.target.checked }))}
                  className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                />
                <span className={`ml-2 text-sm ${isDark ? 'text-gray-300' : 'text-gray-700'}`}>
                  Afficher les tâches terminées
                </span>
              </label>

              {/* Project Filters */}
              <div className="flex flex-wrap gap-2">
                {projects.map(project => (
                  <button
                    key={project.id}
                    onClick={() => toggleProjectFilter(project.id)}
                    className={`
                      inline-flex items-center px-3 py-1 rounded-full text-xs font-medium transition-colors
                      ${filters.projectIds.includes(project.id)
                        ? 'bg-blue-100 text-blue-800 border border-blue-200'
                        : isDark ? 'bg-gray-700 text-gray-300 border border-gray-600' : 'bg-gray-100 text-gray-800 border border-gray-200'
                      }
                    `}
                  >
                    <div 
                      className="w-2 h-2 rounded-full mr-2"
                      style={{ backgroundColor: project.color || '#3B82F6' }}
                    />
                    {project.name}
                    {filters.projectIds.includes(project.id) ? (
                      <EyeIcon className="h-3 w-3 ml-1" />
                    ) : (
                      <EyeSlashIcon className="h-3 w-3 ml-1" />
                    )}
                  </button>
                ))}
              </div>
            </div>

            <div className={`text-sm ${isDark ? 'text-gray-400' : 'text-gray-600'}`}>
              {tasks.length} tâche{tasks.length !== 1 ? 's' : ''} trouvée{tasks.length !== 1 ? 's' : ''}
            </div>
          </div>
        </div>

        {/* Calendar Navigation */}
        <div className={`mb-6 p-4 rounded-lg shadow-sm border ${isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'}`}>
          <div className="flex items-center justify-between">
            <button
              onClick={() => navigateDate(-1)}
              className={`
                p-2 rounded-lg hover:bg-gray-100 transition-colors
                ${isDark ? 'hover:bg-gray-700 text-gray-400' : 'text-gray-500'}
              `}
            >
              <ChevronLeftIcon className="h-5 w-5" />
            </button>

            <h2 className={`text-xl font-semibold ${isDark ? 'text-white' : 'text-gray-900'}`}>
              {getDateFormat()}
            </h2>

            <button
              onClick={() => navigateDate(1)}
              className={`
                p-2 rounded-lg hover:bg-gray-100 transition-colors
                ${isDark ? 'hover:bg-gray-700 text-gray-400' : 'text-gray-500'}
              `}
            >
              <ChevronRightIcon className="h-5 w-5" />
            </button>
          </div>
        </div>

        {/* Calendar Grid */}
        {view === 'month' && (
          <div className={`rounded-lg shadow-sm border overflow-hidden ${isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'}`}>
            {/* Week Days Header */}
            <div className={`grid grid-cols-7 ${isDark ? 'bg-gray-700' : 'bg-gray-50'}`}>
              {weekDays.map(day => (
                <div key={day} className={`py-3 text-center text-sm font-medium ${isDark ? 'text-gray-300' : 'text-gray-700'}`}>
                  {day}
                </div>
              ))}
            </div>

            {/* Calendar Days */}
            <div className="grid grid-cols-7 divide-y divide-x divide-gray-200">
              {getDaysInMonth().map((date, index) => {
                const dayTasks = getTasksForDate(date);
                const isCurrentMonthDay = isCurrentMonth(date);
                const isTodayDate = isToday(date);

                return (
                  <div
                    key={index}
                    className={`
                      min-h-32 p-2 relative
                      ${!isCurrentMonthDay ? (isDark ? 'bg-gray-700 text-gray-500' : 'bg-gray-50 text-gray-400') : ''}
                      ${isTodayDate ? (isDark ? 'bg-blue-900/20' : 'bg-blue-50') : ''}
                    `}
                  >
                    {/* Date Number */}
                    <div className={`
                      text-sm font-medium mb-1
                      ${isTodayDate ? 'text-blue-600' : isDark ? 'text-white' : 'text-gray-900'}
                    `}>
                      {date.getDate()}
                    </div>

                    {/* Tasks */}
                    <div className="space-y-1">
                      {dayTasks.slice(0, 3).map(task => (
                        <div
                          key={task.id}
                          className={`
                            text-xs p-1 rounded truncate cursor-pointer transition-colors
                            ${task.status === 'completed' 
                              ? (isDark ? 'bg-green-900/40 text-green-200' : 'bg-green-100 text-green-800')
                              : (isDark ? 'bg-gray-700 text-gray-200 hover:bg-gray-600' : 'bg-gray-100 text-gray-800 hover:bg-gray-200')
                            }
                          `}
                          title={task.title}
                        >
                          <div className="flex items-center space-x-1">
                            <div className={`w-2 h-2 rounded-full ${getPriorityColor(task.priority)}`} />
                            <span className="truncate">{task.title}</span>
                          </div>
                        </div>
                      ))}
                      
                      {dayTasks.length > 3 && (
                        <div className={`text-xs ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
                          +{dayTasks.length - 3} autres
                        </div>
                      )}
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        )}

        {/* Week View */}
        {view === 'week' && (
          <div className={`rounded-lg shadow-sm border overflow-hidden ${isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'}`}>
            <div className="grid grid-cols-7 divide-x divide-gray-200">
              {getDaysInMonth().slice(0, 7).map((date, index) => {
                const dayTasks = getTasksForDate(date);
                const isTodayDate = isToday(date);

                return (
                  <div key={index} className={`min-h-96 ${isTodayDate ? (isDark ? 'bg-blue-900/20' : 'bg-blue-50') : ''}`}>
                    {/* Header */}
                    <div className={`p-3 border-b ${isDark ? 'border-gray-700' : 'border-gray-200'}`}>
                      <div className={`text-sm font-medium ${isTodayDate ? 'text-blue-600' : isDark ? 'text-white' : 'text-gray-900'}`}>
                        {weekDays[date.getDay()]}
                      </div>
                      <div className={`text-lg font-semibold ${isTodayDate ? 'text-blue-600' : isDark ? 'text-white' : 'text-gray-900'}`}>
                        {date.getDate()}
                      </div>
                    </div>

                    {/* Tasks */}
                    <div className="p-3 space-y-2">
                      {dayTasks.map(task => (
                        <div
                          key={task.id}
                          className={`
                            text-sm p-2 rounded border-l-4 cursor-pointer transition-colors
                            ${task.status === 'completed' 
                              ? (isDark ? 'bg-green-900/40 text-green-200 border-green-500' : 'bg-green-50 text-green-800 border-green-500')
                              : (isDark ? 'bg-gray-700 text-gray-200 hover:bg-gray-600' : 'bg-gray-50 text-gray-800 hover:bg-gray-100')
                            }
                          `}
                          style={{ borderLeftColor: task.status !== 'completed' ? getPriorityColor(task.priority).replace('bg-', '#') : undefined }}
                        >
                          <div className="font-medium truncate">{task.title}</div>
                          {task.project && (
                            <div className={`text-xs mt-1 ${isDark ? 'text-gray-400' : 'text-gray-600'}`}>
                              {task.project.name}
                            </div>
                          )}
                        </div>
                      ))}
                    </div>
                  </div>
                );
              })}
            </div>
          </div>
        )}

        {/* Day View */}
        {view === 'day' && (
          <div className={`rounded-lg shadow-sm border ${isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'}`}>
            <div className="p-6">
              <h3 className={`text-lg font-medium mb-4 ${isDark ? 'text-white' : 'text-gray-900'}`}>
                Tâches du {currentDate.getDate()} {months[currentDate.getMonth()]}
              </h3>
              
              {getTasksForDate(currentDate).length === 0 ? (
                <div className="text-center py-8">
                  <CalendarIcon className={`mx-auto h-12 w-12 ${isDark ? 'text-gray-600' : 'text-gray-400'}`} />
                  <h3 className={`mt-2 text-sm font-medium ${isDark ? 'text-gray-300' : 'text-gray-900'}`}>
                    Aucune tâche prévue
                  </h3>
                  <p className={`mt-1 text-sm ${isDark ? 'text-gray-500' : 'text-gray-500'}`}>
                    Profitez de cette journée libre ou ajoutez de nouvelles tâches.
                  </p>
                </div>
              ) : (
                <div className="space-y-3">
                  {getTasksForDate(currentDate).map(task => (
                    <div
                      key={task.id}
                      className={`
                        p-4 rounded-lg border-l-4 cursor-pointer transition-colors
                        ${task.status === 'completed' 
                          ? (isDark ? 'bg-green-900/40 text-green-200 border-green-500' : 'bg-green-50 text-green-800 border-green-500')
                          : (isDark ? 'bg-gray-700 text-gray-200 hover:bg-gray-600' : 'bg-gray-50 text-gray-800 hover:bg-gray-100')
                        }
                      `}
                      style={{ borderLeftColor: task.status !== 'completed' ? getPriorityColor(task.priority).replace('bg-', '#') : undefined }}
                    >
                      <div className="flex items-center justify-between">
                        <div>
                          <h4 className="font-medium">{task.title}</h4>
                          {task.description && (
                            <p className={`mt-1 text-sm ${isDark ? 'text-gray-400' : 'text-gray-600'}`}>
                              {task.description}
                            </p>
                          )}
                          <div className="flex items-center space-x-4 mt-2">
                            {task.project && (
                              <span className={`text-xs ${isDark ? 'text-gray-400' : 'text-gray-600'}`}>
                                📁 {task.project.name}
                              </span>
                            )}
                            <span className={`text-xs ${isDark ? 'text-gray-400' : 'text-gray-600'}`}>
                              {task.priority === 'urgent' ? '🔴' : task.priority === 'high' ? '🟠' : task.priority === 'medium' ? '🟡' : '🟢'} {task.priority}
                            </span>
                          </div>
                        </div>
                        
                        <div className={`
                          px-3 py-1 rounded-full text-xs font-medium
                          ${task.status === 'completed' 
                            ? (isDark ? 'bg-green-900 text-green-200' : 'bg-green-100 text-green-800')
                            : task.status === 'in_progress'
                            ? (isDark ? 'bg-blue-900 text-blue-200' : 'bg-blue-100 text-blue-800')
                            : (isDark ? 'bg-gray-700 text-gray-200' : 'bg-gray-100 text-gray-800')
                          }
                        `}>
                          {task.status === 'completed' ? 'Terminé' : 
                           task.status === 'in_progress' ? 'En cours' : 'À faire'}
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default TaskCalendar;