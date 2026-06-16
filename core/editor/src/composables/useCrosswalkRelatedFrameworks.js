import { computed } from 'vue';
import { useCurrentDocumentStore } from '../stores/currentDocumentStore';
import { api } from '../services/api.js';
import { logger } from '../utils/logger.js';

/**
 * Maximum number of items a framework can have to be considered a "pure crosswalk"
 * (i.e., a framework focused on associations between other frameworks rather than
 * having items of its own).
 *
 * Configurable in-code, not a runtime option.
 */
const CROSSWALK_ITEM_THRESHOLD = 0;

/**
 * Extension key for explicitly declared related framework identifiers on a
 * CFDocument's extensions object.
 *
 * When present, the value should be an array of UUID strings identifying the
 * frameworks whose items participate in this crosswalk's associations.
 *
 * Example extensions value:
 *   { "salt:relatedFrameworks": ["uuid-1", "uuid-2"] }
 */
const RELATED_FRAMEWORKS_EXTENSION_KEY = 'salt:relatedFrameworks';

/**
 * Composable that identifies the frameworks referenced by a crosswalk framework's
 * associations, using a tiered fallback strategy:
 *
 *  1. Tier 1 — Framework extensions: Check the current document's
 *     `salt:relatedFrameworks` extension for an explicit list of UUIDs.
 *  2. Tier 2 — Association traversal: Extract unique documentIdentifier values
 *     from the crosswalk's own associations (originNodeURI / destinationNodeURI).
 *  3. Tier 3 — API fallback: Call the /related endpoint to discover frameworks
 *     through server-side association graph traversal.
 *
 * The feature is only active when the current framework has zero items (i.e.,
 * it is a "pure crosswalk" with no items of its own).
 *
 * @param {Object} [options]
 * @param {import('vue').Ref<Object>} [options.currentDocument] — Reactive ref to the current document
 * @param {import('vue').Ref<Array>}  [options.currentDocumentTree] — Reactive ref to the current document tree
 * @param {import('vue').Ref<Array>}  [options.currentDocumentAssociations] — Reactive ref to the current document's associations
 * @returns {{ isPureCrosswalk: import('vue').ComputedRef<boolean>, relatedFrameworkIds: import('vue').ComputedRef<Set<string>> }}
 */
export function useCrosswalkRelatedFrameworks(options = {}) {
  const currentDocumentStore = useCurrentDocumentStore();

  const currentDocument = options.currentDocument || computed(() => currentDocumentStore.currentDocument);
  const currentDocumentTree = options.currentDocumentTree || computed(() => currentDocumentStore.currentDocumentTree);
  const currentDocumentAssociations = options.currentDocumentAssociations || computed(() => currentDocumentStore.currentDocumentAssociations);

  /**
   * A "pure crosswalk" is a framework with no items of its own — it exists
   * solely to contain associations between items in other frameworks.
   */
  const isPureCrosswalk = computed(() => {
    const tree = currentDocumentTree.value;
    return Array.isArray(tree) && tree.length <= CROSSWALK_ITEM_THRESHOLD;
  });

  /**
   * Tier 1: Extract related framework IDs from the document's extensions.
   * Returns a Set of UUID strings, or null if the extension key is absent.
   */
  function getRelatedFrameworksFromExtensions() {
    const extensions = currentDocument.value?.extensions;
    if (!extensions || typeof extensions !== 'object') return null;

    const related = extensions[RELATED_FRAMEWORKS_EXTENSION_KEY];
    if (!Array.isArray(related)) return null;

    const ids = related.filter(id => typeof id === 'string' && id.length > 0);
    return ids.length > 0 ? new Set(ids) : null;
  }

  /**
   * Tier 2: Extract related framework IDs from the crosswalk's own associations.
   *
   * Iterates associations where associationDocumentIdentifier matches the current
   * framework, and collects unique documentIdentifier values from both
   * originNodeURI and destinationNodeURI.
   *
   * Excludes the crosswalk framework's own identifier from the result.
   */
  function getRelatedFrameworksFromAssociations() {
    const associations = currentDocumentAssociations.value;
    if (!Array.isArray(associations) || associations.length === 0) return null;

    const currentFrameworkId = currentDocument.value?.identifier;
    if (!currentFrameworkId) return null;

    const relatedIds = new Set();

    for (const assoc of associations) {
      // Only consider associations owned by this crosswalk framework
      const assocDocId = assoc.associationDocumentIdentifier || null;
      if (assocDocId && assocDocId !== currentFrameworkId) continue;

      // Collect origin document identifier
      const originDocId = assoc.originNodeURI?.documentIdentifier;
      if (originDocId && originDocId !== currentFrameworkId) {
        relatedIds.add(originDocId);
      }

      // Collect destination document identifier
      const destDocId = assoc.destinationNodeURI?.documentIdentifier;
      if (destDocId && destDocId !== currentFrameworkId) {
        relatedIds.add(destDocId);
      }
    }

    return relatedIds.size > 0 ? relatedIds : null;
  }

  /**
   * Combined related framework IDs using the tiered fallback.
   *
   * Tier 1 (extensions) is checked first and is synchronous.
   * Tier 2 (association traversal) is checked second and is synchronous.
   * If both return null, the consumer should trigger the Tier 3 API call
   * via fetchRelatedFrameworksFromApi().
   */
  const relatedFrameworkIds = computed(() => {
    if (!isPureCrosswalk.value) return new Set();

    // Tier 1: Extensions
    const fromExtensions = getRelatedFrameworksFromExtensions();
    if (fromExtensions) return fromExtensions;

    // Tier 2: Association traversal
    const fromAssociations = getRelatedFrameworksFromAssociations();
    if (fromAssociations) return fromAssociations;

    // Tier 3: API fallback (handled by consumer via fetchRelatedFrameworksFromApi)
    return new Set();
  });

  /**
   * Async Tier 3 fetch. Call this when the synchronous tiers return empty.
   *
   * @returns {Promise<Set<string>>} — Set of related framework identifiers
   */
  async function fetchRelatedFrameworksFromApi() {
    const frameworkId = currentDocument.value?.identifier;
    if (!frameworkId) return new Set();

    try {
      const relatedDocs = await api.getRelatedDocuments(frameworkId);
      if (!Array.isArray(relatedDocs)) return new Set();

      const ids = new Set();
      for (const doc of relatedDocs) {
        if (doc.identifier && doc.identifier !== frameworkId) {
          ids.add(doc.identifier);
        }
      }
      return ids;
    } catch (error) {
      logger.error('[useCrosswalkRelatedFrameworks] Failed to fetch related frameworks from API:', error);
      return new Set();
    }
  }

  return {
    isPureCrosswalk,
    relatedFrameworkIds,
    fetchRelatedFrameworksFromApi,
  };
}
