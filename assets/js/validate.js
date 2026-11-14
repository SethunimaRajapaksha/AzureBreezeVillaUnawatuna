// assets/js/validate.js - client-side form validation (the server validates again in PHP)
// Usage: <form data-validate> with inputs that have data-rules="required,email,..."
document.addEventListener('DOMContentLoaded', function () {
  var checks = {
    required: function (v) { return v.trim() !== '' || 'This field is required.'; },
    name: function (v) { return (v.trim().length >= 2 && v.trim().length <= 100) || 'Enter your full name (2-100 characters).'; },
    email: function (v) { return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(v.trim()) || 'Enter a valid email address.'; },
    phone: function (v) { return /^\+?[0-9 ]{9,15}$/.test(v.trim()) || 'Enter a valid phone number (9-15 digits).'; },
    phoneopt: function (v) { return v.trim() === '' || /^\+?[0-9 ]{9,15}$/.test(v.trim()) || 'Enter a valid phone number or leave it empty.'; },
    subject: function (v) { return (v.trim().length >= 2 && v.trim().length <= 150) || 'Enter a subject (2-150 characters).'; },
    message: function (v) { return (v.trim().length >= 10 && v.trim().length <= 2000) || 'Your message must be 10-2000 characters.'; },
    password: function (v) {
      return (v.length >= 8 && /[A-Za-z]/.test(v) && /\d/.test(v)) || 'Use at least 8 characters with a letter and a number.';
    }
  };

  function showError(input, message) {
    var box = input.parentElement.querySelector('.form-error');
    if (!box) { box = document.createElement('span'); box.className = 'form-error'; box.setAttribute('role', 'alert'); input.parentElement.appendChild(box); }
    box.textContent = message || '';
    input.classList.toggle('input-error', !!message);
    input.setAttribute('aria-invalid', message ? 'true' : 'false');
  }

  function validateInput(input, form) {
    var rules = (input.getAttribute('data-rules') || '').split(',').filter(Boolean);
    for (var i = 0; i < rules.length; i++) {
      var result = checks[rules[i]] ? checks[rules[i]](input.value) : true;
      if (result !== true) { showError(input, result); return false; }
    }
    var matchName = input.getAttribute('data-match');
    if (matchName) {
      var other = form.querySelector('[name="' + matchName + '"]');
      if (other && other.value !== input.value) { showError(input, 'Passwords do not match.'); return false; }
    }
    showError(input, '');
    return true;
  }

  document.querySelectorAll('form[data-validate]').forEach(function (form) {
    var inputs = form.querySelectorAll('[data-rules]');
    inputs.forEach(function (input) {
      input.addEventListener('blur', function () { validateInput(input, form); });
    });
    form.addEventListener('submit', function (event) {
      var ok = true;
      inputs.forEach(function (input) { if (!validateInput(input, form)) { ok = false; } });
      if (!ok) { event.preventDefault(); var first = form.querySelector('.input-error'); if (first) { first.focus(); } }
    });
  });
});
