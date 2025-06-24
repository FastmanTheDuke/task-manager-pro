import React from 'react';
import {
  MagnifyingGlassIcon,
  FunnelIcon,
  XMarkIcon
} from '@heroicons/react/24/outline';

const ProjectFilters = ({ filters, onFilterChange, isDark }) => {
  const handleSearchChange = (e) => {
    onFilterChange({ search: e.target.value });
  };

  const handleStatusChange = (e) => {
    onFilterChange({ status: e.target.value });
  };

  const handleRoleChange = (e) => {
    onFilterChange({ role: e.target.value });
  };

  const handleSortChange = (e) => {
    const [sortBy, sortOrder] = e.target.value.split('-');
    onFilterChange({ sortBy, sortOrder });
  };

  const clearFilters = () => {
    onFilterChange({
      search: '',
      status: 'all',
      role: 'all',
      sortBy: 'updated_at',
      sortOrder: 'desc'
    });
  };

  const hasActiveFilters = filters.search || filters.status !== 'all' || filters.role !== 'all';

  return (
    <div className={`
      p-4 rounded-lg shadow-sm border mb-6
      ${isDark ? 'bg-gray-800 border-gray-700' : 'bg-white border-gray-200'}
    `}>
      <div className="flex flex-col lg:flex-row gap-4">
        {/* Search */}
        <div className="flex-1">
          <div className="relative">
            <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
              <MagnifyingGlassIcon className={`h-5 w-5 ${isDark ? 'text-gray-400' : 'text-gray-400'}`} />
            </div>
            <input
              type="text"
              placeholder="Rechercher des projets..."
              value={filters.search}
              onChange={handleSearchChange}
              className={`
                block w-full pl-10 pr-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500
                ${isDark 
                  ? 'bg-gray-700 border-gray-600 text-white placeholder-gray-400' 
                  : 'bg-white border-gray-300 text-gray-900 placeholder-gray-500'
                }
              `}
            />
          </div>
        </div>

        {/* Status Filter */}
        <div className="lg:w-48">
          <select
            value={filters.status}
            onChange={handleStatusChange}
            className={`
              block w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500
              ${isDark 
                ? 'bg-gray-700 border-gray-600 text-white' 
                : 'bg-white border-gray-300 text-gray-900'
              }
            `}
          >
            <option value="all">Tous les statuts</option>
            <option value="active">Actifs</option>
            <option value="completed">Terminés</option>
            <option value="on_hold">En pause</option>
            <option value="cancelled">Annulés</option>
          </select>
        </div>

        {/* Role Filter */}
        <div className="lg:w-48">
          <select
            value={filters.role}
            onChange={handleRoleChange}
            className={`
              block w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500
              ${isDark 
                ? 'bg-gray-700 border-gray-600 text-white' 
                : 'bg-white border-gray-300 text-gray-900'
              }
            `}
          >
            <option value="all">Tous les rôles</option>
            <option value="owner">Propriétaire</option>
            <option value="admin">Administrateur</option>
            <option value="member">Membre</option>
            <option value="viewer">Observateur</option>
          </select>
        </div>

        {/* Sort Options */}
        <div className="lg:w-56">
          <select
            value={`${filters.sortBy}-${filters.sortOrder}`}
            onChange={handleSortChange}
            className={`
              block w-full px-3 py-2 border rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500
              ${isDark 
                ? 'bg-gray-700 border-gray-600 text-white' 
                : 'bg-white border-gray-300 text-gray-900'
              }
            `}
          >
            <option value="updated_at-desc">Récemment modifiés</option>
            <option value="updated_at-asc">Anciennement modifiés</option>
            <option value="created_at-desc">Récemment créés</option>
            <option value="created_at-asc">Anciennement créés</option>
            <option value="name-asc">Nom (A-Z)</option>
            <option value="name-desc">Nom (Z-A)</option>
            <option value="due_date-asc">Échéance (proche)</option>
            <option value="due_date-desc">Échéance (éloignée)</option>
            <option value="completion_percentage-desc">Progression (élevée)</option>
            <option value="completion_percentage-asc">Progression (faible)</option>
          </select>
        </div>

        {/* Clear Filters */}
        {hasActiveFilters && (
          <button
            onClick={clearFilters}
            className={`
              flex items-center px-4 py-2 text-sm font-medium rounded-lg border transition-colors
              ${isDark 
                ? 'border-gray-600 text-gray-300 hover:bg-gray-700' 
                : 'border-gray-300 text-gray-700 hover:bg-gray-50'
              }
            `}
          >
            <XMarkIcon className="h-4 w-4 mr-2" />
            Effacer
          </button>
        )}
      </div>

      {/* Active Filters Summary */}
      {hasActiveFilters && (
        <div className="mt-3 flex flex-wrap gap-2">
          {filters.search && (
            <span className={`
              inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
              ${isDark ? 'bg-blue-900 text-blue-200' : 'bg-blue-100 text-blue-800'}
            `}>
              Recherche: "{filters.search}"
            </span>
          )}
          {filters.status !== 'all' && (
            <span className={`
              inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
              ${isDark ? 'bg-green-900 text-green-200' : 'bg-green-100 text-green-800'}
            `}>
              Statut: {filters.status}
            </span>
          )}
          {filters.role !== 'all' && (
            <span className={`
              inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
              ${isDark ? 'bg-purple-900 text-purple-200' : 'bg-purple-100 text-purple-800'}
            `}>
              Rôle: {filters.role}
            </span>
          )}
        </div>
      )}
    </div>
  );
};

export default ProjectFilters;