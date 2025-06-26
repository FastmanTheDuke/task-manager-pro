import React from 'react';
import { Link, useLocation } from 'react-router-dom';
import {
  HomeIcon,
  ClipboardDocumentListIcon,
  FolderIcon,
  TagIcon,
  ClockIcon,
  CalendarIcon,
  ChartBarIcon,
  UserIcon,
  Cog6ToothIcon,
  XMarkIcon
} from '@heroicons/react/24/outline';
import { useAuth } from '../../contexts/AuthContext';
import { ShieldCheckIcon } from '@heroicons/react/24/outline'; // Ajoutez cette icône

const Sidebar = ({ open, onClose }) => {
  const location = useLocation();
  const { user } = useAuth();
  
  const navigation = [
    {
      name: 'Tableau de bord',
      href: '/',
      icon: HomeIcon,
      current: location.pathname === '/'
    },
    {
      name: 'Mes tâches',
      href: '/tasks',
      icon: ClipboardDocumentListIcon,
      current: location.pathname.startsWith('/tasks')
    },
    {
      name: 'Projets',
      href: '/projects',
      icon: FolderIcon,
      current: location.pathname.startsWith('/projects')
    },
    {
      name: 'Tags',
      href: '/tags',
      icon: TagIcon,
      current: location.pathname.startsWith('/tags')
    },
    {
      name: 'Suivi du temps',
      href: '/time-tracking',
      icon: ClockIcon,
      current: location.pathname.startsWith('/time-tracking')
    },
    {
      name: 'Calendrier',
      href: '/calendar',
      icon: CalendarIcon,
      current: location.pathname.startsWith('/calendar')
    },
    {
      name: 'Rapports',
      href: '/reports',
      icon: ChartBarIcon,
      current: location.pathname.startsWith('/reports')
    }
  ];

  const userNavigation = [
    {
      name: 'Profil',
      href: '/profile',
      icon: UserIcon,
      current: location.pathname === '/profile'
    },
    {
      name: 'Paramètres',
      href: '/settings',
      icon: Cog6ToothIcon,
      current: location.pathname === '/settings'
    }
  ];
  const adminNavigation = [
      {
          name: 'Gestion Utilisateurs',
          href: '/admin/users',
          icon: ShieldCheckIcon,
          current: location.pathname.startsWith('/admin')
      }
  ];
  return (
    <>
      {/* Overlay mobile */}
      {open && (
        <div className="lg:hidden fixed inset-0 bg-black bg-opacity-50 z-40" onClick={onClose}></div>
      )}
      
      {/* Sidebar */}
      <div className={`
        fixed inset-y-0 left-0 z-50 w-64 bg-white dark:bg-gray-800 shadow-lg transform transition-transform duration-300 ease-in-out
        lg:translate-x-0 lg:static lg:inset-0 lg:z-0
        ${open ? 'translate-x-0' : '-translate-x-full'}
      `}>
        <div className="flex flex-col h-full">
          {/* Header */}
          <div className="flex items-center justify-between p-4 border-b border-gray-200 dark:border-gray-700">
            <div className="flex items-center space-x-2">
              <div className="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                <ClipboardDocumentListIcon className="w-5 h-5 text-white" />
              </div>
              <h1 className="text-lg font-bold text-gray-900 dark:text-white">
                Task Manager
              </h1>
            </div>
            
            {/* Bouton fermer mobile */}
            <button
              onClick={onClose}
              className="lg:hidden p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 dark:hover:bg-gray-700"
            >
              <XMarkIcon className="w-5 h-5" />
            </button>
          </div>

          {/* User info */}
          {user && (
            <div className="p-4 border-b border-gray-200 dark:border-gray-700">
              <div className="flex items-center space-x-3">
                <div className="w-10 h-10 bg-gray-300 rounded-full flex items-center justify-center">
                  <UserIcon className="w-6 h-6 text-gray-600" />
                </div>
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-medium text-gray-900 dark:text-white truncate">
                    {user.first_name} {user.last_name}
                  </p>
                  <p className="text-sm text-gray-500 dark:text-gray-400 truncate">
                    {user.email}
                  </p>
                </div>
              </div>
            </div>
          )}

          {/* Navigation */}
          <nav className="flex-1 p-4 space-y-1 overflow-y-auto">
            {/* Navigation principale */}
            <div className="space-y-1">
              {navigation.map((item) => {
                const Icon = item.icon;
                return (
                  <Link
                    key={item.name}
                    to={item.href}
                    onClick={() => onClose()}
                    className={`
                      group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200
                      ${
                        item.current
                          ? 'bg-blue-50 text-blue-700 dark:bg-blue-900 dark:text-blue-200'
                          : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white'
                      }
                    `}
                  >
                    <Icon
                      className={`
                        mr-3 w-5 h-5 flex-shrink-0
                        ${
                          item.current
                            ? 'text-blue-500 dark:text-blue-300'
                            : 'text-gray-400 group-hover:text-gray-500 dark:text-gray-400 dark:group-hover:text-gray-300'
                        }
                      `}
                    />
                    {item.name}
                  </Link>
                );
              })}
            </div>
            {/* AJOUTER CETTE SECTION */}
            {user && user.role === 'admin' && (
                <>
                    <div className="border-t border-gray-200 dark:border-gray-700 my-4"></div>
                    <div className="space-y-1">
                        <p className="px-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                            Administration
                        </p>
                        {adminNavigation.map((item) => {
                            const Icon = item.icon;
                            return (
                                <Link
                                    key={item.name}
                                    to={item.href}
                                    onClick={() => onClose()}
                                    className={`
                                        group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200
                                        ${
                                            item.current
                                            ? 'bg-blue-50 text-blue-700 dark:bg-blue-900 dark:text-blue-200'
                                            : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white'
                                        }
                                    `}
                                >
                                    <Icon
                                        className={`
                                            mr-3 w-5 h-5 flex-shrink-0
                                            ${
                                                item.current
                                                ? 'text-blue-500 dark:text-blue-300'
                                                : 'text-gray-400 group-hover:text-gray-500 dark:text-gray-400 dark:group-hover:text-gray-300'
                                            }
                                        `}
                                    />
                                    {item.name}
                                </Link>
                            );
                        })}
                    </div>
                </>
            )}

            {/* Divider */}
            <div className="border-t border-gray-200 dark:border-gray-700 my-4"></div>

            {/* Navigation utilisateur */}
            <div className="space-y-1">
              <p className="px-3 text-xs font-semibold text-gray-500 dark:text-gray-400 uppercase tracking-wider">
                Compte
              </p>
              {userNavigation.map((item) => {
                const Icon = item.icon;
                return (
                  <Link
                    key={item.name}
                    to={item.href}
                    onClick={() => onClose()}
                    className={`
                      group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors duration-200
                      ${
                        item.current
                          ? 'bg-blue-50 text-blue-700 dark:bg-blue-900 dark:text-blue-200'
                          : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white'
                      }
                    `}
                  >
                    <Icon
                      className={`
                        mr-3 w-5 h-5 flex-shrink-0
                        ${
                          item.current
                            ? 'text-blue-500 dark:text-blue-300'
                            : 'text-gray-400 group-hover:text-gray-500 dark:text-gray-400 dark:group-hover:text-gray-300'
                        }
                      `}
                    />
                    {item.name}
                  </Link>
                );
              })}
            </div>
          </nav>

          {/* Footer */}
          <div className="p-4 border-t border-gray-200 dark:border-gray-700">
            <p className="text-xs text-gray-500 dark:text-gray-400 text-center">
              Task Manager Pro v1.0
            </p>
          </div>
        </div>
      </div>
    </>
  );
};

export default Sidebar;
