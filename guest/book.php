<?php
// guest/book.php - booking form. Prices and availability are always checked on the server.
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
if (is_admin()) {
    flash('error', 'Administrator accounts cannot make bookings. Please use a guest account.');
    header('Location: ' . BASE_URL . '/admin/dashboard.php');
    exit;
}

// 1. Load the room (inactive or unknown rooms cannot be booked)
$roomId = (int)($_GET['room_id'] ?? $_POST['room_id'] ?? 0);
$stmt = $pdo->prepare("SELECT * FROM rooms WHERE room_id = ? AND room_status = 'active'");
$stmt->execute([$roomId]);
$room = $stmt->fetch();
if (!$room) {
    flash('error', 'That room is not available for booking.');
    header('Location: ' . BASE_URL . '/rooms.php');
    exit;
}

// 2. Guest details come from the logged-in account (never from the form)
$userStmt = $pdo->prepare('SELECT name, email, contact_number FROM users WHERE user_id = ?');
$userStmt->execute([$_SESSION['user_id']]);
$user = $userStmt->fetch();
if (!$user) {
    header('Location: ' . BASE_URL . '/logout.php');
    exit;
}

$errors   = [];
$source   = $_SERVER['REQUEST_METHOD'] === 'POST' ? $_POST : $_GET;
$checkIn  = trim($source['check_in'] ?? '');
$checkOut = trim($source['check_out'] ?? '');
$guests   = max(1, (int)($source['guests'] ?? 1));
$guests   = min($guests, (int)$room['max_guests']);

// 3. Handle the submitted form
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $errors[] = 'Your session expired. Please try again.';
    } elseif (validate_stay($checkIn, $checkOut, (int)($_POST['guests'] ?? 0), $errors)) {
        $guests = (int)$_POST['guests'];
        if ($guests > (int)$room['max_guests']) {
            $errors[] = 'This room allows a maximum of ' . (int)$room['max_guests'] . ' guests.';
        } else {
            try {
                $pdo->beginTransaction();
                // Lock the room row so two guests cannot book the same dates at the same moment
                $lock = $pdo->prepare("SELECT price_per_night FROM rooms WHERE room_id = ? AND room_status = 'active' FOR UPDATE");
                $lock->execute([$roomId]);
                $locked = $lock->fetch();

                if (!$locked || !room_is_available($pdo, $roomId, $checkIn, $checkOut)) {
                    $pdo->rollBack();
                    $errors[] = 'Sorry, this room is already booked for those dates. Please choose different dates.';
                } else {
                    $nights = nights_between($checkIn, $checkOut);
                    $total  = $nights * (float)$locked['price_per_night'];   // price calculated on the server

                    $insert = $pdo->prepare('INSERT INTO bookings (user_id, room_id, check_in, check_out, guests, total_amount, booking_status)
                                             VALUES (?, ?, ?, ?, ?, ?, ?)');
                    $insert->execute([$_SESSION['user_id'], $roomId, $checkIn, $checkOut, $guests, $total, 'Pending']);
                    $bookingId = (int)$pdo->lastInsertId();
                    $pdo->commit();

                    flash('success', 'Booking request #BK-' . str_pad((string)$bookingId, 4, '0', STR_PAD_LEFT) . ' sent. It is pending until the villa confirms it.');
                    header('Location: ' . BASE_URL . '/guest/my-bookings.php');
                    exit;
                }
            } catch (PDOException $ex) {
                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }
                error_log('Booking failed: ' . $ex->getMessage());
                $errors[] = 'Something went wrong while saving your booking. Please try again.';
            }
        }
    }
}

// 4. Summary shown next to the form (JavaScript keeps it updated as dates change)
$nights = 0;
$total  = 0;
$quiet  = [];
if ($checkIn !== '' && $checkOut !== '' && validate_stay($checkIn, $checkOut, $guests, $quiet)) {
    $nights = nights_between($checkIn, $checkOut);
    $total  = $nights * (float)$room['price_per_night'];
}

$pageTitle   = 'Book ' . $room['room_name'];
$activePage  = 'rooms';
$pageScripts = ['booking.js'];
include __DIR__ . '/../includes/header.php';
?>
<section class="section">
  <div class="container">
    <h1 class="section-title">Complete your booking</h1>

    <?php foreach ($errors as $err): ?><div class="alert alert-error" role="alert"><?= e($err) ?></div><?php endforeach; ?>

    <div class="booking-layout">
      <form class="booking-card" method="post" action="" id="bookingForm" novalidate
            data-price="<?= e($room['price_per_night']) ?>" data-max="<?= (int)$room['max_guests'] ?>">
        <?= csrf_field() ?>
        <input type="hidden" name="room_id" value="<?= $roomId ?>">
        <h2>Your stay</h2>
        <div class="form-row">
          <div class="form-group">
            <label for="check_in">Check-in</label>
            <input type="date" id="check_in" name="check_in" value="<?= e($checkIn) ?>" required>
          </div>
          <div class="form-group">
            <label for="check_out">Check-out</label>
            <input type="date" id="check_out" name="check_out" value="<?= e($checkOut) ?>" required>
          </div>
        </div>
        <div class="form-group">
          <label for="guests">Guests (maximum <?= (int)$room['max_guests'] ?>)</label>
          <select id="guests" name="guests">
            <?php for ($g = 1; $g <= (int)$room['max_guests']; $g++): ?>
              <option value="<?= $g ?>" <?= $g === $guests ? 'selected' : '' ?>><?= $g ?> Guest<?= $g > 1 ? 's' : '' ?></option>
            <?php endfor; ?>
          </select>
        </div>
        <p class="form-message" id="dateMessage" role="alert"></p>

        <h2>Guest details</h2>
        <div class="readonly-list">
          <div><span>Name</span><strong><?= e($user['name']) ?></strong></div>
          <div><span>Email</span><strong><?= e($user['email']) ?></strong></div>
          <div><span>Phone</span><strong><?= e($user['contact_number']) ?></strong></div>
        </div>
        <p class="form-hint-dark">These come from your account. You can change them on your profile page.</p>

        <button type="submit" class="btn btn-primary btn-block">Confirm Booking Request</button>
      </form>

      <aside class="summary-card">
        <div class="summary-photo"><img src="<?= BASE_URL ?>/assets/images/rooms/<?= e($room['image']) ?>" alt="<?= e($room['room_name']) ?>"></div>
        <h3><?= e($room['room_name']) ?></h3>
        <div class="summary-row"><span>Price per night</span><strong><?= money($room['price_per_night']) ?></strong></div>
        <div class="summary-row"><span>Nights</span><strong id="sumNights"><?= $nights ?: '-' ?></strong></div>
        <div class="summary-row total"><span>Estimated total</span><strong id="sumTotal"><?= $nights ? money($total) : '-' ?></strong></div>
        <p class="summary-note">Your booking is a request. It becomes confirmed once the villa approves it. You can cancel a pending booking any time from My Bookings.</p>
      </aside>
    </div>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
