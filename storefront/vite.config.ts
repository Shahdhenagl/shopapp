import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'node:path';

// Served at /store on the production host — a real directory beside
// /dashboard, so Apache serves it directly and Laravel never sees these
// paths. Its own .htaccess routes deep links back to index.html.
export default defineConfig(({ mode }) => ({
  base: mode === 'production' ? '/store/' : '/',
  plugins: [react()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  build: {
    rollupOptions: {
      output: {
        manualChunks: {
          react: ['react', 'react-dom', 'react-router-dom'],
          query: ['@tanstack/react-query', 'axios'],
        },
      },
    },
  },
  server: {
    port: 5174,
    proxy: {
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        secure: false,
      },
    },
  },
}));
