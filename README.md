# ImageKpr

A minimalistic image repository app for hosting and linking images. Built with LAMP (Linux, Apache, MySQL, PHP) and vanilla JavaScript — no frameworks, no build step. Deploy on shared hosting (cPanel, Plesk) or any server with PHP and MySQL.

## Features

- **Hero header** with logo + **mini dashboard** (total images, storage used, last 10 thumbnails)
- **Lists filter** dropdown (Favourites, custom lists) — filter grid by list
- **Masonry grid** with lazy loading; click card to copy URL, star to add to list, expand icon for full-size modal
- **Search & sort** (filename, tags, date, size, random) — 1s debounce on search
- **Drag-and-drop upload** with client-side resize (max 3MB, 1920px width) for lightweight linking
- **Modal** with Copy URL, Download, Delete, tag edit; visual feedback on hover/click
- **Bulk actions** (selection mode): Delete, Download ZIP, Edit tags, Add to list, Rename
- **Hot folder (inbox)**: Drop images via FTP → Import all from dashboard
- **Favourites & lists**: localStorage, import/export, manage lists dialog
- Infinite scroll (up to 1000 images), then pagination

## Requirements

- PHP 7.4+ (8.x recommended)
- MySQL 5.7+ or MariaDB
- Apache 2.4+
- PHP extensions: `pdo_mysql`, `gd` or `imagick`, `fileinfo`, `json`, `zip`

## Quick Setup

1. **Create database** via cPanel/phpMyAdmin and run `database.sql`
2. **Copy config**: `cp config.example.php config.php` and fill in DB credentials, `IMAGES_DIR`, `IMAGES_URL`, `INBOX_DIR`
3. **Set permissions**: Make `images/` and `inbox/` writable (755 or 775)
4. **Point DocumentRoot** to the project root
5. Ensure `upload_max_filesize` and `post_max_size` in php.ini are at least 3MB

## Project Structure

```
/imagekpr/
├── index.php
├── styles.css
├── app.js
├── lists.js
├── config.example.php
├── database.sql
├── .htaccess
├── api/
│   ├── images.php
│   ├── stats.php
│   ├── upload.php
│   ├── delete.php
│   ├── delete_bulk.php
│   ├── download_bulk.php
│   ├── tags.php
│   ├── rename_bulk.php
│   └── inbox.php
├── assets/
│   └── logo.svg
├── images/
├── inbox/
└── scripts/
    └── sync_images.php
```

## Hot Folder (FTP Workflow)

1. Drop images into `inbox/` via FTP or File Manager
2. Dashboard shows "X images in inbox" — click **Import all**
3. Or run from CLI: `php scripts/sync_images.php`

## License

MIT
