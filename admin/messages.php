<?php
// admin/messages.php - enquiries sent from the Contact page
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'delete') {
    if (csrf_valid()) {
        $pdo->prepare('DELETE FROM contact_messages WHERE message_id = ?')->execute([(int)($_POST['message_id'] ?? 0)]);
        flash('success', 'Message deleted.');
    } else {
        flash('error', 'Your session expired. Please try again.');
    }
    header('Location: ' . BASE_URL . '/admin/messages.php');
    exit;
}

$messages = $pdo->query('SELECT * FROM contact_messages ORDER BY created_at DESC, message_id DESC')->fetchAll();

$pageTitle = 'Messages';
$adminPage = 'messages';
include __DIR__ . '/../includes/admin_header.php';
?>
<h1 class="admin-title">Messages</h1>
<?php if (!$messages): ?><div class="panel"><p class="muted-cell">No messages yet.</p></div><?php endif; ?>
<?php foreach ($messages as $m): ?>
  <article class="panel message-card">
    <div class="message-head">
      <div>
        <strong><?= e($m['subject']) ?></strong>
        <div class="muted"><?= e($m['name']) ?> &middot; <a href="mailto:<?= e($m['email']) ?>"><?= e($m['email']) ?></a>
          <?php if ($m['phone']): ?> &middot; <?= e($m['phone']) ?><?php endif; ?></div>
      </div>
      <div class="muted"><?= e(date('j M Y, g:i A', strtotime($m['created_at']))) ?></div>
    </div>
    <p class="message-body"><?= nl2br(e($m['message'])) ?></p>
    <form method="post" action="" data-confirm="Delete this message?"><?= csrf_field() ?>
      <input type="hidden" name="action" value="delete"><input type="hidden" name="message_id" value="<?= (int)$m['message_id'] ?>">
      <button class="btn btn-danger btn-sm" type="submit">Delete</button>
    </form>
  </article>
<?php endforeach; ?>
<?php include __DIR__ . '/../includes/admin_footer.php'; ?>
