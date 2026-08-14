/**
 * DataForge theme toggle.
 * Persists the chosen theme in localStorage so it survives page loads/navigation.
 */
(function () {
  function applyTheme(mode) {
    document.body.setAttribute('data-theme', mode);
    var darkBtn = document.getElementById('btn-dark');
    var lightBtn = document.getElementById('btn-light');
    if (darkBtn) darkBtn.classList.toggle('active', mode === 'dark');
    if (lightBtn) lightBtn.classList.toggle('active', mode === 'light');
    try { localStorage.setItem('dataforge-theme', mode); } catch (e) {}
  }

  window.setTheme = applyTheme;

  document.addEventListener('DOMContentLoaded', function () {
    var saved = null;
    try { saved = localStorage.getItem('dataforge-theme'); } catch (e) {}
    applyTheme(saved === 'light' ? 'light' : 'dark');
  });
})();

// Close the account dropdown when clicking anywhere outside it.
document.addEventListener('click', function (e) {
  var dropdown = document.getElementById('account-dropdown');
  var trigger = document.querySelector('.account-trigger');
  if (!dropdown || !trigger) return;
  if (!dropdown.contains(e.target) && !trigger.contains(e.target)) {
    dropdown.classList.remove('open');
  }
});