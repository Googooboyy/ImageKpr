# ImageKpr

A LAMP (Linux, Apache, MySQL, PHP) media library for teams: **Google sign-in**, per-account **folders and tags**, **copyable file URLs**, bulk actions, optional **FTP inbox import**, and **curated share links** (shared dashboards). The front end is **vanilla JavaScript** (no framework, no build step).

Today the product spans **images** plus **short MP4 uploads for paid accounts**, **storage quotas**, **upload tiers** (up to 500 MB/file on the top SaaS preset), an **Admin** area, and marketing/support pages. Billing automation (Stripe) and third-party API tokens are **roadmapped**—see `.cursor/plans/` (summary below).

## Features

- **Google OAuth** sign-in; **email allowlist** with optional **enforcement** (when enforcement is off, successful sign-ins can be auto-added so you can tighten access later). Public **early access** request flow on the landing page.
- **Main grid** with lazy loading, search, sort (filename, tags, date, size, random), folder filter, tag filters, selection strip, and **bulk** delete / ZIP / tags / folder / rename.
- **Per-user file storage** under `images/{user_id}/…` with matching public URLs (`IMAGES_URL/{user_id}/filename`).
- **Upload** respects per-user **upload tier** and **storage quota**; client reads limits from `api/whoami.php`. Images may be **client-resized** before upload (max width 1920px path in the UI); supported formats are driven server-side (JPEG, PNG, GIF, WebP, and **MP4** for **paid** users when enabled).
- **Folders** stored in MySQL (`api/folders.php`); drag thumbnails to folders.
- **Modal** viewer: copy URL, download, delete, tags; **slideshow** with timer, transitions, letterbox colors, optional filename caption, **fill/crop** mode with pan, and an optional **mini controller** tray.
- **Compact grid** toggle (dense thumbnails with thin separators), persisted in the browser.
- **Inbox (hot folder)**: drop files under `inbox/` (FTP or file manager) → import from the app or `php scripts/sync_images.php` (optional `IMAGEKPR_SYNC_USER_ID` for CLI ownership).
- **Shared dashboards**: owner picks images in the app; visitors open `index.php?share={token}` (optional password, expiry, embed). JSON CRUD under `api/dashboards.php`. Dashboard image count caps follow **upload tier** (`inc/tiers.php`).
- **Account** page: optional **display name** (session header label); **Admin → Config** for quotas, maintenance, limits; **Admin → Allowlist** for requests and list; **Admin → Updates** for DB-backed product posts; **Admin → Dashboard** for users, tiers, presets, audits, and dangerous operations (e.g. bulk user delete with typed confirmation).
- **Public site**: About, Pricing (Free / Silver / Gold / **Platinum** plus dedicated **Ultra** white-label band), Features, Knowledge base, Support, Contact, legal pages, **Updates** blog (`updates.php` + `updates_posts`), favicons under `favicons/`.

> **Legacy note:** Older installs stored folder membership only in `localStorage`. Run folder migrations (see `migrations/phase13_folders.sql`) or use a current `database.sql` so folders live in MySQL.

## Requirements

- PHP **7.4+** (**8.x** recommended on new hosts).
- MySQL **5.7+** or MariaDB.
- Apache **2.4+** with **`mod_rewrite`** (and ideally **`mod_headers`** for `.htaccess` cache rules).
- PHP extensions: `pdo_mysql`, **`gd` or `imagick`**, `fileinfo`, `json`, `zip`.
- **`upload_max_filesize`** and **`post_max_size`** must be at least as large as your **largest per-user upload tier** (up to **500 MB** for Platinum in code today—set PHP and web server body limits accordingly).

## Quick setup

1. Create a database and import **`database.sql`** (baseline schema).
2. Apply **incremental migrations** from `migrations/` for anything missing on your server compared to this release. The checked-in **`database.sql`** is the baseline for a **new** database; older production DBs are usually brought forward by importing `database.sql` into a scratch DB and **diffing**, or by running only the `phase*.sql` files they have not yet applied (always in **numeric order**). Common gaps on legacy installs:
   - **`phase17_shared_dashboards.sql`** — adds `shared_dashboards` / `shared_dashboard_images` (required for share links).
   - **`phase17_updates_posts.sql`** — adds `updates_posts` (if not already present).
   - **`phase18_user_display_name.sql`** — adds `users.display_name` (if not already in your schema).
   - **`phase21_top_sections_mode.sql`** — adds `users.top_sections_mode` so each account can choose collapsible vs classic top sections.
   - **`phase19_video_support.sql`** — adds `images.media_type` (skip if your `images` table already has that column, e.g. after importing the current `database.sql`).
3. Copy **`config.example.php` → `config.php`** and set DB credentials, **`IMAGES_DIR`**, **`IMAGES_URL`**, **`INBOX_DIR`**, and **Google OAuth** (`GOOGLE_*`). Optionally set `ADMIN_GOOGLE_SUB`, mail constants for `contact.php`, session TTL, etc. (comments in the example file).
4. Ensure **`images/`** and **`inbox/`** exist and are writable by the web user.
5. Point the site **DocumentRoot** at this project root (where `index.php` lives).
6. In **Google Cloud Console**, add the OAuth **redirect URI** that matches `GOOGLE_REDIRECT_URI` (e.g. `https://your-domain.example/auth/google/callback.php`).

## Authentication and access

- Sessions use a custom cookie name when possible, with a safe fallback if the host is strict.
- **Allowlist enforcement** is configurable in **Admin → Allowlist** (stored in `app_settings`). When the allowlist has entries and enforcement is on, only listed emails may use the app.
- **Maintenance mode** and several safety limits are configurable from **Admin → Config** (backed by `app_settings`).

## Shared dashboard links

- Owner-managed dashboards are served when **`index.php` is called with `?share=`** and a valid token (see `inc/shared_dashboard.php`).
- Authenticated dashboard editing uses **`api/dashboards.php`** (session); visitors use the share URL only.

## Upgrading file layout (legacy flat `images/`)

If you still have the old flat `images/*.jpg` layout:

```bash
php scripts/migrate_to_user_folders.php
```

That moves files into `images/{user_id}/` and updates `images.url`.

## Hot folder (FTP workflow)

1. Drop files into `inbox/` (FTP or control panel).
2. In the app, use **Import** from the inbox banner, or run:

```bash
php scripts/sync_images.php
```

Use `IMAGEKPR_SYNC_USER_ID` when running the sync CLI as a specific library owner.

## Project structure (high level)

```
├── index.php              # Main app + ?share= shared dashboards route
├── account.php            # Signed-in profile (display name)
├── updates.php            # Public updates / blog listing + posts
├── pricing.php, features.php, about.php, …   # Marketing / support pages
├── app.js, folders.js, styles.css
├── config.example.php     # Copy to config.php (gitignored)
├── database.sql           # Base schema (plus ordered migrations/)
├── .htaccess
├── api/                   # JSON endpoints (session-auth unless noted)
│   ├── images.php, upload.php, stats.php, whoami.php
│   ├── folders.php, tags.php, inbox.php, inbox_preview.php
│   ├── delete.php, delete_bulk.php, rename.php, rename_bulk.php
│   ├── download_bulk.php, check_duplicates.php
│   └── dashboards.php     # Shared dashboard CRUD
├── admin/                 # HTML admin UI (allowlist, config, updates, user dashboard)
├── auth/google/           # OAuth start + callback
├── inc/                   # PHP includes (auth, admin, tiers, shared dashboard renderer, …)
├── migrations/            # Incremental SQL (run in order for greenfield / parity)
├── scripts/               # CLI helpers (sync, migrations)
├── favicons/
├── assets/
├── images/                # Writable: per-user upload tree
└── inbox/                 # Writable: optional hot-folder staging
```

## JSON API (current state)

Endpoints under `api/` power the web UI. They expect a **logged-in browser session** today. A **Personal Access Token** model, CORS, versioning, and published `docs/API.md` are outlined in **`.cursor/plans/api_readiness_roadmap_1202a95e.plan.md`** (not shipped as a stable public API yet).

Useful fields for integrators probing the same session include `api/whoami.php` (upload limits, quota, dashboard caps, supported MIME types, video permission flag).

## Roadmaps and runbooks (in-repo)

Operational and product planning notes live under **`.cursor/plans/`** (Markdown). Highlights:

| Topic | File |
|--------|------|
| New empty install checklist | `fresh_instance_setup_24ea54b1.plan.md` |
| Domain / hosting migration | `domain_hosting_migration_dac83a01.plan.md` |
| Allowlist UX at scale (search, pagination, bulk) | `allowlist_scale_and_alerts_39fb7141.plan.md` |
| Stripe billing + entitlements roadmap | `stripe_paid_tier_upgrade_e2f8b7f0.plan.md` |
| Public API / tokens roadmap | `api_readiness_roadmap_1202a95e.plan.md` |
| Platinum vs Ultra pricing layout | `platinum_and_ultra_pricing_c14ad369.plan.md` |

## License

MIT
