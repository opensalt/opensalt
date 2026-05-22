import { ref, computed, watch } from 'vue';
import { useDocumentStore } from '../stores/documentStore';
import { useEditorContextStore } from '../stores/editorContextStore';

export function useViewedDoc(options = {}) {
  const documentStore = useDocumentStore();
  const contextStore = useEditorContextStore();

  const isViewingDifferentFramework = computed(() => contextStore.isViewingDifferentFramework);

  const viewedDocumentId = computed(() => contextStore.viewedDocumentId);

  const transformedItems = ref([]);
  const transformedDocId = ref(null);
  const transformVersion = ref(0);

  if (options.transformItems) {
    let transformSequence = 0;

    watch(
      () => {
        const id = contextStore.viewedDocumentId;
        if (!id) return null;
        return { id };
      },
      async (snapshot) => {
        if (!snapshot?.id) {
          transformedItems.value = [];
          transformedDocId.value = null;
          return;
        }

        const seq = ++transformSequence;

        const treeResponse = await documentStore.fetchTree(snapshot.id);
        if (seq !== transformSequence) return;

        transformedItems.value = treeResponse.tree || [];
        transformedDocId.value = snapshot.id;
        transformVersion.value++;
      },
      { immediate: true }
    );
  }

  const viewedDoc = computed(() => {
    void transformVersion.value;
    const id = contextStore.viewedDocumentId;
    if (!id) return null;
    const docMeta = contextStore.documentRegistry.get(id);
    if (!docMeta) return null;

    if (options.transformItems) {
      return { ...docMeta, id: docMeta.identifier, items: transformedItems.value };
    }

    return docMeta;
  });

  return {
    viewedDoc,
    isViewingDifferentFramework,
    viewedDocumentId
  };
}
