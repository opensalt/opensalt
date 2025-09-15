import { createApp } from 'vue';
import { createPinia } from 'pinia';
import App from './App.vue';
import router from './router/index.js';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap';
import 'bootstrap-icons/font/bootstrap-icons.css';
import 'katex/dist/katex.min.css';
import "github-markdown-css/github-markdown.css"

const app = createApp(App);
const pinia = createPinia();

app.use(pinia);
app.use(router);

// Initialize router guards after Pinia is set up
import { useFrameworkStore } from './stores/frameworkStore.js';

router.beforeEach(async (to, from, next) => {
  const frameworkStore = useFrameworkStore();
  const frameworkId = to.params.frameworkId;

  // Ensure document list is loaded if not already
  if (frameworkStore.documents.length === 0) {
    await frameworkStore.fetchDocuments();
  }

  // If no frameworkId and at root, load first document if available
  if (!frameworkId && to.path === '/') {
    if (frameworkStore.documents.length > 0) {
      next(`/${frameworkStore.documents[0].id}`);
      return;
    }
  }

  // Load framework if frameworkId changed or not loaded
  if (frameworkId && frameworkId !== frameworkStore.currentDocument?.id) {
    try {
      await frameworkStore.fetchDocument(frameworkId);
      // Mark as current in the list
      frameworkStore.selectDocument(frameworkStore.currentDocument);
    } catch (error) {
      console.error('Failed to load framework:', error);
      // Redirect to root or error page
      next('/');
      return;
    }
  }

  next();
});

app.mount('#app');
