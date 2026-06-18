import { ref } from 'vue';
import { getEditableExtensions, getReservedKeys } from '../config/extensionsConfig.js';

export function useExtensionsEditor({ scope, kind = '', getRawExtensions } = {}) {
  const show = ref(false);
  const editable = ref({});
  const reserved = ref({});
  const reservedKeys = getReservedKeys(scope, kind);

  function open() {
    const raw = getRawExtensions ? getRawExtensions() : undefined;
    const { editable: ed, reserved: res } = getEditableExtensions(raw, scope, kind);
    editable.value = ed;
    reserved.value = res;
    show.value = true;
  }

  function buildExtensions() {
    return { ...reserved.value, ...editable.value };
  }

  return { show, editable, reserved, reservedKeys, open, buildExtensions };
}