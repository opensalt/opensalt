import { ref, computed } from 'vue';

export function useSideTreePanel(props) {
  const selectedDocumentId = ref('');

  const currentDocForSelector = computed(() => {
    if (selectedDocumentId.value && props.availableDocuments?.length) {
      const found = props.availableDocuments.find(doc => doc.id === selectedDocumentId.value || doc.identifier === selectedDocumentId.value);
      if (found) return found;
    }
    return props.currentDocument;
  });

  function onDocumentSelected(docId) {
    selectedDocumentId.value = docId;
    return docId;
  }

  function resetSelection() {
    selectedDocumentId.value = '';
  }

  return {
    selectedDocumentId,
    currentDocForSelector,
    onDocumentSelected,
    resetSelection
  };
}
