<?php
// admin/bookings.php - view all bookings, confirm (with overlap check) or cancel
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

/** Confirm a pending booking unless another CONFIRMED booking already holds those dates. */
function confirm_booking(PDO $pdo, int $bookingId): array
{
    try {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare('SELECT * FROM bookings WHERE booking_id = ? FOR UPDATE');
        $stmt->execute([$bookingId]);
        $b = $stmt->fetch();
        if (!$b) {
            $pdo->rollBack();
            return ['error', 'Booking not found.'];
        }
        if ($b['booking_status'] !== 'Pending') {
            $pdo->rollBack();
            return ['error', 'Only pending bookings can be confirmed.'];
        }
        // Lock the room so two admins cannot confirm clashing bookings at the same moment
        $lock = $pdo->prepare('SELECT room_id FROM rooms WHERE room_id = ? FOR UPDATE');
        $lock->execute([$b['room_id']]);

        $clash = find_conflicting_booking($pdo, (int)$b['room_id'], $b['check_in'], $b['check_out'], $bookingId);
        if ($clash) {
            $pdo->rollBack();
            return ['error', 'Cannot confirm ' . booking_ref($bookingId) . ': ' . $clash['room_name'] . ' is already booked from '
                . date('j M Y', strtotime($clash['check_in'])) . ' to ' . date('j M Y', strtotime($clash['check_out']))
                . ' (' . booking_ref((int)$clash['booking_id']) . ').'];
        }
        $pdo->prepare("UPDATE bookings SET booking_status = 'Confirmed' WHERE booking_id = ?")->execute([$bookingId]);
        $pdo->commit();
        return ['success', booking_ref($bookingId) . ' has been confirmed.'];
    } catch (PDOException $ex) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        error_log('Confirm failed: ' . $ex->getMessage());
        return ['error', 'Something went wrong. Please try again.'];
    }
}

// Handle Confirm / Cancel (POST only, CSRF protected), then redirect so a refresh does not repeat it
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id     = (int)($_POST['booking_id'] ?? 0);
    $action = $_POST['action'] ?? '';
    if (!csrf_valid()) {
        flash('error', 'Your session expired. Please try again.');
    } elseif ($action === 'confirm') {
        [$type, $msg] = confirm_booking($pdo, $id);
        flash($type, $msg);
    } elseif ($action === 'cancel') {
        $stmt = $pdo->prepare("UPDATE bookings SET booking_status = 'Cancelled'
                               WHERE booking_id = ? AND booking_status IN ('Pending', 'Confirmed')");
        $stmt->execute([$id]);
        $stmt->rowCount() === 1
            ? flash('success', booking_ref($id) . ' has been cancelled.')
            : flash('error', 'That booking could not be cancelled.');
    }
    header('Location: ' . BASE_URL . '/admin/bookings.php' . (!empty($_POST['return']) ? '?' . ltrim((string)$_POST['return'], '?') : ''));
    exit;
}

// Filter by status and search by guest name, email or booking reference
$allowed = ['Pending', 'Confirmed', 'Cancelled'];
$filter  = in_array($_GET['status'] ?? '', $allowed, true) ? $_GET['status'] : '';
$q       = trim($_GET['q'] ?? '');

$sql    = "SELECT b.*, u.name AS guest_name, u.email AS guest_email, r.room_name
           FROM bookings b
           JOIN users u ON u.user_id = b.user_id
           JOIN rooms r ON r.room_id = b.room_id
           WHERE 1 = 1";
$params = [];
if ($filter !== '') {
    $sql .= ' AND b.booking_status = :status';
    $params[':status'] = $filter;
}
if ($q !== '') {
    $qid = preg_match('/^#?(?:BK-)?0*(\d+)$/i', $q, $m) ? (int)$m[1] : 0;
    $sql .= ' AND (u.name LIKE :q1 OR u.email LIKE :q2 OR b.booking_id = :qid)';
    $params[':q1']  = '%' . $q . '%';
    $params[':q2']  = '%' . $q . '%';
    $params[':qid'] = $qid;
}
$stmt = $pdo->prepare($sql . ' ORDER BY b.booking_date DESC, b.booking_id DESC');
$stmt->execute($params);
$bookings = $stmt->fetchAll();

$counts = ['' => 0, 'Pending' => 0, 'Confirmed' => 0, 'Cancelled' => 0];
foreach ($pdo->query('SELECT booking_status, COUNT(*) AS c FROM bookings GROUP BY booking_status')->fetchAll() as $row) {
    $counts[$row['booking_status']] = (int)$row['c'];
    $counts[''] += (int)$row['c'];
}
$returnQuery = http_build_query(array_filter(['status' => $filter, 'q' => $q]));

$pageTitle = 'Manage Bookings';
$adminPage = 'bookings';
include __DIR__ . '/../includes/admin_header.php';
?>
<h1 class="admin-title">Manage Bookings</h1>

<div class="toolbar">
  <div class="tabs">
    <a href="?" class="<?= $filter === '' ? 'active' : '' ?>">All (<?= $counts[''] ?>)</a>
    <?php foreach ($allowed as $s): ?>
      <a href="?status=<?= $s ?>" class="<?= $filter === $s ? 'active' : '' ?>"><?= $s ?> (<?= $counts[$s] ?>)</a>
    <?php endforeach; ?>
  </div>
  <form method="get" class="search-form">
    <?php if ($filter !== ''): ?><input type="hidden" name="status" value="<?= e($filter) ?>"><?php endif; ?>
    <input type="search" name="q" value="<?= e($q) ?>" placeholder="Search guest, email or ref" aria-label="Search bookings">
    <button class="btn btn-primary btn-sm" type="submit">Search</button>
  </form>
</div>

<div class="panel">
  <div class="table-wrap">
    <table class="data-table">
      <thead><tr><th>Ref</th><th>Guest</th><th>Room</th><th>Dates</th><th>Guests</th><th>Amount</th><th>Status</th><th>Action</th></tr></thead>
      <tbody>
        <?php foreach ($bookings as $b): ?>
          <tr>
            <td><?= e(booking_ref((int)$b['booking_id'])) ?></td>
            <td><strong><?= e($b['guest_name']) ?></strong><br><span class="muted"><?= e($b['guest_email']) ?></span></td>
            <td><?= e($b['room_name']) ?></td>
            <td><?= e(date('j M', strtotime($b['check_in']))) ?> &ndash; <?= e(date('j M Y', strtotime($b['check_out']))) ?></td>
            <td><?= (int)$b['guests'] ?></td>
            <td><?= e(money($b['total_amount'])) ?></td>
            <td><span class="status status-<?= e(strtolower($b['booking_status'])) ?>"><?= e($b['booking_status']) ?></span></td>
            <td>
              <div class="row-actions">
                <?php if ($b['booking_status'] === 'Pending'): ?>
                  <form method="post" action=""><?= csrf_field() ?>
                    <input type="hidden" name="action" value="confirm"><input type="hidden" name="booking_id" value="<?= (int)$b['booking_id'] ?>"><input type="hidden" name="return" value="<?= e($returnQuery) ?>">
                    <button class="btn btn-primary btn-sm" type="submit">Confirm</button></form>
                <?php endif; ?>
                <?php if ($b['booking_status'] !== 'Cancelled'): ?>
                  <form method="post" action="" data-confirm="Cancel booking <?= e(booking_ref((int)$b['booking_id'])) ?>?"><?= csrf_field() ?>
                    <input type="hidden" name="action" value="cancel"><input type="hidden" name="booking_id" value="<?= (int)$b['booking_id'] ?>"><input type="hidden" name="return" value="<?= e($returnQuery) ?>">
                    <button class="btn btn-danger btn-sm" type="submit">Cancel</button></form>
                <?php else: ?><span class="muted">No actions</span><?php endif; ?>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$bookings): ?><tr><td colspan="8" class="muted-cell">No bookings found.</td></tr><?php endif; ?>
      </tbody>
    </table>
  </div>
</div>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
