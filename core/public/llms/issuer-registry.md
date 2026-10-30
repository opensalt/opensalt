# Issuer registry

OpenSALT exposes public issuer-registry endpoints under `/issuer-registry`. They publish OpenID Federation entity statements and issuer identifiers derived from public organization and identifier records. No Bearer token is required for these endpoints.

## Machine-readable endpoints

| Path | Returns |
|------|---------|
| `/issuer-registry/.well-known/openid-federation` | OpenSALT's signed federation entity statement. The response type is `application/entity-statement+jwt`. |
| `/issuer-registry/fetch?sub={issuerDID}` | A signed entity statement for the issuer identified by `sub`. The issuer must be known to this registry. |
| `/issuer-registry/subordinate_listing` | A JSON array of issuer identifier URIs known to this registry. |

The `/issuer-registry/` prefix redirects to the federation entity-statement endpoint. Unknown issuer identifiers return `404`.

The entity statements include issuer organization metadata and validity timestamps. The root statement also advertises the federation fetch and subordinate-listing endpoints and includes the registry's public signing key material.

## Human-readable information

`/issuer-registry/governance` serves the registry governance document as HTML.

## Cross-origin access

The issuer-registry GET endpoints allow cross-origin browser reads. Clients should use the response media types rather than assuming every response is JSON.

For the CASE content and organization records behind issuer entries, use [CASE](/llms/case-api.md), [URI lookup](/llms/uri.md), or the [MCP server](/llms/mcp.md).
