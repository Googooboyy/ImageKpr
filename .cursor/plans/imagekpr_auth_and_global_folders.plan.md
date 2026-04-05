---
name: ImageKpr Auth and Global Folders
overview: Phases 7-9 — Google OAuth, email allowlist, gated app; admin quotas and purge; API-backed global folders; security guardrails including bulk request caps (ids, filenames, uploads per POST).
todos: []
isProject: false
---

# ImageKpr Auth, Admin, and Global Folders (Phases 7-9)

**Prerequisites:** Phases 1-6 of the UI/UX overhaul are completed and stable.

---

## Phase 7: Authentication

**Goals**

- **Inbox:** If the user is **not** logged in, they **do not see** inbox UI or data. [api/inbox.php](api/inbox.php), [api/inbox_preview.php](api/inbox_preview.php), and any inbox-related UI in [index.php](index.php) / [app.js](app.js) only run behind a valid session. Unauthenticated users get **401** from APIs and never load the full app shell (so no inbox strip/modal).
- **Full image URLs:** **No login** required for **direct GET** to published gallery files (paths stored in `images.url` / static `images/` tree). Link secrecy = access control for those bytes.
- **Everything else in the main app** (grid, upload, folders strip, stats, tags, etc.): **login required** — same as today’s plan: [login.php](login.php), Google OAuth (`auth/google/start.php`, `callback.php`, `logout.php`), [index.php](index.php) gated, [inc/auth.php](inc/auth.php), all listed JSON APIs scoped by `user_id`, [app.js](app.js) redirects to `login.php` on 401.
- **Email allowlist (gating):** After Google returns a verified identity in `**auth/google/callback.php`**, normalize the Google **email** (lowercase) and check it against the **allowlist** (see Phase 8). If **not** on the list: **do not** create a session, **do not** upsert a `users` row (or upsert a stub only if you want audit — default: no account). Redirect to [login.php](login.php) with a clear query or flash message, e.g. “Your account is not authorized to use this app.” **Bootstrap:** e.g. `ADMIN_GOOGLE_SUB` (or `BOOTSTRAP_ALLOWLIST_EMAILS` in config) may sign in once to open the admin UI and seed the allowlist, or ship a migration that inserts initial allowed emails.

**Implementation summary**

1. Google Cloud OAuth client; secrets in `config.php`.
2. DB: `users` (OAuth fields) + `images.user_id`; `**email_allowlist`** table (Phase 8 schema, enforced in Phase 7 callback).
3. Session after callback: **only if** email is allowlisted; then `user_id`, etc.
4. **Optional stricter revocation:** On each authenticated request, re-check allowlist (or re-check periodically); if email was removed from the list, destroy session and treat as logged out. Simpler MVP: check **only at OAuth callback** (user stays logged in until session expires if removed mid-session).
5. Require session on **all** app APIs including **inbox**; **do not** wrap static gallery file URLs in session checks.
6. Server-side: unauthenticated visitors never receive HTML that includes inbox markup + scripts that fetch inbox (prefer **redirect to login** from `index.php`).

**Migration:** One-time assignment for rows with NULL `user_id` (choose policy A or B as before).

**Deploy order (Phase 7):** DB migration → OAuth config → `login.php` + auth endpoints → `inc/auth.php` + gate `index.php` → protect all APIs (inbox + rest) → `app.js` 401 handling.

---

## Phase 8: Admin page

**Goals**

- **User limits:** Per-user storage quota (e.g. GB), optional global default in config. Enforced on **upload** and **inbox** paths using `SUM(images.size_bytes)` for that `user_id` (or cached column if needed later).
- **Remove / purge:**
  - **Delete user** (account row): define whether this **cascades** (delete their images from disk + DB, folder rows, memberships) or **blocks** until data is purged — document chosen behavior in implementation.
  - **Purge user’s images:** Delete DB rows + files on disk for that `user_id` only.
  - **Purge user’s folders:** Delete `folder_images` + `folders` for that `user_id` (images may remain unassigned or deleted per product rule — default: **delete folders and memberships only**, keep images unless admin chooses full image purge).

**Implementation summary**

1. **Schema:** `users.is_admin` (or `role`), `users.storage_quota_bytes` (nullable → default from config).
2. **Bootstrap admin:** e.g. `ADMIN_GOOGLE_SUB` in config matching Google `sub`, or one-time DB flag.
3. **Admin surface:** e.g. `admin/index.php` (and small `admin/admin.js` or server-rendered forms). **Every** admin API (e.g. `api/admin_users.php`, `api/admin_purge.php`) must verify `is_admin` on the server.
4. **UX:** Table of users (email, name, used bytes, quota, last login); edit quota; destructive actions with **confirmation** (typed phrase or second step) and clear labels (“Delete all images for this user”).
5. **Security:** CSRF tokens on state-changing admin actions, rate limiting, audit log optional but recommended for purges.

**Depends on:** Phase 7 (sessions, `user_id`, OAuth identity).

---

## Phase 9: Global folders

**Goals**

- **No longer using JSON** for folder persistence: remove **localStorage**-backed folder map from [folders.js](folders.js); folders and memberships live in **MySQL** only.
- **No import/export necessary:** Remove UX and code paths that **export** folders to JSON or **import** from JSON / “migrate from localStorage” prompts — first load after deploy uses **empty API state** or whatever is already in DB for that user.

**Implementation summary**

1. **Schema:** `folders` (`id`, `user_id`, `name`, …), `folder_images` (`folder_id`, `image_id`, …), unique constraints per user where needed.
2. **API:** [api/folders.php](api/folders.php) — GET list + counts, POST create, PATCH add/remove membership, DELETE folder; all filtered by session `user_id`.
3. **Frontend:** Refactor [folders.js](folders.js) to call the API; keep public function names stable for [app.js](app.js) callers where possible.
4. **Remove:** localStorage read/write for folders, any “Export folders” / “Import folders” / “Migrate local folders” modals or buttons.

**Depends on:** Phase 7 (authenticated `user_id`). Phase 8 is independent unless you want admin to “purge folders” via the same DB tables (Phase 8 can ship before or after Phase 9; if after, admin purge already targets real tables).

---

## What Is Required

- Google Cloud OAuth app, HTTPS in production, PHP session hardening.
- MySQL migrations for `users`, `images.user_id`, folder tables, admin/quota columns.
- Clear rules for **purge** and **delete user** (cascade vs manual steps).

---

## Limits and Tradeoffs

- **Direct gallery URLs** remain **public-by-link** (Phase 7).
- **Any Google account** can sign up unless you add an allowlist later.
- **Admin powers** are destructive; protect routes and confirm dangerous actions.
- **Phase 9** abandons local folder JSON; users who relied only on localStorage lose that mapping unless you run a **one-time** migration script separately (you said import/export not necessary — so default is **no** migration prompt).

---

## Suggested Future Hardening (Optional)

- Email allowlist for invite-only signup.
- Login rate limiting; audit log for admin purges.
- CSRF on all state-changing endpoints (including main app, not only admin).

---

## Files to Create / Modify (high level)

**Phase 7:** [login.php](login.php), `auth/google/start.php`, `auth/google/callback.php`, `auth/logout.php`, [inc/auth.php](inc/auth.php), [database.sql](database.sql), [index.php](index.php), protected [api/*.php](api/) including [api/inbox.php](api/inbox.php) and [api/inbox_preview.php](api/inbox_preview.php), [app.js](app.js).

**Phase 8:** `admin/`*, `api/admin_*.php` (or equivalent), [database.sql](database.sql) (`is_admin`, `storage_quota_bytes`), [api/upload.php](api/upload.php) + inbox upload paths for quota checks.

**Phase 9:** [api/folders.php](api/folders.php), [database.sql](database.sql) (folder tables), [folders.js](folders.js), [app.js](app.js) / [index.php](index.php) only if folder UI wiring changes.