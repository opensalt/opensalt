<template>
  <div class="association-view">
    <div v-if="loading" class="d-flex justify-content-center align-items-center" style="height: 100%;">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading associations...</span>
      </div>
    </div>
    <div v-else-if="error" class="alert alert-danger my-4" role="alert">
      {{ error }}
    </div>
    <div v-else-if="!currentDocument" class="alert alert-info my-4" role="alert">
      Please select a document to view associations.
    </div>
    <div v-else class="row g-0" style="height: 100%;">
      <!-- Association Graph Panel -->
      <section class="col-8 association-graph-panel d-flex flex-column">
        <div class="p-3 flex-grow-1 d-flex flex-column">
          <h3 class="mb-3">Association Network</h3>
          <div class="association-graph-container border rounded p-3 flex-grow-1 overflow-auto">
            <div v-if="associations.length === 0" class="text-center text-muted">
              <i class="bi bi-share fs-1 mb-3"></i>
              <p>No associations found in this document.</p>
            </div>
            <div v-else class="association-network">
              <!-- Simple association visualization -->
              <div class="association-list">
                <div v-for="assoc in associations.slice(0, 20)" :key="assoc.identifier" class="association-item mb-3 p-3 border rounded">
                  <div class="row g-0 align-items-center">
                    <div class="col-5">
                      <div class="association-node source-node">
                        <small class="text-muted">Source</small>
                        <div class="node-content">{{ getItemTitle(assoc.originNodeURI?.identifier) }}</div>
                      </div>
                    </div>
                    <div class="col-2 text-center">
                      <div class="association-arrow">
                        <i class="bi bi-arrow-right fs-4 text-primary"></i>
                        <div class="association-type badge bg-primary mt-1">{{ assoc.associationType }}</div>
                      </div>
                    </div>
                    <div class="col-5">
                      <div class="association-node target-node">
                        <small class="text-muted">Target</small>
                        <div class="node-content">{{ getItemTitle(assoc.destinationNodeURI?.identifier) }}</div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
              <div v-if="associations.length > 20" class="text-center mt-3">
                <small class="text-muted">Showing first 20 associations of {{ associations.length }} total</small>
              </div>
            </div>
          </div>
        </div>
      </section>

      <!-- Association Details Panel -->
      <section class="col-4 association-details-panel d-flex flex-column">
        <div class="p-3 flex-grow-1 d-flex flex-column">
          <h4 class="mb-3">Association Statistics</h4>
          <div class="stats-container flex-grow-1 overflow-auto">
            <div class="stat-item mb-3">
              <div class="stat-label">Total Associations</div>
              <div class="stat-value h3 text-primary">{{ associations.length }}</div>
            </div>
            <div class="stat-item mb-3">
              <div class="stat-label">Association Types</div>
              <div class="type-breakdown">
                <div v-for="[type, count] in associationTypes" :key="type" class="type-item d-flex justify-content-between">
                  <span>{{ type }}</span>
                  <span class="badge bg-secondary">{{ count }}</span>
                </div>
              </div>
            </div>
            <div class="stat-item mb-3">
              <div class="stat-label">Items with Associations</div>
              <div class="stat-value h5">{{ itemsWithAssociations }}</div>
            </div>
            <div class="stat-item">
              <div class="stat-label">Association Groups</div>
              <div class="stat-value h5">{{ associationGroups.length - 2 }}</div> <!-- Subtract 'all' and 'default' -->
            </div>
          </div>
        </div>
      </section>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { useDocumentStore } from '../../stores/documentStore';
import { useCurrentDocumentStore } from '../../stores/currentDocumentStore';
import { useItemStore } from '../../stores/itemStore';
import { useAssociationStore } from '../../stores/associationStore';

const route = useRoute();
const documentStore = useDocumentStore();
const currentDocumentStore = useCurrentDocumentStore();
const itemStore = useItemStore();
const associationStore = useAssociationStore();

const loading = computed(() => documentStore.loading);
const error = computed(() => documentStore.error);
const currentDocument = computed(() => currentDocumentStore.currentDocument);
const associationGroups = computed(() => currentDocumentStore.associationGroups);

onMounted(() => {
  const itemId = route.params.itemId;
  if (itemId && currentDocument.value) {
    const item = itemStore.findItemByIdentifier(currentDocument.value.items, itemId);
    if (item) {
      // Note: setCurrentItem would need to be moved to viewStore if needed
      // For now, we'll just log it
      console.log('Item found:', item);
    }
  }
});

const associations = computed(() => {
  if (!currentDocument.value) return [];
  return currentDocumentStore.currentDocumentAssociations || [];
});

const associationTypes = computed(() => {
  const types = new Map();
  associations.value.forEach(assoc => {
    const type = assoc.associationType || 'Unknown';
    types.set(type, (types.get(type) || 0) + 1);
  });
  return Array.from(types.entries()).sort((a, b) => b[1] - a[1]);
});

const itemsWithAssociations = computed(() => {
  if (!currentDocument.value || !currentDocument.value.items) return 0;
  return currentDocument.value.items.filter(item =>
    item.associations && item.associations.length > 0
  ).length;
});

function getItemTitle(identifier) {
  if (!currentDocument.value || !currentDocument.value.items) return identifier || 'Unknown';

  const findItem = (items) => {
    for (const item of items) {
      if (item.identifier === identifier) {
        return item.title || item.abbreviatedTitle || 'Untitled';
      }
      if (item.children && item.children.length > 0) {
        const found = findItem(item.children);
        if (found) return found;
      }
    }
    return null;
  };

  return findItem(currentDocument.value.items) || identifier || 'Unknown';
}
</script>

<style scoped>
.association-view {
  height: 100%;
}

.association-graph-container {
  background-color: #f8f9fa;
  overflow-y: auto;
}

.association-network {
  height: 100%;
}

.association-list {
  max-height: 100%;
}

.association-item {
  background: white;
  transition: box-shadow 0.2s ease;
}

.association-item:hover {
  box-shadow: 0 2px 8px rgba(0,0,0,0.1);
}

.association-node {
  text-align: center;
  padding: 0.5rem;
}

.source-node {
  border-right: 2px solid #dee2e6;
}

.target-node {
  border-left: 2px solid #dee2e6;
}

.node-content {
  font-weight: 500;
  font-size: 0.9em;
  word-break: break-word;
  line-height: 1.4;
}

.association-arrow {
  display: flex;
  flex-direction: column;
  align-items: center;
  padding: 0.5rem 0;
}

.association-type {
  font-size: 0.75em;
  margin-top: 4px;
  white-space: nowrap;
}

.stats-container {
  background: white;
  border-radius: 8px;
  padding: 1rem;
  box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.stat-item {
  border-bottom: 1px solid #dee2e6;
  padding-bottom: 0.5rem;
  margin-bottom: 1rem;
}

.stat-item:last-child {
  border-bottom: none;
  margin-bottom: 0;
}

.stat-label {
  font-size: 0.9em;
  color: #6c757d;
  margin-bottom: 0.25rem;
}

.stat-value {
  margin: 0;
}

.type-breakdown {
  max-height: 150px;
  overflow-y: auto;
}

.type-item {
  font-size: 0.85em;
  margin-bottom: 0.25rem;
}
</style>
