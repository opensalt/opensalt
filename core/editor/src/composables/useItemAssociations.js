import { ref, shallowRef, computed, watch, onUnmounted, nextTick } from 'vue';
import { useCurrentDocumentStore } from '../stores/currentDocumentStore';
import { useEditorContextStore } from '../stores/editorContextStore';

/**
 * Composable that manages association merging and caching for an item.
 *
 * @param {Object} options
 * @param {import('vue').ComputedRef} options.item - Reactive ref to the current item object
 * @param {import('vue').Ref}         options.displayItem - Reactive ref to the displayItem (may include cross-framework overrides)
 * @returns Association state + handlers for use in ItemDetails
 */
export function useItemAssociations({ item, displayItem }) {
    const currentDocumentStore = useCurrentDocumentStore();
    const contextStore = useEditorContextStore();

    // -----------------------------------------------------------------------
    // Cache & processing state
    // -----------------------------------------------------------------------
    const mergedAssociationsCache = shallowRef(new Map());
    const isProcessingAssociations = ref(false);
    const lastProcessedItemId = ref(null);
    const lastAssociatedDocumentsSize = ref(0);
    const processingItemId = ref(null);
    const processingVersion = ref(0);
    const MAX_CACHE_SIZE = 50;

    // -----------------------------------------------------------------------
    // Delete-association modal state
    // -----------------------------------------------------------------------
    const showDeleteModal = ref(false);
    const associationToDelete = ref(null);

    // -----------------------------------------------------------------------
    // Helpers
    // -----------------------------------------------------------------------

    /** Build a flat identifier → item Map for O(1) lookup */
    function buildItemIndex(items, index = new Map()) {
        if (!items || !Array.isArray(items)) return index;
        for (const it of items) {
            if (it?.identifier) index.set(it.identifier, it);
            if (it?.children?.length) buildItemIndex(it.children, index);
        }
        return index;
    }

    function clearAssociationsCache() {
        mergedAssociationsCache.value = new Map();
        lastProcessedItemId.value = null;
    }

    /** Compute the merged association groups for a given item identifier */
    function computeMergedAssociations(itemIdentifier) {
        if (!itemIdentifier) return [];

        const itemUri =
            item.value?.uri ||
            item.value?.crossFrameworkUri ||
            displayItem.value?.uri;
        const allContextAssociations = contextStore.getAssociations(itemIdentifier, itemUri);

        console.debug(
            `[useItemAssociations] computeMergedAssociations for ${itemIdentifier}: ` +
            `found ${allContextAssociations.length} associations from registry ` +
            `(registry size: ${contextStore.associationRegistry.size}, ` +
            `loadedPackages: ${contextStore.loadedPackages.size})`
        );

        const currentAssociations = allContextAssociations.map((regAssoc) => ({
            ...regAssoc.association,
            _sourceFrameworkId: regAssoc.frameworkId,
            groupId:
                regAssoc.association.CFAssociationGroupingURI?.identifier ||
                (typeof regAssoc.association.CFAssociationGroupingURI === 'string'
                    ? regAssoc.association.CFAssociationGroupingURI
                    : null),
        }));

        // Build item index (unused but kept for consistent O(1) access pattern)
        buildItemIndex(currentDocumentStore.currentDocument?.items);

        const filteredAssociations = currentAssociations.filter((a) => {
            const assocType = a.associationType || a.type;
            if (assocType === 'isChildOf') {
                const assocFrameworkId = a._sourceFrameworkId;
                if (!assocFrameworkId) return false;
                const displayedFrameworkId = contextStore.isViewingDifferentFramework
                    ? contextStore.viewedDocumentId
                    : contextStore.activeWriteDocumentId;
                return assocFrameworkId !== displayedFrameworkId;
            }
            return true;
        });

        console.debug(
            `[useItemAssociations] After filter: ${filteredAssociations.length} of ` +
            `${currentAssociations.length} associations remain`
        );

        const groupedAssociations = {};
        filteredAssociations.forEach((assoc) => {
            const associationType =
                assoc.associationType || assoc.type || assoc.association?.type || 'unknown';
            const destId = assoc.destinationNodeURI?.identifier;
            const direction = destId === itemIdentifier ? 'reversed' : 'normal';
            const groupKey = `${associationType}-${direction}`;

            if (!groupedAssociations[groupKey]) {
                groupedAssociations[groupKey] = { type: associationType, direction, associations: [] };
            }
            groupedAssociations[groupKey].associations.push(assoc);
        });

        const result = Object.values(groupedAssociations);
        console.debug(
            `[useItemAssociations] Final: ${result.length} groups with ${filteredAssociations.length} total associations`
        );
        return result;
    }

    /**
     * Process associations asynchronously to avoid blocking the UI.
     * Uses requestIdleCallback when available, falls back to setTimeout.
     */
    async function processAssociationsAsync(
        itemIdentifier,
        version,
        background = false,
        force = false
    ) {
        if (!itemIdentifier) return;

        if (!force && mergedAssociationsCache.value.has(itemIdentifier)) return;

        if (!background) {
            isProcessingAssociations.value = true;
        }

        await nextTick();

        const scheduleTask =
            typeof requestIdleCallback !== 'undefined'
                ? (cb) => {
                    try {
                        return requestIdleCallback(cb, { timeout: 100 });
                    } catch {
                        return setTimeout(cb, 0);
                    }
                }
                : (cb) => setTimeout(cb, 0);

        return new Promise((resolve) => {
            scheduleTask(() => {
                if (version !== processingVersion.value) {
                    resolve();
                    return;
                }
                try {
                    const result = computeMergedAssociations(itemIdentifier);
                    const newCache = new Map(mergedAssociationsCache.value);
                    if (newCache.size >= MAX_CACHE_SIZE) {
                        newCache.delete(newCache.keys().next().value);
                    }
                    newCache.set(itemIdentifier, result);
                    mergedAssociationsCache.value = newCache;
                } catch (error) {
                    console.error('Error processing associations:', error);
                    const errorCache = new Map(mergedAssociationsCache.value);
                    errorCache.set(itemIdentifier, []);
                    mergedAssociationsCache.value = errorCache;
                } finally {
                    if (version === processingVersion.value) {
                        isProcessingAssociations.value = false;
                    }
                    resolve();
                }
            });
        });
    }

    // -----------------------------------------------------------------------
    // Watchers
    // -----------------------------------------------------------------------

    // Re-compute when the selected item changes
    watch(
        () => item.value?.identifier,
        async (newItemId, oldItemId) => {
            if (newItemId && newItemId !== oldItemId) {
                currentDocumentStore.setSelectedItem(item.value);

                const currentDocsSize = contextStore.loadedPackages.size;
                if (currentDocsSize !== lastAssociatedDocumentsSize.value) {
                    clearAssociationsCache();
                    lastAssociatedDocumentsSize.value = currentDocsSize;
                }

                if (processingItemId.value === newItemId) return;

                const version = ++processingVersion.value;
                processingItemId.value = newItemId;
                lastProcessedItemId.value = newItemId;
                await processAssociationsAsync(newItemId, version);
                processingItemId.value = null;
            }
        },
        { immediate: true }
    );

    // Re-compute when associations on the item change (add/delete)
    watch(
        () => item.value?.associations?.length,
        () => {
            if (item.value?.identifier) {
                const newCache = new Map(mergedAssociationsCache.value);
                newCache.delete(item.value.identifier);
                mergedAssociationsCache.value = newCache;
                if (lastProcessedItemId.value === item.value.identifier) {
                    const version = ++processingVersion.value;
                    processAssociationsAsync(item.value.identifier, version);
                }
            }
        }
    );

    // Re-compute in the background when the registry grows
    let registryDebounceTimer = null;
    watch(
        () => contextStore.loadedPackages.size + contextStore.associationRegistry.size + contextStore.registryVersion,
        (newTotal, oldTotal) => {
            if (newTotal > oldTotal && lastProcessedItemId.value) {
                console.debug(
                    `[useItemAssociations] Registry changed: ${oldTotal} -> ${newTotal}, ` +
                    `will re-process ${lastProcessedItemId.value}`
                );
                if (registryDebounceTimer) clearTimeout(registryDebounceTimer);
                registryDebounceTimer = setTimeout(() => {
                    const version = ++processingVersion.value;
                    processAssociationsAsync(lastProcessedItemId.value, version, true, true);
                }, 200);
            }
        }
    );

    // -----------------------------------------------------------------------
    // Delete modal handlers
    // -----------------------------------------------------------------------

    /**
     * @param {Object} association
     * @param {boolean} isCrossFrameworkItem
     * @param {boolean} canEditItem
     */
    function handleDeleteAssociationRequest(association, isCrossFrameworkItem, canEditItem) {
        if (!canEditItem) return;

        if (isCrossFrameworkItem) {
            const associationType = association.associationType || association.type;
            if (associationType !== 'isChildOf') return;
        }

        associationToDelete.value = association;
        showDeleteModal.value = true;
    }

    function handleDeleteConfirmed(association, emit) {
        emit('delete-association', association);
        showDeleteModal.value = false;
    }

    function handleDeleteModalHidden() {
        associationToDelete.value = null;
    }

    // -----------------------------------------------------------------------
    // Cleanup
    // -----------------------------------------------------------------------
    onUnmounted(() => {
        processingVersion.value++;
        isProcessingAssociations.value = false;
        processingItemId.value = null;
        if (registryDebounceTimer) clearTimeout(registryDebounceTimer);
    });

    // -----------------------------------------------------------------------
    // Derived computed
    // -----------------------------------------------------------------------
    const mergedAssociations = computed(() => {
        if (!item.value?.identifier) return [];
        if (mergedAssociationsCache.value.has(item.value.identifier)) {
            return mergedAssociationsCache.value.get(item.value.identifier);
        }
        return [];
    });

    return {
        mergedAssociations,
        isProcessingAssociations,
        showDeleteModal,
        associationToDelete,
        handleDeleteAssociationRequest,
        handleDeleteConfirmed,
        handleDeleteModalHidden,
    };
}
