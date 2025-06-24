import React from 'react';
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom';
import { AuthProvider } from './contexts/AuthContext';
import { ThemeProvider } from './contexts/ThemeContext';
import PrivateRoute from './components/Auth/PrivateRoute';
import Layout from './components/Layout/Layout';
import Login from './components/Auth/Login';
import Register from './components/Auth/Register';
import Dashboard from './components/Dashboard/Dashboard';
import TaskList from './components/Tasks/TaskList';
import TaskDetail from './components/Tasks/TaskDetail';
import TaskForm from './components/Tasks/TaskForm';
import ProjectList from './components/Projects/ProjectList';
import ProjectForm from './components/Projects/ProjectForm';
import './styles/index.css';

function App() {
  return (
    <Router>
      <ThemeProvider>
        <AuthProvider>
          <Routes>
            <Route path="/login" element={<Login />} />
            <Route path="/register" element={<Register />} />
            
            <Route
              path="/"
              element={
                <PrivateRoute>
                  <Layout />
                </PrivateRoute>
              }
            >
              <Route index element={<Dashboard />} />
              <Route path="tasks" element={<TaskList />} />
              <Route path="tasks/new" element={<TaskForm />} />
              <Route path="tasks/:id" element={<TaskDetail />} />
              <Route path="tasks/:id/edit" element={<TaskForm />} />
              
              {/* Projects Routes */}
              <Route path="projects" element={<ProjectList />} />
              <Route path="projects/new" element={<ProjectForm />} />
              <Route path="projects/:id/edit" element={<ProjectForm />} />
              
              {/* Autres routes à implémenter */}
              <Route path="tags" element={<div>Tags (à venir)</div>} />
              <Route path="time-tracking" element={<div>Suivi du temps (à venir)</div>} />
              <Route path="calendar" element={<div>Calendrier (à venir)</div>} />
              <Route path="reports" element={<div>Rapports (à venir)</div>} />
              <Route path="profile" element={<div>Profil (à venir)</div>} />
              <Route path="settings" element={<div>Paramètres (à venir)</div>} />
            </Route>
            
            <Route path="*" element={<Navigate to="/" replace />} />
          </Routes>
        </AuthProvider>
      </ThemeProvider>
    </Router>
  );
}

export default App;