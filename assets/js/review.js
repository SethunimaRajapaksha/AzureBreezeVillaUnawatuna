// assets/js/review.js - checks the rating and comment before the review form is sent
document.addEventListener('DOMContentLoaded', function () {
  var form = document.getElementById('reviewForm');
  if (!form) { return; }
  var ratingError = document.getElementById('ratingError');
  var commentError = document.getElementById('commentError');

  form.addEventListener('submit', function (event) {
    var ok = true;
    ratingError.textContent = '';
    commentError.textContent = '';
    if (!form.querySelector('input[name="rating"]:checked')) {
      ratingError.textContent = 'Please choose a rating from 1 to 5 stars.';
      ok = false;
    }
    var text = form.comment.value.trim();
    if (text.length < 10 || text.length > 1000) {
      commentError.textContent = 'Please write 10 to 1000 characters about your stay.';
      ok = false;
    }
    if (!ok) { event.preventDefault(); }
  });
});
