import { ref } from 'vue';
import { api } from '../services/api.js';
import { useDocumentStore } from '../stores/documentStore';
import { buildAlignmentReport } from '../services/alignmentReport.js';

const PAGE_SIZE = 1000;

/**
 * Loads two frameworks' trees + all related associations and computes
 * the alignment report. All data comes from existing editor endpoints.
 */
export function useAlignmentReport() {
  const documentStore = useDocumentStore();
  const rows = ref([]);
  const loading = ref(false);
  const error = ref(null);
  // 0-100, driving the progress bar across the top of the report table.
  const progress = ref(0);

  async function fetchAllAssociations(identifier, onProgress = () => {}) {
    const all = new Map();
    let offset = 0;
    // Guard against a backend total that never converges (defensive cap).
    for (let page = 0; page < 500; page += 1) {
      const response = await api.get(
        `/framework/editor/associations/framework/${encodeURIComponent(identifier)}?limit=${PAGE_SIZE}&offset=${offset}`,
      );
      for (const association of response?.data || []) {
        if (!all.has(association.identifier)) {
          all.set(association.identifier, association);
        }
      }
      const total = Number(response?.total ?? 0);
      offset += response?.data?.length || 0;
      onProgress(offset, total);
      if (offset >= total || !(response?.data?.length)) {
        break;
      }
    }
    return [...all.values()];
  }

  async function generate({ leftId, rightId }) {
    loading.value = true;
    error.value = null;
    rows.value = [];
    progress.value = 0;
    try {
      // Progress bands: 0-10 loading trees, 10-90 association pages, 90-100 build.
      progress.value = 5;
      const [left, right] = await Promise.all([
        documentStore.fetchTree(leftId),
        documentStore.fetchTree(rightId),
      ]);
      progress.value = 10;
      // One fetch suffices: the framework associations endpoint spans owning
      // documents in both directions (owned-by-doc ∪ origin∈doc ∪ destination∈doc),
      // so every A↔B mapping — wherever it is stored — is in the left set.
      const associations = await fetchAllAssociations(leftId, (fetched, total) => {
        progress.value = 10 + Math.round(80 * (total > 0 ? fetched / total : 1));
      });
      progress.value = 90;
      rows.value = buildAlignmentReport({
        leftTree: left.tree,
        rightTree: right.tree,
        associations,
      }).rows;
      progress.value = 100;
    } catch (err) {
      rows.value = [];
      error.value = err?.message || String(err);
    } finally {
      loading.value = false;
    }
  }

  return { rows, loading, error, progress, generate, fetchAllAssociations };
}
