/**
 * Browser-compatible shim for Node.js "url" module.
 *
 * Only the surface area required by PostCSS (bundled transitively via
 * sanitize-html) is provided.  URL-to-path conversions are no-ops in the
 * browser so that PostCSS's source-map code paths gracefully degrade without
 * triggering Vite's browser-externalization warnings.
 */

export function fileURLToPath(url) {
  if (typeof url === 'string') return url;
  return url != null ? url.pathname : '';
}

export function pathToFileURL(path) {
  return { href: path, pathname: path };
}

export function parse(urlStr) {
  try {
    return new URL(urlStr);
  } catch {
    return { protocol: null, slashes: null, auth: null, host: null, port: null, hostname: null, hash: null, search: null, query: null, pathname: null, path: null, href: urlStr };
  }
}

export function format(urlObj) {
  return urlObj != null ? String(urlObj.href || urlObj) : '';
}

export function resolve(from, to) {
  return to || from;
}

export default {
  fileURLToPath,
  pathToFileURL,
  parse,
  format,
  resolve,
};
