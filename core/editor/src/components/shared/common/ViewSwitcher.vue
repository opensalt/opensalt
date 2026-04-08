<template>
  <div class="view-switcher d-flex justify-content-center">
    <div class="btn-group" role="group" aria-label="View selection">
      <button
        type="button"
        class="btn btn-sm"
        :class="{ 'btn-primary': currentView === 'tree', 'btn-outline-primary': currentView !== 'tree' }"
        @click="switchView('tree')"
        :aria-pressed="currentView === 'tree'"
      >
        <i class="bi bi-diagram-3 me-1"></i>
        Tree View
      </button>
      <button
        type="button"
        class="btn btn-sm"
        :class="{ 'btn-primary': currentView === 'association', 'btn-outline-primary': currentView !== 'association' }"
        @click="switchView('association')"
        :aria-pressed="currentView === 'association'"
      >
        <i class="bi bi-share me-1"></i>
        Association View
      </button>
        <button
        v-if="sessionStore.isAuthenticated"
        type="button"
        class="btn btn-sm"
        :class="{ 'btn-primary': currentView === 'log', 'btn-outline-primary': currentView !== 'log' }"
        @click="switchView('log')"
        :aria-pressed="currentView === 'log'"
      >
        <i class="bi bi-list-check me-1"></i>
        Log View
      </button>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import { useCurrentDocumentStore } from '../../../stores/currentDocumentStore';
import { useSessionStore } from '../../../stores/sessionStore';
import { useViewStore } from '../../../stores/viewStore';
import { useEditorContextStore } from '../../../stores/editorContextStore';
import { logger } from '../../../utils/logger.js';

const router = useRouter();
const route = useRoute();
const currentDocumentStore = useCurrentDocumentStore();
const sessionStore = useSessionStore();
const viewStore = useViewStore();
const editorContextStore = useEditorContextStore();

const currentFrameworkId = computed(() => currentDocumentStore.currentDocument?.id || '');

const currentView = computed(() => {
  const path = route.path;
  if (path.includes('/association')) return 'association';
  if (path.includes('/log')) return 'log';
  return 'tree';
});

async function switchView(view) {
  if (!currentFrameworkId.value) {
    logger.warn('No framework ID available for navigation');
    return;
  }

  let path;

  if (view === 'tree') {
    // Check if there's a saved framework selection for treeView
    const treeViewSelection = editorContextStore.getFrameworkSelection('treeView');

    if (treeViewSelection?.documentId && treeViewSelection.documentId !== currentFrameworkId.value) {
      // Use the saved framework selection
      const lastItemId = viewStore.getLastItemIdForDocument(treeViewSelection.documentId);
      if (lastItemId) {
        path = `/${treeViewSelection.documentId}/${lastItemId}`;
      } else {
        path = `/${treeViewSelection.documentId}`;
      }
      logger.debug('[ViewSwitcher] Using saved treeView framework:', treeViewSelection.documentId);
    } else {
      // Use current framework
      const lastItemId = viewStore.getLastItemIdForDocument(currentFrameworkId.value);
      if (lastItemId) {
        path = `/${currentFrameworkId.value}/${lastItemId}`;
      } else {
        path = `/${currentFrameworkId.value}`;
      }
    }
  } else if (view === 'association') {
    path = `/${currentFrameworkId.value}/association`;
  } else if (view === 'log') {
    path = `/${currentFrameworkId.value}/log`;
  }

  router.push(path);
}
</script>

<style scoped>
.btn-group .btn {
  border-radius: 0.375rem !important;
  margin: 0 2px;
}

.btn-group .btn:first-child {
  border-top-right-radius: 0 !important;
  border-bottom-right-radius: 0 !important;
}

.btn-group .btn:last-child {
  border-top-left-radius: 0 !important;
  border-bottom-left-radius: 0 !important;
}

.btn-group .btn:not(:first-child):not(:last-child) {
  border-radius: 0 !important;
}

.btn i {
  font-size: 0.9em;
}
</style>
