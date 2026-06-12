import { computed } from 'vue';

/**
 * Composable that groups and sorts documents by creator.
 * Groups are sorted alphabetically by creator name.
 * Documents within each group are sorted alphabetically by title.
 *
 * @param {import('vue').Ref<Array>} documents - Reactive ref of document objects
 * @returns {{ groupedDocuments: import('vue').ComputedRef<Array<{creator: string, documents: Array}>> }}
 */
export function useDocumentGroups(documents) {
  const groupedDocuments = computed(() => {
    const grouped = new Map();

    for (const doc of documents.value) {
      const creator = doc.creator || 'Unknown Creator';
      if (!grouped.has(creator)) {
        grouped.set(creator, []);
      }
      grouped.get(creator).push(doc);
    }

    const sortedCreators = Array.from(grouped.keys()).sort((a, b) => a.localeCompare(b));

    const result = [];
    for (const creator of sortedCreators) {
      const docs = grouped.get(creator).sort((a, b) => (a.title || '').localeCompare(b.title || ''));
      result.push({ creator, documents: docs });
    }

    return result;
  });

  return { groupedDocuments };
}
