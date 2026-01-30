import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

export default defineConfig({
  plugins: [react()],
  base: '/time/',
  server: {
    proxy: {
      '/api.php': { target: 'http://www.adamrmunday.com/time', changeOrigin: true },
      '/sse.php': { target: 'http://www.adamrmunday.com/time', changeOrigin: true },
    },
  },
})
