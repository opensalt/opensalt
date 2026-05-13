import { defineConfig } from 'vite';
import vue from '@vitejs/plugin-vue';
import path from 'path';
import { fileURLToPath } from 'url';

const __filename = fileURLToPath(import.meta.url);
const __dirname = path.dirname(__filename);

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
    vue(),
  ],
  optimizeDeps: {
    esbuildOptions: {
      sourcemap: false,
    },
  },
  resolve: {
    alias: [
      // Preserve the @ alias
      { find: '@', replacement: path.resolve(__dirname, './src') },
      // Browser-compatible shims for Node.js built-ins pulled in transitively
      // by sanitize-html → postcss.  Prevents Vite's browser-externalization
      // warnings in the dev console.  Use RegExp aliases to match exact bare
      // specifiers only (e.g. "fs" but NOT "fs/promises").
      { find: /^path$/, replacement: path.resolve(__dirname, './src/shims/path.js') },
      { find: /^fs$/, replacement: path.resolve(__dirname, './src/shims/fs.js') },
      { find: /^url$/, replacement: path.resolve(__dirname, './src/shims/url.js') },
      { find: /^source-map-js$/, replacement: path.resolve(__dirname, './src/shims/source-map-js.js') },
    ],
    tsconfigPaths: true
  },
  server: {
    port: 5173,
    allowedHosts: true,
    hmr: {
      path: '/@vite-hmr',
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
