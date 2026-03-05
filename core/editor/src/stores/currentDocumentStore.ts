import { defineStore } from 'pinia';
import { ref, computed, type Ref, type ComputedRef, nextTick } from 'vue';
import { api } from '../services/api.js';
import { logger } from '../utils/logger.js';
import { useRelatedFrameworksQueue } from '../composables/useRelatedFrameworksQueue.js';
import { useDocumentStore } from './documentStore';
import { useViewStore } from './viewStore';
import { useEditorContextStore } from './editorContextStore';
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
  const viewStore = useViewStore();
  const contextStore = useEditorContextStore();

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
  const currentDocumentAssociationGroupings = ref<EditorAssociationGrouping[]>([]);

  // viewedDocument state moved to contextStore.viewedDocumentId

  // Actions

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

  // isViewingDifferentFramework moved to contextStore

  // Actions
  function selectDocument(
    document: CFDocument | null,
    associationGroupings: CFAssociationGrouping[] = [],
    associations: CaseAssociation[] = [],
    definitions: CFDefinitions | null = null
  ) {
    currentDocument.value = document;
    contextStore.activeWriteDocumentId = document?.identifier || null;

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

    // If viewed document matches the new current document, clear it
    if (contextStore.viewedDocumentId === document?.identifier) {
      contextStore.viewedDocumentId = null;
    }

    // Ensure main framework data is in contextStore registries
    if (document) {
      contextStore.registerDocumentMetadata({
        identifier: document.identifier,
        uri: document.uri,
        title: document.title,
        frameworkId: document.identifier
      });

      // Register associations in global registry
      associations.forEach(assoc => {
        contextStore.associationRegistry.set(assoc.identifier, {
          association: assoc,
          frameworkId: document.identifier
        });
      });

      // Register items in global registry for cross-framework lookup
      if ((document as any).items) {
        (function registerItems(items: any[]) {
          items.forEach(item => {
            contextStore.registerItem(item as any, (document as any).identifier);
            if (item.children) registerItems(item.children);
          });
        })((document as any).items);
      }
    }
  }

  function clearCurrentDocument() {
    currentDocument.value = null;
    contextStore.activeWriteDocumentId = null;
    currentDocumentDefinitions.value = {
      CFAssociationGroupings: [],
      CFConcepts: [],
      CFSubjects: [],
      CFLicenses: [],
      CFItemTypes: [],
      extensions: undefined
    };
    currentDocumentRubrics.value = [];
    currentDocumentAssociationGroupings.value = [];
    // Also clear viewed document when clearing current document
    contextStore.viewedDocumentId = null;
  }

  // viewedDocument actions moved to contextStore

  function transformCASEItems(
    cfItems: CFPckgItem[],
    cfAssociations: CaseAssociation[],
    docId: UUID | null = null
  ): EditorItemNode[] {
    const items = new Map<UUID, EditorItemNode>();
    const children = new Map<UUID, UUID>(); // originId -> destinationId for all isChildOf
    const itemsWithParentItem = new Set<UUID>(); // items that are children of another ITEM (not document)

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
        sequenceNumber: 0
      });
    });

    // Second pass: build parent-child relationships (only isChildOf)
    cfAssociations.forEach(assoc => {
      if (assoc.associationType !== 'isChildOf') return;

      const originId = assoc.originNodeURI?.identifier;
      const destinationId = assoc.destinationNodeURI?.identifier;
      if (!originId || !destinationId) return;

      const originInDoc = items.has(originId);
      const destInDoc = items.has(destinationId);

      if (originInDoc && destInDoc) {
        // Case 1: Both origin and destination are items in the current document
        const child = items.get(originId)!;
        const parent = items.get(destinationId)!;
        child.sequenceNumber = assoc.sequenceNumber ?? 0;
        child.childOfAssocId = 0;
        parent.children.push(child);
        children.set(originId, destinationId);
        itemsWithParentItem.add(originId);

      } else if (originInDoc && !destInDoc) {
        // Case 2: Origin is in the doc, destination is NOT
        const child = items.get(originId)!;
        child.sequenceNumber = assoc.sequenceNumber ?? 0;
        child.childOfAssocId = 0;
        children.set(originId, destinationId);

        if (destinationId === docId) {
          // Case 2a: isChildOf the document → this is a root item
          // Don't add to any parent's children array; it will be collected as a root
        } else {
          // Case 2b: Cross-framework parent (destination is in another framework)
          // Create a placeholder parent node for display purposes
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
              isCrossFramework: true,
              crossFrameworkUri: destUri?.uri
            };
            items.set(destinationId, parent);
          }
          parent.children.push(child);
          itemsWithParentItem.add(originId);
        }

      } else if (!originInDoc && destInDoc) {
        // Case 3: Cross-framework child (origin is from another framework)
        const parent = items.get(destinationId)!;
        const originUri = assoc.originNodeURI;

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
          sequenceNumber: assoc.sequenceNumber ?? 0,
          isCrossFramework: true,
          crossFrameworkUri: originUri?.uri
        };

        parent.children.push(placeholderChild);
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
    function itemSortComparator(a: EditorItemNode, b: EditorItemNode): number {
      // 1. sequenceNumber
      const seqA = a.sequenceNumber ?? 0;
      const seqB = b.sequenceNumber ?? 0;
      if (seqA !== seqB) return seqA - seqB;

      // 2. humanCodingScheme (by segments)
      const schemeA = a.humanCodingScheme || '';
      const schemeB = b.humanCodingScheme || '';
      const schemeCmp = compareBySegment(schemeA, schemeB);
      if (schemeCmp !== 0) return schemeCmp;

      // 3. listEnumeration
      const enumA = a.listEnumeration || '';
      const enumB = b.listEnumeration || '';
      if (enumA !== enumB) return enumA.localeCompare(enumB);

      // 4. abbreviatedStatement / abbreviatedTitle
      const abbrA = a.abbreviatedStatement || a.abbreviatedTitle || '';
      const abbrB = b.abbreviatedStatement || b.abbreviatedTitle || '';
      if (abbrA !== abbrB) return abbrA.localeCompare(abbrB);

      // 5. fullStatement
      const fullA = a.fullStatement || '';
      const fullB = b.fullStatement || '';
      return fullA.localeCompare(fullB);
    }

    // Third pass: sort children
    items.forEach(item => {
      if (item.children && item.children.length > 0) {
        item.children.sort(itemSortComparator);
      }
    });

    // Get root items: items NOT placed into another item's children array
    // This includes both document-children (isChildOf -> document) and orphans
    const rootItems = Array.from(items.values()).filter(item => !itemsWithParentItem.has(item.identifier));

    // Sort root items
    rootItems.sort(itemSortComparator);

    return rootItems;
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
   * LEGACY: identifyAssociatedFrameworks is being moved to editorContextStore logic
   */
  function identifyAssociatedFrameworks(): UUID[] {
    return [];
  }

  function setHighPriorityForAssociatedFrameworks() {
    // Legacy: being removed
  }

  function setSelectedItem(item: EditorItemNode | null) {
    viewStore.setCurrentItem(item);
  }

  return {
    currentDocument,
    currentDocumentDefinitions,
    currentDocumentRubrics,
    currentDocumentAssociationGroupings,
    associationGroups,
    selectDocument,
    clearCurrentDocument,
    transformCASEItems,
    updateItems,
    addAssociation,
    removeAssociation,
    deleteItem,
    createItem,
    updateItem,
    createAssociationGroup,
    updateAssociationGroup,
    deleteAssociationGroup,
    copyItem,
    currentItem: computed(() => viewStore.currentItem),
    setSelectedItem,
    draggedItem: computed(() => viewStore.draggedItem),
    setDraggedItem: (item: any) => viewStore.setDraggedItem(item)
  };
});

// Export types for use in components
export type CurrentDocumentStore = ReturnType<typeof useCurrentDocumentStore>;
