const { spawn } = require('child_process');
const path = require('path');

// Utilisez process.cwd() pour obtenir le répertoire actuel
const reactScripts = path.join(process.cwd(), 'node_modules', '.bin', 'react-scripts.cmd');

const start = spawn('cmd', ['/c', `"${reactScripts}" start`], { 
  stdio: 'inherit',
  shell: true,
  cwd: process.cwd()
});

start.on('error', (err) => {
  console.error('Erreur:', err);
});