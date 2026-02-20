import { describe, it, expect, vi, beforeEach, afterEach } from 'vitest';
import { setActivePinia, createPinia } from 'pinia';
import { useAssociationStore } from '@/stores/associationStore.js';
import { api } from '@/services/api.js';

vi.mock('@/services/api.js', () => ({
  api: {
    get: vi.fn(),
    post: vi.fn()
  }
}));

describe('AssociationStore', () => {
  let associationStore;

  beforeEach(() => {
    setActivePinia(createPinia());
    associationStore = useAssociationStore();
    vi.clearAllMocks();
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  describe('initial state', () => {
    it('has selectedAssociationGroup set to "all"', () => {
      expect(associationStore.selectedAssociationGroup).toBe('all');
    });

    it('has loading set to false', () => {
      expect(associationStore.loading).toBe(false);
    });

    it('has error set to null', () => {
      expect(associationStore.error).toBeNull();
    });
  });

  describe('associationGroups getter', () => {
    it('returns default groups', () => {
      const groups = associationStore.associationGroups;

      expect(groups).toHaveLength(2);
      expect(groups[0]).toEqual({
        id: 'all',
        title: 'All Groups',
        description: 'Show items from all association groups'
      });
      expect(groups[1]).toEqual({
        id: 'default',
        title: 'Default Group',
        description: 'Default association group'
      });
    });
  });

  describe('setSelectedAssociationGroup', () => {
    it('sets selected association group', () => {
      associationStore.setSelectedAssociationGroup('group-1');
      expect(associationStore.selectedAssociationGroup).toBe('group-1');
    });
  });

  describe('fetchAssociationGroups', () => {
    it('fetches association groups successfully', async () => {
      const mockGroups = [
        { id: 'group-1', title: 'Group 1' },
        { id: 'group-2', title: 'Group 2' }
      ];

      api.get.mockResolvedValueOnce(mockGroups);

      const result = await associationStore.fetchAssociationGroups('doc-1');

      expect(result).toEqual(mockGroups);
      expect(api.get).toHaveBeenCalledWith('/cftree/association_groupings/doc-1');
      expect(associationStore.loading).toBe(false);
      expect(associationStore.error).toBeNull();
    });

    it('handles fetch error', async () => {
      const errorMessage = 'Failed to fetch association groups';
      api.get.mockRejectedValueOnce(new Error(errorMessage));

      await expect(associationStore.fetchAssociationGroups('doc-1')).rejects.toThrow(errorMessage);
      expect(associationStore.error).toBe(errorMessage);
      expect(associationStore.loading).toBe(false);
    });

    it('sets loading state during fetch', async () => {
      let resolvePromise;
      api.get.mockImplementationOnce(() => new Promise(resolve => {
        resolvePromise = resolve;
      }));

      const fetchPromise = associationStore.fetchAssociationGroups('doc-1');
      expect(associationStore.loading).toBe(true);

      resolvePromise([]);
      await fetchPromise;

      expect(associationStore.loading).toBe(false);
    });
  });

  describe('createAssociationGroup', () => {
    it('creates association group successfully', async () => {
      const groupData = {
        title: 'New Group',
        description: 'Group description'
      };
      const mockResponse = { id: 'group-1', ...groupData };

      api.post.mockResolvedValueOnce(mockResponse);

      const result = await associationStore.createAssociationGroup('doc-1', groupData);

      expect(result).toEqual(mockResponse);
      expect(api.post).toHaveBeenCalledWith(
        '/cftree/association_grouping/new/doc-1',
        groupData
      );
    });

    it('handles create error', async () => {
      const errorMessage = 'Failed to create association group';
      api.post.mockRejectedValueOnce(new Error(errorMessage));

      await expect(
        associationStore.createAssociationGroup('doc-1', { title: 'Test' })
      ).rejects.toThrow(errorMessage);

      expect(associationStore.error).toBe(errorMessage);
    });
  });

  describe('updateAssociationGroup', () => {
    it('updates association group successfully', async () => {
      const groupData = {
        title: 'Updated Title',
        description: 'Updated description'
      };
      const mockResponse = { id: 'group-1', ...groupData };

      api.post.mockResolvedValueOnce(mockResponse);

      const result = await associationStore.updateAssociationGroup('group-1', groupData);

      expect(result).toEqual(mockResponse);
      expect(api.post).toHaveBeenCalledWith(
        '/cftree/association_grouping/update/group-1',
        groupData
      );
    });

    it('handles update error', async () => {
      const errorMessage = 'Failed to update association group';
      api.post.mockRejectedValueOnce(new Error(errorMessage));

      await expect(
        associationStore.updateAssociationGroup('group-1', { title: 'Test' })
      ).rejects.toThrow(errorMessage);

      expect(associationStore.error).toBe(errorMessage);
    });
  });

  describe('deleteAssociationGroup', () => {
    it('deletes association group successfully', async () => {
      api.post.mockResolvedValueOnce({});

      const result = await associationStore.deleteAssociationGroup('group-1');

      expect(result).toBe(true);
      expect(api.post).toHaveBeenCalledWith('/cftree/association_grouping/delete/group-1');
    });

    it('handles delete error', async () => {
      const errorMessage = 'Failed to delete association group';
      api.post.mockRejectedValueOnce(new Error(errorMessage));

      await expect(
        associationStore.deleteAssociationGroup('group-1')
      ).rejects.toThrow(errorMessage);

      expect(associationStore.error).toBe(errorMessage);
    });
  });

  describe('fetchAssociations', () => {
    it('fetches associations without filters', async () => {
      const mockAssociations = [
        { identifier: 'assoc-1', associationType: 'isChildOf' },
        { identifier: 'assoc-2', associationType: 'isRelatedTo' }
      ];

      api.get.mockResolvedValueOnce(mockAssociations);

      const result = await associationStore.fetchAssociations('doc-1');

      expect(result).toEqual(mockAssociations);
      expect(api.get).toHaveBeenCalledWith('/cftree/associations/doc-1');
    });

    it('fetches associations with filters', async () => {
      const mockAssociations = [{ identifier: 'assoc-1' }];

      api.get.mockResolvedValueOnce(mockAssociations);

      const result = await associationStore.fetchAssociations('doc-1', {
        type: 'isChildOf',
        groupId: 'group-1'
      });

      expect(result).toEqual(mockAssociations);
      expect(api.get).toHaveBeenCalledWith(
        expect.stringContaining('/cftree/associations/doc-1?')
      );
      expect(api.get).toHaveBeenCalledWith(
        expect.stringContaining('type=isChildOf')
      );
      expect(api.get).toHaveBeenCalledWith(
        expect.stringContaining('groupId=group-1')
      );
    });

    it('encodes filter values', async () => {
      api.get.mockResolvedValueOnce([]);

      await associationStore.fetchAssociations('doc-1', {
        type: 'is Child Of'
      });

      expect(api.get).toHaveBeenCalledWith(
        expect.stringContaining('is%20Child%20Of')
      );
    });

    it('handles fetch error', async () => {
      const errorMessage = 'Failed to fetch associations';
      api.get.mockRejectedValueOnce(new Error(errorMessage));

      await expect(associationStore.fetchAssociations('doc-1')).rejects.toThrow(errorMessage);
      expect(associationStore.error).toBe(errorMessage);
    });

    it('sets loading state during fetch', async () => {
      let resolvePromise;
      api.get.mockImplementationOnce(() => new Promise(resolve => {
        resolvePromise = resolve;
      }));

      const fetchPromise = associationStore.fetchAssociations('doc-1');
      expect(associationStore.loading).toBe(true);

      resolvePromise([]);
      await fetchPromise;

      expect(associationStore.loading).toBe(false);
    });
  });

  describe('clearError', () => {
    it('clears error state', async () => {
      api.get.mockRejectedValueOnce(new Error('Test error'));
      await associationStore.fetchAssociationGroups('doc-1').catch(() => {});

      expect(associationStore.error).toBe('Test error');

      associationStore.clearError();
      expect(associationStore.error).toBeNull();
    });
  });
});
