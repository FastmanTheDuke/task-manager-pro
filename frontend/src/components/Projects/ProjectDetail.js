import React, { useState, useEffect } from 'react';
import { useParams, Link, useNavigate } from 'react-router-dom';
import {
  FolderIcon,
  UserGroupIcon,
  ClockIcon,
  CalendarIcon,
  CheckCircleIcon,
  PencilIcon,
  TrashIcon,
  StarIcon,
  ArchiveBoxIcon,
  ArrowLeftIcon,
  PlusIcon,
  ChevronRightIcon
} from '@heroicons/react/24/outline';
import { StarIcon as StarIconSolid } from '@heroicons/react/24/solid';

const ProjectDetail = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const [project, setProject] = useState(null);
  const [tasks, setTasks] = useState([]);
  const [members, setMembers] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);

  useEffect(() => {
    fetchProjectDetails();
  }, [id]);

  const fetchProjectDetails = async () => {
    try {
      setLoading(true);
      const token = localStorage.getItem('token');
      
      // Fetch project details
      const projectResponse = await fetch(`${process.env.REACT_APP_API_URL}/projects/${id}`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      if (!projectResponse.ok) {
        throw new Error('Projet non trouvé');
      }

      const projectData = await projectResponse.json();
      setProject(projectData.data);

      // Fetch project tasks
      const tasksResponse = await fetch(`${process.env.REACT_APP_API_URL}/projects/${id}/tasks`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      if (tasksResponse.ok) {
        const tasksData = await tasksResponse.json();
        setTasks(tasksData.data || []);
      }

      // Fetch project members
      const membersResponse = await fetch(`${process.env.REACT_APP_API_URL}/projects/${id}/members`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      if (membersResponse.ok) {
        const membersData = await membersResponse.json();
        setMembers(membersData.data || []);
      }

    } catch (error) {
      console.error('Erreur lors du chargement du projet:', error);
      setError(error.message);
    } finally {
      setLoading(false);
    }
  };

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
      month: 'long',
      year: 'numeric'
    });
  };

  const handleFavorite = async () => {
    try {
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
        setProject(data.data);
      }
    } catch (error) {
      console.error('Erreur lors de la mise à jour des favoris:', error);
    }
  };

  const handleDelete = async () => {
    if (!window.confirm('Êtes-vous sûr de vouloir supprimer ce projet ? Cette action est irréversible.')) {
      return;
    }

    try {
      const token = localStorage.getItem('token');
      
      const response = await fetch(`${process.env.REACT_APP_API_URL}/projects/${project.id}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      if (response.ok) {
        navigate('/projects');
      }
    } catch (error) {
      console.error('Erreur lors de la suppression:', error);
    }
  };

  const getTaskStatusColor = (status) => {
    const colors = {
      todo: 'bg-gray-100 text-gray-800',
      in_progress: 'bg-blue-100 text-blue-800',
      completed: 'bg-green-100 text-green-800',
      cancelled: 'bg-red-100 text-red-800'
    };
    return colors[status] || 'bg-gray-100 text-gray-800';
  };

  const getTaskStatusText = (status) => {
    const texts = {
      todo: 'À faire',
      in_progress: 'En cours',
      completed: 'Terminé',
      cancelled: 'Annulé'
    };
    return texts[status] || status;
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-32 w-32 border-b-2 border-blue-600"></div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="max-w-2xl mx-auto mt-8">
        <div className="bg-red-50 border border-red-200 rounded-md p-4">
          <div className="flex">
            <div className="ml-3">
              <h3 className="text-sm font-medium text-red-800">
                Erreur
              </h3>
              <div className="mt-2 text-sm text-red-700">
                {error}
              </div>
              <div className="mt-3">
                <Link
                  to="/projects"
                  className="bg-red-100 px-3 py-2 rounded-md text-sm font-medium text-red-800 hover:bg-red-200"
                >
                  Retour aux projets
                </Link>
              </div>
            </div>
          </div>
        </div>
      </div>
    );
  }

  if (!project) {
    return null;
  }

  const isOverdue = project.due_date && new Date(project.due_date) < new Date() && project.status !== 'completed';

  return (
    <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      {/* Breadcrumb */}
      <nav className="flex mb-8" aria-label="Breadcrumb">
        <ol className="flex items-center space-x-4">
          <li>
            <Link to="/projects" className="text-gray-400 hover:text-gray-500">
              <ArrowLeftIcon className="h-5 w-5" />
            </Link>
          </li>
          <li>
            <div className="flex items-center">
              <ChevronRightIcon className="h-5 w-5 text-gray-400" />
              <Link to="/projects" className="ml-4 text-sm font-medium text-gray-500 hover:text-gray-700">
                Projets
              </Link>
            </div>
          </li>
          <li>
            <div className="flex items-center">
              <ChevronRightIcon className="h-5 w-5 text-gray-400" />
              <span className="ml-4 text-sm font-medium text-gray-900">{project.name}</span>
            </div>
          </li>
        </ol>
      </nav>

      {/* Header */}
      <div className="bg-white shadow rounded-lg mb-8">
        <div className="px-6 py-6">
          <div className="flex items-start justify-between">
            <div className="flex items-center space-x-4">
              <div className="p-3 bg-blue-50 rounded-lg">
                <FolderIcon className="h-8 w-8 text-blue-600" />
              </div>
              <div>
                <h1 className="text-2xl font-bold text-gray-900">{project.name}</h1>
                <div className="flex items-center space-x-3 mt-2">
                  <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(project.status)}`}>
                    {getStatusText(project.status)}
                  </span>
                  {project.is_favorite && (
                    <StarIconSolid className="h-5 w-5 text-yellow-500" />
                  )}
                  {project.priority && project.priority !== 'medium' && (
                    <span className={`inline-flex items-center px-2 py-1 rounded-full text-xs font-medium ${getPriorityColor(project.priority)}`}>
                      <ClockIcon className="h-3 w-3 mr-1" />
                      Priorité {project.priority === 'high' ? 'haute' : project.priority === 'urgent' ? 'urgente' : 'basse'}
                    </span>
                  )}
                </div>
              </div>
            </div>

            <div className="flex items-center space-x-2">
              <button
                onClick={handleFavorite}
                className="p-2 text-gray-400 hover:text-yellow-500 hover:bg-gray-50 rounded-lg transition-colors"
              >
                {project.is_favorite ? (
                  <StarIconSolid className="h-5 w-5 text-yellow-500" />
                ) : (
                  <StarIcon className="h-5 w-5" />
                )}
              </button>
              
              <Link
                to={`/projects/${project.id}/edit`}
                className="p-2 text-gray-400 hover:text-blue-600 hover:bg-gray-50 rounded-lg transition-colors"
              >
                <PencilIcon className="h-5 w-5" />
              </Link>
              
              <button
                onClick={handleDelete}
                className="p-2 text-gray-400 hover:text-red-600 hover:bg-gray-50 rounded-lg transition-colors"
              >
                <TrashIcon className="h-5 w-5" />
              </button>
            </div>
          </div>

          {project.description && (
            <div className="mt-6">
              <p className="text-gray-600">{project.description}</p>
            </div>
          )}

          {/* Project Stats */}
          <div className="mt-6 grid grid-cols-1 md:grid-cols-4 gap-6">
            <div className="text-center">
              <div className="text-2xl font-bold text-blue-600">{Math.round(project.completion_percentage || 0)}%</div>
              <div className="text-sm text-gray-500">Progression</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-green-600">{project.tasks_completed || 0}</div>
              <div className="text-sm text-gray-500">Tâches terminées</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-orange-600">{project.tasks_total || 0}</div>
              <div className="text-sm text-gray-500">Total tâches</div>
            </div>
            <div className="text-center">
              <div className="text-2xl font-bold text-purple-600">{members.length}</div>
              <div className="text-sm text-gray-500">Membres</div>
            </div>
          </div>

          {/* Progress Bar */}
          <div className="mt-6">
            <div className="w-full bg-gray-200 rounded-full h-3">
              <div
                className="bg-blue-600 h-3 rounded-full transition-all duration-300"
                style={{ width: `${project.completion_percentage || 0}%` }}
              />
            </div>
          </div>

          {/* Dates */}
          <div className="mt-6 flex items-center justify-between text-sm text-gray-500">
            <div>
              Créé le {formatDate(project.created_at)}
            </div>
            {project.due_date && (
              <div className={isOverdue ? 'text-red-500 font-medium' : ''}>
                <CalendarIcon className="h-4 w-4 inline mr-1" />
                Échéance : {formatDate(project.due_date)}
              </div>
            )}
          </div>
        </div>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-3 gap-8">
        {/* Tasks Section */}
        <div className="lg:col-span-2">
          <div className="bg-white shadow rounded-lg">
            <div className="px-6 py-4 border-b border-gray-200">
              <div className="flex items-center justify-between">
                <h2 className="text-lg font-medium text-gray-900">
                  Tâches ({tasks.length})
                </h2>
                <Link
                  to={`/tasks/new?project=${project.id}`}
                  className="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700"
                >
                  <PlusIcon className="h-4 w-4 mr-1" />
                  Nouvelle tâche
                </Link>
              </div>
            </div>
            
            <div className="divide-y divide-gray-200">
              {tasks.length === 0 ? (
                <div className="px-6 py-8 text-center text-gray-500">
                  Aucune tâche dans ce projet
                </div>
              ) : (
                tasks.map((task) => (
                  <div key={task.id} className="px-6 py-4 hover:bg-gray-50">
                    <div className="flex items-center justify-between">
                      <div className="flex-1">
                        <Link
                          to={`/tasks/${task.id}`}
                          className="text-sm font-medium text-gray-900 hover:text-blue-600"
                        >
                          {task.title}
                        </Link>
                        <div className="flex items-center space-x-2 mt-1">
                          <span className={`inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium ${getTaskStatusColor(task.status)}`}>
                            {getTaskStatusText(task.status)}
                          </span>
                          {task.due_date && (
                            <span className="text-xs text-gray-500">
                              {formatDate(task.due_date)}
                            </span>
                          )}
                        </div>
                      </div>
                      {task.assignee_name && (
                        <div className="text-xs text-gray-500">
                          Assigné à {task.assignee_name}
                        </div>
                      )}
                    </div>
                  </div>
                ))
              )}
            </div>
          </div>
        </div>

        {/* Sidebar */}
        <div className="space-y-6">
          {/* Members Section */}
          <div className="bg-white shadow rounded-lg">
            <div className="px-6 py-4 border-b border-gray-200">
              <h3 className="text-lg font-medium text-gray-900">
                Membres ({members.length})
              </h3>
            </div>
            <div className="px-6 py-4">
              {members.length === 0 ? (
                <p className="text-sm text-gray-500">Aucun membre assigné</p>
              ) : (
                <div className="space-y-3">
                  {members.map((member) => (
                    <div key={member.id} className="flex items-center space-x-3">
                      <div className="flex-shrink-0">
                        <div className="h-8 w-8 bg-gray-300 rounded-full flex items-center justify-center">
                          <span className="text-sm font-medium text-gray-700">
                            {member.name.charAt(0).toUpperCase()}
                          </span>
                        </div>
                      </div>
                      <div className="flex-1 min-w-0">
                        <p className="text-sm font-medium text-gray-900 truncate">
                          {member.name}
                        </p>
                        <p className="text-sm text-gray-500 truncate">
                          {member.role || 'Membre'}
                        </p>
                      </div>
                    </div>
                  ))}
                </div>
              )}
            </div>
          </div>

          {/* Project Info */}
          <div className="bg-white shadow rounded-lg">
            <div className="px-6 py-4 border-b border-gray-200">
              <h3 className="text-lg font-medium text-gray-900">Informations</h3>
            </div>
            <div className="px-6 py-4 space-y-3">
              <div>
                <dt className="text-sm font-medium text-gray-500">Statut</dt>
                <dd className="mt-1">
                  <span className={`inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${getStatusColor(project.status)}`}>
                    {getStatusText(project.status)}
                  </span>
                </dd>
              </div>
              
              {project.priority && (
                <div>
                  <dt className="text-sm font-medium text-gray-500">Priorité</dt>
                  <dd className={`mt-1 text-sm ${getPriorityColor(project.priority)}`}>
                    {project.priority === 'high' ? 'Haute' : project.priority === 'urgent' ? 'Urgente' : project.priority === 'low' ? 'Basse' : 'Moyenne'}
                  </dd>
                </div>
              )}
              
              <div>
                <dt className="text-sm font-medium text-gray-500">Créé le</dt>
                <dd className="mt-1 text-sm text-gray-900">
                  {formatDate(project.created_at)}
                </dd>
              </div>
              
              {project.due_date && (
                <div>
                  <dt className="text-sm font-medium text-gray-500">Échéance</dt>
                  <dd className={`mt-1 text-sm ${isOverdue ? 'text-red-600 font-medium' : 'text-gray-900'}`}>
                    {formatDate(project.due_date)}
                  </dd>
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};

export default ProjectDetail;