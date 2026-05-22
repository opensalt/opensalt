<template>
  <div
    id="editor"
    class="d-flex flex-column h-100 overflow-hidden"
  >
    <div
      class="container-fluid d-flex flex-column flex-grow-1 overflow-hidden"
      style="min-height: 0;"
    >
      <!-- Header row: document name (left), status (right) -->
      <header class="d-flex align-items-center justify-content-between my-2 header-section flex-shrink-0">
        <h1 class="fs-4 fw-bold mb-0 doc-title">
          {{ docTitle }}
        </h1>
        <ViewSwitcher />
        <div v-if="docStatus">
          <span
            class="badge text-dark fs-5 px-4 py-2 doc-status"
            :class="docStatusClass"
            role="status"
            aria-live="polite"
          >{{ docStatus }}</span>
        </div>
        <div
          v-else
          class="badge-spacer"
        />
      </header>

      <div
        class="flex-grow-1 overflow-hidden d-flex flex-column"
        style="min-height: 0;"
      >
        <router-view />
      </div>
    </div>

    <SessionTimeoutModal />

    <!-- Toast notifications -->
    <div
      class="toast-container position-fixed top-0 end-0 p-3"
      style="z-index: 1100"
    >
      <div
        v-for="toast in toasts"
        :key="toast.id"
        class="toast align-items-center border-0 show mb-2"
        :class="`text-bg-${toast.type}`"
        role="alert"
        aria-live="assertive"
        aria-atomic="true"
      >
        <div class="d-flex">
          <div class="toast-body">
            <i
              v-if="toast.type === 'success'"
              class="bi bi-check-circle-fill me-2"
            />
            <i
              v-else-if="toast.type === 'danger'"
              class="bi bi-x-circle-fill me-2"
            />
            <i
              v-else
              class="bi bi-info-circle-fill me-2"
            />
            {{ toast.message }}
          </div>
          <button
            type="button"
            class="btn-close btn-close-white me-2 m-auto"
            @click="removeToast(toast.id)"
          />
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, provide, computed, onMounted, watch } from 'vue';
import { RouterView } from 'vue-router';
import { useCurrentDocumentStore } from './stores/currentDocumentStore';
import { useSessionStore } from './stores/sessionStore';
import ViewSwitcher from './components/shared/common/ViewSwitcher.vue';
import SessionTimeoutModal from './components/shared/common/SessionTimeoutModal.vue';

const currentDocumentStore = useCurrentDocumentStore();
const sessionStore = useSessionStore();

onMounted(() => {
  sessionStore.init();
});

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

const doc = computed(() => currentDocumentStore.currentDocument || { title: '', adoptionStatus: '', items: [] });
const docTitle = computed(() => doc.value.title);
const docStatus = computed(() => currentDocumentStore.currentDocument ? (doc.value.adoptionStatus || 'Draft') : null);
const docStatusClass = computed(() => {
  if (docStatus.value === 'Draft') return 'draft';
  if (docStatus.value === 'Private Draft') return 'private';
  if (docStatus.value === 'Deprecated') return 'deprecated';
  if (docStatus.value === 'Adopted') return 'adopted';
  return 'draft'; // default fallback
});

// Update browser tab title when framework changes
watch(() => doc.value?.title, (newTitle) => {
  if (newTitle) {
    document.title = `${newTitle} - OpenSALT Framework Editor`;
  } else {
    document.title = 'OpenSALT Framework Editor';
  }
}, { immediate: true });
</script>

<style>
@import './styles/cftree-styles.scss';

.toast-container {
  pointer-events: none;
}
.toast {
  pointer-events: auto;
}
.badge-spacer {
  min-width: 120px;
}
</style>
