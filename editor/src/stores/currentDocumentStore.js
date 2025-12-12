import { defineStore } from 'pinia';
import { ref, computed } from 'vue';

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

  // Getters
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
  function selectDocument(document) {
    currentDocument.value = document;
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

  function transformCASEItems(cfItems, cfAssociations) {
    const items = new Map();
    const children = new Map();

    // First pass: create all items
    cfItems.forEach(item => {
      items.set(item.identifier, {
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
          identifier: assoc.identifier,
          associationType: assoc.associationType,
          uri: assoc.uri,
          sequenceNumber: assoc.sequenceNumber,
          originNodeURI: assoc.originNodeURI,
          destinationNodeURI: assoc.destinationNodeURI,
          CFAssociationGroupingURI: assoc.CFAssociationGroupingURI,
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
          identifier: assoc.identifier,
          associationType: assoc.associationType,
          uri: assoc.uri,
          sequenceNumber: assoc.sequenceNumber,
          originNodeURI: assoc.originNodeURI,
          destinationNodeURI: assoc.destinationNodeURI,
          CFAssociationGroupingURI: assoc.CFAssociationGroupingURI,
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

  return {
    currentDocument,
    currentDocumentDefinitions,
    currentDocumentRubrics,
    currentDocumentAssociations,
    currentDocumentAssociationGroupings,
    associationGroups,
    selectDocument,
    clearCurrentDocument,
    transformCASEItems
  };
});
