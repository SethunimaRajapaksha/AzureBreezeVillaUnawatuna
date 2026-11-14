<?php
// room.php - details page for one room
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';

$roomId = (int)($_GET['room_id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE room_id = ? AND room_status = 'active'");
$stmt->execute([$roomId]);
$room = $stmt->fetch();

if (!$room) {
    http_response_code(404);
    $pageTitle = 'Room not found';
    $activePage = 'rooms';
    include __DIR__ . '/includes/header.php';
    echo '<section class="section"><div class="container empty-state"><h3>Room not found</h3><p>This room does not exist or is not available.</p><a class="btn btn-primary" href="' . BASE_URL . '/rooms.php">Back to rooms</a></div></section>';
    include __DIR__ . '/includes/footer.php';
    exit;
}

$checkIn  = trim($_GET['check_in'] ?? '');
$checkOut = trim($_GET['check_out'] ?? '');
$guests   = max(1, (int)($_GET['guests'] ?? 1));
$errors   = [];
$stayInfo = null;
if ($checkIn !== '' && $checkOut !== '' && validate_stay($checkIn, $checkOut, $guests, $errors)) {
    $nights   = nights_between($checkIn, $checkOut);
    $stayInfo = [
        'nights'    => $nights,
        'total'     => $room['price_per_night'] * $nights,
        'available' => $guests <= $room['max_guests'] && room_is_available($pdo, $roomId, $checkIn, $checkOut),
    ];
}
$roomReviews = latest_reviews($pdo, 10, $roomId);
$roomRating  = room_rating_stats($pdo)[$roomId] ?? null;
$bookQuery = $stayInfo ? '&check_in=' . urlencode($checkIn) . '&check_out=' . urlencode($checkOut) . '&guests=' . $guests : '';

$pageTitle  = $room['room_name'];
$activePage = 'rooms';
include __DIR__ . '/includes/header.php';
?>
<section class="section">
  <div class="container room-detail">
    <div class="room-detail-photo"><img src="<?= BASE_URL ?>/assets/images/rooms/<?= e($room['image']) ?>" alt="<?= e($room['room_name']) ?>"></div>
    <div class="room-detail-info">
      <div class="eyebrow"><?= e(strtoupper($room['room_type'])) ?></div>
      <h1><?= e($room['room_name']) ?></h1>
      <div class="rating-line"><?php if ($roomRating): ?><?= stars_html($roomRating['avg']) ?> <?= number_format($roomRating['avg'], 1) ?> (<?= $roomRating['count'] ?> review<?= $roomRating['count'] === 1 ? '' : 's' ?>)<?php else: ?>No reviews yet<?php endif; ?></div>
      <p class="lead-text"><?= e($room['description']) ?></p>
      <div class="room-meta"><span>Up to <?= (int)$room['max_guests'] ?> guests</span><span><?= e($room['bed_info']) ?></span></div>
      <h3>Facilities</h3>
      <ul class="facility-list big">
        <?php foreach (facility_list($room['facilities']) as $f): ?><li><?= e($f) ?></li><?php endforeach; ?>
      </ul>
      <div class="price big"><?= money($room['price_per_night']) ?> <small>/ night</small></div>

      <?php if ($stayInfo): ?>
        <?php if ($stayInfo['available']): ?>
          <p class="alert alert-success">Available for your dates: <?= $stayInfo['nights'] ?> night<?= $stayInfo['nights'] === 1 ? '' : 's' ?>, total <?= money($stayInfo['total']) ?>.</p>
        <?php else: ?>
          <p class="alert alert-error">Sorry, this room is not available for your selected dates or guests.</p>
        <?php endif; ?>
      <?php endif; ?>

      <div class="card-actions">
        <a class="btn btn-primary" href="<?= BASE_URL ?>/guest/book.php?room_id=<?= $roomId . $bookQuery ?>">Book This Room</a>
        <a class="btn btn-outline-dark" href="<?= BASE_URL ?>/rooms.php">Back to rooms</a>
      </div>
    </div>
  </div>
</section>
<?php if ($roomReviews): ?>
<section class="section band-soft">
  <div class="container">
    <h2 class="section-title">Guest reviews</h2>
    <div class="card-grid three">
      <?php foreach ($roomReviews as $rv): ?>
        <article class="review-card">
          <?= stars_html((float)$rv['rating']) ?>
          <p class="review-text">&ldquo;<?= e($rv['comment']) ?>&rdquo;</p>
          <div class="review-author"><?= e(display_name($rv['guest_name'])) ?> &middot; <?= e(date('M Y', strtotime($rv['created_at']))) ?></div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
