<?php
// contact.php - enquiry form (saved to the contact_messages table) and villa details
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

$errors = [];
$old    = ['name' => $_SESSION['name'] ?? '', 'email' => '', 'phone' => '', 'subject' => '', 'message' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $old = [
        'name'    => trim($_POST['name'] ?? ''),
        'email'   => strtolower(trim($_POST['email'] ?? '')),
        'phone'   => trim($_POST['phone'] ?? ''),
        'subject' => trim($_POST['subject'] ?? ''),
        'message' => trim($_POST['message'] ?? ''),
    ];

    if (!csrf_valid()) {
        $errors['form'] = 'Your session expired. Please try again.';
    } elseif (!empty($_POST['website'])) {
        // Hidden "honeypot" field: real visitors never fill it in, spam bots do
        flash('success', 'Thank you! We will get back to you soon.');
        header('Location: ' . BASE_URL . '/contact.php');
        exit;
    } else {
        if (mb_strlen($old['name']) < 2 || mb_strlen($old['name']) > 100)            { $errors['name'] = 'Enter your full name (2-100 characters).'; }
        if (!filter_var($old['email'], FILTER_VALIDATE_EMAIL) || strlen($old['email']) > 150) { $errors['email'] = 'Enter a valid email address.'; }
        if ($old['phone'] !== '' && !preg_match('/^\+?[0-9 ]{9,15}$/', $old['phone'])) { $errors['phone'] = 'Enter a valid phone number or leave it empty.'; }
        if (mb_strlen($old['subject']) < 2 || mb_strlen($old['subject']) > 150)       { $errors['subject'] = 'Enter a subject (2-150 characters).'; }
        if (mb_strlen($old['message']) < 10 || mb_strlen($old['message']) > 2000)     { $errors['message'] = 'Your message must be 10-2000 characters.'; }

        if (!$errors) {
            try {
                $stmt = $pdo->prepare('INSERT INTO contact_messages (name, email, phone, subject, message) VALUES (?, ?, ?, ?, ?)');
                $stmt->execute([$old['name'], $old['email'], $old['phone'] !== '' ? $old['phone'] : null, $old['subject'], $old['message']]);
                flash('success', 'Thank you! Your message has been sent. We will get back to you soon.');
                header('Location: ' . BASE_URL . '/contact.php');
                exit;
            } catch (PDOException $ex) {
                error_log('Contact message failed: ' . $ex->getMessage());
                $errors['form'] = 'Sorry, your message could not be sent. Please try again.';
            }
        }
    }
}

$pageTitle   = 'Contact';
$activePage  = 'contact';
$pageScripts = ['validate.js'];
include __DIR__ . '/includes/header.php';
?>
<section class="page-hero">
  <img src="<?= BASE_URL ?>/assets/images/swing.jpg" alt="">
  <div class="page-hero-text"><h1>Contact Us</h1><p>We'd love to host you in Unawatuna</p></div>
</section>

<section class="section">
  <div class="container contact-layout">
    <aside class="info-card">
      <h2>Visit us</h2>
      <div class="info-item"><span>Address</span><?= e(CONTACT_ADDRESS) ?></div>
      <div class="info-item"><span>Phone</span><?= e(CONTACT_PHONE) ?></div>
      <div class="info-item"><span>Email</span><?= e(CONTACT_EMAIL) ?></div>
      <div class="info-item"><span>Check-in / Check-out</span><?= e(CONTACT_CHECKIN) ?> / <?= e(CONTACT_CHECKOUT) ?></div>
      <?php if (CONTACT_WHATSAPP !== ''): ?>
        <a class="btn btn-gold btn-block" href="https://wa.me/<?= e(CONTACT_WHATSAPP) ?>" target="_blank" rel="noopener">Chat on WhatsApp</a>
      <?php endif; ?>
    </aside>

    <div class="form-card">
      <h2>Send us a message</h2>
      <?php if (isset($errors['form'])): ?><div class="alert alert-error"><?= e($errors['form']) ?></div><?php endif; ?>
      <form method="post" action="" data-validate novalidate>
        <?= csrf_field() ?>
        <div class="honeypot" aria-hidden="true"><label>Leave this empty<input type="text" name="website" tabindex="-1" autocomplete="off"></label></div>
        <div class="form-row">
          <div class="form-group"><label for="name">Full name</label>
            <input type="text" id="name" name="name" value="<?= e($old['name']) ?>" data-rules="required,name" autocomplete="name">
            <span class="form-error" role="alert"><?= e($errors['name'] ?? '') ?></span></div>
          <div class="form-group"><label for="email">Email address</label>
            <input type="email" id="email" name="email" value="<?= e($old['email']) ?>" data-rules="required,email" autocomplete="email">
            <span class="form-error" role="alert"><?= e($errors['email'] ?? '') ?></span></div>
        </div>
        <div class="form-row">
          <div class="form-group"><label for="phone">Phone (optional)</label>
            <input type="tel" id="phone" name="phone" value="<?= e($old['phone']) ?>" data-rules="phoneopt" autocomplete="tel">
            <span class="form-error" role="alert"><?= e($errors['phone'] ?? '') ?></span></div>
          <div class="form-group"><label for="subject">Subject</label>
            <input type="text" id="subject" name="subject" value="<?= e($old['subject']) ?>" data-rules="required,subject" placeholder="Booking enquiry">
            <span class="form-error" role="alert"><?= e($errors['subject'] ?? '') ?></span></div>
        </div>
        <div class="form-group"><label for="message">Message</label>
          <textarea id="message" name="message" rows="6" data-rules="required,message" placeholder="Tell us about your stay..."><?= e($old['message']) ?></textarea>
          <span class="form-error" role="alert"><?= e($errors['message'] ?? '') ?></span></div>
        <button class="btn btn-primary btn-block" type="submit">Send Message</button>
      </form>
    </div>
  </div>
</section>

<section class="section map-section">
  <div class="container">
    <iframe class="map-frame" title="Map of Unawatuna, Sri Lanka" loading="lazy"
            src="https://www.google.com/maps?q=Unawatuna,+Sri+Lanka&amp;output=embed"></iframe>
  </div>
</section>
<?php include __DIR__ . '/includes/footer.php'; ?>
