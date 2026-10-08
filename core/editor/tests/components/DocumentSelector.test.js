import { describe, it, expect, beforeEach, vi } from 'vitest';
import { mount } from '@vue/test-utils';
import DocumentSelector from '@/components/shared/common/DocumentSelector.vue';

const documents = [
  { identifier: 'doc-1', title: 'Edited Framework', creator: 'State' },
  { identifier: 'doc-2', title: 'Other Framework', creator: 'State' },
];

function doc(i, creator = 'State') {
  return { identifier: `doc-${i}`, title: `Framework ${i}`, creator };
}

const longList = Array.from({ length: 12 }, (_, i) => doc(i + 1));

function mountSelector(props = {}) {
  return mount(DocumentSelector, {
    props: {
      currentDoc: documents[0],
      availableDocuments: documents,
      ...props,
    },
  });
}

function openDropdown(wrapper) {
  return wrapper.find('[role="combobox"]').trigger('click');
}

describe('DocumentSelector.vue tree-view mode', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('hides the external document option when requested', async () => {
    const wrapper = mountSelector({ side: 'treeView', hideExternal: true, compact: true });
    await openDropdown(wrapper);

    expect(wrapper.text()).not.toContain('Load external document');
  });

  it('keeps the external document option for side-panel use', async () => {
    const wrapper = mountSelector({ side: 'right', hideExternal: false, compact: false });
    await openDropdown(wrapper);

    expect(wrapper.text()).toContain('Load external document');
  });

  it('renders compactly without card chrome', () => {
    const wrapper = mountSelector({ side: 'treeView', hideExternal: true, compact: true });

    expect(wrapper.find('.document-selector.card').exists()).toBe(false);
    // The hidden label names the combobox via aria-labelledby
    const label = wrapper.find('span.visually-hidden');
    expect(label.text()).toBe('Document');
    const combobox = wrapper.find('[role="combobox"]');
    expect(combobox.attributes('aria-labelledby')).toBe(label.attributes('id'));
    expect(combobox.attributes('id')).toMatch(/^documentSelector-treeView-/);
    expect(combobox.text()).toContain('Main framework - Edited Framework (doc-1)');
  });

  it('names the non-compact combobox from the card-header label', () => {
    const wrapper = mountSelector({ side: 'right', hideExternal: false, compact: false, label: 'External Document' });

    const header = wrapper.find('.card-header h6');
    expect(header.text()).toBe('External Document');
    expect(wrapper.find('[role="combobox"]').attributes('aria-labelledby')).toBe(header.attributes('id'));
  });

  it('emits viewed-document-changed only after user selection', async () => {
    const wrapper = mountSelector({ side: 'treeView', hideExternal: true, compact: true });

    expect(wrapper.emitted('viewed-document-changed')).toBeUndefined();

    await openDropdown(wrapper);
    const option = wrapper.findAll('[role="option"]').find((o) => o.text().includes('Other Framework'));
    await option.trigger('click');

    expect(wrapper.emitted('viewed-document-changed').at(-1)).toEqual([
      { side: 'treeView', documentId: 'doc-2' },
    ]);
    // The closed control shows the new selection
    expect(wrapper.find('.singleselect__selected-value').text()).toContain('Other Framework');
  });

  it('preserves mount-time emit for non-compact (side-panel) usage', () => {
    const wrapper = mountSelector({ side: 'right', hideExternal: false, compact: false });

    expect(wrapper.emitted('viewed-document-changed')).toEqual([
      [{ side: 'right', documentId: 'doc-1' }],
    ]);
  });

  it('emits external-document-requested and resets when the external action is chosen', async () => {
    const wrapper = mountSelector({ side: 'right', hideExternal: false, compact: false });
    await openDropdown(wrapper);

    const option = wrapper.findAll('[role="option"]').find((o) => o.text().includes('Load external document'));
    await option.trigger('click');

    expect(wrapper.emitted('external-document-requested')).toEqual([[{ side: 'right' }]]);
    // Selection falls back to the edited document, not the pseudo-option
    expect(wrapper.emitted('viewed-document-changed').at(-1)).toEqual([
      { side: 'right', documentId: 'doc-1' },
    ]);
    expect(wrapper.find('.singleselect__selected-value').text()).toContain('Edited Framework');
  });

  it('labels framework options with the Main Document prefix for the edited doc', async () => {
    const wrapper = mountSelector({ side: 'right', hideExternal: false, compact: false });
    await openDropdown(wrapper);

    const option = wrapper.findAll('[role="option"]').find((o) => o.text().includes('doc-1'));
    expect(option.text()).toContain('Main Document - Edited Framework (doc-1)');
  });
});

describe('DocumentSelector.vue framework filtering', () => {
  beforeEach(() => {
    vi.clearAllMocks();
  });

  it('filters the list from the single search box by title and identifier', async () => {
    const wrapper = mountSelector({ side: 'right', searchable: true, availableDocuments: longList });
    await openDropdown(wrapper);

    const frameworkOptions = () => wrapper.findAll('[role="option"]')
      .filter((o) => o.text() !== 'Load external document...');
    expect(frameworkOptions().length).toBe(12);

    await wrapper.find('.singleselect__input').setValue('Framework 1');
    // Framework 1, 10, 11, 12 all contain "framework 1"
    expect(frameworkOptions().length).toBe(4);

    await wrapper.find('.singleselect__input').setValue('doc-11');
    expect(frameworkOptions().length).toBe(1);
    expect(frameworkOptions()[0].text()).toContain('Framework 11');

    await wrapper.find('.singleselect__input').setValue('');
    expect(frameworkOptions().length).toBe(12);
  });

  it('keeps whole groups whose creator matches the query', async () => {
    const mixedCreators = [
      ...longList.slice(0, 6),
      doc(7, 'Organization'),
      doc(8, 'Organization'),
      ...longList.slice(8),
    ];
    const wrapper = mountSelector({
      side: 'right',
      availableDocuments: mixedCreators,
      currentDoc: null,
    });
    await openDropdown(wrapper);

    await wrapper.find('.singleselect__input').setValue('organiz');

    const groupHeaders = wrapper.findAll('.singleselect__option-group').map((g) => g.text());
    expect(groupHeaders).toEqual(['Organization']);
    const options = wrapper.findAll('[role="option"]');
    expect(options.length).toBe(2);
  });

  it('shows the no-results text and keeps the selection visible when nothing matches', async () => {
    const wrapper = mountSelector({ side: 'right', availableDocuments: longList });
    await openDropdown(wrapper);

    const combobox = wrapper.find('[role="combobox"]');
    expect(combobox.text()).toContain('(doc-1)');

    await wrapper.find('.singleselect__input').setValue('zzz-no-match');

    expect(wrapper.text()).toContain('No frameworks match the filter');
    expect(wrapper.findAll('[role="option"]').length).toBe(0);
    // The current selection is unaffected — the trigger still shows it
    expect(combobox.text()).toContain('(doc-1)');
  });

  it('announces the number of filtered options to screen readers', async () => {
    const wrapper = mountSelector({ side: 'right', availableDocuments: longList });
    await openDropdown(wrapper);

    const liveRegion = wrapper.find('[aria-live="polite"]');
    await wrapper.find('.singleselect__input').setValue('Framework 1');
    expect(liveRegion.text()).toBe('4 options available');

    await wrapper.find('.singleselect__input').setValue('zzz');
    expect(liveRegion.text()).toBe('No frameworks match the filter');

    await wrapper.find('.singleselect__input').setValue('');
    expect(liveRegion.text()).toBe('');
  });
});
