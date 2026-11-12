<?php
// index.php - Home page
require_once __DIR__ . '/config/db.php';
$pageTitle  = 'Home';
$activePage = 'home';

// Three cheapest active rooms for the "Featured Rooms" section (prepared statement)
$stmt = $pdo->prepare("SELECT room_id, room_name, description, price_per_night, max_guests, bed_info, image
                       FROM rooms WHERE room_status = 'active' ORDER BY price_per_night ASC LIMIT 3");
$stmt->execute();
$featured = $stmt->fetchAll();

include __DIR__ . '/includes/header.php';
?>
<section class="hero">
  <img src="<?= BASE_URL ?>/assets/images/villa-hero.jpg" alt="Azure Breeze Villa at sunset with infinity pool">
  <div class="hero-content">
    <div class="eyebrow">LUXURY VILLA IN</div>
    <h1>Unawatuna</h1>
    <div class="hero-sub">Sri Lanka</div>
    <p>Experience the perfect blend of luxury, comfort and natural beauty at our exclusive beachfront villa in Unawatuna.</p>
    <div class="hero-chips"><span>Unawatuna, Sri Lanka</span><span>Private Beach Access</span><span>Luxury Rooms &amp; Suites</span><span>Free Wi-Fi &amp; More</span></div>
    <a class="btn btn-gold" href="<?= BASE_URL ?>/rooms.php">Explore Our Rooms</a>
  </div>
</section>

<div class="search-wrap">
  <form class="search-bar" action="<?= BASE_URL ?>/rooms.php" method="get" id="searchForm">
    <div class="field"><label for="check_in">Check-in</label><input type="date" id="check_in" name="check_in" required></div>
    <div class="field"><label for="check_out">Check-out</label><input type="date" id="check_out" name="check_out" required></div>
    <div class="field"><label for="guests">Guests</label>
      <select id="guests" name="guests">
        <?php for ($g = 1; $g <= 6; $g++): ?><option value="<?= $g ?>"><?= $g ?> Guest<?= $g > 1 ? 's' : '' ?></option><?php endfor; ?>
      </select>
    </div>
    <button class="btn btn-primary" type="submit">Check Availability</button>
  </form>
</div>

<section class="section">
  <div class="container welcome-grid">
    <div class="welcome-card">
      <div class="eyebrow">WELCOME TO</div>
      <h2>Azure Breeze Villa</h2>
      <p>Nestled in the heart of Unawatuna, our luxury villa offers a serene escape with breathtaking ocean views, modern comforts and warm Sri Lankan hospitality.</p>
      <div class="mini-facilities"><div>Private Beach Access</div><div>Spa &amp; Wellness</div><div>Fine Dining</div><div>24/7 Security</div></div>
      <a class="btn btn-outline-dark" href="<?= BASE_URL ?>/about.php">Learn More</a>
    </div>
    <div>
      <div class="row-head"><h2>Featured Rooms</h2><a href="<?= BASE_URL ?>/rooms.php">View All Rooms</a></div>
      <div class="room-grid">
        <?php foreach ($featured as $r): ?>
          <article class="room-card">
            <div class="photo"><img src="<?= BASE_URL ?>/assets/images/rooms/<?= e($r['image']) ?>" alt="<?= e($r['room_name']) ?>"></div>
            <div class="room-body">
              <h3><?= e($r['room_name']) ?></h3>
              <p><?= e($r['description']) ?></p>
              <div class="room-meta"><span><?= (int)$r['max_guests'] ?> Guests</span><span><?= e($r['bed_info']) ?></span></div>
              <div class="room-foot">
                <div class="price"><?= money($r['price_per_night']) ?> <small>/ night</small></div>
                <a class="btn btn-primary btn-sm" href="<?= BASE_URL ?>/guest/book.php?room_id=<?= (int)$r['room_id'] ?>">Book Now</a>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
        <?php if (!$featured): ?><p>No rooms available right now. Please check back soon.</p><?php endif; ?>
      </div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <h2 class="section-title">Facilities &amp; experiences</h2>
    <div class="tile-row">
      <div class="tile"><img src="<?= BASE_URL ?>/assets/images/spa.jpg" alt="Hot stone spa treatment"><span>Spa &amp; Wellness</span></div>
      <div class="tile"><img src="<?= BASE_URL ?>/assets/images/rice-curry.jpg" alt="Sri Lankan rice and curry"><span>Rice &amp; Curry Dining</span></div>
      <div class="tile"><img src="<?= BASE_URL ?>/assets/images/coconut.jpg" alt="Fresh coconut with frangipani"><span>Welcome Coconut</span></div>
      <div class="tile"><img src="<?= BASE_URL ?>/assets/images/hammock.jpg" alt="Hammock between palms"><span>Hammock by the Sea</span></div>
    </div>
  </div>
</section>

<section class="section">
  <div class="container">
    <h2 class="section-title">Life by the sea</h2>
    <div class="tile-row five small">
      <div class="tile"><img src="<?= BASE_URL ?>/assets/images/unawatuna.jpg" alt="Unawatuna beach sunset"></div>
      <div class="tile"><img src="<?= BASE_URL ?>/assets/images/couple-walk.jpg" alt="Couple on a white sand beach"></div>
      <div class="tile"><img src="<?= BASE_URL ?>/assets/images/swing.jpg" alt="Beach swing"></div>
      <div class="tile"><img src="<?= BASE_URL ?>/assets/images/loungers.jpg" alt="Sun loungers at sunset"></div>
      <div class="tile"><img src="<?= BASE_URL ?>/assets/images/couple-chairs.jpg" alt="Couple relaxing with drinks"></div>
    </div>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
