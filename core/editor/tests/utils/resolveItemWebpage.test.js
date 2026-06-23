import { describe, it, expect, beforeEach, afterEach, vi } from 'vitest';
import { resolveItemWebpage } from '@/utils/resolveItemWebpage.js';

describe('resolveItemWebpage', () => {
  beforeEach(() => {
    Object.defineProperty(window, 'location', {
      value: { origin: 'http://localhost', pathname: '/cftree/doc/123' },
      writable: true,
    });
  });

  afterEach(() => {
    vi.restoreAllMocks();
  });

  it('returns subjectWebpage when it is a valid URL', () => {
    const result = resolveItemWebpage('local:abc', 'https://example.com/page');
    expect(result).toEqual({ href: 'https://example.com/page', display: 'https://example.com/page' });
  });

  it('returns subjectWebpage when it is an http URL', () => {
    const result = resolveItemWebpage('local:abc', 'http://example.com/page');
    expect(result).toEqual({ href: 'http://example.com/page', display: 'http://example.com/page' });
  });

  it('falls back to uri when subjectWebpage is empty', () => {
    const result = resolveItemWebpage('https://example.com/item', '');
    expect(result).toEqual({ href: 'https://example.com/item', display: 'https://example.com/item' });
  });

  it('falls back to uri when subjectWebpage is null', () => {
    const result = resolveItemWebpage('https://example.com/item', null);
    expect(result).toEqual({ href: 'https://example.com/item', display: 'https://example.com/item' });
  });

  it('falls back to uri when subjectWebpage does not look like a URL', () => {
    const result = resolveItemWebpage('https://example.com/item', 'not a url');
    expect(result).toEqual({ href: 'https://example.com/item', display: 'https://example.com/item' });
  });

  it('rewrites local: prefix to base URL + /uri/<identifier>', () => {
    const result = resolveItemWebpage('local:abc123', null);
    expect(result).toEqual({
      href: 'http://localhost/uri/abc123',
      display: 'http://localhost/uri/abc123',
    });
  });

  it('rewrites local: prefix with long identifier', () => {
    const result = resolveItemWebpage('local:urn:opensalt:item:uuid-9999', '');
    expect(result).toEqual({
      href: 'http://localhost/uri/urn:opensalt:item:uuid-9999',
      display: 'http://localhost/uri/urn:opensalt:item:uuid-9999',
    });
  });

  it('returns uri as-is when it is a regular URL (no local: prefix)', () => {
    const result = resolveItemWebpage('https://example.com/item', null);
    expect(result).toEqual({ href: 'https://example.com/item', display: 'https://example.com/item' });
  });
});
