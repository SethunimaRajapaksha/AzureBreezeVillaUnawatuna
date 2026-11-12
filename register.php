<?php
// register.php - guest registration (server-side validation + password hashing)
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

if (is_logged_in()) {
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$errors = [];
$old = ['name' => '', 'email' => '', 'phone' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_valid()) {
        $errors['form'] = 'Your session expired. Please try again.';
    }

    $name     = trim($_POST['name'] ?? '');
    $email    = strtolower(trim($_POST['email'] ?? ''));
    $phone    = trim($_POST['phone'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm  = $_POST['confirm_password'] ?? '';
    $old      = ['name' => $name, 'email' => $email, 'phone' => $phone];

    if (mb_strlen($name) < 2 || mb_strlen($name) > 100) {
        $errors['name'] = 'Enter your full name (2-100 characters).';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($email) > 150) {
        $errors['email'] = 'Enter a valid email address.';
    }
    if (!preg_match('/^\+?[0-9 ]{9,15}$/', $phone)) {
        $errors['phone'] = 'Enter a valid phone number (9-15 digits).';
    }
    if (strlen($password) < 8 || !preg_match('/[A-Za-z]/', $password) || !preg_match('/\d/', $password)) {
        $errors['password'] = 'Use at least 8 characters with a letter and a number.';
    }
    if ($password !== $confirm) {
        $errors['confirm_password'] = 'Passwords do not match.';
    }

    if (!$errors) {
        $check = $pdo->prepare('SELECT user_id FROM users WHERE email = ?');
        $check->execute([$email]);
        if ($check->fetch()) {
            $errors['email'] = 'This email is already registered. Please log in.';
        } else {
            try {
                $insert = $pdo->prepare('INSERT INTO users (name, email, contact_number, password, role) VALUES (?, ?, ?, ?, ?)');
                $insert->execute([$name, $email, $phone, password_hash($password, PASSWORD_DEFAULT), 'guest']);
                flash('success', 'Account created. You can log in now.');
                header('Location: ' . BASE_URL . '/login.php');
                exit;
            } catch (PDOException $ex) {
                // 23000 = duplicate key (two people registered the same email at once)
                $errors['email'] = $ex->getCode() === '23000' ? 'This email is already registered.' : 'Something went wrong. Please try again.';
            }
        }
    }
}

$pageTitle  = 'Create an account';
$activePage = '';
$pageScripts = ['validate.js'];
include __DIR__ . '/includes/header.php';
?>
<section class="auth">
  <img src="<?= BASE_URL ?>/assets/images/auth-bg.jpg" alt="">
  <div class="auth-card">
    <h1>Create your account</h1>
    <p class="lead">Register to book rooms and manage your stays</p>
    <?php if (isset($errors['form'])): ?><div class="alert alert-error"><?= e($errors['form']) ?></div><?php endif; ?>
    <form method="post" action="" data-validate novalidate>
      <?= csrf_field() ?>
      <div class="form-group">
        <label for="name">Full name</label>
        <input type="text" id="name" name="name" value="<?= e($old['name']) ?>" data-rules="required,name" autocomplete="name" placeholder="Your name">
        <span class="form-error" role="alert"><?= e($errors['name'] ?? '') ?></span>
      </div>
      <div class="form-group">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" data-rules="required,email" autocomplete="email" placeholder="you@example.com">
        <span class="form-error" role="alert"><?= e($errors['email'] ?? '') ?></span>
      </div>
      <div class="form-group">
        <label for="phone">Phone number</label>
        <input type="tel" id="phone" name="phone" value="<?= e($old['phone']) ?>" data-rules="required,phone" autocomplete="tel" placeholder="07X XXX XXXX">
        <span class="form-error" role="alert"><?= e($errors['phone'] ?? '') ?></span>
      </div>
      <div class="form-group">
        <label for="password">Password</label>
        <input type="password" id="password" name="password" data-rules="required,password" autocomplete="new-password" placeholder="At least 8 characters">
        <span class="form-error" role="alert"><?= e($errors['password'] ?? '') ?></span>
      </div>
      <div class="form-group">
        <label for="confirm_password">Confirm password</label>
        <input type="password" id="confirm_password" name="confirm_password" data-rules="required" data-match="password" autocomplete="new-password" placeholder="Repeat your password">
        <span class="form-error" role="alert"><?= e($errors['confirm_password'] ?? '') ?></span>
      </div>
      <button class="btn btn-primary" type="submit">Create Account</button>
    </form>
    <p class="switch">Already have an account? <a href="<?= BASE_URL ?>/login.php">Log in</a></p>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
