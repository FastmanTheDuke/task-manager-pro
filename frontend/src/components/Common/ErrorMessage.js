import React from 'react';
import { XMarkIcon, ExclamationTriangleIcon } from '@heroicons/react/24/outline';
import { useTheme } from '../../contexts/ThemeContext';

const ErrorMessage = ({ 
  message, 
  onClose, 
  type = 'error', 
  className = '',
  closable = true 
}) => {
  const { isDark } = useTheme();
  
  const typeStyles = {
    error: {
      bg: isDark ? 'bg-red-900/20 border-red-800' : 'bg-red-50 border-red-200',
      text: isDark ? 'text-red-200' : 'text-red-800',
      icon: 'text-red-500'
    },
    warning: {
      bg: isDark ? 'bg-yellow-900/20 border-yellow-800' : 'bg-yellow-50 border-yellow-200',
      text: isDark ? 'text-yellow-200' : 'text-yellow-800',
      icon: 'text-yellow-500'
    },
    info: {
      bg: isDark ? 'bg-blue-900/20 border-blue-800' : 'bg-blue-50 border-blue-200',
      text: isDark ? 'text-blue-200' : 'text-blue-800',
      icon: 'text-blue-500'
    },
    success: {
      bg: isDark ? 'bg-green-900/20 border-green-800' : 'bg-green-50 border-green-200',
      text: isDark ? 'text-green-200' : 'text-green-800',
      icon: 'text-green-500'
    }
  };

  const styles = typeStyles[type] || typeStyles.error;

  if (!message) return null;

  return (
    <div className={`rounded-lg border p-4 ${styles.bg} ${className}`}>
      <div className="flex">
        <div className="flex-shrink-0">
          <ExclamationTriangleIcon className={`h-5 w-5 ${styles.icon}`} />
        </div>
        <div className="ml-3 flex-1">
          <div className={`text-sm ${styles.text}`}>
            {typeof message === 'string' ? message : JSON.stringify(message)}
          </div>
        </div>
        {closable && onClose && (
          <div className="ml-auto pl-3">
            <div className="-mx-1.5 -my-1.5">
              <button
                type="button"
                onClick={onClose}
                className={`
                  inline-flex rounded-md p-1.5 focus:outline-none focus:ring-2 focus:ring-offset-2
                  ${styles.text} hover:bg-black/10 focus:ring-offset-transparent
                `}
              >
                <span className="sr-only">Fermer</span>
                <XMarkIcon className="h-5 w-5" />
              </button>
            </div>
          </div>
        )}
      </div>
    </div>
  );
};

export default ErrorMessage;