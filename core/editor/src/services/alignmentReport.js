import { diffWords } from 'diff';
import { treeItemComparator } from '../utils/tree.js';

/**
 * Pure alignment-report algorithm (no Vue, DOM, or api imports).
 * Reconstructs the behavior of the lost reports-dev.opensalt.net app.
 * Spec: docs/superpowers/specs/2026-10-07-alignment-report-design.md §4.3
 */

export const MATCH_ASSOCIATION_TYPES = ['exactMatchOf', 'isRelatedTo', 'isPartOf'];

/**
 * An oriented mapping edge. Every kept association produces two edges
 * (forward + reversed); `reversed` isPartOf displays as "hasPart".
 */
export function edgeDisplayType(edge) {
  if (edge.reversed && edge.type === 'isPartOf') {
    return 'hasPart';
  }
  return edge.type;
}

export function normalizeEdges(associations) {
  const byOrigin = new Map();
  const seen = new Set();

  const addEdge = (associationId, type, originId, destId, reversed) => {
    const key = associationId + (reversed ? 'R' : '');
    if (seen.has(key)) {
      return;
    }
    seen.add(key);
    if (!byOrigin.has(originId)) {
      byOrigin.set(originId, []);
    }
    byOrigin.get(originId).push({ key, associationId, type, reversed, originId, destId });
  };

  for (const a of associations || []) {
    if (!MATCH_ASSOCIATION_TYPES.includes(a.associationType)) {
      continue;
    }
    const originId = a.originNodeURI?.identifier;
    const destId = a.destinationNodeURI?.identifier;
    if (!originId || !destId) {
      continue;
    }
    if (a.destinationNodeURI?.targetType === 'document') {
      continue;
    }
    addEdge(a.identifier, a.associationType, originId, destId, false);
    addEdge(a.identifier, a.associationType, destId, originId, true);
  }

  return byOrigin;
}

function indexItems(tree, doc, index) {
  const visit = (node) => {
    index.set(node.identifier, { node, doc });
    for (const child of node.children || []) {
      visit(child);
    }
  };
  for (const root of tree) {
    visit(root);
  }
}

// Sort with the editor tree's own comparator so report row order always
// matches what users see in the tree UI (sequenceNumber -> humanCodingScheme
// -> listEnumeration, nulls last).
function orderedChildren(node) {
  return [...(node.children || [])].sort(treeItemComparator);
}

function collectMatches(originId, edgesByOrigin, itemIndex) {
  // Returns [{ typeLabel, target }] for destinations in the right framework,
  // plus every right-destination id seen (matched or not) for coverage marking.
  const matches = [];
  const seenDestinations = [];
  const walk = (itemId, label, visited) => {
    for (const edge of edgesByOrigin.get(itemId) || []) {
      if (visited.has(edge.key)) {
        continue;
      }
      visited.add(edge.key);
      const resolved = itemIndex.get(edge.destId);
      const display = edgeDisplayType(edge);
      const typeLabel = label ? label + ',' + display : display;
      if (resolved) {
        if (resolved.doc === 'right') {
          matches.push({ typeLabel, target: resolved.node });
          seenDestinations.push(edge.destId);
        }
        // resolved left/other items contribute nothing (original behavior)
      } else {
        walk(edge.destId, typeLabel, visited);
      }
    }
  };
  walk(originId, '', new Set());
  return { matches, seenDestinations };
}

export function buildAlignmentReport({ leftTree, rightTree, associations }) {
  const itemIndex = new Map();
  indexItems(leftTree || [], 'left', itemIndex);
  indexItems(rightTree || [], 'right', itemIndex);
  const edgesByOrigin = normalizeEdges(associations);

  const rows = [];
  const coveredRightIds = new Set();

  const emitRow = (node, smartLevel, matches) => {
    if (matches.length === 0) {
      rows.push({
        identifier: node.identifier,
        smartLevel,
        humanCodingScheme: node.humanCodingScheme ?? '',
        fullStatement: node.fullStatement ?? '',
        associationType: '',
        matchedHumanCodingScheme: '',
        matchedFullStatement: '',
        matchedIdentifier: '',
        notes: '',
        diffSegments: [],
        exactMatch: false,
        unmatchedSide: null,
      });
      return;
    }
    for (const { typeLabel, target } of matches) {
      rows.push({
        identifier: node.identifier,
        smartLevel,
        humanCodingScheme: node.humanCodingScheme ?? '',
        fullStatement: node.fullStatement ?? '',
        associationType: typeLabel,
        matchedHumanCodingScheme: target.humanCodingScheme ?? '',
        matchedFullStatement: target.fullStatement ?? '',
        matchedIdentifier: target.identifier,
        notes: '',
        diffSegments: diffWords(node.fullStatement ?? '', target.fullStatement ?? ''),
        exactMatch: (node.fullStatement ?? '') === (target.fullStatement ?? ''),
        unmatchedSide: null,
      });
    }
  };

  const visitLeft = (node, prefix) => {
    let i = 0;
    for (const child of orderedChildren(node)) {
      i += 1;
      const smartLevel = prefix + i + '.';
      const { matches, seenDestinations } = collectMatches(child.identifier, edgesByOrigin, itemIndex);
      for (const id of seenDestinations) {
        coveredRightIds.add(id);
      }
      emitRow(child, smartLevel, matches);
      visitLeft(child, smartLevel);
    }
  };
  visitLeft({ children: leftTree || [] }, '');

  const emitFlipped = (node) => {
    if (!coveredRightIds.has(node.identifier)) {
      rows.push({
        identifier: '',
        smartLevel: '',
        humanCodingScheme: '',
        fullStatement: '',
        associationType: '-',
        matchedHumanCodingScheme: node.humanCodingScheme ?? '',
        matchedFullStatement: node.fullStatement ?? '',
        matchedIdentifier: node.identifier,
        notes: '',
        diffSegments: [],
        exactMatch: false,
        unmatchedSide: 'right',
      });
    }
    for (const child of orderedChildren(node)) {
      emitFlipped(child);
    }
  };
  for (const root of rightTree || []) {
    emitFlipped(root);
  }

  return { rows };
}
