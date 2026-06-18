import { ref } from 'vue';
import { getEditableExtensions, getReservedKeys } from '../config/extensionsConfig.js';

/**
 * Per-modal state for the Extensions Editor overlay.
 *
 * The host modal MUST call seed() when it opens (typically inside its
 * loadFormData()/loadDocumentData() handler) so that saving the entity WITHOUT
 * opening the overlay still round-trips the entity's existing extensions.
 * open() re-seeds and then shows the overlay.
 *
 * @param {Object} options
 * @param {'item'|'document'|'association'} options.scope
 * @param {string} [options.kind] item kind (ignored for document/association)
 * @param {() => Record<string, unknown>|undefined} [options.getRawExtensions]
 * @returns {{
 *   show: import('vue').Ref<boolean>,
 *   editable: import('vue').Ref<Record<string, unknown>>,
 *   reserved: import('vue').Ref<Record<string, unknown>>,
 *   reservedKeys: string[],
 *   open: () => void,
 *   seed: () => void,
 *   buildExtensions: () => Record<string, unknown>
 * }}
 */
export function useExtensionsEditor({ scope, kind = '', getRawExtensions } = {}) {
  const show = ref(false);
  const editable = ref({});
  const reserved = ref({});
  const reservedKeys = getReservedKeys(scope, kind);

  /**
   * (Re)initialize the editable/reserved working copies from the entity's
   * current extensions. Call this whenever the host modal opens for an entity
   * so existing extensions are preserved on save even if the overlay is never
   * opened.
   */
  function seed() {
    const raw = getRawExtensions ? getRawExtensions() : undefined;
    const { editable: ed, reserved: res } = getEditableExtensions(raw, scope, kind);
    editable.value = ed;
    reserved.value = res;
  }

  function open() {
    seed();
    show.value = true;
  }

  function buildExtensions() {
    // Always re-derive reserved keys from the current raw extensions so they
    // can never be lost (defensive: works even if seed() was not called), then
    // layer the user's editable working copy on top.
    const raw = getRawExtensions ? getRawExtensions() : undefined;
    const { reserved: currentReserved } = getEditableExtensions(raw, scope, kind);

    return { ...currentReserved, ...editable.value };
  }

  return { show, editable, reserved, reservedKeys, open, seed, buildExtensions };
}
