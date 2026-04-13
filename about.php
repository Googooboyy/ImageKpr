<?php
declare(strict_types=1);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>About - ImageKpr</title>
  <link rel="stylesheet" href="styles.css">
</head>
<body class="ikpr-doc-page">
  <?php
  require_once __DIR__ . '/inc/page_hero.php';
  imagekpr_render_page_hero();
  ?>
  <main class="ikpr-doc-wrap">
    <h1>About ImageKpr</h1>
    <p class="ikpr-doc-lead">Hi, I’m Mar. I’m a graphic designer at heart, an animator who lives for great motion and illustration, and someone who is always collecting visuals I want to keep, reuse, and share—online and for personal projects.</p>

    <p>Here’s the honest version of how ImageKpr came to be. If you work in design or animation, you probably know the feeling: you save something brilliant—a graphic, a frame, a piece of reference art—and weeks later you can’t remember where it lives. Was it a download? A screenshot? A folder on another machine? That little “where did I put that?” loop steals time and mental energy, and it kept happening to me.</p>

    <p>I looked around for something that felt <em>simple</em>: fast to use, easy to trust, and built for people who think in images, not spreadsheets. When I couldn’t find the right fit, the web designer in me did what it always does—it started sketching a better workflow. That itch became ImageKpr: a straightforward home for the images and animation-inspired work you care about, so you can find them again without playing detective.</p>

    <p>I shared early versions with friends and fellow creatives, listened closely, and kept refining the experience. Accessibility matters to me, so ImageKpr stays <strong>free to use</strong> for anyone who needs a dependable place to store and organize images. If you outgrow the basics and want a little more room to grow, <strong>upgrades are there</strong> when you’re ready—no pressure, just options.</p>

    <p>My hope is simple: help you free up brain space you used to spend remembering file paths and stray tabs, and give you a calm “second brain” for images instead. If ImageKpr makes your day a little easier, that’s exactly why I built it. If you ever want to say hello or share feedback, I’d love to hear from you.</p>

    <h2>What ImageKpr does</h2>
    <p>ImageKpr is a personal image repository for anyone who needs to upload, organize, find, and share images quickly.</p>
    <ul>
        <li>Privacy-first — sign in with Google; only you can access your personal gallery.</li>
        <li>Bulk upload, organize and share private link images in one place.</li>
        <li>Share private image links — but only if you want to.</li>
        <li>Folders and tags to keep your collection tidy.</li>
        <li>Search your library; run slideshows and presentations.</li>
        <li>Zip batches of images and download for distribution.</li>
      </ul>
    <h2>What It Is Built For</h2>
    <ul>
      <li>Keeping a shared image library easy to navigate through folders, tags, and search.</li>
      <li>Moving fast with drag-and-drop uploads, bulk actions, and copyable links.</li>
      <li>Supporting a lightweight workflow for agencies, marketers, and day-to-day operations.</li>
    </ul>

    <h2>Core Product Principles</h2>
    <h3>1. Speed over ceremony</h3>
    <p>Common tasks should take one or two steps. Uploading, tagging, and grabbing a usable link should be immediate.</p>
    <h3>2. Clarity over complexity</h3>
    <p>The UI favors obvious controls and clear feedback, with plain language for actions and system states.</p>
    <h3>3. Controlled sharing</h3>
    <p>Sign-in and allowlist controls let you choose who has access without slowing down everyday collaboration.</p>

    <h2>Typical use cases</h2>
    <ul>
      <li>Marketing operations libraries for campaign assets.</li>
      <li>Internal repositories for social, blog, and email imagery.</li>
      <li>Client handoff libraries where quick retrieval matters more than deep media governance.</li>
    </ul>

    <p class="ikpr-doc-note">ImageKpr is intentionally framework-light and hosting-friendly, making it straightforward to deploy and maintain in environments where reliability and simplicity are key requirements.</p>
  </main>
  <?php
  require_once __DIR__ . '/inc/footer.php';
  imagekpr_render_footer();
  ?>
</body>
</html>
