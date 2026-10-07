import { describe, it, expect, vi, beforeEach } from 'vitest';

const apiGet = vi.fn();
vi.mock('@/services/api', () => ({
  api: { get: (...args) => apiGet(...args) },
}));

const fetchTree = vi.fn();
vi.mock('@/stores/documentStore', () => ({
  useDocumentStore: () => ({ fetchTree }),
}));

import { useAlignmentReport } from '@/composables/useAlignmentReport';

function page(items, total) {
  return Promise.resolve({ data: items, total, frameworkIdentifier: 'x' });
}

function itemNode(identifier, fullStatement) {
  return {
    identifier,
    fullStatement,
    humanCodingScheme: null,
    sequenceNumber: null,
    listEnumeration: null,
    children: [],
  };
}

describe('useAlignmentReport', () => {
  beforeEach(() => {
    apiGet.mockReset();
    fetchTree.mockReset();
  });

  it('paginates framework associations until total is consumed and dedupes by identifier', async () => {
    const mk = (id) => ({
      identifier: id,
      associationType: 'exactMatchOf',
      associationDocumentIdentifier: 'A',
      originNodeURI: { identifier: 'o-' + id, targetType: 'item' },
      destinationNodeURI: { identifier: 'd-' + id, targetType: 'item' },
    });
    apiGet
      .mockImplementationOnce(() => page([mk('1'), mk('2')], 3, 0))
      .mockImplementationOnce(() => page([mk('2'), mk('3')], 3, 1000));

    const { fetchAllAssociations } = useAlignmentReport();
    const all = await fetchAllAssociations('docA');

    expect(apiGet).toHaveBeenCalledTimes(2);
    expect(apiGet.mock.calls[0][0]).toBe('/framework/editor/associations/framework/docA?limit=1000&offset=0');
    expect(apiGet.mock.calls[1][0]).toBe('/framework/editor/associations/framework/docA?limit=1000&offset=2');
    expect(all.map((a) => a.identifier)).toEqual(['1', '2', '3']);
  });

  it('generate() loads both trees, fetches only the left framework associations, and sets rows', async () => {
    const leftTree = [itemNode('A1', 'a')];
    const rightTree = [itemNode('B1', 'a')];
    fetchTree.mockImplementation(async (id) => ({
      document: { identifier: id, title: id },
      tree: id === 'left' ? leftTree : rightTree,
    }));
    // A mapping stored in a third document (associationDocumentIdentifier: 'C'),
    // origin = left item, destination = right item. The left framework's
    // association fetch spans owning documents, so this is found without
    // fetching the right framework's associations.
    apiGet.mockImplementation(() => page([{
      identifier: 'm1',
      associationType: 'exactMatchOf',
      associationDocumentIdentifier: 'C',
      originNodeURI: { identifier: 'A1', documentIdentifier: 'A', targetType: 'item' },
      destinationNodeURI: { identifier: 'B1', documentIdentifier: 'B', targetType: 'item' },
    }], 1, 0));

    const { generate, rows, loading, error } = useAlignmentReport();
    await generate({ leftId: 'left', rightId: 'right' });

    expect(loading.value).toBe(false);
    expect(error.value).toBeNull();
    expect(fetchTree).toHaveBeenCalledWith('left');
    expect(fetchTree).toHaveBeenCalledWith('right');
    expect(apiGet).toHaveBeenCalledTimes(1);
    expect(apiGet.mock.calls[0][0]).toBe('/framework/editor/associations/framework/left?limit=1000&offset=0');
    const matchRows = rows.value.filter((r) => r.identifier === 'A1' && r.unmatchedSide === null);
    expect(matchRows).toHaveLength(1);
    expect(matchRows[0].matchedIdentifier).toBe('B1');
    expect(matchRows[0].exactMatch).toBe(true);
  });

  it('surfaces fetch errors on error state and clears rows', async () => {
    fetchTree.mockRejectedValue(new Error('boom'));
    const { generate, rows, error, loading } = useAlignmentReport();
    await generate({ leftId: 'x', rightId: 'y' });
    expect(error.value).toBe('boom');
    expect(loading.value).toBe(false);
    expect(rows.value).toEqual([]);
  });

  it('reports progress through the fetch phases and completes at 100', async () => {
    const leftTree = [itemNode('A1', 'a')];
    const rightTree = [itemNode('B1', 'a')];
    fetchTree.mockImplementation(async (id) => ({
      document: { identifier: id, title: id },
      tree: id === 'left' ? leftTree : rightTree,
    }));
    // Page 1 of the left fetch resolves immediately; page 2 stays pending so we
    // can sample mid-generation progress.
    let resolvePage2;
    apiGet
      .mockImplementationOnce(() => page([{
        identifier: 'm1',
        associationType: 'exactMatchOf',
        associationDocumentIdentifier: 'left',
        originNodeURI: { identifier: 'A1', targetType: 'item' },
        destinationNodeURI: { identifier: 'B1', targetType: 'item' },
      }], 2, 0))
      .mockImplementationOnce(() => new Promise((resolve) => {
        resolvePage2 = resolve;
      }));

    const { generate, rows, progress } = useAlignmentReport();
    const done = generate({ leftId: 'left', rightId: 'right' });

    // Wait until the first page is consumed and the second is pending.
    await vi.waitFor(() => expect(apiGet).toHaveBeenCalledTimes(2));
    await Promise.resolve();
    expect(progress.value).toBeGreaterThanOrEqual(10);
    expect(progress.value).toBeLessThan(90);

    resolvePage2(page([], 2, 1));
    await done;
    expect(progress.value).toBe(100);
    expect(rows.value.length).toBeGreaterThan(0);
  });
});
