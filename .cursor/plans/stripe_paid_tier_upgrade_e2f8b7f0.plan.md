---
name: stripe_paid_tier_upgrade
overview: "Security hardening (Phase 0: UUID opaque image storage + upload DoS protection) followed by Stripe Checkout + webhooks so users can self-upgrade across FREE/SILVER/GOLD on the shared SaaS; PRO is maximum-features white-label on a dedicated server (SGD 999 / 3-year license, renewal at term end—not a SaaS seat tier). Entitlement sync for upload size, caps, quotas on the multi-tenant app; Admin tracking for subscriptions and Pro sales handoff."
todos:
  - id: phase0a-migration-sql
    content: "Phase 0a: Create migration SQL adding disk_filename column to images, backfill existing rows, add unique index."
    status: pending
  - id: phase0a-helpers
    content: "Phase 0a: Add imagekpr_user_image_url() and imagekpr_disk_filename() helpers in inc/images_path.php (or new inc/image_helpers.php)."
    status: pending
  - id: phase0a-upload
    content: "Phase 0a: Update api/upload.php to generate UUID disk filenames on insert in user-scoped folders; store both filename (display) and disk_filename (disk); build url from user_id + disk_filename."
    status: pending
  - id: phase0a-inbox
    content: "Phase 0a: Update api/inbox.php to generate UUID disk filenames on import."
    status: pending
  - id: phase0a-rename
    content: "Phase 0a: Simplify api/rename.php and api/rename_bulk.php to DB-only updates (no disk rename); url stays based on disk_filename."
    status: pending
  - id: phase0a-delete
    content: "Phase 0a: Update api/delete.php, api/delete_bulk.php, and inc/admin.php purge to use disk_filename for unlink."
    status: pending
  - id: phase0a-download-images
    content: "Phase 0a: Update api/download_bulk.php (zip entry = display name, disk read = user-scoped UUID) and api/images.php (return user-scoped url from disk_filename)."
    status: pending
  - id: phase0a-sync-script
    content: "Phase 0a: Update scripts/sync_images.php to generate UUID disk filenames."
    status: pending
  - id: phase0a-migrate-script
    content: "Phase 0a: Create scripts/migrate_uuid_filenames.php one-time CLI tool to rename existing files on disk to UUIDs and update DB rows."
    status: pending
  - id: phase0a-js-rename
    content: "Phase 0a: Update app.js rename response handling to keep existing url when only filename (display name) changes."
    status: pending
  - id: phase0b-htaccess
    content: "Phase 0b: Add LimitRequestBody (aligned with max 50MB tier) to .htaccess."
    status: pending
  - id: phase0b-upload-rate-limit
    content: "Phase 0b: Add per-user and per-IP rate limits to api/upload.php using existing imagekpr_rate_limit()."
    status: pending
  - id: phase0b-config-docs
    content: "Phase 0b: Document recommended php.ini values (upload_max_filesize, post_max_size, max_file_uploads) in config.example.php."
    status: pending
  - id: stripe-dashboard-checklist
    content: "Pre-Phase 1: Complete Stripe Dashboard checklist (Test mode): Products/Prices with metadata, Customer portal, webhook endpoint + signing secret, API keys; repeat essentials in Live before launch."
    status: pending
  - id: phase1-composer
    content: "Phase 1: Initialize Composer and install Stripe PHP SDK."
    status: pending
  - id: phase1-migrations
    content: "Phase 1: Add DB migrations for billing columns on users, billing_membership_periods, and billing_events tables."
    status: pending
  - id: phase1-config
    content: "Phase 1: Add Stripe config keys to config.example.php and config.php."
    status: pending
  - id: phase1-tier-allowlist
    content: "Phase 1: Update imagekpr_allowed_upload_size_tiers_mb to [3, 10, 50] in inc/admin.php."
    status: pending
  - id: phase1-js-refactor
    content: "Phase 1: Extract upload.js and slideshow.js from app.js (pure reorganization, no behavior changes)."
    status: pending
  - id: phase2-checkout
    content: "Phase 2: Implement create-checkout-session and create-customer-portal-session endpoints."
    status: pending
  - id: phase2-webhook
    content: "Phase 2: Implement webhook endpoint with Stripe signature auth (no session), idempotent event handling, and tier mapping."
    status: pending
  - id: phase2-summary
    content: "Phase 2: Implement billing/summary endpoint for Account & Billing page data."
    status: pending
  - id: phase2-periods
    content: "Phase 2: Implement billing_membership_periods open/close logic in webhook handlers."
    status: pending
  - id: phase3-caps
    content: "Phase 3: Enforce account-wide image-count caps, shared-dashboard per-dashboard image caps (matrix column), and per-tier storage quotas per Tier specification matrix; sync users.storage_quota_bytes and upload_size_mb from entitlements; extend whoami with usage."
    status: pending
  - id: phase3-billing-js
    content: "Phase 3: Create billing.js module with upgrade flow, overage dialog, and Stripe portal helpers."
    status: pending
  - id: phase3-overage
    content: "Phase 3: Add overage UX dialog with Cancel and Upgrade actions at all limit-hit points."
    status: pending
  - id: phase3-whoami
    content: "Phase 3: Extend whoami payload with tier, image/storage usage vs caps, and billing state fields."
    status: pending
  - id: phase4-account
    content: "Phase 4: Build account.php (Account & Billing page) with plan, usage, actions, invoice table."
    status: pending
  - id: phase4-footer-legal
    content: "Phase 4: Add app footer + create terms.php, privacy.php, billing-policy.php. Apply all UX copy snippets."
    status: pending
  - id: phase5-admin
    content: "Phase 5: Add admin paid streak/lifetime columns, tier badges, and sortable billing columns."
    status: pending
  - id: phase6-qa
    content: "Phase 6: End-to-end QA in Stripe test mode, live smoke test, and launch sign-off."
    status: pending
isProject: false
---

# ractImageKpr -- Security Hardening + Stripe Paid Tiers

## Tier specification matrix (source of truth — edit here)

**Purpose:** One place to define what each named plan means for product, engineering, and Stripe. When you change limits, update the tables below first, then align code (`imagekpr_allowed_upload_size_tiers_mb()`, webhook tier mapping, copy on Account / legal pages).

**Conventions**

- **Per-file limit** maps to `users.upload_size_mb` (MiB). Allowed values in app code today: **3, 10, 50** only (see `[inc/admin.php](inc/admin.php)` `imagekpr_allowed_upload_size_tiers_mb()`). If you introduce a new upload step, extend that allowlist and migrations.
- **Total storage** maps to `users.storage_quota_bytes` (binary bytes). Use the **Bytes (decimal)** column for config/constants, or compute as `MiB * 1024 * 1024`.
- **Max images** is the optional **account-wide** image count cap planned for Phase 3 (total images in library), not the shared-dashboard column.
- **Shared dashboard images (per dashboard):** Max **selected images on a single** shared dashboard ([Shared Dashboard feature](shared_dashboard_feature_6fe7f508.plan.md)). The limit is **per dashboard**, not pooled across dashboards — e.g. on Free, two dashboards with 20 different images each is valid.
- **Silver / Gold** are **subscriptions** sold as **monthly** and **yearly** Stripe Prices (same entitlements for both intervals; different Price IDs). Checkout and webhooks must accept either interval for each tier.
- **Pro (white-label)** is **not** a higher tier **on the shared ImageKpr app**. It targets buyers who want **maximum features** on **their own** stack: **duplicate ImageKpr** for one client on a **dedicated new server**, **white-labeled** for them. **Commercial shape:** **SGD 999** for a **3-year** license term, with **license renewal** at end of term (contract/SOW). On that dedicated instance there is **no Free/Silver/Gold ladder** (single-tenant; entitlements per matrix row below). Stripe may use a **one-time** or invoiced **payment** for the term, or equivalent—**not** a monthly SaaS subscription like Silver/Gold. Fulfillment is **off-platform** (provisioning, DNS, branding). The **Entitlements** row for `pro` below is for **CRM / admin / marketing consistency** — it does **not** mean a Pro customer’s library lives in the same DB as public SaaS users unless you intentionally run a hybrid (usually you do not).
- **Pro — detail later:** Full Pro playbook (sales, SOW, provisioning, and **feature-set differences per dedicated instance** vs stock ImageKpr) is **out of scope** for Phases 0–6 here. Treat Pro as **listed + payment capture** (and minimal admin/CRM hooks if needed) until **Stripe on the shared SaaS is successful**; then spec Pro delivery in a **separate plan**.

### Entitlements (product → database fields)


| Tier (customer-facing name) | Internal key (API / metadata) | `upload_size_mb` (per file) | Total storage | `storage_quota_bytes` (decimal) | Max images (cap) | Shared dashboard max images (per dashboard) | Notes                                                                                            |
| --------------------------- | ----------------------------- | --------------------------- | ------------- | ------------------------------- | ---------------- | ------------------------------------------- | ------------------------------------------------------------------------------------------------ |
| Free                        | `free`                        | 3                           | 50 MiB        | 52428800                        | 100              | 20                                          | Default for new signups; no Stripe.                                                              |
| Silver                      | `silver`                      | 10                          | 200 MiB       | 209715200                       | 200              | 40                                          | Paid subscription starts from this tier onwards.                                                 |
| Gold                        | `gold`                        | 50                          | 1 GiB         | 1048576000                      | 1000             | 200                                         | Highest self-serve upload tier (50 MB/file).                                                     |
| Pro (white-label)           | `pro`                         | *Dedicated instance*        | *20 GiB*      | 20971520000                     | *unlimited*      | *unlimited*                                 | **3-yr contract.** New server + white-label ImageKpr; **no tier distinction** on their instance. |


**Subscriptions (Silver, Gold):** Create **two** recurring Prices per tier — **monthly** and **yearly** — in Stripe (same Product or separate Products; your choice). Same internal key `silver` or `gold` on each Price metadata; webhook maps **each** Price ID to the same entitlement row.

**Pro:** List **SGD 999** per **3-year** license term (renewal at end of term). In Stripe, model as **one-time** or **invoice** payment for the contract term—not Silver/Gold-style recurring subscription unless you deliberately add a renewal SKU. (Large deals may still be invoiced outside Stripe.) Do **not** map Pro to `upload_size_mb` on the **shared** SaaS unless you explicitly sell a rare “hosted Pro” variant. **Per-instance feature differences** and full fulfillment workflow are **deferred** until after SaaS Stripe is stable (see convention **Pro — detail later** above).

**List price (SGD)** — optional column for documentation, UX copy, and internal notes. **Stripe remains the source of truth** for what is charged (amount + currency are on each Price in Dashboard). Keep this column in sync when you change prices in Stripe.


| Tier   | Payment cadence  | Stripe mode  | Suggested Price metadata `tier` | List price (SGD, optional) | Test Price ID (`price_...`) | Live Price ID (`price_...`) |
| ------ | ---------------- | ------------ | ------------------------------- | -------------------------- | --------------------------- | --------------------------- |
| Silver | Monthly          | Subscription | `silver`                        | 2.99                       |                             |                             |
| Silver | Yearly           | Subscription | `silver`                        | 29.9                       |                             |                             |
| Gold   | Monthly          | Subscription | `gold`                          | 9.99                       |                             |                             |
| Gold   | Yearly           | Subscription | `gold`                          | 99.9                       |                             |                             |
| Pro    | **3-year term**  | **One-time / invoice** (not monthly SaaS) | `pro`                           | 999 (per term)             |                             |                             |


### Quick edit checklist

1. Change **Entitlements** table (storage, upload MB, account max images, **shared dashboard per-dashboard** image cap).
2. If `upload_size_mb` values change set: update `imagekpr_allowed_upload_size_tiers_mb()` and any admin UI copy.
3. If storage changes: ensure Phase 3 enforcement and default for new subscribers use the new `storage_quota_bytes`.
4. Fill **Stripe catalog** Price IDs (Test then Live): **four** subscription lines (Silver monthly/yearly, Gold monthly/yearly) plus **Pro** (e.g. one-time or invoice for **SGD 999** / **3-year** term); keep webhook mapping in sync. Optionally fill **List price (SGD)** (must match Stripe).
5. Grep the repo for old numbers in UX copy (`account.php`, `billing-policy.php`, terms, marketing).

### Related roadmaps (coordination)

These plans do **not** change the Stripe technical phases below, but implementation order should avoid contradictions.

- **[Shared Dashboard](shared_dashboard_feature_6fe7f508.plan.md)** — Per-dashboard image limits apply to the **shared SaaS** (Free 20, Silver 40, Gold 200 — see Entitlements table above). **Pro (white-label)** customers are on a **separate deployment**; cap rules there are **deploy-time / contract**, not this matrix’s Pro row. Paid-only behavior on SaaS uses an **interim** proxy: `upload_size_mb >= 10` so **Silver and Gold** count as paid (Free remains `3`). When billing columns and **plan tier** exist, replace `imagekpr_user_is_paid()` and enforce dashboard caps from **plan tier** for SaaS users only.
- **[API Readiness](api_readiness_roadmap_1202a95e.plan.md)** — Phase 3 of this Stripe plan extends `**whoami`** with tier and usage; if external PAT + CORS access is live by then, reflect new fields in `**docs/API.md`** (API roadmap Phase 4). Any new billing endpoints (`create-checkout-session`, webhooks, etc.) should follow the same **session vs Bearer** rules and rate limiting as the rest of `api/*` (especially if called from a separate origin). Webhooks stay **server-to-server** (Stripe signature), not PAT-authenticated.

---

## Phase ordering

- **Phase 0a** -- UUID opaque image storage (security refactor, done first so later phases build on clean storage model)
- **Phase 0b** -- Upload rate limiting and DoS hardening
- **Stripe Dashboard** -- Checklist below (do in Test mode before Phase 1--2 wiring; mirror for Live at launch)
- **Phase 1** -- Stripe foundation (SDK, migrations, config, JS refactor)
- **Phase 2** -- Stripe Checkout, webhooks, billing periods
- **Phase 3** -- Tier enforcement, billing UX, overage dialogs
- **Phase 4** -- Account and Billing page, footer, legal pages
- **Phase 5** -- Admin billing columns and badges
- **Phase 6** -- End-to-end QA, live smoke test, launch sign-off

### Recommended implementation stages (milestones)

The numbered **phases** stay as the technical sequence. **Stages** are how you ship in chunks so each merge has a clear outcome and test bar. Five stages match natural dependency boundaries; you can **merge Stage 4 + 5** if you prefer one “surfaces + ops” release before launch.


| Stage                                | Includes                                            | What “done” means                                                                                                                                      |
| ------------------------------------ | --------------------------------------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------ |
| **1 -- Security & upload hardening** | Phase 0a, 0b                                        | Opaque disk URLs, migration + scripts verified, upload limits and rate limits in place. **No Stripe.** Safe to deploy and soak.                        |
| **2 -- Stripe backend**              | Stripe Dashboard checklist (Test), Phase 1, Phase 2 | Users can complete Checkout / Portal in test mode; webhooks idempotently sync tier and membership periods; billing summary API exists for a future UI. |
| **3 -- Enforcement & billing UX**    | Phase 3                                             | Quotas and caps enforced server-side; `whoami` exposes usage; overage dialogs and `billing.js` work end-to-end in test mode.                           |
| **4 -- Account & legal surfaces**    | Phase 4                                             | Account & Billing page, footer, terms / privacy / billing-policy live and linked.                                                                      |
| **5 -- Admin & launch**              | Phase 5, Phase 6                                    | Admin billing columns; full test-mode QA; Live keys, Live webhook, smoke test, sign-off.                                                               |


**Why split this way:** Stage 1 is valuable on its own (security + abuse protection) and avoids coupling file migration to payment bugs. Stage 2 concentrates all “money pipe” risk in one place before you build product UX on top. Stage 3 is where most edge-case logic lives (limits + dialogs); keep it testable before polishing marketing pages. Stages 4--5 are mostly UI and operations.

**Todo list size:** Keep the detailed YAML todos for execution tracking, or collapse to **one todo per stage** in your tracker and use this doc’s phase list as the checklist inside each stage.

---

## Phase 0a: UUID opaque image storage

### Problem

Images are currently stored in per-user folders and served at predictable URLs like `/images/{user_id}/vacation.jpg`. Even with user scoping, filenames are still human-readable and guessable once a path leaks.

### Solution

Keep the existing per-user folder layout, but store each image file under a **random opaque name** (e.g. `a3f8c1e97b2d4e5a9c0f1d2e3f4a5b6c.jpg`) inside that user's folder. The original display name stays in DB `filename` for UI; new `disk_filename` stores the physical filename on disk.

### DB migration

New file: `migrations/phase0a_uuid_disk_filename.sql`

- ADD `disk_filename VARCHAR(128)` to `images` table
- Backfill all existing rows: `SET disk_filename = filename`
- Make `disk_filename NOT NULL`, add `UNIQUE INDEX uq_disk_filename`

### Helper functions

Add to `inc/images_path.php` (or new `inc/image_helpers.php`):

- `imagekpr_disk_filename(string $ext): string` -- generates `bin2hex(random_bytes(16)) . '.' . $ext`
- `imagekpr_user_image_url(int $userId, string $diskFilename): string` -- returns `imagekpr_user_images_url($userId) . '/' . $diskFilename`

All endpoints call these alongside existing `imagekpr_user_images_dir()` / `imagekpr_user_images_url()` helpers instead of hand-building paths.

### Files changed

- **[api/upload.php](api/upload.php)** -- On insert: generate UUID disk name via helper, `move_uploaded_file` to user-scoped UUID path (`/images/{uid}/{uuid.ext}`), INSERT both `filename` (display) and `disk_filename` (disk), build `url` from `user_id + disk_filename`.
- **[api/inbox.php](api/inbox.php)** -- Same pattern for inbox import: `copy()` to user-scoped UUID path.
- **[api/rename.php](api/rename.php)** -- Simplifies to a pure DB UPDATE of `filename` only. No filesystem `rename()`. URL stays based on `disk_filename` and does not change.
- **[api/rename_bulk.php](api/rename_bulk.php)** -- Same simplification.
- **[api/delete.php](api/delete.php)** -- Uses `disk_filename` for the `unlink()` path.
- **[api/delete_bulk.php](api/delete_bulk.php)** -- Same.
- **[api/download_bulk.php](api/download_bulk.php)** -- Reads file from user-scoped `disk_filename` path on disk, but names the zip entry with display `filename`.
- **[api/images.php](api/images.php)** -- SELECT includes `disk_filename`; returned `url` is built via user-scoped URL helper.
- **[scripts/sync_images.php](scripts/sync_images.php)** -- Generates UUID disk filenames on import.
- **[inc/admin.php](inc/admin.php)** -- `imagekpr_admin_purge_gallery_for_users()` uses `disk_filename` for `unlink()`.

### One-time migration script

New file: `scripts/migrate_uuid_filenames.php`

- CLI tool: loops rows where `disk_filename = filename` (old-style, not yet migrated)
- For each: generates UUID name, `rename()` file on disk, UPDATE DB row (`disk_filename`, `url`)
- Supports `--dry-run` (prints planned renames, no action)
- Idempotent: skips rows already migrated
- Prerequisite: per-user folder migration is already in place (`scripts/migrate_to_user_folders.php`) or this script must detect both flat and user-scoped layouts safely.

### Frontend JS

Minimal change. `app.js` already uses `img.url` for `<img src>` and `img.filename` for display. Only change: rename response handling should **keep existing `url`** (URL no longer changes on rename since the disk file doesn't move).

### Backward compatibility

- Old bookmarked URLs that reference original filenames (including `/images/{user_id}/photo.jpg`) will 404 after UUID migration. This is intentional -- filename-based URLs should no longer resolve.
- `.htaccess` rules for `images/` directory (PHP engine off, caching headers) remain unchanged.

---

## Phase 0b: Upload rate limiting and DoS hardening

### 1. Apache-level body size cap

In [.htaccess](.htaccess), add:

```apache
LimitRequestBody 104857600
```

Rejects oversized requests before PHP starts. Aligned with the max upload tier (50MB).

### 2. Per-user upload rate limit

In [api/upload.php](api/upload.php), after `imagekpr_require_api_user()`, add a rate-limit call using the existing `imagekpr_rate_limit()` from [inc/rate_limit.php](inc/rate_limit.php):

- Bucket key: `'upload_u' . $uid`
- Limit: 60 requests per 10 minutes per user
- Returns 429 on breach

### 3. Per-IP upload rate limit

Second bucket keyed only by IP (catches abuse from stolen sessions):

- Bucket key: `'upload_ip'`
- Limit: 120 requests per 10 minutes per IP
- Returns 429 on breach

### 4. php.ini documentation

Add a comment block in [config.example.php](config.example.php) noting recommended values:

- `upload_max_filesize = 100M`
- `post_max_size = 105M`
- `max_file_uploads = 50`

These should match the highest upload tier and the `max_files_per_upload_post` app setting.

---

## Stripe Dashboard checklist (before Phase 1--2)

Do this in **Test mode** first so Price IDs and webhook secrets can be copied into config when implementing Checkout and webhooks. Repeat the same categories in **Live mode** before switching production keys.

### Account and readiness

- **Business profile** -- Legal business name, address, support URL/email as needed for Stripe and customer emails.
- **Bank account** -- Payout account connected and verified so go-live is not blocked.
- **Branding** -- Logo, brand color, public website URL (used in Checkout and emails where applicable).
- **Statement descriptor** -- Short name on card statements (Settings or Payments settings); keep within character limits.

### Products and Prices (map to app tiers)

- **Products** -- Create one Product per sellable line matching the **Stripe catalog** table in the Tier specification matrix (top of this doc). Names can change later; stable **metadata** on Product or Price (e.g. `tier: silver`) helps webhook code stay maintainable.
- **Recurring Prices** -- For SILVER/GOLD (or equivalent): create **Subscription** prices (monthly and/or yearly if both are offered). Each gets a **Price ID** (`price_...`) for `config.php`.
- **Pro contract Price** -- For PRO (**SGD 999** / **3-year** term): create a **one-time** or **invoice** Price (or equivalent) linked to its Product; record its **Price ID** separately from subscriptions; handle **renewal** as a new sale/term when appropriate.
- **Copy Price IDs** -- Store test Price IDs in a secure note or env template until `config.example.php` documents the exact variable names (Phase 1).

### Customer portal

- **Settings → Billing → Customer portal** -- Enable portal; choose what subscribers can do (cancel subscription, update payment method, view invoices). This controls behavior when the app opens a **Customer Portal session** (Phase 2).

### Developers: keys

- **API keys** -- From Developers → API keys: **Publishable** and **Secret** for Test. Later, copy **Live** keys when going live. Never commit real secrets.

### Webhooks (source of truth for subscription state)

- **Test webhook endpoint** -- After the webhook URL exists in code (Phase 2), add endpoint in Dashboard (Developers → Webhooks) pointing to the **HTTPS** URL (e.g. `https://yourdomain.com/.../stripe-webhook.php`). Select events the handlers will implement (at minimum: `checkout.session.completed`; subscription lifecycle such as `customer.subscription.created`, `updated`, `deleted`; `invoice.paid` / `invoice.payment_failed` as needed for tier sync and dunning).
- **Signing secret** -- Copy the endpoint **Signing secret** (`whsec_...`) into server config only (not the repo). Test and Live endpoints have **different** secrets.
- **Local development** -- Use **Stripe CLI** (`stripe listen --forward-to ...`) to forward events to localhost and obtain a CLI signing secret for dev.

### Tax and compliance (if applicable)

- **Stripe Tax** or manual tax behavior -- Decide whether to enable automatic tax in Checkout; align with terms and business jurisdiction.

### Launch (Live)

- Duplicate the checklist for **Live mode**: Live Products/Prices (or activate same products per Stripe workflow), Live webhook URL + **Live** signing secret, Live API keys, and a **live smoke test** (Phase 6).

---

## Phases 1--6: Stripe paid tiers

*(Existing plan content -- unchanged from original. See todo items above for detailed task breakdown.)*

### Phase 1: Stripe foundation

- Initialize Composer, install Stripe PHP SDK
- DB migrations: billing columns on `users`, new `billing_membership_periods` and `billing_events` tables
- Add Stripe keys to `config.example.php` and `config.php`
- Update `imagekpr_allowed_upload_size_tiers_mb` to `[3, 10, 50]`
- Extract `upload.js` and `slideshow.js` from `app.js` (pure reorg)

### Phase 2: Checkout + webhooks

- `create-checkout-session` and `create-customer-portal-session` endpoints
- Webhook endpoint with Stripe signature verification, idempotent event handling, tier mapping
- Billing summary endpoint for Account page
- `billing_membership_periods` open/close logic in webhook handlers

### Phase 3: Tier enforcement + billing UX

- Image-count caps and per-tier storage quotas (**values:** Tier specification matrix at top of this doc)
- `billing.js` module with upgrade flow, overage dialog, Stripe portal helpers
- Overage UX dialog with Cancel and Upgrade actions
- Extend `whoami` with tier, usage vs caps, billing state

### Phase 4: Account page + legal

- `account.php` with plan info, usage bars, actions, invoice table
- App footer + `terms.php`, `privacy.php`, `billing-policy.php`

### Phase 5: Admin billing columns

- Paid streak/lifetime columns, tier badges, sortable billing columns in admin user list

### Phase 6: QA

- End-to-end QA in Stripe test mode
- Live smoke test
- Launch sign-off

---

## Appendix: Customer support (post-launch; outside Phases 1--6)

**Chosen model:** **Self-serve first** (FAQ / help articles), then **escalation via email** reached through a **contact form** (or mailto link if you want zero backend at first). This is standard, low-overhead, and absolutely doable.

### Tier 1 -- Self-serve

- **FAQ or Help hub** -- Short articles: upload limits, tiers, upgrade path, and **billing self-service** (Stripe Customer Portal for card, invoices, cancellation where applicable).
- **In-app copy** -- Link errors and limit messages to the relevant article when possible.

### Tier 2 -- Email escalation

- **Contact form** -- Fields such as: email, subject/category, message; optional logged-in user id hidden or shown for faster lookup. Submissions go to `support@...` via server-side mail (e.g. PHP `mail()` or SMTP) or a form-to-email service.
- **Simpler MVP:** A **contact** page with category text + **mailto:** link with prefilled subject line (no server mail required); slightly worse UX but fine to start.

### Avoiding repeat emails (“I never heard back”)

Users re-send when they lack **acknowledgment** and **expectations**.

- **Immediate auto-reply** (strongly recommended for email support) -- On submit, email the user: “We received your message,” include a **reference id**, and state a **response window** (e.g. “We aim to reply within 2 business days”). Include the same id in the notification to `support@...` so you can thread in the inbox.
- **Published SLA** -- On Help / Contact, state typical response time so silence is not read as being ignored.
- **Help desk (optional)** -- Help Scout / Zendesk / similar send confirmations and keep threading; many offer a **customer portal** where people see ticket status without custom app code.

### In-app submission status on Account (optional; doable)

Showing **status on the profile** is good UX and **is doable**, but it is **more than a dumb email form**: you **persist** each submission in the database, tie it to `user_id` (for logged-in users), and maintain a **lifecycle** (e.g. Open → In progress → Resolved).

**Rough shape:**

- **Table** e.g. `support_tickets` or `contact_submissions`: `id`, `user_id`, `subject`, `body` or excerpt, `status`, `created_at`, `updated_at`; optional internal fields for admin.
- **Submit flow** -- Insert row, email support with ticket id, **auto-reply** to user with the same id.
- **Account page** -- Section “Your requests” with status and date (read-only for the user).
- **Admin** -- Simple UI to update status when you reply or close the ticket.

**Tradeoffs:** Adds schema, API, Account UI, and admin upkeep; it directly reduces duplicate submissions if users see movement (e.g. “In progress”). For **v1**, **auto-reply + reference id + SLA copy** is often enough; add **in-app status** when volume or trust justifies the build.

**Alternative:** A **hosted helpdesk with portal** gives status without storing tickets in ImageKpr.

### Routing (still important)

- **Payment method, receipts, subscription cancel** -- Help articles + **billing-policy** should push users to **Stripe Customer Portal** first; form is for **product/account** issues that docs do not cover.
- **Complex or account-specific issues** -- Contact form / email; Phase 5 admin fields help identify paid users when replying.

### Later (optional)

- Forward the same inbox into **Help Scout / Zendesk / etc.** when volume grows.
- Live chat or community forum only if you add capacity and a clear need.

Implementation (help index, article pages, contact form or mailto) can ship **alongside or after** Phase 4 footer/legal links; it is not required to close the Stripe phases. Revisit scope after launch.