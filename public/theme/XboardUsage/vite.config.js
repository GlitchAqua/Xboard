import { defineConfig } from 'vite'
import vue from '@vitejs/plugin-vue'
import path from 'path'

export default defineConfig({
  plugins: [vue()],
  base: '/theme/XboardUsage/assets/',
  resolve: {
    alias: {
      '@': path.resolve(__dirname, 'src')
    }
  },
  build: {
    outDir: 'assets',
    emptyOutDir: true,
    rollupOptions: {
      input: path.resolve(__dirname, 'src/main.js'),
      output: {
        entryFileNames: 'app.js',
        chunkFileNames: '[name]-[hash].js',
        assetFileNames: '[name].[ext]'
      }
    }
  }
})
