import { defineStore } from 'pinia';
import { ref, computed, type Ref, type ComputedRef } from 'vue';
import { api } from '../services/api.js';
import { logger } from '../utils/logger.js';
import type {
  CFDocument,
  CFDefinitions,
  CFRubric,
  CFAssociation,
  CFAssociationGrouping,
  CFItemNode,
  UUID,
  CFItem,
  CFPckgItem,
  CFAssociation as CaseAssociation,
  LinkURI,
  LinkGenURI,
  CFPackage,
  CFPckgDocument,
  ExtensionObject
} from '../types/case';

/**
 * Tree node type with additional runtime properties for the editor
 */
export interface EditorItemNode extends CFItemNode {
  /** Salt database ID */
  id: number;
  /** Display title (fullStatement or abbreviatedStatement) */
  title: string;
  /** Abbreviated title for display */
  abbreviatedTitle: string;
  /** Human coding scheme */
  humanCodingScheme: string | undefined;
  /** Last changed timestamp */
  lastChanged: string;
  /** Last change timestamp from CASE data */
  lastChangeDateTime: string;
  /** Item type */
  itemType: string | undefined;
  /** Item type URI */
  CFItemTypeURI: LinkURI | undefined;
  /** Concept keywords */
  conceptKeywords: string[];
  /** Concept keywords URI */
  conceptKeywordsURI: LinkURI | undefined;
  /** Notes */
  notes: string | undefined;
  /** Language */
  language: string | undefined;
  /** Education level */
  educationLevel: string[];
  /** License URI */
  licenseURI: LinkURI | undefined;
  /** Status start date */
  statusStartDate: string | undefined;
  /** Status end date */
  statusEndDate: string | undefined;
  /** Subject */
  subject: string[];
  /** Subject URI */
  subjectURI: LinkURI[];
  /** Extensions */
  extensions: ExtensionObject | undefined;
  /** Document ID */
  documentId: UUID | null;
  /** Children */
  children: EditorItemNode[];
  /** Sequence number */
  sequenceNumber: number;
  /** Associations */
  associations?: EditorAssociation[];
  /** Child of association ID for reordering */
  childOfAssocId?: number;
}

/**
 * Association type with additional runtime properties
 */
export interface EditorAssociation extends CaseAssociation {
  /** Salt database ID */
  id?: number;
  /** Group ID for filtering */
  groupId: UUID | null;
}

/**
 * Association group with additional runtime properties
 */
export interface EditorAssociationGrouping extends CFAssociationGrouping {
  /** Group ID (same as identifier) */
  id: UUID;
}

/**
 * Associated document data for cross-document references
 */
export interface AssociatedDocument {
  id: UUID;
  title: string;
  items: EditorItemNode[];
}

/**
 * Document store type for API calls
 */
export interface DocumentStore {
  fetchDocument: (identifier: UUID) => Promise<CFPackage>;
}

export const useCurrentDocumentStore = defineStore('currentDocument', () => {
  // State
  const currentDocument = ref<CFDocument | null>(null);
  const currentDocumentDefinitions = ref<CFDefinitions | null>({
    CFConcepts: [],
    CFSubjects: [],
    CFLicenses: [],
    CFItemTypes: [],
    extensions: undefined
  });
  const currentDocumentRubrics = ref<CFRubric[]>([]);
  const currentDocumentAssociations = ref<EditorAssociation[]>([]);
  const currentDocumentAssociationGroupings = ref<EditorAssociationGrouping[]>([]);
  const draggedItem = ref<EditorItemNode | null>(null);

  // Actions
  function setDraggedItem(item: EditorItemNode | null) {
    draggedItem.value = item;
  }

  const associationGroups = computed(() => {
    const defaultGroups: EditorAssociationGrouping[] = [
      { id: 'all', title: 'All Groups', description: 'Show items from all association groups', identifier: 'all', uri: '', lastChangeDateTime: '', extensions: undefined },
      { id: 'default', title: 'Default Group', description: 'Default association group', identifier: 'default', uri: '', lastChangeDateTime: '', extensions: undefined }
    ];

    // Add groups from the current document's CFAssociationGroupings
    const packageGroups = currentDocumentAssociationGroupings.value.map(grouping => ({
      ...grouping,
      id: grouping.identifier
    }));

    return [...defaultGroups, ...packageGroups];
  });

  // Actions
  function selectDocument(
    document: CFDocument | null,
    associationGroupings: CFAssociationGrouping[] = [],
    associations: CaseAssociation[] = []
  ) {
    currentDocument.value = document;
    currentDocumentAssociationGroupings.value = associationGroupings.map(group => ({
      ...group,
      id: group.identifier
    }));
    currentDocumentAssociations.value = associations.map(assoc => ({
      ...assoc,
      groupId: assoc.CFAssociationGroupingURI?.identifier || (typeof assoc.CFAssociationGroupingURI === 'string' ? assoc.CFAssociationGroupingURI : null),
    }));
  }

  function clearCurrentDocument() {
    currentDocument.value = null;
    currentDocumentDefinitions.value = {
      CFAssociationGroupings: [],
      CFConcepts: [],
      CFSubjects: [],
      CFLicenses: [],
      CFItemTypes: [],
      extensions: undefined
    };
    currentDocumentRubrics.value = [];
    currentDocumentAssociations.value = [];
    currentDocumentAssociationGroupings.value = [];
  }

  function transformCASEItems(
    cfItems: CFPckgItem[],
    cfAssociations: CaseAssociation[],
    docId: UUID | null = null
  ): EditorItemNode[] {
    const items = new Map<UUID, EditorItemNode>();
    const children = new Map<UUID, UUID>();

    // First pass: create all items
    cfItems.forEach(item => {
      items.set(item.identifier, {
        id: 0, // Will be set by backend
        identifier: item.identifier,
        uri: item.uri || '',
        title: item.fullStatement || item.abbreviatedStatement || 'Untitled Item',
        fullStatement: item.fullStatement || '',
        abbreviatedTitle: item.abbreviatedStatement || item.fullStatement || 'Untitled Item',
        abbreviatedStatement: item.abbreviatedStatement || undefined,
        alternativeLabel: item.alternativeLabel || '',
        humanCodingScheme: item.humanCodingScheme || undefined,
        listEnumeration: item.listEnumeration || undefined,
        lastChanged: item.lastChangeDateTime || '',
        lastChangeDateTime: item.lastChangeDateTime || '',
        itemType: item.CFItemType || undefined,
        CFItemTypeURI: item.CFItemTypeURI || undefined,
        conceptKeywords: item.conceptKeywords || [],
        conceptKeywordsURI: item.conceptKeywordsURI || undefined,
        notes: item.notes || undefined,
        language: item.language || undefined,
        educationLevel: item.educationLevel || [],
        licenseURI: item.licenseURI || undefined,
        statusStartDate: item.statusStartDate || undefined,
        statusEndDate: item.statusEndDate || undefined,
        subject: item.subject || [],
        subjectURI: item.subjectURI || [],
        extensions: item.extensions || undefined,
        CFDocumentURI: undefined, // Not in CFPckgItem
        documentId: docId || null,
        children: [],
        sequenceNumber: 0,
        expanded: false,
        selected: false,
        loading: false
      });
    });

    // Second pass: build parent-child relationships and store associations
    cfAssociations.forEach(assoc => {
      const originId = assoc.originNodeURI?.identifier;
      const destinationId = assoc.destinationNodeURI?.identifier;

      // Store association data on items
      if (originId && items.has(originId)) {
        const originItem = items.get(originId)!;
        if (!originItem.associations) {
          originItem.associations = [];
        }
        originItem.associations.push({
          id: 0, // Will be set by backend
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

      if (destinationId && items.has(destinationId)) {
        const destItem = items.get(destinationId)!;
        if (!destItem.associations) {
          destItem.associations = [];
        }
        destItem.associations.push({
          id: 0, // Will be set by backend
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
        if (originId && destinationId && items.has(originId) && items.has(destinationId)) {
            const child = items.get(originId)!;
            const parent = items.get(destinationId)!;

            child.sequenceNumber = assoc.sequenceNumber || 0;
            // Store the association ID for reordering
            child.childOfAssocId = 0; // Will be set by backend
            parent.children.push(child);
            children.set(originId, destinationId);
        }

        if (originId && destinationId && items.has(originId) && (destinationId === docId)) {
            const child = items.get(originId)!;
            child.sequenceNumber = assoc.sequenceNumber || 0;
        }
      }
    });

    function compareBySegment(a: string, b: string): number {
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
    }

    // Third pass: sort children by sequenceNumber, then listEnumeration, then humanCodingScheme segments
    items.forEach(item => {
      if (item.children && item.children.length > 0) {
        item.children.sort((a, b) => {
          // First priority: sequenceNumber from isChildOf association
          const seqA = a.sequenceNumber || 0;
          const seqB = b.sequenceNumber || 0;

          if (seqA !== seqB) {
            return seqA - seqB;
          }

          // Second priority: listEnumeration (listEnumInSource)
          const enumA = a.listEnumeration || '';
          const enumB = b.listEnumeration || '';

          if (enumA !== enumB) {
            return enumA.localeCompare(enumB);
          }

          // Third priority: each segment of humanCodingScheme (divided by . or -)
          const schemeA = a.humanCodingScheme || '';
          const schemeB = b.humanCodingScheme || '';
          const cmp = compareBySegment(schemeA, schemeB);
          if (cmp !== 0) {
            return cmp;
          }

          // Final fallback: title
          const titleA = a.title || '';
          const titleB = b.title || '';

          return titleA.localeCompare(titleB);
        });
      }
    });

    // Get root items (those without parents)
    const rootItems = Array.from(items.values()).filter(item => !children.has(item.identifier));

    // Sort root items by sequenceNumber, then listEnumeration, then humanCodingScheme segments
    rootItems.sort((a, b) => {
      // First priority: sequenceNumber from isChildOf association
      const seqA = a.sequenceNumber || 0;
      const seqB = b.sequenceNumber || 0;

      if (seqA !== seqB) {
        return seqA - seqB;
      }

      // Second priority: listEnumeration (listEnumInSource)
      const enumA = a.listEnumeration || '';
      const enumB = b.listEnumeration || '';

      if (enumA !== enumB) {
        return enumA.localeCompare(enumB);
      }

      // Third priority: each segment of humanCodingScheme (divided by . or -)
      const schemeA = a.humanCodingScheme || '';
      const schemeB = b.humanCodingScheme || '';
      const cmp = compareBySegment(schemeA, schemeB);
      if (cmp !== 0) {
        return cmp;
      }

      // Final fallback: title
      const titleA = a.title || '';
      const titleB = b.title || '';

      return titleA.localeCompare(titleB);
    });

    return rootItems;
  }

  // Batch loading state with LRU cache limit
  const MAX_CACHE_SIZE = 100;
  const associatedDocuments = ref<Map<UUID, AssociatedDocument>>(new Map());
  const loadingAssociatedDocs = ref<boolean>(false);

  async function fetchAssociatedDocuments(documentStore: DocumentStore, identifiers: UUID[]) {
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
            const cfDoc: CFPckgDocument = docData.CFDocument || {} as CFPckgDocument;
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
              if (firstKey) {
                associatedDocuments.value.delete(firstKey);
              }
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

  function getAssociatedDocument(id: UUID): AssociatedDocument | undefined {
    return associatedDocuments.value.get(id);
  }

  async function updateItems(documentId: number, lsItems: Record<string, unknown>) {
    try {
      // Use API service for consistent error handling
      const data = await api.post(`/doctree/update_items/${documentId}?_format=json`, { lsItems });
      return data;
    } catch (e) {
      logger.error("Error updating items:", e);
      throw e;
    }
  }

  async function addAssociation(documentId: number, associationData: Record<string, unknown>) {
    try {
      const data = await api.post(`/cftree/association/new/${documentId}`, associationData);
      return data;
    } catch (e) {
      logger.error("Error creating association:", e);
      throw e;
    }
  }

  async function removeAssociation(associationId: number) {
    try {
      await api.post(`/cftree/association/${associationId}/remove`, {});
      return true;
    } catch (e) {
      logger.error("Error removing association:", e);
      throw e;
    }
  }

  async function deleteItem(itemId: number) {
    try {
      await api.post(`/cftree/item/delete/${itemId}`, {});
      return true;
    } catch (e) {
      logger.error("Error deleting item:", e);
      throw e;
    }
  }

  async function createItem(documentId: number, parentId: number, itemData: Record<string, unknown>) {
    try {
      const data = await api.post(`/cftree/item/new/${parentId}`, itemData);
      return data;
    } catch (e) {
      logger.error("Error creating item:", e);
      throw e;
    }
  }

  async function copyItem(documentId: number, sourceItem: EditorItemNode, targetParentId: number) {
    try {
      // Prepare data for copying
      const itemData: Record<string, unknown> = {
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

  async function updateItem(documentId: number, itemId: number, itemData: Record<string, unknown>) {
    try {
      const data = await api.post(`/cftree/item/update/${itemId}`, itemData);
      return data;
    } catch (e) {
      logger.error("Error updating item:", e);
      throw e;
    }
  }

  async function createAssociationGroup(documentId: number, groupData: Record<string, unknown>) {
    try {
      const data = await api.post(`/cftree/association_grouping/new/${documentId}`, groupData);
      return data;
    } catch (e) {
      logger.error("Error creating association group:", e);
      throw e;
    }
  }

  async function updateAssociationGroup(groupId: number, groupData: Record<string, unknown>) {
    try {
      const data = await api.post(`/cftree/association_grouping/update/${groupId}`, groupData);
      return data;
    } catch (e) {
      logger.error("Error updating association group:", e);
      throw e;
    }
  }

  async function deleteAssociationGroup(groupId: number) {
    try {
      await api.post(`/cftree/association_grouping/delete/${groupId}`, {});
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

// Export types for use in components
export type CurrentDocumentStore = ReturnType<typeof useCurrentDocumentStore>;
