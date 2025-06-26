import React, { useState, useEffect } from 'react';
import { useAuth } from '../../contexts/AuthContext';
import { useTheme } from '../../contexts/ThemeContext';
import LoadingSpinner from '../Common/LoadingSpinner';
import ErrorMessage from '../Common/ErrorMessage';
import toast from 'react-hot-toast';
import api from '../../services/api';

const UserManagement = () => {
    const { user } = useAuth();
    const { isDark } = useTheme();
    const [users, setUsers] = useState([]);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);

    useEffect(() => {
        fetchUsers();
    }, []);

    const fetchUsers = async () => {
        try {
            setLoading(true);
            const response = await api.get('/users');
            if (response.data.success) {
                setUsers(response.data.data);
            } else {
                throw new Error(response.data.message);
            }
        } catch (err) {
            setError(err.message || 'Erreur lors du chargement des utilisateurs.');
        } finally {
            setLoading(false);
        }
    };

    const handleRoleChange = async (userId, newRole) => {
        try {
            const response = await api.post('/users/update-role', { user_id: userId, role: newRole });
            if (response.data.success) {
                toast.success('Rôle mis à jour avec succès.');
                fetchUsers(); // Recharger la liste
            } else {
                throw new Error(response.data.message);
            }
        } catch (err) {
            toast.error(err.message || 'Erreur lors de la mise à jour.');
        }
    };
    
    if (loading) return <LoadingSpinner text="Chargement des utilisateurs..." />;

    return (
        <div className={`min-h-screen ${isDark ? 'bg-gray-900' : 'bg-gray-50'}`}>
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
                <h1 className={`text-3xl font-bold mb-6 ${isDark ? 'text-white' : 'text-gray-900'}`}>
                    Gestion des Utilisateurs
                </h1>
                
                {error && <ErrorMessage message={error} onClose={() => setError(null)} />}

                <div className="overflow-x-auto shadow-md sm:rounded-lg">
                    <table className="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead className={isDark ? 'bg-gray-700' : 'bg-gray-50'}>
                            <tr>
                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Utilisateur</th>
                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Rôle</th>
                                <th scope="col" className="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Statut</th>
                            </tr>
                        </thead>
                        <tbody className={`divide-y ${isDark ? 'divide-gray-700 bg-gray-800' : 'divide-gray-200 bg-white'}`}>
                            {users.map((u) => (
                                <tr key={u.id}>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <div className="text-sm font-medium text-gray-900 dark:text-white">{u.username}</div>
                                        <div className="text-sm text-gray-500 dark:text-gray-400">{u.email}</div>
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        {u.id === user.id ? (
                                            <span className="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-blue-100 text-blue-800">{u.role}</span>
                                        ) : (
                                            <select
                                                value={u.role}
                                                onChange={(e) => handleRoleChange(u.id, e.target.value)}
                                                className={`block w-full pl-3 pr-10 py-2 text-base border-gray-300 focus:outline-none focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm rounded-md ${isDark ? 'bg-gray-700 border-gray-600' : ''}`}
                                            >
                                                <option value="user">Utilisateur</option>
                                                <option value="manager">Manager</option>
                                                <option value="admin">Admin</option>
                                            </select>
                                        )}
                                    </td>
                                    <td className="px-6 py-4 whitespace-nowrap">
                                        <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full ${u.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-yellow-100 text-yellow-800'}`}>
                                            {u.status}
                                        </span>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    );
};

export default UserManagement;
