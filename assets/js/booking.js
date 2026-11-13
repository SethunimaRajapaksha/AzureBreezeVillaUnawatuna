// assets/js/booking.js - live night count and price on the booking form.
// The server recalculates everything again, so this is only for the guest's convenience.
document.addEventListener('DOMContentLoaded', function () {
  var form = document.getElementById('bookingForm');
  if (!form) { return; }

  var price     = parseFloat(form.dataset.price);
  var checkIn   = document.getElementById('check_in');
  var checkOut  = document.getElementById('check_out');
  var message   = document.getElementById('dateMessage');
  var nightsOut = document.getElementById('sumNights');
  var totalOut  = document.getElementById('sumTotal');

  function show(text) {
    message.textContent = text || '';
    message.classList.toggle('has-error', !!text);
  }

  // Returns true when the chosen dates are valid. Also updates the summary card.
  function update() {
    nightsOut.textContent = '-';
    totalOut.textContent = '-';
    show('');
    if (!checkIn.value || !checkOut.value) { return false; }

    var start = new Date(checkIn.value);
    var end = new Date(checkOut.value);
    var today = new Date();
    today.setHours(0, 0, 0, 0);

    if (start < today) { show('Check-in date cannot be in the past.'); return false; }
    var nights = Math.round((end - start) / 86400000);
    if (nights <= 0) { show('Check-out date must be after the check-in date.'); return false; }
    if (nights > 30) { show('Online bookings are limited to 30 nights.'); return false; }

    nightsOut.textContent = nights;
    totalOut.textContent = 'LKR ' + Math.round(nights * price).toLocaleString('en-US');
    return true;
  }

  checkIn.addEventListener('change', update);
  checkOut.addEventListener('change', update);

  form.addEventListener('submit', function (event) {
    if (!checkIn.value || !checkOut.value) {
      show('Please choose your check-in and check-out dates.');
      event.preventDefault();
      return;
    }
    if (!update()) { event.preventDefault(); }
  });

  update();
});
