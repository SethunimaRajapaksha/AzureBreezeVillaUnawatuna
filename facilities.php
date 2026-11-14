<?php
// facilities.php - what the villa offers
require_once __DIR__ . '/includes/auth.php';
$pageTitle  = 'Facilities';
$activePage = 'facilities';

$facilities = [
    ['Private Beach Access', 'unawatuna.jpg',  'Walk from the villa to the sand and enjoy the sunset at your own pace.'],
    ['Pool',                 'pool-palms.jpg', 'Swim with a tropical backdrop, with sun loungers close by.'],
    ['Spa & Wellness',       'spa.jpg',        'Unwind with relaxing treatments in a calm setting.'],
    ['Sri Lankan Dining',    'rice-curry.jpg', 'Enjoy authentic rice and curry made with local flavours.'],
    ['Luxury Rooms & Suites','rooms/ocean-view-suite.jpg', 'Comfortable rooms with sea or garden views.'],
    ['Garden & Hammocks',    'hammock.jpg',    'Relax in the shade between the palms.'],
];
include __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <img src="<?= BASE_URL ?>/assets/images/spa.jpg" alt="">
  <div class="page-hero-text"><h1>Facilities</h1><p>Everything you need for a relaxed stay</p></div>
</section>

<section class="section">
  <div class="container">
    <div class="card-grid three">
      <?php foreach ($facilities as [$title, $image, $text]): ?>
        <article class="feature-card">
          <div class="feature-photo"><img src="<?= BASE_URL ?>/assets/images/<?= e($image) ?>" alt="<?= e($title) ?>"></div>
          <div class="feature-body"><h3><?= e($title) ?></h3><p><?= e($text) ?></p></div>
        </article>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<section class="section band-soft">
  <div class="container strip">
    <div>Free Wi-Fi</div><div>24/7 Security</div><div>Prime Unawatuna location</div>
  </div>
</section>

<section class="cta-band cta-plain">
  <div class="cta-band-text center">
    <h2>Ready for your Unawatuna escape?</h2>
    <a class="btn btn-gold" href="<?= BASE_URL ?>/rooms.php">Explore Our Rooms</a>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
