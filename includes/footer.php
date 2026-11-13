</main>
<footer class="site-footer">
  <a class="brand" href="<?= BASE_URL ?>/index.php">
    <img src="<?= BASE_URL ?>/assets/images/logo.png" alt="Azure Breeze Villa logo">
    <span class="brand-text">Azure Breeze<small>VILLA</small></span>
  </a>
  <div class="footer-mid">Unawatuna &middot; Sri Lanka</div>
  <div class="footer-tag">Luxury by the Ocean</div>
</footer>
<script src="<?= BASE_URL ?>/assets/js/main.js"></script>
<?php foreach (($pageScripts ?? []) as $script): ?>
<script src="<?= BASE_URL ?>/assets/js/<?= e($script) ?>"></script>
<?php endforeach; ?>
</body>
</html>
