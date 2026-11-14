<?php
// admin/reviews.php - hide, show or delete guest reviews
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['review_id'] ?? 0);
    if (!csrf_valid()) {
        flash('error', 'Your session expired. Please try again.');
    } elseif (($_POST['action'] ?? '') === 'toggle') {
        $to = ($_POST['to'] ?? '') === 'Visible' ? 'Visible' : 'Hidden';
        $pdo->prepare('UPDATE reviews SET review_status = ? WHERE review_id = ?')->execute([$to, $id]);
        flash('success', $to === 'Visible' ? 'Review is visible again.' : 'Review hidden from the website.');
    } elseif (($_POST['action'] ?? '') === 'delete') {
        $pdo->prepare('DELETE FROM reviews WHERE review_id = ?')->execute([$id]);
        flash('success', 'Review deleted.');
    }
    header('Location: ' . BASE_URL . '/admin/reviews.php');
    exit;
}

$reviews = $pdo->query("SELECT rv.*, u.name AS guest_name, r.room_name
                        FROM reviews rv
                        JOIN users u ON u.user_id = rv.user_id
                        JOIN rooms r ON r.room_id = rv.room_id
                        ORDER BY rv.created_at DESC, rv.review_id DESC")->fetchAll();
$site = site_rating_stats($pdo);

$pageTitle = 'Reviews';
$adminPage = 'reviews';
include __DIR__ . '/../includes/admin_header.php';
?>
<h1 class="admin-title">Guest Reviews</h1>
<p class="muted">Average rating (visible reviews): <strong><?= $site['count'] ? number_format($site['avg'], 1) : '-' ?></strong> from <?= $site['count'] ?> review<?= $site['count'] === 1 ? '' : 's' ?></p>

<?php if (!$reviews): ?><div class="panel"><p class="muted-cell">No reviews yet.</p></div><?php endif; ?>
<?php foreach ($reviews as $rv): ?>
  <article class="panel message-card">
    <div class="message-head">
      <div>
        <strong><?= e($rv['guest_name']) ?></strong> &middot; <?= e($rv['room_name']) ?>
        <div><?= stars_html((float)$rv['rating']) ?></div>
      </div>
      <div class="muted"><?= e(date('j M Y', strtotime($rv['created_at']))) ?>
        &middot; <span class="status status-<?= $rv['review_status'] === 'Visible' ? 'confirmed' : 'inactive' ?>"><?= e($rv['review_status']) ?></span></div>
    </div>
    <p class="message-body"><?= nl2br(e($rv['comment'])) ?></p>
    <div class="row-actions">
      <form method="post" action=""><?= csrf_field() ?>
        <input type="hidden" name="action" value="toggle"><input type="hidden" name="review_id" value="<?= (int)$rv['review_id'] ?>">
        <input type="hidden" name="to" value="<?= $rv['review_status'] === 'Visible' ? 'Hidden' : 'Visible' ?>">
        <button class="btn btn-outline-dark btn-sm" type="submit"><?= $rv['review_status'] === 'Visible' ? 'Hide' : 'Show' ?></button></form>
      <form method="post" action="" data-confirm="Delete this review permanently?"><?= csrf_field() ?>
        <input type="hidden" name="action" value="delete"><input type="hidden" name="review_id" value="<?= (int)$rv['review_id'] ?>">
        <button class="btn btn-danger btn-sm" type="submit">Delete</button></form>
    </div>
  </article>
<?php endforeach; ?>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
