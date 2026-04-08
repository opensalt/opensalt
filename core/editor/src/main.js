import { createApp } from 'vue';
import { createPinia, setActivePinia } from 'pinia';
import App from './App.vue';
import router from './router/index.js';
import { localFrameworkDb } from './services/localFrameworkDb.js';
import { cleanupStaleDatabases } from './db/pgliteDataDir.js';
import { logger } from './utils/logger.js';
import { useDocumentStore } from './stores/documentStore.ts';
import { useCurrentDocumentStore } from './stores/currentDocumentStore.ts';
import 'bootstrap/dist/css/bootstrap.min.css';
import 'bootstrap-icons/font/bootstrap-icons.css';
import 'katex/dist/katex.min.css';
import "github-markdown-css/github-markdown.css"

const app = createApp(App);
const pinia = createPinia();
setActivePinia(pinia);

// Remove legacy PGlite databases as early as possible so page reloads can clear them.
await cleanupStaleDatabases();

// Initialize database before mounting the app to prevent race conditions
try {
  await localFrameworkDb.initialize();
} catch (error) {
  logger.error('Failed to initialize database:', error);
  // Mount anyway - the app can still function for viewing, 
  // but framework operations may fail gracefully
}

router.beforeEach(async (to) => {
  if (to.name === 'DbReplView') {
    return true;
  }

  const documentStore = useDocumentStore(pinia);
  const currentDocumentStore = useCurrentDocumentStore(pinia);
  const frameworkId = to.params.frameworkId;

  // Ensure document list is loaded if not already
  if (documentStore.documents.length === 0) {
    await documentStore.fetchDocuments();
  }

  // If no frameworkId and at root, load first document if available
  if (!frameworkId && to.path === '/') {
    if (documentStore.documents.length > 0) {
      return `/${documentStore.documents[0].id}`;
    }
  }

  // Load framework if frameworkId changed or not loaded
  if (frameworkId && frameworkId !== currentDocumentStore.currentDocument?.id) {
    try {
      const docData = await documentStore.fetchDocument(frameworkId);
      // Transform and set the current document
      const cfDoc = docData.CFDocument || {};
      const items = await currentDocumentStore.transformCASEItems(docData.CFItems || [], docData.CFAssociations || [], cfDoc.identifier);
      const definitions = docData.CFDefinitions || {};
      const associationGroupings = definitions.CFAssociationGroupings || docData.CFAssociationGroupings || [];

      currentDocumentStore.selectDocument({
        id: cfDoc.identifier,
        identifier: cfDoc.identifier,
        uri: cfDoc.uri || '',
        title: cfDoc.title || 'Untitled',
        description: cfDoc.description || null,
        creator: cfDoc.creator || '',
        subject: cfDoc.subject || null,
        subjectURI: cfDoc.subjectURI || [],
        status: cfDoc.adoptionStatus || 'Draft',
        statusStartDate: cfDoc.statusStartDate || null,
        statusEndDate: cfDoc.statusEndDate || null,
        lastModified: cfDoc.lastChangeDateTime || '',
        language: cfDoc.language || null,
        version: cfDoc.version || null,
        officialSourceURL: cfDoc.officialSourceURL || null,
        publisher: cfDoc.publisher || null,
        licenseURI: cfDoc.licenseURI || null,
        notes: cfDoc.notes || null,
        frameworkType: cfDoc.frameworkType || null,
        caseVersion: cfDoc.caseVersion || null,
        extensions: cfDoc.extensions || null,
        CFPackageURI: cfDoc.CFPackageURI || null,
        items: items
      }, associationGroupings, docData.CFAssociations || [], definitions);
    } catch (error) {
      logger.error('Failed to load framework:', error);
      // Redirect to root or error page
      return '/';
    }
  }

  return true;
});

app.use(pinia);
app.use(router);

app.mount('#app');
