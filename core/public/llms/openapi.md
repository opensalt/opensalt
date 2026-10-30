# OpenAPI (`/api/v1`)

Machine-readable documentation for the authenticated registry API:

| Resource | URL |
|----------|-----|
| OpenAPI document | `/api/v1/doc.json` |
| Swagger UI | `/api/v1/doc` |

The generated spec's documented paths are `/api/v1/documents`, `/api/v1/packages`, and `/api/v1/articulations` (and their subpaths). It does **not** list `/ims/case/…`. For those, use [CASE HTTP API](/llms/case-api.md). Security scheme name in the spec is `Bearer`. Writes need a valid token for an account that is allowed to make that change. Some reads allow anonymous access. Who may create or change a framework is described in [authentication](/llms/authentication.md).

CASE 1.0 and 1.1 HTML reference pages (separate from `/api/v1/doc.json`):

- `/api/doc` redirects to the CASE 1.0 page
- `/api/doc/case-1-0`
- `/api/doc/case-1-1`

## Documents

| Method | Path | Who can call |
|--------|------|-------------|
| `GET` | `/api/v1/documents` | Anyone. Paginated, filterable list. Private drafts and hidden mirrors follow the catalog rules in [authentication](/llms/authentication.md). |
| `POST` | `/api/v1/documents` | A signed-in user with the Editor role. Creates a document. |
| `GET` | `/api/v1/documents/{documentIdentifier}` | Anyone who knows the identifier. |
| `PUT` | `/api/v1/documents/{documentIdentifier}` | A signed-in Editor who may change that framework. Replaces the document. |

List queries use `page` and `filter` objects (see the OpenAPI document for fields). This list is the application API, not `CFDocuments`.

## Packages

CASE package JSON, not the markdown package.

| Method | Path | Who can call |
|--------|------|-------------|
| `GET` | `/api/v1/packages/{documentIdentifier}` | Anyone who knows the identifier. Package JSON, same payload family as `/uri/p{id}.json`. |
| `POST` | `/api/v1/packages` | A signed-in user with the Editor role. Imports a CFPackage. |
| `PUT` | `/api/v1/packages/{documentIdentifier}` | A signed-in Editor who may change that framework. Replaces it from a CFPackage. |
| `DELETE` | `/api/v1/packages/{documentIdentifier}` | A signed-in Editor who may change that framework. Deletes the document. |

## Items and associations

Nested under a package document id. Reading is open to anyone who knows the identifiers. Creating, replacing, or deleting an item or association requires a signed-in Editor who may change that framework.

| Method | Path |
|--------|------|
| `POST` | `/api/v1/packages/{documentIdentifier}/items` |
| `GET`, `PUT`, `DELETE` | `/api/v1/packages/{documentIdentifier}/items/{itemIdentifier}` |
| `POST` | `/api/v1/packages/{documentIdentifier}/associations` |
| `GET`, `PUT`, `DELETE` | `/api/v1/packages/{documentIdentifier}/associations/{associationIdentifier}` |

Request and response fields are in `/api/v1/doc.json`. Do not invent properties that the schema does not list.

## Articulations

`POST /api/v1/articulations/evaluate`

Authenticated evaluation of sending courses against articulation rules stored as CASE packages for a sending institution and a receiving institution. The service picks the articulation package for that pair (latest academic year, then highest document id). Results include satisfied and partial matches only. Request and response schemas are `EvaluateRequest` and `EvaluateResponse` in the OpenAPI document.

Institution identifiers may be `identifier`, `uri`, or extension keys on the organization item. Course identifiers may be `identifier`, `uri`, `courseCode`, or extension keys on the course item. Courses are matched only against course items linked from that articulation package, not a full catalog.
