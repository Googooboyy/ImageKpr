<?php
/** @var string $adminNavCurrent 'dashboard'|'config'|'plans'|'allowlist'|'updates' */
$cur = $adminNavCurrent ?? 'dashboard';
$allowlistModeLabel = imagekpr_allowlist_enforcement_enabled() ? 'Restricted' : 'Open';
?>
<nav class="admin-nav" aria-label="Admin">
  <span class="admin-badge">Admin</span>
  <a href="index.php"<?php echo $cur === 'dashboard' ? ' aria-current="page"' : ''; ?>>Dashboard</a>
  <a href="config.php"<?php echo $cur === 'config' ? ' aria-current="page"' : ''; ?>>Config</a>
  <a href="plans.php"<?php echo $cur === 'plans' ? ' aria-current="page"' : ''; ?>>Plans</a>
  <a href="allowlist.php"<?php echo $cur === 'allowlist' ? ' aria-current="page"' : ''; ?>>Allowlist <span class="admin-badge" title="Current allowlist access mode"><?php echo htmlspecialchars($allowlistModeLabel, ENT_QUOTES, 'UTF-8'); ?></span></a>
  <a href="updates.php"<?php echo $cur === 'updates' ? ' aria-current="page"' : ''; ?>>Updates</a>
  <span class="admin-nav-spacer"></span>
  <a href="../index.php">← Back to ImageKpr</a>
</nav>
