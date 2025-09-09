<template>
  <div id="editor" style="height: 100%; overflow: hidden;">
    <nav class="navbar navbar-expand navbar-light bg-light mb-3">
      <div class="container-fluid">
        <a class="navbar-brand" href="#">
          <i class="bi bi-diagram-3"></i> Document Editor
        </a>
      </div>
    </nav>
    <div class="container-fluid" style="height: calc(100% - 76px); overflow: hidden;">
    <!-- Header row: document name (left), status (right) -->
    <header class="d-flex align-items-center justify-content-between mb-2 header-section">
      <h1 class="fs-4 fw-bold mb-0 doc-title">{{ docTitle }}</h1>
      <ViewSwitcher />
      <div>
        <span class="badge bg-warning text-dark fs-5 px-4 py-2 doc-status" :class="{ draft: docStatus === 'Draft', deprecated: docStatus === 'Deprecated' }" role="status" aria-live="polite">{{ docStatus }}</span>
      </div>
    </header>

      <!-- Conditional View Rendering -->
      <TreeViewContainer v-if="currentView === 'tree'" />
      <AssociationView v-else-if="currentView === 'association'" />
      <LogView v-else-if="currentView === 'log'" />
    </div>

    <!-- Toast notifications -->
    <div class="toast-container position-fixed top-0 end-0 p-3" style="z-index: 1100">
      <div
        v-for="(toast, idx) in toasts"
        :key="toast.id"
        class="toast align-items-center text-bg-{{ toast.type }} border-0 show mb-2"
        role="alert"
        aria-live="assertive"
        aria-atomic="true"
      >
        <div class="d-flex">
          <div class="toast-body">
            <i v-if="toast.type === 'success'" class="bi bi-check-circle-fill me-2"></i>
            <i v-else-if="toast.type === 'danger'" class="bi bi-x-circle-fill me-2"></i>
            <i v-else class="bi bi-info-circle-fill me-2"></i>
            {{ toast.message }}
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" @click="removeToast(toast.id)"></button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, provide, computed } from 'vue';
import { useFrameworkStore } from './stores/frameworkStore';
import ViewSwitcher from './components/shared/common/ViewSwitcher.vue';
import TreeViewContainer from './components/tree/EnhancedDocumentTreeEditor.vue';
import AssociationView from './components/association/AssociationView.vue';
import LogView from './components/log/LogView.vue';

const frameworkStore = useFrameworkStore();
const currentView = computed(() => frameworkStore.currentView);

const toasts = ref([]);
let toastId = 0;

function notify(message, type = 'info', timeout = 3500) {
  const id = ++toastId;
  toasts.value.push({ id, message, type });
  setTimeout(() => removeToast(id), timeout);
}
function removeToast(id) {
  toasts.value = toasts.value.filter(t => t.id !== id);
}

// Provide notification function to child components
provide('notify', notify);

const doc = computed(() => frameworkStore.currentDocument || { title: '', status: '', items: [] });
const docTitle = computed(() => doc.value.title);
const docStatus = computed(() => doc.value.status || 'Draft');
</script>

<style>
@import './styles/cftree-styles.scss';

.toast-container {
  pointer-events: none;
}
.toast {
  pointer-events: auto;
}
</style>
