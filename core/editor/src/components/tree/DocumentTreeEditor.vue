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
import { useAnnouncer } from '../../composables/useAnnouncer.js';

// Initialize screen reader announcer
const announcer = useAnnouncer();

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
    const itemsWithParentItem = new Set();
    (data.CFAssociations || []).forEach(assoc => {
      if (assoc.associationType !== 'isChildOf') return;

      const childId = assoc.originNodeURI?.identifier || assoc.originNodeIdentifier;
      const parentId = assoc.destinationNodeURI?.identifier || assoc.destinationNodeIdentifier;
      if (!childId || !parentId) return;

      const childInDoc = items.has(childId);
      const parentInDoc = items.has(parentId);

      if (childInDoc && parentInDoc) {
        // Both in document: standard parent-child
        const child = items.get(childId);
        const parent = items.get(parentId);
        child.sequenceNumber = assoc.sequenceNumber ?? 0;
        parent.children.push(child);
        children.set(childId, parentId);
        itemsWithParentItem.add(childId);
      } else if (childInDoc && !parentInDoc) {
        // Child is in doc, parent is NOT (could be document itself or cross-framework)
        const child = items.get(childId);
        child.sequenceNumber = assoc.sequenceNumber ?? 0;
        children.set(childId, parentId);
        // Don't add to itemsWithParentItem — this is a root item
      }
    });

    // Segment-aware comparison for humanCodingScheme (e.g., "1.2.3" vs "1.10.1")
    function compareBySegment(a, b) {
      const segmentsA = a.split(/[^a-zA-Z0-9]+/).filter(s => s !== '');
      const segmentsB = b.split(/[^a-zA-Z0-9]+/).filter(s => s !== '');
      const maxLen = Math.max(segmentsA.length, segmentsB.length);

      for (let i = 0; i < maxLen; i++) {
        const segA = segmentsA[i] || '';
        const segB = segmentsB[i] || '';
        const isNumA = /^\d+$/.test(segA);
        const isNumB = /^\d+$/.test(segB);

        if (isNumA && isNumB) {
          const numA = parseInt(segA, 10);
          const numB = parseInt(segB, 10);
          if (numA !== numB) return numA < numB ? -1 : 1;
        } else {
          const cmp = segA.localeCompare(segB);
          if (cmp !== 0) return cmp;
        }
      }
      return 0;
    }

    // Sort comparator: sequenceNumber → humanCodingScheme (by segments) → listEnumeration → abbreviatedTitle → fullStatement
    function itemSortComparator(a, b) {
      const seqA = a.sequenceNumber ?? 0;
      const seqB = b.sequenceNumber ?? 0;
      if (seqA !== seqB) return seqA - seqB;

      const schemeA = a.humanCodingScheme || '';
      const schemeB = b.humanCodingScheme || '';
      const schemeCmp = compareBySegment(schemeA, schemeB);
      if (schemeCmp !== 0) return schemeCmp;

      const enumA = a.listEnumeration || '';
      const enumB = b.listEnumeration || '';
      if (enumA !== enumB) return enumA.localeCompare(enumB);

      const abbrA = a.abbreviatedTitle || '';
      const abbrB = b.abbreviatedTitle || '';
      if (abbrA !== abbrB) return abbrA.localeCompare(abbrB);

      const titleA = a.title || '';
      const titleB = b.title || '';
      return titleA.localeCompare(titleB);
    }

    // Third pass: sort children
    items.forEach(item => {
      if (item.children && item.children.length > 0) {
        item.children.sort(itemSortComparator);
      }
    });

    // Get root items (those NOT placed into another item's children array)
    const rootItems = Array.from(items.values()).filter(item => !itemsWithParentItem.has(item.identifier));

    // Sort root items
    rootItems.sort(itemSortComparator);

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

function onTreeFocus(itemId) {
  // Handle tree focus events for accessibility
  const item = findItem(doc.value.items || [], itemId);
  if (item) {
    announcer.announceNavigation(item);
  }
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
