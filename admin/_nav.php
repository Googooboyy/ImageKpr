<?php
/** @var string $adminNavCurrent 'dashboard'|'config' */
$cur = $adminNavCurrent ?? 'dashboard';
?>
<nav class="admin-nav" aria-label="Admin">
  <span class="admin-badge">Admin</span>
  <a href="index.php"<?php echo $cur === 'dashboard' ? ' aria-current="page"' : ''; ?>>Dashboard</a>
  <a href="config.php"<?php echo $cur === 'config' ? ' aria-current="page"' : ''; ?>>Config</a>
  <span class="admin-nav-spacer"></span>
  <a href="../index.php">← Back to ImageKpr</a>
</nav>
