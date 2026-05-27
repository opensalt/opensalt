import { ref, onMounted } from 'vue';
import { logger } from '../utils/logger.js';

/**
 * Screen Reader Announcer Composable
 * Provides functionality to announce changes to screen readers
 * using ARIA live regions.
 *
 * @returns {Object} Announcer methods and state
 */
export function useAnnouncer() {
  const politeMessage = ref('');
  const assertiveMessage = ref('');
  let announcerElement = null;

  // Get announcer from parent or create one
  const getAnnouncerElement = () => {
    if (announcerElement) return announcerElement;

    const politeRegion = document.getElementById('a11y-announcer-polite');
    const assertiveRegion = document.getElementById('a11y-announcer-assertive');

    if (politeRegion && assertiveRegion) {
      announcerElement = { polite: politeRegion, assertive: assertiveRegion };
    } else {
      announcerElement = createAnnouncerElement();
    }

    return announcerElement;
  };

  /**
   * Create ARIA live region elements for screen reader announcements
   */
  const createAnnouncerElement = () => {
    const container = document.createElement('div');
    container.id = 'a11y-announcer-container';
    container.className = 'visually-hidden';
    // Inline styles as fallback in case Bootstrap class is not loaded
    container.style.position = 'absolute';
    container.style.width = '1px';
    container.style.height = '1px';
    container.style.overflow = 'hidden';
    container.style.clip = 'rect(0, 0, 0, 0)';
    container.style.whiteSpace = 'nowrap';
    // Note: aria-hidden is NOT set here because aria-live regions must be perceivable by screen readers

    // Create polite region (for non-critical announcements)
    const politeRegion = document.createElement('div');
    politeRegion.id = 'a11y-announcer-polite';
    politeRegion.setAttribute('role', 'status');
    politeRegion.setAttribute('aria-live', 'polite');
    politeRegion.setAttribute('aria-atomic', 'true');

    // Create assertive region (for important announcements)
    const assertiveRegion = document.createElement('div');
    assertiveRegion.id = 'a11y-announcer-assertive';
    assertiveRegion.setAttribute('role', 'alert');
    assertiveRegion.setAttribute('aria-live', 'assertive');
    assertiveRegion.setAttribute('aria-atomic', 'true');

    container.appendChild(politeRegion);
    container.appendChild(assertiveRegion);
    document.body.appendChild(container);

    return {
      polite: politeRegion,
      assertive: assertiveRegion
    };
  };

  /**
   * Announce a message to screen readers
   * @param {string} message - The message to announce
   * @param {string} priority - 'polite' or 'assertive'
   */
  const announce = (message, priority = 'polite') => {
    if (!message) return;

    try {
      const announcers = getAnnouncerElement();
      if (!announcers || !announcers.polite || !announcers.assertive) {
        logger.warn('Announcer elements not available');
        return;
      }

      const targetRegion = priority === 'assertive' ? announcers.assertive : announcers.polite;

      // Clear the content first to ensure the announcement is detected
      targetRegion.textContent = '';

      // Small delay to ensure screen reader picks up the change
      setTimeout(() => {
        try {
          targetRegion.textContent = message;
        } catch (error) {
          logger.warn('Failed to set announcement text:', error);
        }
      }, 50);
    } catch (error) {
      logger.warn('Announcement failed:', error);
    }
  };

  /**
   * Announce when an item is selected
   * @param {Object} item - The selected item
   */
  const announceSelection = (item) => {
    const title = item?.title || item?.fullStatement || item?.abbreviatedStatement || item?.identifier || 'Item';
    announce(`Selected ${title}`, 'polite');
  };

  /**
   * Announce when a node is expanded or collapsed
   * @param {Object} item - The item being expanded/collapsed
   * @param {boolean} expanded - Whether the node is expanded
   */
  const announceExpansion = (item, expanded) => {
    const title = item?.title || item?.fullStatement || item?.abbreviatedStatement || item?.identifier || 'Item';
    const action = expanded ? 'Expanded' : 'Collapsed';
    announce(`${action} ${title}`, 'polite');
  };

  /**
   * Announce navigation to a new item
   * @param {Object} item - The navigated-to item
   * @param {number} position - The position in the list (optional)
   * @param {number} total - Total number of items (optional)
   */
  const announceNavigation = (item, position, total) => {
    const title = item?.title || item?.fullStatement || item?.abbreviatedStatement || item?.identifier || 'Item';
    let message = `Navigated to ${title}`;

    if (position !== undefined && total !== undefined) {
      message += `, ${position} of ${total}`;
    }

    announce(message, 'polite');
  };

  /**
   * Announce a drag operation
   * @param {Object} item - The item being dragged
   * @param {string} action - 'start', 'move', 'drop', 'cancel'
   */
  const announceDrag = (item, action) => {
    const title = item?.title || item?.fullStatement || item?.abbreviatedStatement || item?.identifier || 'Item';
    const messages = {
      start: `Started dragging ${title}. Use arrow keys to move, Space to drop, Escape to cancel.`,
      move: `Moving ${title}`,
      drop: `Dropped ${title}`,
      cancel: `Cancelled dragging ${title}`
    };
    announce(messages[action] || `${action} ${title}`, 'polite');
  };

  /**
   * Announce when an item is moved in the tree
   * @param {Object} item - The moved item
   * @param {string} position - 'before', 'after', or 'inside'
   * @param {Object} target - The target item (optional)
   */
  const announceMove = (item, position, target) => {
    const title = item?.title || item?.fullStatement || item?.abbreviatedStatement || item?.identifier || 'Item';
    const targetTitle = target?.title || target?.fullStatement || target?.abbreviatedStatement || target?.identifier || 'item';
    const positionText = {
      before: 'before',
      after: 'after',
      inside: 'inside'
    }[position] || 'to';

    announce(`Moved ${title} ${positionText} ${targetTitle}`, 'polite');
  };

  /**
   * Announce an error message
   * @param {string} message - The error message
   */
  const announceError = (message) => {
    announce(message, 'assertive');
  };

  /**
   * Announce a success message
   * @param {string} message - The success message
   */
  const announceSuccess = (message) => {
    announce(message, 'polite');
  };

  /**
   * Announce loading state
   * @param {boolean} loading - Whether loading is in progress
   * @param {string} context - What is being loaded (optional)
   */
  const announceLoading = (loading, context = 'content') => {
    const message = loading ? `Loading ${context}...` : `${context} loaded.`;
    announce(message, 'polite');
  };

  /**
   * Announce when search results are available
   * @param {number} count - Number of results
   * @param {string} query - The search query
   */
  const announceSearchResults = (count, query) => {
    if (count === 0) {
      announce(`No results found for "${query}"`, 'polite');
    } else {
      announce(`Found ${count} result${count !== 1 ? 's' : ''} for "${query}"`, 'polite');
    }
  };

  /**
   * Clear all announcements
   */
  const clearAnnouncements = () => {
    const announcers = getAnnouncerElement();
    announcers.polite.textContent = '';
    announcers.assertive.textContent = '';
  };

  // Initialize announcer on mount
  onMounted(() => {
    getAnnouncerElement();
  });

  return {
    // State
    politeMessage,
    assertiveMessage,

    // Methods
    announce,
    announceSelection,
    announceExpansion,
    announceNavigation,
    announceDrag,
    announceMove,
    announceError,
    announceSuccess,
    announceLoading,
    announceSearchResults,
    clearAnnouncements
  };
}

export default useAnnouncer;
