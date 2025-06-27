import React, { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import {
  FolderIcon,
  UserGroupIcon,
  CalendarIcon,
  TagIcon,
  ExclamationTriangleIcon,
  XMarkIcon
} from '@heroicons/react/24/outline';
import { useAuth } from '../../contexts/AuthContext';
import { useTheme } from '../../contexts/ThemeContext';
import LoadingSpinner from '../Common/LoadingSpinner';
import ErrorMessage from '../Common/ErrorMessage';

const ProjectForm = () => {
  const { id } = useParams();
  const navigate = useNavigate();
  const { user } = useAuth();
  const { isDark } = useTheme();
  const isEditing = Boolean(id);

  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [formData, setFormData] = useState({
    name: '',
    description: '',
    status: 'active',
    priority: 'medium',
    end_date: '', // CORRECTION: end_date au lieu de due_date
    color: '#3B82F6',
    is_public: false,
    template_id: null
  });
  const [members, setMembers] = useState([]);
  const [availableUsers, setAvailableUsers] = useState([]);
  const [memberSearch, setMemberSearch] = useState('');
  const [showMemberSearch, setShowMemberSearch] = useState(false);
  const [searchLoading, setSearchLoading] = useState(false);

  useEffect(() => {
    if (isEditing) {
      fetchProject();
    }
  }, [id, isEditing]);

  const fetchProject = async () => {
    try {
      setLoading(true);
      const token = localStorage.getItem('token');
      
      const response = await fetch(`${process.env.REACT_APP_API_URL}/projects/${id}`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      if (!response.ok) {
        throw new Error('Erreur lors du chargement du projet');
      }

      const data = await response.json();
      if (data.success) {
        const project = data.data;
        setFormData({
          name: project.name || '',
          description: project.description || '',
          status: project.status || 'active',
          priority: project.priority || 'medium',
          end_date: project.end_date ? project.end_date.split('T')[0] : '', // CORRECTION
          color: project.color || '#3B82F6',
          is_public: project.is_public || false,
          template_id: project.template_id || null
        });
        setMembers(project.members || []);
      }
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const fetchAvailableUsers = async (searchTerm = '') => {
    if (!searchTerm.trim()) {
      setAvailableUsers([]);
      return;
    }

    try {
      setSearchLoading(true);
      const token = localStorage.getItem('token');
      const response = await fetch(`${process.env.REACT_APP_API_URL}/users/search?q=${encodeURIComponent(searchTerm)}`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      if (response.ok) {
        const data = await response.json();
        if (data.success) {
          // Filtrer les utilisateurs qui ne sont pas déjà membres
          // et exclure l'utilisateur courant (pas nécessaire car le backend le fait déjà)
          const filteredUsers = data.data.filter(searchUser => 
            !members.some(member => member.id === searchUser.id)
          );
          setAvailableUsers(filteredUsers);
        }
      } else {
        console.error('Erreur lors de la recherche d\'utilisateurs:', response.status);
        setAvailableUsers([]);
      }
    } catch (err) {
      console.error('Erreur lors de la recherche d\'utilisateurs:', err);
      setAvailableUsers([]);
    } finally {
      setSearchLoading(false);
    }
  };

  const handleInputChange = (e) => {
    const { name, value, type, checked } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: type === 'checkbox' ? checked : value
    }));
  };

  const handleMemberSearchChange = (e) => {
    const value = e.target.value;
    setMemberSearch(value);
    setShowMemberSearch(true);
    
    if (value.length >= 2) {
      fetchAvailableUsers(value);
    } else {
      setAvailableUsers([]);
    }
  };

  const handleAddMember = (user, role = 'member') => {
    if (!members.some(member => member.id === user.id)) {
      setMembers(prev => [...prev, { ...user, role, pivot: { role } }]);
      setMemberSearch('');
      setShowMemberSearch(false);
      setAvailableUsers([]);
    }
  };

  const handleRemoveMember = (userId) => {
    setMembers(prev => prev.filter(member => member.id !== userId));
  };

  const handleMemberRoleChange = (userId, newRole) => {
    setMembers(prev => prev.map(member => 
      member.id === userId 
        ? { ...member, role: newRole, pivot: { ...member.pivot, role: newRole } }
        : member
    ));
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!formData.name.trim()) {
      setError('Le nom du projet est requis');
      return;
    }

    try {
      setLoading(true);
      setError(null);
      
      const token = localStorage.getItem('token');
      const url = isEditing 
        ? `${process.env.REACT_APP_API_URL}/projects/${id}`
        : `${process.env.REACT_APP_API_URL}/projects`;
      
      const method = isEditing ? 'PUT' : 'POST';
      
      const payload = {
        ...formData,
        members: members.map(member => ({
          user_id: member.id,
          role: member.role || member.pivot?.role || 'member'
        }))
      };

      console.log('Payload envoyé:', payload); // Debug

      const response = await fetch(url, {
        method,
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(payload)
      });

      const data = await response.json();
      
      if (!response.ok) {
        // Gestion améliorée des erreurs de validation
        if (data.errors) {
          const errorMessages = Object.values(data.errors).flat();
          throw new Error(errorMessages.join(', '));
        }
        throw new Error(data.message || 'Erreur lors de la sauvegarde');
      }

      if (data.success) {
        navigate('/projects');
      } else {
        throw new Error(data.message || 'Erreur inconnue');
      }
    } catch (err) {
      setError(err.message);
      console.error('Erreur lors de la soumission:', err); // Debug
    } finally {
      setLoading(false);
    }
  };

  if (loading && isEditing) return <LoadingSpinner />;

  return (
    <div className={`min-h-screen ${isDark ? 'bg-gray-900' : 'bg-gray-50'}`}>
      <div className="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Header */}
        <div className="mb-8">
          <h1 className={`text-3xl font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}>
            {isEditing ? 'Modifier le projet' : 'Créer un nouveau projet'}
          </h1>
          <p className={`mt-2 ${isDark ? 'text-gray-400' : 'text-gray-600'}`}>
            {isEditing 
              ? 'Modifiez les informations de votre projet collaboratif'
              : 'Créez un nouveau projet collaboratif pour votre équipe'
            }
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

        {/* Form */}
        <form onSubmit={handleSubmit} className="space-y-8">
          <div className={`p-6 rounded-lg shadow-sm border ${isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'}`}>
            <h3 className={`text-lg font-medium mb-4 ${isDark ? 'text-white' : 'text-gray-900'}`}>
              Informations générales
            </h3>
            
            <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
              {/* Name */}
              <div className="md:col-span-2">
                <label className={`block text-sm font-medium mb-2 ${isDark ? 'text-gray-300' : 'text-gray-700'}`}>
                  Nom du projet *
                </label>
                <input
                  type="text"
                  name="name"
                  value={formData.name}
                  onChange={handleInputChange}
                  placeholder="Nom de votre projet"
                  required
                  className={`
                    block w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                    ${isDark 
                      ? 'bg-gray-700 border-gray-600 text-white placeholder-gray-400' 
                      : 'bg-white border-gray-300 text-gray-900 placeholder-gray-500'
                    }
                  `}
                />
              </div>

              {/* Description */}
              <div className="md:col-span-2">
                <label className={`block text-sm font-medium mb-2 ${isDark ? 'text-gray-300' : 'text-gray-700'}`}>
                  Description
                </label>
                <textarea
                  name="description"
                  value={formData.description}
                  onChange={handleInputChange}
                  rows={4}
                  placeholder="Description détaillée du projet..."
                  className={`
                    block w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                    ${isDark 
                      ? 'bg-gray-700 border-gray-600 text-white placeholder-gray-400' 
                      : 'bg-white border-gray-300 text-gray-900 placeholder-gray-500'
                    }
                  `}
                />
              </div>

              {/* Status */}
              <div>
                <label className={`block text-sm font-medium mb-2 ${isDark ? 'text-gray-300' : 'text-gray-700'}`}>
                  Statut
                </label>
                <select
                  name="status"
                  value={formData.status}
                  onChange={handleInputChange}
                  className={`
                    block w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                    ${isDark 
                      ? 'bg-gray-700 border-gray-600 text-white' 
                      : 'bg-white border-gray-300 text-gray-900'
                    }
                  `}
                >
                  <option value="active">Actif</option>
                  <option value="on_hold">En pause</option>
                  <option value="completed">Terminé</option>
                  <option value="cancelled">Annulé</option>
                </select>
              </div>

              {/* Priority */}
              <div>
                <label className={`block text-sm font-medium mb-2 ${isDark ? 'text-gray-300' : 'text-gray-700'}`}>
                  Priorité
                </label>
                <select
                  name="priority"
                  value={formData.priority}
                  onChange={handleInputChange}
                  className={`
                    block w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                    ${isDark 
                      ? 'bg-gray-700 border-gray-600 text-white' 
                      : 'bg-white border-gray-300 text-gray-900'
                    }
                  `}
                >
                  <option value="low">Basse</option>
                  <option value="medium">Moyenne</option>
                  <option value="high">Haute</option>
                  <option value="urgent">Urgente</option>
                </select>
              </div>

              {/* End Date - CORRECTION */}
              <div>
                <label className={`block text-sm font-medium mb-2 ${isDark ? 'text-gray-300' : 'text-gray-700'}`}>
                  Date d'échéance
                </label>
                <input
                  type="date"
                  name="end_date"
                  value={formData.end_date}
                  onChange={handleInputChange}
                  className={`
                    block w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                    ${isDark 
                      ? 'bg-gray-700 border-gray-600 text-white' 
                      : 'bg-white border-gray-300 text-gray-900'
                    }
                  `}
                />
              </div>

              {/* Color */}
              <div>
                <label className={`block text-sm font-medium mb-2 ${isDark ? 'text-gray-300' : 'text-gray-700'}`}>
                  Couleur du projet
                </label>
                <input
                  type="color"
                  name="color"
                  value={formData.color}
                  onChange={handleInputChange}
                  className="h-10 w-20 border rounded-lg cursor-pointer"
                />
              </div>

              {/* Public */}
              <div className="md:col-span-2">
                <label className="flex items-center">
                  <input
                    type="checkbox"
                    name="is_public"
                    checked={formData.is_public}
                    onChange={handleInputChange}
                    className="h-4 w-4 text-blue-600 focus:ring-blue-500 border-gray-300 rounded"
                  />
                  <span className={`ml-2 text-sm ${isDark ? 'text-gray-300' : 'text-gray-700'}`}>
                    Projet public (visible par tous les utilisateurs)
                  </span>
                </label>
              </div>
            </div>
          </div>

          {/* Members Section */}
          <div className={`p-6 rounded-lg shadow-sm border ${isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'}`}>
            <h3 className={`text-lg font-medium mb-4 ${isDark ? 'text-white' : 'text-gray-900'}`}>
              Membres de l'équipe
            </h3>

            {/* Add Member */}
            <div className="mb-4">
              <div className="relative">
                <input
                  type="text"
                  value={memberSearch}
                  onChange={handleMemberSearchChange}
                  onBlur={() => {
                    // Attendre un peu avant de fermer pour permettre les clics
                    setTimeout(() => setShowMemberSearch(false), 200);
                  }}
                  onFocus={() => setShowMemberSearch(true)}
                  placeholder="Rechercher des utilisateurs..."
                  className={`
                    block w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                    ${isDark 
                      ? 'bg-gray-700 border-gray-600 text-white placeholder-gray-400' 
                      : 'bg-white border-gray-300 text-gray-900 placeholder-gray-500'
                    }
                  `}
                />
                
                {searchLoading && (
                  <div className="absolute right-3 top-3">
                    <div className="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-500"></div>
                  </div>
                )}
                
                {showMemberSearch && memberSearch && availableUsers.length > 0 && (
                  <div className={`
                    absolute z-10 mt-1 w-full rounded-md shadow-lg
                    ${isDark ? 'bg-gray-800 border border-gray-700' : 'bg-white border border-gray-200'}
                  `}>
                    <div className="py-1 max-h-60 overflow-y-auto">
                      {availableUsers.map(searchUser => (
                        <button
                          key={searchUser.id}
                          type="button"
                          onClick={() => handleAddMember(searchUser)}
                          className={`
                            flex items-center w-full px-4 py-2 text-sm hover:bg-gray-50 transition-colors
                            ${isDark ? 'text-gray-300 hover:bg-gray-700' : 'text-gray-700'}
                          `}
                        >
                          <UserGroupIcon className="h-4 w-4 mr-3" />
                          <div className="text-left">
                            <div className="font-medium">{searchUser.username}</div>
                            <div className="text-xs text-gray-500">{searchUser.email}</div>
                          </div>
                        </button>
                      ))}
                    </div>
                  </div>
                )}
                
                {showMemberSearch && memberSearch && !searchLoading && availableUsers.length === 0 && memberSearch.length >= 2 && (
                  <div className={`
                    absolute z-10 mt-1 w-full rounded-md shadow-lg
                    ${isDark ? 'bg-gray-800 border border-gray-700' : 'bg-white border border-gray-200'}
                  `}>
                    <div className="py-3 px-4 text-sm text-gray-500">
                      Aucun utilisateur trouvé
                    </div>
                  </div>
                )}
              </div>
            </div>

            {/* Current Members */}
            <div className="space-y-2">
              {members.map(member => (
                <div
                  key={member.id}
                  className={`
                    flex items-center justify-between p-3 rounded-lg border
                    ${isDark ? 'bg-gray-700 border-gray-600' : 'bg-gray-50 border-gray-200'}
                  `}
                >
                  <div className="flex items-center">
                    <div className={`
                      w-8 h-8 rounded-full flex items-center justify-center text-white text-sm font-medium
                      ${member.avatar ? '' : 'bg-blue-600'}
                    `}>
                      {member.avatar ? (
                        <img src={member.avatar} alt={member.username} className="w-8 h-8 rounded-full" />
                      ) : (
                        member.username.charAt(0).toUpperCase()
                      )}
                    </div>
                    <div className="ml-3">
                      <div className={`font-medium ${isDark ? 'text-white' : 'text-gray-900'}`}>
                        {member.username}
                      </div>
                      <div className={`text-sm ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
                        {member.email}
                      </div>
                    </div>
                  </div>
                  
                  <div className="flex items-center space-x-2">
                    <select
                      value={member.role || member.pivot?.role || 'member'}
                      onChange={(e) => handleMemberRoleChange(member.id, e.target.value)}
                      className={`
                        px-2 py-1 text-sm border rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                        ${isDark 
                          ? 'bg-gray-800 border-gray-600 text-white' 
                          : 'bg-white border-gray-300 text-gray-900'
                        }
                      `}
                    >
                      <option value="member">Membre</option>
                      <option value="admin">Admin</option>
                      <option value="viewer">Observateur</option>
                    </select>
                    
                    <button
                      type="button"
                      onClick={() => handleRemoveMember(member.id)}
                      className="p-1 text-red-500 hover:text-red-700 transition-colors"
                    >
                      <XMarkIcon className="h-4 w-4" />
                    </button>
                  </div>
                </div>
              ))}
              
              {members.length === 0 && (
                <p className={`text-sm text-center py-4 ${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
                  Aucun membre ajouté. Recherchez des utilisateurs à ajouter au projet.
                </p>
              )}
            </div>
          </div>

          {/* Actions */}
          <div className="flex justify-end space-x-4">
            <button
              type="button"
              onClick={() => navigate('/projects')}
              className={`
                px-6 py-2 border rounded-lg font-medium transition-colors
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
              disabled={loading}
              className="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50"
            >
              {loading ? 'Sauvegarde...' : (isEditing ? 'Mettre à jour' : 'Créer le projet')}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
};

export default ProjectForm;