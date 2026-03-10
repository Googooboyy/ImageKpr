---
name: ImageKpr Auth and Global Folders
overview: Phases 7 and 8 from the main UI overhaul plan - Simple Auth (database-based) and Global Folders. To be implemented after Phases 1-6.
todos: []
isProject: false
---

# ImageKpr Auth and Global Folders (Phases 7 & 8)

**Prerequisites:** Phases 1-6 of the UI/UX overhaul must be completed first.

---

## Phase 7: Simple Auth (Database-Based)

Auth enables each user (family/friend) to have their own images and folders. Implement before global folders so both share user scoping.

1. **Users table** — Add to [database.sql](database.sql): `CREATE TABLE users (id INT AUTO_INCREMENT PRIMARY KEY, username VARCHAR(64) NOT NULL UNIQUE, password_hash VARCHAR(255) NOT NULL, created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP);`
2. **Session setup** — Ensure `session_start()` runs (e.g. in config or a bootstrap file included by all pages).
3. **login.php** — Simple login form; on POST, verify username/password with `password_verify()`, set `$_SESSION['user_id']` and `$_SESSION['username']`, redirect to index.
4. **register.php** — Self-registration form; insert into users with `password_hash()`. Redirect to login on success.
5. **logout.php** — `session_destroy()`, redirect to login.
6. **inc/auth.php** — Check `$_SESSION['user_id']`; if empty, send 401 or redirect to login. All API scripts `require` this at top.
7. **Scope APIs by user_id** — Add `WHERE user_id = :uid` to: images.php, tags.php, stats.php, upload.php, inbox.php, delete.php, delete_bulk.php, rename.php, rename_bulk.php, download_bulk.php.
8. **Upload sets user_id** — In upload.php and inbox import, set `user_id = $_SESSION['user_id']` on INSERT.
9. **Migrate orphan images** — One-time: `UPDATE images SET user_id = 1 WHERE user_id IS NULL` (run after first user created). Or: first user to log in gets ownership; script assigns orphans to them.
10. **Frontend auth handling** — On 401 from API, redirect to login.php. Add "Logged in as X" + logout link in header or banner.
11. **Protect index.php** — If no session, redirect to login (or show login form inline). Ensure index.php requires auth before rendering app.

**Order**: Create users table → login/register/logout pages → inc/auth.php → add require to each API → scope queries → migrate orphans → frontend redirect + logout link.

---

## Phase 8: Global Folders (Backend + Migration)

Depends on Phase 7; folders are scoped by user_id.

1. **Folder qty fix** — In [folders.js](folders.js) `removeFromFolder`, use `Number(x) !== Number(id)` (fixes localStorage behavior until migration).
2. **DB schema** — `folders` (id, user_id, name, created_at), `folder_images` (folder_id, image_id). Both with appropriate indexes.
3. **api/folders.php** — GET (list folders with counts for current user), POST (create folder), PATCH (add/remove images), DELETE (delete folder). All filtered by `$_SESSION['user_id']`.
4. **folders.js refactor** — Replace localStorage with API calls. Keep same API surface (`load`, `save`, `addToFolder`, `removeFromFolder`, etc.) so app.js needs minimal changes.
5. **Migration path** — On first load after deploy: if localStorage has folder data and user is logged in, offer "Import my folders" or auto-POST to folders API. Alternatively: export/import JSON as manual step.

---

## Files to Create/Modify

- **New**: `login.php`, `register.php`, `logout.php`
- **New**: `inc/auth.php`
- **New**: `api/folders.php` (Phase 8)
- **Modify**: [database.sql](database.sql), [index.php](index.php), all [api/*.php](api/), [folders.js](folders.js)
