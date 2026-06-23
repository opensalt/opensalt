import { ref, onUnmounted, getCurrentInstance } from 'vue';
import { logger } from '@/utils/logger.js';

/**
 * Composable for managing a markdown statement popover.
 * @param {() => string} getStatement - Callback returning the statement text to render.
 * @param {{ delay?: number }} options - Optional settings; `delay` in ms (default 500).
 */
let markdownRendererPromise = null;
let cachedRenderMarkdown = null;

async function getMarkdownRenderer() {
  if (cachedRenderMarkdown) return cachedRenderMarkdown;
  if (!markdownRendererPromise) {
    markdownRendererPromise = import('@/utils/markdownRenderer').then((m) => {
      cachedRenderMarkdown = m.renderMarkdown;
      return cachedRenderMarkdown;
    });
  }
  return markdownRendererPromise;
}

export function useStatementPopover(getStatement, options = {}) {
  const delay = options.delay ?? 500;
  const showPopover = ref(false);
  const statementHtml = ref('');
  let timeoutId = null;

  function onTriggerEnter() {
    if (timeoutId) clearTimeout(timeoutId);
    timeoutId = setTimeout(async () => {
      try {
        const text = getStatement();
        if (!text) {
          showPopover.value = false;
          statementHtml.value = '';
          return;
        }
        const renderMarkdownText = await getMarkdownRenderer();
        statementHtml.value = renderMarkdownText(text).trimEnd();
        showPopover.value = true;
      } catch (err) {
        logger.error('Error rendering statement popover:', err);
      }
    }, delay);
  }

  function onTriggerLeave() {
    if (timeoutId) {
      clearTimeout(timeoutId);
      timeoutId = null;
    }
    showPopover.value = false;
    statementHtml.value = '';
  }

  function cleanup() {
    if (timeoutId) {
      clearTimeout(timeoutId);
      timeoutId = null;
    }
    showPopover.value = false;
  }

  if (getCurrentInstance()) {
    onUnmounted(cleanup);
  }

  return { showPopover, statementHtml, onTriggerEnter, onTriggerLeave, cleanup };
}
