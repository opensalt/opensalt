import { defineStore } from 'pinia';
import { ref, reactive, computed } from 'vue';
import { api } from '../services/api.js';
import { logger } from '../utils/logger.js';
import { externalEndpointCacheService } from '../services/externalEndpointCacheService.js';
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

    /**
     * Initialize the store, hydrating registries from persistent caches
     */
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
            if (pkg.CFAssociations) {
                pkg.CFAssociations.forEach(assoc => {
                    associationRegistry.set(assoc.identifier, { association: assoc, frameworkId: id });
                });
                logger.debug(`[loadPackage] Registered ${pkg.CFAssociations.length} associations for framework ${id}, total registry: ${associationRegistry.size}`);
            }

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
    }

    /**
     * Register item metadata
     */
    function registerItem(item: CFItem, frameworkId: UUID) {
        itemRegistry.set(item.identifier, { item, frameworkId });
        if (item.uri) itemUriRegistry.set(item.uri, item.identifier);
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
    }

    /**
     * Resolve an endpoint (item, document, or external) by identifier or URI
     */
    function resolveEndpoint(identifier: string) {
        // DEBUG: Log what we're trying to resolve
        console.log('[resolveEndpoint] Called with:', {
            identifier,
            identifierType: typeof identifier,
            identifierValue: identifier
        });

        if (!identifier) {
            console.log('[resolveEndpoint] Early return: identifier is null/undefined');
            return null;
        }

        // 1. Check item registry
        let registeredItem = itemRegistry.get(identifier as UUID);
        console.log('[resolveEndpoint] Item registry lookup:', {
            lookupKey: identifier,
            found: !!registeredItem,
            registrySize: itemRegistry.size
        });

        if (!registeredItem) {
            const uuid = itemUriRegistry.get(identifier);
            console.log('[resolveEndpoint] Item URI registry lookup:', {
                lookupKey: identifier,
                foundUuid: uuid
            });
            if (uuid) registeredItem = itemRegistry.get(uuid);
        }

        if (registeredItem) {
            console.log('[resolveEndpoint] Found item in registry:', {
                itemIdentifier: registeredItem.item.identifier,
                itemUri: registeredItem.item.uri,
                frameworkId: registeredItem.frameworkId,
                itemType: registeredItem.item.CFItemType
            });
            return {
                entity: registeredItem.item,
                frameworkId: registeredItem.frameworkId,
                entityType: 'item' as const
            };
        }

        // 2. Check document registry
        let registeredDoc = documentRegistry.get(identifier as UUID);
        console.log('[resolveEndpoint] Document registry lookup:', {
            lookupKey: identifier,
            found: !!registeredDoc,
            registrySize: documentRegistry.size
        });

        if (!registeredDoc) {
            const uuid = documentUriRegistry.get(identifier);
            console.log('[resolveEndpoint] Document URI registry lookup:', {
                lookupKey: identifier,
                foundUuid: uuid
            });
            if (uuid) registeredDoc = documentRegistry.get(uuid);
        }

        if (registeredDoc) {
            console.log('[resolveEndpoint] Found document in registry:', {
                docIdentifier: registeredDoc.identifier,
                docUri: registeredDoc.uri,
                frameworkId: registeredDoc.frameworkId
            });
            return {
                entity: registeredDoc,
                frameworkId: registeredDoc.frameworkId,
                entityType: 'document' as const
            };
        }

        // 3. Check external endpoint registry
        const external = externalEndpointRegistry.get(identifier);
        console.log('[resolveEndpoint] External endpoint registry lookup:', {
            lookupKey: identifier,
            found: !!external,
            registrySize: externalEndpointRegistry.size
        });

        if (external) {
            console.log('[resolveEndpoint] Found external endpoint:', {
                uri: external.uri,
                title: external.title,
                targetType: external.targetType
            });
            return {
                entity: external,
                frameworkId: null,
                entityType: 'external' as const
            };
        }

        console.log('[resolveEndpoint] Not found in any registry, returning null');
        return null;
    }

    /**
     * Get associations for an entity (item or document)
     */
    function getAssociations(identifier: string, uri?: string, filter?: { type?: string }) {
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
    function isEditable(identifier: string | CFItem): boolean {
        // DEBUG: Log what's being passed to isEditable
        console.log('[isEditable] Called with:', {
            identifier,
            identifierType: typeof identifier,
            identifierValue: identifier,
            'is CFItem?': identifier && typeof identifier === 'object' && 'identifier' in identifier,
            'has uri?': identifier && typeof identifier === 'object' && 'uri' in identifier
        });

        // Handle case where a CFItem object is passed instead of identifier string
        if (identifier && typeof identifier === 'object' && 'identifier' in identifier) {
            const item = identifier as CFItem;
            console.log('[isEditable] Received CFItem object, extracting identifier:', {
                itemIdentifier: item.identifier,
                itemUri: item.uri,
                itemCFDocumentURI: item.CFDocumentURI
            });
            identifier = item.identifier;
        }

        if (!activeWriteDocumentId.value) {
            console.log('[isEditable] Early return: no active write document');
            return false;
        }

        const resolved = resolveEndpoint(identifier as string);
        if (!resolved) {
            console.log('[isEditable] Early return: resolveEndpoint returned null');
            return false;
        }

        // DEBUG: Log isEditable computation
        console.log('[editorContextStore] isEditable resolved:', {
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
            const result = (resolved.entity as RegistryDocument).identifier === activeWriteDocumentId.value;
            console.log('[isEditable] Document check:', {
                docIdentifier: (resolved.entity as RegistryDocument).identifier,
                activeWriteDocumentId: activeWriteDocumentId.value,
                result
            });
            return result;
        }

        // For items, check framework it belongs to
        // Fallback: if item not in registry, check if its document matches active write document
        if (resolved.entityType === 'item' && resolved.frameworkId === null) {
            const item = resolved.entity as CFItem;
            const itemDocId = item.CFDocumentURI?.identifier;
            console.log('[isEditable] Item with null frameworkId, checking document:', {
                itemIdentifier: item.identifier,
                itemDocId,
                activeWriteDocumentId: activeWriteDocumentId.value,
                matches: itemDocId === activeWriteDocumentId.value
            });
            if (itemDocId && itemDocId === activeWriteDocumentId.value) {
                return true;
            }
        }

        const result = resolved.frameworkId === activeWriteDocumentId.value;
        console.log('[isEditable] Final result:', {
            resolvedFrameworkId: resolved.frameworkId,
            activeWriteDocumentId: activeWriteDocumentId.value,
            result
        });
        return result;
    }

    /**
     * Extract UUID from a CASE URI
     */
    function extractUuidFromUri(uri: string): string | null {
        console.log('[extractUuidFromUri] Called with:', {
            uri,
            uriType: typeof uri,
            uriValue: uri
        });

        if (!uri) {
            console.log('[extractUuidFromUri] Early return: uri is null/undefined');
            return null;
        }

        try {
            const url = new URL(uri);
            console.log('[extractUuidFromUri] Successfully parsed as URL:', {
                protocol: url.protocol,
                hostname: url.hostname,
                pathname: url.pathname
            });

            const segments = url.pathname.split('/').filter(Boolean);
            const lastSegment = segments[segments.length - 1];
            console.log('[extractUuidFromUri] Extracted segments:', {
                segments,
                lastSegment,
                isUuid: lastSegment && /^[0-9a-fA-F-]{36}$/.test(lastSegment)
            });

            if (lastSegment && /^[0-9a-fA-F-]{36}$/.test(lastSegment)) {
                console.log('[extractUuidFromUri] Returning UUID from URL:', lastSegment);
                return lastSegment;
            }
            return null;
        } catch (err) {
            console.log('[extractUuidFromUri] URL parsing failed, trying regex:', {
                uri,
                error: err
            });
            const uuidMatch = uri.match(/([0-9a-fA-F]{8}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{4}-[0-9a-fA-F]{12})/);
            const result = uuidMatch ? uuidMatch[1] : null;
            console.log('[extractUuidFromUri] Regex match result:', {
                uri,
                match: uuidMatch,
                result
            });
            return result;
        }
    }

    /**
     * Centralized cross-framework fetching
     */
    async function fetchExternalItemData(uri: string) {
        console.log('[fetchExternalItemData] Called with:', {
            uri,
            uriType: typeof uri,
            uriValue: uri
        });

        try {
            // 1. Try to extract UUID and fetch locally if possible
            const uuid = extractUuidFromUri(uri);
            console.log('[fetchExternalItemData] Extracted UUID:', {
                uri,
                extractedUuid: uuid
            });

            if (uuid) {
                try {
                    // Try fetching as a package first to get full data
                    const pkg = await loadPackage(uuid);
                    if (pkg) {
                        console.log('[fetchExternalItemData] Successfully loaded package:', {
                            uuid,
                            packageId: pkg.CFDocument?.identifier
                        });
                        return { item: pkg.CFDocument, isPackage: true };
                    }

                    // Fallback to single item fetch if package fetch fails
                    const item = await api.get(`/ims/case/v1p1/CFItems/${uuid}`) as CFItem;
                    if (item) {
                        console.log('[fetchExternalItemData] Successfully fetched item:', {
                            uuid,
                            itemIdentifier: item.identifier,
                            itemUri: item.uri,
                            itemCFDocumentURI: item.CFDocumentURI
                        });
                        // We don't have a frameworkId here yet, ideally we find it from CFDocumentURI
                        const fwId = extractUuidFromUri(item.CFDocumentURI?.uri || '') as UUID;
                        if (fwId) {
                            itemRegistry.set(item.identifier, { item, frameworkId: fwId });
                            console.log('[fetchExternalItemData] Registered item with frameworkId:', {
                                itemIdentifier: item.identifier,
                                frameworkId: fwId
                            });
                        }
                        return { item, isPackage: false };
                    }
                } catch (err) {
                    logger.debug(`Local fetch failed for ${uuid}, trying direct URI`);
                    console.log('[fetchExternalItemData] Local fetch failed:', {
                        uuid,
                        error: err
                    });
                }
            }

            // 2. Fallback to direct URI fetch
            /*
            const response = await api.get(uri);
            return response;
            */
            //throw new Error(`Failed to fetch external item data for ${uri}`); // Do not try using remote URI for now
            const response = await api.get(`/ims/case/v1p1/CFItems/${uuid}`) as CFItem;
            return response;
        } catch (err) {
            logger.error(`Failed to fetch external item data for ${uri}:`, err);
            console.log('[fetchExternalItemData] Error:', {
                uri,
                error: err,
                errorMessage: err instanceof Error ? err.message : String(err)
            });
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
        registerItem
    };
});
