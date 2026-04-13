<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/marketing_illustration.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Features - ImageKpr</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body class="ikpr-doc-page ikpr-features-page">
  <?php
  require_once __DIR__ . '/inc/page_hero.php';
  imagekpr_render_page_hero();
  ?>
  <main class="ikpr-features-main">
    <header class="ikpr-features-hero">
      <h1>Your image library, ready when you are</h1>
      <p class="ikpr-features-hero-lead">Upload in bulk, organize with folders and tags, find images in seconds, and share only when you choose — all in one private workspace.</p>
    </header>

    <section class="ikpr-features-spotlight" aria-labelledby="ikpr-features-spotlight-heading">
      <h2 id="ikpr-features-spotlight-heading" class="ikpr-footer-visually-hidden">Highlights</h2>
      <ul class="ikpr-features-spotlight-grid">
        <li class="ikpr-features-spotlight-card">
          <div class="ikpr-features-spotlight-icon" aria-hidden="true">
            <svg viewBox="0 0 48 48" width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M8 32V14a2 2 0 0 1 2-2h8l4 4h14a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H10a2 2 0 0 1-2-2z"/>
              <path d="M18 22v10M13 27h10"/>
            </svg>
          </div>
          <h3>Bulk upload</h3>
          <p>Drag in many files at once and keep momentum; optional resize when files exceed your limit.</p>
        </li>
        <li class="ikpr-features-spotlight-card">
          <div class="ikpr-features-spotlight-icon" aria-hidden="true">
            <svg viewBox="0 0 48 48" width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M10 14h28v24H10z"/>
              <path d="M10 20h28"/>
              <path d="M16 26h10M16 32h16"/>
            </svg>
          </div>
          <h3>Folders &amp; tags</h3>
          <p>Structure libraries the way you think — projects, clients, campaigns, or seasons.</p>
        </li>
        <li class="ikpr-features-spotlight-card">
          <div class="ikpr-features-spotlight-icon" aria-hidden="true">
            <svg viewBox="0 0 48 48" width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="18" cy="22" r="6"/>
              <path d="M26 22h10M31 17v10"/>
              <path d="M14 34h26a2 2 0 0 0 2-2v-4H12v4a2 2 0 0 0 2 2z"/>
            </svg>
          </div>
          <h3>Private links</h3>
          <p>Copy shareable links only when you want; your gallery stays yours by default.</p>
        </li>
        <li class="ikpr-features-spotlight-card">
          <div class="ikpr-features-spotlight-icon" aria-hidden="true">
            <svg viewBox="0 0 48 48" width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <rect x="8" y="12" width="32" height="24" rx="2"/>
              <path d="M14 36h20"/>
              <path d="M24 36v-6"/>
            </svg>
          </div>
          <h3>Search &amp; present</h3>
          <p>Jump to assets with search, then run slideshows and lightweight presentations.</p>
        </li>
      </ul>
    </section>

    <h2 class="ikpr-features-section-title">Features</h2>

    <section class="ikpr-features-row">
      <div class="ikpr-features-copy">
        <h3>Fast capture</h3>
        <p>Drop a batch onto the upload zone or pick files manually. Review imports from the inbox when you want a second pass before files land in your main library.</p>
        <ul>
          <li>Multi-file uploads with clear progress</li>
          <li>Large files can be resized when you continue past the limit</li>
          <li>Designed for repeated daily use, not one-off uploads</li>
        </ul>
        <a class="ikpr-features-cta" href="/">Open ImageKpr</a>
      </div>
      <?php ikpr_marketing_render_illustration('Illustration: bulk upload and inbox flow', 'grid'); ?>
    </section>

    <section class="ikpr-features-row ikpr-features-row--flip">
      <div class="ikpr-features-copy">
        <h3>Organize without friction</h3>
        <p>Folders and tags work together so you can browse visually or filter precisely. Manage folder lists as your library grows.</p>
        <ul>
          <li>Folders for broad structure</li>
          <li>Tags for cross-cutting themes</li>
          <li>Sort options to match how you scan a set</li>
        </ul>
        <a class="ikpr-features-cta" href="/">Get started</a>
      </div>
      <?php ikpr_marketing_render_illustration('Illustration: folders and tags', 'folders'); ?>
    </section>

    <section class="ikpr-features-row">
      <div class="ikpr-features-copy">
        <h3>Controlled sharing</h3>
        <p>Sign in with Google keeps the gallery private to approved accounts. Generate links for individual images when collaboration calls for it — and skip sharing when it does not.</p>
        <ul>
          <li>Privacy-first access model</li>
          <li>Copyable links when you choose to share</li>
          <li>Aligned with small teams and solo operators</li>
        </ul>
        <a class="ikpr-features-cta" href="/">Open ImageKpr</a>
      </div>
      <?php ikpr_marketing_render_illustration('Illustration: sharing and private links', 'link'); ?>
    </section>

    <section class="ikpr-features-row ikpr-features-row--flip">
      <div class="ikpr-features-copy">
        <h3>Find in seconds</h3>
        <p>Search across your library so you are not hunting through nested folders during a deadline. Pair search with tags for a fast, repeatable workflow.</p>
        <ul>
          <li>Text search across your collection</li>
          <li>Works alongside folder and tag filters</li>
          <li>Built for “I know we have that shot somewhere” moments</li>
        </ul>
        <a class="ikpr-features-cta" href="/">Get started</a>
      </div>
      <?php ikpr_marketing_render_illustration('Illustration: search', 'search'); ?>
    </section>

    <section class="ikpr-features-row">
      <div class="ikpr-features-copy">
        <h3>Present &amp; slideshow</h3>
        <p>Move from library to presentation without exporting to another tool for every review. Use slideshow mode when you need to walk someone through a set.</p>
        <ul>
          <li>Slideshow-friendly viewing</li>
          <li>Presentation-style flow from your own assets</li>
          <li>Ideal for internal reviews and quick walkthroughs</li>
        </ul>
        <a class="ikpr-features-cta" href="/">Open ImageKpr</a>
      </div>
      <?php ikpr_marketing_render_illustration('Illustration: slideshow or presentation view', 'slideshow'); ?>
    </section>

    <section class="ikpr-features-row ikpr-features-row--flip">
      <div class="ikpr-features-copy">
        <h3>Export batches</h3>
        <p>Zip a selection when you need files on disk for email, handoff, or archive — without manually downloading one image at a time.</p>
        <ul>
          <li>Bulk download as a zip</li>
          <li>Complements link-based sharing</li>
          <li>Handy for campaign packs and client deliveries</li>
        </ul>
        <a class="ikpr-features-cta" href="/">Get started</a>
      </div>
      <?php ikpr_marketing_render_illustration('Illustration: zip download or export', 'default'); ?>
    </section>

    <section class="ikpr-features-bottom-cta" aria-labelledby="ikpr-features-bottom-heading">
      <h2 id="ikpr-features-bottom-heading">Try ImageKpr</h2>
      <p>Sign in with Google when your account is approved, or request access from the home page.</p>
      <a class="ikpr-features-cta ikpr-features-cta--primary" href="/">Go to home</a>
    </section>
  </main>
  <?php
  require_once __DIR__ . '/inc/footer.php';
  imagekpr_render_footer();
  ?>
</body>
</html>
