(() => {
  const el = document.querySelector('[data-current-year]');
  if (el) el.textContent = String(new Date().getFullYear());
})();
