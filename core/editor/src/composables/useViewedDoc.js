import { ref, computed, watch } from 'vue';
import { useCurrentDocumentStore } from '../stores/currentDocumentStore';
import { useEditorContextStore } from '../stores/editorContextStore';

export function useViewedDoc(options = {}) {
  const currentDocumentStore = useCurrentDocumentStore();
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
        const registryVersion = contextStore.registryVersion;
        void registryVersion;
        if (!id) return null;
        const pkg = contextStore.loadedPackages.get(id);
        return {
          id,
          hasItems: !!pkg?.CFItems?.length,
          itemCount: pkg?.CFItems?.length || 0,
          assocCount: pkg?.CFAssociations?.length || 0,
        };
      },
      async (snapshot) => {
        if (!snapshot?.id) {
          transformedItems.value = [];
          transformedDocId.value = null;
          return;
        }

        const seq = ++transformSequence;
        const pkg = contextStore.loadedPackages.get(snapshot.id);
        if (!pkg?.CFItems) return;

        const result = await currentDocumentStore.transformCASEItems(
          pkg.CFItems,
          pkg.CFAssociations || [],
          snapshot.id
        );
        if (seq !== transformSequence) return;

        transformedItems.value = Array.isArray(result) ? result : (result.items || []);
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
