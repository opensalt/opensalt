<template>
  <div class="modal fade" id="cloneFrameworkModal" tabindex="-1" role="dialog"
       aria-labelledby="cloneFrameworkModalLabel" aria-hidden="true" ref="modalElement">
    <div class="modal-dialog" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="cloneFrameworkModalLabel">Clone Framework</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>Are you sure you want to clone this framework?</p>
          <p><strong>{{ frameworkTitle }}</strong></p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
            Cancel
          </button>
          <button type="button" class="btn btn-primary" @click="confirmClone">
            Clone Framework
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch, onMounted } from 'vue';
import Modal from 'bootstrap/js/dist/modal';

const props = defineProps({
  show: Boolean,
  frameworkTitle: {
    type: String,
    default: ''
  }
});

const emit = defineEmits(['confirmed', 'hidden']);

const modalElement = ref(null);
let bsModal = null;

onMounted(() => {
  if (modalElement.value) {
    bsModal = new Modal(modalElement.value);
    modalElement.value.addEventListener('hidden.bs.modal', () => {
      emit('hidden');
    });
  }
});

watch(() => props.show, (newVal) => {
  if (bsModal) {
    if (newVal) {
      bsModal.show();
    } else {
      bsModal.hide();
    }
  }
});

function confirmClone() {
  emit('confirmed');
  if (bsModal) {
    bsModal.hide();
  }
}
</script>
