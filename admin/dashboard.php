<?php
// admin/dashboard.php - booking statistics
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

// Counts for the stat cards (SUM() returns NULL on an empty table, so cast to int)
$c = $pdo->query("SELECT COUNT(*) AS total,
                         SUM(booking_status = 'Pending')   AS pending,
                         SUM(booking_status = 'Confirmed') AS confirmed,
                         SUM(booking_status = 'Cancelled') AS cancelled
                  FROM bookings")->fetch();
$stats = [
    ['Total bookings', (int)$c['total'],     'dot-blue'],
    ['Confirmed',      (int)$c['confirmed'], 'dot-green'],
    ['Pending',        (int)$c['pending'],   'dot-amber'],
    ['Cancelled',      (int)$c['cancelled'], 'dot-red'],
];
$revenue     = (float)$pdo->query("SELECT COALESCE(SUM(total_amount), 0) FROM bookings WHERE booking_status = 'Confirmed'")->fetchColumn();
$activeRooms = (int)$pdo->query("SELECT COUNT(*) FROM rooms WHERE room_status = 'active'")->fetchColumn();
$guestCount  = (int)$pdo->query("SELECT COUNT(*) FROM users WHERE role = 'guest'")->fetchColumn();

// Bookings per month (by check-in date): 2 months back to 3 months ahead, cancelled ones excluded
$start  = (new DateTimeImmutable('first day of this month'))->modify('-2 months');
$months = [];
for ($i = 0; $i < 6; $i++) {
    $m = $start->modify("+$i months");
    $months[$m->format('Y-m')] = ['label' => $m->format('M'), 'count' => 0];
}
$stmt = $pdo->prepare("SELECT DATE_FORMAT(check_in, '%Y-%m') AS ym, COUNT(*) AS c
                       FROM bookings
                       WHERE check_in >= ? AND check_in < ? AND booking_status <> 'Cancelled'
                       GROUP BY ym");
$stmt->execute([$start->format('Y-m-d'), $start->modify('+6 months')->format('Y-m-d')]);
foreach ($stmt->fetchAll() as $row) {
    if (isset($months[$row['ym']])) {
        $months[$row['ym']]['count'] = (int)$row['c'];
    }
}
$maxCount = max(1, max(array_column($months, 'count')));

$recent = $pdo->query("SELECT b.booking_id, b.check_in, b.check_out, b.total_amount, b.booking_status, u.name AS guest, r.room_name
                       FROM bookings b
                       JOIN users u ON u.user_id = b.user_id
                       JOIN rooms r ON r.room_id = b.room_id
                       ORDER BY b.booking_date DESC, b.booking_id DESC LIMIT 5")->fetchAll();

$pageTitle = 'Dashboard';
$adminPage = 'dashboard';
include __DIR__ . '/../includes/admin_header.php';
?>
<h1 class="admin-title">Bookings Overview</h1>

<div class="stat-cards">
  <?php foreach ($stats as [$label, $value, $dot]): ?>
    <div class="stat-card">
      <div class="stat-value"><?= $value ?></div>
      <div class="stat-label"><span class="dot <?= $dot ?>"></span><?= e($label) ?></div>
    </div>
  <?php endforeach; ?>
</div>

<div class="stat-cards small">
  <div class="stat-card"><div class="stat-value"><?= e(money($revenue)) ?></div><div class="stat-label">Confirmed revenue</div></div>
  <div class="stat-card"><div class="stat-value"><?= $activeRooms ?></div><div class="stat-label">Active rooms</div></div>
  <div class="stat-card"><div class="stat-value"><?= $guestCount ?></div><div class="stat-label">Registered guests</div></div>
</div>

<div class="panel">
  <div class="panel-title">Bookings per month (by check-in date)</div>
  <div class="bar-chart" role="img" aria-label="Bar chart of bookings per month">
    <?php foreach ($months as $m): ?>
      <div class="bar-col">
        <span class="bar-count"><?= $m['count'] ?></span>
        <div class="bar" style="height: <?= $m['count'] > 0 ? max(6, round($m['count'] / $maxCount * 100)) : 2 ?>%"></div>
        <span class="bar-label"><?= e($m['label']) ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="panel">
  <div class="panel-title">Recent bookings <a class="panel-link" href="<?= BASE_URL ?>/admin/bookings.php">View all</a></div>
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Ref</th><th>Guest</th><th>Room</th><th>Dates</th><th>Amount</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($recent as $r): ?>
          <tr>
            <td><?= e(booking_ref((int)$r['booking_id'])) ?></td>
            <td><?= e($r['guest']) ?></td>
            <td><?= e($r['room_name']) ?></td>
            <td><?= e(date('j M', strtotime($r['check_in']))) ?> &ndash; <?= e(date('j M Y', strtotime($r['check_out']))) ?></td>
            <td><?= e(money($r['total_amount'])) ?></td>
            <td><span class="status status-<?= e(strtolower($r['booking_status'])) ?>"><?= e($r['booking_status']) ?></span></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$recent): ?><tr><td colspan="6" class="muted-cell">No bookings yet.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
