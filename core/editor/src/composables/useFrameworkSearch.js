import { computed } from 'vue';

/**
 * Composable that provides framework-level search count and matching-ID set
 * for the main tree editor.
 *
 * @param {Object} options
 * @param {import('vue').Ref} options.treeSearchQuery - The current search string
 * @param {import('vue').ComputedRef} options.doc - The edited document (currentDocument)
 * @param {import('vue').ComputedRef} options.viewedDoc - The viewed document (or null)
 * @param {import('vue').ComputedRef} options.isViewingDifferentFramework
 */
export function useFrameworkSearch({ treeSearchQuery, doc, viewedDoc, isViewingDifferentFramework }) {
    function countMatches(items, query) {
        let count = 0;
        for (const item of items) {
            const searchableText = [
                item.humanCodingScheme,
                item.abbreviatedStatement,
                item.fullStatement,
                item.title,
                item.identifier,
            ]
                .filter(Boolean)
                .join(' ')
                .toLowerCase();
            if (searchableText.includes(query)) count++;
            if (item.children) count += countMatches(item.children, query);
        }
        return count;
    }

    function findMatches(items, query, matches) {
        for (const item of items) {
            const searchableText = [
                item.humanCodingScheme,
                item.abbreviatedStatement,
                item.fullStatement,
                item.title,
                item.identifier,
            ]
                .filter(Boolean)
                .join(' ')
                .toLowerCase();
            if (searchableText.includes(query)) matches.add(item.identifier);
            if (item.children) findMatches(item.children, query, matches);
        }
    }

    const matchCount = computed(() => {
        if (!treeSearchQuery.value) return null;
        const query = treeSearchQuery.value.toLowerCase();
        const itemsToSearch = isViewingDifferentFramework.value
            ? viewedDoc.value?.items || []
            : doc.value.items || [];
        return countMatches(itemsToSearch, query);
    });

    const matchingItemIds = computed(() => {
        if (!treeSearchQuery.value) return new Set();
        const query = treeSearchQuery.value.toLowerCase();
        const matches = new Set();
        const itemsToSearch = isViewingDifferentFramework.value
            ? viewedDoc.value?.items || []
            : doc.value.items || [];
        findMatches(itemsToSearch, query, matches);
        return matches;
    });

    return { matchCount, matchingItemIds };
}
