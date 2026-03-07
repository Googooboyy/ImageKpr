# ImageKpr

A minimalistic image repository app for hosting and linking images. Built with LAMP (Linux, Apache, MySQL, PHP) and vanilla JavaScript — no frameworks, no build step. Deploy on shared hosting (cPanel, Plesk) or any server with PHP and MySQL.

## Features

- **Hero header** with logo + **mini dashboard** (total images, storage used, last 10 thumbnails)
- **Masonry grid** with lazy loading; click card to copy URL, expand icon for full-size modal
- **Search & sort** (filename, tags, date, size, random)
- **Drag-and-drop upload** with client-side resize (max 3MB, 1920px width) for lightweight linking
- **Modal** with Copy URL, Download, Close; visual feedback on hover/click
- Infinite scroll (up to 1000 images), then pagination

## Requirements

- PHP 7.4+ (8.x recommended)
- MySQL 5.7+ or MariaDB
- Apache 2.4+ with mod_rewrite
- PHP extensions: `pdo_mysql`, `gd` or `imagick`, `fileinfo`, `json`

## Quick Setup

1. **Create database** via cPanel/phpMyAdmin and run `database.sql`
2. **Copy config**: `cp config.example.php config.php` and fill in DB credentials, `IMAGES_DIR`, `IMAGES_URL`
3. **Set permissions**: Make `images/` writable (755 or 775)
4. **Point DocumentRoot** to the project root
5. Ensure `upload_max_filesize` and `post_max_size` in php.ini are at least 3MB

## Project Structure

```
/imagekpr/
├── index.php             # Main page
├── styles.css
├── app.js
├── config.php            # DB credentials (not in git; use config.example.php)
├── config.example.php
├── database.sql          # Schema
├── api/
│   ├── images.php        # GET list (search, sort, pagination)
│   ├── stats.php         # GET dashboard stats
│   ├── upload.php        # POST upload
│   └── delete.php        # DELETE (optional)
├── assets/
│   └── logo.svg
├── images/               # Uploaded files
├── scripts/
│   └── sync_images.php   # CLI: sync /images/ to DB (FTP workflow)
└── .htaccess
```

## FTP Workflow

If you add images via FTP instead of the web upload, run:

```bash
php scripts/sync_images.php
```

This scans the `images/` folder and inserts any new files into the database.

## License

MIT
