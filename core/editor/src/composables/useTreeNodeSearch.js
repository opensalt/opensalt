import { computed, watch } from 'vue';

/**
 * Composable that manages per-node search/filter/highlight logic for a TreeNode.
 *
 * @param {Object} options
 * @param {import('vue').ComputedRef} options.resolvedItem   - Resolved item object (may include cross-framework overrides)
 * @param {import('vue').Ref|import('vue').ComputedRef} options.searchQuery    - Current active search string
 * @param {import('vue').Ref|import('vue').ComputedRef} options.matchingItemIds - Set of identifiers that directly match
 * @param {Object} options.navigation     - Injected treeNavigation context (needs expandItem)
 * @param {import('vue').ComputedRef} options.itemIdentifier - This node's identifier
 */
export function useTreeNodeSearch({ resolvedItem, searchQuery, matchingItemIds, navigation, itemIdentifier }) {
    const hasMatch = computed(() => {
        if (!searchQuery.value) return true;
        return matchingItemIds.value.has(itemIdentifier.value);
    });

    const hasMatchingDescendant = computed(() => {
        if (!searchQuery.value) return false;
        // In the filtered tree, if a node has children, it's because either it matches or a descendant matches.
        // If it doesn't match itself, but has children, then it MUST have matching descendants.
        return resolvedItem.value.children?.length > 0;
    });

    const isVisible = computed(() => true); // If it's in the filtered tree, it's visible.

    const isAncestorOnlyMatch = computed(() => {
        if (!searchQuery.value) return false;
        return !hasMatch.value && hasMatchingDescendant.value;
    });

    function escapeRegExp(string) {
        return string.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
    }

    const highlightedTitle = computed(() => {
        const title =
            resolvedItem.value.abbreviatedStatement ||
            resolvedItem.value.fullStatement ||
            resolvedItem.value.title ||
            resolvedItem.value.identifier ||
            '';
        if (!searchQuery.value || !hasMatch.value) return title;
        const sanitizedQuery = escapeRegExp(searchQuery.value);
        const regex = new RegExp(`(${sanitizedQuery})`, 'gi');
        return title.replace(regex, '<mark class="search-highlight">$1</mark>');
    });

    // Auto-expand when descendants match
    watch(
        () => searchQuery.value,
        (newQuery) => {
            if (newQuery && hasMatchingDescendant.value) {
                navigation.expandItem(itemIdentifier.value);
            }
        }
    );

    return {
        hasMatch,
        hasMatchingDescendant,
        isVisible,
        isAncestorOnlyMatch,
        highlightedTitle,
    };
}
