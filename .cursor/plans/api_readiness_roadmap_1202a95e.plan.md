---
name: API Readiness Roadmap
overview: A phased roadmap to make ImageKpr's existing JSON endpoints accessible to third-party developers (server-to-server, browser cross-origin, and mobile clients), with token-based auth, CORS, rate limiting, versioning, and manual API docs.
todos:
  - id: phase1-db
    content: "Phase 1a: Create `api_tokens` table schema and migration"
    status: pending
  - id: phase1-auth
    content: "Phase 1b: Build `inc/api_auth.php` middleware (Bearer token lookup + session fallback)"
    status: pending
  - id: phase1-ui
    content: "Phase 1c: Add token management UI (list, create, revoke)"
    status: pending
  - id: phase1-endpoints
    content: "Phase 1d: Build token CRUD endpoints (`api/tokens.php`)"
    status: pending
  - id: phase2-cors
    content: "Phase 2a: Build `inc/cors.php` middleware with preflight handling"
    status: pending
  - id: phase2-ratelimit
    content: "Phase 2b: Extend rate limiting to all `api/*` endpoints (per-token + per-IP)"
    status: pending
  - id: phase2-responses
    content: "Phase 2c: Standardize JSON response envelope across all 16 endpoints"
    status: pending
  - id: phase2-image-url-contract
    content: "Phase 2d: Define canonical image URL contract (opaque URL semantics + stable fields)"
    status: pending
  - id: phase3-versioning
    content: "Phase 3a: Create `api/v1/` versioned directory with wrapper files"
    status: pending
  - id: phase3-bootstrap
    content: "Phase 3b: Create shared `api/bootstrap.php` (auth + CORS + rate limit + headers)"
    status: pending
  - id: phase4-docs
    content: "Phase 4: Write `docs/API.md` markdown reference with auth guide, endpoint reference, and examples"
    status: pending
  - id: phase4-url-migration-docs
    content: "Phase 4b: Document per-user image path migration and legacy URL compatibility policy"
    status: pending
isProject: false
---

# API Readiness Roadmap for ImageKpr

## Current State

ImageKpr already has **16 JSON endpoints** under `api/` that power the built-in UI. These cover images, tags, folders, uploads, bulk operations, inbox, and stats. However, they are **not consumable by third parties** because:

- **Auth is session-only** — requires a browser cookie from Google OAuth login (`inc/auth.php`)
- **No CORS headers** — cross-origin requests from other domains are blocked
- **No machine-to-machine auth** — no API keys or tokens
- **No API versioning** — breaking changes would break all consumers immediately
- **Rate limiting only on auth routes** — `api/`* endpoints are unprotected (`inc/rate_limit.php`)
- **Inconsistent response format** — some endpoints return `{ error }`, others `{ success, error }`
- **No API documentation** — no reference for external developers

---

## New Constraint: Per-User Image Paths

The app now stores image files in **per-user folders** and persists URLs in the form `IMAGES_URL/{user_id}/{filename}` (instead of legacy flat paths):

- Path helpers are centralized in `inc/images_path.php`
- Upload/import/rename flows write per-user URLs (`api/upload.php`, `api/inbox.php`, `api/rename.php`, `api/rename_bulk.php`, `scripts/sync_images.php`)
- There is migration tooling for old files (`scripts/migrate_to_user_folders.php`)
- Server-side file operations include legacy fallback lookup, but old public flat HTTP links may still need a compatibility decision

This does not change the token-auth approach, but it does require an explicit API URL contract so third-party clients do not construct paths manually.

---

## Recommended Auth Model: Personal Access Tokens (PATs)

Since each third-party app acts on behalf of a **single user**, the simplest and most effective approach is **Personal Access Tokens** (like GitHub or GitLab PATs):

- Each user generates one or more tokens from their account settings
- Tokens are sent as `Authorization: Bearer <token>` on every request
- Each token is tied to the user who created it and grants access to **only their data**
- No OAuth 2.0 dance needed — much simpler for both you and developers
- Works identically for servers, browsers, and mobile apps

```
Third-party app                     ImageKpr
     |                                  |
     |-- GET /api/v1/images.php ------->|
     |   Authorization: Bearer abc123   |
     |                                  |-- Look up token in DB
     |                                  |-- Map to user_id
     |                                  |-- Execute as that user
     |<--- 200 JSON response -----------|
```

---

## Phase 1: Authentication and Token Infrastructure

**Goal:** Let third-party apps authenticate without a browser session.

### 1a. Database — `api_tokens` table

New table in `database.sql` (and a migration file):

```sql
CREATE TABLE api_tokens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token_hash VARCHAR(64) NOT NULL,       -- SHA-256 of the raw token
    name VARCHAR(100) NOT NULL,            -- user-given label, e.g. "My CMS integration"
    scopes JSON DEFAULT NULL,              -- future: granular permissions
    last_used_at DATETIME DEFAULT NULL,
    expires_at DATETIME DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY (token_hash),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

- Store only the **hash** of the token (never the plaintext)
- Show the raw token **once** at creation, then never again
- Optional `scopes` column for future granular permissions (read-only, upload-only, etc.)
- Optional `expires_at` for time-limited tokens

### 1b. Auth middleware — `inc/api_auth.php`

New file that sits alongside the existing `inc/auth.php`:

- Check for `Authorization: Bearer <token>` header
- Hash the token, look up in `api_tokens`
- If found and not expired: set `$_SESSION['user_id']` (or equivalent request-scoped variable) so all existing endpoint code works unchanged
- If not found: fall back to existing session auth (so the built-in UI keeps working)
- Update `last_used_at`
- Return 401 with JSON if neither token nor session is valid

### 1c. Token management UI

Add a "API Tokens" section to the user's account area (or a new page):

- List active tokens (name, created date, last used, expiry)
- Create new token (show raw token once)
- Revoke/delete tokens

### 1d. Token management endpoints

- `POST /api/tokens.php` — create token (returns raw token once)
- `GET /api/tokens.php` — list user's tokens (no raw values)
- `DELETE /api/tokens.php?id=N` — revoke a token

---

## Phase 2: CORS and Request Hardening

**Goal:** Allow cross-origin browser apps and mobile apps to call the API safely.

### 2a. CORS middleware — `inc/cors.php`

New file included at the top of each `api/*.php` endpoint (or via a shared `api/bootstrap.php`):

- For **token-authenticated requests**: allow any origin (the token itself is the credential)
- For **session-authenticated requests**: restrict to same-origin (current behavior)
- Handle `OPTIONS` preflight requests
- Set headers: `Access-Control-Allow-Origin`, `Access-Control-Allow-Headers` (Authorization, Content-Type), `Access-Control-Allow-Methods`

### 2b. Extend rate limiting to all API endpoints

Extend the existing `inc/rate_limit.php` to cover `api/`*:

- Rate limit by **token** (not just IP) to prevent per-token abuse
- Suggested defaults: 60 requests/minute for reads, 30/minute for writes
- Return `429 Too Many Requests` with `Retry-After` header
- Configurable via `app_settings` table

### 2c. Standardize JSON response format

Audit all 16 endpoints and ensure a consistent envelope:

```json
{
  "success": true,
  "data": { ... },
  "meta": { "page": 1, "per_page": 50, "total": 230 }
}
```

Error responses:

```json
{
  "success": false,
  "error": { "code": "not_found", "message": "Image not found" }
}
```

Files to audit: `api/images.php`, `api/stats.php`, `api/whoami.php`, `api/upload.php`, `api/delete.php`, `api/delete_bulk.php`, `api/tags.php`, `api/rename.php`, `api/rename_bulk.php`, `api/download_bulk.php`, `api/folders.php`, `api/inbox.php`, `api/inbox_preview.php`, `api/check_duplicates.php`.

### 2d. Define canonical image URL contract

Lock down how image locations are represented in API responses:

- Treat returned image URLs as **opaque** values (clients must not build `/images/...` paths themselves)
- Keep a stable field contract in v1:
  - `url` (canonical public image URL)
  - optional explicit aliases (e.g. `image_url`) if needed for SDK clarity
  - `filename` as metadata only, not URL-construction input
- Ensure all image-returning endpoints follow the same contract (`images.php`, `upload.php`, `inbox.php`, rename flows)
- Decide and implement legacy flat URL compatibility strategy during migration window (rewrite/symlink/redirect vs no support)

---

## Phase 3: API Versioning

**Goal:** Allow future breaking changes without breaking existing consumers.

### 3a. URL versioning

Create a versioned directory structure:

```
api/
  v1/
    images.php      (symlink or include wrapper to ../images.php)
    upload.php
    ...
```

- `api/v1/*.php` files are thin wrappers that include the real logic
- Old `api/*.php` paths continue to work (treated as "latest" or redirect to v1)
- When v2 is needed later, v1 stays frozen

### 3b. Shared bootstrap

Create `api/bootstrap.php` that every versioned endpoint includes:

- Loads `config.php`
- Runs `inc/api_auth.php` (token or session auth)
- Runs `inc/cors.php`
- Runs `inc/rate_limit.php`
- Sets JSON content type and security headers

This replaces the scattered `require` statements at the top of each endpoint.

---

## Phase 4: Documentation and Developer Experience

**Goal:** Give third-party developers a clear reference.

### 4a. Markdown API reference

Create `docs/API.md` with:

- Authentication guide (how to get and use a PAT)
- Endpoint reference (method, URL, parameters, response shape, error codes)
- Rate limit policy
- Example requests (curl, JavaScript fetch, Python requests)
- Changelog section for version history

### 4b. URL migration and compatibility docs

Document the image-path transition and integration rules:

- Explain that image URLs are now per-user (`/images/{user_id}/{filename}` pattern may evolve; clients treat URL as opaque)
- Provide migration guidance for integrators that cached old flat URLs
- Publish the legacy URL compatibility policy and timeline (if any)
- Add examples for server, browser, and mobile consumers showing URL usage without path concatenation

### 4c. Error code catalog

Define machine-readable error codes (`unauthorized`, `rate_limited`, `invalid_input`, `not_found`, `quota_exceeded`, etc.) used consistently across all endpoints.

---

## Phase 5 (Future): Advanced Features

Not needed now, but worth planning for:

- **Scoped tokens** — restrict a token to read-only, upload-only, specific folders, etc. (the `scopes` JSON column is already in the schema)
- **Webhooks** — notify third-party apps when images are uploaded/deleted/tagged
- **OAuth 2.0** — if you ever want a "Sign in with ImageKpr" flow for third-party apps where the *end user* grants permission (not the developer themselves)
- **OpenAPI spec** — auto-generate interactive docs from a spec file

---

## Effort Estimate


| Phase   | Scope                                         | Rough Effort       |
| ------- | --------------------------------------------- | ------------------ |
| Phase 1 | Token auth + management UI                    | Medium (core work) |
| Phase 2 | CORS + rate limits + response standardization | Medium             |
| Phase 3 | Versioning + bootstrap                        | Small              |
| Phase 4 | Markdown docs                                 | Small-Medium       |


Phases 1 and 2 are the critical ones — they unlock third-party access. Phases 3 and 4 are important for maintainability and developer experience but can follow shortly after.