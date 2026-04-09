<?php
/** @var string $adminNavCurrent 'dashboard'|'config'|'allowlist'|'updates' */
$cur = $adminNavCurrent ?? 'dashboard';
?>
<nav class="admin-nav" aria-label="Admin">
  <span class="admin-badge">Admin</span>
  <a href="index.php"<?php echo $cur === 'dashboard' ? ' aria-current="page"' : ''; ?>>Dashboard</a>
  <a href="config.php"<?php echo $cur === 'config' ? ' aria-current="page"' : ''; ?>>Config</a>
  <a href="allowlist.php"<?php echo $cur === 'allowlist' ? ' aria-current="page"' : ''; ?>>Allowlist</a>
  <a href="updates.php"<?php echo $cur === 'updates' ? ' aria-current="page"' : ''; ?>>Updates</a>
  <span class="admin-nav-spacer"></span>
  <a href="../index.php">← Back to ImageKpr</a>
</nav>
