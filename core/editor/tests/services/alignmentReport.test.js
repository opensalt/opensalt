import { describe, it, expect } from 'vitest';
import { normalizeEdges, edgeDisplayType, MATCH_ASSOCIATION_TYPES, buildAlignmentReport } from '@/services/alignmentReport';

function assoc(id, type, origin, dest, { originDoc = 'A', destDoc = 'B', destTarget = 'item' } = {}) {
  return {
    identifier: id,
    associationType: type,
    associationDocumentIdentifier: 'A',
    originNodeURI: { identifier: origin, documentIdentifier: originDoc, targetType: 'item' },
    destinationNodeURI: { identifier: dest, documentIdentifier: destDoc, targetType: destTarget },
  };
}

describe('normalizeEdges', () => {
  it('exports the three matched association types', () => {
    expect(MATCH_ASSOCIATION_TYPES).toEqual(['exactMatchOf', 'isRelatedTo', 'isPartOf']);
  });

  it('indexes forward and reverse edges by origin item', () => {
    const edges = normalizeEdges([assoc('a1', 'exactMatchOf', 'itemA1', 'itemB1')]);
    const fromA1 = edges.get('itemA1').map((e) => [e.originId, e.destId, e.reversed]);
    const fromB1 = edges.get('itemB1').map((e) => [e.originId, e.destId, e.reversed]);
    expect(fromA1).toContainEqual(['itemA1', 'itemB1', false]);
    expect(fromB1).toContainEqual(['itemB1', 'itemA1', true]);
  });

  it('flags reversed isPartOf for hasPart display', () => {
    const edges = normalizeEdges([assoc('a1', 'isPartOf', 'child', 'parent')]);
    const rev = edges.get('parent').find((e) => e.reversed);
    expect(edgeDisplayType(rev)).toBe('hasPart');
    const fwd = edges.get('child').find((e) => !e.reversed);
    expect(edgeDisplayType(fwd)).toBe('isPartOf');
  });

  it('keeps other types in both directions unchanged', () => {
    const edges = normalizeEdges([assoc('a1', 'isRelatedTo', 'x', 'y')]);
    expect(edgeDisplayType(edges.get('y')[0])).toBe('isRelatedTo');
  });

  it('drops non-matching association types and document-targeted edges', () => {
    const edges = normalizeEdges([
      assoc('a1', 'isChildOf', 'p', 'c'),
      assoc('a2', 'exactMatchOf', 'q', 'docB', { destTarget: 'document' }),
    ]);
    expect(edges.size).toBe(0);
  });

  it('keeps dangling destinations (uri targetType) for chain following', () => {
    const edges = normalizeEdges([assoc('a1', 'exactMatchOf', 'itemA1', 'ext-1', { destTarget: 'uri' })]);
    expect(edges.get('itemA1')).toHaveLength(1);
  });

  it('dedupes duplicated associations (A-side and B-side fetch overlap)', () => {
    const dup = assoc('a1', 'exactMatchOf', 'itemA1', 'itemB1');
    dup.associationDocumentIdentifier = 'B';
    const edges = normalizeEdges([dup, { ...dup }]);
    expect(edges.get('itemA1')).toHaveLength(1);
  });
});

function node(identifier, extra = {}, children = []) {
  return {
    identifier,
    fullStatement: identifier + ' statement',
    humanCodingScheme: null,
    sequenceNumber: null,
    listEnumeration: null,
    children,
    ...extra,
  };
}
describe('buildAlignmentReport', () => {
  const leftTree = [
    node('A1', { sequenceNumber: 2 }),
    node('A2', { sequenceNumber: 1 }, [
      node('A2a', { sequenceNumber: 2 }),
      node('A2b', { sequenceNumber: 1, humanCodingScheme: 'A2.b' }),
    ]),
  ];
  const rightTree = [node('B1'), node('B2', {}, [node('B2a')])];

  it('orders children by sequenceNumber and computes smartLevel', () => {
    const { rows } = buildAlignmentReport({ leftTree, rightTree: [], associations: [] });
    const levels = rows.filter((r) => r.identifier).map((r) => [r.identifier, r.smartLevel]);
    expect(levels).toEqual([
      ['A2', '1.'],
      ['A2b', '1.1.'],
      ['A2a', '1.2.'],
      ['A1', '2.'],
    ]);
  });

  it('sorts items without a sequenceNumber last, matching the editor tree order', () => {
    const leftTree = [
      node('A9', {}),
      node('A1', { sequenceNumber: 1 }),
    ];
    const { rows } = buildAlignmentReport({ leftTree, rightTree: [], associations: [] });
    expect(rows.filter((r) => r.identifier).map((r) => r.identifier)).toEqual(['A1', 'A9']);
  });

  it('emits matched rows and carries association type + exactMatch', () => {
    const rows = buildAlignmentReport({
      leftTree: [node('A1', { sequenceNumber: 2, fullStatement: 'Shared statement' })],
      rightTree: [node('B1', { fullStatement: 'Shared statement' })],
      associations: [assoc('m1', 'exactMatchOf', 'A1', 'B1')],
    }).rows;
    const row = rows.find((r) => r.identifier === 'A1');
    expect(row.associationType).toBe('exactMatchOf');
    expect(row.matchedIdentifier).toBe('B1');
    expect(row.matchedFullStatement).toBe('Shared statement');
    expect(row.exactMatch).toBe(true);
    expect(row.smartLevel).toBe('1.');
    expect(row.notes).toBe('');
    expect(row.unmatchedSide).toBeNull();
  });

  it('matches reverse-direction associations with hasPart flip', () => {
    const rows = buildAlignmentReport({
      leftTree,
      rightTree,
      associations: [assoc('m1', 'isPartOf', 'B1', 'A1')],
    }).rows;
    const row = rows.find((r) => r.identifier === 'A1');
    expect(row.associationType).toBe('hasPart');
    expect(row.matchedIdentifier).toBe('B1');
  });

  it('finds mappings stored in a third (crosswalk) framework', () => {
    const crosswalkAssoc = assoc('m1', 'exactMatchOf', 'A1', 'B1');
    crosswalkAssoc.associationDocumentIdentifier = 'docC';
    const rows = buildAlignmentReport({ leftTree, rightTree, associations: [crosswalkAssoc] }).rows;
    expect(rows.find((r) => r.identifier === 'A1').matchedIdentifier).toBe('B1');
  });

  it('follows chains through dangling destinations and accumulates labels', () => {
    const rows = buildAlignmentReport({
      leftTree,
      rightTree,
      associations: [
        assoc('m1', 'exactMatchOf', 'A1', 'ext-1', { destTarget: 'uri' }),
        assoc('m2', 'exactMatchOf', 'ext-1', 'B2'),
      ],
    }).rows;
    const row = rows.find((r) => r.identifier === 'A1');
    expect(row.matchedIdentifier).toBe('B2');
    expect(row.associationType).toBe('exactMatchOf,exactMatchOf');
    expect(row.exactMatch).toBe(false);
  });

  it('emits an empty-match row for left items without matches', () => {
    const rows = buildAlignmentReport({ leftTree, rightTree: [], associations: [] }).rows;
    const row = rows.find((r) => r.identifier === 'A1');
    expect(row).toMatchObject({
      associationType: '',
      matchedIdentifier: '',
      matchedFullStatement: '',
      diffSegments: [],
      exactMatch: false,
      unmatchedSide: null,
    });
  });

  it('appends flipped rows for right items never matched', () => {
    const rows = buildAlignmentReport({
      leftTree,
      rightTree,
      associations: [assoc('m1', 'exactMatchOf', 'A1', 'B1')],
    }).rows;
    const flipped = rows.filter((r) => r.unmatchedSide === 'right').map((r) => r.matchedIdentifier);
    expect(flipped).toEqual(['B2', 'B2a']);
    const b2 = rows.find((r) => r.matchedIdentifier === 'B2' && r.unmatchedSide === 'right');
    expect(b2).toMatchObject({
      identifier: '',
      associationType: '-',
      matchedHumanCodingScheme: '',
      matchedFullStatement: 'B2 statement',
    });
  });

  it('marks matched right items as covered even when the row came from the reverse direction', () => {
    const rows = buildAlignmentReport({
      leftTree,
      rightTree,
      associations: [assoc('m1', 'exactMatchOf', 'B1', 'A1')],
    }).rows;
    const flipped = rows.filter((r) => r.unmatchedSide === 'right').map((r) => r.matchedIdentifier);
    expect(flipped).toEqual(['B2', 'B2a']);
  });

  it('produces word diffSegments for changed statements', () => {
    const left = [node('A1', { fullStatement: 'The quick fox' })];
    const right = [node('B1', { fullStatement: 'The slow fox' })];
    const rows = buildAlignmentReport({
      leftTree: left,
      rightTree: right,
      associations: [assoc('m1', 'exactMatchOf', 'A1', 'B1')],
    }).rows;
    const segs = rows[0].diffSegments;
    expect(segs.some((s) => s.removed && s.value.includes('quick'))).toBe(true);
    expect(segs.some((s) => s.added && s.value.includes('slow'))).toBe(true);
    expect(segs.some((s) => !s.added && !s.removed && s.value.includes('The'))).toBe(true);
  });
});
