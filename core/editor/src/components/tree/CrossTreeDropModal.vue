<script setup>
import { ref } from 'vue';
import Modal from 'bootstrap/js/dist/modal';

const props = defineProps({
  show: Boolean,
  sourceItem: Object,
  targetItem: Object, // Parent dropped onto
});

const emit = defineEmits(['close', 'copy', 'associate']);

function onCopy() {
    emit('copy');
}

function onAssociate() {
    emit('associate');
}

function onClose() {
    emit('close');
}
</script>

<template>
  <div class="modal fade" 
       :class="{ show: show, 'd-block': show }" 
       tabindex="-1" 
       role="dialog" 
       aria-modal="true"
       v-if="show"
       style="background-color: rgba(0,0,0,0.5);"
  >
    <div class="modal-dialog modal-dialog-centered" role="document">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Action Required</h5>
          <button type="button" class="btn-close" aria-label="Close" @click="onClose"></button>
        </div>
        <div class="modal-body">
          <p>
            You dragged local item <strong>{{ sourceItem?.title }}</strong> onto 
            <strong>{{ targetItem?.title || 'Root' }}</strong>.
          </p>
          <p>What would you like to do?</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" @click="onClose">Cancel</button>
          <button type="button" class="btn btn-primary" @click="onAssociate">Associate</button>
          <button type="button" class="btn btn-primary" @click="onCopy">Copy Item</button>
        </div>
      </div>
    </div>
  </div>
</template>
