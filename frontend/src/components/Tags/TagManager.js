import React, { useState, useEffect } from 'react';
import { 
  TagIcon, 
  PlusIcon, 
  PencilIcon, 
  TrashIcon,
  SwatchIcon,
  MagnifyingGlassIcon
} from '@heroicons/react/24/outline';
import { useAuth } from '../../contexts/AuthContext';
import { useTheme } from '../../contexts/ThemeContext';
import LoadingSpinner from '../Common/LoadingSpinner';
import ErrorMessage from '../Common/ErrorMessage';

const TagManager = () => {
  const { user } = useAuth();
  const { isDark } = useTheme();
  const [tags, setTags] = useState([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState(null);
  const [showForm, setShowForm] = useState(false);
  const [editingTag, setEditingTag] = useState(null);
  const [searchTerm, setSearchTerm] = useState('');
  const [formData, setFormData] = useState({
    name: '',
    color: '#3B82F6',
    description: ''
  });

  const predefinedColors = [
    '#3B82F6', '#EF4444', '#10B981', '#F59E0B', '#8B5CF6',
    '#EC4899', '#06B6D4', '#84CC16', '#F97316', '#6366F1',
    '#14B8A6', '#F43F5E', '#8B5A2B', '#6B7280', '#1F2937'
  ];

  useEffect(() => {
    fetchTags();
  }, []);

  const fetchTags = async () => {
    try {
      setLoading(true);
      const token = localStorage.getItem('token');
      
      const response = await fetch(`${process.env.REACT_APP_API_URL}/tags`, {
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      if (!response.ok) {
        throw new Error('Erreur lors du chargement des tags');
      }

      const data = await response.json();
      if (data.success) {
        setTags(data.data || []);
      } else {
        throw new Error(data.message || 'Erreur inconnue');
      }
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const handleSubmit = async (e) => {
    e.preventDefault();
    
    if (!formData.name.trim()) {
      setError('Le nom du tag est requis');
      return;
    }

    try {
      setLoading(true);
      setError(null);
      
      const token = localStorage.getItem('token');
      const url = editingTag 
        ? `${process.env.REACT_APP_API_URL}/tags/${editingTag.id}`
        : `${process.env.REACT_APP_API_URL}/tags`;
      
      const method = editingTag ? 'PUT' : 'POST';

      const response = await fetch(url, {
        method,
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        },
        body: JSON.stringify(formData)
      });

      const data = await response.json();
      
      if (!response.ok) {
        throw new Error(data.message || 'Erreur lors de la sauvegarde');
      }

      if (data.success) {
        await fetchTags();
        resetForm();
      } else {
        throw new Error(data.message || 'Erreur inconnue');
      }
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const handleEdit = (tag) => {
    setEditingTag(tag);
    setFormData({
      name: tag.name,
      color: tag.color,
      description: tag.description || ''
    });
    setShowForm(true);
  };

  const handleDelete = async (tag) => {
    if (!window.confirm(`Êtes-vous sûr de vouloir supprimer le tag "${tag.name}" ? Cette action supprimera aussi le tag de toutes les tâches associées.`)) {
      return;
    }

    try {
      setLoading(true);
      const token = localStorage.getItem('token');
      
      const response = await fetch(`${process.env.REACT_APP_API_URL}/tags/${tag.id}`, {
        method: 'DELETE',
        headers: {
          'Authorization': `Bearer ${token}`,
          'Content-Type': 'application/json'
        }
      });

      if (response.ok) {
        setTags(prev => prev.filter(t => t.id !== tag.id));
      } else {
        const data = await response.json();
        throw new Error(data.message || 'Erreur lors de la suppression');
      }
    } catch (err) {
      setError(err.message);
    } finally {
      setLoading(false);
    }
  };

  const resetForm = () => {
    setFormData({
      name: '',
      color: '#3B82F6',
      description: ''
    });
    setEditingTag(null);
    setShowForm(false);
  };

  const handleInputChange = (e) => {
    const { name, value } = e.target;
    setFormData(prev => ({
      ...prev,
      [name]: value
    }));
  };

  const filteredTags = tags.filter(tag =>
    tag.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    (tag.description && tag.description.toLowerCase().includes(searchTerm.toLowerCase()))
  );

  if (loading && tags.length === 0) return <LoadingSpinner />;

  return (
    <div className={`min-h-screen ${isDark ? 'bg-gray-900' : 'bg-gray-50'}`}>
      <div className="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Header */}
        <div className="mb-8">
          <div className="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-4">
            <div>
              <h1 className={`text-3xl font-bold ${isDark ? 'text-white' : 'text-gray-900'}`}>
                Gestion des Tags
              </h1>
              <p className={`mt-2 ${isDark ? 'text-gray-400' : 'text-gray-600'}`}>
                Organisez vos tâches avec des tags colorés et personnalisables
              </p>
            </div>
            <button
              onClick={() => setShowForm(!showForm)}
              className="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200"
            >
              <PlusIcon className="h-5 w-5 mr-2" />
              Nouveau Tag
            </button>
          </div>

          {/* Search */}
          <div className="mt-6">
            <div className="relative max-w-md">
              <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                <MagnifyingGlassIcon className={`h-5 w-5 ${isDark ? 'text-gray-400' : 'text-gray-400'}`} />
              </div>
              <input
                type="text"
                placeholder="Rechercher des tags..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className={`
                  block w-full pl-10 pr-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                  ${isDark 
                    ? 'bg-gray-800 border-gray-700 text-white placeholder-gray-400' 
                    : 'bg-white border-gray-300 text-gray-900 placeholder-gray-500'
                  }
                `}
              />
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

        {/* Form */}
        {showForm && (
          <div className={`mb-8 p-6 rounded-lg shadow-sm border ${isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'}`}>
            <h3 className={`text-lg font-medium mb-4 ${isDark ? 'text-white' : 'text-gray-900'}`}>
              {editingTag ? 'Modifier le tag' : 'Créer un nouveau tag'}
            </h3>
            
            <form onSubmit={handleSubmit} className="space-y-4">
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label className={`block text-sm font-medium mb-2 ${isDark ? 'text-gray-300' : 'text-gray-700'}`}>
                    Nom du tag *
                  </label>
                  <input
                    type="text"
                    name="name"
                    value={formData.name}
                    onChange={handleInputChange}
                    placeholder="Ex: Urgent, Design, Bug..."
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

                <div>
                  <label className={`block text-sm font-medium mb-2 ${isDark ? 'text-gray-300' : 'text-gray-700'}`}>
                    Couleur
                  </label>
                  <div className="flex items-center space-x-2">
                    <input
                      type="color"
                      name="color"
                      value={formData.color}
                      onChange={handleInputChange}
                      className="h-10 w-16 border rounded-lg cursor-pointer"
                    />
                    <div className="flex flex-wrap gap-1">
                      {predefinedColors.map(color => (
                        <button
                          key={color}
                          type="button"
                          onClick={() => setFormData(prev => ({ ...prev, color }))}
                          className={`
                            w-6 h-6 rounded-full border-2 transition-all
                            ${formData.color === color ? 'border-gray-400 scale-110' : 'border-transparent hover:scale-105'}
                          `}
                          style={{ backgroundColor: color }}
                        />
                      ))}
                    </div>
                  </div>
                </div>
              </div>

              <div>
                <label className={`block text-sm font-medium mb-2 ${isDark ? 'text-gray-300' : 'text-gray-700'}`}>
                  Description (optionnelle)
                </label>
                <textarea
                  name="description"
                  value={formData.description}
                  onChange={handleInputChange}
                  rows={2}
                  placeholder="Description du tag..."
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
                  onClick={resetForm}
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
                  disabled={loading}
                  className="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors disabled:opacity-50"
                >
                  {loading ? 'Sauvegarde...' : (editingTag ? 'Mettre à jour' : 'Créer')}
                </button>
              </div>
            </form>
          </div>
        )}

        {/* Tags Grid */}
        {filteredTags.length === 0 ? (
          <div className={`text-center py-12 ${isDark ? 'bg-gray-800' : 'bg-white'} rounded-lg shadow-sm`}>
            <TagIcon className={`mx-auto h-12 w-12 ${isDark ? 'text-gray-600' : 'text-gray-400'}`} />
            <h3 className={`mt-2 text-sm font-medium ${isDark ? 'text-gray-300' : 'text-gray-900'}`}>
              {searchTerm ? 'Aucun tag trouvé' : 'Aucun tag'}
            </h3>
            <p className={`mt-1 text-sm ${isDark ? 'text-gray-500' : 'text-gray-500'}`}>
              {searchTerm 
                ? 'Essayez avec d\'autres termes de recherche.'
                : 'Commencez par créer votre premier tag pour organiser vos tâches.'
              }
            </p>
            {!searchTerm && (
              <div className="mt-6">
                <button
                  onClick={() => setShowForm(true)}
                  className="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-medium rounded-lg transition-colors duration-200"
                >
                  <PlusIcon className="h-5 w-5 mr-2" />
                  Créer un tag
                </button>
              </div>
            )}
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            {filteredTags.map(tag => (
              <div
                key={tag.id}
                className={`
                  relative p-4 rounded-lg shadow-sm border transition-all duration-200 hover:shadow-md
                  ${isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'}
                `}
              >
                <div className="flex items-center justify-between mb-3">
                  <div className="flex items-center space-x-3">
                    <div 
                      className="w-4 h-4 rounded-full"
                      style={{ backgroundColor: tag.color }}
                    />
                    <h3 className={`font-medium ${isDark ? 'text-white' : 'text-gray-900'}`}>
                      {tag.name}
                    </h3>
                  </div>
                  
                  <div className="flex items-center space-x-1">
                    <button
                      onClick={() => handleEdit(tag)}
                      className={`
                        p-1 rounded hover:bg-gray-100 transition-colors
                        ${isDark ? 'hover:bg-gray-700 text-gray-400' : 'text-gray-500'}
                      `}
                    >
                      <PencilIcon className="h-4 w-4" />
                    </button>
                    <button
                      onClick={() => handleDelete(tag)}
                      className="p-1 rounded hover:bg-red-50 text-red-500 hover:text-red-700 transition-colors"
                    >
                      <TrashIcon className="h-4 w-4" />
                    </button>
                  </div>
                </div>

                {tag.description && (
                  <p className={`text-sm mb-3 ${isDark ? 'text-gray-400' : 'text-gray-600'}`}>
                    {tag.description}
                  </p>
                )}

                <div className="flex items-center justify-between text-sm">
                  <span className={`${isDark ? 'text-gray-400' : 'text-gray-500'}`}>
                    {tag.tasks_count || 0} tâche{(tag.tasks_count || 0) !== 1 ? 's' : ''}
                  </span>
                  <span
                    className="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium text-white"
                    style={{ backgroundColor: tag.color }}
                  >
                    {tag.name}
                  </span>
                </div>
              </div>
            ))}
          </div>
        )}
      </div>
    </div>
  );
};

export default TagManager;