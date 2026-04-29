(() => {
  const el = document.querySelector('[data-current-year]');
  if (el) el.textContent = String(new Date().getFullYear());

  const currentPath = window.location.pathname.replace(/\/+$/, '') || '/index.php';
  document.querySelectorAll('.nav a').forEach((link, index) => {
    const href = link.getAttribute('href') || '';
    const normalizedHref = href.replace(/\/+$/, '');

    if (normalizedHref && currentPath === normalizedHref) {
      link.classList.add('is-active');
      link.setAttribute('aria-current', 'page');
    }

    // Keep staggered nav animation configurable without hardcoding nth-child CSS rules.
    link.style.setProperty('--nav-delay', `${40 * (index + 1)}ms`);
  });

  document.querySelectorAll('.card').forEach((card, index) => {
    card.style.setProperty('--card-delay', `${60 * index}ms`);
  });

  document.querySelectorAll('.flash').forEach((flash) => {
    window.setTimeout(() => {
      flash.classList.add('is-hiding');
      window.setTimeout(() => flash.remove(), 220);
    }, 2600);
  });

  document.querySelectorAll('textarea').forEach((textarea) => {
    const syncHeight = () => {
      textarea.style.height = 'auto';
      textarea.style.height = `${Math.max(textarea.scrollHeight, 110)}px`;
    };

    textarea.addEventListener('input', syncHeight);
    syncHeight();
  });

  document.querySelectorAll('[data-time]').forEach((node) => {
    const raw = node.getAttribute('data-time') || '';
    const date = new Date(raw.replace(' ', 'T'));
    if (Number.isNaN(date.getTime())) return;

    const minutes = Math.floor((Date.now() - date.getTime()) / 60000);
    if (minutes < 1) {
      node.textContent = 'Just now';
    } else if (minutes < 60) {
      node.textContent = `${minutes}m ago`;
    } else if (minutes < 1440) {
      node.textContent = `${Math.floor(minutes / 60)}h ago`;
    } else {
      node.textContent = `${Math.floor(minutes / 1440)}d ago`;
    }
  });
})();
