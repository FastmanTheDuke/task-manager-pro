import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import { Calendar, Clock, Tag, MoreVertical, Edit, Trash2, CheckCircle } from 'lucide-react';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';
import taskService from '../../services/taskService';
import toast from 'react-hot-toast';

const TaskCard = ({ task, onUpdate, onDelete }) => {
  const [menuOpen, setMenuOpen] = useState(false);
  const [loading, setLoading] = useState(false);

  const getPriorityColor = (priority) => {
    switch (priority) {
      case 'urgent':
        return 'text-red-600 bg-red-100 dark:bg-red-900 dark:text-red-300';
      case 'high':
        return 'text-orange-600 bg-orange-100 dark:bg-orange-900 dark:text-orange-300';
      case 'medium':
        return 'text-yellow-600 bg-yellow-100 dark:bg-yellow-900 dark:text-yellow-300';
      case 'low':
        return 'text-blue-600 bg-blue-100 dark:bg-blue-900 dark:text-blue-300';
      default:
        return 'text-gray-600 bg-gray-100 dark:bg-gray-700 dark:text-gray-300';
    }
  };

  const getStatusColor = (status) => {
    switch (status) {
      case 'completed':
        return 'text-green-600';
      case 'in_progress':
        return 'text-blue-600';
      case 'pending':
        return 'text-gray-600';
      default:
        return 'text-gray-600';
    }
  };

  const handleStatusToggle = async () => {
    setLoading(true);
    const newStatus = task.status === 'completed' ? 'pending' : 'completed';

    try {
      const result = await taskService.updateTask(task.id, { status: newStatus });
      if (result.success) {
        onUpdate(result.data);
        toast.success(`Tâche marquée comme ${newStatus === 'completed' ? 'terminée' : 'à faire'}`);
      } else {
        toast.error(result.message);
      }
    } catch (error) {
      toast.error('Erreur lors de la mise à jour');
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async () => {
    if (!window.confirm('Êtes-vous sûr de vouloir supprimer cette tâche ?')) {
      return;
    }

    setLoading(true);
    try {
      const result = await taskService.deleteTask(task.id);
      if (result.success) {
        onDelete(task.id);
      } else {
        toast.error(result.message);
      }
    } catch (error) {
      toast.error('Erreur lors de la suppression');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="bg-white dark:bg-gray-800 rounded-lg shadow-md p-4 hover:shadow-lg transition-shadow">
      <div className="flex items-start justify-between mb-3">
        <Link to={`/tasks/${task.id}`} className="flex-1">
          <h3 className="text-lg font-medium text-gray-900 dark:text-white hover:text-primary-600 dark:hover:text-primary-400">
            {task.title}
          </h3>
        </Link>
        <div className="relative ml-2">
          <button
            onClick={() => setMenuOpen(!menuOpen)}
            className="p-1 rounded hover:bg-gray-100 dark:hover:bg-gray-700"
          >
            <MoreVertical className="h-5 w-5 text-gray-500" />
          </button>
          {menuOpen && (
            <div className="absolute right-0 mt-2 w-48 rounded-md shadow-lg bg-white dark:bg-gray-700 ring-1 ring-black ring-opacity-5">
              <Link
                to={`/tasks/${task.id}/edit`}
                className="block px-4 py-2 text-sm text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-600"
              >
                <Edit className="inline-block w-4 h-4 mr-2" />
                Modifier
              </Link>
              <button
                onClick={handleDelete}
                disabled={loading}
                className="block w-full text-left px-4 py-2 text-sm text-red-700 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-gray-600"
              >
                <Trash2 className="inline-block w-4 h-4 mr-2" />
                Supprimer
              </button>
            </div>
          )}
        </div>
      </div>

      {task.description && (
        <p className="text-sm text-gray-600 dark:text-gray-400 mb-3 line-clamp-2">
          {task.description}
        </p>
      )}

      <div className="flex items-center justify-between mb-3">
        <span className={`text-xs px-2 py-1 rounded-full ${getPriorityColor(task.priority)}`}>
          {task.priority}
        </span>
        <button
          onClick={handleStatusToggle}
          disabled={loading}
          className={`flex items-center text-sm ${getStatusColor(task.status)}`}
        >
          <CheckCircle className="h-4 w-4 mr-1" />
          {task.status === 'completed' ? 'Terminée' : 'À faire'}
        </button>
      </div>

      {task.tags && task.tags.length > 0 && (
        <div className="flex flex-wrap gap-1 mb-3">
          {task.tags.map((tag) => (
            <span
              key={tag.id}
              className="inline-flex items-center text-xs px-2 py-1 rounded"
              style={{
                backgroundColor: `${tag.color}20`,
                color: tag.color,
              }}
            >
              <Tag className="h-3 w-3 mr-1" />
              {tag.name}
            </span>
          ))}
        </div>
      )}

      <div className="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
        {task.due_date && (
          <div className="flex items-center">
            <Calendar className="h-3 w-3 mr-1" />
            {format(new Date(task.due_date), 'dd MMM', { locale: fr })}
          </div>
        )}
        {task.assignee_name && (
          <div className="flex items-center">
            <div className="h-5 w-5 rounded-full bg-gray-300 dark:bg-gray-600 mr-1"></div>
            {task.assignee_name}
          </div>
        )}
      </div>
    </div>
  );
};

export default TaskCard;