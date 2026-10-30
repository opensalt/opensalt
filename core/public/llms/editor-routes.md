# Editor and UI routes

These paths serve the OpenSALT web application. Integrators should use [CASE](/llms/case-api.md), [URI lookup](/llms/uri.md), or [OpenAPI](/llms/openapi.md) instead.

| Path | What it is |
|------|------------|
| `/` | Web application home |
| `/login`, `/logout`, `/2fa` | Browser sign-in |
| `/account/tokens` | Where a person creates Bearer tokens |
| `/editor` and `/editor/{path}` | Vue framework editor (HTML shell) |
| `/framework/editor/…` | JSON used by that editor (items, associations, and similar). Session or Bearer on the main site; not a stable public contract |

Reading a framework in a browser often means `/editor/{documentIdentifier}`. Agents that need the competency text should request `/uri/p{documentIdentifier}.md` or `/uri/{itemIdentifier}.md`.
