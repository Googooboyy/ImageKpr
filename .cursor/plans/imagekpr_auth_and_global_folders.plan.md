---
name: ImageKpr Auth and Global Folders
overview: Phases 7–13 — Google OAuth; admin and config as sequential Phases 8–12; API-backed global folders as Phase 13; security guardrails including bulk request caps.
todos: []
isProject: false
---

# ImageKpr Auth, Admin, and Global Folders (Phases 7–13)

**Prerequisites:** Phases 1-6 of the UI/UX overhaul are completed and stable.

**Roadmap (order):** Phase 7 → **8** (admin foundation) → **9** (dashboard) → **10** (bulk quota + enforcement) → **11** (bulk purge) → **12** (config + runtime settings) → **13** (global folders).

---

## Phase 7: Authentication

**Goals**

- **Inbox:** If the user is **not** logged in, they **do not see** inbox UI or data. [api/inbox.php](api/inbox.php), [api/inbox_preview.php](api/inbox_preview.php), and any inbox-related UI in [index.php](index.php) / [app.js](app.js) only run behind a valid session. Unauthenticated users get **401** from APIs and never load the full app shell (so no inbox strip/modal).
- **Full image URLs:** **No login** required for **direct GET** to published gallery files (paths stored in `images.url` / static `images/` tree). Link secrecy = access control for those bytes.
- **Everything else in the main app** (grid, upload, folders strip, stats, tags, etc.): **login required** — same as today’s plan: [login.php](login.php), Google OAuth (`auth/google/start.php`, `callback.php`, `logout.php`), [index.php](index.php) gated, [inc/auth.php](inc/auth.php), all listed JSON APIs scoped by `user_id`, [app.js](app.js) redirects to `login.php` on 401.
- **Email allowlist (gating):** After Google returns a verified identity in `**auth/google/callback.php`**, normalize the Google **email** (lowercase) and check it against the **allowlist** (managed in DB; **admin UI** in Phase **12**). If **not** on the list: **do not** create a session, **do not** upsert a `users` row (or upsert a stub only if you want audit — default: no account). Redirect to [login.php](login.php) with a clear query or flash message, e.g. “Your account is not authorized to use this app.” **Bootstrap:** e.g. `ADMIN_GOOGLE_SUB` (or `BOOTSTRAP_ALLOWLIST_EMAILS` in config) may sign in once to open the admin UI and seed the allowlist, or ship a migration that inserts initial allowed emails.

**Implementation summary**

1. Google Cloud OAuth client; secrets in `config.php`.
2. DB: `users` (OAuth fields) + `images.user_id`; `**email_allowlist`** table (enforced in Phase 7 callback; CRUD UI in Phase 12).
3. Session after callback: **only if** email is allowlisted; then `user_id`, etc.
4. **Optional stricter revocation:** On each authenticated request, re-check allowlist (or re-check periodically); if email was removed from the list, destroy session and treat as logged out. Simpler MVP: check **only at OAuth callback** (user stays logged in until session expires if removed mid-session).
5. Require session on **all** app APIs including **inbox**; **do not** wrap static gallery file URLs in session checks.
6. Server-side: unauthenticated visitors never receive HTML that includes inbox markup + scripts that fetch inbox (prefer **redirect to login** from `index.php`).

**Migration:** One-time assignment for rows with NULL `user_id` (choose policy A or B as before).

**Deploy order (Phase 7):** DB migration → OAuth config → `login.php` + auth endpoints → `inc/auth.php` + gate `index.php` → protect all APIs (inbox + rest) → `app.js` 401 handling.

---

## Phase 8: Admin foundation & shell

- **Migrations + [database.sql](database.sql):** `users.storage_quota_bytes` (nullable); `app_settings` (key/value); append-only **admin audit** table (actor `user_id`, action, JSON/metadata, `created_at`).
- `**inc/`:** CSRF issue/verify helpers; `**imagekpr_require_admin_html`** / `**imagekpr_require_admin_api**` — require session **and** verify `is_admin` from DB (not session alone).
- `**admin/index.php`:** Super-admin-only shell (shared layout, nav: Dashboard | Config); no destructive workflows yet (placeholders OK).
- **Audit:** small helper to append audit rows (used from Phase 9 onward).

**Depends on:** Phase 7.

---

## Phase 9: Dashboard — stats, user table, single-user quota

- **Quick stats:** Total storage, count at/over quota, user count, largest users (as agreed in product spec).
- **User table:** Sortable/searchable (email/name), columns: used bytes, quota, last login, admin flag; **PHP SSR** + minimal JS for toasts if needed.
- **Single-user quota edit:** POST with CSRF; **audit** each change.

**Depends on:** Phase 8.

---

## Phase 10: Bulk quota + quota enforcement

- **Bulk quota** UI/API (multi-select or ID list + CSRF + audit).
- **Enforcement:** [api/upload.php](api/upload.php) and inbox **import** in [api/inbox.php](api/inbox.php) reject when `SUM(images.size_bytes)` for that `user_id` would exceed effective quota (per-user `storage_quota_bytes` or global default from config / `app_settings` once Phase 12 exists).

**Depends on:** Phase 9.

---

## Phase 11: Bulk gallery purge

- **Bulk purge** = **gallery only:** delete selected users’ `images` rows + files under `IMAGES_DIR`; **does not** delete `users`, **does not** change `email_allowlist`, **does not** touch shared inbox files — **document in UI**.
- Strong confirmation (e.g. typed phrase); CSRF; **audit** each run.

**Depends on:** Phase 10 (prefer after bulk quota + enforcement are in place).

---

## Phase 12: Config page & runtime settings

- **Allowlist CRUD** in admin UI (normalize email lowercase).
- `**app_settings` UI:** Global default quota; **share NULL `user_id` rows** (replaces/supersedes `IMAGEKPR_SHARE_NULL_USER_ROWS` in [api/images.php](api/images.php), [api/stats.php](api/stats.php)); **maintenance / read-only** flag + optional banner message; **request/bulk limits** (values currently in [inc/request_limits.php](inc/request_limits.php)) stored in DB and read at runtime.
- **Maintenance enforcement:** All **mutating** main-app `api/*.php` endpoints reject when maintenance is on; **GET**/downloads allowed; **admin** routes exempt. [index.php](index.php) / [app.js](app.js): banner + disable/hide mutation controls; **server is source of truth**.
- **Audit** every settings change.

**Depends on:** Phase 8. (Work can overlap Phases 9–11 in development, but **ship Phase 12 last** or in lockstep with a final pass over all mutating APIs for maintenance and share-null wiring.)

---

### Agreed product spec (Phases 8–12)

Solo-owner operator; north star = **storage & quotas**. **PHP server-rendered** admin + minimal JS; **toasts** for feedback. **Single super-admin** (`is_admin` / `ADMIN_GOOGLE_SUB`); **CSRF** on all state-changing admin actions; **append-only DB audit** for config and purges.

**Bulk purge semantics (v1):** See Phase **11** — gallery only; shared inbox excluded.

**Phase 13 folders:** **Separate** admin action later — “purge folders” vs “purge images” — **not** bundled into bulk image purge. Maintenance (Phase 12): no folder mutations once Phase 13 exists.

---

## Phase 13: Global folders

**Goals**

- **No longer using JSON** for folder persistence: remove **localStorage**-backed folder map from [folders.js](folders.js); folders and memberships live in **MySQL** only.
- **No import/export necessary:** Remove UX and code paths that **export** folders to JSON or **import** from JSON / “migrate from localStorage” prompts — first load after deploy uses **empty API state** or whatever is already in DB for that user.

**Implementation summary**

1. **Schema:** `folders` (`id`, `user_id`, `name`, …), `folder_images` (`folder_id`, `image_id`, …), unique constraints per user where needed.
2. **API:** [api/folders.php](api/folders.php) — GET list + counts, POST create, PATCH add/remove membership, DELETE folder; all filtered by session `user_id`.
3. **Frontend:** Refactor [folders.js](folders.js) to call the API; keep public function names stable for [app.js](app.js) callers where possible.
4. **Remove:** localStorage read/write for folders, any “Export folders” / “Import folders” / “Migrate local folders” modals or buttons.
5. **Admin (follow-up):** Optional **purge user folders** action in admin UI targets these tables — separate from gallery purge (Phase 11).

**Depends on:** Phase 7 (authenticated `user_id`). Phases **8–12** can complete before or after Phase 13; if Phase 13 ships first, add maintenance checks to folder mutating APIs when Phase 12 lands.

---

## What Is Required

- Google Cloud OAuth app, HTTPS in production, PHP session hardening.
- MySQL migrations for `users`, `images.user_id`, Phase 8 settings/audit/quota columns, folder tables (Phase 13).
- Clear rules for **purge** (Phase 11) vs **delete user** / offboarding (out of scope for bulk purge v1).

---

## Limits and Tradeoffs

- **Direct gallery URLs** remain **public-by-link** (Phase 7).
- **Any Google account** can sign up unless you add an allowlist later.
- **Admin powers** are destructive; protect routes and confirm dangerous actions.
- **Phase 13** abandons local folder JSON; users who relied only on localStorage lose that mapping unless you run a **one-time** migration script separately (you said import/export not necessary — so default is **no** migration prompt).

---

## Suggested Future Hardening (Optional)

- Email allowlist for invite-only signup.
- Login rate limiting; expand audit beyond admin.
- CSRF on all state-changing endpoints (including main app, not only admin).

---

## Files to Create / Modify (high level)

**Phase 7:** [login.php](login.php), `auth/google/start.php`, `auth/google/callback.php`, `auth/logout.php`, [inc/auth.php](inc/auth.php), [database.sql](database.sql), [index.php](index.php), protected [api/*.php](api/) including [api/inbox.php](api/inbox.php) and [api/inbox_preview.php](api/inbox_preview.php), [app.js](app.js).

**Phases 8–12:** `admin/`*, `api/admin_*.php` (or equivalent), [database.sql](database.sql) + migrations; [api/upload.php](api/upload.php), [api/inbox.php](api/inbox.php) (quota Phases 10–12, maintenance Phase 12); [api/images.php](api/images.php), [api/stats.php](api/stats.php), [inc/request_limits.php](inc/request_limits.php) (Phase 12); [index.php](index.php) / [app.js](app.js) (maintenance banner, Phase 12).

**Phase 13:** [api/folders.php](api/folders.php), [database.sql](database.sql) (folder tables), [folders.js](folders.js), [app.js](app.js) / [index.php](index.php) only if folder UI wiring changes.