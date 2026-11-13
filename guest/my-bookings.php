<?php
// guest/my-bookings.php - a guest's own bookings, with cancel for pending ones
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
if (is_admin()) {
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}
$userId = (int)$_SESSION['user_id'];

// Cancel a pending booking (POST only). The WHERE clause checks ownership AND status in the database.
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'cancel') {
    if (!csrf_valid()) {
        flash('error', 'Your session expired. Please try again.');
    } else {
        $cancel = $pdo->prepare("UPDATE bookings SET booking_status = 'Cancelled'
                                 WHERE booking_id = ? AND user_id = ? AND booking_status = 'Pending'");
        $cancel->execute([(int)($_POST['booking_id'] ?? 0), $userId]);
        if ($cancel->rowCount() === 1) {
            flash('success', 'Your booking has been cancelled.');
        } else {
            flash('error', 'That booking could not be cancelled. Only your own pending bookings can be cancelled.');
        }
    }
    header('Location: ' . BASE_URL . '/guest/my-bookings.php');
    exit;
}

// Status filter (whitelist, never put raw input into SQL)
$allowed = ['Pending', 'Confirmed', 'Cancelled'];
$filter  = in_array($_GET['status'] ?? '', $allowed, true) ? $_GET['status'] : '';

$sql    = 'SELECT b.*, r.room_name, r.image FROM bookings b
           JOIN rooms r ON r.room_id = b.room_id
           WHERE b.user_id = :uid';
$params = [':uid' => $userId];
if ($filter !== '') {
    $sql .= ' AND b.booking_status = :status';
    $params[':status'] = $filter;
}
$stmt = $pdo->prepare($sql . ' ORDER BY b.booking_date DESC, b.booking_id DESC');
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$pageTitle  = 'My Bookings';
$activePage = '';
include __DIR__ . '/../includes/header.php';
?>
<section class="section">
  <div class="container">
    <h1 class="section-title">My Bookings</h1>

    <div class="tabs">
      <a href="?" class="<?= $filter === '' ? 'active' : '' ?>">All</a>
      <?php foreach ($allowed as $s): ?>
        <a href="?status=<?= $s ?>" class="<?= $filter === $s ? 'active' : '' ?>"><?= $s ?></a>
      <?php endforeach; ?>
    </div>

    <?php if (!$bookings): ?>
      <div class="empty-state">
        <h3>No bookings yet</h3>
        <p>When you book a room, it will appear here.</p>
        <a class="btn btn-primary" href="<?= BASE_URL ?>/rooms.php">Browse rooms</a>
      </div>
    <?php endif; ?>

    <?php foreach ($bookings as $b): ?>
      <?php $nights = nights_between($b['check_in'], $b['check_out']); ?>
      <article class="booking-item">
        <div class="booking-item-photo"><img src="<?= BASE_URL ?>/assets/images/rooms/<?= e($b['image']) ?>" alt="<?= e($b['room_name']) ?>"></div>
        <div class="booking-item-body">
          <div class="booking-item-head">
            <h3><?= e($b['room_name']) ?></h3>
            <span class="status status-<?= e(strtolower($b['booking_status'])) ?>"><?= e($b['booking_status']) ?></span>
          </div>
          <p class="booking-meta">
            #BK-<?= str_pad((string)$b['booking_id'], 4, '0', STR_PAD_LEFT) ?> &middot;
            <?= e(date('j M Y', strtotime($b['check_in']))) ?> &ndash; <?= e(date('j M Y', strtotime($b['check_out']))) ?>
            (<?= $nights ?> night<?= $nights === 1 ? '' : 's' ?>) &middot; <?= (int)$b['guests'] ?> guest<?= (int)$b['guests'] === 1 ? '' : 's' ?>
          </p>
          <p class="booking-total"><?= money($b['total_amount']) ?></p>
          <?php if ($b['booking_status'] === 'Pending'): ?>
            <form method="post" action="" data-confirm="Cancel this booking request?">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="cancel">
              <input type="hidden" name="booking_id" value="<?= (int)$b['booking_id'] ?>">
              <button type="submit" class="btn btn-danger btn-sm">Cancel booking</button>
            </form>
          <?php endif; ?>
        </div>
      </article>
    <?php endforeach; ?>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
