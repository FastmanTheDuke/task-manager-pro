import React, { useState, useEffect } from 'react';
import { Clock, Calendar, Trash2 } from 'lucide-react';
import { format } from 'date-fns';
import { fr } from 'date-fns/locale';
import timeService from '../../services/timeService';
import toast from 'react-hot-toast';

const TimeEntryList = ({ taskId }) => {
  const [entries, setEntries] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchEntries();
  }, [taskId]);

  const fetchEntries = async () => {
    setLoading(true);
    try {
      const result = await timeService.getAllTimeEntries({ task_id: taskId });
      if (result.success) {
        setEntries(result.data);
      }
    } catch (error) {
      toast.error('Erreur lors du chargement');
    } finally {
      setLoading(false);
    }
  };

  const handleDelete = async (entryId) => {
    if (!window.confirm('Êtes-vous sûr de vouloir supprimer cette entrée ?')) {
      return;
    }

    try {
      const result = await timeService.deleteTimeEntry(entryId);
      if (result.success) {
        setEntries(entries.filter(entry => entry.id !== entryId));
        toast.success('Entrée supprimée');
      } else {
        toast.error(result.message);
      }
    } catch (error) {
      toast.error('Erreur lors de la suppression');
    }
  };

  const formatDuration = (seconds) => {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    return `${hours}h ${minutes}m`;
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-32">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  if (entries.length === 0) {
    return (
      <p className="text-center text-gray-500 dark:text-gray-400 py-8">
        Aucune entrée de temps
      </p>
    );
  }

  return (
    <div className="space-y-3">
      {entries.map((entry) => (
        <div
          key={entry.id}
          className="flex items-center justify-between p-4 bg-gray-50 dark:bg-gray-700 rounded-lg"
        >
          <div className="flex-1">
            <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
              <Calendar className="h-4 w-4 mr-1" />
              {format(new Date(entry.start_time), 'dd MMM yyyy', { locale: fr })}
              <span className="mx-2">•</span>
              <Clock className="h-4 w-4 mr-1" />
              {format(new Date(entry.start_time), 'HH:mm')} -{' '}
              {entry.end_time
                ? format(new Date(entry.end_time), 'HH:mm')
                : 'En cours'}
            </div>
            {entry.description && (
              <p className="text-sm text-gray-900 dark:text-white mt-1">
                {entry.description}
              </p>
            )}
          </div>
          <div className="flex items-center space-x-4">
            <span className="text-lg font-medium text-gray-900 dark:text-white">
              {entry.duration ? formatDuration(entry.duration) : '-'}
            </span>
            {entry.end_time && (
              <button
                onClick={() => handleDelete(entry.id)}
                className="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
              >
                <Trash2 className="h-4 w-4" />
              </button>
            )}
          </div>
        </div>
      ))}
    </div>
  );
};

export default TimeEntryList;