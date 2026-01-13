// webHelpers – ACF Accordion (patch: clase .is-open para rotación estable)
(function () {
  function setup(root) {
    root.querySelectorAll('.webh-acc-button').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var item   = btn.closest('.webh-acc-item');
        var open   = btn.getAttribute('aria-expanded') === 'true';
        var panel  = root.querySelector('#' + btn.getAttribute('aria-controls'));

        // Cerrar todos los demás
        root.querySelectorAll('.webh-acc-item.is-open').forEach(function (it) {
          if (it !== item) {
            it.classList.remove('is-open');
            var b = it.querySelector('.webh-acc-button');
            var p = it.querySelector('.webh-acc-panel');
            if (b) b.setAttribute('aria-expanded', 'false');
            if (p) p.setAttribute('hidden', '');
          }
        });

        // Toggle actual
        if (open) {
          btn.setAttribute('aria-expanded', 'false');
          item.classList.remove('is-open');
          if (panel) panel.setAttribute('hidden', '');
        } else {
          btn.setAttribute('aria-expanded', 'true');
          item.classList.add('is-open');
          if (panel) panel.removeAttribute('hidden');
        }
      });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('[data-webh-acc]').forEach(setup);
  });
})();
