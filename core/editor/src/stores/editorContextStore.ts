import { defineStore } from 'pinia';
import { ref, reactive, computed } from 'vue';
import { api } from '../services/api.js';
import { logger } from '../utils/logger.js';
import { externalEndpointCacheService } from '../services/externalEndpointCacheService.js';
import { editorConfig } from '../config/editorConfig.js';
import type {
    CFPackage,
    CFItem,
    CFAssociation,
    CFDocument,
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

export const useEditorContextStore = defineStore('editorContext', () => {
    // State
    const loadedPackages = reactive(new Map<UUID, CFPackage>());
    const documentRegistry = reactive(new Map<UUID, RegistryDocument>());
    const itemRegistry = reactive(new Map<UUID, RegistryItem>());
    const itemUriRegistry = reactive(new Map<string, UUID>());
    const associationRegistry = reactive(new Map<UUID, RegistryAssociation>());
    const externalEndpointRegistry = reactive(new Map<string, ExternalEndpoint>());
    const documentUriRegistry = reactive(new Map<string, UUID>());
    const activeWriteDocumentId = ref<UUID | null>(null);
    const viewedDocumentId = ref<UUID | null>(null);
    const registryVersion = ref(0);

    /**
     * Increment the registry version to trigger reactive updates in watchers
     */
    function touchRegistry() {
        registryVersion.value++;
    }
    async function initialize() {
        try {
            const endpoints = await externalEndpointCacheService.getAll();
            endpoints.forEach((endpoint: ExternalEndpoint) => {
                externalEndpointRegistry.set(endpoint.uri, endpoint);
            });
        } catch (err) {
            logger.error('Failed to initialize editorContextStore:', err);
        }
    }

    /**
     * Load a full framework package and populate registries
     */
    async function loadPackage(id: UUID): Promise<CFPackage> {
        try {
            const pkg = await api.get(`/ims/case/v1p1/CFPackages/${id}`) as CFPackage;
            if (!pkg) throw new Error(`Package ${id} is null`);

            loadedPackages.set(id, pkg);

            // Populate document registry
            if (pkg.CFDocument) {
                registerDocumentMetadata({
                    identifier: pkg.CFDocument.identifier,
                    uri: pkg.CFDocument.uri,
                    title: pkg.CFDocument.title,
                    frameworkId: id
                });
            }

            // Populate item registry
            if (pkg.CFItems) {
                pkg.CFItems.forEach(item => {
                    registerItem(item, id);
                });
            }

            // Populate association registry
            if (pkg.CFAssociations && !editorConfig.features.useLocalAssociationQueries) {
                pkg.CFAssociations.forEach(assoc => {
                    associationRegistry.set(assoc.identifier, { association: assoc, frameworkId: id });
                });
                logger.debug(`[loadPackage] Registered ${pkg.CFAssociations.length} associations for framework ${id}, total registry: ${associationRegistry.size}`);
            }

            touchRegistry();
            return pkg;
        } catch (err) {
            logger.error(`Failed to load package ${id}:`, err);
            throw err;
        }
    }

    /**
     * Register lightweight document metadata
     */
    function registerDocumentMetadata(doc: RegistryDocument) {
        documentRegistry.set(doc.identifier, doc);
        if (doc.uri) documentUriRegistry.set(doc.uri, doc.identifier);
        touchRegistry();
    }

    /**
     * Register item metadata
     */
    function registerItem(item: CFItem, frameworkId: UUID) {
        itemRegistry.set(item.identifier, { item, frameworkId });
        if (item.uri) itemUriRegistry.set(item.uri, item.identifier);
        touchRegistry();
    }

    function removeFrameworkData(frameworkId: UUID) {
        loadedPackages.delete(frameworkId);

        const documentEntry = documentRegistry.get(frameworkId);
        if (documentEntry?.uri) {
            documentUriRegistry.delete(documentEntry.uri);
        }
        documentRegistry.delete(frameworkId);

        for (const [identifier, entry] of itemRegistry.entries()) {
            if (entry.frameworkId !== frameworkId) {
                continue;
            }

            if (entry.item.uri) {
                itemUriRegistry.delete(entry.item.uri);
            }
            itemRegistry.delete(identifier);
        }

        for (const [identifier, entry] of associationRegistry.entries()) {
            if (entry.frameworkId === frameworkId) {
                associationRegistry.delete(identifier);
            }
        }

        if (activeWriteDocumentId.value === frameworkId) {
            activeWriteDocumentId.value = null;
        }

        if (viewedDocumentId.value === frameworkId) {
            viewedDocumentId.value = null;
        }

        touchRegistry();
    }

    /**
     * Register a non-CASE external endpoint
     */
    async function registerExternalEndpoint(uri: string, metadata: Omit<ExternalEndpoint, 'uri' | 'cachedAt'>) {
        const endpoint: ExternalEndpoint = {
            uri,
            ...metadata,
            cachedAt: new Date().toISOString()
        };
        externalEndpointRegistry.set(uri, endpoint);
        await externalEndpointCacheService.set(uri, endpoint);
        touchRegistry();
    }

    /**
     * Resolve an endpoint (item, document, or external) by identifier or URI
     */
    function resolveEndpoint(identifier: string): { entity: CFItem | RegistryDocument | ExternalEndpoint; frameworkId: UUID | null; entityType: 'item' | 'document' | 'external' } | null {
        if (!identifier) return null;

        // 1. Check item registry
        let registeredItem = itemRegistry.get(identifier as UUID);
        if (!registeredItem) {
            const uuid = itemUriRegistry.get(identifier);
            if (uuid) registeredItem = itemRegistry.get(uuid);
        }

        if (registeredItem) {
            return {
                entity: registeredItem.item,
                frameworkId: registeredItem.frameworkId,
                entityType: 'item' as const
            };
        }

        // 2. Check document registry
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

        // 3. Check external endpoint registry
        const external = externalEndpointRegistry.get(identifier);
        if (external) {
            return {
                entity: external,
                frameworkId: null,
                entityType: 'external' as const
            };
        }

        return null;
    }

    /**
     * Get associations for an entity (item or document)
     */
    function getAssociations(identifier: string, uri?: string, filter?: { type?: string }) {
        if (editorConfig.features.useLocalAssociationQueries) {
            return [];
        }

        const results: (RegistryAssociation)[] = [];
        if (!identifier) return results;

        // Try to resolve the item to get its URI for more robust matching
        const uris: string[] = [identifier];
        if (uri) uris.push(uri);

        const resolved = resolveEndpoint(identifier);
        if (resolved && (resolved.entity as any).uri && !uris.includes((resolved.entity as any).uri)) {
            uris.push((resolved.entity as any).uri);
        }

        logger.debug(`[getAssociations] Looking for ${identifier} (matchable: ${JSON.stringify(uris)}), scanning ${associationRegistry.size} associations`);

        // Scan all registered associations
        associationRegistry.forEach(entry => {
            const assoc = entry.association as any;

            const originId = assoc.originNodeURI?.identifier || assoc.originNodeIdentifier;
            const originUri = assoc.originNodeURI?.uri || assoc.originNodeURI; // Handle case where it might be a string
            const destId = assoc.destinationNodeURI?.identifier || assoc.destinationNodeIdentifier;
            const destUri = assoc.destinationNodeURI?.uri || assoc.destinationNodeURI;

            const matchesOrigin = uris.includes(originId as string) || (typeof originUri === 'string' && uris.includes(originUri));
            const matchesDest = uris.includes(destId as string) || (typeof destUri === 'string' && uris.includes(destUri));

            if (matchesOrigin || matchesDest) {
                if (!filter?.type || assoc.associationType === filter.type) {
                    results.push(entry);
                }
            }
        });

        logger.debug(`[getAssociations] Found ${results.length} matching associations for ${identifier}`);
        return results;
    }

    /**
     * Check if an entity is editable within the current workspace session
     */
    function isEditable(identifier: string): boolean {
        if (!activeWriteDocumentId.value) return false;

        const resolved = resolveEndpoint(identifier);
        if (!resolved) return false;

        // DEBUG: Log isEditable computation
        console.log('[editorContextStore] isEditable called:', {
            identifier,
            activeWriteDocumentId: activeWriteDocumentId.value,
            resolved,
            resolvedFrameworkId: resolved.frameworkId,
            entityType: resolved.entityType,
            'frameworkId === activeWriteDocumentId': resolved.frameworkId === activeWriteDocumentId.value,
            result: resolved.frameworkId === activeWriteDocumentId.value
        });

        // If it's a document, check if it's the active document
        if (resolved.entityType === 'document') {
            return (resolved.entity as RegistryDocument).identifier === activeWriteDocumentId.value;
        }

        // For items, check framework it belongs to
        // Fallback: if item not in registry, check if its document matches active write document
        if (resolved.entityType === 'item' && resolved.frameworkId === null) {
            const item = resolved.entity as CFItem;
            const itemDocId = item.CFDocumentURI?.identifier;
            if (itemDocId && itemDocId === activeWriteDocumentId.value) {
                return true;
            }
        }

        return resolved.frameworkId === activeWriteDocumentId.value;
    }

    /**
     * Extract UUID from a CASE URI
     */
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

    /**
     * Centralized cross-framework fetching
     */
    async function fetchExternalItemData(uri: string) {
        try {
            const registerFetchedItem = (item: CFItem) => {
                const frameworkIdentifier =
                    item.CFDocumentURI?.identifier
                    || extractUuidFromUri(item.CFDocumentURI?.uri || '')
                    || null;
                if (frameworkIdentifier) {
                    itemRegistry.set(item.identifier, { item, frameworkId: frameworkIdentifier as UUID });
                }
            };

            const normalizeFetchedPayload = (payload: any): { item: any; isPackage: boolean } | null => {
                if (!payload) return null;

                if (payload.CFDocument && payload.CFItems) {
                    return { item: payload.CFDocument, isPackage: true };
                }

                const item = payload.item || payload.CFItem || (payload.identifier ? payload : null);
                if (item) {
                    return { item, isPackage: false };
                }

                return null;
            };

            // Try direct CASE URI first (same-host absolute URI or relative path).
            if (uri) {
                try {
                    let directPath = uri;
                    if (/^https?:\/\//i.test(uri)) {
                        const parsed = new URL(uri);
                        directPath = `${parsed.pathname}${parsed.search || ''}`;
                    }

                    const directPayload = await api.get(directPath);
                    const normalizedDirect = normalizeFetchedPayload(directPayload);
                    if (normalizedDirect) {
                        if (!normalizedDirect.isPackage) {
                            registerFetchedItem(normalizedDirect.item as CFItem);
                        }
                        return normalizedDirect;
                    }
                } catch {
                    // Fall through to identifier-based attempts.
                }
            }

            // Build candidate identifiers from UUID, raw input, and URI path segment.
            const candidates: string[] = [];
            const extractedUuid = extractUuidFromUri(uri);
            if (extractedUuid) candidates.push(extractedUuid);
            if (uri) candidates.push(uri);

            try {
                const parsed = new URL(uri);
                const segments = parsed.pathname.split('/').filter(Boolean);
                const lastSegment = decodeURIComponent(segments[segments.length - 1] || '');
                if (lastSegment) candidates.push(lastSegment);
            } catch {
                // Input may already be an identifier, not a URL.
            }

            // De-duplicate while preserving order.
            const uniqueCandidates = Array.from(new Set(candidates.filter(Boolean)));

            for (const candidate of uniqueCandidates) {
                // If this candidate is a document/package id, this will warm registries.
                try {
                    const pkg = await loadPackage(candidate as UUID);
                    if (pkg?.CFDocument) {
                        return { item: pkg.CFDocument, isPackage: true };
                    }
                } catch {
                    // Not a package id; continue with item fetch.
                }

                try {
                    const payload = await api.get(`/ims/case/v1p1/CFItems/${encodeURIComponent(candidate)}`);
                    const normalized = normalizeFetchedPayload(payload);
                    if (normalized) {
                        if (!normalized.isPackage) {
                            registerFetchedItem(normalized.item as CFItem);
                        }
                        return normalized;
                    }
                } catch {
                    // Try next candidate.
                }
            }

            throw new Error(`Failed to fetch external item data for ${uri}`);
        } catch (err) {
            logger.error(`Failed to fetch external item data for ${uri}:`, err);
            return null;
        }
    }

    /**
     * Computed property to check if viewing a different framework than editing
     */
    const isViewingDifferentFramework = computed(() => {
        return viewedDocumentId.value !== null &&
            viewedDocumentId.value !== activeWriteDocumentId.value;
    });

    return {
        loadedPackages,
        documentRegistry,
        itemRegistry,
        associationRegistry,
        externalEndpointRegistry,
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
        registryVersion,
        touchRegistry
    };
});
