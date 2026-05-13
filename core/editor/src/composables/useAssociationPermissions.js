import { computed, toRef, unref } from 'vue';
import { useCurrentDocumentStore } from '../stores/currentDocumentStore';
import { useEditorContextStore } from '../stores/editorContextStore';

export function useAssociationPermissions(association, options = {}) {
  const currentDocumentStore = useCurrentDocumentStore();
  const contextStore = useEditorContextStore();

  const isReadOnlyRef = toRef(() => unref(options.isReadOnly) ?? false);

  const displayedFrameworkId = computed(() => (
    contextStore.isViewingDifferentFramework
      ? (
        contextStore.viewedDocumentId ||
        currentDocumentStore.currentDocument?.identifier ||
        currentDocumentStore.currentDocument?.id ||
        null
      )
      : (
        contextStore.activeWriteDocumentId ||
        currentDocumentStore.currentDocument?.identifier ||
        currentDocumentStore.currentDocument?.id ||
        null
      )
  ));

  const activeFrameworkId = computed(() => (
    contextStore.activeWriteDocumentId ||
    currentDocumentStore.currentDocument?.identifier ||
    currentDocumentStore.currentDocument?.id ||
    null
  ));

  const associationSourceFrameworkId = computed(() => {
    const assoc = association.value || association;
    return (
      assoc?._sourceFrameworkId
      || assoc?.CFDocumentURI?.identifier
      || (typeof assoc?.CFDocumentURI === 'string' ? assoc.CFDocumentURI : null)
      || null
    );
  });

  const resolvedAssociationSourceDocumentId = computed(() => {
    const sourceId = associationSourceFrameworkId.value;
    if (!sourceId) return null;
    if (contextStore.documentRegistry.has(sourceId)) {
      return sourceId;
    }
    for (const doc of contextStore.documentRegistry.values()) {
      if (doc.frameworkId === sourceId) {
        return doc.identifier;
        }
    }
    return sourceId;
  });

  const isAssociationFromDifferentDisplayedFramework = computed(() => {
    if (!resolvedAssociationSourceDocumentId.value) return false;
    return displayedFrameworkId.value != null && resolvedAssociationSourceDocumentId.value !== displayedFrameworkId.value;
  });

  const associationTypeForPermissions = computed(() => {
    const assoc = association.value || association;
    return assoc?.associationType || assoc?.type || 'unknown';
  });

  const isAssociationSourceEditable = computed(() => {
    if (!resolvedAssociationSourceDocumentId.value || !activeFrameworkId.value) return true;
    return contextStore.isEditable(resolvedAssociationSourceDocumentId.value);
  });

  const canManageAssociation = computed(() => {
    if (isReadOnlyRef.value || associationTypeForPermissions.value === 'isChildOf') return false;
    return isAssociationSourceEditable.value;
  });

  const canDeleteAssociation = computed(() => {
    if (isReadOnlyRef.value) return false;
    return isAssociationSourceEditable.value;
  });

  const sourceFrameworkTitle = computed(() => {
    if (!isAssociationFromDifferentDisplayedFramework.value) return null;
    const frameworkId = resolvedAssociationSourceDocumentId.value;
    if (!frameworkId) return null;
    const doc = contextStore.documentRegistry.get(frameworkId);
    return doc?.title || null;
  });

  return {
    displayedFrameworkId,
    activeFrameworkId,
    associationSourceFrameworkId,
    resolvedAssociationSourceDocumentId,
    isAssociationFromDifferentDisplayedFramework,
    associationTypeForPermissions,
    isAssociationSourceEditable,
    canManageAssociation,
    canDeleteAssociation,
    sourceFrameworkTitle,
    isReadOnly: isReadOnlyRef
  };
}
