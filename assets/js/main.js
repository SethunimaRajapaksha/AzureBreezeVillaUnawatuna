// assets/js/main.js - navbar toggle and date-field behaviour
document.addEventListener('DOMContentLoaded', function () {
  // Mobile menu
  var toggle = document.getElementById('navToggle');
  if (toggle) {
    toggle.addEventListener('click', function () {
      var open = document.body.classList.toggle('nav-open');
      toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
    });
  }

  // Date range inputs: no past dates, check-out always after check-in
  var checkIn  = document.querySelector('input[name="check_in"]');
  var checkOut = document.querySelector('input[name="check_out"]');
  if (checkIn && checkOut) {
    var today = new Date().toISOString().split('T')[0];
    checkIn.min = today;
    checkOut.min = today;
    checkIn.addEventListener('change', function () {
      var next = new Date(checkIn.value);
      next.setDate(next.getDate() + 1);
      var nextStr = next.toISOString().split('T')[0];
      checkOut.min = nextStr;
      if (!checkOut.value || checkOut.value <= checkIn.value) {
        checkOut.value = nextStr;
      }
    });
  }
});
