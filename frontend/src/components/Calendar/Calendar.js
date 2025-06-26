import React, { useState, useEffect } from 'react';
import {
  ChevronLeftIcon,
  ChevronRightIcon,
  CalendarIcon,
  ClockIcon,
  FlagIcon,
  EyeIcon,
  PlusIcon
} from '@heroicons/react/24/outline';
import { useAuth } from '../../contexts/AuthContext';
import { useTheme } from '../../contexts/ThemeContext';
import { Link } from 'react-router-dom';
import LoadingSpinner from '../Common/LoadingSpinner';
import ErrorMessage from '../Common/ErrorMessage';

const Calendar = () => {
  const { user } = useAuth();
  const { isDark } = useTheme();
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [currentDate, setCurrentDate] = useState(new Date());
  const [view, setView] = useState('month'); // month, week, day
  const [events, setEvents] = useState([]);
  const [selectedDate, setSelectedDate] = useState(null);
  const [showEventModal, setShowEventModal] = useState(false);

  useEffect(() => {
    fetchEvents();
  }, [currentDate, view]);

  const fetchEvents = async () => {
    try {
      setLoading(true);
      const token = localStorage.getItem('token');
      
      // Calculer les dates de début et fin selon la vue
      const { startDate, endDate } = getDateRange();
      
      const response = await fetch(
        `${process.env.REACT_APP_API_URL}/calendar/events?start_date=${startDate}&end_date=${endDate}`,
        {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Content-Type': 'application/json'
          }
        }
      );

      if (!response.ok) {
        throw new Error('Erreur lors du chargement des événements');
      }

      const data = await response.json();
      if (data.success) {
        setEvents(data.data || []);
      } else {
        throw new Error(data.message || 'Erreur inconnue');
      }
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const getDateRange = () => {
    const start = new Date(currentDate);
    const end = new Date(currentDate);
    
    switch (view) {
      case 'month':
        start.setDate(1);
        end.setMonth(end.getMonth() + 1);
        end.setDate(0);
        break;
      case 'week':
        const startOfWeek = start.getDate() - start.getDay();
        start.setDate(startOfWeek);
        end.setDate(startOfWeek + 6);
        break;
      case 'day':
        // start et end restent sur le même jour
        break;
    }
    
    return {
      startDate: start.toISOString().split('T')[0],
      endDate: end.toISOString().split('T')[0]
    };
  };

  const navigateDate = (direction) => {
    const newDate = new Date(currentDate);
    
    switch (view) {
      case 'month':
        newDate.setMonth(newDate.getMonth() + (direction === 'next' ? 1 : -1));
        break;
      case 'week':
        newDate.setDate(newDate.getDate() + (direction === 'next' ? 7 : -7));
        break;
      case 'day':
        newDate.setDate(newDate.getDate() + (direction === 'next' ? 1 : -1));
        break;
    }
    
    setCurrentDate(newDate);
  };

  const getMonthDays = () => {
    const year = currentDate.getFullYear();
    const month = currentDate.getMonth();
    const firstDay = new Date(year, month, 1);
    const lastDay = new Date(year, month + 1, 0);
    const startDate = new Date(firstDay);
    startDate.setDate(startDate.getDate() - firstDay.getDay());
    
    const days = [];
    const current = new Date(startDate);
    
    for (let i = 0; i < 42; i++) {
      days.push(new Date(current));
      current.setDate(current.getDate() + 1);
    }
    
    return days;
  };

  const getWeekDays = () => {
    const days = [];
    const startOfWeek = new Date(currentDate);
    startOfWeek.setDate(currentDate.getDate() - currentDate.getDay());
    
    for (let i = 0; i < 7; i++) {
      const day = new Date(startOfWeek);
      day.setDate(startOfWeek.getDate() + i);
      days.push(day);
    }
    
    return days;
  };

  const getEventsForDate = (date) => {
    const dateStr = date.toISOString().split('T')[0];
    return events.filter(event => {
      const eventDate = new Date(event.due_date || event.start_date).toISOString().split('T')[0];
      return eventDate === dateStr;
    });
  };

  const formatDate = (date, format = 'long') => {
    if (format === 'short') {
      return date.toLocaleDateString('fr-FR', { day: 'numeric' });
    }
    if (format === 'weekday') {
      return date.toLocaleDateString('fr-FR', { weekday: 'short' });
    }
    return date.toLocaleDateString('fr-FR', { 
      year: 'numeric', 
      month: 'long',
      day: 'numeric'
    });
  };

  const formatCurrentPeriod = () => {
    switch (view) {
      case 'month':
        return currentDate.toLocaleDateString('fr-FR', { 
          year: 'numeric', 
          month: 'long' 
        });
      case 'week':
        const startOfWeek = new Date(currentDate);
        startOfWeek.setDate(currentDate.getDate() - currentDate.getDay());
        const endOfWeek = new Date(startOfWeek);
        endOfWeek.setDate(startOfWeek.getDate() + 6);
        return `${formatDate(startOfWeek)} - ${formatDate(endOfWeek)}`;
      case 'day':
        return formatDate(currentDate);
      default:
        return '';
    }
  };

  const getEventTypeColor = (event) => {
    if (event.type === 'task') {
      switch (event.priority) {
        case 'urgent': return 'bg-red-500';
        case 'high': return 'bg-orange-500';
        case 'medium': return 'bg-blue-500';
        case 'low': return 'bg-green-500';
        default: return 'bg-gray-500';
      }
    }
    return 'bg-purple-500'; // Projects, meetings, etc.
  };

  const isToday = (date) => {
    const today = new Date();
    return date.toDateString() === today.toDateString();
  };

  const isCurrentMonth = (date) => {
    return date.getMonth() === currentDate.getMonth();
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
                Calendrier
              </h1>
              <p className={`mt-2 ${isDark ? 'text-gray-400' : 'text-gray-600'}`}>
                Visualisez vos tâches, échéances et événements
              </p>
            </div>
            
            <div className="flex items-center space-x-4">
              <Link
                to="/tasks/new"
                className="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200"
              >
                <PlusIcon className="h-5 w-5 mr-2" />
                Nouvelle Tâche
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

        {/* Controls */}
        <div className={`mb-6 p-4 rounded-lg shadow-sm border ${isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'}`}>
          <div className="flex flex-col sm:flex-row justify-between items-center gap-4">
            {/* Navigation */}
            <div className="flex items-center space-x-4">
              <button
                onClick={() => navigateDate('prev')}
                className={`
                  p-2 rounded-lg transition-colors
                  ${isDark ? 'hover:bg-gray-700 text-gray-400' : 'hover:bg-gray-100 text-gray-600'}
                `}
              >
                <ChevronLeftIcon className="h-5 w-5" />
              </button>
              
              <h2 className={`text-lg font-semibold min-w-0 ${isDark ? 'text-white' : 'text-gray-900'}`}>
                {formatCurrentPeriod()}
              </h2>
              
              <button
                onClick={() => navigateDate('next')}
                className={`
                  p-2 rounded-lg transition-colors
                  ${isDark ? 'hover:bg-gray-700 text-gray-400' : 'hover:bg-gray-100 text-gray-600'}
                `}
              >
                <ChevronRightIcon className="h-5 w-5" />
              </button>
              
              <button
                onClick={() => setCurrentDate(new Date())}
                className={`
                  px-3 py-1 text-sm rounded-lg border transition-colors
                  ${isDark 
                    ? 'border-gray-600 text-gray-300 hover:bg-gray-700' 
                    : 'border-gray-300 text-gray-700 hover:bg-gray-50'
                  }
                `}
              >
                Aujourd'hui
              </button>
            </div>

            {/* View Switch */}
            <div className={`flex rounded-lg border ${isDark ? 'border-gray-600' : 'border-gray-300'}`}>
              {['month', 'week', 'day'].map((viewType) => (
                <button
                  key={viewType}
                  onClick={() => setView(viewType)}
                  className={`
                    px-4 py-2 text-sm font-medium first:rounded-l-lg last:rounded-r-lg transition-colors
                    ${view === viewType
                      ? 'bg-blue-600 text-white'
                      : isDark 
                        ? 'text-gray-300 hover:bg-gray-700' 
                        : 'text-gray-700 hover:bg-gray-50'
                    }
                  `}
                >
                  {viewType === 'month' ? 'Mois' : viewType === 'week' ? 'Semaine' : 'Jour'}
                </button>
              ))}
            </div>
          </div>
        </div>

        {/* Calendar Grid */}
        <div className={`rounded-lg shadow-sm border ${isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'}`}>
          {view === 'month' && (
            <>
              {/* Week Headers */}
              <div className="grid grid-cols-7 border-b border-gray-200">
                {['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'].map(day => (
                  <div
                    key={day}
                    className={`p-4 text-center text-sm font-medium ${isDark ? 'text-gray-400 border-gray-700' : 'text-gray-500 border-gray-200'}`}
                  >
                    {day}
                  </div>
                ))}
              </div>

              {/* Month Days */}
              <div className="grid grid-cols-7">
                {getMonthDays().map((date, index) => {
                  const dayEvents = getEventsForDate(date);
                  const isCurrentDay = isToday(date);
                  const isOtherMonth = !isCurrentMonth(date);
                  
                  return (
                    <div
                      key={index}
                      className={`
                        min-h-32 p-2 border-r border-b transition-colors cursor-pointer
                        ${isDark ? 'border-gray-700 hover:bg-gray-700' : 'border-gray-200 hover:bg-gray-50'}
                        ${isOtherMonth ? (isDark ? 'bg-gray-900' : 'bg-gray-50') : ''}
                      `}
                      onClick={() => setSelectedDate(date)}
                    >
                      <div className={`
                        text-sm font-medium mb-1
                        ${isCurrentDay 
                          ? 'bg-blue-600 text-white w-6 h-6 rounded-full flex items-center justify-center'
                          : isOtherMonth 
                            ? (isDark ? 'text-gray-600' : 'text-gray-400')
                            : (isDark ? 'text-white' : 'text-gray-900')
                        }
                      `}>
                        {formatDate(date, 'short')}
                      </div>
                      
                      {/* Events */}
                      <div className="space-y-1">
                        {dayEvents.slice(0, 3).map((event, eventIndex) => (
                          <div
                            key={eventIndex}
                            className={`
                              text-xs p-1 rounded text-white truncate
                              ${getEventTypeColor(event)}
                            `}
                            title={event.title}
                          >
                            {event.title}
                          </div>
                        ))}
                        {dayEvents.length > 3 && (
                          <div className={`text-xs ${isDark ? 'text-gray-400' : 'text-gray-600'}`}>
                            +{dayEvents.length - 3} autres
                          </div>
                        )}
                      </div>
                    </div>
                  );
                })}
              </div>
            </>
          )}

          {view === 'week' && (
            <>
              {/* Week Headers */}
              <div className="grid grid-cols-8 border-b border-gray-200">
                <div className={`p-4 ${isDark ? 'border-gray-700' : 'border-gray-200'}`}></div>
                {getWeekDays().map((date, index) => (
                  <div
                    key={index}
                    className={`
                      p-4 text-center border-r
                      ${isDark ? 'border-gray-700' : 'border-gray-200'}
                    `}
                  >
                    <div className={`text-sm ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
                      {formatDate(date, 'weekday')}
                    </div>
                    <div className={`
                      text-lg font-semibold mt-1
                      ${isToday(date) 
                        ? 'bg-blue-600 text-white w-8 h-8 rounded-full flex items-center justify-center mx-auto'
                        : (isDark ? 'text-white' : 'text-gray-900')
                      }
                    `}>
                      {formatDate(date, 'short')}
                    </div>
                  </div>
                ))}
              </div>

              {/* Week Time Slots */}
              <div className="grid grid-cols-8">
                {Array.from({ length: 24 }, (_, hour) => (
                  <React.Fragment key={hour}>
                    <div className={`
                      p-2 text-right text-sm border-r border-b
                      ${isDark ? 'text-gray-400 border-gray-700 bg-gray-750' : 'text-gray-500 border-gray-200 bg-gray-50'}
                    `}>
                      {hour.toString().padStart(2, '0')}:00
                    </div>
                    {getWeekDays().map((date, dayIndex) => {
                      const dayEvents = getEventsForDate(date).filter(event => {
                        const eventHour = event.start_time ? new Date(`2000-01-01T${event.start_time}`).getHours() : 9;
                        return eventHour === hour;
                      });
                      
                      return (
                        <div
                          key={dayIndex}
                          className={`
                            min-h-16 p-1 border-r border-b transition-colors cursor-pointer
                            ${isDark ? 'border-gray-700 hover:bg-gray-700' : 'border-gray-200 hover:bg-gray-50'}
                          `}
                        >
                          {dayEvents.map((event, eventIndex) => (
                            <div
                              key={eventIndex}
                              className={`
                                text-xs p-1 rounded text-white truncate mb-1
                                ${getEventTypeColor(event)}
                              `}
                              title={event.title}
                            >
                              {event.title}
                            </div>
                          ))}
                        </div>
                      );
                    })}
                  </React.Fragment>
                ))}
              </div>
            </>
          )}

          {view === 'day' && (
            <div className="p-6">
              <div className="mb-4">
                <h3 className={`text-lg font-semibold ${isDark ? 'text-white' : 'text-gray-900'}`}>
                  {formatDate(currentDate)}
                </h3>
              </div>
              
              <div className="space-y-4">
                {Array.from({ length: 24 }, (_, hour) => {
                  const hourEvents = getEventsForDate(currentDate).filter(event => {
                    const eventHour = event.start_time ? new Date(`2000-01-01T${event.start_time}`).getHours() : 9;
                    return eventHour === hour;
                  });
                  
                  return (
                    <div key={hour} className="flex">
                      <div className={`
                        w-20 text-right pr-4 text-sm
                        ${isDark ? 'text-gray-400' : 'text-gray-500'}
                      `}>
                        {hour.toString().padStart(2, '0')}:00
                      </div>
                      <div className={`
                        flex-1 min-h-16 border-l pl-4
                        ${isDark ? 'border-gray-700' : 'border-gray-200'}
                      `}>
                        {hourEvents.map((event, eventIndex) => (
                          <div
                            key={eventIndex}
                            className={`
                              p-3 rounded-lg mb-2 border-l-4
                              ${isDark ? 'bg-gray-700' : 'bg-gray-50'}
                            `}
                            style={{ borderLeftColor: getEventTypeColor(event).replace('bg-', '#') }}
                          >
                            <div className={`font-medium ${isDark ? 'text-white' : 'text-gray-900'}`}>
                              {event.title}
                            </div>
                            {event.description && (
                              <div className={`text-sm mt-1 ${isDark ? 'text-gray-400' : 'text-gray-600'}`}>
                                {event.description}
                              </div>
                            )}
                            <div className={`flex items-center space-x-4 mt-2 text-xs ${isDark ? 'text-gray-500' : 'text-gray-500'}`}>
                              {event.type === 'task' && (
                                <>
                                  <span className="flex items-center">
                                    <FlagIcon className="h-3 w-3 mr-1" />
                                    {event.priority}
                                  </span>
                                  <span className="flex items-center">
                                    <ClockIcon className="h-3 w-3 mr-1" />
                                    {event.status}
                                  </span>
                                </>
                              )}
                            </div>
                          </div>
                        ))}
                      </div>
                    </div>
                  );
                })}
              </div>
            </div>
          )}
        </div>

        {/* Legend */}
        <div className={`mt-6 p-4 rounded-lg shadow-sm border ${isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'}`}>
          <h4 className={`text-sm font-medium mb-3 ${isDark ? 'text-white' : 'text-gray-900'}`}>
            Légende
          </h4>
          <div className="flex flex-wrap gap-4 text-sm">
            <div className="flex items-center">
              <div className="w-3 h-3 bg-red-500 rounded mr-2"></div>
              <span className={isDark ? 'text-gray-300' : 'text-gray-700'}>Urgent</span>
            </div>
            <div className="flex items-center">
              <div className="w-3 h-3 bg-orange-500 rounded mr-2"></div>
              <span className={isDark ? 'text-gray-300' : 'text-gray-700'}>Haute priorité</span>
            </div>
            <div className="flex items-center">
              <div className="w-3 h-3 bg-blue-500 rounded mr-2"></div>
              <span className={isDark ? 'text-gray-300' : 'text-gray-700'}>Priorité moyenne</span>
            </div>
            <div className="flex items-center">
              <div className="w-3 h-3 bg-green-500 rounded mr-2"></div>
              <span className={isDark ? 'text-gray-300' : 'text-gray-700'}>Basse priorité</span>
            </div>
            <div className="flex items-center">
              <div className="w-3 h-3 bg-purple-500 rounded mr-2"></div>
              <span className={isDark ? 'text-gray-300' : 'text-gray-700'}>Événements</span>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Calendar;