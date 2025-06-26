import React, { useState, useEffect } from 'react';
import { useNavigate, useParams } from 'react-router-dom';
import { useForm, Controller } from 'react-hook-form';
import { Save, X } from 'lucide-react';
import taskService from '../../services/taskService';
import tagService from '../../services/tagService';
import toast from 'react-hot-toast';

const TaskForm = () => {
  const navigate = useNavigate();
  const { id } = useParams();
  const isEditMode = !!id;
  
  const [loading, setLoading] = useState(false);
  const [tags, setTags] = useState([]);
  const [selectedTags, setSelectedTags] = useState([]);

  const {
    register,
    handleSubmit,
    control,
    formState: { errors },
    reset,
  } = useForm({
    defaultValues: {
      title: '',
      description: '',
      status: 'pending',
      priority: 'medium',
      due_date: '',
      estimated_hours: '',
      tags: [],
    },
  });

  useEffect(() => {
    fetchTags();
    if (isEditMode) {
      fetchTask();
    }
  }, [id]);

  const fetchTags = async () => {
    const result = await tagService.getAllTags();
    if (result.success) {
      setTags(result.data);
    }
  };

  const fetchTask = async () => {
    setLoading(true);
    try {
      const result = await taskService.getTaskById(id);
      if (result.success) {
        const task = result.data;
        reset({
          title: task.title,
          description: task.description || '',
          status: task.status,
          priority: task.priority,
          due_date: task.due_date ? task.due_date.split('T')[0] : '',
          estimated_hours: task.estimated_hours || '',
        });
        
        if (task.tags) {
          setSelectedTags(task.tags.map(tag => tag.id));
        }
      } else {
        toast.error('Tâche non trouvée');
        navigate('/tasks');
      }
    } catch (error) {
      toast.error('Erreur lors du chargement');
      navigate('/tasks');
    } finally {
      setLoading(false);
    }
  };

  const onSubmit = async (data) => {
    setLoading(true);
    try {
      const taskData = {
        ...data,
        tags: selectedTags,
      };

      const result = isEditMode
        ? await taskService.updateTask(id, taskData)
        : await taskService.createTask(taskData);

      if (result.success) {
        toast.success(isEditMode ? 'Tâche mise à jour' : 'Tâche créée');
        navigate(`/tasks/${result.data?.id || id}`);
      } else {
        toast.error(result.message);
      }
    } catch (error) {
      toast.error('Une erreur est survenue');
    } finally {
      setLoading(false);
    }
  };

  const handleTagToggle = (tagId) => {
    setSelectedTags(prev =>
      prev.includes(tagId)
        ? prev.filter(id => id !== tagId)
        : [...prev, tagId]
    );
  };

  return (
    <div className="max-w-4xl mx-auto">
      <div className="mb-6">
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
          {isEditMode ? 'Modifier la tâche' : 'Nouvelle tâche'}
        </h1>
      </div>

      <form onSubmit={handleSubmit(onSubmit)} className="space-y-6">
        <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
          <div className="space-y-4">
            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Titre *
              </label>
              <input
                {...register('title', {
                  required: 'Le titre est obligatoire',
                  maxLength: { value: 200, message: 'Maximum 200 caractères' },
                })}
                type="text"
                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                placeholder="Entrez le titre de la tâche"
              />
              {errors.title && (
                <p className="mt-1 text-sm text-red-600">{errors.title.message}</p>
              )}
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                Description
              </label>
              <textarea
                {...register('description', {
                  maxLength: { value: 1000, message: 'Maximum 1000 caractères' },
                })}
                rows={4}
                className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                placeholder="Décrivez la tâche..."
              />
              {errors.description && (
                <p className="mt-1 text-sm text-red-600">{errors.description.message}</p>
              )}
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Statut
                </label>
                <select
                  {...register('status')}
                  className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                >
                  <option value="pending">À faire</option>
                  <option value="in_progress">En cours</option>
                  <option value="completed">Terminée</option>
                  <option value="archived">Archivée</option>
                </select>
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Priorité
                </label>
                <select
                  {...register('priority')}
                  className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                >
                  <option value="low">Faible</option>
                  <option value="medium">Moyenne</option>
                  <option value="high">Haute</option>
                  <option value="urgent">Urgente</option>
                </select>
              </div>
            </div>

            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Date d'échéance
                </label>
                <input
                  {...register('due_date')}
                  type="date"
                  className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                />
              </div>

              <div>
                <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                  Heures estimées
                </label>
                <input
                  {...register('estimated_hours', {
                    pattern: {
                      value: /^[0-9]+(\.[0-9]{1,2})?$/,
                      message: 'Format invalide (ex: 2.5)',
                    },
                  })}
                  type="number"  // Changez "text" en "number"
                  step="0.01"   // Permet les décimales
                  className="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-md focus:ring-primary-500 focus:border-primary-500 dark:bg-gray-700 dark:text-white"
                  placeholder="2.5"
                />
                  
                {errors.estimated_hours && (
                  <p className="mt-1 text-sm text-red-600">{errors.estimated_hours.message}</p>
                )}
              </div>
            </div>

            <div>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                Tags
              </label>
              <div className="flex flex-wrap gap-2">
                {tags.map((tag) => (
                  <button
                    key={tag.id}
                    type="button"
                    onClick={() => handleTagToggle(tag.id)}
                    className={`px-3 py-1 rounded-full text-sm transition-all ${
                      selectedTags.includes(tag.id)
                        ? 'ring-2 ring-offset-2 ring-primary-500'
                        : ''
                    }`}
                    style={{
                      backgroundColor: selectedTags.includes(tag.id)
                        ? tag.color
                        : `${tag.color}20`,
                      color: selectedTags.includes(tag.id) ? '#fff' : tag.color,
                    }}
                  >
                    {tag.name}
                  </button>
                ))}
              </div>
            </div>
          </div>
        </div>

        <div className="flex justify-end space-x-4">
          <button
            type="button"
            onClick={() => navigate('/tasks')}
            className="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700"
          >
            <X className="h-4 w-4 mr-2 inline" />
            Annuler
          </button>
          <button
            type="submit"
            disabled={loading}
            className="px-4 py-2 bg-primary-600 text-white rounded-md hover:bg-primary-700 disabled:opacity-50"
          >
            <Save className="h-4 w-4 mr-2 inline" />
            {loading ? 'Enregistrement...' : 'Enregistrer'}
          </button>
        </div>
      </form>
    </div>
  );
};

export default TaskForm;
