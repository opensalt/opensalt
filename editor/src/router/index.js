import { createRouter, createWebHistory } from 'vue-router';
import TreeViewContainer from '../components/tree/EnhancedDocumentTreeEditor.vue';
import AssociationView from '../components/association/AssociationView.vue';
import LogView from '../components/log/LogView.vue';

const routes = [
  {
    path: '/:frameworkId/association/:itemId?',
    name: 'AssociationView',
    component: AssociationView
  },
  {
    path: '/:frameworkId/:itemId?',
    name: 'TreeView',
    component: TreeViewContainer
  },
  {
    path: '/:frameworkId/log',
    name: 'LogView',
    component: LogView
  },
  {
    path: '/',
    redirect: '/' // Default, will be handled by beforeEach
  }
];

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes
});

export default router;
