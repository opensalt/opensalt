/**
 * Browser-compatible shim for Node.js "path" module.
 *
 * Only the surface area required by PostCSS (bundled transitively via
 * sanitize-html) is provided.  Every function returns a safe no-op value so
 * that Boolean() checks inside PostCSS succeed without triggering Vite's
 * browser-externalization warnings.
 */

export const sep = '/';
export const delimiter = ':';

export function basename(p) {
  return p ? p.split('/').pop() : '';
}

export function dirname(p) {
  if (!p) return '.';
  const parts = p.split('/');
  parts.pop();
  return parts.join('/') || '.';
}

export function extname(p) {
  const base = basename(p);
  const idx = base.lastIndexOf('.');
  return idx > 0 ? base.slice(idx) : '';
}

export function isAbsolute(p) {
  return p != null && p.startsWith('/');
}

export function join(...segments) {
  return segments.filter(Boolean).join('/').replace(/\/+/g, '/');
}

export function relative(from, to) {
  return to || '';
}

export function resolve(...segments) {
  return segments.filter(Boolean).join('/').replace(/\/+/g, '/') || '/';
}

export function normalize(p) {
  return p || '.';
}

export function parse(p) {
  const root = isAbsolute(p) ? '/' : '';
  const dir = dirname(p);
  const base = basename(p);
  const ext = extname(p);
  const name = base.slice(0, base.length - ext.length);
  return { root, dir, base, ext, name };
}

export function format(parsed) {
  return parsed.dir ? `${parsed.dir}/${parsed.base}` : parsed.base || '';
}
