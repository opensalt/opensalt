import { ref, computed, toValue } from 'vue';
import { useCrossFrameworkItem } from './useCrossFrameworkItem';

/**
 * Composable for managing association direction switching
 *
 * This composable handles:
 * 1. Direction state (normal vs reversed)
 * 2. Left/right side item data based on direction
 * 3. Display text computation for both sides
 * 4. Direction switching logic
 *
 * @param {Object} options - Configuration options
 * @param {import('vue').Ref<Object>|Object} options.association - The association object
 * @param {import('vue').Ref<Object>|Object} options.currentItem - The current item (for add mode)
 * @param {import('vue').Ref<string>|string} options.mode - 'add' or 'edit'
 * @param {import('vue').Ref<string>|string} options.initialType - Initial association type
 * @param {import('vue').Ref<string>|string} options.selectedItemIdentifier - Currently selected item identifier
 * @param {import('vue').Ref<string>|string} options.formData - Form data containing type
 * @returns {Object} - Composable return values
 */
export function useAssociationDirection(options) {
  const {
    association,
    currentItem,
    destinationItem,
    mode,
    initialType,
    selectedItemIdentifier,
    formData
  } = options;

  // Direction state
  const isReversed = ref(false);

  // Mode detection
  const isAddMode = computed(() => toValue(mode) === 'add');
  const isEditMode = computed(() => toValue(mode) === 'edit');

  // Create a synthetic association for add mode so the composable can extract origin data
  const effectiveAssociation = computed(() => {
    if (isAddMode.value && toValue(currentItem)) {
      const item = toValue(currentItem);
      const destItem = toValue(destinationItem);

      // In add mode, create a synthetic association with the currentItem as origin
      const syntheticAssoc = {
        originNodeURI: {
          identifier: item.identifier,
          uri: item.uri || item.identifier,
          title: item.title || item.fullStatement || item.abbreviatedStatement
        },
        associationType: toValue(initialType) || toValue(formData)?.type
      };

      if (destItem) {
        syntheticAssoc.destinationNodeURI = {
          identifier: destItem.identifier,
          uri: destItem.uri || destItem.identifier,
          title: destItem.title || destItem.fullStatement || destItem.abbreviatedStatement,
          targetType: 'CASE' // Ensure it recognizes as CASE item for item endpoints
        };
      } else if (toValue(mode) === 'add') {
        // If there's no destination item, but we are providing one manually via forms
        if (toValue(formData)?.type === 'exemplar') {
           syntheticAssoc.destinationNodeURI = { uri: toValue(formData)?.exemplarUrl, targetType: undefined };
        } else if (toValue(formData)?.destinationUri) {
           syntheticAssoc.destinationNodeURI = {
             uri: toValue(formData)?.destinationUri,
             title: toValue(formData)?.destinationTitle,
             identifier: toValue(formData)?.destinationIdentifier,
             targetType: toValue(formData)?.destinationTargetType
           }
        }
      }

      // Add CASE targetType to origin node too always
      syntheticAssoc.originNodeURI.targetType = 'CASE';

      return syntheticAssoc;
    }
    return toValue(association);
  });

  // Use cross-framework item composables for origin and destination
  // Origin item (direction = 'reversed' shows origin as the target)
  const {
    itemData: originItemData,
    itemTitle: originItemTitle,
    frameworkTitle: originFrameworkTitle,
    isLoading: originIsLoading,
    targetTypeInfo: originTargetTypeInfo,
    fetchError: originFetchError
  } = useCrossFrameworkItem({
    association: effectiveAssociation,
    direction: 'reversed' // Get origin item
  });

  // Destination item (direction = 'normal' shows destination as the target)
  const {
    itemData: destinationItemData,
    itemTitle: destinationItemTitle,
    frameworkTitle: destinationFrameworkTitle,
    isLoading: destinationIsLoading,
    targetTypeInfo: destinationTargetTypeInfo,
    fetchError: destinationFetchError
  } = useCrossFrameworkItem({
    association: effectiveAssociation,
    direction: 'normal' // Get destination item
  });

  // Get the origin node identifier
  const originIdentifier = computed(() => {
    return effectiveAssociation.value?.originNodeURI?.identifier ||
           effectiveAssociation.value?.origin?.identifier;
  });

  // Get the destination node identifier
  const destinationIdentifier = computed(() => {
    return effectiveAssociation.value?.destinationNodeURI?.identifier ||
           effectiveAssociation.value?.destination?.identifier;
  });

  // Display text for origin item - prefer abbreviatedStatement
  const originItemDisplayText = computed(() => {
    const item = originItemData.value;
    if (!item) return originItemTitle.value || 'Unknown item';

    // Prefer abbreviatedStatement, fall back to shortened fullStatement
    if (item.abbreviatedStatement) {
      return item.abbreviatedStatement;
    }

    if (item.fullStatement) {
      // Truncate to ~100 characters if no abbreviatedStatement
      return item.fullStatement.length > 100
        ? item.fullStatement.substring(0, 100) + '...'
        : item.fullStatement;
    }

    return item.title || item.identifier || 'Unknown item';
  });

  // Display text for destination item - prefer abbreviatedStatement
  const destinationItemDisplayText = computed(() => {
    const item = destinationItemData.value;
    if (!item) return destinationItemTitle.value || 'Unknown item';

    if (item.abbreviatedStatement) {
      return item.abbreviatedStatement;
    }

    if (item.fullStatement) {
      return item.fullStatement.length > 100
        ? item.fullStatement.substring(0, 100) + '...'
        : item.fullStatement;
    }

    return item.title || item.identifier || 'Unknown item';
  });

  // Fallback text when item not found in document
  const originTitle = computed(() => {
    return effectiveAssociation.value?.originNodeURI?.title ||
           effectiveAssociation.value?.origin?.title;
  });

  const destinationTitle = computed(() => {
    // For exemplar associations, show the URL as the destination
    if (effectiveAssociation.value?.associationType === 'exemplar') {
      const url = effectiveAssociation.value?.destinationNodeURI?.uri ||
                  effectiveAssociation.value?.destination?.uri;
      if (url) {
        return url;
      }
    }
    
    // In add mode, if we are typing manual fields, show them as the fallback immediately
    const formObj = toValue(formData);
    if (isAddMode.value && !toValue(destinationItem) && formObj) {
      if (formObj.destinationTitle) return formObj.destinationTitle;
      if (formObj.destinationUri) return formObj.destinationUri;
      if (formObj.destinationIdentifier) return formObj.destinationIdentifier;
    }

    return effectiveAssociation.value?.destinationNodeURI?.title ||
           effectiveAssociation.value?.destination?.title;
  });

  // Computed properties for left/right side items based on direction
  const leftSideItemData = computed(() => {
    return isReversed.value ? destinationItemData.value : originItemData.value;
  });

  const rightSideItemData = computed(() => {
    return isReversed.value ? originItemData.value : destinationItemData.value;
  });

  // Loading states for left/right sides
  const leftSideIsLoading = computed(() => {
    return isReversed.value ? destinationIsLoading.value : originIsLoading.value;
  });

  const rightSideIsLoading = computed(() => {
    return isReversed.value ? originIsLoading.value : destinationIsLoading.value;
  });

  // Framework titles for left/right sides
  const leftSideFrameworkTitle = computed(() => {
    return isReversed.value ? destinationFrameworkTitle.value : originFrameworkTitle.value;
  });

  const rightSideFrameworkTitle = computed(() => {
    return isReversed.value ? originFrameworkTitle.value : destinationFrameworkTitle.value;
  });

  // Target type info for left/right sides
  const leftSideTargetTypeInfo = computed(() => {
    return isReversed.value ? destinationTargetTypeInfo.value : originTargetTypeInfo.value;
  });

  const rightSideTargetTypeInfo = computed(() => {
    return isReversed.value ? originTargetTypeInfo.value : destinationTargetTypeInfo.value;
  });

  // Fetch errors for left/right sides
  const leftSideFetchError = computed(() => {
    return isReversed.value ? destinationFetchError.value : originFetchError.value;
  });

  const rightSideFetchError = computed(() => {
    return isReversed.value ? originFetchError.value : destinationFetchError.value;
  });

  // Display text for left side item
  const leftSideDisplayText = computed(() => {
    const item = leftSideItemData.value;
    if (!item) return leftSideFallbackText.value;

    if (item.abbreviatedStatement) {
      return item.abbreviatedStatement;
    }

    if (item.fullStatement) {
      return item.fullStatement.length > 100
        ? item.fullStatement.substring(0, 100) + '...'
        : item.fullStatement;
    }

    return item.title || item.identifier || 'Unknown item';
  });

  // Display text for right side item
  const rightSideDisplayText = computed(() => {
    const item = rightSideItemData.value;
    if (!item) return rightSideFallbackText.value;

    if (item.abbreviatedStatement) {
      return item.abbreviatedStatement;
    }

    if (item.fullStatement) {
      return item.fullStatement.length > 100
        ? item.fullStatement.substring(0, 100) + '...'
        : item.fullStatement;
    }

    return item.title || item.identifier || 'Unknown item';
  });

  // Short text for direction button (truncated for display)
  const leftSideShortText = computed(() => {
    const item = leftSideItemData.value;
    if (!item) return leftSideFallbackText.value;

    // Prefer humanCodingScheme for short display, then abbreviatedStatement
    if (item.humanCodingScheme) {
      return item.humanCodingScheme;
    }

    if (item.abbreviatedStatement) {
      return item.abbreviatedStatement.length > 30
        ? item.abbreviatedStatement.substring(0, 30) + '...'
        : item.abbreviatedStatement;
    }

    if (item.fullStatement) {
      return item.fullStatement.length > 30
        ? item.fullStatement.substring(0, 30) + '...'
        : item.fullStatement;
    }

    return item.title || item.identifier || 'Unknown';
  });

  const rightSideShortText = computed(() => {
    const item = rightSideItemData.value;
    if (!item) return rightSideFallbackText.value;

    if (item.humanCodingScheme) {
      return item.humanCodingScheme;
    }

    if (item.abbreviatedStatement) {
      return item.abbreviatedStatement.length > 30
        ? item.abbreviatedStatement.substring(0, 30) + '...'
        : item.abbreviatedStatement;
    }

    if (item.fullStatement) {
      return item.fullStatement.length > 30
        ? item.fullStatement.substring(0, 30) + '...'
        : item.fullStatement;
    }

    return item.title || item.identifier || 'Unknown';
  });

  // Fallback text for left/right sides
  const leftSideFallbackText = computed(() => {
    if (isReversed.value) {
      const formDataType = toValue(formData)?.type;
      if (isAddMode.value && !toValue(destinationItem)) {
        if (formDataType === 'exemplar') {
          return 'Enter exemplar URL below';
        }
        return 'Enter origin URI below';
      }
      return destinationTitle.value || 'Origin item';
    }
    return originTitle.value || 'Origin item';
  });

  const rightSideFallbackText = computed(() => {
    if (isReversed.value) {
      return originTitle.value || 'Destination item';
    }
    // For add mode, show a placeholder if no destination items are selected
    const formDataType = toValue(formData)?.type;
    if (isAddMode.value && !toValue(destinationItem)) {
      if (formDataType === 'exemplar') {
        return 'Enter exemplar URL below';
      }
      return 'Enter destination URI below';
    }
    return destinationTitle.value || 'Destination item';
  });

  // Check if origin or destination is the selected item (for reference)
  const isOriginSelected = computed(() => {
    const identifier = toValue(selectedItemIdentifier);
    if (!identifier || !originIdentifier.value) return false;
    return identifier === originIdentifier.value;
  });

  const isDestinationSelected = computed(() => {
    const identifier = toValue(selectedItemIdentifier);
    if (!identifier || !destinationIdentifier.value) return false;
    return identifier === destinationIdentifier.value;
  });

  // Check if the item currently displayed on each side is selected
  // These follow the item, not the position
  const isLeftSideSelected = computed(() => {
    const identifier = toValue(selectedItemIdentifier);
    if (!identifier) return false;
    const leftIdentifier = leftSideItemData.value?.identifier;
    return leftIdentifier && identifier === leftIdentifier;
  });

  const isRightSideSelected = computed(() => {
    const identifier = toValue(selectedItemIdentifier);
    if (!identifier) return false;
    const rightIdentifier = rightSideItemData.value?.identifier;
    return rightIdentifier && identifier === rightIdentifier;
  });

  // Direction icon (constant)
  const directionIcon = 'bi-arrow-right';

  /**
   * Switch the direction of the association
   */
  function switchDirection() {
    isReversed.value = !isReversed.value;
    // Association type is preserved when switching direction
  }

  /**
   * Reset direction to default (non-reversed)
   */
  function resetDirection() {
    isReversed.value = false;
  }

  return {
    // State
    isReversed,

    // Mode
    isAddMode,
    isEditMode,

    // Effective association
    effectiveAssociation,

    // Origin/destination data (from composable)
    originItemData,
    originItemTitle,
    originFrameworkTitle,
    originIsLoading,
    originTargetTypeInfo,
    originFetchError,
    originIdentifier,
    originItemDisplayText,
    originTitle,

    destinationItemData,
    destinationItemTitle,
    destinationFrameworkTitle,
    destinationIsLoading,
    destinationTargetTypeInfo,
    destinationFetchError,
    destinationIdentifier,
    destinationItemDisplayText,
    destinationTitle,

    // Left/right side computed (based on direction)
    leftSideItemData,
    rightSideItemData,
    leftSideIsLoading,
    rightSideIsLoading,
    leftSideFrameworkTitle,
    rightSideFrameworkTitle,
    leftSideTargetTypeInfo,
    rightSideTargetTypeInfo,
    leftSideFetchError,
    rightSideFetchError,
    leftSideDisplayText,
    rightSideDisplayText,
    leftSideShortText,
    rightSideShortText,
    leftSideFallbackText,
    rightSideFallbackText,

    // Selection state
    isOriginSelected,
    isDestinationSelected,
    isLeftSideSelected,
    isRightSideSelected,

    // Direction
    directionIcon,

    // Methods
    switchDirection,
    resetDirection
  };
}

export default useAssociationDirection;
