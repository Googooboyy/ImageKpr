<?php
ob_start();
require_once __DIR__ . '/../inc/admin.php';
imagekpr_require_admin_html(1, 1);
$pageTitle = 'Admin — Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle, ENT_QUOTES, 'UTF-8'); ?></title>
  <link rel="stylesheet" href="../styles.css">
  <style>
    .admin-wrap { max-width: 960px; margin: 0 auto; padding: 1rem 1.5rem 2rem; }
    .admin-nav { display: flex; gap: 1rem; align-items: center; flex-wrap: wrap; padding: 0.75rem 0; border-bottom: 1px solid #ddd; margin-bottom: 1.25rem; }
    .admin-nav a { color: #1565c0; text-decoration: none; font-weight: 600; }
    .admin-nav a:hover { text-decoration: underline; }
    .admin-nav .admin-nav-spacer { flex: 1; min-width: 0; }
    .admin-muted { color: #666; font-size: 0.9rem; }
    .admin-badge { display: inline-block; padding: 0.2rem 0.5rem; background: #e3f2fd; border-radius: 4px; font-size: 0.75rem; color: #1565c0; }
  </style>
</head>
<body>
  <div class="admin-wrap">
    <nav class="admin-nav" aria-label="Admin">
      <span class="admin-badge">Admin</span>
      <a href="index.php" aria-current="page">Dashboard</a>
      <a href="config.php">Config</a>
      <span class="admin-nav-spacer"></span>
      <a href="../index.php">← Back to ImageKpr</a>
    </nav>
    <h1>Dashboard</h1>
    <p class="admin-muted">User stats, quotas, and bulk actions will ship in Phase 9 and later.</p>
  </div>
</body>
</html>
