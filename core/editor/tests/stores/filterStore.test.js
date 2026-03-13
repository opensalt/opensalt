import { describe, it, expect, beforeEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useFilterStore } from '@/stores/filterStore.js';

describe('FilterStore', () => {
  let filterStore;

  beforeEach(() => {
    setActivePinia(createPinia());
    filterStore = useFilterStore();
  });

  it('initializes selectedAssociationGroup to "default"', () => {
    expect(filterStore.selectedAssociationGroup).toBe('default');
  });

  it('selects default on framework switch when at least one association is ungrouped', () => {
    filterStore.setSelectedAssociationGroup('group-1');

    filterStore.syncSelectedAssociationGroup({
      frameworkId: 'framework-1',
      associations: [
        { identifier: 'assoc-1' }
      ],
      realGroupIds: ['group-1']
    });

    expect(filterStore.selectedAssociationGroup).toBe('default');
  });

  it('selects the sole real group when there are no default-group associations', () => {
    filterStore.syncSelectedAssociationGroup({
      frameworkId: 'framework-1',
      associations: [
        {
          identifier: 'assoc-1',
          CFAssociationGroupingURI: { identifier: 'group-1', uri: '/groups/group-1', title: 'Group 1' }
        }
      ],
      realGroupIds: ['group-1']
    });

    expect(filterStore.selectedAssociationGroup).toBe('group-1');
  });

  it('keeps default when no default-group associations and multiple real groups exist', () => {
    filterStore.syncSelectedAssociationGroup({
      frameworkId: 'framework-1',
      associations: [
        {
          identifier: 'assoc-1',
          CFAssociationGroupingURI: { identifier: 'group-1', uri: '/groups/group-1', title: 'Group 1' }
        }
      ],
      realGroupIds: ['group-1', 'group-2']
    });

    expect(filterStore.selectedAssociationGroup).toBe('default');
  });

  it('preserves current selection on same-framework reload when still valid', () => {
    filterStore.syncSelectedAssociationGroup({
      frameworkId: 'framework-1',
      associations: [],
      realGroupIds: ['group-1', 'group-2']
    });

    filterStore.setSelectedAssociationGroup('group-2');

    filterStore.syncSelectedAssociationGroup({
      frameworkId: 'framework-1',
      associations: [],
      realGroupIds: ['group-1', 'group-2']
    });

    expect(filterStore.selectedAssociationGroup).toBe('group-2');
  });
});
