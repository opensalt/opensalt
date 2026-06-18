import { describe, it, expect } from 'vitest';
import { useExtensionsEditor } from '../../src/composables/useExtensionsEditor';

describe('useExtensionsEditor', () => {
  it('opens and seeds editable/reserved from raw extensions', () => {
    const raw = { 'salt:type': 'organization', 'ceterms:agentType': 'orgType:Business', 'acme:x': 1 };
    const ext = useExtensionsEditor({ scope: 'item', kind: 'organization', getRawExtensions: () => raw });

    expect(ext.show.value).toBe(false);
    ext.open();
    expect(ext.show.value).toBe(true);
    expect(ext.reservedKeys).toContain('salt:type');
    expect(ext.reservedKeys).toContain('ceterms:agentType');
    expect(ext.editable.value).toEqual({ 'acme:x': 1 });
    expect(ext.reserved.value).toEqual({ 'salt:type': 'organization', 'ceterms:agentType': 'orgType:Business' });
  });

  it('buildExtensions merges reserved and editable, editable wins', () => {
    const ext = useExtensionsEditor({ scope: 'item', kind: 'general', getRawExtensions: () => ({ 'salt:type': 'general', 'a': 1 }) });
    ext.open();
    ext.editable.value = { a: 2, b: 3 };
    expect(ext.buildExtensions()).toEqual({ 'salt:type': 'general', a: 2, b: 3 });
  });

  it('buildExtensions is empty when there are no raw extensions (create flow)', () => {
    const ext = useExtensionsEditor({ scope: 'document', getRawExtensions: () => undefined });
    ext.open();
    expect(ext.editable.value).toEqual({});
    expect(ext.buildExtensions()).toEqual({});
    // editable added via overlay still flows through
    ext.editable.value = { newKey: 'val' };
    expect(ext.buildExtensions()).toEqual({ newKey: 'val' });
  });

  it('treats missing getRawExtensions as no extensions', () => {
    const ext = useExtensionsEditor({ scope: 'association' });
    ext.open();
    expect(ext.reservedKeys).toEqual(expect.arrayContaining(['crosswalk:status']));
    expect(ext.buildExtensions()).toEqual({});
  });
});