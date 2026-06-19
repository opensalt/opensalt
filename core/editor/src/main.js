import { createApp } from 'vue';
import { createPinia, setActivePinia } from 'pinia';
import App from './App.vue';
import router from './router/index.js';
import { logger } from './utils/logger.js';
import { useDocumentStore } from './stores/documentStore.ts';
import { useCurrentDocumentStore } from './stores/currentDocumentStore.ts';
import 'bootstrap/dist/css/bootstrap.min.css';
import * as bootstrap from 'bootstrap/dist/js/bootstrap.esm.min.js';
window.bootstrap = window.bootstrap || bootstrap;
import 'bootstrap-icons/font/bootstrap-icons.css';
import 'katex/dist/katex.min.css';
import "github-markdown-css/github-markdown.css"

const app = createApp(App);
const pinia = createPinia();
setActivePinia(pinia);

router.beforeEach(async (to) => {
  if (to.name === 'DbReplView') {
    return true;
  }

  const documentStore = useDocumentStore(pinia);
  const currentDocumentStore = useCurrentDocumentStore(pinia);
  const frameworkId = to.params.frameworkId;

  if (documentStore.documents.length === 0) {
    await documentStore.fetchDocuments();
  }

  if (!frameworkId && to.path === '/') {
    if (documentStore.documents.length > 0) {
      return `/${documentStore.documents[0].id}`;
    }
  }

  if (frameworkId && frameworkId !== currentDocumentStore.currentDocument?.id) {
    try {
      const treeResponse = await documentStore.fetchTree(frameworkId);
      currentDocumentStore.selectDocument(treeResponse);
    } catch (error) {
      logger.error('Failed to load framework:', error);
      return '/';
    }
  }

  return true;
});

app.use(pinia);
app.use(router);

app.mount('#app');
