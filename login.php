<?php
// login.php - guest and administrator login (sessions + password_verify)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$redirect = $_GET['redirect'] ?? ($_POST['redirect'] ?? '');

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$error = '';
$email = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $password = $_POST['password'] ?? '';

    if (!csrf_valid()) {
        $error = 'Your session expired. Please try again.';
    } elseif ($email === '' || $password === '') {
        $error = 'Please enter your email and password.';
    } else {
        $stmt = $pdo->prepare('SELECT user_id, name, password, role FROM users WHERE email = ?');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // One generic message so attackers cannot tell which emails exist
        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);               // prevents session fixation
            $_SESSION['user_id'] = (int)$user['user_id'];
            $_SESSION['name']    = $user['name'];
            $_SESSION['role']    = $user['role'];

            $home = $user['role'] === 'admin' ? BASE_URL . '/admin/dashboard.php' : BASE_URL . '/guest/my-bookings.php';
            header('Location: ' . safe_redirect_target($redirect, $home));
            exit;
        }
        $error = 'Invalid email or password.';
    }
}

$pageTitle  = 'Login';
$activePage = '';
$pageScripts = ['validate.js'];
include __DIR__ . '/includes/header.php';
?>
<section class="auth">
  <img src="<?= BASE_URL ?>/assets/images/auth-bg.jpg" alt="">
  <div class="auth-card">
    <h1>Welcome back</h1>
    <p class="lead">Sign in to manage your stay</p>
    <?php if ($error): ?><div class="alert alert-error" role="alert"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="" data-validate novalidate>
      <?= csrf_field() ?>
      <input type="hidden" name="redirect" value="<?= e($redirect) ?>">
      <div class="form-group">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" value="<?= e($email) ?>" data-rules="required,email" autocomplete="email" placeholder="you@example.com">
        <span class="form-error" role="alert"></span>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" data-rules="required" autocomplete="current-password" placeholder="Your password">
        <span class="form-error" role="alert"></span>
      </div>
      <button class="btn btn-primary" type="submit">Sign In</button>
    </form>
    <p class="switch">New here? <a href="<?= BASE_URL ?>/register.php">Create an account</a></p>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
