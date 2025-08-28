<template>
  <div class="right-side-panel">
    <!-- Control Buttons -->
    <div class="d-flex justify-content-between align-items-center mb-3 p-2 bg-light rounded">
      <div class="btn-group" role="group" aria-label="Right side mode">
        <button
          type="button"
          class="btn"
          :class="mode === 'itemDetails' ? 'btn-primary' : 'btn-outline-primary'"
          @click="setMode('itemDetails')"
        >
          <i class="bi bi-info-circle"></i> Item Details
        </button>
        <button
          type="button"
          class="btn"
          :class="mode === 'copyItem' ? 'btn-primary' : 'btn-outline-primary'"
          @click="setMode('copyItem')"
        >
          <i class="bi bi-copy"></i> Copy Items
        </button>
        <button
          type="button"
          class="btn"
          :class="mode === 'addAssociation' ? 'btn-primary' : 'btn-outline-primary'"
          @click="setMode('addAssociation')"
        >
          <i class="bi bi-link"></i> Associations
        </button>
      </div>
    </div>

    <!-- Item Details Mode -->
    <div v-if="mode === 'itemDetails'" class="item-details-panel">
      <div v-if="!selectedItem" class="text-center text-muted p-4">
        <i class="bi bi-info-circle fs-1 mb-3"></i>
        <p>Select an item from the tree to view its details</p>
      </div>

      <div v-else class="item-details">
        <!-- Item Header -->
        <div class="card mb-3">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Item Details</h6>
            <div class="btn-group btn-group-sm">
              <button
                type="button"
                class="btn btn-outline-primary"
                @click="editItem"
                title="Edit item"
              >
                <i class="bi bi-pencil"></i>
              </button>
              <button
                type="button"
                class="btn btn-outline-danger"
                @click="deleteItem"
                title="Delete item"
              >
                <i class="bi bi-trash"></i>
              </button>
            </div>
          </div>
          <div class="card-body">
            <h5 class="card-title">
              <span v-if="selectedItem.humanCodingScheme" class="badge bg-secondary me-2">
                {{ selectedItem.humanCodingScheme }}
              </span>
              {{ selectedItem.title || selectedItem.abbreviatedTitle || selectedItem.identifier }}
            </h5>

            <div v-if="selectedItem.fullStatement" class="mb-3">
              <strong>Full Statement:</strong>
              <p class="mt-1">{{ selectedItem.fullStatement }}</p>
            </div>

            <div v-if="selectedItem.abbreviatedStatement && selectedItem.abbreviatedStatement !== selectedItem.fullStatement" class="mb-3">
              <strong>Abbreviated Statement:</strong>
              <p class="mt-1">{{ selectedItem.abbreviatedStatement }}</p>
            </div>

            <div v-if="selectedItem.notes" class="mb-3">
              <strong>Notes:</strong>
              <p class="mt-1">{{ selectedItem.notes }}</p>
            </div>

            <div class="row">
              <div class="col-sm-6">
                <strong>Item Type:</strong> {{ selectedItem.itemType || 'General' }}
              </div>
              <div class="col-sm-6">
                <strong>Language:</strong> {{ selectedItem.language || 'en' }}
              </div>
            </div>

            <div v-if="selectedItem.lastChanged" class="mt-2">
              <small class="text-muted">
                Last changed: {{ formatDate(selectedItem.lastChanged) }}
              </small>
            </div>
          </div>
        </div>

        <!-- Associations -->
        <div v-if="selectedItem.associations && selectedItem.associations.length > 0" class="card mb-3">
          <div class="card-header d-flex justify-content-between align-items-center">
            <h6 class="mb-0">Associations</h6>
            <button type="button" class="btn btn-sm btn-outline-primary" @click="addAssociation">
              <i class="bi bi-plus"></i> Add
            </button>
          </div>
          <div class="card-body">
            <div class="list-group">
              <div
                v-for="assoc in selectedItem.associations"
                :key="assoc.id"
                class="list-group-item d-flex justify-content-between align-items-center"
              >
                <div>
                  <strong>{{ assoc.type }}</strong>
                  <span v-if="assoc.annotation" class="text-muted ms-2">({{ assoc.annotation }})</span>
                  <br>
                  <small class="text-muted">
                    {{ assoc.destination?.title || assoc.destination?.identifier || 'Unknown' }}
                  </small>
                </div>
                <div class="btn-group btn-group-sm">
                  <button
                    type="button"
                    class="btn btn-outline-primary"
                    @click="editAssociation(assoc)"
                    title="Edit association"
                  >
                    <i class="bi bi-pencil"></i>
                  </button>
                  <button
                    type="button"
                    class="btn btn-outline-danger"
                    @click="deleteAssociation(assoc)"
                    title="Delete association"
                  >
                    <i class="bi bi-trash"></i>
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Actions -->
        <div class="card">
          <div class="card-header">
            <h6 class="mb-0">Actions</h6>
          </div>
          <div class="card-body">
            <div class="d-grid gap-2">
              <button type="button" class="btn btn-outline-primary" @click="addChild">
                <i class="bi bi-plus-circle"></i> Add Child Item
              </button>
              <button type="button" class="btn btn-outline-secondary" @click="addExemplar">
                <i class="bi bi-link-45deg"></i> Add Exemplar
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>

    <!-- Copy Items Mode -->
    <div v-else-if="mode === 'copyItem'" class="copy-items-panel">
      <div class="alert alert-info">
        <i class="bi bi-info-circle"></i>
        Drag items from the right tree to copy them to the left tree
      </div>
      <div class="card">
        <div class="card-body">
          <h6>Copy Instructions</h6>
          <ul class="mb-0">
            <li>Select items in the right tree</li>
            <li>Drag them to the desired location in the left tree</li>
            <li>The items will be copied with their associations</li>
          </ul>
        </div>
      </div>
    </div>

    <!-- Add Association Mode -->
    <div v-else-if="mode === 'addAssociation'" class="association-panel">
      <div class="alert alert-info">
        <i class="bi bi-info-circle"></i>
        Drag items from the right tree to create associations with items in the left tree
      </div>
      <div class="card">
        <div class="card-body">
          <h6>Association Instructions</h6>
          <ul class="mb-0">
            <li>Select an item in the left tree</li>
            <li>Drag an item from the right tree onto it</li>
            <li>Choose the association type in the dialog</li>
          </ul>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';

const props = defineProps({
  selectedItem: Object,
  initialMode: {
    type: String,
    default: 'itemDetails'
  }
});

const emit = defineEmits([
  'mode-changed',
  'edit-item',
  'delete-item',
  'add-child',
  'add-exemplar',
  'add-association',
  'edit-association',
  'delete-association'
]);

const mode = ref(props.initialMode);

watch(() => props.initialMode, (newMode) => {
  mode.value = newMode;
});

function setMode(newMode) {
  mode.value = newMode;
  emit('mode-changed', newMode);
}

function editItem() {
  emit('edit-item', props.selectedItem);
}

function deleteItem() {
  emit('delete-item', props.selectedItem);
}

function addChild() {
  emit('add-child', props.selectedItem);
}

function addExemplar() {
  emit('add-exemplar', props.selectedItem);
}

function addAssociation() {
  emit('add-association', props.selectedItem);
}

function editAssociation(association) {
  emit('edit-association', association);
}

function deleteAssociation(association) {
  emit('delete-association', association);
}

function formatDate(dateString) {
  if (!dateString) return '';
  return new Date(dateString).toLocaleDateString();
}
</script>

<style scoped>
.right-side-panel {
  height: 100%;
  overflow-y: auto;
}

.btn-group .btn {
  font-size: 0.875rem;
}

.card-header {
  padding: 0.5rem 1rem;
  background-color: #f8f9fa;
}

.card-body {
  padding: 1rem;
}

.list-group-item {
  padding: 0.75rem 1rem;
}

.badge {
  font-size: 0.75em;
}

.alert {
  padding: 0.75rem 1rem;
}

.btn-group-sm .btn {
  padding: 0.25rem 0.5rem;
  font-size: 0.75rem;
}
</style>
