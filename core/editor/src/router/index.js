import { createRouter, createWebHistory } from 'vue-router';

// Lazy-loaded routes for better performance
// Components are only loaded when the route is accessed
const routes = [
  {
    path: '/:frameworkId/association/:itemId?',
    name: 'AssociationView',
    component: () => import('../components/association/AssociationView.vue')
  },
  {
    path: '/:frameworkId/crosswalk',
    name: 'CrosswalkView',
    component: () => import('../components/crosswalk/CrosswalkView.vue')
  },
  {
    path: '/:frameworkId/log',
    name: 'LogView',
    component: () => import('../components/log/LogView.vue')
  },
  {
    path: '/:frameworkId/:itemId?',
    name: 'TreeView',
    component: () => import('../components/tree/EnhancedDocumentTreeEditor.vue')
  },
  {
    path: '/',
    redirect: '/' // Default, will be handled by beforeEach
  }
];

const router = createRouter({
  history: createWebHistory(import.meta.env.BASE_URL),
  routes,
  scrollBehavior(_to, _from, _savedPosition) {
    // Always scroll to top on navigation
    return { top: 0 };
  }
});

// Navigation guard placeholder for future app-wide checks.
router.beforeEach(() => true);

export default router;
