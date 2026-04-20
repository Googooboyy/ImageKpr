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
        <h3>Supported file formats</h3>
        <p>ImageKpr accepts <strong>JPEG, PNG, GIF, and WebP</strong> for all plans. <strong>Paid users (Silver, Gold, Platinum)</strong> can also upload <strong>MP4</strong> clips (up to your plan's per-file limit).</p>
        <p>The following common formats are <strong>not supported</strong> and will be skipped on upload:</p>
        <ul>
          <li><strong>HEIC / HEIF</strong> — default photo format on iPhones and many Android devices. Convert to JPEG before uploading (most phones offer a compatibility setting, or use a free converter).</li>
          <li><strong>AVIF</strong> — newer web-delivery format. Supported automatically if the server has libavif compiled in (PHP 8.1+); if not, export as WebP or JPEG instead.</li>
          <li><strong>SVG</strong> — vector graphics, not a raster image.</li>
          <li><strong>BMP, TIFF, ICO</strong> — legacy or specialised formats rarely needed in day-to-day image libraries.</li>
          <li><strong>MP4 on Free</strong> — video upload is available on paid plans only. Free users get a friendly prompt to continue with photos only or cancel.</li>
        </ul>
        <p>If you drag or select a mix of supported and unsupported files, you'll get a heads-up before anything is sent so you can cancel or continue with only the accepted files.</p>
      </div>
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
      <p>Slideshow mode is for review meetings and visual walkthroughs. Select the files you want, arrange them in the <strong>Selected</strong> strip (drag thumbnails left-to-right: start → end), then open <strong>Slideshow</strong> to configure and start.</p>
      <div class="ikpr-doc-subsection">
        <h3>Before you start</h3>
        <ul>
          <li><strong>Order</strong> — Only selected files play, in the order shown under Selected. Selected files that are not visible in the current grid still appear if they stay selected.</li>
          <li><strong>Mixed media timing</strong> — In Auto mode, images use your seconds-per-slide setting; MP4 clips advance when the clip ends. In Manual mode, you can move slide-by-slide and use native video controls.</li>
          <li><strong>Loop</strong> — Optionally loop from the last slide back to the first.</li>
          <li><strong>Randomize order</strong> — Shuffle at any time, with or without Loop. Turn it off to restore your Selected order. With Loop on, the deck reshuffles each time you wrap from last to first.</li>
          <li><strong>On-screen options</strong> — Toggle a subtle filename caption, pick a transition (diffuse crossfade or fly-in), and set letterbox padding colour (black, white, or RGB). Transitions involving MP4 clips use an instant cut for smoother playback.</li>
        </ul>
      </div>
      <div class="ikpr-doc-subsection">
        <h3>Controls while playing</h3>
        <ul>
          <li><strong>Next / previous</strong> — <kbd>Space</kbd> advances on images and play/pauses on videos; <kbd>←</kbd> and <kbd>→</kbd> move backward and forward. <kbd>Home</kbd> and <kbd>End</kbd> jump to the first or last slide in the current order.</li>
          <li><strong>Replay on end</strong> — <kbd>O</kbd> toggles whether the current MP4 starts again when it finishes (instead of advancing). With Auto on, replay takes priority over auto-advance until you turn it off.</li>
          <li><strong>Randomize</strong> — <kbd>R</kbd> or the RAND control toggles shuffle; turn off to restore Selected order.</li>
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
