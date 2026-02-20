<template>
  <div v-if="visible" class="modal fade show d-block" tabindex="-1" role="dialog" aria-modal="true" style="background-color: rgba(0,0,0,0.5);">
    <div class="modal-dialog modal-lg" role="document">
      <div class="modal-content">
        <div class="modal-body text-center text-black" :class="bgClass">
          <h3>{{ message }}</h3>
          <button v-if="canRenew" class="btn btn-md btn-primary" @click="renew">Renew Session</button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { computed } from 'vue';
import { useSessionStore } from '../../../stores/sessionStore';

const sessionStore = useSessionStore();

const visible = computed(() => sessionStore.sessionModalVisible);

const bgClass = computed(() => {
  switch (sessionStore.warningLevel) {
    case 'expired':
    case 'warning-2':
      return 'bg-danger';
    case 'warning':
      return 'bg-warning';
    case 'info':
    default:
      return 'bg-info';
  }
});

const message = computed(() => {
  switch (sessionStore.warningLevel) {
    case 'expired':
      return 'Your session has expired.';
    case 'warning-2':
    case 'warning':
      return 'Your session is about to expire.';
    case 'info':
    default:
      return 'Your session will expire soon.';
  }
});

const canRenew = computed(() => sessionStore.warningLevel !== 'expired');

function renew() {
  sessionStore.renewSession();
}
</script>

<style scoped>
.modal {
    z-index: 1055; /* Ensure it's on top of other modals */
}
</style>
