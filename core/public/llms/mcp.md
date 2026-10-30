# MCP server

OpenSALT runs a Model Context Protocol (MCP) server for agents that prefer tool calls over raw HTTP. Point an MCP client at `/_mcp` on this origin (HTTP transport). The server identifies itself as `OpenSALT MCP Server`.

No authentication is required: the server is read-only and serves public content only — public documents, their items, and their associations. Private drafts and hidden mirrors are never returned.

Creating or changing frameworks is not available over MCP. Use [OpenAPI `/api/v1`](/llms/openapi.md) for writes.

## Tools

| Tool | What it does |
|------|--------------|
| `opensalt_list_documents` | List public documents. Options: `limit` (default 20, max 100), `offset`, `query` (matched against title, creator, publisher, and identifier). |
| `opensalt_get_document` | Fetch one public document by identifier. Markdown by default: front matter, About, top-level items, and a link to the full package. `format: "json"` returns structured fields. `includeItems: true` appends up to `itemLimit` (default 25, max 100) item summaries. |
| `opensalt_list_items` | List public items, optionally scoped to one document. Options: `limit` (default 20, max 100), `offset`, `documentIdentifier`, `query` (matched against statements, coding scheme, and identifier). |
| `opensalt_get_item` | Fetch one public item by identifier. Markdown by default: front matter, ancestor path, statement, kind sections, associations, and related items. `format: "json"` returns structured fields plus related items. `includeRelated` (default true) and `relatedLimit` (default 10, max 100) control the related-items section. |
| `opensalt_search_related_items` | Find public items related to a source item through the association graph in both directions, grouped per related item with relation counts and types. |

Out-of-range limits fall back to their defaults. List results include `limit`, `offset`, `count`, and the entries, ordered by most recent change.

The markdown returned by `opensalt_get_document` and `opensalt_get_item` follows the same shape as [`/uri/` markdown](/llms/uri.md): YAML front matter, then headings.

## Resources

| URI | Contents |
|-----|----------|
| `opensalt://documents` | Up to 50 document summaries |
| `opensalt://items/recent` | Up to 50 recently changed public items |

Document summaries include `identifier`, `uri`, `title`, `creator`, `adoption_status`, `language`, and `updated_at`. Item summaries include `identifier`, `uri`, `document_identifier`, `object_type`, `item_type`, `human_coding_scheme`, `abbreviated_statement`, `full_statement`, and `updated_at`. Fetch a single object for full details.

## Prompts

- `opensalt_usage_guide` — a starting prompt that suggests how to use the tools and what to include when reporting findings.

## Choosing between MCP and HTTP

- MCP tools: multi-step exploration from a client already connected to `/_mcp`.
- [`/uri/{identifier}.md`](/llms/uri.md): one-off fetches with no client session.
- [CASE](/llms/case-api.md): the public catalog and CASE 1.1 packages.
- [`/api/v1`](/llms/openapi.md): writes, and reads with authenticated catalog rules.
