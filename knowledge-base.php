<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Knowledge Base - ImageKpr</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body class="ikpr-doc-page">
  <?php
  require_once __DIR__ . '/inc/page_hero.php';
  imagekpr_render_page_hero();
  ?>
  <main class="ikpr-doc-wrap ikpr-kb">
    <header class="ikpr-kb-intro">
      <h1>Knowledge Base</h1>
      <p class="ikpr-doc-lead">Operational guidance for getting the most from ImageKpr.</p>
    </header>

    <section class="ikpr-doc-card" aria-labelledby="kb-getting-started">
      <h2 id="kb-getting-started">Getting Started</h2>
      <ul>
        <li>Sign in from the main entry route using your approved account.</li>
        <li>Upload files through drag-and-drop or click-to-select in the upload zone.</li>
        <li>Use folders and tags immediately to keep the library searchable.</li>
      </ul>
    </section>

    <section class="ikpr-doc-card" aria-labelledby="kb-working">
      <h2 id="kb-working">Working with Images</h2>
      <div class="ikpr-doc-subsection">
        <h3>Upload and review</h3>
        <p>Upload confirmation lets you rename files and apply folders/tags before final import. Use this to enforce naming quality from the start.</p>
      </div>
      <div class="ikpr-doc-subsection">
        <h3>Search and filters</h3>
        <p>Combine search text, folder selection, and tag chips to narrow to the exact set of assets you need.</p>
      </div>
      <div class="ikpr-doc-subsection">
        <h3>Bulk operations</h3>
        <p>Selection mode supports batch delete, ZIP download, tags, folders, and rename workflows for high-volume curation tasks.</p>
      </div>
    </section>

    <section class="ikpr-doc-card" aria-labelledby="kb-sharing">
      <h2 id="kb-sharing">Sharing</h2>
      <ul>
        <li>Use copy-link actions to place assets directly into docs, chat, or production tickets.</li>
        <li>Use folder conventions to make recurring share bundles predictable.</li>
      </ul>
    </section>

    <section class="ikpr-doc-card" aria-labelledby="kb-slideshows">
      <h2 id="kb-slideshows">Slideshows</h2>
      <p>Slideshow mode is for review meetings and visual walkthroughs. Select the images you want, arrange them in the <strong>Selected</strong> strip (drag thumbnails left-to-right: start → end), then open <strong>Slideshow</strong> to configure and start.</p>
      <div class="ikpr-doc-subsection">
        <h3>Before you start</h3>
        <ul>
          <li><strong>Order</strong> — Only selected images play, in the order shown under Selected. Images that are selected but not visible in the current grid still appear if they stay selected.</li>
          <li><strong>Manual vs auto</strong> — Choose manual advance or timed auto-advance with a seconds-per-slide value (adjustable while playing in auto mode).</li>
          <li><strong>Loop</strong> — Optionally loop from the last slide back to the first. You can randomize the order on each loop for varied reviews.</li>
          <li><strong>On-screen options</strong> — Toggle a subtle filename caption, pick a transition (diffuse crossfade or fly-in), and set letterbox padding colour (black, white, or RGB) around images that do not fill the frame.</li>
        </ul>
      </div>
      <div class="ikpr-doc-subsection">
        <h3>Controls while playing</h3>
        <ul>
          <li><strong>Next / previous</strong> — <kbd>Space</kbd> advances; <kbd>←</kbd> and <kbd>→</kbd> move backward and forward.</li>
          <li><strong>Exit</strong> — <kbd>Esc</kbd>, the × control, or click outside the image (on the letterbox area).</li>
          <li><strong>Auto mode only</strong> — <kbd>Enter</kbd> pauses or resumes. <kbd>↑</kbd> and <kbd>↓</kbd> increase or decrease seconds per slide by one second (the player shows the new interval).</li>
        </ul>
      </div>
    </section>

  </main>
  <?php
  require_once __DIR__ . '/inc/footer.php';
  imagekpr_render_footer();
  ?>
</body>
</html>
