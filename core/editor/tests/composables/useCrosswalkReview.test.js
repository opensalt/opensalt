import { describe, it, expect, vi } from 'vitest';

vi.mock('pinia', () => ({ defineStore: vi.fn(), storeToRefs: vi.fn() }));

// Singleton store so the composable and the test share the same mock instance.
const mockStore = vi.hoisted(() => ({
  currentDocument: { identifier: '156', id: '156', title: 'Crosswalk Framework' },
  fetchFrameworkAssociations: vi.fn(),
  invalidateItemDetailsCache: vi.fn(),
  updateAssociation: vi.fn(),
  removeAssociation: vi.fn(),
}));

vi.mock('../../src/stores/currentDocumentStore.ts', () => ({
  useCurrentDocumentStore: vi.fn(() => mockStore),
}));

const crosswalkAssociations = [
  {
    identifier: 'assoc-1',
    associationType: 'exactMatchOf',
    originNodeURI: { identifier: 'origin-uuid', title: 'Origin Item', uri: 'https://example.com/1', documentIdentifier: 'fw-origin' },
    destinationNodeURI: { identifier: 'dest-uuid', title: 'Dest Item', uri: 'https://example.com/2', documentIdentifier: 'fw-dest' },
    extensions: { 'crosswalk:confidence': 0.95, 'crosswalk:status': 'pending', 'crosswalk:subtype': 'exact' },
  },
  {
    identifier: 'assoc-2',
    associationType: 'isRelatedTo',
    originNodeURI: { identifier: 'origin-uuid-2', title: 'Origin Item 2', uri: 'https://example.com/3', documentIdentifier: 'fw-origin' },
    destinationNodeURI: { identifier: 'dest-uuid-2', title: 'Dest Item 2', uri: 'https://example.com/4', documentIdentifier: 'fw-dest' },
    extensions: { 'crosswalk:confidence': 0.80, 'crosswalk:status': 'pending', 'crosswalk:subtype': 'related' },
  },
];

// Configure return values after crosswalkAssociations is defined.
mockStore.fetchFrameworkAssociations.mockResolvedValue(crosswalkAssociations);
mockStore.updateAssociation.mockResolvedValue(true);
mockStore.removeAssociation.mockResolvedValue(true);

const originTreeItems = [
  { identifier: 'origin-uuid', fullStatement: 'Origin Item', humanCodingScheme: 'MATH.1' },
  { identifier: 'origin-uuid-2', fullStatement: 'Origin Item 2', humanCodingScheme: 'MATH.2' },
  { identifier: 'origin-unmatched-1', fullStatement: 'Unmatched Origin A', humanCodingScheme: 'MATH.3' },
  { identifier: 'origin-unmatched-2', fullStatement: 'Unmatched Origin B', humanCodingScheme: 'ELA.1' },
];

const destTreeItems = [
  { identifier: 'dest-uuid', fullStatement: 'Dest Item', humanCodingScheme: 'STD.1' },
  { identifier: 'dest-uuid-2', fullStatement: 'Dest Item 2', humanCodingScheme: 'STD.2' },
  { identifier: 'dest-unmatched-1', fullStatement: 'Unmatched Dest A', humanCodingScheme: 'STD.3' },
];

vi.mock('../../src/stores/documentStore.ts', () => ({
  useDocumentStore: vi.fn(() => ({
    fetchTree: vi.fn().mockImplementation((frameworkId) => {
      if (frameworkId === 'fw-origin') {
        return Promise.resolve({ tree: originTreeItems });
      }
      if (frameworkId === 'fw-dest') {
        return Promise.resolve({ tree: destTreeItems });
      }
      return Promise.resolve({ tree: [] });
    }),
  }))
}));

describe('useCrosswalkReview', () => {
  it('loads associations from current document', async () => {
    const { useCrosswalkReview } = await import('../../src/composables/useCrosswalkReview.js');
    const { allAssociations, loadCrosswalkAssociations } = useCrosswalkReview();
    await loadCrosswalkAssociations();
    expect(allAssociations.value).toHaveLength(2);
  });

  it('has correct initial stats', async () => {
    const { useCrosswalkReview } = await import('../../src/composables/useCrosswalkReview.js');
    const { stats, loadOriginAndDestinationItems } = useCrosswalkReview();
    await loadOriginAndDestinationItems();
    expect(stats.value.matched).toBe(2);
    expect(stats.value.unmatchedOrigin).toBe(2);
    expect(stats.value.unmatchedDestination).toBe(1);
    expect(stats.value.originTotal).toBe(4);
    expect(stats.value.destinationTotal).toBe(3);
    expect(stats.value.pending).toBe(2);
  });

  it('filters by confidence range', async () => {
    const { useCrosswalkReview } = await import('../../src/composables/useCrosswalkReview.js');
    const { filters, filteredPairs, loadOriginAndDestinationItems } = useCrosswalkReview();
    await loadOriginAndDestinationItems();
    expect(filteredPairs.value).toHaveLength(2);
    filters.value.confidenceMin = 0.85;
    filters.value.confidenceMax = 1.0;
    expect(filteredPairs.value).toHaveLength(1);
    expect(filteredPairs.value[0].confidence).toBe(0.95);
  });

  it('filters by search text with case folding', async () => {
    const { useCrosswalkReview } = await import('../../src/composables/useCrosswalkReview.js');
    const { filters, filteredPairs, filteredUnmatchedOrigin, loadOriginAndDestinationItems } = useCrosswalkReview();
    await loadOriginAndDestinationItems();

    filters.value.search = 'math.1';
    expect(filteredPairs.value).toHaveLength(1);
    expect(filteredPairs.value[0].originItem.humanCodingScheme).toBe('MATH.1');

    filters.value.search = 'unmatched origin';
    expect(filteredUnmatchedOrigin.value).toHaveLength(2);

    filters.value.search = 'ela';
    expect(filteredUnmatchedOrigin.value).toHaveLength(1);
    expect(filteredUnmatchedOrigin.value[0].humanCodingScheme).toBe('ELA.1');
  });

  it('filters unmatched items by match-status filter', async () => {
    const { useCrosswalkReview } = await import('../../src/composables/useCrosswalkReview.js');
    const { filters, filteredPairs, filteredUnmatchedOrigin, filteredUnmatchedDestination, loadOriginAndDestinationItems } = useCrosswalkReview();
    await loadOriginAndDestinationItems();

    filters.value.matchStatus = 'matched';
    expect(filteredPairs.value).toHaveLength(2);
    expect(filteredUnmatchedOrigin.value).toHaveLength(0);
    expect(filteredUnmatchedDestination.value).toHaveLength(0);

    filters.value.matchStatus = 'unmatched-origin';
    expect(filteredPairs.value).toHaveLength(0);
    expect(filteredUnmatchedOrigin.value).toHaveLength(2);

    filters.value.matchStatus = 'unmatched-destination';
    expect(filteredPairs.value).toHaveLength(0);
    expect(filteredUnmatchedDestination.value).toHaveLength(1);
  });

  it('hides approved associations when hideApproved filter is set', async () => {
    const { useCrosswalkReview } = await import('../../src/composables/useCrosswalkReview.js');
    const { filters, filteredPairs, approveAssociation, loadOriginAndDestinationItems } = useCrosswalkReview();
    await loadOriginAndDestinationItems();

    crosswalkAssociations[0].extensions['crosswalk:status'] = 'pending';
    crosswalkAssociations[1].extensions['crosswalk:status'] = 'pending';

    expect(filteredPairs.value).toHaveLength(2);

    await approveAssociation('assoc-1');
    expect(filteredPairs.value).toHaveLength(2);

    filters.value.hideApproved = true;
    expect(filteredPairs.value).toHaveLength(1);
    expect(filteredPairs.value[0].id).toBe('assoc-2');

    crosswalkAssociations[0].extensions['crosswalk:status'] = 'pending';
  });

  it('persists bulk approve for selected associations', async () => {
    const { useCrosswalkReview } = await import('../../src/composables/useCrosswalkReview.js');
    const { bulkApprove, loadOriginAndDestinationItems } = useCrosswalkReview();
    await loadOriginAndDestinationItems();

    mockStore.updateAssociation.mockClear();

    await bulkApprove(new Set(['assoc-1', 'assoc-2']));

    expect(mockStore.updateAssociation).toHaveBeenCalledTimes(2);
    expect(mockStore.updateAssociation).toHaveBeenCalledWith('assoc-1', expect.objectContaining({
      extensions: { 'crosswalk:status': 'approved' },
    }));
    expect(mockStore.updateAssociation).toHaveBeenCalledWith('assoc-2', expect.objectContaining({
      extensions: { 'crosswalk:status': 'approved' },
    }));
  });

  it('ignores unmatched row IDs in bulk approve', async () => {
    const { useCrosswalkReview } = await import('../../src/composables/useCrosswalkReview.js');
    const { bulkApprove, loadOriginAndDestinationItems } = useCrosswalkReview();
    await loadOriginAndDestinationItems();

    mockStore.updateAssociation.mockClear();

    await bulkApprove(new Set(['assoc-1', 'unmatched-origin-xyz']));

    expect(mockStore.updateAssociation).toHaveBeenCalledTimes(1);
    expect(mockStore.updateAssociation).toHaveBeenCalledWith('assoc-1', expect.anything());
  });

  it('deletes associations in bulk reject for selected ids', async () => {
    const { useCrosswalkReview } = await import('../../src/composables/useCrosswalkReview.js');
    const { bulkReject, loadOriginAndDestinationItems } = useCrosswalkReview();
    await loadOriginAndDestinationItems();

    mockStore.removeAssociation.mockClear();

    await bulkReject(new Set(['assoc-1', 'assoc-2']));

    expect(mockStore.removeAssociation).toHaveBeenCalledTimes(2);
    expect(mockStore.removeAssociation).toHaveBeenCalledWith('assoc-1');
    expect(mockStore.removeAssociation).toHaveBeenCalledWith('assoc-2');
  });

  it('persists edited association via store then reloads', async () => {
    const { useCrosswalkReview } = await import('../../src/composables/useCrosswalkReview.js');
    const { editAssociation, loadOriginAndDestinationItems } = useCrosswalkReview();
    await loadOriginAndDestinationItems();

    mockStore.updateAssociation.mockClear();

    await editAssociation({
      identifier: 'assoc-1',
      type: 'isRelatedTo',
      annotation: 'Updated note',
    });

    expect(mockStore.updateAssociation).toHaveBeenCalledTimes(1);
    expect(mockStore.updateAssociation).toHaveBeenCalledWith('assoc-1', expect.objectContaining({
      type: 'isRelatedTo',
      annotation: 'Updated note',
    }));
    expect(mockStore.fetchFrameworkAssociations).toHaveBeenCalled();
  });
});
