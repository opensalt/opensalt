import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';

const manualChunkGroups = [
  ['vendor-vue', ['vue', 'vue-router', 'pinia']],
  ['vendor-bootstrap', ['bootstrap']],
  ['vendor-markdown', ['markdown-it', 'markdown-it-underline', 'sanitize-html']],
  ['vendor-katex', ['katex', '@vscode/markdown-it-katex']],
  ['vendor-editor', ['easymde']],
  ['vendor-tables', ['datatables.net', 'datatables.net-bs5']],
];

function manualChunks(id) {
  if (!id.includes('node_modules')) {
    return undefined;
  }

  for (const [chunkName, packages] of manualChunkGroups) {
    if (packages.some((pkg) => id.includes(`/node_modules/${pkg}/`))) {
      return chunkName;
    }
  }

  return undefined;
}

export default defineConfig({
  base: '/editor/',
  plugins: [
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
    esbuildOptions: {
      // Avoid remote source-map lookups from prebundled deps (for example
      // markdown-it -> entities) that get blocked by the app CSP in dev.
      sourcemap: false,
    },
  },
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
    tsconfigPaths: true
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
  worker: {
    format: 'es',
  },
  build: {
    manifest: true,
    rollupOptions: {
      input: 'src/main.js',
      output: {
        manualChunks,
      }
    }
  }
});
