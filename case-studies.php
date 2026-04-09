<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Case Studies - ImageKpr</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body class="ikpr-doc-page">
  <main class="ikpr-doc-wrap">
    <h1>Case Studies</h1>
    <p class="ikpr-doc-lead">Representative examples of how teams use ImageKpr to simplify image operations and reduce delivery friction.</p>

    <h2>Creative Agency Delivery Pipeline</h2>
    <p><strong>Challenge:</strong> A creative agency needed to deliver campaign visuals quickly across many client projects while keeping naming and retrieval consistent.</p>
    <p><strong>Approach:</strong> They used folders per client and tags per campaign wave, then relied on search + sort for daily production.</p>
    <p><strong>Outcome:</strong> Faster retrieval during review calls, fewer duplicate uploads, and less back-and-forth over missing assets.</p>

    <h2>Content Team Publishing Workflow</h2>
    <p><strong>Challenge:</strong> A content team struggled with scattered image sources across local machines and chat threads.</p>
    <p><strong>Approach:</strong> They centralized assets in ImageKpr, used tags to denote publication channels, and used bulk operations for recurring updates.</p>
    <p><strong>Outcome:</strong> A consistent source of truth for visuals and reduced prep time for newsletters, blogs, and social posts.</p>

    <h2>Internal Brand Repository</h2>
    <p><strong>Challenge:</strong> Team members needed quick access to approved brand images without opening large design files.</p>
    <p><strong>Approach:</strong> They curated top assets into stable folders and used direct links for internal docs and presentations.</p>
    <p><strong>Outcome:</strong> More consistent brand usage and fewer requests to design for basic asset retrieval.</p>

    <p class="ikpr-doc-note">These scenarios are illustrative and summarize common implementation patterns observed in lightweight media operations.</p>
  </main>
  <?php
  require_once __DIR__ . '/inc/footer.php';
  imagekpr_render_footer();
  ?>
</body>
</html>
