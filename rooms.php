<?php
// rooms.php - room list, availability search and filters
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/functions.php';
$pageTitle  = 'Rooms';
$activePage = 'rooms';

$checkIn  = trim($_GET['check_in'] ?? '');
$checkOut = trim($_GET['check_out'] ?? '');
$guests   = max(1, (int)($_GET['guests'] ?? 1));
$type     = trim($_GET['type'] ?? '');
$searched = ($checkIn !== '' || $checkOut !== '');

$errors = [];
$nights = 0;
$rooms  = [];

$ratings = room_rating_stats($pdo);
$types = $pdo->query("SELECT DISTINCT room_type FROM rooms WHERE room_status = 'active' ORDER BY room_type")->fetchAll(PDO::FETCH_COLUMN);

if ($searched) {
    if (validate_stay($checkIn, $checkOut, $guests, $errors)) {
        $nights = nights_between($checkIn, $checkOut);
        $rooms  = find_available_rooms($pdo, $checkIn, $checkOut, $guests, $type);
    }
} else {
    $rooms = list_active_rooms($pdo, $type);
}
$validSearch = $searched && !$errors;
$stayQuery   = $validSearch ? '&check_in=' . urlencode($checkIn) . '&check_out=' . urlencode($checkOut) . '&guests=' . $guests : '';

include __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <img src="<?= BASE_URL ?>/assets/images/aerial.jpg" alt="">
  <div class="page-hero-text">
    <h1>Our Rooms</h1>
    <p>Find your perfect stay in Unawatuna</p>
  </div>
</section>

<div class="search-wrap">
  <form class="search-bar" action="" method="get" novalidate>
    <div class="field"><label for="check_in">Check-in</label><input type="date" id="check_in" name="check_in" value="<?= e($checkIn) ?>"></div>
    <div class="field"><label for="check_out">Check-out</label><input type="date" id="check_out" name="check_out" value="<?= e($checkOut) ?>"></div>
    <div class="field"><label for="guests">Guests</label>
      <select id="guests" name="guests">
        <?php for ($g = 1; $g <= 6; $g++): ?><option value="<?= $g ?>" <?= $g === $guests ? 'selected' : '' ?>><?= $g ?> Guest<?= $g > 1 ? 's' : '' ?></option><?php endfor; ?>
      </select>
    </div>
    <div class="field"><label for="type">Room type</label>
      <select id="type" name="type">
        <option value="">All rooms</option>
        <?php foreach ($types as $t): ?><option value="<?= e($t) ?>" <?= $t === $type ? 'selected' : '' ?>><?= e($t) ?></option><?php endforeach; ?>
      </select>
    </div>
    <button class="btn btn-primary" type="submit">Search</button>
  </form>
</div>

<section class="section">
  <div class="container">
    <?php foreach ($errors as $err): ?><div class="alert alert-error" role="alert"><?= e($err) ?></div><?php endforeach; ?>

    <p class="results-note">
      <?php if ($validSearch): ?>
        <strong><?= count($rooms) ?></strong> room<?= count($rooms) === 1 ? '' : 's' ?> available for
        <strong><?= e(date('j M Y', strtotime($checkIn))) ?> &ndash; <?= e(date('j M Y', strtotime($checkOut))) ?></strong>
        (<?= $nights ?> night<?= $nights === 1 ? '' : 's' ?>, <?= $guests ?> guest<?= $guests === 1 ? '' : 's' ?>)
      <?php elseif (!$searched): ?>
        Choose your dates above to see which rooms are available.
      <?php endif; ?>
    </p>

    <?php if (!$rooms && !$errors): ?>
      <div class="empty-state">
        <h3>No rooms available</h3>
        <p>Sorry, no rooms match these dates and guests. Try different dates or fewer guests.</p>
        <a class="btn btn-primary" href="<?= BASE_URL ?>/rooms.php">Clear search</a>
      </div>
    <?php endif; ?>

    <div class="room-grid">
      <?php foreach ($rooms as $r): ?>
        <article class="room-card">
          <div class="photo">
            <img src="<?= BASE_URL ?>/assets/images/rooms/<?= e($r['image']) ?>" alt="<?= e($r['room_name']) ?>">
            <?php if ($validSearch): ?><span class="badge badge-available">Available</span><?php endif; ?>
          </div>
          <div class="room-body">
            <h3><?= e($r['room_name']) ?></h3>
            <p><?= e($r['description']) ?></p>
            <?php $rt = $ratings[(int)$r['room_id']] ?? null; ?>
            <div class="rating-line"><?php if ($rt): ?><?= stars_html($rt['avg']) ?> <?= number_format($rt['avg'], 1) ?> (<?= $rt['count'] ?> review<?= $rt['count'] === 1 ? '' : 's' ?>)<?php else: ?>No reviews yet<?php endif; ?></div>
            <div class="room-meta"><span><?= (int)$r['max_guests'] ?> Guests</span><span><?= e($r['bed_info']) ?></span><span><?= e($r['room_type']) ?></span></div>
            <ul class="facility-list">
              <?php foreach (array_slice(facility_list($r['facilities']), 0, 4) as $f): ?><li><?= e($f) ?></li><?php endforeach; ?>
            </ul>
            <div class="room-foot">
              <div class="price"><?= money($r['price_per_night']) ?> <small>/ night</small>
                <?php if ($validSearch): ?><div class="total-line">Total <?= money($r['price_per_night'] * $nights) ?> for <?= $nights ?> night<?= $nights === 1 ? '' : 's' ?></div><?php endif; ?>
              </div>
              <div class="card-actions">
                <a class="btn btn-outline-dark btn-sm" href="<?= BASE_URL ?>/room.php?room_id=<?= (int)$r['room_id'] . $stayQuery ?>">Details</a>
                <a class="btn btn-primary btn-sm" href="<?= BASE_URL ?>/guest/book.php?room_id=<?= (int)$r['room_id'] . $stayQuery ?>">Book Now</a>
              </div>
            </div>
          </div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
