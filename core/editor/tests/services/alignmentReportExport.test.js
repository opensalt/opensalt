// @vitest-environment node
//
// Node realm (not jsdom): @office-kit/xlsx's zip writer checks
// `bytes instanceof Uint8Array`, and jsdom's cross-realm TextEncoder output
// fails that check. The util needs only Blob (global in Node >= 18).
import { describe, it, expect } from 'vitest';
import { exportAlignmentReportXlsx } from '@/services/alignmentReportExport';

const rows = [
  {
    identifier: 'A1',
    smartLevel: '1.',
    humanCodingScheme: 'A.1',
    fullStatement: '<ul><li>Grade 1</li><li>Grade 2</li></ul>',
    associationType: 'exactMatchOf',
    matchedHumanCodingScheme: 'B.1',
    matchedFullStatement: 'Grade 1 and Grade 2',
    matchedIdentifier: 'B1',
    notes: '',
    diffSegments: [
      { value: 'Grade ', added: undefined, removed: undefined },
      { value: '1', added: undefined, removed: true },
      { value: ' and ', added: undefined, removed: undefined },
      { value: '2', added: true, removed: undefined },
    ],
    exactMatch: false,
    unmatchedSide: null,
  },
  {
    identifier: '',
    smartLevel: '',
    humanCodingScheme: '',
    fullStatement: '',
    associationType: '-',
    matchedHumanCodingScheme: 'B.2',
    matchedFullStatement: 'Unmatched right item',
    matchedIdentifier: 'B2',
    notes: '',
    diffSegments: [],
    exactMatch: false,
    unmatchedSide: 'right',
  },
];

async function loadWorkbookFromBlob(blob) {
  const { loadWorkbook, fromArrayBuffer } = await import('@office-kit/xlsx/io');
  const { getSheetByIndex } = await import('@office-kit/xlsx/workbook');
  const { getCellByCoord } = await import('@office-kit/xlsx/worksheet');
  const buf = await blob.arrayBuffer();
  const wb = await loadWorkbook(fromArrayBuffer(new Uint8Array(buf)));
  const ws = getSheetByIndex(wb, 0);
  return { wb, ws, getCellByCoord };
}

// Cell read helper: plain values come back as strings; rich text as {kind, runs}.
function cellText(cell) {
  const value = cell?.value;
  if (typeof value === 'string') {
    return value;
  }
  if (value?.kind === 'rich-text') {
    return value.runs.map((r) => r.text).join('');
  }
  return String(value ?? '');
}

describe('exportAlignmentReportXlsx', () => {
  it('produces a downloadable xlsx with the expected sheet and filename', async () => {
    const { blob, filename } = await exportAlignmentReportXlsx({
      rows,
      leftTitle: 'Left Framework!',
      rightTitle: 'Right Framework',
    });

    expect(filename).toBe('alignment-report-left-framework-to-right-framework.xlsx');
    expect(blob.type).toBe('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    const { wb } = await loadWorkbookFromBlob(blob);
    const { getSheet } = await import('@office-kit/xlsx/workbook');
    expect(getSheet(wb, 'Alignment Report')).toBeDefined();
  });

  it('freezes the header row', async () => {
    const { blob } = await exportAlignmentReportXlsx({ rows, leftTitle: 'L', rightTitle: 'R' });
    const { loadWorkbook, fromArrayBuffer } = await import('@office-kit/xlsx/io');
    const { getSheetByIndex } = await import('@office-kit/xlsx/workbook');
    const buf = await blob.arrayBuffer();
    const wb = await loadWorkbook(fromArrayBuffer(new Uint8Array(buf)));
    const ws = getSheetByIndex(wb, 0);
    expect(ws.views?.[0]?.pane?.state).toBe('frozen');
    expect(ws.views?.[0]?.pane?.ySplit).toBe(1);
  });

  it('writes a bold header row', async () => {
    const { ws, getCellByCoord } = await loadWorkbookFromBlob(
      await exportAlignmentReportXlsx({ rows, leftTitle: 'L', rightTitle: 'R' }).then((r) => r.blob),
    );
    const header = getCellByCoord(ws, 'A1');
    expect(cellText(header)).toBe('identifier');
    expect(header.value?.runs?.[0]?.font?.b).toBe(true);
  });

  it('converts list markup to bullets and keeps plain text cells', async () => {
    const { ws, getCellByCoord } = await loadWorkbookFromBlob(
      await exportAlignmentReportXlsx({ rows, leftTitle: 'L', rightTitle: 'R' }).then((r) => r.blob),
    );
    const statement = cellText(getCellByCoord(ws, 'D2'));
    expect(statement).toContain('•');
    expect(statement).not.toContain('<li>');
    expect(cellText(getCellByCoord(ws, 'B2'))).toBe('1.');
    expect(cellText(getCellByCoord(ws, 'H2'))).toBe('B1');
  });

  it('attaches a wrap-text style to data cells so long strings do not spill', async () => {
    const { blob } = await exportAlignmentReportXlsx({ rows, leftTitle: 'L', rightTitle: 'R' });
    const { loadWorkbook, fromArrayBuffer } = await import('@office-kit/xlsx/io');
    const { getSheetByIndex } = await import('@office-kit/xlsx/workbook');
    const { getCellByCoord } = await import('@office-kit/xlsx/worksheet');
    const buf = await blob.arrayBuffer();
    const wb = await loadWorkbook(fromArrayBuffer(new Uint8Array(buf)));
    const ws = getSheetByIndex(wb, 0);

    const statementCell = getCellByCoord(ws, 'D2');
    expect(statementCell.styleId).not.toBe(0);

    // The attached style must carry wrapText.
    const styleSheet = wb.styles ?? wb._styles;
    const xf = styleSheet?.cellXfs?.[statementCell.styleId];
    expect(xf?.alignment?.wrapText).toBe(true);
    // Plain header cell keeps the default style.
    expect(getCellByCoord(ws, 'A1').styleId).toBe(0);
  });

  it('writes colored rich-text diff runs (removed struck, added underlined)', async () => {
    const { ws, getCellByCoord } = await loadWorkbookFromBlob(
      await exportAlignmentReportXlsx({ rows, leftTitle: 'L', rightTitle: 'R' }).then((r) => r.blob),
    );
    const diffCell = getCellByCoord(ws, 'J2');
    expect(diffCell.value?.kind).toBe('rich-text');
    const runs = diffCell.value.runs;
    expect(runs.length).toBe(4);
    const removed = runs.find((r) => r.font?.strike);
    const added = runs.find((r) => r.font?.u);
    expect(removed?.text).toContain('1');
    expect(removed?.font?.color?.rgb).toBe('FFFF0000');
    expect(added?.text).toContain('2');
    expect(added?.font?.color?.rgb).toBe('FF008000');
  });

  it('colors the Association Type cell green on exact matches', async () => {
    const exactRows = [{ ...rows[0], exactMatch: true }];
    const { ws, getCellByCoord } = await loadWorkbookFromBlob(
      await exportAlignmentReportXlsx({ rows: exactRows, leftTitle: 'L', rightTitle: 'R' }).then((r) => r.blob),
    );
    const typeCell = getCellByCoord(ws, 'E2');
    expect(cellText(typeCell)).toBe('exactMatchOf');
    expect(typeCell.value?.runs?.[0]?.font?.color?.rgb).toBe('FF008000');
  });

  it('emits the flipped unmatched-right row', async () => {
    const { ws, getCellByCoord } = await loadWorkbookFromBlob(
      await exportAlignmentReportXlsx({ rows, leftTitle: 'L', rightTitle: 'R' }).then((r) => r.blob),
    );
    expect(cellText(getCellByCoord(ws, 'E3'))).toBe('-');
    expect(cellText(getCellByCoord(ws, 'G3'))).toBe('Unmatched right item');
    expect(cellText(getCellByCoord(ws, 'H3'))).toBe('B2');
  });
});
