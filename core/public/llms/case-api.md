# CASE HTTP API

OpenSALT serves 1EdTech CASE on this host. Use **v1.1** unless a client specifically needs v1.0. The two versions share the same path shape; only the version segment changes.

| Version | Base path |
|---------|-----------|
| CASE 1.1 | `/ims/case/v1p1` |
| CASE 1.0 | `/ims/case/v1p0` |

These routes are read-only (`GET`). Paths are the CASE service paths: no `.json` suffix. The response body is JSON (`Content-Type: application/json` on package responses). Send `Accept: application/json`. This host also accepts an optional `.{format}` suffix; call the unsuffixed paths from the [CASE 1.1 OpenAPI definition](https://purl.imsglobal.org/spec/case/v1p1/schema/openapi/imscasev1p1_openapi2_v1p0.json).

CORS is enabled for `/ims/case/` and `/uri/` (and for `/api/v1/` and `/issuer-registry/`; see [authentication](/llms/authentication.md)).

## Start here

1. List frameworks: `GET /ims/case/v1p1/CFDocuments`
2. Download one framework: `GET /ims/case/v1p1/CFPackages/{documentIdentifier}`
3. Fetch one item: `GET /ims/case/v1p1/CFItems/{itemIdentifier}`

`{documentIdentifier}` and `{itemIdentifier}` are the object's UUID, not a human code.

## Catalog

`GET /ims/case/v1p1/CFDocuments`

Query parameters:

| Name | Default | Meaning |
|------|---------|---------|
| `limit` | `100` | Page size after filtering |
| `offset` | `0` | Items to skip |
| `sort` | unset | Sort field passed to the document query. When `sort=updatedAt`, responses include update timestamps |
| `orderBy` | `asc` | `asc` or `desc` |

The response body is `{ "CFDocuments": [ ... ] }`. `X-Total-Count` is the number of documents in the filtered catalog before `limit`/`offset`.

The catalog leaves out private drafts unless the caller is signed in and either belongs to that framework's organization or is allowed to change the framework. It also leaves out mirrored frameworks that are not marked visible, unless the caller has the Editor role or belongs to that framework's organization. A document that is absent from this list can still be fetched by identifier if the caller already knows the UUID.

## Package and objects

| Resource | Path |
|----------|------|
| Full package (document, items, associations, definitions) | `/ims/case/v1p1/CFPackages/{id}` |
| Document | `/ims/case/v1p1/CFDocuments/{id}` |
| Item | `/ims/case/v1p1/CFItems/{id}` |
| Associations for an item | `/ims/case/v1p1/CFItemAssociations/{id}` (this host also accepts `/ims/case/v1p1/CFItems/{id}/associations`) |
| One association | `/ims/case/v1p1/CFAssociations/{id}` |
| Association grouping | `/ims/case/v1p1/CFAssociationGroupings/{id}` |
| License | `/ims/case/v1p1/CFLicenses/{id}` |
| Rubric, criterion, criterion level | `/ims/case/v1p1/CFRubrics/{id}`, `/ims/case/v1p1/CFRubricCriteria/{id}`, `/ims/case/v1p1/CFRubricCriterionLevels/{id}` |
| Concept, item type, subject | `/ims/case/v1p1/CFConcepts/{id}`, `/ims/case/v1p1/CFItemTypes/{id}`, `/ims/case/v1p1/CFSubjects/{id}` |

`GET /ims/case/v1p1/CFPackages/{id}?download=1` sets `Content-Disposition: attachment` with a filename `opensalt-framework-{id}.json`.

Item association lists omit an association when it points at another document that is a private draft and that draft would not appear in the caller's catalog. Associations inside the item's own document are kept.

## Related documents

`GET /ims/case/v1p1/CFDocuments/{documentIdentifier}/related`

Anyone who knows the document identifier can call this, including a signed-out caller. It returns a JSON array of related documents (OpenSALT document view), not a CASE collection wrapper. Changing a framework is separate. See [authentication](/llms/authentication.md).

## Caching

Successful CASE GETs send `ETag`, `Last-Modified`, and a public `Cache-Control` max-age of 60 seconds. Repeat requests with `If-None-Match` or `If-Modified-Since` can receive `304 Not Modified`. Responses vary on `Accept` and `Accept-Encoding`.

## Errors

Requests under `/ims/case/v1p0/` and `/ims/case/v1p1/` that fail are returned as API errors for those prefixes. Unknown identifiers are not found (`404`).

## What CASE on this host is not

- It does not create or update frameworks. Use [OpenAPI `/api/v1`](/llms/openapi.md).
- It is not the richest representation for language models. Use [markdown via `/uri/`](/llms/uri.md).
- CASE 1.0 package JSON from `/uri/` (when generated as a package) and CASE 1.1 package JSON from `/ims/case/v1p1/CFPackages/` are different serializations. Prefer v1.1 paths for new clients.
