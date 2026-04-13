<?php
declare(strict_types=1);

require_once __DIR__ . '/inc/marketing_illustration.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Case Studies - ImageKpr</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body class="ikpr-doc-page ikpr-case-studies-page">
  <?php
  require_once __DIR__ . '/inc/page_hero.php';
  imagekpr_render_page_hero();
  ?>
  <main class="ikpr-features-main">
    <header class="ikpr-features-hero">
      <h1>Case studies</h1>
      <p class="ikpr-features-hero-lead">Representative examples of how teams use ImageKpr to simplify image operations, keep assets findable, and cut delivery friction.</p>
    </header>

    <section class="ikpr-features-spotlight" aria-labelledby="ikpr-case-studies-spotlight-heading">
      <h2 id="ikpr-case-studies-spotlight-heading" class="ikpr-footer-visually-hidden">Who it fits</h2>
      <ul class="ikpr-features-spotlight-grid">
        <li class="ikpr-features-spotlight-card">
          <div class="ikpr-features-spotlight-icon" aria-hidden="true">
            <svg viewBox="0 0 48 48" width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M12 20h24v18H12z"/>
              <path d="M16 20V14a4 4 0 0 1 4-4h8a4 4 0 0 1 4 4v6"/>
              <path d="M12 28h24"/>
            </svg>
          </div>
          <h3>Agencies &amp; studios</h3>
          <p>Many clients, parallel campaigns, and tight review cycles — one library pattern that scales.</p>
        </li>
        <li class="ikpr-features-spotlight-card">
          <div class="ikpr-features-spotlight-icon" aria-hidden="true">
            <svg viewBox="0 0 48 48" width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <path d="M14 10h20v28H14z"/>
              <path d="M18 16h12M18 22h12M18 28h8"/>
            </svg>
          </div>
          <h3>Content &amp; publishing</h3>
          <p>Centralize visuals for newsletters, blogs, and social without losing track of versions.</p>
        </li>
        <li class="ikpr-features-spotlight-card">
          <div class="ikpr-features-spotlight-icon" aria-hidden="true">
            <svg viewBox="0 0 48 48" width="40" height="40" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
              <circle cx="24" cy="20" r="8"/>
              <path d="M14 38c2-6 6-8 10-8s8 2 10 8"/>
            </svg>
          </div>
          <h3>Brand &amp; internal teams</h3>
          <p>Approved imagery in one place — fewer one-off requests and more consistent usage.</p>
        </li>
      </ul>
    </section>

    <h2 class="ikpr-features-section-title">Stories</h2>

    <section class="ikpr-features-row">
      <div class="ikpr-features-copy">
        <h3>Creative agency delivery pipeline</h3>
        <p>A creative shop needed to ship campaign visuals quickly across many client projects while keeping naming and retrieval consistent.</p>
        <ul>
          <li><strong>Challenge:</strong> High volume of deliverables and mixed asset sources during production.</li>
          <li><strong>Approach:</strong> Folders per client, tags per campaign wave, then search and sort for daily work.</li>
          <li><strong>Outcome:</strong> Faster retrieval on review calls, fewer duplicate uploads, less chasing for “the final” file.</li>
        </ul>
        <a class="ikpr-features-cta" href="/features.php">Explore features</a>
      </div>
      <?php ikpr_marketing_render_illustration('Graphic suggesting multiple campaign deliverables in a grid', 'grid', 'green'); ?>
    </section>

    <section class="ikpr-features-row ikpr-features-row--flip">
      <div class="ikpr-features-copy">
        <h3>Content team publishing workflow</h3>
        <p>A content team was juggling images across local machines and chat threads, with no single place to prep for channels.</p>
        <ul>
          <li><strong>Challenge:</strong> Scattered sources and repeated prep for each publication.</li>
          <li><strong>Approach:</strong> Central library in ImageKpr, tags for channels, bulk actions for recurring updates.</li>
          <li><strong>Outcome:</strong> One source of truth for visuals and less prep time for newsletters, blogs, and social.</li>
        </ul>
        <a class="ikpr-features-cta" href="/">Open ImageKpr</a>
      </div>
      <?php ikpr_marketing_render_illustration('Graphic suggesting folder structure and organized assets', 'folders', 'green'); ?>
    </section>

    <section class="ikpr-features-row">
      <div class="ikpr-features-copy">
        <h3>Internal brand repository</h3>
        <p>Team members needed fast access to approved brand images without opening heavy design files for every pull.</p>
        <ul>
          <li><strong>Challenge:</strong> Ad-hoc requests to design for basic asset retrieval.</li>
          <li><strong>Approach:</strong> Curated folders of top assets and direct links in internal docs and decks.</li>
          <li><strong>Outcome:</strong> More consistent brand usage and fewer interruptions for routine image lookups.</li>
        </ul>
        <a class="ikpr-features-cta" href="/features.php">See how it works</a>
      </div>
      <?php ikpr_marketing_render_illustration('Graphic suggesting linked sharing of approved assets', 'link', 'green'); ?>
    </section>

    <p class="ikpr-doc-note ikpr-case-studies-note">These scenarios are illustrative and summarize common patterns for lightweight media operations — not endorsements or guarantees of results.</p>

    <section class="ikpr-features-bottom-cta" aria-labelledby="ikpr-case-studies-bottom-heading">
      <h2 id="ikpr-case-studies-bottom-heading">See the full capability set</h2>
      <p>Browse features, then sign in from the home page when your account is ready.</p>
      <a class="ikpr-features-cta ikpr-features-cta--primary" href="/features.php">View features</a>
    </section>
  </main>
  <?php
  require_once __DIR__ . '/inc/footer.php';
  imagekpr_render_footer();
  ?>
</body>
</html>
