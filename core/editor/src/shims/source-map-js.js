/**
 * Browser-compatible shim for the "source-map-js" module.
 *
 * Only the surface area required by PostCSS (bundled transitively via
 * sanitize-html) is provided.  Source-map generation / consumption is never
 * needed at runtime in the browser editor, so we expose lightweight stubs
 * that satisfy PostCSS's `Boolean(SourceMapConsumer)` guards without
 * triggering Vite's browser-externalization warnings.
 */

export class SourceMapConsumer {
  constructor() {}
  static async initialize() {}
  static async with() {}
  get sources() { return []; }
  get sourcesContent() { return []; }
  get mappings() { return ''; }
  get names() { return []; }
  get version() { return 3; }
  computeColumnSpans() {}
  allGeneratedPositionsFor() { return []; }
  eachMapping() {}
  generatedPositionFor() { return { line: null, column: null }; }
  originalPositionFor() { return { source: null, line: null, column: null, name: null }; }
  destroy() {}
}

export class SourceMapGenerator {
  constructor() {
    this._map = { version: 3, sources: [], names: [], mappings: '', sourcesContent: [] };
  }
  static fromSourceMap() { return new SourceMapGenerator(); }
  addMapping() {}
  setSourceContent() {}
  applySourceMap() {}
  toString() { return JSON.stringify(this._map); }
  toJSON() { return this._map; }
}

export class SourceNode {
  constructor() {}
  static fromStringWithSourceMap() { return new SourceNode(); }
  add() {}
  prepend() {}
  setSourceContent() {}
  toString() { return ''; }
  toStringWithSourceMap() { return { code: '', map: new SourceMapGenerator() }; }
  walk() {}
  walkSourceContents() {}
}
