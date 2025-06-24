import React, { useState } from 'react';
import { Link } from 'react-router-dom';
import {
  FolderIcon,
  UserGroupIcon,
  ClockIcon,
  CalendarIcon,
  CheckCircleIcon,
  EllipsisVerticalIcon,
  PencilIcon,
  TrashIcon,
  StarIcon,
  ArchiveBoxIcon
} from '@heroicons/react/24/outline';
import { StarIcon as StarIconSolid } from '@heroicons/react/24/solid';

const ProjectCard = ({ project, onUpdate, onDelete, isDark }) => {
  const [showMenu, setShowMenu] = useState(false);
  const [loading, setLoading] = useState(false);

  const getStatusColor = (status) => {
    const colors = {
      active: 'bg-green-100 text-green-800',
      completed: 'bg-blue-100 text-blue-800',
      on_hold: 'bg-yellow-100 text-yellow-800',
      cancelled: 'bg-red-100 text-red-800'
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
  };

  const getStatusText = (status) => {
    const texts = {
      active: 'Actif',
      completed: 'Terminé',
      on_hold: 'En pause',
      cancelled: 'Annulé'
    };
    return texts[status] || status;
  };

  const getPriorityColor = (priority) => {
    const colors = {
      low: 'text-green-500',
      medium: 'text-yellow-500',
      high: 'text-orange-500',
      urgent: 'text-red-500'
    };
    return colors[priority] || 'text-gray-500';
  };

  const formatDate = (dateString) => {
    if (!dateString) return '';
    return new Date(dateString).toLocaleDateString('fr-FR', {
      day: 'numeric',
      month: 'short',
      year: 'numeric'
    });
  };

  const handleFavorite = async () => {
    try {
      setLoading(true);
      const token = localStorage.getItem('token');
      
      const response = await fetch(`${process.env.REACT_APP_API_URL}/projects/${project.id}/favorite`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      if (response.ok) {
        const data = await response.json();
        onUpdate(data.data);
      }
    } catch (error) {
      console.error('Erreur lors de la mise à jour des favoris:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleArchive = async () => {
    try {
      setLoading(true);
      const token = localStorage.getItem('token');
      
      const response = await fetch(`${process.env.REACT_APP_API_URL}/projects/${project.id}/archive`, {
        method: 'POST',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      if (response.ok) {
        const data = await response.json();
        onUpdate(data.data);
      }
    } catch (error) {
      console.error('Erreur lors de l\'archivage:', error);
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async () => {
    if (!window.confirm('Êtes-vous sûr de vouloir supprimer ce projet ? Cette action est irréversible.')) {
      return;
    }

    try {
      setLoading(true);
      const token = localStorage.getItem('token');
      
      const response = await fetch(`${process.env.REACT_APP_API_URL}/projects/${project.id}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      if (response.ok) {
        onDelete(project.id);
      }
    } catch (error) {
      console.error('Erreur lors de la suppression:', error);
    } finally {
      setLoading(false);
    }
  };

  const isOverdue = project.due_date && new Date(project.due_date) < new Date() && project.status !== 'completed';

  return (
    <div className={`
      relative rounded-lg shadow-sm border transition-all duration-200 hover:shadow-md
      ${isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'}
      ${loading ? 'opacity-50 pointer-events-none' : ''}
    `}>
      {/* Header */}
      <div className="p-6">
        <div className="flex items-start justify-between">
          <div className="flex items-center space-x-3">
            <div className={`
              p-2 rounded-lg
              ${isDark ? 'bg-gray-700' : 'bg-blue-50'}
            `}>
              <FolderIcon className={`h-6 w-6 ${
                isDark ? 'text-blue-400' : 'text-blue-600'
              }`} />
            </div>
            <div>
              <Link
                to={`/projects/${project.id}`}
                className={`
                  text-lg font-semibold hover:underline
                  ${isDark ? 'text-white hover:text-blue-400' : 'text-gray-900 hover:text-blue-600'}
                `}
              >
                {project.name}
              </Link>
              <div className="flex items-center space-x-2 mt-1">
                <span className={`
                  inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                  ${isDark ? 'bg-gray-700 text-gray-300' : getStatusColor(project.status)}
                `}>
                  {getStatusText(project.status)}
                </span>
                {project.is_favorite && (
                  <StarIconSolid className="h-4 w-4 text-yellow-500" />
                )}
              </div>
            </div>
          </div>

          {/* Menu */}
          <div className="relative">
            <button
              onClick={() => setShowMenu(!showMenu)}
              className={`
                p-1 rounded-lg hover:bg-gray-100 transition-colors
                ${isDark ? 'hover:bg-gray-700 text-gray-400' : 'text-gray-500'}
              `}
            >
              <EllipsisVerticalIcon className="h-5 w-5" />
            </button>

            {showMenu && (
              <div
                className={`
                  absolute right-0 mt-1 w-48 rounded-md shadow-lg z-10
                  ${isDark ? 'bg-gray-800 border border-gray-700' : 'bg-white border border-gray-200'}
                `}
                onBlur={() => setShowMenu(false)}
              >
                <div className="py-1">
                  <button
                    onClick={handleFavorite}
                    className={`
                      flex items-center w-full px-4 py-2 text-sm hover:bg-gray-50 transition-colors
                      ${isDark ? 'text-gray-300 hover:bg-gray-700' : 'text-gray-700'}
                    `}
                  >
                    <StarIcon className="h-4 w-4 mr-3" />
                    {project.is_favorite ? 'Retirer des favoris' : 'Ajouter aux favoris'}
                  </button>
                  
                  <Link
                    to={`/projects/${project.id}/edit`}
                    className={`
                      flex items-center w-full px-4 py-2 text-sm hover:bg-gray-50 transition-colors
                      ${isDark ? 'text-gray-300 hover:bg-gray-700' : 'text-gray-700'}
                    `}
                  >
                    <PencilIcon className="h-4 w-4 mr-3" />
                    Modifier
                  </Link>

                  <button
                    onClick={handleArchive}
                    className={`
                      flex items-center w-full px-4 py-2 text-sm hover:bg-gray-50 transition-colors
                      ${isDark ? 'text-gray-300 hover:bg-gray-700' : 'text-gray-700'}
                    `}
                  >
                    <ArchiveBoxIcon className="h-4 w-4 mr-3" />
                    {project.is_archived ? 'Désarchiver' : 'Archiver'}
                  </button>

                  <button
                    onClick={handleDelete}
                    className={`
                      flex items-center w-full px-4 py-2 text-sm text-red-600 hover:bg-red-50 transition-colors
                      ${isDark ? 'hover:bg-red-900/20' : ''}
                    `}
                  >
                    <TrashIcon className="h-4 w-4 mr-3" />
                    Supprimer
                  </button>
                </div>
              </div>
            )}
          </div>
        </div>

        {/* Description */}
        {project.description && (
          <p className={`
            mt-3 text-sm line-clamp-2
            ${isDark ? 'text-gray-400' : 'text-gray-600'}
          `}>
            {project.description}
          </p>
        )}

        {/* Progress Bar */}
        <div className="mt-4">
          <div className="flex items-center justify-between text-sm mb-2">
            <span className={isDark ? 'text-gray-400' : 'text-gray-600'}>
              Progression
            </span>
            <span className={`font-medium ${isDark ? 'text-white' : 'text-gray-900'}`}>
              {Math.round(project.completion_percentage || 0)}%
            </span>
          </div>
          <div className={`w-full bg-gray-200 rounded-full h-2 ${isDark ? 'bg-gray-700' : ''}`}>
            <div
              className="bg-blue-600 h-2 rounded-full transition-all duration-300"
              style={{ width: `${project.completion_percentage || 0}%` }}
            />
          </div>
        </div>

        {/* Stats */}
        <div className="mt-4 flex items-center justify-between text-sm">
          <div className="flex items-center space-x-4">
            <div className={`flex items-center ${isDark ? 'text-gray-400' : 'text-gray-600'}`}>
              <CheckCircleIcon className="h-4 w-4 mr-1" />
              <span>{project.tasks_completed || 0}/{project.tasks_total || 0}</span>
            </div>
            
            <div className={`flex items-center ${isDark ? 'text-gray-400' : 'text-gray-600'}`}>
              <UserGroupIcon className="h-4 w-4 mr-1" />
              <span>{project.members_count || 0}</span>
            </div>
          </div>

          {project.due_date && (
            <div className={`
              flex items-center
              ${isOverdue ? 'text-red-500' : isDark ? 'text-gray-400' : 'text-gray-600'}
            `}>
              <CalendarIcon className="h-4 w-4 mr-1" />
              <span>{formatDate(project.due_date)}</span>
            </div>
          )}
        </div>

        {/* Priority indicator */}
        {project.priority && project.priority !== 'medium' && (
          <div className="mt-2">
            <span className={`
              inline-flex items-center px-2 py-1 rounded-full text-xs font-medium
              ${getPriorityColor(project.priority)}
            `}>
              <ClockIcon className="h-3 w-3 mr-1" />
              Priorité {project.priority === 'high' ? 'haute' : project.priority === 'urgent' ? 'urgente' : 'basse'}
            </span>
          </div>
        )}
      </div>
    </div>
  );
};

export default ProjectCard;