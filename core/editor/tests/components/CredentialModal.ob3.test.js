import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import { defineComponent, nextTick } from 'vue';
import CredentialModal from '@/components/tree/modals/item-types/CredentialModal.vue';

vi.mock('@opensalt/ob3-definer/dist/ob3-definer.js', () => ({}));
vi.mock('@opensalt/ob3-definer/dist/ob3-definer.css', () => ({}));

const ExtensionsEditorStub = defineComponent({
  name: 'ExtensionsEditor',
  emits: ['update:modelValue', 'update:show'],
  template: '<div />'
});

function mountModal(props = {}) {
  return mount(CredentialModal, {
    props: { show: true, itemType: 'credential', ...props },
    global: { stubs: { ExtensionsEditor: ExtensionsEditorStub } }
  });
}

describe('CredentialModal ob3 encoding', () => {
  beforeEach(() => {
    vi.useFakeTimers();
  });

  afterEach(() => {
    vi.useRealTimers();
  });

  it('normalizes a stringified widget payload to single-encoded JSON on update', async () => {
    const item = {
      identifier: 'c1',
      fullStatement: 'old statement',
      abbreviatedStatement: 'old name',
      extensions: { ob3: '{}', 'salt:type': 'credential' }
    };
    const wrapper = mountModal({ item });
    await nextTick();

    const achievement = {
      name: 'My Badge',
      description: 'A great badge',
      criteria: { narrative: 'n' }
    };

    // Kick off the save flow (enters the submitWidgetForm wait).
    await wrapper.find('[data-testid="save-item"]').trigger('click');

    // The OB3 widget emits an already-stringified JSON string.
    window.dispatchEvent(new CustomEvent('saveDefinition', { detail: JSON.stringify(achievement) }));

    // Let the save promise and its timeout settle.
    await vi.advanceTimersByTimeAsync(600);

    const events = wrapper.emitted('updated');
    expect(events).toBeTruthy();
    const updated = events[0][0];

    // credential must be single-encoded JSON (not double-encoded).
    expect(JSON.parse(updated.credential)).toEqual(achievement);
    expect(updated.credential).toBe(JSON.stringify(achievement));

    // description is copied to fullStatement.
    expect(updated.fullStatement).toBe('A great badge');

    // extensions.ob3 must also be single-encoded.
    expect(JSON.parse(updated.extensions.ob3)).toEqual(achievement);
  });

  it('passes an object payload through unchanged (no extra encoding)', async () => {
    const item = {
      identifier: 'c2',
      fullStatement: 'old statement',
      abbreviatedStatement: 'old name',
      extensions: { ob3: '{}', 'salt:type': 'credential' }
    };
    const wrapper = mountModal({ item });
    await nextTick();

    const achievement = { name: 'Obj Badge', description: 'Object payload' };

    await wrapper.find('[data-testid="save-item"]').trigger('click');
    window.dispatchEvent(new CustomEvent('saveDefinition', { detail: achievement }));
    await vi.advanceTimersByTimeAsync(600);

    const updated = wrapper.emitted('updated')[0][0];
    expect(JSON.parse(updated.credential)).toEqual(achievement);
    expect(updated.fullStatement).toBe('Object payload');
  });
});
