import { describe, it, expect, beforeEach } from 'vitest';
import { mount } from '@vue/test-utils';
import { createPinia, setActivePinia } from 'pinia';
import { createRouter, createMemoryHistory } from 'vue-router';
import AlignmentReportView from '@/components/alignmentReport/AlignmentReportView.vue';
import { useDocumentStore } from '@/stores/documentStore';

const seedDocuments = [
  { identifier: 'fw-1', title: 'Alpha Framework', creator: 'State A' },
  { identifier: 'fw-2', title: 'Beta Framework', creator: 'State B' },
];

async function mountView(routeId = 'fw-1') {
  const router = createRouter({
    history: createMemoryHistory(),
    routes: [
      {
        path: '/:frameworkId/alignment-report',
        name: 'AlignmentReportView',
        component: AlignmentReportView,
      },
    ],
  });
  router.push(`/${routeId}/alignment-report`);
  await router.isReady();

  // Seed the store so onMounted skips fetchDocuments (fetch is stubbed in setup.js).
  const store = useDocumentStore();
  store.documents = seedDocuments;

  return mount(AlignmentReportView, {
    global: { plugins: [router] },
  });
}

describe('AlignmentReportView framework selectors', () => {
  beforeEach(() => {
    setActivePinia(createPinia());
  });

  it('names both pickers with their visible captions', async () => {
    const wrapper = await mountView();

    for (const [id, labelText] of [
      ['alignment-source-framework', 'Select framework for alignment'],
      ['alignment-target-framework', 'Select aligned framework'],
    ]) {
      const caption = wrapper.find(`span#${id}-label`);
      expect(caption.exists()).toBe(true);
      expect(caption.text()).toBe(labelText);

      // The SingleSelect combobox takes its accessible name from the caption
      const combobox = wrapper.find(`[role="combobox"]#${id}`);
      expect(combobox.exists()).toBe(true);
      expect(combobox.attributes('aria-labelledby')).toBe(`${id}-label`);
    }
  });

  it('preselects the URL framework in the source picker', async () => {
    const wrapper = await mountView('fw-2');

    const pickers = wrapper.findAllComponents({ name: 'SingleSelect' });
    expect(pickers.length).toBe(2);
    expect(pickers[0].props('modelValue')).toBe('fw-2');
  });

  it('offers grouped "Title (identifier)" options and enables the target picker', async () => {
    const wrapper = await mountView('fw-1');

    const pickers = wrapper.findAllComponents({ name: 'SingleSelect' });
    expect(pickers[0].props('options')).toEqual([
      {
        label: 'State A',
        options: [{ id: 'fw-1', text: 'Alpha Framework (fw-1)' }],
      },
      {
        label: 'State B',
        options: [{ id: 'fw-2', text: 'Beta Framework (fw-2)' }],
      },
    ]);
    // The source framework comes from the URL, so the target picker is enabled
    expect(pickers[1].props('disabled')).toBe(false);
  });

  it('updates the target id through the picker event', async () => {
    const wrapper = await mountView('fw-1');

    const pickers = wrapper.findAllComponents({ name: 'SingleSelect' });
    await pickers[1].vm.$emit('update:modelValue', 'fw-2');

    const button = wrapper.find('#determineDifferencesBtn');
    // With source (fw-1) and target (fw-2) set and different, the button enables
    expect(button.attributes('disabled')).toBeUndefined();
  });
});
