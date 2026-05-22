import { useAssociationList } from './useAssociationList.js';

/**
 * Composable that manages document association display state for DocumentDetailsPanel.
 *
 * @param {Object} options
 * @param {import('vue').Ref|import('vue').ComputedRef} options.document
 */
export function useDocumentAssociations({ document }) {
  return useAssociationList({
    mode: 'document',
    document,
  });
}
