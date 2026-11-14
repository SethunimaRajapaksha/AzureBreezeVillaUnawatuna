<?php
// gallery.php - photo gallery with category filter and full-size viewer
require_once __DIR__ . '/includes/auth.php';
$pageTitle  = 'Gallery';
$activePage = 'gallery';
$pageScripts = ['gallery.js'];

// [file inside assets/images, category, description]
$photos = [
    ['villa-hero.jpg',              'villa',  'Azure Breeze Villa at sunset'],
    ['rooms/ocean-view-suite.jpg',  'rooms',  'Ocean View Suite'],
    ['rooms/garden-villa.jpg',      'rooms',  'Garden Villa'],
    ['unawatuna.jpg',               'beach',  'Unawatuna beach sunset'],
    ['rooms/family-suite.jpg',      'rooms',  'Family Suite'],
    ['spa.jpg',                     'spa',    'Spa treatment'],
    ['couple-walk.jpg',             'beach',  'A walk on the white sand'],
    ['rice-curry.jpg',              'dining', 'Sri Lankan rice and curry'],
    ['loungers.jpg',                'beach',  'Sun loungers at sunset'],
    ['swing.jpg',                   'beach',  'Beach swing'],
    ['bali-room.jpg',               'rooms',  'Courtyard room'],
    ['hammock.jpg',                 'beach',  'Hammock by the sea'],
    ['lounge.jpg',                  'villa',  'Villa lounge and pool'],
    ['coconut.jpg',                 'dining', 'Welcome coconut'],
    ['aerial.jpg',                  'beach',  'The coast from above'],
    ['bathroom.jpg',                'rooms',  'Bathroom with a sea view'],
    ['pool-palms.jpg',              'villa',  'Pool among the palms'],
    ['couple-chairs.jpg',           'beach',  'Relaxing on the beach'],
];
$categories = ['all' => 'All photos', 'rooms' => 'Rooms', 'beach' => 'Beach', 'dining' => 'Dining', 'spa' => 'Spa', 'villa' => 'Villa'];
include __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <img src="<?= BASE_URL ?>/assets/images/aerial.jpg" alt="">
  <div class="page-hero-text"><h1>Gallery</h1><p>Moments from Azure Breeze Villa and Unawatuna</p></div>
</section>

<section class="section">
  <div class="container">
    <div class="chips" role="group" aria-label="Filter photos">
      <?php foreach ($categories as $key => $label): ?>
        <button type="button" class="chip <?= $key === 'all' ? 'active' : '' ?>" data-filter="<?= e($key) ?>"><?= e($label) ?></button>
      <?php endforeach; ?>
    </div>
    <div class="gallery-grid">
      <?php foreach ($photos as [$file, $cat, $desc]): ?>
        <button type="button" class="gallery-item" data-category="<?= e($cat) ?>" aria-label="View larger: <?= e($desc) ?>">
          <img src="<?= BASE_URL ?>/assets/images/<?= e($file) ?>" alt="<?= e($desc) ?>" loading="lazy">
        </button>
      <?php endforeach; ?>
    </div>
  </div>
</section>

<div class="lightbox" id="lightbox" hidden>
  <button type="button" class="lightbox-close" aria-label="Close">&times;</button>
  <img src="" alt="">
  <p class="lightbox-caption"></p>
</div>
<?php include __DIR__ . '/includes/footer.php'; ?>
