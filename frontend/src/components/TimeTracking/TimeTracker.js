import React, { useState, useEffect, useRef } from 'react';
import { Play, Pause, Square } from 'lucide-react';
import timeService from '../../services/timeService';
import toast from 'react-hot-toast';

const TimeTracker = ({ taskId }) => {
  const [isRunning, setIsRunning] = useState(false);
  const [activeEntry, setActiveEntry] = useState(null);
  const [elapsedTime, setElapsedTime] = useState(0);
  const intervalRef = useRef(null);

  useEffect(() => {
    checkActiveTimer();
    return () => {
      if (intervalRef.current) {
        clearInterval(intervalRef.current);
      }
    };
  }, [taskId]);

  useEffect(() => {
    if (isRunning && activeEntry) {
      intervalRef.current = setInterval(() => {
        const startTime = new Date(activeEntry.start_time).getTime();
        const now = Date.now();
        setElapsedTime(Math.floor((now - startTime) / 1000));
      }, 1000);
    } else {
      if (intervalRef.current) {
        clearInterval(intervalRef.current);
      }
    }
  }, [isRunning, activeEntry]);

  const checkActiveTimer = async () => {
    const entries = await timeService.getAllTimeEntries({ task_id: taskId });
    if (entries.success) {
      const active = entries.data.find(entry => !entry.end_time);
      if (active) {
        setActiveEntry(active);
        setIsRunning(true);
        const startTime = new Date(active.start_time).getTime();
        const now = Date.now();
        setElapsedTime(Math.floor((now - startTime) / 1000));
      }
    }
  };

  const handleStart = async () => {
    try {
      const result = await timeService.startTimer(taskId);
      if (result.success) {
        setActiveEntry(result.data);
        setIsRunning(true);
        setElapsedTime(0);
        toast.success('Chronomètre démarré');
      } else {
        toast.error(result.message);
      }
    } catch (error) {
      toast.error('Erreur lors du démarrage');
    }
  };

  const handleStop = async () => {
    if (!activeEntry) return;

    try {
      const result = await timeService.stopTimer(activeEntry.id);
      if (result.success) {
        setIsRunning(false);
        setActiveEntry(null);
        setElapsedTime(0);
        toast.success('Chronomètre arrêté');
      } else {
        toast.error(result.message);
      }
    } catch (error) {
      toast.error('Erreur lors de l\'arrêt');
    }
  };

  const formatTime = (seconds) => {
    const hours = Math.floor(seconds / 3600);
    const minutes = Math.floor((seconds % 3600) / 60);
    const secs = seconds % 60;

    return `${hours.toString().padStart(2, '0')}:${minutes
      .toString()
      .padStart(2, '0')}:${secs.toString().padStart(2, '0')}`;
  };

  return (
    <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
      <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-4">
        Suivi du temps
      </h3>

      <div className="text-center">
        <div className="text-4xl font-mono text-gray-900 dark:text-white mb-6">
          {formatTime(elapsedTime)}
        </div>

        <div className="flex justify-center space-x-4">
          {!isRunning ? (
            <button
              onClick={handleStart}
              className="inline-flex items-center px-4 py-2 bg-green-600 text-white rounded-md hover:bg-green-700"
            >
              <Play className="h-4 w-4 mr-2" />
              Démarrer
            </button>
          ) : (
            <>
              <button
                onClick={handleStop}
                className="inline-flex items-center px-4 py-2 bg-red-600 text-white rounded-md hover:bg-red-700"
              >
                <Square className="h-4 w-4 mr-2" />
                Arrêter
              </button>
            </>
          )}
        </div>
      </div>

      <div className="mt-6 pt-6 border-t border-gray-200 dark:border-gray-700">
        <p className="text-sm text-gray-500 dark:text-gray-400 text-center">
          {isRunning
            ? 'Chronomètre en cours...'
            : 'Cliquez sur Démarrer pour commencer'}
        </p>
      </div>
    </div>
  );
};

export default TimeTracker;