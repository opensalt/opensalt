import { describe, it, expect, vi } from 'vitest';
import { useDynamicModal } from '@/composables/useDynamicModal.js';

describe('useDynamicModal', () => {
  it('closes the modal after an async onCreate handler resolves', async () => {
    const onCreate = vi.fn().mockResolvedValue(undefined);
    const { handleCreated, isModalVisible } = useDynamicModal(null, onCreate, []);

    isModalVisible.value = true;
    await handleCreated({ fullStatement: 'Test' });

    expect(onCreate).toHaveBeenCalledWith({ fullStatement: 'Test' });
    expect(isModalVisible.value).toBe(false);
  });

  it('keeps the modal open when an async onCreate handler rejects', async () => {
    const onCreate = vi.fn().mockRejectedValue(new Error('save failed'));
    const { handleCreated, isModalVisible } = useDynamicModal(null, onCreate, []);

    isModalVisible.value = true;
    await handleCreated({ fullStatement: 'Test' });

    expect(isModalVisible.value).toBe(true);
  });

  it('closes the modal after a synchronous onCreate handler', async () => {
    const onCreate = vi.fn();
    const { handleCreated, isModalVisible } = useDynamicModal(null, onCreate, []);

    isModalVisible.value = true;
    await handleCreated({ fullStatement: 'Test' });

    expect(isModalVisible.value).toBe(false);
  });
});
