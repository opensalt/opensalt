import { defineStore } from 'pinia';
import { logger } from '../utils/logger.js';
import { ref } from 'vue';
import { api } from '../services/api.js';
import type { CFDocument, CFPackage, UUID } from '../types/case';
import { useEditorContextStore } from './editorContextStore';

interface ApiDocumentListResponse {
  data: CFDocument[];
  pagination?: {
    hasNextPage: boolean;
    nextCursor?: string;
  };
}

interface ApiPackageResponse {
  CFDocument: CFPackage['CFDocument'];
  CFPackageURI?: {
    uri: string;
  };
}

export interface TreeResponse {
  document: {
    identifier: string;
    uri?: string;
    title: string;
    description?: string;
    lastChangeDateTime?: string;
    adoptionStatus?: string;
    language?: string;
    version?: string;
    org?: number | null;
    orgName?: string | null;
    licenseURI?: { identifier?: string; uri?: string; title?: string } | null;
    subject?: string[] | null;
    subjects?: Array<{ identifier?: string; uri?: string; title?: string }>;
    licence?: string | null;
  };
  tree: TreeNode[];
  definitions?: {
    CFAssociationGroupings?: Array<{ identifier: string; uri?: string; title?: string; description?: string }>;
    CFItemTypes?: Array<{ identifier: string; uri?: string; title?: string; description?: string }>;
    CFConcepts?: Array<{ identifier: string; uri?: string; title?: string; keywords?: string }>;
    CFSubjects?: Array<{ identifier: string; uri?: string; title?: string }>;
    CFLicenses?: Array<{ identifier: string; uri?: string; title?: string; description?: string; licenseText?: string }>;
  };
  permissions?: {
    canEdit: boolean;
    isAdmin?: boolean;
  };
}

export interface TreeNode {
  identifier: string;
  uri?: string;
  documentIdentifier?: string;
  documentTitle?: string;
  humanCodingScheme?: string;
  fullStatement?: string;
  abbreviatedStatement?: string;
  listEnumeration?: string;
  itemType?: string;
  sequenceNumber?: number | null;
  lastChangeDateTime?: string;
  childOfAssociationIdentifier?: string | null;
  associationGroupIdentifier?: string | null;
  isCrossFramework: boolean;
  isUnresolved?: boolean;
  discriminator?: number;
  extensions?: Record<string, unknown>;
  children: TreeNode[];
}

export interface ItemDetailsResponse {
  identifier: string;
  uri?: string;
  fullStatement?: string;
  abbreviatedStatement?: string;
  humanCodingScheme?: string;
  listEnumeration?: string;
  notes?: string;
  language?: string;
  educationLevel?: string;
  conceptKeywords?: string;
  itemType?: string;
  CFItemTypeURI?: { title?: string; identifier?: string; uri?: string } | null;
  statusStartDate?: string;
  statusEndDate?: string;
  subject?: string[] | string | null;
  subjectURI?: Array<{ identifier?: string; uri?: string; title?: string }>;
  licenseURI?: { identifier?: string; uri?: string; title?: string } | null;
  licence?: string | null;
  extensions?: Record<string, unknown>;
  lastChangeDateTime?: string;
  documentIdentifier?: string;
  permissions?: { canEdit: boolean };
  associations?: AssociationDetails[];
}

export interface AssociationDetails {
  identifier: string;
  associationType: string;
  associationDocumentIdentifier?: string;
  originNodeURI: {
    identifier?: string;
    title?: string;
    uri?: string;
    documentIdentifier?: string;
  };
  destinationNodeURI: {
    identifier?: string;
    title?: string;
    uri?: string;
    documentIdentifier?: string;
  };
  targetType?: string;
  sequenceNumber?: number | null;
  annotation?: string;
  CFAssociationGroupingURI?: { identifier: string; title?: string } | null;
  canEdit: boolean;
}

export const useDocumentStore = defineStore('documents', () => {
  const documents = ref<CFDocument[]>([]);
  const loading = ref<boolean>(false);
  const error = ref<string | null>(null);
  const loadingSideDocument = ref<boolean>(false);
  const sideDocError = ref<string | null>(null);
  const loadingViewedDocument = ref<boolean>(false);
  const viewedDocError = ref<string | null>(null);

  const treeCache = new Map<UUID, TreeResponse>();
  const pendingTreeRequests = new Map<UUID, Promise<TreeResponse>>();

  async function fetchDocuments(): Promise<void> {
    loading.value = true;
    error.value = null;

    try {
      const endpoint = '/api/v1/documents';
      const limit = 1000;
      let allDocuments: CFDocument[] = [];
      let cursor: string | null = null;
      let hasNextPage = true;

      while (hasNextPage) {
        const params = new URLSearchParams({
          'page[size]': limit.toString(),
        });

        if (cursor) {
          params.append('page[after]', cursor);
        }

        const url = `${endpoint}?${params.toString()}`;
        const data = await api.get(url) as ApiDocumentListResponse;

        if (!data.data || !Array.isArray(data.data)) {
          throw new Error('Invalid response format: expected data array');
        }

        allDocuments = allDocuments.concat(data.data);

        if (data.pagination && typeof data.pagination.hasNextPage === 'boolean') {
          hasNextPage = data.pagination.hasNextPage;
          cursor = data.pagination.nextCursor || null;
        } else {
          hasNextPage = false;
        }
      }

      documents.value = allDocuments;

      const contextStore = useEditorContextStore();
      allDocuments.forEach(doc => {
        contextStore.registerDocumentMetadata({
          identifier: doc.identifier,
          uri: doc.uri,
          title: doc.title,
          frameworkId: doc.identifier
        });
      });
    } catch (err) {
      error.value = (err as Error).message || 'Failed to fetch documents';
      logger.error('Error fetching documents:', err);
      if (documents.value.length === 0) {
        documents.value = [];
      }
    } finally {
      loading.value = false;
    }
  }

  async function fetchTree(identifier: UUID): Promise<TreeResponse> {
    if (treeCache.has(identifier)) {
      return treeCache.get(identifier)!;
    }

    if (pendingTreeRequests.has(identifier)) {
      return pendingTreeRequests.get(identifier)!;
    }

    const promise = (async (): Promise<TreeResponse> => {
      try {
        const response = await api.get(`/framework/editor/tree/${identifier}`) as TreeResponse;
        treeCache.set(identifier, response);
        return response;
      } finally {
        pendingTreeRequests.delete(identifier);
      }
    })();

    pendingTreeRequests.set(identifier, promise);
    return promise;
  }

  async function fetchLightweightTree(identifier: UUID): Promise<TreeResponse> {
    const cacheKey = `lw:${identifier}`;
    if (treeCache.has(cacheKey)) {
      return treeCache.get(cacheKey)!;
    }

    const response = await api.get(`/framework/editor/tree/${identifier}?mode=lightweight`) as TreeResponse;
    treeCache.set(cacheKey, response);
    return response;
  }

  async function fetchDocument(identifier: UUID): Promise<TreeResponse> {
    return fetchTree(identifier);
  }

  async function fetchSideDocument(identifier: UUID): Promise<TreeResponse> {
    loadingSideDocument.value = true;
    sideDocError.value = null;
    try {
      return await fetchLightweightTree(identifier);
    } catch (err) {
      sideDocError.value = (err as Error).message || 'Failed to load side document';
      throw err;
    } finally {
      loadingSideDocument.value = false;
    }
  }

  async function fetchViewedDocument(identifier: UUID): Promise<TreeResponse> {
    loadingViewedDocument.value = true;
    viewedDocError.value = null;
    try {
      return await fetchTree(identifier);
    } catch (err) {
      viewedDocError.value = (err as Error).message || 'Failed to load viewed document';
      throw err;
    } finally {
      loadingViewedDocument.value = false;
    }
  }

  function extractUuidFromUri(uri: string): string | null {
    if (!uri) return null;
    try {
      const url = new URL(uri);
      const segments = url.pathname.split('/').filter(Boolean);
      const lastSegment = segments[segments.length - 1];
      if (lastSegment && /^[0-9a-fA-F-]{36}$/.test(lastSegment)) {
        return lastSegment;
      }
      return null;
    } catch {
      const uuidMatch = uri.match(/([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12})/);
      return uuidMatch ? uuidMatch[1] : null;
    }
  }

  async function loadExternalDocument(url: string): Promise<{ data: CFPackage, finalUrl: string }> {
    loading.value = true;
    error.value = null;
    let finalUrl = url;

    try {
      const uuid = extractUuidFromUri(url);
      if (uuid) {
        try {
          const treeResponse = await fetchTree(uuid);
          return {
            data: {
              CFDocument: treeResponse.document as any,
              CFItems: [],
              CFAssociations: [],
              CFDefinitions: treeResponse.definitions as any || {}
            } as CFPackage,
            finalUrl: url
          };
        } catch {
          // local fetch failed, try external
        }
      }

      const data = await api.get(url) as CFPackage;
      return { data, finalUrl };
    } catch (err) {
      error.value = (err as Error).message || 'Failed to load external document';
      throw err;
    } finally {
      loading.value = false;
    }
  }

  function invalidateTreeCache(identifier: UUID): void {
    treeCache.delete(identifier);
    treeCache.delete(`lw:${identifier}`);
  }

  async function revalidatePackage(identifier: UUID, refetch = false): Promise<TreeResponse | null> {
    invalidateTreeCache(identifier);
    if (refetch) {
      return await fetchTree(identifier);
    }
    return null;
  }

  function clearError(): void { error.value = null; }
  function clearSideDocError(): void { sideDocError.value = null; }
  function clearViewedDocError(): void { viewedDocError.value = null; }

  function resetLoadingSideDocument(): void {
    loadingSideDocument.value = false;
  }

  async function fetchDocumentMetadata(identifier: UUID): Promise<CFDocument | null> {
    try {
      return await api.get(`/ims/case/v1p1/CFDocuments/${identifier}`) as CFDocument;
    } catch (err) {
      logger.warn('Failed to fetch document metadata:', err);
      return null;
    }
  }

  function removeDocument(identifier: UUID): void {
    documents.value = documents.value.filter(doc => doc.identifier !== identifier);
    invalidateTreeCache(identifier);
    sideDocError.value = null;
    if (useEditorContextStore().viewedDocumentId === identifier) {
      viewedDocError.value = null;
    }
  }

  function isDocumentCached(identifier: UUID): boolean {
    return treeCache.has(identifier);
  }

  return {
    documents,
    loading,
    error,
    loadingSideDocument,
    sideDocError,
    loadingViewedDocument,
    viewedDocError,
    fetchDocuments,
    fetchDocument,
    fetchDocumentMetadata,
    fetchSideDocument,
    fetchViewedDocument,
    fetchTree,
    fetchLightweightTree,
    loadExternalDocument,
    clearError,
    clearSideDocError,
    clearViewedDocError,
    resetLoadingSideDocument,
    invalidateTreeCache,
    revalidatePackage,
    isDocumentCached,
    removeDocument,
  };
});
