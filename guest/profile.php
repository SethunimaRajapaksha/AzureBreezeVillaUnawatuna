<?php
// guest/profile.php - change name, phone number and password
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_login();
$userId = (int)$_SESSION['user_id'];

$stmt = $pdo->prepare('SELECT user_id, name, email, contact_number, password, created_at FROM users WHERE user_id = ?');
$stmt->execute([$userId]);
$user = $stmt->fetch();
if (!$user) {
    header('Location: ' . BASE_URL . '/logout.php');
    exit;
}

$detailErrors   = [];
$passwordErrors = [];
$form = ['name' => $user['name'], 'phone' => $user['contact_number']];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        flash('error', 'Your session expired. Please try again.');
        header('Location: ' . BASE_URL . '/guest/profile.php');
        exit;
    }
    $action = $_POST['action'] ?? '';

    // 1. Update name and phone number
    if ($action === 'details') {
        $form = ['name' => trim($_POST['name'] ?? ''), 'phone' => trim($_POST['phone'] ?? '')];
        if (mb_strlen($form['name']) < 2 || mb_strlen($form['name']) > 100) {
            $detailErrors['name'] = 'Enter your full name (2-100 characters).';
        }
        if (!preg_match('/^\+?[0-9 ]{9,15}$/', $form['phone'])) {
            $detailErrors['phone'] = 'Enter a valid phone number (9-15 digits).';
        }
        if (!$detailErrors) {
            $pdo->prepare('UPDATE users SET name = ?, contact_number = ? WHERE user_id = ?')
                ->execute([$form['name'], $form['phone'], $userId]);
            $_SESSION['name'] = $form['name'];
            flash('success', 'Your details have been updated.');
            header('Location: ' . BASE_URL . '/guest/profile.php');
            exit;
        }
    }

    // 2. Change password (the current password must be correct)
    if ($action === 'password') {
        $current = $_POST['current_password'] ?? '';
        $new     = $_POST['new_password'] ?? '';
        $confirm = $_POST['confirm_password'] ?? '';

        if (!password_verify($current, $user['password'])) {
            $passwordErrors['current_password'] = 'Your current password is not correct.';
        }
        if (strlen($new) < 8 || !preg_match('/[A-Za-z]/', $new) || !preg_match('/\d/', $new)) {
            $passwordErrors['new_password'] = 'Use at least 8 characters with a letter and a number.';
        } elseif ($new === $current) {
            $passwordErrors['new_password'] = 'Your new password must be different from the current one.';
        }
        if ($new !== $confirm) {
            $passwordErrors['confirm_password'] = 'Passwords do not match.';
        }
        if (!$passwordErrors) {
            $pdo->prepare('UPDATE users SET password = ? WHERE user_id = ?')
                ->execute([password_hash($new, PASSWORD_DEFAULT), $userId]);
            session_regenerate_id(true);
            flash('success', 'Your password has been changed.');
            header('Location: ' . BASE_URL . '/guest/profile.php');
            exit;
        }
    }
}

// Small summary of this guest's stays
$sum = $pdo->prepare("SELECT COUNT(*) AS total,
                             SUM(booking_status = 'Pending') AS pending,
                             SUM(booking_status = 'Confirmed' AND check_out >= CURDATE()) AS upcoming
                      FROM bookings WHERE user_id = ?");
$sum->execute([$userId]);
$s = $sum->fetch();

$pageTitle   = 'My Profile';
$activePage  = '';
$pageScripts = ['validate.js'];
include __DIR__ . '/../includes/header.php';
?>
<section class="section">
  <div class="container">
    <h1 class="section-title">My Profile</h1>
    <div class="booking-layout">
      <div class="stack">
        <form class="booking-card" method="post" action="" data-validate novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="details">
          <h2>Your details</h2>
          <div class="form-row">
            <div class="form-group"><label for="name">Full name</label>
              <input type="text" id="name" name="name" value="<?= e($form['name']) ?>" data-rules="required,name" autocomplete="name">
              <span class="form-error" role="alert"><?= e($detailErrors['name'] ?? '') ?></span></div>
            <div class="form-group"><label for="phone">Phone number</label>
              <input type="tel" id="phone" name="phone" value="<?= e($form['phone']) ?>" data-rules="required,phone" autocomplete="tel">
              <span class="form-error" role="alert"><?= e($detailErrors['phone'] ?? '') ?></span></div>
          </div>
          <div class="form-group"><label for="email">Email address</label>
            <input type="email" id="email" value="<?= e($user['email']) ?>" disabled>
            <p class="form-hint-dark">Your email is your login, so it cannot be changed here.</p></div>
          <button class="btn btn-primary" type="submit">Save changes</button>
        </form>

        <form class="booking-card" method="post" action="" data-validate novalidate>
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="password">
          <h2>Change password</h2>
          <div class="form-group"><label for="current_password">Current password</label>
            <input type="password" id="current_password" name="current_password" data-rules="required" autocomplete="current-password">
            <span class="form-error" role="alert"><?= e($passwordErrors['current_password'] ?? '') ?></span></div>
          <div class="form-row">
            <div class="form-group"><label for="new_password">New password</label>
              <input type="password" id="new_password" name="new_password" data-rules="required,password" autocomplete="new-password" placeholder="At least 8 characters">
              <span class="form-error" role="alert"><?= e($passwordErrors['new_password'] ?? '') ?></span></div>
            <div class="form-group"><label for="confirm_password">Confirm new password</label>
              <input type="password" id="confirm_password" name="confirm_password" data-rules="required" data-match="new_password" autocomplete="new-password">
              <span class="form-error" role="alert"><?= e($passwordErrors['confirm_password'] ?? '') ?></span></div>
          </div>
          <button class="btn btn-primary" type="submit">Update password</button>
        </form>
      </div>

      <aside class="summary-card">
        <h3>Your stays</h3>
        <div class="summary-row"><span>Member since</span><strong><?= e(date('M Y', strtotime($user['created_at']))) ?></strong></div>
        <div class="summary-row"><span>Total bookings</span><strong><?= (int)$s['total'] ?></strong></div>
        <div class="summary-row"><span>Upcoming (confirmed)</span><strong><?= (int)$s['upcoming'] ?></strong></div>
        <div class="summary-row"><span>Pending</span><strong><?= (int)$s['pending'] ?></strong></div>
        <a class="btn btn-gold btn-block" href="<?= BASE_URL ?>/guest/my-bookings.php">View My Bookings</a>
      </aside>
    </div>
  </div>
</section>
<?php include __DIR__ . '/../includes/footer.php'; ?>
