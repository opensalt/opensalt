import { defineStore } from 'pinia';
import { ref, computed } from 'vue';
import { api } from '../services/api.js';
import { logger } from '../utils/logger.js';
import { useDocumentStore, type TreeResponse, type TreeNode, type ItemDetailsResponse, type AssociationDetails } from './documentStore';
import { useViewStore } from './viewStore';
import { useEditorContextStore } from './editorContextStore';
import type {
  CFDocument,
  CFDocumentNode,
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
  ExtensionObject
} from '../types/case';

export interface EditorItemNode extends CFItemNode {
  id: number;
  title: string;
  abbreviatedTitle: string;
  humanCodingScheme: string | undefined;
  lastChanged: string;
  lastChangeDateTime: string;
  itemType: string | undefined;
  CFItemTypeURI: LinkURI | undefined;
  conceptKeywords: string[];
  conceptKeywordsURI: LinkURI | undefined;
  notes: string | undefined;
  language: string | undefined;
  educationLevel: string[];
  licenseURI: LinkURI | undefined;
  statusStartDate: string | undefined;
  statusEndDate: string | undefined;
  subject: string[];
  subjectURI: LinkURI[];
  extensions: ExtensionObject | undefined;
  additionalFields?: Record<string, string>;
  discriminator?: number;
  documentId: UUID | null;
  externalFrameworkTitle?: string;
  children: EditorItemNode[];
  sequenceNumber: number;
  associations?: EditorAssociation[];
  groupIds?: Set<string>;
  childOfAssocId?: string;
  childOfAssociationIdentifier?: string;
  parentIdentifier?: string;
  isCrossFramework?: boolean;
  crossFrameworkUri?: string;
}

export interface EditorAssociation extends CaseAssociation {
  id?: number;
  groupId: UUID | null;
}

export interface EditorAssociationGrouping extends CFAssociationGrouping {
  id: UUID;
}

export interface AssociatedDocument {
  id: UUID;
  title: string;
  items: EditorItemNode[];
  cfAssociations: CaseAssociation[];
}

function isUnresolvedCrossFrameworkPlaceholder(
  item: Partial<EditorItemNode> | Partial<CFItem> | null | undefined
): boolean {
  if (!item?.isCrossFramework) return false;

  const hasFrameworkIdentity = !!(
    item.CFDocumentURI?.identifier ||
    item.CFDocumentURI?.uri ||
    (item as Partial<EditorItemNode>).documentId
  );
  if (hasFrameworkIdentity) return false;

  const displayValue =
    item.fullStatement ||
    item.abbreviatedStatement ||
    (item as Partial<EditorItemNode>).title ||
    '';

  return !displayValue || displayValue === 'Loading...';
}

function mapTreeNodeToEditorNode(node: TreeNode, parentIdentifier?: string): EditorItemNode {
  return {
    id: 0,
    identifier: node.identifier,
    uri: node.uri || '',
    title: node.fullStatement || node.abbreviatedStatement || 'Untitled Item',
    fullStatement: node.fullStatement || '',
    abbreviatedTitle: node.abbreviatedStatement || node.fullStatement || 'Untitled Item',
    abbreviatedStatement: node.abbreviatedStatement || undefined,
    alternativeLabel: '',
    humanCodingScheme: node.humanCodingScheme || undefined,
    listEnumeration: node.listEnumeration || undefined,
    lastChanged: node.lastChangeDateTime || '',
    lastChangeDateTime: node.lastChangeDateTime || '',
    itemType: node.itemType || undefined,
    CFItemTypeURI: undefined,
    conceptKeywords: [],
    conceptKeywordsURI: undefined,
    notes: undefined,
    language: undefined,
    educationLevel: [],
    licenseURI: (node as any).licenseURI || undefined,
    statusStartDate: undefined,
    statusEndDate: undefined,
    subject: [],
    subjectURI: (node as any).subjectURI || [],
    extensions: (node.extensions as ExtensionObject | undefined) || undefined,
    additionalFields: node.additionalFields || undefined,
    discriminator: node.discriminator,
    CFDocumentURI: node.documentIdentifier ? { identifier: node.documentIdentifier, title: node.documentTitle || '', uri: '' } : undefined,
    documentId: (node.documentIdentifier as UUID) || null,
    externalFrameworkTitle: node.isCrossFramework ? (node.documentTitle || undefined) : undefined,
    children: node.children ? node.children.map(child => mapTreeNodeToEditorNode(child, node.identifier)) : [],
    sequenceNumber: node.sequenceNumber ?? 0,
    groupIds: node.associationGroupIdentifier ? new Set([node.associationGroupIdentifier]) : new Set(),
    childOfAssocId: node.childOfAssociationIdentifier || undefined,
    childOfAssociationIdentifier: node.childOfAssociationIdentifier || undefined,
    parentIdentifier: parentIdentifier || undefined,
    isCrossFramework: node.isCrossFramework,
    associations: []
  };
}

export const useCurrentDocumentStore = defineStore('currentDocument', () => {
  const documentStore = useDocumentStore();
  const viewStore = useViewStore();
  const contextStore = useEditorContextStore();

  const currentDocument = ref<CFDocumentNode | null>(null);
  const currentDocumentDefinitions = ref<CFDefinitions | null>({
    CFConcepts: [],
    CFSubjects: [],
    CFLicenses: [],
    CFItemTypes: [],
    extensions: undefined
  });
  const currentDocumentRubrics = ref<CFRubric[]>([]);
  const currentDocumentAssociationGroupings = ref<EditorAssociationGrouping[]>([]);
  const currentDocumentTree = ref<TreeNode[]>([]);

  const itemDetailsCache = new Map<string, { data: ItemDetailsResponse; timestamp: number }>();
  const pendingItemDetailsRequests = new Map<string, Promise<ItemDetailsResponse>>();
  const itemAssociationsCache = new Map<string, { data: AssociationDetails[]; timestamp: number }>();
  const pendingItemAssociationsRequests = new Map<string, Promise<AssociationDetails[]>>();
  const documentAssociationsCache = new Map<string, { data: AssociationDetails[]; timestamp: number }>();
  const pendingDocumentAssociationsRequests = new Map<string, Promise<AssociationDetails[]>>();
  const frameworkAssociationsCache = new Map<string, { data: AssociationDetails[]; timestamp: number }>();
  const pendingFrameworkAssociationsRequests = new Map<string, Promise<AssociationDetails[]>>();

  const ITEM_DETAILS_CACHE_TTL = 5 * 60 * 1000;

  const associationGroups = computed(() => {
    const defaultGroups: EditorAssociationGrouping[] = [
      { id: 'all', title: 'All Groups', description: 'Show items from all association groups', identifier: 'all', uri: '', lastChangeDateTime: '', extensions: undefined },
      { id: 'default', title: 'Default Group', description: 'Default association group', identifier: 'default', uri: '', lastChangeDateTime: '', extensions: undefined }
    ];

    const packageGroups = currentDocumentAssociationGroupings.value.map(grouping => ({
      ...grouping,
      id: grouping.identifier || grouping.uri
    }));

    return [...defaultGroups, ...packageGroups];
  });

  function selectDocument(response: TreeResponse | null) {
    if (!response) {
      clearCurrentDocument();
      return;
    }

    const doc = response.document;
    const docIdentifier = doc.identifier || null;
    const mappedItems = (response.tree || []).map(node => mapTreeNodeToEditorNode(node, docIdentifier || undefined));

    const normalizedDocument = {
      ...doc,
      identifier: doc.identifier || null,
      id: doc.identifier || null,
      uri: doc.uri || '',
      title: doc.title || '',
      description: doc.description || '',
      lastChangeDateTime: doc.lastChangeDateTime || '',
      items: mappedItems,
    } as CFDocumentNode;

    const documentIdentifier = normalizedDocument.identifier;

    currentDocument.value = normalizedDocument;
    contextStore.activeWriteDocumentId = documentIdentifier;
    contextStore.isAdmin = response.permissions?.isAdmin ?? false;

    currentDocumentDefinitions.value = response.definitions
      ? {
          CFAssociationGroupings: response.definitions.CFAssociationGroupings || [],
          CFConcepts: response.definitions.CFConcepts || [],
          CFSubjects: response.definitions.CFSubjects || [],
          CFLicenses: response.definitions.CFLicenses || [],
          CFItemTypes: response.definitions.CFItemTypes || [],
          extensions: undefined
        }
      : {
          CFAssociationGroupings: [],
          CFConcepts: [],
          CFSubjects: [],
          CFLicenses: [],
          CFItemTypes: [],
          extensions: undefined
        };

    const groupings = response.definitions?.CFAssociationGroupings || [];
    currentDocumentAssociationGroupings.value = groupings.map((group: any) => ({
      ...group,
      id: group.identifier || group.uri
    }));

    currentDocumentTree.value = response.tree || [];

    function registerForeignDocs(nodes: TreeNode[]) {
      for (const node of nodes) {
        if (node.isCrossFramework && node.documentIdentifier && !contextStore.documentRegistry.has(node.documentIdentifier)) {
          contextStore.registerDocumentMetadata({
            identifier: node.documentIdentifier,
            uri: '',
            title: node.documentTitle || node.documentIdentifier,
            frameworkId: node.documentIdentifier,
          });
        }
        if (node.children) registerForeignDocs(node.children);
      }
    }
    registerForeignDocs(currentDocumentTree.value);

    if (contextStore.viewedDocumentId === documentIdentifier) {
      contextStore.viewedDocumentId = null;
    }

    if (documentIdentifier) {
      contextStore.registerDocumentMetadata({
        identifier: documentIdentifier,
        uri: normalizedDocument.uri,
        title: normalizedDocument.title,
        frameworkId: documentIdentifier
      });
    }

    function registerTreeItemsInContext(nodes: EditorItemNode[]) {
      for (const node of nodes) {
        if (!contextStore.itemDetailsCache.has(node.identifier)) {
          contextStore.itemDetailsCache.set(node.identifier, {
            identifier: node.identifier,
            uri: node.uri,
            fullStatement: node.fullStatement,
            abbreviatedStatement: node.abbreviatedStatement,
            humanCodingScheme: node.humanCodingScheme,
            listEnumeration: node.listEnumeration,
            documentIdentifier: documentIdentifier ?? undefined,
          });
        }
        if (node.children?.length) {
          registerTreeItemsInContext(node.children);
        }
      }
    }
    registerTreeItemsInContext(mappedItems);
  }

  function clearCurrentDocument() {
    const prevDocId = contextStore.activeWriteDocumentId;
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
    currentDocumentTree.value = [];
    contextStore.viewedDocumentId = null;

    if (prevDocId) {
      const entriesToRemove: string[] = [];
      for (const [key, val] of contextStore.itemDetailsCache) {
        if (val.documentIdentifier === prevDocId) {
          entriesToRemove.push(key);
        }
      }
      entriesToRemove.forEach(key => contextStore.itemDetailsCache.delete(key));
    }
  }

  /**
   * @deprecated Only used for external document loading. Will be removed when
   * external document handling is migrated to API-first.
   */
  async function transformCASEItems(
    cfItems: CFPckgItem[],
    cfAssociations: CaseAssociation[],
    docId: UUID | null = null
  ): Promise<EditorItemNode[]> {
    const items = new Map<string, EditorItemNode>();
    const inDocumentIds = new Set<string>();
    const parentByChild = new Map<string, string>();
    const itemsWithParentItem = new Set<string>();

    function normalizeAssociationGroupId(assoc: CaseAssociation): string {
      return assoc.CFAssociationGroupingURI?.identifier || assoc.CFAssociationGroupingURI?.uri || 'default';
    }

    function getAssociationOriginId(assoc: CaseAssociation | any): string | undefined {
      return assoc.originNodeURI?.identifier || assoc.originNodeIdentifier;
    }

    function getAssociationDestinationId(assoc: CaseAssociation | any): string | undefined {
      return assoc.destinationNodeURI?.identifier || assoc.destinationNodeIdentifier;
    }

    cfItems.forEach(item => {
      inDocumentIds.add(item.identifier);
      items.set(item.identifier, {
        id: 0,
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
        CFDocumentURI: undefined,
        documentId: docId || null,
        children: [],
        sequenceNumber: 0,
        groupIds: new Set(),
        associations: []
      });
    });

    function ensureEditorAssociation(item: EditorItemNode, assoc: CaseAssociation, groupId: string) {
      if (!item.associations) item.associations = [];
      if (!item.associations.some(existing => existing.identifier === assoc.identifier)) {
        const editorAssoc: EditorAssociation = {
          ...assoc,
          groupId: groupId as UUID
        };
        item.associations.push(editorAssoc);
      }
      if (!item.groupIds) item.groupIds = new Set();
      item.groupIds.add(groupId);
    }

    function wouldCreateCycle(childId: string, parentId: string): boolean {
      if (childId === parentId) return true;
      const visited = new Set<string>();
      let currentParentId: string | undefined = parentId;
      while (currentParentId && !visited.has(currentParentId)) {
        if (currentParentId === childId) return true;
        visited.add(currentParentId);
        currentParentId = parentByChild.get(currentParentId);
      }
      return false;
    }

    function attachChildToParent(
      child: EditorItemNode,
      parentId: string,
      parent: EditorItemNode | null,
      assoc: CaseAssociation,
      groupId: string
    ): boolean {
      const existingParentId = parentByChild.get(child.identifier);
      if (existingParentId && existingParentId !== parentId) {
        return false;
      }
      if (wouldCreateCycle(child.identifier, parentId)) {
        return false;
      }
      child.sequenceNumber = assoc.sequenceNumber ?? child.sequenceNumber ?? 0;
      child.childOfAssocId = assoc.identifier || undefined;
      child.childOfAssociationIdentifier = assoc.identifier || undefined;
      child.parentIdentifier = parentId;
      ensureEditorAssociation(child, assoc, groupId);
      if (parent && parentId !== docId) {
        if (!parent.children.some(node => node.identifier === child.identifier)) {
          parent.children.push(child);
        }
        itemsWithParentItem.add(child.identifier);
      } else if (parentId === docId) {
        itemsWithParentItem.delete(child.identifier);
      }
      parentByChild.set(child.identifier, parentId);
      return true;
    }

    cfAssociations.forEach(assoc => {
      const originId = getAssociationOriginId(assoc);
      if (originId && items.has(originId)) {
        const item = items.get(originId)!;
        const groupId = normalizeAssociationGroupId(assoc);
        ensureEditorAssociation(item, assoc, groupId);
      }
    });

    type ChildEdge = {
      assoc: CaseAssociation;
      originId: string;
      destinationId: string;
      groupId: string;
    };

    const immediateEdges: ChildEdge[] = [];

    cfAssociations.forEach(assoc => {
      if (assoc.associationType !== 'isChildOf') return;
      const originId = getAssociationOriginId(assoc);
      const destinationId = getAssociationDestinationId(assoc);
      if (!originId || !destinationId) return;
      const groupId = normalizeAssociationGroupId(assoc);
      const originInDoc = inDocumentIds.has(originId);
      const destInDoc = inDocumentIds.has(destinationId);
      const destinationIsDoc = docId !== null && destinationId === docId;
      if (originInDoc || destInDoc || destinationIsDoc) {
        immediateEdges.push({ assoc, originId, destinationId, groupId });
      }
    });

    immediateEdges.forEach(({ assoc, originId, destinationId, groupId }) => {
      const originInDoc = inDocumentIds.has(originId);
      const destInDoc = inDocumentIds.has(destinationId);
      const destinationIsDoc = docId !== null && destinationId === docId;

      if (originInDoc && destInDoc) {
        const child = items.get(originId)!;
        const parent = items.get(destinationId)!;
        attachChildToParent(child, destinationId, parent, assoc, groupId);
      } else if (originInDoc && !destInDoc) {
        const child = items.get(originId)!;
        if (destinationIsDoc) {
          attachChildToParent(child, destinationId, null, assoc, groupId);
        }
      } else if (!originInDoc && destInDoc) {
        const parent = items.get(destinationId)!;
        // For external docs, only process simple child-of relationships
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

    function itemSortComparator(a: EditorItemNode, b: EditorItemNode): number {
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
      const abbrA = a.abbreviatedStatement || a.abbreviatedTitle || '';
      const abbrB = b.abbreviatedStatement || b.abbreviatedTitle || '';
      if (abbrA !== abbrB) return abbrA.localeCompare(abbrB);
      const fullA = a.fullStatement || '';
      const fullB = b.fullStatement || '';
      return fullA.localeCompare(fullB);
    }

    items.forEach(item => {
      if (item.children && item.children.length > 0) {
        item.children.sort(itemSortComparator);
      }
    });

    const rootItems = Array.from(items.values()).filter(item => !itemsWithParentItem.has(item.identifier));
    rootItems.sort(itemSortComparator);
    return rootItems;
  }

  async function fetchItemDetails(identifier: UUID): Promise<ItemDetailsResponse> {
    const cached = itemDetailsCache.get(identifier);
    if (cached && Date.now() - cached.timestamp < ITEM_DETAILS_CACHE_TTL) {
      return cached.data;
    }

    if (pendingItemDetailsRequests.has(identifier)) {
      return pendingItemDetailsRequests.get(identifier)!;
    }

    const promise = (async (): Promise<ItemDetailsResponse> => {
      try {
        const data = await api.get(`/framework/editor/item/${identifier}/details`) as ItemDetailsResponse;
        itemDetailsCache.set(identifier, { data, timestamp: Date.now() });
        return data;
      } finally {
        pendingItemDetailsRequests.delete(identifier);
      }
    })();

    pendingItemDetailsRequests.set(identifier, promise);
    return promise;
  }

  async function fetchItemAssociations(identifier: UUID): Promise<AssociationDetails[]> {
    const cached = itemAssociationsCache.get(identifier);
    if (cached && Date.now() - cached.timestamp < ITEM_DETAILS_CACHE_TTL) {
      return cached.data;
    }

    if (pendingItemAssociationsRequests.has(identifier)) {
      return pendingItemAssociationsRequests.get(identifier)!;
    }

    const promise = (async (): Promise<AssociationDetails[]> => {
      try {
        const response = await api.get(`/framework/editor/associations/item/${identifier}`) as { data: AssociationDetails[] };
        const data = Array.isArray(response?.data) ? response.data : (Array.isArray(response) ? response : []);
        itemAssociationsCache.set(identifier, { data, timestamp: Date.now() });
        return data;
      } finally {
        pendingItemAssociationsRequests.delete(identifier);
      }
    })();

    pendingItemAssociationsRequests.set(identifier, promise);
    return promise;
  }

  async function fetchDocumentAssociations(identifier: UUID): Promise<AssociationDetails[]> {
    const cached = documentAssociationsCache.get(identifier);
    if (cached && Date.now() - cached.timestamp < ITEM_DETAILS_CACHE_TTL) {
      return cached.data;
    }

    if (pendingDocumentAssociationsRequests.has(identifier)) {
      return pendingDocumentAssociationsRequests.get(identifier)!;
    }

    const promise = (async (): Promise<AssociationDetails[]> => {
      try {
        const response = await api.get(`/framework/editor/associations/document/${identifier}`) as { data: AssociationDetails[] };
        const data = Array.isArray(response?.data) ? response.data : (Array.isArray(response) ? response : []);
        documentAssociationsCache.set(identifier, { data, timestamp: Date.now() });
        return data;
      } finally {
        pendingDocumentAssociationsRequests.delete(identifier);
      }
    })();

    pendingDocumentAssociationsRequests.set(identifier, promise);
    return promise;
  }

  async function fetchFrameworkAssociations(identifier: UUID): Promise<AssociationDetails[]> {
    const cached = frameworkAssociationsCache.get(identifier);
    if (cached && Date.now() - cached.timestamp < ITEM_DETAILS_CACHE_TTL) {
      return cached.data;
    }

    if (pendingFrameworkAssociationsRequests.has(identifier)) {
      return pendingFrameworkAssociationsRequests.get(identifier)!;
    }

    const promise = (async (): Promise<AssociationDetails[]> => {
      try {
        const response = await api.get(`/framework/editor/associations/framework/${identifier}`) as { data: AssociationDetails[] };
        const data = Array.isArray(response?.data) ? response.data : (Array.isArray(response) ? response : []);
        frameworkAssociationsCache.set(identifier, { data, timestamp: Date.now() });
        return data;
      } finally {
        pendingFrameworkAssociationsRequests.delete(identifier);
      }
    })();

    pendingFrameworkAssociationsRequests.set(identifier, promise);
    return promise;
  }

  function invalidateItemDetailsCache(identifier?: UUID) {
    if (identifier) {
      itemDetailsCache.delete(identifier);
      itemAssociationsCache.delete(identifier);
      documentAssociationsCache.delete(identifier);
      frameworkAssociationsCache.delete(identifier);
    } else {
      itemDetailsCache.clear();
      itemAssociationsCache.clear();
      documentAssociationsCache.clear();
      frameworkAssociationsCache.clear();
    }
  }

  function invalidateCurrentDocumentCache() {
    const docId = contextStore.activeWriteDocumentId;
    if (docId) {
      documentStore.invalidateTreeCache(docId);
    }
  }

  async function updateItems(documentIdentifier: UUID, lsItems: Record<string, unknown>) {
    try {
      const data = await api.post(`/framework/editor/document/${documentIdentifier}/update_items`, { lsItems });
      documentStore.invalidateTreeCache(documentIdentifier);
      return data;
    } catch (e) {
      logger.error("Error updating items:", e);
      throw e;
    }
  }

  async function addAssociation(documentIdentifier: UUID, associationData: Record<string, unknown>) {
    try {
      const data = await api.post(`/framework/editor/association/new/${documentIdentifier}`, associationData);
      documentStore.invalidateTreeCache(documentIdentifier);
      return data;
    } catch (e) {
      logger.error("Error creating association:", e);
      throw e;
    }
  }

  async function removeAssociation(associationIdentifier: UUID) {
    try {
      await api.delete(`/framework/editor/association/${associationIdentifier}`);
      invalidateCurrentDocumentCache();
      return true;
    } catch (e) {
      logger.error("Error removing association:", e);
      throw e;
    }
  }

  async function deleteItem(itemIdentifier: UUID) {
    try {
      await api.delete(`/framework/editor/item/${itemIdentifier}`);
      invalidateItemDetailsCache(itemIdentifier);
      invalidateCurrentDocumentCache();
      return true;
    } catch (e) {
      logger.error("Error deleting item:", e);
      throw e;
    }
  }

  async function createItem(parentIdentifier: UUID, itemData: Record<string, unknown>) {
    try {
      const data = await api.post(`/framework/editor/item/new/${parentIdentifier}`, itemData);
      invalidateCurrentDocumentCache();
      return data;
    } catch (e) {
      logger.error("Error creating item:", e);
      throw e;
    }
  }

  async function copyItem(documentIdentifier: UUID, sourceItem: EditorItemNode, targetParentIdentifier: UUID) {
    try {
      const itemData: Record<string, unknown> = {
        copyFromIdentifier: sourceItem.identifier,
        addCopyToTitle: 'true',
      };

      const newItem = await createItem(targetParentIdentifier, itemData);
      return newItem;
    } catch (e) {
      logger.error("Error copying item:", e);
      throw e;
    }
  }

  async function updateItem(itemIdentifier: UUID, itemData: Record<string, unknown>) {
    try {
      const data = await api.put(`/framework/editor/item/${itemIdentifier}`, itemData);
      invalidateItemDetailsCache(itemIdentifier);
      invalidateCurrentDocumentCache();
      return data;
    } catch (e) {
      logger.error("Error updating item:", e);
      throw e;
    }
  }

  async function createAssociationGroup(documentIdentifier: UUID, groupData: Record<string, unknown>) {
    try {
      const data = await api.post(`/framework/editor/association_grouping/new/${documentIdentifier}`, groupData);
      documentStore.invalidateTreeCache(documentIdentifier);
      return data;
    } catch (e) {
      logger.error("Error creating association group:", e);
      throw e;
    }
  }

  async function updateAssociationGroup(groupIdentifier: UUID, groupData: Record<string, unknown>) {
    try {
      const data = await api.put(`/framework/editor/association_grouping/${groupIdentifier}`, groupData);
      invalidateCurrentDocumentCache();
      return data;
    } catch (e) {
      logger.error("Error updating association group:", e);
      throw e;
    }
  }

  async function deleteAssociationGroup(groupIdentifier: UUID) {
    try {
      await api.delete(`/framework/editor/association_grouping/${groupIdentifier}`);
      invalidateCurrentDocumentCache();
      return true;
    } catch (e) {
      logger.error("Error deleting association group:", e);
      throw e;
    }
  }

  function identifyAssociatedFrameworks(): UUID[] {
    return [];
  }

  function setHighPriorityForAssociatedFrameworks() {
    // Legacy: being removed
  }

  function setSelectedItem(item: EditorItemNode | null) {
    viewStore.setCurrentItem(item);
  }

  async function reloadActiveDocument() {
    const id = contextStore.activeWriteDocumentId;
    if (!id) return;

    try {
      const treeResponse = await documentStore.fetchTree(id);
      documentStore.invalidateTreeCache(id);
      const freshResponse = await documentStore.fetchTree(id);
      selectDocument(freshResponse);
    } catch (e) {
      logger.error('[currentDocumentStore] Error reloading active document:', e);
    }
  }

  async function updateDocument(documentIdentifier: UUID, data: Record<string, unknown>) {
    try {
      await api.put(`/framework/editor/document/${documentIdentifier}`, data);
      documentStore.invalidateTreeCache(documentIdentifier);
      return true;
    } catch (e) {
      logger.error("Error updating document:", e);
      throw e;
    }
  }

  async function deleteDocument(documentIdentifier: UUID) {
    try {
      await api.delete(`/framework/editor/document/${documentIdentifier}`);
      documentStore.removeDocument(documentIdentifier);
      contextStore.removeFrameworkData(documentIdentifier);
      clearCurrentDocument();
      viewStore.setCurrentItem(null);
      return true;
    } catch (e) {
      logger.error("Error deleting document:", e);
      throw e;
    }
  }

  async function updateAssociation(associationIdentifier: UUID, data: Record<string, unknown>) {
    try {
      await api.put(`/framework/editor/association/${associationIdentifier}`, data);
      invalidateCurrentDocumentCache();
      return true;
    } catch (e) {
      logger.error("Error updating association:", e);
      throw e;
    }
  }

  return {
    currentDocument,
    currentDocumentDefinitions,
    currentDocumentRubrics,
    currentDocumentAssociationGroupings,
    currentDocumentTree,
    associationGroups,
    selectDocument,
    reloadActiveDocument,
    clearCurrentDocument,
    transformCASEItems,
    fetchItemDetails,
    fetchItemAssociations,
    fetchDocumentAssociations,
    fetchFrameworkAssociations,
    invalidateItemDetailsCache,
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
    updateDocument,
    deleteDocument,
    updateAssociation,
    currentItem: computed(() => viewStore.currentItem),
    setSelectedItem,
    draggedItem: computed(() => viewStore.draggedItem),
    setDraggedItem: (item: any) => viewStore.setDraggedItem(item)
  };
});

export type CurrentDocumentStore = ReturnType<typeof useCurrentDocumentStore>;
