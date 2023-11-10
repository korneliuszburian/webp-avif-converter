jQuery(document).ready(function($) {
  $('#toggle-summary').on('click', function(e) {
      // Firstly, should be hidden
      e.preventDefault(); // Prevent the default form submission behavior
      $('.conversion-summary-toggle').toggle();
  });
});

function hideAlert() {
  const alert = document.getElementById('alert');
  if (alert) {
      alert.classList.add('hidden'); // Add the 'hidden' class to hide the alert
  }
}

document.addEventListener('DOMContentLoaded', function () {
  const alert = document.getElementById('alert');
  if (alert) {
      alert.addEventListener('click', hideAlert);
      setTimeout(hideAlert, 2000);
  }
});
