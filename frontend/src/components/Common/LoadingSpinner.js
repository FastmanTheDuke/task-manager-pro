import React from 'react';
import { useTheme } from '../../contexts/ThemeContext';

const LoadingSpinner = ({ size = 'md', text = 'Chargement...', className = '' }) => {
  const { isDark } = useTheme();
  
  const sizes = {
    sm: 'h-4 w-4',
    md: 'h-8 w-8',
    lg: 'h-12 w-12',
    xl: 'h-16 w-16'
  };

  return (
    <div className={`flex flex-col items-center justify-center py-8 ${className}`}>
      <div
        className={`
          animate-spin rounded-full border-2 border-t-transparent
          ${sizes[size]}
          ${isDark ? 'border-blue-400' : 'border-blue-600'}
        `}
      />
      {text && (
        <p className={`mt-3 text-sm ${isDark ? 'text-gray-400' : 'text-gray-600'}`}>
          {text}
        </p>
      )}
    </div>
  );
};

export default LoadingSpinner;