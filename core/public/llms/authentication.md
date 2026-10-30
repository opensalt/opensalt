# Authentication

Many reads are anonymous. Writes, and some lists that include private drafts, need a user.

## API tokens

Any signed-in user can create tokens in the browser at `/account/tokens`. The token is shown when it is created. Send it on later requests:

```
Authorization: Bearer <token>
```

There is no anonymous token endpoint. A token acts as that user: the same organization and the same ability to read or change frameworks. Expired or unknown tokens fail authentication.

The token is accepted on the main site (including `/ims/case/` and `/uri/`) and on `/api/`.

Browser sessions also authenticate `/api/` requests. Agents should use Bearer tokens, not cookies.

## What is public

- `GET /ims/case/v1p1/…` and `GET /ims/case/v1p0/…` do not require a token.
- The CFDocument catalog hides private drafts and non-visible mirrors from anonymous callers. See [CASE HTTP API](/llms/case-api.md).
- `GET /uri/…` does not require a token for objects the server will resolve.

Direct CASE fetch by UUID is not the same filter as the catalog. Do not treat an unlisted UUID as secret if it has been shared.

Browser clients can call these prefixes cross-origin (CORS with wildcard origins): `GET /ims/case/…` and `GET /uri/…`, every `/api/v1/` method, and `GET /issuer-registry/…`. The `X-Total-Count` and `X-OpenSALT` response headers are exposed to that page.

## What needs a token

OpenAPI marks `/api/v1` document, package, item, and association operations with the Bearer scheme. An **invalid** token is rejected (`401`). Omitting the header does not by itself block every route.

Reading a document you already know the identifier for can succeed without a token. `GET /api/v1/documents` follows the same catalog rules as CASE: anonymous callers see public documents, not private drafts and not hidden mirrors.

Creating, changing, or deleting a framework needs a signed-in account:

| Action | Who |
|--------|-----|
| Create a document or import a package | A user with the **Editor** role. |
| Change items, associations, or the document, or delete it | A signed-in Editor who is allowed to change that framework. See below. |

Someone may change or delete a framework when all of the following are true:

- They are signed in and have the Editor role.
- The framework is not a mirrored copy.
- They created the framework, they were granted access to it, or they belong to the same organization as the framework.

| Status | Meaning |
|--------|---------|
| 401 | The token is invalid, or the request required a signed-in user and none was sent |
| 403 | The account is signed in but is not allowed to do that |
| 404 | Identifier not found |

`POST /api/v1/articulations/evaluate` requires a signed-in user. Unknown institution pairs are 404.

`GET /ims/case/v1p1/CFDocuments/{id}/related` can be called by anyone who knows the document identifier, including a signed-out caller.

## Logging in

Humans sign in at `/login`. Two-factor, when enabled for the account, continues at `/2fa`. Agents integrating with the registry should use a token from `/account/tokens` after a person has signed in, not scrape the login form.
