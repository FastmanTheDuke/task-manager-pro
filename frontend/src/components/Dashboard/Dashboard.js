import React, { useState, useEffect } from 'react';
import { Link } from 'react-router-dom';
import {
  CheckSquare,
  Clock,
  TrendingUp,
  Calendar,
  Plus,
  ArrowRight,
} from 'lucide-react';
import taskService from '../../services/taskService';
import timeService from '../../services/timeService';
import { useAuth } from '../../contexts/AuthContext';

const Dashboard = () => {
  const { user } = useAuth();
  const [stats, setStats] = useState({
    totalTasks: 0,
    completedTasks: 0,
    inProgressTasks: 0,
    totalHours: 0,
  });
  const [recentTasks, setRecentTasks] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchDashboardData();
  }, []);

  const fetchDashboardData = async () => {
    setLoading(true);
    try {
      // Récupérer les tâches
      const tasksResult = await taskService.getAllTasks({}, 1, 5);
      if (tasksResult.success) {
        setRecentTasks(tasksResult.data);
        
        // Calculer les statistiques
        const allTasksResult = await taskService.getAllTasks({}, 1, 100);
        if (allTasksResult.success) {
          const tasks = allTasksResult.data;
          setStats({
            totalTasks: tasks.length,
            completedTasks: tasks.filter(t => t.status === 'completed').length,
            inProgressTasks: tasks.filter(t => t.status === 'in_progress').length,
            totalHours: 0, // À implémenter avec les time entries
          });
        }
      }
    } catch (error) {
      console.error('Erreur:', error);
    } finally {
      setLoading(false);
    }
  };

  const StatCard = ({ icon: Icon, title, value, color }) => (
    <div className="bg-white dark:bg-gray-800 rounded-lg shadow p-6">
      <div className="flex items-center justify-between">
        <div>
          <p className="text-sm text-gray-600 dark:text-gray-400">{title}</p>
          <p className="text-2xl font-bold text-gray-900 dark:text-white mt-1">
            {value}
          </p>
        </div>
        <div className={`p-3 rounded-full ${color}`}>
          <Icon className="h-6 w-6 text-white" />
        </div>
      </div>
    </div>
  );

  if (loading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-12 w-12 border-b-2 border-primary-600"></div>
      </div>
    );
  }

  return (
    <div>
      <div className="mb-8">
        <h1 className="text-3xl font-bold text-gray-900 dark:text-white">
          Bonjour, {user?.first_name || user?.username} 👋
        </h1>
        <p className="text-gray-600 dark:text-gray-400 mt-2">
          Voici un aperçu de votre activité
        </p>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
        <StatCard
          icon={CheckSquare}
          title="Tâches totales"
          value={stats.totalTasks}
          color="bg-blue-600"
        />
        <StatCard
          icon={TrendingUp}
          title="En cours"
          value={stats.inProgressTasks}
          color="bg-yellow-600"
        />
        <StatCard
          icon={CheckSquare}
          title="Terminées"
          value={stats.completedTasks}
          color="bg-green-600"
        />
        <StatCard
          icon={Clock}
          title="Heures travaillées"
          value={`${stats.totalHours}h`}
          color="bg-purple-600"
        />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <div className="bg-white dark:bg-gray-800 rounded-lg shadow">
          <div className="p-6 border-b border-gray-200 dark:border-gray-700">
            <div className="flex items-center justify-between">
              <h2 className="text-lg font-medium text-gray-900 dark:text-white">
                Tâches récentes
              </h2>
              <Link
                to="/tasks/new"
                className="text-primary-600 hover:text-primary-700 text-sm font-medium"
              >
                <Plus className="h-4 w-4 inline mr-1" />
                Nouvelle
              </Link>
            </div>
          </div>
          <div className="p-6">
            {recentTasks.length === 0 ? (
              <p className="text-center text-gray-500 dark:text-gray-400 py-8">
                Aucune tâche pour le moment
              </p>
            ) : (
              <div className="space-y-4">
                {recentTasks.map((task) => (
                  <Link
                    key={task.id}
                    to={`/tasks/${task.id}`}
                    className="block p-4 bg-gray-50 dark:bg-gray-700 rounded-lg hover:bg-gray-100 dark:hover:bg-gray-600 transition"
                  >
                    <div className="flex items-center justify-between">
                      <div>
                        <h3 className="font-medium text-gray-900 dark:text-white">
                          {task.title}
                        </h3>
                        <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                          {task.status === 'completed' ? 'Terminée' : 'En cours'}
                        </p>
                      </div>
                      <ArrowRight className="h-5 w-5 text-gray-400" />
                    </div>
                  </Link>
                ))}
              </div>
            )}
          </div>
        </div>

        <div className="bg-white dark:bg-gray-800 rounded-lg shadow">
          <div className="p-6 border-b border-gray-200 dark:border-gray-700">
            <h2 className="text-lg font-medium text-gray-900 dark:text-white">
              Activité récente
            </h2>
          </div>
          <div className="p-6">
            <p className="text-center text-gray-500 dark:text-gray-400 py-8">
              Aucune activité récente
            </p>
          </div>
        </div>
      </div>
    </div>
  );
};

export default Dashboard;