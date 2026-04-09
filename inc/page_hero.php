<?php
declare(strict_types=1);

function imagekpr_render_page_hero(): void
{
  ?>
  <header class="ikpr-page-hero" role="banner">
    <div class="ikpr-page-hero-inner">
      <img src="/assets/imagekpr-logo.png" alt="ImageKpr" width="500" height="273" onerror="this.style.display='none';">
    </div>
  </header>
  <?php
}
