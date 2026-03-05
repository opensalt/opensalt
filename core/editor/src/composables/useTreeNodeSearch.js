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
    const searchableText = computed(() => {
        const parts = [
            resolvedItem.value.humanCodingScheme,
            resolvedItem.value.abbreviatedStatement,
            resolvedItem.value.fullStatement,
            resolvedItem.value.title,
            resolvedItem.value.identifier,
        ].filter(Boolean);
        return parts.join(' ').toLowerCase();
    });

    const hasMatch = computed(() => {
        if (!searchQuery.value) return true;
        return searchableText.value.includes(searchQuery.value.toLowerCase());
    });

    function checkDescendantsForMatch(children, query) {
        for (const child of children) {
            const childText = [
                child.humanCodingScheme,
                child.abbreviatedStatement,
                child.fullStatement,
                child.title,
                child.identifier,
            ]
                .filter(Boolean)
                .join(' ')
                .toLowerCase();
            if (childText.includes(query)) return true;
            if (child.children && checkDescendantsForMatch(child.children, query)) return true;
        }
        return false;
    }

    const hasMatchingDescendant = computed(() => {
        if (!searchQuery.value) return false;
        return checkDescendantsForMatch(resolvedItem.value.children || [], searchQuery.value.toLowerCase());
    });

    const isVisible = computed(() => {
        if (!searchQuery.value) return true;
        if (matchingItemIds.value.has(itemIdentifier.value)) return true;
        if (hasMatchingDescendant.value) return true;
        return false;
    });

    const isAncestorOnlyMatch = computed(() => {
        if (!searchQuery.value) return false;
        if (hasMatch.value) return false;
        return isVisible.value && hasMatchingDescendant.value;
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
        searchableText,
        hasMatch,
        hasMatchingDescendant,
        isVisible,
        isAncestorOnlyMatch,
        highlightedTitle,
    };
}
