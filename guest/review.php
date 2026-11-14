<?php
// guest/review.php - a guest rates and reviews a FINISHED, confirmed stay (one review per booking)
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
$userId    = (int)$_SESSION['user_id'];
$bookingId = (int)($_GET['booking_id'] ?? $_POST['booking_id'] ?? 0);

// The booking must belong to this guest, be confirmed, and the stay must be over
$stmt = $pdo->prepare("SELECT b.*, r.room_name, r.image, rv.review_id
                       FROM bookings b
                       JOIN rooms r ON r.room_id = b.room_id
                       LEFT JOIN reviews rv ON rv.booking_id = b.booking_id
                       WHERE b.booking_id = ? AND b.user_id = ? AND b.booking_status = 'Confirmed' AND b.check_out < CURDATE()");
$stmt->execute([$bookingId, $userId]);
$booking = $stmt->fetch();

if (!$booking) {
    flash('error', 'You can only review your own confirmed stays after check-out.');
    header('Location: ' . BASE_URL . '/guest/my-bookings.php');
    exit;
}
if ($booking['review_id']) {
    flash('info', 'You have already reviewed this stay. Thank you!');
    header('Location: ' . BASE_URL . '/guest/my-bookings.php');
    exit;
}

$errors  = [];
$rating  = 0;
$comment = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $rating  = (int)($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if (!csrf_valid()) {
        $errors['form'] = 'Your session expired. Please try again.';
    } else {
        if ($rating < 1 || $rating > 5) {
            $errors['rating'] = 'Please choose a rating from 1 to 5 stars.';
        }
        if (mb_strlen($comment) < 10 || mb_strlen($comment) > 1000) {
            $errors['comment'] = 'Please write 10 to 1000 characters about your stay.';
        }
        if (!$errors) {
            try {
                $pdo->prepare('INSERT INTO reviews (booking_id, user_id, room_id, rating, comment) VALUES (?, ?, ?, ?, ?)')
                    ->execute([$bookingId, $userId, $booking['room_id'], $rating, $comment]);
                flash('success', 'Thank you! Your review has been published.');
                header('Location: ' . BASE_URL . '/guest/my-bookings.php');
                exit;
            } catch (PDOException $ex) {
                // 23000 = duplicate key: this booking already has a review
                $errors['form'] = $ex->getCode() === '23000' ? 'You have already reviewed this stay.' : 'Something went wrong. Please try again.';
                error_log('Review failed: ' . $ex->getMessage());
            }
        }
    }
}

$pageTitle   = 'Write a review';
$activePage  = '';
$pageScripts = ['review.js'];
include __DIR__ . '/../includes/header.php';
?>
<section class="section">
  <div class="container narrow">
    <h1 class="section-title">How was your stay?</h1>
    <div class="booking-card">
      <p class="booking-meta"><?= e($booking['room_name']) ?> &middot;
        <?= e(date('j M Y', strtotime($booking['check_in']))) ?> &ndash; <?= e(date('j M Y', strtotime($booking['check_out']))) ?></p>
      <?php if (isset($errors['form'])): ?><div class="alert alert-error"><?= e($errors['form']) ?></div><?php endif; ?>
      <form method="post" action="" id="reviewForm" novalidate>
        <?= csrf_field() ?>
        <input type="hidden" name="booking_id" value="<?= $bookingId ?>">
        <fieldset class="form-group">
          <legend class="legend-label">Your rating</legend>
          <div class="star-input">
            <?php for ($i = 5; $i >= 1; $i--): ?>
              <input type="radio" id="star<?= $i ?>" name="rating" value="<?= $i ?>" <?= $rating === $i ? 'checked' : '' ?>>
              <label for="star<?= $i ?>" title="<?= $i ?> star<?= $i > 1 ? 's' : '' ?>"><span class="sr-only"><?= $i ?> star<?= $i > 1 ? 's' : '' ?></span>★</label>
            <?php endfor; ?>
          </div>
          <span class="form-error" id="ratingError" role="alert"><?= e($errors['rating'] ?? '') ?></span>
        </fieldset>
        <div class="form-group">
          <label for="comment">Your review</label>
          <textarea id="comment" name="comment" rows="5" maxlength="1000" placeholder="What did you enjoy most?"><?= e($comment) ?></textarea>
          <span class="form-error" id="commentError" role="alert"><?= e($errors['comment'] ?? '') ?></span>
        </div>
        <button class="btn btn-primary" type="submit">Publish review</button>
        <a class="btn btn-outline-dark" href="<?= BASE_URL ?>/guest/my-bookings.php">Cancel</a>
      </form>
    </div>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
