/**
 * Renders alignment report rows to a real .xlsx via @office-kit/xlsx.
 * Dynamically imported library: never part of the main editor bundle.
 * Spec: docs/superpowers/specs/2026-10-07-alignment-report-design.md §3
 */

const HEADERS = [
  'identifier',
  'smartLevel',
  'humanCodingScheme',
  'fullStatement',
  'Association Type',
  'Matched humanCodingScheme',
  'Matched fullStatement',
  'Matched identifier',
  'notes',
  'Diff',
];

const COLUMN_WIDTHS = [38, 10, 16, 60, 22, 22, 60, 38, 24, 60];

const GREEN = { rgb: 'FF008000' };
const RED = { rgb: 'FFFF0000' };

function markupToBullets(text) {
  return String(text ?? '')
    .replace(/<\/(ul|ol|li)>/gi, '')
    .replace(/<(ul|ol)[^>]*>/gi, '')
    .replace(/<li>/gi, '• ');
}

function slug(text) {
  return (
    String(text ?? '')
      .toLowerCase()
      .replace(/[^a-z0-9]+/g, '-')
      .replace(/^-+|-+$/g, '') || 'framework'
  );
}

function makeDiffCell(diffSegments, { makeRichText, makeTextRun }) {
  const runs = (diffSegments || []).map((s) => {
    if (s.added) {
      return makeTextRun(s.value, { u: 'single', color: GREEN });
    }
    if (s.removed) {
      return makeTextRun(s.value, { strike: true, color: RED });
    }
    return makeTextRun(s.value);
  });
  return { kind: 'rich-text', runs: makeRichText(runs) };
}

export async function exportAlignmentReportXlsx({ rows, leftTitle, rightTitle }) {
  const { createWorkbook, addWorksheet } = await import('@office-kit/xlsx/workbook');
  const { setCell, setColumnWidth, setFreezePanes } = await import('@office-kit/xlsx/worksheet');
  const { makeRichText, makeTextRun } = await import('@office-kit/xlsx/cell');
  const { makeAlignment, registerCellStyle } = await import('@office-kit/xlsx/styles');
  const { workbookToBytes } = await import('@office-kit/xlsx/io');

  const wb = createWorkbook();
  const ws = addWorksheet(wb, 'Alignment Report');

  // Wrap-text on data cells keeps long statements from spilling into
  // neighbouring (empty) cells; vertical top reads naturally when wrapped.
  const wrapStyleId = registerCellStyle(wb, {
    alignment: makeAlignment({ wrapText: true, vertical: 'top', horizontal: 'left' }),
  });

  const put = (row, col, value) => {
    setCell(ws, row, col, value, wrapStyleId);
  };

  HEADERS.forEach((h, i) => {
    // Headers stay unstyled (no wrap) — they are short and single-line.
    setCell(ws, 1, i + 1, { kind: 'rich-text', runs: makeRichText([makeTextRun(h, { b: true })]) });
  });
  COLUMN_WIDTHS.forEach((width, i) => {
    setColumnWidth(ws, i + 1, width);
  });
  // Freeze the header row so it stays visible while scrolling.
  setFreezePanes(ws, { rows: 1, cols: 0 });

  (rows || []).forEach((row, rIdx) => {
    const r = rIdx + 2;
    put(r, 1, row.identifier);
    put(r, 2, row.smartLevel);
    put(r, 3, row.humanCodingScheme ?? '');
    put(r, 4, markupToBullets(row.fullStatement));
    put(r, 5, {
      kind: 'rich-text',
      runs: makeRichText([
        makeTextRun(row.associationType ?? '', row.exactMatch ? { color: GREEN } : undefined),
      ]),
    });
    put(r, 6, row.matchedHumanCodingScheme ?? '');
    put(r, 7, markupToBullets(row.matchedFullStatement));
    put(r, 8, row.matchedIdentifier ?? '');
    put(r, 9, row.notes ?? '');
    put(r, 10, makeDiffCell(row.diffSegments, { makeRichText, makeTextRun }));
  });

  const bytes = await workbookToBytes(wb);
  const blob = new Blob([bytes], {
    type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
  });
  return {
    blob,
    filename: `alignment-report-${slug(leftTitle)}-to-${slug(rightTitle)}.xlsx`,
  };
}
