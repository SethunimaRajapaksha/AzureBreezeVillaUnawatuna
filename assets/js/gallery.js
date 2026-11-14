// assets/js/gallery.js - category filter and full-size photo viewer
document.addEventListener('DOMContentLoaded', function () {
  var chips = document.querySelectorAll('.chip[data-filter]');
  var items = document.querySelectorAll('.gallery-item');

  chips.forEach(function (chip) {
    chip.addEventListener('click', function () {
      chips.forEach(function (c) { c.classList.remove('active'); });
      chip.classList.add('active');
      var filter = chip.getAttribute('data-filter');
      items.forEach(function (item) {
        item.hidden = !(filter === 'all' || item.getAttribute('data-category') === filter);
      });
    });
  });

  var box = document.getElementById('lightbox');
  if (!box) { return; }
  var boxImage = box.querySelector('img');
  var caption = box.querySelector('.lightbox-caption');

  function close() { box.hidden = true; document.body.classList.remove('no-scroll'); }

  items.forEach(function (item) {
    item.addEventListener('click', function () {
      var img = item.querySelector('img');
      boxImage.src = img.src;
      boxImage.alt = img.alt;
      caption.textContent = img.alt;
      box.hidden = false;
      document.body.classList.add('no-scroll');
    });
  });
  box.addEventListener('click', function (event) { if (event.target !== boxImage) { close(); } });
  document.addEventListener('keydown', function (event) { if (event.key === 'Escape' && !box.hidden) { close(); } });
});
