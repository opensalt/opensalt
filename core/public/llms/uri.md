# URI lookup and markdown

`/uri/` is the content-negotiated view of an object that already has an identifier. Use it when you want markdown, HTML, JSON-LD, or CSV for one object or one whole framework. Use [CASE paths](/llms/case-api.md) when you need the CASE catalog or a CASE 1.1 package URL.

## Paths

| Request | Returns |
|---------|---------|
| `/uri/{identifier}` | That document, item, association, or definition |
| `/uri/{frameworkIdentifier}/{identifier}` | The object scoped to a framework |
| `/uri/p{documentIdentifier}` | The framework as a package (`p` prefix plus the document UUID) |
| `/uri/` | Not found. An identifier is required |

## Choosing a representation

Send `Accept`, or put the format in the path:

| Format | Path suffix | `Accept` |
|--------|-------------|----------|
| Markdown (preferred for agents) | `.md` | `text/markdown` |
| JSON | `.json` | `application/json` |
| JSON-LD | `.jsonld` | `application/ld+json` |
| OpenSALT JSON (extra fields) | — | header `X-OpenSALT` (any value) selects the `opensalt` representation |
| HTML | `.html` | `text/html` |
| CSV | `.csv` | `text/csv` |
| NDJSON | — | `application/x-ndjson` |

If `Accept` and a path suffix are missing, the server answers **300 Multiple Choices** and lists alternates. Always send `Accept` or use a suffix.

Markdown responses use `Content-Type: text/markdown; charset=utf-8`. JSON responses advertise markdown with `Link: rel="alternate"` and an RFC 2295 `Alternates` header.

Examples:

```
GET /uri/p{documentIdentifier}.md
Accept: text/markdown

GET /uri/{itemIdentifier}.json
Accept: application/json
```

## Markdown shape

A markdown body is YAML front matter, then headings.

Front matter includes identity (`objectType`, `kind`, `identifier`, `uri`), the owning framework when there is one, `title`, freshness (`lastChangeDateTime`), and compact fields such as `itemType`, `humanCodingScheme`, `language`, and simple `extensions`. `objectType` is `Document`, `Item`, `Package`, `Association`, and similar names — not the CASE `CF` prefix. `kind` is the item kind (`credential`, `course`, `job`, `organization`, `assessment`, and others). `itemType` is the framework's own label when one is set.

Single-object fetches start at heading level 1 for the object. Package fetches (`/uri/p{id}.md`) use `#` for the framework, then sections such as About, Contents, Crosswalks, and Items. Empty sections are omitted.

Associations are grouped by relationship type. Incoming associations are labeled `(incoming)`. Targets link to `/uri/{identifier}`.

Credential, course, job, organization, and similar kinds add extra sections (criteria, course code, legal name, and so on). Nested extension objects appear as fenced JSON on a single-object fetch, not inside front matter.

Package markdown can be large. Request one item when you only need one statement.

## Caching

The same validators as CASE apply when the object has an update time: `ETag`, `Last-Modified`, and a short public cache lifetime. `304` is valid.

## Credentials and organizations

HTML and JSON-LD fetches of a credential item may render a credential view. HTML fetches of an organization item may render an organization page.
