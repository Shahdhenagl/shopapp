import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';
import path from 'node:path';

// https://vitejs.dev/config/
export default defineConfig(({ mode }) => ({
  // Served under /dashboard on the production host; root in dev.
  base: mode === 'production' ? '/dashboard/' : '/',
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
          charts: ['recharts'],
          query: ['@tanstack/react-query', 'axios'],
        },
      },
    },
  },
  server: {
    port: 5173,
    proxy: {
      // Dev proxy so the real Laravel backend can be reached without CORS.
      '/api': {
        target: 'http://localhost:8000',
        changeOrigin: true,
        secure: false,
      },
    },
  },
}));
