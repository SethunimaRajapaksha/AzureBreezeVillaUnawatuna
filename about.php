<?php
// about.php - story, mission, vision and values
require_once __DIR__ . '/includes/auth.php';
$pageTitle  = 'About';
$activePage = 'about';
include __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <img src="<?= BASE_URL ?>/assets/images/villa-hero.jpg" alt="">
  <div class="page-hero-text"><h1>About Azure Breeze Villa</h1><p>Luxury by the ocean in Unawatuna, Sri Lanka</p></div>
</section>

<section class="section">
  <div class="container split">
    <div>
      <div class="eyebrow">OUR STORY</div>
      <h2 class="section-title">A calm place by the sea</h2>
      <p class="body-text">Azure Breeze Villa is a beachfront villa in Unawatuna, on Sri Lanka's southern coast. We created it as a calm place to unwind, with ocean views, comfortable rooms and warm Sri Lankan hospitality.</p>
      <p class="body-text">Whether you travel as a couple, a family or a group of friends, our team is here to make your stay simple and memorable.</p>
    </div>
    <div class="split-photo"><img src="<?= BASE_URL ?>/assets/images/lounge.jpg" alt="Villa lounge opening onto the pool and beach"></div>
  </div>
</section>

<section class="section band-soft">
  <div class="container">
    <div class="center-head"><div class="eyebrow">WHAT GUIDES US</div><h2 class="section-title">Mission, vision and values</h2></div>
    <div class="card-grid three">
      <article class="info-tile"><h3>Our Mission</h3><p>To give every guest a calm, comfortable and memorable stay by the sea, with warm Sri Lankan hospitality and simple, reliable booking.</p></article>
      <article class="info-tile"><h3>Our Vision</h3><p>To be Unawatuna's most loved boutique beachfront villa, known for genuine care for our guests and for the coast we call home.</p></article>
      <article class="info-tile"><h3>Our Values</h3><p>Warm hospitality. Comfort and safety for every guest. Respect for the local community and the natural beauty around us.</p></article>
    </div>
  </div>
</section>

<section class="cta-band cta-plain">
  <div class="cta-band-text center">
    <h2>Ready for your Unawatuna escape?</h2>
    <a class="btn btn-gold" href="<?= BASE_URL ?>/rooms.php">Explore Our Rooms</a>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
