import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import tsconfigPaths from 'vite-tsconfig-paths';
import path from 'path';

export default defineConfig({
  base: '/editor/',
  plugins: [
    tsconfigPaths(),
    vue({
      template: {
        compilerOptions: {
          isCustomElement: (tag) => tag === 'pglite-repl',
        },
      },
    }),
  ],
  optimizeDeps: {
    exclude: ['@electric-sql/pglite'],
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  server: {
    port: 5173,
    allowedHosts: true,
    hmr: {
      path: '/editor/@vite-hmr',
    },
    headers: {
      'Cross-Origin-Opener-Policy': 'same-origin',
      'Cross-Origin-Embedder-Policy': 'credentialless',
    },
  },
  build: {
    manifest: true,
    rollupOptions: {
      input: 'src/main.js',
      output: {
        manualChunks: {
          'vendor-vue': ['vue', 'vue-router', 'pinia'],
          'vendor-bootstrap': ['bootstrap'],
          'vendor-markdown': ['markdown-it', 'markdown-it-underline', 'sanitize-html'],
          'vendor-katex': ['katex', '@vscode/markdown-it-katex'],
          'vendor-editor': ['easymde'],
          'vendor-tables': ['datatables.net', 'datatables.net-bs5'],
        }
      }
    }
  }
});
