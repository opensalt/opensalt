/**
 * Resolve a URI (with optional subjectWebpage override) into a displayable
 * webpage link with both href and display text.
 *
 * @param {string} uri            - The item URI (fallback)
 * @param {string|null|undefined} subjectWebpage - Override from extensions
 * @returns {{ href: string, display: string }}
 */
export function resolveItemWebpage(uri, subjectWebpage) {
  // Prefer subjectWebpage if it looks like a URL
  if (subjectWebpage && /^https?:\/\//i.test(subjectWebpage)) {
    return { href: subjectWebpage, display: subjectWebpage };
  }

  // Rewrite local: prefix to a resolvable URL
  if (uri && uri.startsWith('local:')) {
    const identifier = uri.slice(6); // strip 'local:'
    const base = window.location.origin;
    const resolved = `${base.replace(/\/$/, '')}/uri/${identifier}`;
    return { href: resolved, display: resolved };
  }

  return { href: uri || '', display: uri || '' };
}
