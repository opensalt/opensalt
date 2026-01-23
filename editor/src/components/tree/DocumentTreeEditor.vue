<template>
  <div>
    <!-- Header row: document name (left), status (right) -->
    <header class="d-flex align-items-center justify-content-between mb-2 p-2" style="background: #f8d7da; border-radius: 6px; border: 2px solid #e09c6d;">
      <h1 class="fs-4 fw-bold text-uppercase mb-0">{{ docTitle }}</h1>
      <div>
        <span class="badge bg-warning text-dark fs-5 px-4 py-2" style="font-size: 1.5rem;" role="status" aria-live="polite">{{ docStatus }}</span>
      </div>
    </header>

    <div v-if="loading" class="d-flex justify-content-center align-items-center" style="height: 80vh;" role="status" aria-live="polite">
      <div class="spinner-border text-primary" role="status">
        <span class="visually-hidden">Loading document...</span>
      </div>
    </div>
    <div v-else-if="error" class="alert alert-danger my-4" role="alert" aria-live="assertive">{{ error }}</div>
    <main v-else class="row g-0" style="height: 80vh;">
      <!-- Tree panel -->
      <section class="col-5 border-end p-3" style="background: #fff;">
        <label for="search-input" class="visually-hidden">Search items in document</label>
        <input
          type="text"
          id="search-input"
          class="form-control mb-2"
          placeholder="Search..."
          v-model="search"
          aria-describedby="search-help"
        />
        <div id="search-help" class="visually-hidden">Type to search for items in the document tree</div>
        <TreeView :doc="doc" @select="onSelect" :search="search" />
      </section>
      <!-- Details/info panel -->
      <InfoPanel :selected-item="selectedItem" />
    </main>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import TreeView from './TreeView.vue';
import InfoPanel from '../shared/panels/InfoPanel.vue';

const doc = ref({ title: '', status: '', items: [] });
const loading = ref(true);
const error = ref('');
const search = ref('');
const selectedId = ref(null);
const selectedItem = computed(() => findItem(doc.value.items, selectedId.value));

const DATA_URL = '/uri/pb4f319c2-1844-11eb-8a57-0242c0a85003.json';

onMounted(async () => {
  loading.value = true;
  error.value = '';
  try {
    const res = await fetch(DATA_URL);
    if (!res.ok) throw new Error('Failed to fetch document');
    const data = await res.json();

    // Extract document metadata from CFDocument
    const cfDoc = data.CFDocument || {};

    // Build tree structure from CFItems and CFAssociations
    const items = new Map();
    const children = new Map();

    // First pass: create all items
    (data.CFItems || []).forEach(item => {
      items.set(item.identifier, {
        identifier: item.identifier,
        title: item.fullStatement,
        abbreviatedTitle: item.abbreviatedStatement || item.fullStatement,
        humanCodingScheme: item.humanCodingScheme,
        lastChanged: item.lastChangeDateTime,
        children: []
      });
    });

    // Second pass: build parent-child relationships from CFAssociations
    (data.CFAssociations || []).forEach(assoc => {
      if (assoc.associationType === 'isChildOf') {
        const childId = assoc.originNodeURI?.identifier || assoc.originNodeIdentifier;
        const parentId = assoc.destinationNodeURI?.identifier || assoc.destinationNodeIdentifier;
        if (items.has(childId) && items.has(parentId)) {
          const child = items.get(childId);
          const parent = items.get(parentId);
          // Store sequence number with the child for later sorting
          child.sequenceNumber = assoc.sequenceNumber || 0;
          parent.children.push(child);
          children.set(childId, parentId); // Track child->parent relationship
        }
      }
    });

    // Assign sequence numbers to all items from their associations
    (data.CFAssociations || []).forEach(assoc => {
      const itemId = assoc.originNodeURI?.identifier || assoc.originNodeIdentifier;
      if (items.has(itemId)) {
        const item = items.get(itemId);
        // Only assign sequenceNumber if it doesn't already exist (child items already have it)
        if (item.sequenceNumber === undefined) {
          item.sequenceNumber = assoc.sequenceNumber || 0;
        }
      }
    });

    // Third pass: sort children by sequenceNumber
    items.forEach(item => {
      if (item.children && item.children.length > 0) {
        item.children.sort((a, b) => {
          // Primary sort by sequenceNumber
          const seqA = a.sequenceNumber || 0;
          const seqB = b.sequenceNumber || 0;

          if (seqA !== seqB) {
            return seqA - seqB;
          }

          // Secondary sort by humanCodingScheme if sequenceNumbers are equal
          const schemeA = a.humanCodingScheme || '';
          const schemeB = b.humanCodingScheme || '';
          if (schemeA !== schemeB) {
            return schemeA.localeCompare(schemeB);
          }

          // Tertiary sort by title if humanCodingSchemes are equal
          const titleA = a.title || '';
          const titleB = b.title || '';
          return titleA.localeCompare(titleB);
        });
      }
    });

    // Get root items (those without parents)
    const rootItems = Array.from(items.values()).filter(item => !children.has(item.identifier));

    // Sort root items using the same logic as child items
    rootItems.sort((a, b) => {
      // Primary sort: items with isChildOf associations to CFDocument come first
      const aHasDocAssociation = data.CFAssociations?.some(assoc =>
        assoc.associationType === 'isChildOf' &&
        assoc.originNodeURI?.identifier === a.identifier &&
        assoc.destinationNodeURI?.identifier === cfDoc.identifier
      ) || false;
      const bHasDocAssociation = data.CFAssociations?.some(assoc =>
        assoc.associationType === 'isChildOf' &&
        assoc.originNodeURI?.identifier === b.identifier &&
        assoc.destinationNodeURI?.identifier === cfDoc.identifier
      ) || false;

      if (aHasDocAssociation !== bHasDocAssociation) {
        return aHasDocAssociation ? -1 : 1; // Items with doc associations come first
      }

      // Within each group, sort by sequenceNumber, then humanCodingScheme, then title
      const seqA = a.sequenceNumber || 0;
      const seqB = b.sequenceNumber || 0;

      if (seqA !== seqB) {
        return seqA - seqB;
      }

      // Tertiary sort by humanCodingScheme if sequenceNumbers are equal
      const schemeA = a.humanCodingScheme || '';
      const schemeB = b.humanCodingScheme || '';
      if (schemeA !== schemeB) {
        return schemeA.localeCompare(schemeB);
      }

      // Quaternary sort by title if humanCodingSchemes are equal
      const titleA = a.title || '';
      const titleB = b.title || '';
      return titleA.localeCompare(titleB);
    });

    // Map/normalize data
    doc.value = {
      title: cfDoc.title || 'Untitled',
      status: cfDoc.adoptionStatus || 'Draft',
      items: rootItems
    };
  } catch (e) {
    error.value = e.message;
  } finally {
    loading.value = false;
  }
});

function onSelect(id) {
  selectedId.value = id;
}
function findItem(items, id) {
  for (const item of items) {
    if (item.identifier === id) return item;
    if (item.children) {
      const found = findItem(item.children, id);
      if (found) return found;
    }
  }
  return null;
}
const docTitle = computed(() => doc.value.title);
const docStatus = computed(() => doc.value.status || 'Draft');
</script>

<script>
// Future: import composables and logic from apx.js/view-trees.js here
</script>
