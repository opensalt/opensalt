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
import { useDocumentStore } from './stores/documentStore.ts';
import { useCurrentDocumentStore } from './stores/currentDocumentStore.ts';

router.beforeEach(async (to, from, next) => {
  const documentStore = useDocumentStore();
  const currentDocumentStore = useCurrentDocumentStore();
  const frameworkId = to.params.frameworkId;

  // Ensure document list is loaded if not already
  if (documentStore.documents.length === 0) {
    await documentStore.fetchDocuments();
  }

  // If no frameworkId and at root, load first document if available
  if (!frameworkId && to.path === '/') {
    if (documentStore.documents.length > 0) {
      next(`/${documentStore.documents[0].id}`);
      return;
    }
  }

  // Load framework if frameworkId changed or not loaded
  if (frameworkId && frameworkId !== currentDocumentStore.currentDocument?.id) {
    try {
      const docData = await documentStore.fetchDocument(frameworkId);
      // Transform and set the current document
      const cfDoc = docData.CFDocument || {};
      const items = currentDocumentStore.transformCASEItems(docData.CFItems || [], docData.CFAssociations || [], cfDoc.identifier);

      currentDocumentStore.selectDocument({
        id: cfDoc.identifier,
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
      }, docData.CFAssociationGroupings || [], docData.CFAssociations || []);
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
