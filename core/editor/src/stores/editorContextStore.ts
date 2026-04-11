import { defineStore } from 'pinia';
import { ref, reactive, computed } from 'vue';
import { logger } from '../utils/logger.js';
import type {
    CFItem,
    CFAssociation,
    UUID
} from '../types/case';

export interface RegistryItem {
    item: CFItem;
    frameworkId: UUID;
}

export interface RegistryAssociation {
    association: CFAssociation;
    frameworkId: UUID;
}

export interface RegistryDocument {
    id?: number;
    identifier: UUID;
    uri: string;
    title: string;
    frameworkId: UUID;
}

export interface ExternalEndpoint {
    uri: string;
    title?: string;
    targetType: string;
    cachedAt: string;
}

export interface FrameworkSelectionMode {
    documentId: UUID | null;
    lastSelectedAt: string | null;
    isLoaded: boolean;
}

export type FrameworkSelectionMap = {
    itemDetails: null;
    externalDocument: FrameworkSelectionMode;
    treeView: FrameworkSelectionMode;
    associationView: null;
    logView: null;
};

export interface CachedItemDetails {
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
    licenseURI?: { identifier?: string; uri?: string; title?: string } | null;
    extensions?: Record<string, unknown>;
    lastChangeDateTime?: string;
    documentIdentifier?: string;
    permissions?: { canEdit: boolean };
    associations?: Array<{
        identifier: string;
        associationType: string;
        associationDocumentIdentifier?: string;
        originNodeURI: { identifier?: string; title?: string; uri?: string; documentIdentifier?: string };
        destinationNodeURI: { identifier?: string; title?: string; uri?: string; documentIdentifier?: string };
        [key: string]: unknown;
    }>;
}

export const useEditorContextStore = defineStore('editorContext', () => {
    const documentRegistry = reactive(new Map<UUID, RegistryDocument>());
    const documentUriRegistry = reactive(new Map<string, UUID>());

    const loadedPackages = reactive(new Map<UUID, unknown>());
    const itemRegistry = reactive(new Map<UUID, RegistryItem>());
    const itemUriRegistry = reactive(new Map<string, UUID>());
    const associationRegistry = reactive(new Map<UUID, RegistryAssociation>());
    const externalEndpointRegistry = reactive(new Map<string, ExternalEndpoint>());

    const itemDetailsCache = reactive(new Map<UUID, CachedItemDetails>());

    const activeWriteDocumentId = ref<UUID | null>(null);
    const viewedDocumentId = ref<UUID | null>(null);
    const registryVersion = ref(0);

    const frameworkSelectionState = reactive<FrameworkSelectionMap>({
        itemDetails: null,
        externalDocument: { documentId: null, lastSelectedAt: null, isLoaded: false },
        treeView: { documentId: null, lastSelectedAt: null, isLoaded: false },
        associationView: null,
        logView: null
    });

    function touchRegistry() {
        registryVersion.value++;
    }

    async function initialize() {
        logger.debug('[editorContextStore] initialize — no-op in API-first architecture');
    }

    function registerDocumentMetadata(doc: RegistryDocument) {
        documentRegistry.set(doc.identifier, doc);
        if (doc.uri) documentUriRegistry.set(doc.uri, doc.identifier);
        touchRegistry();
    }

    function registerItem(_item: CFItem, _frameworkId: UUID) {
        logger.debug('[editorContextStore] registerItem is a no-op in API-first architecture');
    }

    function removeFrameworkData(frameworkId: UUID) {
        const documentEntry = documentRegistry.get(frameworkId);
        if (documentEntry?.uri) {
            documentUriRegistry.delete(documentEntry.uri);
        }
        documentRegistry.delete(frameworkId);

        if (activeWriteDocumentId.value === frameworkId) {
            activeWriteDocumentId.value = null;
        }

        if (viewedDocumentId.value === frameworkId) {
            viewedDocumentId.value = null;
        }

        touchRegistry();
    }

    function getFrameworkSelection(mode: 'externalDocument' | 'treeView'): FrameworkSelectionMode {
        const selection = frameworkSelectionState[mode];
        return selection;
    }

    function setFrameworkSelection(mode: 'externalDocument' | 'treeView', documentId: UUID | null) {
        const modeState = frameworkSelectionState[mode];
        if (!modeState) {
            return;
        }

        modeState.documentId = documentId;
        modeState.lastSelectedAt = documentId ? new Date().toISOString() : null;
        modeState.isLoaded = !!documentId;

        if (mode === 'treeView') {
            viewedDocumentId.value = documentId;
        }

        touchRegistry();
    }

    function clearFrameworkSelection(mode: 'externalDocument' | 'treeView') {
        setFrameworkSelection(mode, null);
    }

    function clearAllFrameworkSelections() {
        setFrameworkSelection('externalDocument', null);
        setFrameworkSelection('treeView', null);
    }

    function getMostRecentFramework(): UUID | null {
        const modes: Array<keyof FrameworkSelectionMap> = ['externalDocument', 'treeView'];
        let mostRecent: { documentId: UUID | null; timestamp: string | null } = {
            documentId: null,
            timestamp: null
        };

        for (const mode of modes) {
            const modeState = frameworkSelectionState[mode];
            if (modeState?.documentId && modeState.lastSelectedAt) {
                if (!mostRecent.timestamp || modeState.lastSelectedAt > mostRecent.timestamp) {
                    mostRecent = {
                        documentId: modeState.documentId,
                        timestamp: modeState.lastSelectedAt
                    };
                }
            }
        }

        return mostRecent.documentId;
    }

    async function validateFrameworkSelection(mode: 'externalDocument' | 'treeView'): Promise<boolean> {
        const modeState = frameworkSelectionState[mode];
        if (!modeState?.documentId) return false;

        try {
            const docExists = documentRegistry.has(modeState.documentId);
            if (!docExists) {
                clearFrameworkSelection(mode);
                return false;
            }
            return true;
        } catch (err) {
            logger.error(`Failed to validate framework selection for mode ${mode}:`, err);
            return false;
        }
    }

    async function registerExternalEndpoint(_uri: string, _metadata: Omit<ExternalEndpoint, 'uri' | 'cachedAt'>) {
        logger.debug('[editorContextStore] registerExternalEndpoint is a no-op in API-first architecture');
    }

    function resolveEndpoint(identifier: string): { entity: CFItem | RegistryDocument | ExternalEndpoint; frameworkId: UUID | null; entityType: 'item' | 'document' | 'external' } | null {
        if (!identifier) return null;

        let registeredDoc = documentRegistry.get(identifier as UUID);
        if (!registeredDoc) {
            const uuid = documentUriRegistry.get(identifier);
            if (uuid) registeredDoc = documentRegistry.get(uuid);
        }

        if (registeredDoc) {
            return {
                entity: registeredDoc,
                frameworkId: registeredDoc.frameworkId,
                entityType: 'document' as const
            };
        }

        const cachedDetails = itemDetailsCache.get(identifier as UUID);
        if (cachedDetails) {
            const syntheticItem = {
                identifier: cachedDetails.identifier,
                uri: cachedDetails.uri,
                fullStatement: cachedDetails.fullStatement,
                humanCodingScheme: cachedDetails.humanCodingScheme,
            } as CFItem;
            return {
                entity: syntheticItem,
                frameworkId: (cachedDetails.documentIdentifier as UUID) || null,
                entityType: 'item' as const,
            };
        }

        return null;
    }

    function getAssociations(_identifier: string, _uri?: string, _filter?: { type?: string }): RegistryAssociation[] {
        logger.debug('[editorContextStore] getAssociations is a no-op in API-first architecture; use fetchItemAssociations() instead');
        return [];
    }

    function isEditable(identifier: string): boolean {
        if (!activeWriteDocumentId.value) return false;

        if (identifier === activeWriteDocumentId.value) {
            return true;
        }

        const resolved = resolveEndpoint(identifier);
        if (!resolved) return false;

        if (resolved.entityType === 'document') {
            return (resolved.entity as RegistryDocument).identifier === activeWriteDocumentId.value;
        }

        return resolved.frameworkId === activeWriteDocumentId.value;
    }

    function invalidateItemDetails(identifier: UUID) {
        itemDetailsCache.delete(identifier);
        touchRegistry();
    }

    function invalidateAllItemDetails() {
        itemDetailsCache.clear();
        touchRegistry();
    }

    async function loadPackage(_id: UUID, _options: { suppressNotFound?: boolean } = {}): Promise<never> {
        throw new Error('[editorContextStore] loadPackage is removed in API-first architecture; use documentStore.fetchTree() instead');
    }

    async function fetchExternalItemData(_uri: string): Promise<null> {
        logger.debug('[editorContextStore] fetchExternalItemData is removed in API-first architecture; use fetchItemDetails() instead');
        return null;
    }

    const isViewingDifferentFramework = computed(() => {
        return viewedDocumentId.value !== null &&
            viewedDocumentId.value !== activeWriteDocumentId.value;
    });

    return {
        loadedPackages,
        documentRegistry,
        itemRegistry,
        itemUriRegistry,
        associationRegistry,
        externalEndpointRegistry,
        itemDetailsCache,
        activeWriteDocumentId,
        viewedDocumentId,
        isViewingDifferentFramework,
        initialize,
        loadPackage,
        registerDocumentMetadata,
        registerExternalEndpoint,
        resolveEndpoint,
        getAssociations,
        isEditable,
        fetchExternalItemData,
        registerItem,
        removeFrameworkData,
        invalidateItemDetails,
        invalidateAllItemDetails,
        registryVersion,
        touchRegistry,
        frameworkSelectionState,
        getFrameworkSelection,
        setFrameworkSelection,
        clearFrameworkSelection,
        clearAllFrameworkSelections,
        getMostRecentFramework,
        validateFrameworkSelection
    };
});
