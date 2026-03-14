import { defineStore } from 'pinia';
import { ref, computed, type Ref, type ComputedRef, nextTick } from 'vue';
import { api } from '../services/api.js';
import { logger } from '../utils/logger.js';
import { useRelatedFrameworksQueue } from '../composables/useRelatedFrameworksQueue.js';
import { useDocumentStore } from './documentStore';
import { useViewStore } from './viewStore';
import { useEditorContextStore } from './editorContextStore';
import { frameworkCacheService } from '../services/frameworkCacheService.js';
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
  /** Pre-calculated group IDs for faster filtering */
  groupIds?: Set<string>;
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
      id: grouping.identifier || grouping.uri
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
      id: group.identifier || group.uri
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
            if (isUnresolvedCrossFrameworkPlaceholder(item)) {
              if (item.children) registerItems(item.children);
              return;
            }

            const registeredFrameworkId =
              item.documentId ||
              item.CFDocumentURI?.identifier ||
              contextStore.resolveEndpoint(item.CFDocumentURI?.uri || item.crossFrameworkUri || item.uri || item.identifier)?.frameworkId ||
              (document as any).identifier;
            contextStore.registerItem(item as any, registeredFrameworkId);
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
    const items = new Map<string, EditorItemNode>();
    const inDocumentIds = new Set<string>();
    const parentByChild = new Map<string, string>();
    const itemsWithParentItem = new Set<string>(); // items that are children of another ITEM (not document)

    function normalizeAssociationGroupId(assoc: CaseAssociation): string {
      return assoc.CFAssociationGroupingURI?.identifier || assoc.CFAssociationGroupingURI?.uri || 'default';
    }

    function normalizeAssociationNodeLink(
      rawLink: LinkGenURI | string | undefined,
      fallbackIdentifier?: string
    ): LinkGenURI | undefined {
      if (!rawLink && !fallbackIdentifier) return undefined;

      if (rawLink && typeof rawLink === 'object') {
        return {
          ...rawLink,
          identifier: rawLink.identifier || fallbackIdentifier || '',
          title: rawLink.title || '',
          uri: rawLink.uri || ''
        };
      }

      if (typeof rawLink === 'string') {
        return {
          identifier: fallbackIdentifier || rawLink,
          title: '',
          uri: rawLink
        };
      }

      return fallbackIdentifier
        ? {
            identifier: fallbackIdentifier,
            title: '',
            uri: ''
          }
        : undefined;
    }

    function getAssociationOriginId(assoc: CaseAssociation | any): string | undefined {
      return assoc.originNodeURI?.identifier || assoc.originNodeIdentifier;
    }

    function getAssociationDestinationId(assoc: CaseAssociation | any): string | undefined {
      return assoc.destinationNodeURI?.identifier || assoc.destinationNodeIdentifier;
    }

    function getAssociationOriginLink(assoc: CaseAssociation | any): LinkGenURI | undefined {
      return normalizeAssociationNodeLink(assoc.originNodeURI, getAssociationOriginId(assoc));
    }

    function getAssociationDestinationLink(assoc: CaseAssociation | any): LinkGenURI | undefined {
      return normalizeAssociationNodeLink(assoc.destinationNodeURI, getAssociationDestinationId(assoc));
    }

    function resolveRegisteredItem(
      identifier: string,
      link?: LinkGenURI
    ): { item: Partial<CFItem> | null; frameworkId: UUID | null } {
      const registered = contextStore.itemRegistry.get(identifier);
      if (registered?.item && !isUnresolvedCrossFrameworkPlaceholder(registered.item as Partial<EditorItemNode>)) {
        return {
          item: registered.item as Partial<CFItem>,
          frameworkId: registered.frameworkId as UUID
        };
      }

      const resolved = contextStore.resolveEndpoint(link?.uri || identifier);
      if (resolved?.entityType === 'item' && !isUnresolvedCrossFrameworkPlaceholder(resolved.entity as Partial<EditorItemNode>)) {
        return {
          item: resolved.entity as Partial<CFItem>,
          frameworkId: resolved.frameworkId as UUID | null
        };
      }

      return { item: null, frameworkId: null };
    }

    function resolveItemFrameworkId(
      item: Partial<CFItem> | null | undefined,
      fallbackFrameworkId: UUID | null = null
    ): UUID | null {
      if (!item) return fallbackFrameworkId;

      return (
        item.CFDocumentURI?.identifier ||
        contextStore.resolveEndpoint(item.CFDocumentURI?.uri || '')?.frameworkId ||
        contextStore.itemRegistry.get(item.identifier as UUID)?.frameworkId ||
        contextStore.resolveEndpoint(item.uri || item.identifier || '')?.frameworkId ||
        fallbackFrameworkId
      ) as UUID | null;
    }

    function applyAuthoritativeItemData(
      node: EditorItemNode,
      sourceItem: Partial<CFItem> | Partial<CFPckgItem> | null | undefined,
      sourceFrameworkId: UUID | null = null
    ) {
      if (!sourceItem) return;

      const authoritativeTitle =
        sourceItem.fullStatement?.trim() ||
        sourceItem.abbreviatedStatement?.trim() ||
        (sourceItem as any).title?.trim() ||
        node.title ||
        node.identifier;
      const resolvedFrameworkId = resolveItemFrameworkId(sourceItem as Partial<CFItem>, sourceFrameworkId);

      node.id = (sourceItem as any).id ?? node.id;
      node.identifier = sourceItem.identifier || node.identifier;
      node.uri = sourceItem.uri || node.uri || '';
      node.title = authoritativeTitle;
      node.fullStatement = sourceItem.fullStatement ?? node.fullStatement ?? authoritativeTitle;
      node.abbreviatedTitle = sourceItem.abbreviatedStatement ?? sourceItem.fullStatement ?? node.abbreviatedTitle ?? authoritativeTitle;
      node.abbreviatedStatement = sourceItem.abbreviatedStatement ?? node.abbreviatedStatement;
      node.alternativeLabel = sourceItem.alternativeLabel ?? node.alternativeLabel ?? '';
      node.humanCodingScheme = sourceItem.humanCodingScheme ?? node.humanCodingScheme;
      node.listEnumeration = (sourceItem as any).listEnumeration ?? (sourceItem as any).listEnumInSource ?? node.listEnumeration;
      node.lastChanged = sourceItem.lastChangeDateTime ?? node.lastChanged;
      node.lastChangeDateTime = sourceItem.lastChangeDateTime ?? node.lastChangeDateTime;
      node.itemType = sourceItem.CFItemType ?? (sourceItem as any).itemType ?? node.itemType;
      node.CFItemTypeURI = sourceItem.CFItemTypeURI ?? node.CFItemTypeURI;
      node.conceptKeywords = sourceItem.conceptKeywords ?? node.conceptKeywords;
      node.conceptKeywordsURI = sourceItem.conceptKeywordsURI ?? node.conceptKeywordsURI;
      node.notes = sourceItem.notes ?? node.notes;
      node.language = sourceItem.language ?? node.language;
      node.educationLevel = sourceItem.educationLevel ?? node.educationLevel;
      node.licenseURI = sourceItem.licenseURI ?? node.licenseURI;
      node.statusStartDate = sourceItem.statusStartDate ?? node.statusStartDate;
      node.statusEndDate = sourceItem.statusEndDate ?? node.statusEndDate;
      node.subject = sourceItem.subject ?? node.subject;
      node.subjectURI = sourceItem.subjectURI ?? node.subjectURI;
      node.extensions = sourceItem.extensions ?? node.extensions;

      if (resolvedFrameworkId) {
        node.documentId = resolvedFrameworkId;
        node.isCrossFramework = resolvedFrameworkId !== docId;
        if (node.isCrossFramework) {
          node.crossFrameworkUri = sourceItem.uri || node.crossFrameworkUri || node.uri;
        } else {
          node.crossFrameworkUri = undefined;
        }
      }
    }

    function isGenericAssociationNodeTitle(title?: string): boolean {
      if (!title) return true;
      const normalized = title.trim().toLowerCase();
      return normalized === 'origin node' || normalized === 'destination node';
    }

    function getRegistryTitle(identifier: string): string | null {
      const registered = resolveRegisteredItem(identifier);
      if (registered.item) {
        const item = registered.item as any;
        return item.fullStatement || item.abbreviatedStatement || item.title || item.humanCodingScheme || null;
      }

      const resolved = contextStore.resolveEndpoint(identifier);
      if (!resolved) return null;

      if (resolved.entityType === 'item') {
        const item = resolved.entity as any;
        return item.fullStatement || item.abbreviatedStatement || item.title || item.humanCodingScheme || null;
      }

      if (resolved.entityType === 'document') {
        const doc = resolved.entity as any;
        return doc.title || null;
      }

      return null;
    }

    function getPlaceholderTitle(identifier: string, link: LinkGenURI | undefined): string {
      const registryTitle = getRegistryTitle(identifier);
      if (registryTitle) return registryTitle;

      if (link?.title && !isGenericAssociationNodeTitle(link.title)) {
        return link.title;
      }

      return 'Loading...';
    }

    // First pass: create all items
    cfItems.forEach(item => {
      inDocumentIds.add(item.identifier);
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

    function getOrCreatePlaceholder(
      link: LinkGenURI | undefined,
      groupId: string,
      assoc: CaseAssociation,
      sourceItem: Partial<CFItem> | Partial<CFPckgItem> | null = null,
      sourceFrameworkId: UUID | null = null
    ): EditorItemNode | null {
      const identifier = link?.identifier;
      if (!identifier) return null;
      const placeholderTitle = getPlaceholderTitle(identifier, link);
      const registered = resolveRegisteredItem(identifier, link);
      const authoritativeItem = sourceItem || registered.item;
      const authoritativeFrameworkId = sourceFrameworkId || registered.frameworkId;

      let node = items.get(identifier);
      if (!node) {
        node = {
          id: 0,
          identifier,
          uri: link?.uri || '',
          title: placeholderTitle,
          fullStatement: placeholderTitle,
          abbreviatedTitle: placeholderTitle,
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
          crossFrameworkUri: link?.uri,
          groupIds: new Set(),
          associations: []
        };
        items.set(identifier, node);
      }

      if (authoritativeItem) {
        applyAuthoritativeItemData(node, authoritativeItem, authoritativeFrameworkId);
      } else {
        if (!node.crossFrameworkUri && link?.uri) node.crossFrameworkUri = link.uri;
        const resolvedTitle = getPlaceholderTitle(identifier, link);
        if ((!node.title || node.title === 'Loading...' || isGenericAssociationNodeTitle(node.title)) && resolvedTitle !== 'Loading...') {
          node.title = resolvedTitle;
          node.fullStatement = resolvedTitle;
          node.abbreviatedTitle = resolvedTitle;
        }
      }

      if (!node.groupIds) node.groupIds = new Set();
      node.groupIds.add(groupId);
      return node;
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
        // Keep the first discovered parent to avoid duplicate/ambiguous attachment.
        return false;
      }

      if (wouldCreateCycle(child.identifier, parentId)) {
        return false;
      }

      child.sequenceNumber = assoc.sequenceNumber ?? child.sequenceNumber ?? 0;
      child.childOfAssocId = 0;
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

    function markAnchoredBranch(
      item: EditorItemNode,
      anchoredIds: Set<string>,
      visited: Set<string> = new Set()
    ) {
      if (visited.has(item.identifier) || anchoredIds.has(item.identifier)) return;

      visited.add(item.identifier);
      anchoredIds.add(item.identifier);
      item.children.forEach(child => markAnchoredBranch(child, anchoredIds, visited));
      visited.delete(item.identifier);
    }

    function buildAnchoredIdSet(): Set<string> {
      const anchoredIds = new Set<string>();
      const roots = Array.from(items.values()).filter(item => !itemsWithParentItem.has(item.identifier));
      roots.forEach(root => markAnchoredBranch(root, anchoredIds));
      return anchoredIds;
    }

    function enrichAnchoredExternalBranches() {
      const loadedPackages = Array.from(contextStore.loadedPackages.entries())
        .filter(([frameworkId]) => frameworkId !== docId);

      if (loadedPackages.length === 0) return;

      const anchoredIds = buildAnchoredIdSet();
      let progressed = true;

      while (progressed) {
        progressed = false;

        loadedPackages.forEach(([frameworkId, pkg]) => {
          const packageItems = new Map((pkg.CFItems || []).map(item => [item.identifier, item]));

          (pkg.CFAssociations || []).forEach(assoc => {
            if (assoc.associationType !== 'isChildOf') return;

            const originId = getAssociationOriginId(assoc);
            const destinationId = getAssociationDestinationId(assoc);
            if (!originId || !destinationId) return;

            const destinationIsDoc = docId !== null && destinationId === docId;
            if (!destinationIsDoc && !anchoredIds.has(destinationId) && !inDocumentIds.has(destinationId)) {
              return;
            }

            const groupId = normalizeAssociationGroupId(assoc);
            const destinationLink = getAssociationDestinationLink(assoc);
            const originLink = getAssociationOriginLink(assoc);
            const destinationRegistry = resolveRegisteredItem(destinationId, destinationLink);
            const destinationSource =
              packageItems.get(destinationId) ||
              destinationRegistry.item;
            const destinationFrameworkId =
              resolveItemFrameworkId(destinationSource as Partial<CFItem>, frameworkId as UUID) ||
              destinationRegistry.frameworkId;

            let parent: EditorItemNode | null = null;
            if (!destinationIsDoc) {
              parent = items.get(destinationId) || getOrCreatePlaceholder(
                destinationLink,
                groupId,
                assoc,
                destinationSource,
                destinationFrameworkId
              );
              if (!parent) return;
              applyAuthoritativeItemData(parent, destinationSource, destinationFrameworkId);
            }

            const originRegistry = resolveRegisteredItem(originId, originLink);
            const originSource =
              packageItems.get(originId) ||
              originRegistry.item;
            const originFrameworkId =
              resolveItemFrameworkId(originSource as Partial<CFItem>, frameworkId as UUID) ||
              originRegistry.frameworkId;
            const child = items.get(originId) || getOrCreatePlaceholder(
              originLink,
              groupId,
              assoc,
              originSource,
              originFrameworkId
            );
            if (!child) return;

            applyAuthoritativeItemData(child, originSource, originFrameworkId);

            if (attachChildToParent(child, destinationId, parent, assoc, groupId) && !anchoredIds.has(originId)) {
              markAnchoredBranch(child, anchoredIds);
              progressed = true;
            }
          });
        });
      }
    }

    // Associate everything (including non-isChildOf) with local origin items for filtering.
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
    const deferredEdges: ChildEdge[] = [];

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
      } else {
        deferredEdges.push({ assoc, originId, destinationId, groupId });
      }
    });

    // Pass 1: process all edges that directly touch the viewed framework.
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
        } else {
          const parent = getOrCreatePlaceholder(getAssociationDestinationLink(assoc), groupId, assoc);
          if (parent) {
            attachChildToParent(child, destinationId, parent, assoc, groupId);
          }
        }
      } else if (!originInDoc && destInDoc) {
        const parent = items.get(destinationId)!;
        const child = getOrCreatePlaceholder(getAssociationOriginLink(assoc), groupId, assoc);
        if (child) {
          attachChildToParent(child, destinationId, parent, assoc, groupId);
        }
      } else if (!originInDoc && !destInDoc && destinationIsDoc) {
        // Cross-framework item directly attached to document root.
        const child = getOrCreatePlaceholder(getAssociationOriginLink(assoc), groupId, assoc);
        if (child) {
          attachChildToParent(child, destinationId, null, assoc, groupId);
        }
      }
    });

    // Pass 2: resolve deferred external->external edges only when destination is anchored.
    let unresolved = deferredEdges;
    let progressed = true;
    while (progressed && unresolved.length > 0) {
      progressed = false;
      const nextUnresolved: ChildEdge[] = [];

      unresolved.forEach(edge => {
        const destinationIsAnchored =
          edge.destinationId === docId ||
          parentByChild.has(edge.destinationId) ||
          inDocumentIds.has(edge.destinationId);

        if (!destinationIsAnchored) {
          nextUnresolved.push(edge);
          return;
        }

        const parent = items.get(edge.destinationId) ||
          getOrCreatePlaceholder(getAssociationDestinationLink(edge.assoc), edge.groupId, edge.assoc);
        const child = getOrCreatePlaceholder(getAssociationOriginLink(edge.assoc), edge.groupId, edge.assoc);

        if (!parent || !child) {
          nextUnresolved.push(edge);
          return;
        }

        attachChildToParent(child, edge.destinationId, parent, edge.assoc, edge.groupId);
        progressed = true;
      });

      unresolved = nextUnresolved;
    }

    enrichAnchoredExternalBranches();

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
  async function updateItems(documentIdentifier: UUID, lsItems: Record<string, unknown>) {
    try {
      // Use API service for consistent error handling
      const data = await api.post(`/framework/editor/document/${documentIdentifier}/update_items`, { lsItems });
      return data;
    } catch (e) {
      logger.error("Error updating items:", e);
      throw e;
    }
  }

  async function addAssociation(documentIdentifier: UUID, associationData: Record<string, unknown>) {
    try {
      const data = await api.post(`/framework/editor/association/new/${documentIdentifier}`, associationData);
      return data;
    } catch (e) {
      logger.error("Error creating association:", e);
      throw e;
    }
  }

  async function removeAssociation(associationIdentifier: UUID) {
    try {
      await api.delete(`/framework/editor/association/${associationIdentifier}`);
      
      // Manually remove from local registry so UI updates immediately upon reloadActiveDocument
      const contextStore = useEditorContextStore();
      const assocData = contextStore.associationRegistry.get(associationIdentifier);
      if (assocData) {
        const docId = assocData.frameworkId;
        contextStore.associationRegistry.delete(associationIdentifier);
        const pkg = contextStore.loadedPackages.get(docId);
        if (pkg && pkg.CFAssociations) {
          pkg.CFAssociations = pkg.CFAssociations.filter(a => a.identifier !== associationIdentifier);
        }
      }
      
      return true;
    } catch (e) {
      logger.error("Error removing association:", e);
      throw e;
    }
  }

  async function deleteItem(itemIdentifier: UUID) {
    try {
      await api.delete(`/framework/editor/item/${itemIdentifier}`);
      return true;
    } catch (e) {
      logger.error("Error deleting item:", e);
      throw e;
    }
  }

  async function createItem(parentIdentifier: UUID, itemData: Record<string, unknown>) {
    try {
      const data = await api.post(`/framework/editor/item/new/${parentIdentifier}`, itemData);
      return data;
    } catch (e) {
      logger.error("Error creating item:", e);
      throw e;
    }
  }

  async function copyItem(documentIdentifier: UUID, sourceItem: EditorItemNode, targetParentIdentifier: UUID) {
    try {
      // Prepare data for copying
      const itemData: Record<string, unknown> = {
        copyFromIdentifier: sourceItem.identifier,
        addCopyToTitle: 'true',
        // Common fields that might be useful
        title: sourceItem.title,
        fullStatement: sourceItem.fullStatement,
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
      return data;
    } catch (e) {
      logger.error("Error updating item:", e);
      throw e;
    }
  }

  async function createAssociationGroup(documentIdentifier: UUID, groupData: Record<string, unknown>) {
    try {
      const data = await api.post(`/framework/editor/association_grouping/new/${documentIdentifier}`, groupData);
      return data;
    } catch (e) {
      logger.error("Error creating association group:", e);
      throw e;
    }
  }

  async function updateAssociationGroup(groupIdentifier: UUID, groupData: Record<string, unknown>) {
    try {
      const data = await api.put(`/framework/editor/association_grouping/${groupIdentifier}`, groupData);
      return data;
    } catch (e) {
      logger.error("Error updating association group:", e);
      throw e;
    }
  }

  async function deleteAssociationGroup(groupIdentifier: UUID) {
    try {
      await api.delete(`/framework/editor/association_grouping/${groupIdentifier}`);
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

  /**
   * Reload the active document from the central registry
   * and re-run transformations (used after background revalidation)
   */
  function reloadActiveDocument() {
    const id = contextStore.activeWriteDocumentId;
    if (!id) return;

    const pkg = contextStore.loadedPackages.get(id);
    if (!pkg || !pkg.CFDocument) return;

    logger.debug('[currentDocumentStore] Reloading active document from fresh registry data');

    // 1. Transform items using fresh package data
    const items = transformCASEItems(
      pkg.CFItems || [],
      pkg.CFAssociations || [],
      pkg.CFDocument.identifier
    );

    // 2. Prepare transformed document (mirroring useDocumentLoader logic)
    const transformedDoc = {
      ...pkg.CFDocument,
      id: pkg.CFDocument.identifier,
      items: items,
      // Ensure specific fields required by UI are mapped
      lastModified: pkg.CFDocument.lastChangeDateTime || ''
    };

    // 3. Re-select the document (this updates everything reactively)
    selectDocument(
      transformedDoc as any,
      pkg.CFDefinitions?.CFAssociationGroupings || [],
      pkg.CFAssociations || [],
      pkg.CFDefinitions || null
    );
  }

  async function updateDocument(documentIdentifier: UUID, data: Record<string, unknown>) {
    try {
      await api.put(`/framework/editor/document/${documentIdentifier}`, data);
      return true;
    } catch (e) {
      logger.error("Error updating document:", e);
      throw e;
    }
  }

  async function deleteDocument(documentIdentifier: UUID) {
    try {
      await api.delete(`/framework/editor/document/${documentIdentifier}`);
      await frameworkCacheService.deleteFramework(documentIdentifier);
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
    associationGroups,
    selectDocument,
    reloadActiveDocument,
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
    updateDocument,
    deleteDocument,
    updateAssociation,
    currentItem: computed(() => viewStore.currentItem),
    setSelectedItem,
    draggedItem: computed(() => viewStore.draggedItem),
    setDraggedItem: (item: any) => viewStore.setDraggedItem(item)
  };
});

// Export types for use in components
export type CurrentDocumentStore = ReturnType<typeof useCurrentDocumentStore>;
