import React, { useState, useEffect } from 'react';
import { useParams, useNavigate, Link } from 'react-router-dom';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';
import {
  PencilIcon,
  TrashIcon,
  CalendarIcon,
  ClockIcon,
  UserIcon,
  TagIcon,
  FlagIcon
} from '@heroicons/react/24/outline';
import taskService from '../../services/taskService';

const TaskDetail = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [task, setTask] = useState(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    fetchTask();
  }, [id]);

  const fetchTask = async () => {
    try {
      setLoading(true);
      const response = await taskService.getTaskById(id);
      if (response.success) {
        setTask(response.data);
        setError(null);
      } else {
        setError(response.message);
      }
    } catch (err) {
      setError('Erreur lors du chargement de la tâche');
      console.error('Error fetching task:', err);
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async () => {
    if (window.confirm('Êtes-vous sûr de vouloir supprimer cette tâche ?')) {
      try {
        const response = await taskService.deleteTask(id);
        if (response.success) {
          navigate('/tasks', { replace: true });
        } else {
          setError(response.message);
        }
      } catch (err) {
        setError('Erreur lors de la suppression de la tâche');
        console.error('Error deleting task:', err);
      }
    }
  };

  const getPriorityColor = (priority) => {
    const colors = {
      low: 'text-green-600 bg-green-100',
      medium: 'text-yellow-600 bg-yellow-100',
      high: 'text-orange-600 bg-orange-100',
      urgent: 'text-red-600 bg-red-100'
    };
    return colors[priority] || colors.medium;
  };

  const getStatusColor = (status) => {
    const colors = {
      pending: 'text-gray-600 bg-gray-100',
      in_progress: 'text-blue-600 bg-blue-100',
      completed: 'text-green-600 bg-green-100',
      archived: 'text-purple-600 bg-purple-100',
      cancelled: 'text-red-600 bg-red-100'
    };
    return colors[status] || colors.pending;
  };

  const getStatusText = (status) => {
    const statusText = {
      pending: 'En attente',
      in_progress: 'En cours',
      completed: 'Terminée',
      archived: 'Archivée',
      cancelled: 'Annulée'
    };
    return statusText[status] || status;
  };

  const getPriorityText = (priority) => {
    const priorityText = {
      low: 'Basse',
      medium: 'Moyenne',
      high: 'Haute',
      urgent: 'Urgente'
    };
    return priorityText[priority] || priority;
  };

  if (loading) {
    return (
      <div className="flex justify-center items-center min-h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="bg-red-50 border border-red-200 rounded-md p-4">
        <p className="text-red-800">{error}</p>
        <button 
          onClick={() => navigate('/tasks')}
          className="mt-2 text-sm text-red-600 hover:text-red-800 underline"
        >
          Retour à la liste des tâches
        </button>
      </div>
    );
  }

  if (!task) {
    return (
      <div className="text-center py-8">
        <p className="text-gray-500">Tâche non trouvée</p>
        <Link 
          to="/tasks" 
          className="mt-2 inline-block text-blue-600 hover:text-blue-800 underline"
        >
          Retour à la liste des tâches
        </Link>
      </div>
    );
  }

  return (
    <div className="max-w-4xl mx-auto space-y-6">
      {/* Header */}
      <div className="bg-white shadow rounded-lg p-6">
        <div className="flex justify-between items-start mb-4">
          <div className="flex-1">
            <h1 className="text-2xl font-bold text-gray-900 mb-2">{task.title}</h1>
            <div className="flex flex-wrap gap-2 mb-4">
              <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(task.status)}`}>
                {getStatusText(task.status)}
              </span>
              <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getPriorityColor(task.priority)}`}>
                <FlagIcon className="w-3 h-3 mr-1" />
                {getPriorityText(task.priority)}
              </span>
            </div>
          </div>
          
          <div className="flex space-x-2 ml-4">
            <Link
              to={`/tasks/${id}/edit`}
              className="inline-flex items-center px-3 py-2 border border-gray-300 shadow-sm text-sm leading-4 font-medium rounded-md text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500"
            >
              <PencilIcon className="w-4 h-4 mr-1" />
              Modifier
            </Link>
            <button
              onClick={handleDelete}
              className="inline-flex items-center px-3 py-2 border border-red-300 shadow-sm text-sm leading-4 font-medium rounded-md text-red-700 bg-white hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500"
            >
              <TrashIcon className="w-4 h-4 mr-1" />
              Supprimer
            </button>
          </div>
        </div>

        {/* Progress */}
        {task.progress !== undefined && task.progress > 0 && (
          <div className="mb-4">
            <div className="flex justify-between text-sm text-gray-600 mb-1">
              <span>Progression</span>
              <span>{task.progress}%</span>
            </div>
            <div className="w-full bg-gray-200 rounded-full h-2">
              <div 
                className="bg-blue-600 h-2 rounded-full transition-all duration-300" 
                style={{ width: `${task.progress}%` }}
              ></div>
            </div>
          </div>
        )}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        {/* Main Content */}
        <div className="lg:col-span-2 space-y-6">
          {/* Description */}
          {task.description && (
            <div className="bg-white shadow rounded-lg p-6">
              <h2 className="text-lg font-medium text-gray-900 mb-3">Description</h2>
              <div className="prose max-w-none">
                <p className="text-gray-700 whitespace-pre-wrap">{task.description}</p>
              </div>
            </div>
          )}

          {/* Comments Section */}
          <div className="bg-white shadow rounded-lg p-6">
            <h2 className="text-lg font-medium text-gray-900 mb-3">Commentaires</h2>
            <p className="text-gray-500 text-sm">Fonctionnalité de commentaires à venir...</p>
          </div>
        </div>

        {/* Sidebar */}
        <div className="space-y-6">
          {/* Task Info */}
          <div className="bg-white shadow rounded-lg p-6">
            <h2 className="text-lg font-medium text-gray-900 mb-4">Informations</h2>
            <dl className="space-y-3">
              {/* Assignee */}
              {task.assignee_name && (
                <div>
                  <dt className="text-sm font-medium text-gray-500 flex items-center">
                    <UserIcon className="w-4 h-4 mr-1" />
                    Assigné à
                  </dt>
                  <dd className="text-sm text-gray-900 mt-1">{task.assignee_name}</dd>
                </div>
              )}
              
              {/* Creator */}
              {task.creator_name && (
                <div>
                  <dt className="text-sm font-medium text-gray-500 flex items-center">
                    <UserIcon className="w-4 h-4 mr-1" />
                    Créé par
                  </dt>
                  <dd className="text-sm text-gray-900 mt-1">{task.creator_name}</dd>
                </div>
              )}
              
              {/* Due Date */}
              {task.due_date && (
                <div>
                  <dt className="text-sm font-medium text-gray-500 flex items-center">
                    <CalendarIcon className="w-4 h-4 mr-1" />
                    Date d'échéance
                  </dt>
                  <dd className="text-sm text-gray-900 mt-1">
                    {format(new Date(task.due_date), 'PPP', { locale: fr })}
                  </dd>
                </div>
              )}
              
              {/* Start Date */}
              {task.start_date && (
                <div>
                  <dt className="text-sm font-medium text-gray-500 flex items-center">
                    <CalendarIcon className="w-4 h-4 mr-1" />
                    Date de début
                  </dt>
                  <dd className="text-sm text-gray-900 mt-1">
                    {format(new Date(task.start_date), 'PPP', { locale: fr })}
                  </dd>
                </div>
              )}
              
              {/* Estimated Hours */}
              {task.estimated_hours && (
                <div>
                  <dt className="text-sm font-medium text-gray-500 flex items-center">
                    <ClockIcon className="w-4 h-4 mr-1" />
                    Temps estimé
                  </dt>
                  <dd className="text-sm text-gray-900 mt-1">{task.estimated_hours}h</dd>
                </div>
              )}
              
              {/* Actual Hours */}
              {task.actual_hours && task.actual_hours > 0 && (
                <div>
                  <dt className="text-sm font-medium text-gray-500 flex items-center">
                    <ClockIcon className="w-4 h-4 mr-1" />
                    Temps réel
                  </dt>
                  <dd className="text-sm text-gray-900 mt-1">{task.actual_hours}h</dd>
                </div>
              )}
              
              {/* Created */}
              <div>
                <dt className="text-sm font-medium text-gray-500">Créé le</dt>
                <dd className="text-sm text-gray-900 mt-1">
                  {format(new Date(task.created_at), 'PPP à p', { locale: fr })}
                </dd>
              </div>
              
              {/* Updated */}
              {task.updated_at !== task.created_at && (
                <div>
                  <dt className="text-sm font-medium text-gray-500">Modifié le</dt>
                  <dd className="text-sm text-gray-900 mt-1">
                    {format(new Date(task.updated_at), 'PPP à p', { locale: fr })}
                  </dd>
                </div>
              )}
            </dl>
          </div>

          {/* Tags */}
          {task.tags && task.tags.length > 0 && (
            <div className="bg-white shadow rounded-lg p-6">
              <h2 className="text-lg font-medium text-gray-900 mb-3 flex items-center">
                <TagIcon className="w-5 h-5 mr-1" />
                Tags
              </h2>
              <div className="flex flex-wrap gap-2">
                {task.tags.map((tag, index) => (
                  <span
                    key={index}
                    className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-gray-100 text-gray-800"
                  >
                    {tag}
                  </span>
                ))}
              </div>
            </div>
          )}
        </div>
      </div>
    </div>
  );
};

export default TaskDetail;