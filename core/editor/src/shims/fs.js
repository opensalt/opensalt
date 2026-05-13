/**
 * Browser-compatible shim for Node.js "fs" module.
 *
 * Only the surface area required by PostCSS (bundled transitively via
 * sanitize-html) is provided.  File-system operations are no-ops in the
 * browser so that PostCSS's source-map file-loading code paths gracefully
 * degrade without triggering Vite's browser-externalization warnings.
 */

export function existsSync() {
  return false;
}

export function readFileSync() {
  return '';
}

export function statSync() {
  throw new Error('fs.statSync is not available in the browser');
}

export function readdirSync() {
  return [];
}

export function mkdirSync() {}

export function writeFileSync() {}

export function unlinkSync() {}

export function accessSync() {}

export function createReadStream() {
  return null;
}

export function createWriteStream() {
  return null;
}

export default {
  existsSync,
  readFileSync,
  statSync,
  readdirSync,
  mkdirSync,
  writeFileSync,
  unlinkSync,
  accessSync,
  createReadStream,
  createWriteStream,
};
