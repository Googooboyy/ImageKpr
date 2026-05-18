<?php
declare(strict_types=1);

/**
 * @param array{id:int, email:string, name:string} $row
 */
function admin_drilldown_user_cell(array $row): void
{
  $emailEsc = htmlspecialchars((string) $row['email'], ENT_QUOTES, 'UTF-8');
  $nameRaw = trim((string) ($row['name'] ?? ''));
  echo '<span class="admin-drilldown-email">' . $emailEsc . '</span>';
  if ($nameRaw !== '') {
    echo '<span class="admin-drilldown-name">' . htmlspecialchars($nameRaw, ENT_QUOTES, 'UTF-8') . '</span>';
  }
}

function admin_drilldown_find_cell(int $userId, string $email): void
{
  $href = htmlspecialchars(imagekpr_admin_drilldown_find_href($userId, $email), ENT_QUOTES, 'UTF-8');
  echo '<a class="admin-drilldown-find" href="' . $href . '">Find in list</a>';
}

/** @var array<int, array<string, mixed>> $expiredGraceList */
/** @var array<int, array{count:int, max_bytes:int}> $expiredGraceOversized */
/** @var array<int, array<string, mixed>> $overQuotaList */
/** @var array<string, array<int, array<string, mixed>>> $usersByTier */
/** @var array<int, array<string, mixed>> $largestUsersList */

$drilldownPanels = [
  'overQuota' => [
    'title' => 'Over quota',
    'lead' => 'Users using more library storage than their effective quota cap. Consider upgrade, cleanup, or a higher storage preset.',
    'empty' => 'No users are over quota right now.',
    'foot' => 'Suggested actions: contact the user, apply a higher plan preset, or help them delete files to get under cap.',
  ],
  'tierFree' => [
    'title' => 'Free users',
    'lead' => 'Users resolved to the Free tier (storage + upload limits matched to the plan matrix). Custom caps are listed separately in the table below.',
    'empty' => 'No Free-tier users right now.',
    'foot' => 'Use plan presets in the user table below to change tier.',
  ],
  'tierSilver' => [
    'title' => 'Silver users',
    'lead' => 'Users resolved to the Silver tier (storage + upload limits matched to the plan matrix).',
    'empty' => 'No Silver-tier users right now.',
    'foot' => 'Use plan presets in the user table below to change tier.',
  ],
  'tierGold' => [
    'title' => 'Gold users',
    'lead' => 'Users resolved to the Gold tier (storage + upload limits matched to the plan matrix).',
    'empty' => 'No Gold-tier users right now.',
    'foot' => 'Use plan presets in the user table below to change tier.',
  ],
  'tierPlatinum' => [
    'title' => 'Platinum users',
    'lead' => 'Users resolved to the Platinum tier (storage + upload limits matched to the plan matrix).',
    'empty' => 'No Platinum-tier users right now.',
    'foot' => 'Use plan presets in the user table below to change tier.',
  ],
  'largestUsers' => [
    'title' => 'Largest users',
    'lead' => 'Top accounts by total image library storage (up to 50). The stat card preview shows the top 5.',
    'empty' => 'No users with stored images yet.',
    'foot' => null,
  ],
  'graceExpired' => [
    'title' => 'Upload grace expired',
    'lead' => 'These users had their per-file upload limit lowered. The '
      . (int) imagekpr_upload_tier_grace_days()
      . '-day grace period has ended, so files larger than their current limit are hidden from their gallery and shared-dashboard caps may have tightened.',
    'empty' => 'No users are in this state right now.',
    'foot' => 'Suggested actions: contact the user, help them delete or replace oversized files, or restore a higher upload tier if the downgrade was accidental.',
  ],
];
?>
<div class="admin-info-modal" id="admin-stat-drilldown-modal" hidden aria-hidden="true">
  <div class="admin-info-modal-backdrop" id="admin-stat-drilldown-backdrop"></div>
  <div class="admin-info-modal-card admin-drilldown-modal-card" role="dialog" aria-modal="true" aria-labelledby="admin-stat-drilldown-title">
    <div class="admin-info-modal-top">
      <h2 class="admin-info-modal-title" id="admin-stat-drilldown-title"></h2>
      <button type="button" class="admin-info-modal-close" id="admin-stat-drilldown-close" aria-label="Close">Close</button>
    </div>
    <div class="admin-drilldown-modal-body" id="admin-stat-drilldown-body">
      <?php foreach ($drilldownPanels as $panelId => $meta) { ?>
      <section class="admin-drilldown-panel" data-drilldown-panel="<?php echo htmlspecialchars($panelId, ENT_QUOTES, 'UTF-8'); ?>" hidden>
        <p class="admin-drilldown-lead"><?php echo htmlspecialchars((string) $meta['lead'], ENT_QUOTES, 'UTF-8'); ?></p>
        <?php
        if ($panelId === 'overQuota') {
          if (empty($overQuotaList)) {
            echo '<p class="admin-muted">' . htmlspecialchars((string) $meta['empty'], ENT_QUOTES, 'UTF-8') . '</p>';
          } else {
            ?>
        <div class="admin-drilldown-table-wrap">
          <table class="admin-drilldown-table">
            <thead>
              <tr>
                <th scope="col">User</th>
                <th scope="col">Plan</th>
                <th scope="col">Used</th>
                <th scope="col">Quota</th>
                <th scope="col">Over by</th>
                <th scope="col"></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($overQuotaList as $oq) {
                $uid = (int) $oq['id'];
                ?>
              <tr>
                <td><?php admin_drilldown_user_cell($oq); ?></td>
                <td><?php echo htmlspecialchars((string) $oq['tier_label'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars(imagekpr_format_bytes((int) $oq['used_bytes']), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars(imagekpr_format_bytes((int) $oq['eff_bytes']), ENT_QUOTES, 'UTF-8'); ?></td>
                <td class="admin-over"><?php echo htmlspecialchars(imagekpr_format_bytes((int) $oq['over_by_bytes']), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php admin_drilldown_find_cell($uid, (string) $oq['email']); ?></td>
              </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
            <?php
          }
        } elseif (str_starts_with($panelId, 'tier')) {
          $tierKey = match ($panelId) {
            'tierFree' => 'free',
            'tierSilver' => 'silver',
            'tierGold' => 'gold',
            'tierPlatinum' => 'platinum',
            default => 'free',
          };
          $tierRows = $usersByTier[$tierKey] ?? [];
          if ($tierRows === []) {
            echo '<p class="admin-muted">' . htmlspecialchars((string) $meta['empty'], ENT_QUOTES, 'UTF-8') . '</p>';
          } else {
            ?>
        <div class="admin-drilldown-table-wrap">
          <table class="admin-drilldown-table">
            <thead>
              <tr>
                <th scope="col">User</th>
                <th scope="col">Plan</th>
                <th scope="col">Storage</th>
                <th scope="col">Files</th>
                <th scope="col">Upload</th>
                <th scope="col"></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($tierRows as $tr) {
                $uid = (int) $tr['id'];
                ?>
              <tr>
                <td><?php admin_drilldown_user_cell($tr); ?></td>
                <td><?php echo htmlspecialchars((string) $tr['tier_label'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars(imagekpr_format_bytes((int) $tr['used_bytes']), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo (int) $tr['image_count']; ?></td>
                <td><?php echo (int) $tr['upload_mb']; ?> MB</td>
                <td><?php admin_drilldown_find_cell($uid, (string) $tr['email']); ?></td>
              </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
            <?php
          }
        } elseif ($panelId === 'largestUsers') {
          if (empty($largestUsersList)) {
            echo '<p class="admin-muted">' . htmlspecialchars((string) $meta['empty'], ENT_QUOTES, 'UTF-8') . '</p>';
          } else {
            ?>
        <div class="admin-drilldown-table-wrap">
          <table class="admin-drilldown-table">
            <thead>
              <tr>
                <th scope="col">#</th>
                <th scope="col">User</th>
                <th scope="col">Plan</th>
                <th scope="col">Storage</th>
                <th scope="col">Files</th>
                <th scope="col"></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($largestUsersList as $lu) {
                $uid = (int) $lu['id'];
                ?>
              <tr>
                <td><?php echo (int) $lu['rank']; ?></td>
                <td><?php admin_drilldown_user_cell($lu); ?></td>
                <td><?php echo htmlspecialchars((string) $lu['tier_label'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo htmlspecialchars(imagekpr_format_bytes((int) $lu['used_bytes']), ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo (int) $lu['image_count']; ?></td>
                <td><?php admin_drilldown_find_cell($uid, (string) $lu['email']); ?></td>
              </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
            <?php
          }
        } elseif ($panelId === 'graceExpired') {
          if (empty($expiredGraceList)) {
            echo '<p class="admin-muted">' . htmlspecialchars((string) $meta['empty'], ENT_QUOTES, 'UTF-8') . '</p>';
          } else {
            ?>
        <div class="admin-drilldown-table-wrap">
          <table class="admin-drilldown-table">
            <thead>
              <tr>
                <th scope="col">User</th>
                <th scope="col">Plan</th>
                <th scope="col">Upload limit</th>
                <th scope="col">Downgraded</th>
                <th scope="col">Grace ended</th>
                <th scope="col">Hidden files</th>
                <th scope="col"></th>
              </tr>
            </thead>
            <tbody>
              <?php foreach ($expiredGraceList as $eg) {
                $uid = (int) $eg['id'];
                $os = $expiredGraceOversized[$uid] ?? ['count' => 0, 'max_bytes' => 0];
                $osCount = (int) $os['count'];
                $osMax = (int) $os['max_bytes'];
                $downDisp = $eg['downgraded_at'] !== null ? htmlspecialchars((string) $eg['downgraded_at'], ENT_QUOTES, 'UTF-8') : '—';
                $graceEndDisp = '—';
                if ($eg['grace_ends_at'] !== null) {
                  $graceEndDisp = htmlspecialchars(date('Y-m-d H:i', (int) $eg['grace_ends_at']), ENT_QUOTES, 'UTF-8');
                }
                $daysEnded = $eg['days_since_grace_ended'];
                $daysEndedDisp = $daysEnded !== null ? (int) $daysEnded . 'd ago' : '';
                ?>
              <tr>
                <td><?php admin_drilldown_user_cell($eg); ?></td>
                <td><?php echo htmlspecialchars((string) $eg['tier_label'], ENT_QUOTES, 'UTF-8'); ?></td>
                <td><?php echo (int) $eg['upload_mb']; ?> MB</td>
                <td><?php echo $downDisp; ?></td>
                <td><?php echo $graceEndDisp; ?><?php if ($daysEndedDisp !== '') { ?><span class="admin-drilldown-sub"><?php echo htmlspecialchars($daysEndedDisp, ENT_QUOTES, 'UTF-8'); ?></span><?php } ?></td>
                <td class="<?php echo $osCount > 0 ? 'admin-over' : ''; ?>">
                  <?php if ($osCount > 0) { ?>
                    <?php echo (int) $osCount; ?> <span class="admin-drilldown-sub">(largest <?php echo htmlspecialchars(imagekpr_format_bytes($osMax), ENT_QUOTES, 'UTF-8'); ?>)</span>
                  <?php } else { ?>
                    <span class="admin-muted">0</span>
                  <?php } ?>
                </td>
                <td><?php admin_drilldown_find_cell($uid, (string) $eg['email']); ?></td>
              </tr>
              <?php } ?>
            </tbody>
          </table>
        </div>
            <?php
          }
        }
        if (!empty($meta['foot'])) {
          echo '<p class="admin-drilldown-foot admin-muted">' . htmlspecialchars((string) $meta['foot'], ENT_QUOTES, 'UTF-8') . '</p>';
        }
        ?>
      </section>
      <?php } ?>
    </div>
  </div>
</div>
<script type="application/json" id="admin-stat-drilldown-titles"><?php
  echo json_encode(array_map(static function (array $m): string {
    return (string) $m['title'];
  }, $drilldownPanels), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);
?></script>
