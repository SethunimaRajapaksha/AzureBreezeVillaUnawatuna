<?php
// includes/header.php - shared navbar. Set $pageTitle and $activePage before including.
require_once __DIR__ . '/auth.php';
$pageTitle  = $pageTitle  ?? SITE_NAME;
$activePage = $activePage ?? '';
$links = [
    'home'       => ['Home',       '/index.php'],
    'rooms'      => ['Rooms',      '/rooms.php'],
    'about'      => ['About',      '/about.php'],
    'gallery'    => ['Gallery',    '/gallery.php'],
    'facilities' => ['Facilities', '/facilities.php'],
    'contact'    => ['Contact',    '/contact.php'],
];
$flash = flash();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> | <?= e(SITE_NAME) ?></title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Playfair+Display:ital,wght@0,600;1,600&display=swap" rel="stylesheet">
  <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/images/favicon.png">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body>
<header class="site-header">
  <a class="brand" href="<?= BASE_URL ?>/index.php">
    <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="Azure Breeze Villa logo">
    <span class="brand-text">Azure Breeze<small>VILLA</small></span>
  </a>
  <button class="nav-toggle" id="navToggle" aria-label="Open menu" aria-expanded="false">Menu</button>
  <nav class="main-nav" id="mainNav">
    <?php foreach ($links as $key => [$label, $path]): ?>
      <a href="<?= BASE_URL . $path ?>" class="<?= $activePage === $key ? 'active' : '' ?>"><?= $label ?></a>
    <?php endforeach; ?>
  </nav>
  <div class="header-actions" id="headerActions">
    <?php if (is_logged_in()): ?>
      <?php if (is_admin()): ?>
        <a class="btn btn-outline-light" href="<?= BASE_URL ?>/admin/dashboard.php">Admin</a>
      <?php else: ?>
        <a class="btn btn-outline-light" href="<?= BASE_URL ?>/guest/my-bookings.php">My Bookings</a>
      <?php endif; ?>
      <a class="btn btn-gold" href="<?= BASE_URL ?>/logout.php">Logout</a>
    <?php else: ?>
      <a class="btn btn-outline-light" href="<?= BASE_URL ?>/login.php">Login</a>
      <a class="btn btn-gold" href="<?= BASE_URL ?>/register.php">Sign Up</a>
    <?php endif; ?>
  </div>
</header>
<?php if ($flash): ?>
  <div class="alert alert-<?= e($flash['type']) ?>" role="alert"><?= e($flash['message']) ?></div>
<?php endif; ?>
<main>
