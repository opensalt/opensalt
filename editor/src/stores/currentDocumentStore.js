import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { api } from '../services/api.js';
import { logger } from '../utils/logger.js';

export const useCurrentDocumentStore = defineStore('currentDocument', () => {
  // State
  const currentDocument = ref(null);
  const currentDocumentDefinitions = ref({
    concepts: [],
    subjects: [],
    licenses: [],
    itemTypes: [],
    extensions: null
  });
  const currentDocumentRubrics = ref([]);
  const currentDocumentAssociations = ref([]);
  const currentDocumentAssociationGroupings = ref([]);
  const draggedItem = ref(null);
  
  // Actions
  function setDraggedItem(item) {
    draggedItem.value = item;
  }
  
  const associationGroups = computed(() => {
    const defaultGroups = [
      { id: 'all', title: 'All Groups', description: 'Show items from all association groups' },
      { id: 'default', title: 'Default Group', description: 'Default association group' }
    ];

    // Add groups from the current document's CFAssociationGroupings
    const packageGroups = currentDocumentAssociationGroupings.value.map(grouping => ({
      id: grouping.identifier,
      title: grouping.title || 'Untitled Group',
      description: grouping.description || '',
      uri: grouping.uri,
      lastChangeDateTime: grouping.lastChangeDateTime,
      extensions: grouping.extensions
    }));

    return [...defaultGroups, ...packageGroups];
  });
  
  // Actions
  function selectDocument(document, associationGroupings = [], associations = []) {
    currentDocument.value = document;
    currentDocumentAssociationGroupings.value = associationGroupings || [];
    currentDocumentAssociations.value = (associations || []).map(assoc => ({
      ...assoc,
      groupId: assoc.CFAssociationGroupingURI?.identifier || (typeof assoc.CFAssociationGroupingURI === 'string' ? assoc.CFAssociationGroupingURI : null),
    }));
  }
  
  function clearCurrentDocument() {
    currentDocument.value = null;
    currentDocumentDefinitions.value = {
      concepts: [],
      subjects: [],
      licenses: [],
      itemTypes: [],
      extensions: null
    };
    currentDocumentRubrics.value = [];
    currentDocumentAssociations.value = [];
    currentDocumentAssociationGroupings.value = [];
  }
  
  function transformCASEItems(cfItems, cfAssociations, docId = null) {
    const items = new Map();
    const children = new Map();

    // First pass: create all items
    cfItems.forEach(item => {
      items.set(item.identifier, {
        id: item.id, // Add Salt ID
        identifier: item.identifier,
        uri: item.uri || '',
        title: item.fullStatement || item.abbreviatedStatement || 'Untitled Item',
        fullStatement: item.fullStatement || '',
        abbreviatedTitle: item.abbreviatedStatement || item.fullStatement || 'Untitled Item',
        abbreviatedStatement: item.abbreviatedStatement || null,
        alternativeLabel: item.alternativeLabel || '',
        humanCodingScheme: item.humanCodingScheme || null,
        listEnumeration: item.listEnumeration || null,
        lastChanged: item.lastChangeDateTime || '',
        itemType: item.CFItemType || null,
        CFItemTypeURI: item.CFItemTypeURI || null,
        conceptKeywords: item.conceptKeywords || [],
        conceptKeywordsURI: item.conceptKeywordsURI || null,
        notes: item.notes || null,
        language: item.language || null,
        educationLevel: item.educationLevel || [],
        licenseURI: item.licenseURI || null,
        statusStartDate: item.statusStartDate || null,
        statusEndDate: item.statusEndDate || null,
        subject: item.subject || [],
        subjectURI: item.subjectURI || [],
        extensions: item.extensions || null,
        CFDocumentURI: item.CFDocumentURI || null,
        documentId: docId || (item.CFDocumentURI?.identifier) || null,
        children: [],
        sequenceNumber: 0,
      });
    });

    // Second pass: build parent-child relationships and store associations
    cfAssociations.forEach(assoc => {
      const originId = assoc.originNodeURI?.identifier || assoc.originNodeIdentifier;
      const destinationId = assoc.destinationNodeURI?.identifier || assoc.destinationNodeIdentifier;

      // Store association data on items
      if (items.has(originId)) {
        const originItem = items.get(originId);
        if (!originItem.associations) {
          originItem.associations = [];
        }
        originItem.associations.push({
          id: assoc.id,
          identifier: assoc.identifier,
          associationType: assoc.associationType,
          uri: assoc.uri,
          sequenceNumber: assoc.sequenceNumber,
          originNodeURI: assoc.originNodeURI,
          destinationNodeURI: assoc.destinationNodeURI,
          CFAssociationGroupingURI: assoc.CFAssociationGroupingURI,
          groupId: assoc.CFAssociationGroupingURI?.identifier || (typeof assoc.CFAssociationGroupingURI === 'string' ? assoc.CFAssociationGroupingURI : null),
          lastChangeDateTime: assoc.lastChangeDateTime,
          notes: assoc.notes,
          extensions: assoc.extensions,
          CFDocumentURI: assoc.CFDocumentURI
        });
      }

      if (items.has(destinationId)) {
        const destItem = items.get(destinationId);
        if (!destItem.associations) {
          destItem.associations = [];
        }
        destItem.associations.push({
          id: assoc.id,
          identifier: assoc.identifier,
          associationType: assoc.associationType,
          uri: assoc.uri,
          sequenceNumber: assoc.sequenceNumber,
          originNodeURI: assoc.originNodeURI,
          destinationNodeURI: assoc.destinationNodeURI,
          CFAssociationGroupingURI: assoc.CFAssociationGroupingURI,
          groupId: assoc.CFAssociationGroupingURI?.identifier || (typeof assoc.CFAssociationGroupingURI === 'string' ? assoc.CFAssociationGroupingURI : null),
          lastChangeDateTime: assoc.lastChangeDateTime,
          notes: assoc.notes,
          extensions: assoc.extensions,
          CFDocumentURI: assoc.CFDocumentURI
        });
      }

      // Handle parent-child relationships
      if (assoc.associationType === 'isChildOf') {
        if (items.has(originId) && items.has(destinationId)) {
          const child = items.get(originId);
          const parent = items.get(destinationId);

          child.sequenceNumber = assoc.sequenceNumber || 0;
          // Store the association ID for reordering
          child.childOfAssocId = assoc.id;
          parent.children.push(child);
          children.set(originId, destinationId);
        }
      }
    });

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
          // If both segments are numbers then do a numeric comparison
          const numA = parseInt(segA, 10);
          const numB = parseInt(segB, 10);

          if (numA !== numB) {
            return numA < numB ? -1 : 1;
          }
        } else {
          const cmp = segA.localeCompare(segB);
          if (cmp !== 0) {
            return cmp;
          }
        }
      }

      return 0;
    };

    // Third pass: sort children by sequenceNumber
    items.forEach(item => {
      if (item.children && item.children.length > 0) {
        item.children.sort((a, b) => {
          const seqA = a.sequenceNumber || 0;
          const seqB = b.sequenceNumber || 0;

          if (seqA !== seqB) {
            return seqA - seqB;
          }

          const schemeA = a.humanCodingScheme || '';
          const schemeB = b.humanCodingScheme || '';
          const cmp = compareBySegment(schemeA, schemeB);
          if (cmp !== 0) {
            return cmp;
          }

          const titleA = a.title || '';
          const titleB = b.title || '';

          return titleA.localeCompare(titleB);
        });
      }
    });

    // Get root items (those without parents)
    const rootItems = Array.from(items.values()).filter(item => !children.has(item.identifier));

    // Sort root items
    rootItems.sort((a, b) => {
      const seqA = a.sequenceNumber || 0;
      const seqB = b.sequenceNumber || 0;

      if (seqA !== seqB) {
        return seqA - seqB;
      }

      const schemeA = a.humanCodingScheme || '';
      const schemeB = b.humanCodingScheme || '';
      const cmp = compareBySegment(schemeA, schemeB);
      if (cmp !== 0) {
        return cmp;
      }

      const titleA = a.title || '';
      const titleB = b.title || '';

      return titleA.localeCompare(titleB);
    });

    return rootItems;
  }

  // Batch loading state with LRU cache limit
  const MAX_CACHE_SIZE = 100;
  const associatedDocuments = ref(new Map());
  const loadingAssociatedDocs = ref(false);

  async function fetchAssociatedDocuments(documentStore, identifiers) {
    if (loadingAssociatedDocs.value) return;
    loadingAssociatedDocs.value = true;

    try {
      const uniqueIds = [...new Set(identifiers)].filter(id => !associatedDocuments.value.has(id));
      const batchSize = 5;

      for (let i = 0; i < uniqueIds.length; i += batchSize) {
        const batch = uniqueIds.slice(i, i + batchSize);
        await Promise.all(batch.map(async (id) => {
          try {
            const docData = await documentStore.fetchDocument(id);
            const cfDoc = docData.CFDocument || {};
            const items = transformCASEItems(docData.CFItems || [], docData.CFAssociations || []);

            associatedDocuments.value.set(id, {
              id: cfDoc.identifier,
              title: cfDoc.title,
              items: items,
              // Store minimal data needed for reference
            });

            // Simple cache eviction: remove oldest entries when over limit
            if (associatedDocuments.value.size > MAX_CACHE_SIZE) {
              const firstKey = associatedDocuments.value.keys().next().value;
              associatedDocuments.value.delete(firstKey);
            }
          } catch (err) {
            logger.warn(`Failed to load associated document ${id}`, err);
          }
        }));
      }
    } finally {
      loadingAssociatedDocs.value = false;
    }
  }

  function getAssociatedDocument(id) {
    return associatedDocuments.value.get(id);
  }

  async function updateItems(documentId, lsItems) {
    try {
      // Use API service for consistent error handling
      const data = await api.post(`/doctree/update_items/${documentId}?_format=json`, { lsItems });
      return data;
    } catch (e) {
      logger.error("Error updating items:", e);
      throw e;
    }
  }

  async function addAssociation(documentId, associationData) {
    try {
      const data = await api.post(`/cftree/association/new/${documentId}`, associationData);
      return data;
    } catch (e) {
      logger.error("Error creating association:", e);
      throw e;
    }
  }

  async function removeAssociation(associationId) {
    try {
      await api.post(`/cftree/association/${associationId}/remove`);
      return true;
    } catch (e) {
      logger.error("Error removing association:", e);
      throw e;
    }
  }

  async function deleteItem(itemId) {
    try {
      await api.post(`/cftree/item/delete/${itemId}`);
      return true;
    } catch (e) {
      logger.error("Error deleting item:", e);
      throw e;
    }
  }

  async function createItem(documentId, parentId, itemData) {
    try {
      const data = await api.post(`/cftree/item/new/${parentId}`, itemData);
      return data;
    } catch (e) {
      logger.error("Error creating item:", e);
      throw e;
    }
  }

  async function copyItem(documentId, sourceItem, targetParentId) {
    try {
      // Prepare data for copying
      const itemData = {
        copyFromId: sourceItem.id, // The Salt numeric ID
        addCopyToTitle: 'true',
        // Common fields that might be useful
        title: sourceItem.title,
        fullStatement: sourceItem.fullStatement,
        // The backend logic for copyFromId should handle the rest
      };

      const newItem = await createItem(documentId, targetParentId, itemData);
      return newItem;
    } catch (e) {
      logger.error("Error copying item:", e);
      throw e;
    }
  }

  async function updateItem(documentId, itemId, itemData) {
    try {
      const data = await api.post(`/cftree/item/update/${itemId}`, itemData);
      return data;
    } catch (e) {
      logger.error("Error updating item:", e);
      throw e;
    }
  }

  async function createAssociationGroup(documentId, groupData) {
    try {
      const data = await api.post(`/cftree/association_grouping/new/${documentId}`, groupData);
      return data;
    } catch (e) {
      logger.error("Error creating association group:", e);
      throw e;
    }
  }

  async function updateAssociationGroup(groupId, groupData) {
    try {
      const data = await api.post(`/cftree/association_grouping/update/${groupId}`, groupData);
      return data;
    } catch (e) {
      logger.error("Error updating association group:", e);
      throw e;
    }
  }

  async function deleteAssociationGroup(groupId) {
    try {
      await api.post(`/cftree/association_grouping/delete/${groupId}`);
      return true;
    } catch (e) {
      logger.error("Error deleting association group:", e);
      throw e;
    }
  }

  return {
    currentDocument,
    currentDocumentDefinitions,
    currentDocumentRubrics,
    currentDocumentAssociations,
    currentDocumentAssociationGroupings,
    associationGroups,
    associatedDocuments,
    loadingAssociatedDocs,
    selectDocument,
    clearCurrentDocument,
    transformCASEItems,
    fetchAssociatedDocuments,
    getAssociatedDocument,
    updateItems,
    addAssociation,
    removeAssociation,
    deleteItem,
    createItem,
    updateItem,
    createAssociationGroup,
    updateAssociationGroup,
    deleteAssociationGroup,
    draggedItem,
    setDraggedItem,
    copyItem
  };
});
