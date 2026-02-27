import { defineStore } from 'pinia';
import { ref, computed, type Ref, type ComputedRef, watch } from 'vue';
import { api } from '../services/api.js';
import { logger } from '../utils/logger.js';
import { useRelatedFrameworksQueue } from '../composables/useRelatedFrameworksQueue.js';
import { useDocumentStore } from './documentStore';
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
  /** Flag indicating this is a cross-framework item (not in current document) */
  isCrossFramework?: boolean;
  /** Loading state for cross-framework items being fetched */
  loading?: boolean;
  /** URI of the cross-framework item for lazy loading */
  crossFrameworkUri?: string;
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
  cfAssociations: CaseAssociation[];
}

/**
 * Document store type for API calls
 */
export interface DocumentStore {
  fetchDocument: (identifier: UUID) => Promise<CFPackage>;
}

export const useCurrentDocumentStore = defineStore('currentDocument', () => {
  // Initialize queuing system for related frameworks
  // Type as any since it's a JavaScript composable
  const queue: any = useRelatedFrameworksQueue();

  // Access document store for cached frameworks
  const documentStore = useDocumentStore();

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
  const currentItem = ref<EditorItemNode | null>(null);

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
    associations: CaseAssociation[] = [],
    definitions: CFDefinitions | null = null
  ) {
    currentDocument.value = document;
    currentDocumentDefinitions.value = definitions || {
      CFAssociationGroupings: [],
      CFConcepts: [],
      CFSubjects: [],
      CFLicenses: [],
      CFItemTypes: [],
      extensions: undefined
    };
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

        // Handle cross-framework parents: destination (parent) not in current document but origin (child) is
        if (originId && destinationId && items.has(originId) && !items.has(destinationId)) {
          let parent = items.get(destinationId);
          if (!parent) {
            const destUri = assoc.destinationNodeURI;
            parent = {
              id: 0,
              identifier: destinationId,
              uri: destUri?.uri || '',
              title: destUri?.title || 'Loading...',
              fullStatement: destUri?.title || 'Loading...',
              abbreviatedTitle: destUri?.title || 'Loading...',
              abbreviatedStatement: undefined,
              alternativeLabel: '',
              humanCodingScheme: undefined,
              listEnumeration: undefined,
              lastChanged: '',
              lastChangeDateTime: '',
              itemType: undefined,
              CFItemTypeURI: undefined,
              conceptKeywords: [],
              conceptKeywordsURI: undefined,
              notes: undefined,
              language: undefined,
              educationLevel: [],
              licenseURI: undefined,
              statusStartDate: undefined,
              statusEndDate: undefined,
              subject: [],
              subjectURI: [],
              extensions: undefined,
              CFDocumentURI: assoc.CFDocumentURI,
              documentId: null,
              children: [],
              sequenceNumber: 0,
              expanded: false,
              selected: false,
              loading: true,
              isCrossFramework: true,
              crossFrameworkUri: destUri?.uri
            };
            items.set(destinationId, parent);
          }
          const child = items.get(originId)!;
          child.sequenceNumber = assoc.sequenceNumber || 0;
          child.childOfAssocId = 0;
          parent.children.push(child);
          children.set(originId, destinationId);
        }

        // Handle cross-framework children: origin (child) not in current document but destination (parent) is
        if (originId && destinationId && !items.has(originId) && items.has(destinationId)) {
          const parent = items.get(destinationId)!;
          const originUri = assoc.originNodeURI;

          // Create a placeholder child item for the cross-framework reference
          const placeholderChild: EditorItemNode = {
            id: 0,
            identifier: originId,
            uri: originUri?.uri || '',
            title: originUri?.title || 'Loading...',
            fullStatement: originUri?.title || 'Loading...',
            abbreviatedTitle: originUri?.title || 'Loading...',
            abbreviatedStatement: undefined,
            alternativeLabel: '',
            humanCodingScheme: undefined,
            listEnumeration: undefined,
            lastChanged: '',
            lastChangeDateTime: '',
            itemType: undefined,
            CFItemTypeURI: undefined,
            conceptKeywords: [],
            conceptKeywordsURI: undefined,
            notes: undefined,
            language: undefined,
            educationLevel: [],
            licenseURI: undefined,
            statusStartDate: undefined,
            statusEndDate: undefined,
            subject: [],
            subjectURI: [],
            extensions: undefined,
            CFDocumentURI: undefined,
            documentId: null,
            children: [],
            sequenceNumber: assoc.sequenceNumber || 0,
            expanded: false,
            selected: false,
            // Cross-framework specific flags
            isCrossFramework: true,
            loading: true,
            crossFrameworkUri: originUri?.uri
          };

          parent.children.push(placeholderChild);
          // Note: We don't add to children map since this is a cross-framework reference
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
              cfAssociations: docData.CFAssociations || [],
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

  /**
   * Get associations for a specific item by identifier
   * @param {UUID} itemIdentifier - The item identifier
   * @returns {EditorAssociation[]} - Array of associations for the item
   */
  function getAssociationsForItem(itemIdentifier: UUID): EditorAssociation[] {
    if (!itemIdentifier) return [];

    // Find all associations where this item is either origin or destination
    return currentDocumentAssociations.value.filter(assoc => {
      const originId = assoc.originNodeURI?.identifier;
      const destId = assoc.destinationNodeURI?.identifier;
      return originId === itemIdentifier || destId === itemIdentifier;
    });
  }

  /**
   * Identify frameworks associated with the currently selected item
   * Scans all cached frameworks for associations referencing this item
   * @returns {UUID[]} - Array of document identifiers
   */
  function identifyAssociatedFrameworks(): UUID[] {
    const item = currentItem.value;
    if (!item) return [];

    const identifiers = new Set<UUID>();

    // Scan all cached frameworks for associations that reference the current item
    documentStore.documentCache.forEach((cachedDoc, docId) => {
      // Skip the current document
      if (docId === currentDocument.value?.identifier) return;

      const hasRelevantAssociation = cachedDoc.CFAssociations?.some(assoc => {
        const originId = assoc.originNodeURI?.identifier;
        const destId = assoc.destinationNodeURI?.identifier;
        return originId === item.identifier || destId === item.identifier;
      });

      if (hasRelevantAssociation) {
        identifiers.add(docId);
      }
    });

    return Array.from(identifiers);
  }

  /**
   * Set HIGH priority for frameworks associated with the currently selected item
   * This ensures that frameworks shown in the Item Details panel are prioritized
   */
  function setHighPriorityForAssociatedFrameworks() {
    const associatedIds = identifyAssociatedFrameworks();

    if (associatedIds.length === 0) {
      logger.debug('No associated frameworks found for current item');
      return;
    }

    // Set HIGH priority for each associated framework in the queue
    // Type assertion to ensure queue has the expected method
    if (typeof queue.updateItemPriority === 'function') {
      associatedIds.forEach(identifier => {
        queue.updateItemPriority(identifier, 'HIGH');
      });
    }

    logger.debug(`Set HIGH priority for ${associatedIds.length} associated frameworks:`, associatedIds);
  }

  /**
   * Set the currently selected item and trigger priority updates
   * @param {EditorItemNode | null} item - The selected item or null
   */
  function setSelectedItem(item: EditorItemNode | null) {
    currentItem.value = item;

    // When an item is selected, set HIGH priority for its associated frameworks
    if (item) {
      setHighPriorityForAssociatedFrameworks();
    }
  }

  // Watch for changes to current item and trigger priority updates
  watch(currentItem, (newItem) => {
    if (newItem) {
      setHighPriorityForAssociatedFrameworks();
    }
  });

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
    copyItem,
    currentItem,
    setSelectedItem,
    identifyAssociatedFrameworks
  };
});

// Export types for use in components
export type CurrentDocumentStore = ReturnType<typeof useCurrentDocumentStore>;
