(function() {
  const mobileToggle = document.querySelector('.mobile-toggle');
  const navList = document.querySelector('.nav-list');
  const mobileQuery = window.matchMedia('(max-width: 768px)');

  const closeMobileDropdowns = () => {
    document.querySelectorAll('.nav-item.open').forEach((item) => {
      item.classList.remove('open');
      const link = item.querySelector('.nav-link[data-dropdown]');
      if (link) link.setAttribute('aria-expanded', 'false');
    });
    document.querySelectorAll('.dropdown.open').forEach((dropdown) => dropdown.classList.remove('open'));
  };

  const setMobileMenuOpen = (open) => {
    if (!mobileToggle || !navList) return;
    navList.classList.toggle('active', open);
    mobileToggle.classList.toggle('active', open);
    mobileToggle.setAttribute('aria-expanded', String(open));
    document.body.classList.toggle('drawer-open', open && mobileQuery.matches);
  };

  if (!mobileToggle || !navList) return;

  mobileToggle.addEventListener('click', () => {
    setMobileMenuOpen(!navList.classList.contains('active'));
  });

  document.addEventListener('click', (e) => {
    if (!mobileQuery.matches || !navList.classList.contains('active')) return;
    if (!e.target.closest('header')) setMobileMenuOpen(false);
  });

  document.addEventListener('keydown', (e) => {
    if (e.key !== 'Escape') return;
    setMobileMenuOpen(false);
    closeMobileDropdowns();
  });

  document.querySelectorAll('.nav-link[data-dropdown]').forEach((link) => {
    link.addEventListener('click', (e) => {
      if (!mobileQuery.matches) return;

      e.preventDefault();

      const navItem = link.closest('.nav-item');
      const dropdown = navItem?.querySelector('.dropdown');
      if (!navItem || !dropdown) return;

      if (link.getAttribute('data-dropdown-direction') === 'up') {
        dropdown.classList.add('dropdown-up');
      }

      const willOpen = !navItem.classList.contains('open');
      closeMobileDropdowns();

      if (willOpen) {
        navItem.classList.add('open');
        dropdown.classList.add('open');
      }
      link.setAttribute('aria-expanded', String(willOpen));

      if (willOpen) setMobileMenuOpen(true);
    });
  });

  document.querySelectorAll('.nav-link:not([data-dropdown])').forEach((link) => {
    link.addEventListener('click', () => {
      if (!mobileQuery.matches) return;
      setMobileMenuOpen(false);
      closeMobileDropdowns();
    });
  });

  navList.querySelectorAll('.dropdown-item').forEach((item) => {
    item.addEventListener('click', () => {
      if (!mobileQuery.matches) return;
      setMobileMenuOpen(false);
      closeMobileDropdowns();
    });
  });

  mobileQuery.addEventListener('change', (event) => {
    if (!event.matches) {
      setMobileMenuOpen(false);
      closeMobileDropdowns();
    }
  });
})();