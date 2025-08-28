<template>
  <div id="app">
    <nav class="navbar navbar-expand navbar-light bg-light mb-3">
      <div class="container-fluid">
        <a class="navbar-brand" href="#">
          <i class="bi bi-diagram-3"></i> Document Editor
        </a>
      </div>
    </nav>
    <div class="container-fluid">
      <!-- View Switcher -->
      <ViewSwitcher />

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
import ViewSwitcher from './components/ViewSwitcher.vue';
import TreeViewContainer from './components/EnhancedDocumentTreeEditor.vue';
import AssociationView from './components/AssociationView.vue';
import LogView from './components/LogView.vue';

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
