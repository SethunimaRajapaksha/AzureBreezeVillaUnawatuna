<?php
// includes/admin_header.php - layout for all admin pages (sidebar + content area)
require_once __DIR__ . '/auth.php';
require_admin();
$adminPage = $adminPage ?? '';
$pageTitle = $pageTitle ?? 'Admin';
$flash     = flash();
$adminNav  = [
    'dashboard' => ['Dashboard',       '/admin/dashboard.php'],
    'rooms'     => ['Manage Rooms',    '/admin/rooms.php'],
    'bookings'  => ['Manage Bookings', '/admin/bookings.php'],
    'messages'  => ['Messages',        '/admin/messages.php'],
    'reviews'   => ['Reviews',         '/admin/reviews.php'],
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> | Admin | <?= e(SITE_NAME) ?></title>
  <link rel="icon" type="image/png" href="<?= BASE_URL ?>/assets/images/favicon.png">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600&family=Playfair+Display:ital,wght@0,600;1,600&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">
</head>
<body class="admin-body">
<div class="admin-layout">
  <aside class="admin-sidebar">
    <a class="brand" href="<?= BASE_URL ?>/index.php">
      <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="Azure Breeze Villa logo">
      <span class="brand-text">Azure Breeze<small>VILLA</small></span>
    </a>
    <div class="admin-label">ADMIN</div>
    <nav class="admin-nav">
      <?php foreach ($adminNav as $key => [$label, $path]): ?>
        <a href="<?= BASE_URL . $path ?>" class="<?= $adminPage === $key ? 'active' : '' ?>"><?= $label ?></a>
      <?php endforeach; ?>
      <a href="<?= BASE_URL ?>/index.php">View website</a>
      <a href="<?= BASE_URL ?>/logout.php">Logout</a>
    </nav>
    <div class="admin-user">Signed in as<br><strong><?= e($_SESSION['name'] ?? 'Admin') ?></strong></div>
  </aside>
  <main class="admin-main">
    <?php if ($flash): ?><div class="alert alert-<?= e($flash['type']) ?> admin-alert" role="alert"><?= e($flash['message']) ?></div><?php endif; ?>
