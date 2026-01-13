/**
 * mc-registro.js
 * - Maneja las cards de perfiles y carga el formulario CF7 correspondiente.
 * - Requiere contenedores "embed" ocultos con el formulario ya impreso en la página:
 *      <div id="mc-embed-zer" style="display:none">[contact-form-7 id="2759"]</div>
 * - Carga el formulario en #mc-form-area al hacer click en la card o al abrir con #hash.
 * - Si quieres mapear via JS: window.__MC_FORM_MAP = { zer: 2759, mentor: 0, ... }
 *   (Las cards sin formulario se marcan "is-disabled")
 */
(() => {
  const $  = (s, c=document) => c.querySelector(s);
  const $$ = (s, c=document) => Array.from(c.querySelectorAll(s));

  // Área donde se inyecta el formulario
  const formArea = $('#mc-form-area');

  // Mapa opcional (puede venir de PHP con wp_localize_script o setearse antes de este script)
  const map = (window.MCFORMS && window.MCFORMS.map)
           || (window.__MC_FORM_MAP || {}); // { slug: cf7_id }

  // Util: obtener perfil desde hash (#zer, #mentor, etc.)
  const getHashProfile = () => (location.hash || '').replace('#','').trim();

  // Marca/desmarca la card activa
  function setActiveCard(slug){
    $$('.mc-card').forEach(card => {
      const isActive = card.dataset.profile === slug;
      card.classList.toggle('is-active', isActive);
      card.setAttribute('aria-expanded', isActive ? 'true' : 'false');
    });
  }

  // Deshabilita cards sin formulario (según mapa o falta de embed)
  function gateCards(){
    $$('.mc-card').forEach(card => {
      const slug = card.dataset.profile || '';
      const embed = $('#mc-embed-' + slug);
      const hasMap = !!map[slug]; // si usas un mapa explícito
      const hasEmbed = !!embed && embed.innerHTML.trim().length > 0;

      const enabled = hasEmbed || hasMap; // con que exista embed o map, la habilitamos
      if (!enabled){
        card.classList.add('is-disabled');
        card.setAttribute('aria-disabled','true');
        card.title = card.title || 'Próximamente';
      } else {
        card.classList.remove('is-disabled');
        card.removeAttribute('aria-disabled');
        card.removeAttribute('title');
      }
    });
  }

  // Inyecta el formulario del perfil
  function loadForm(profile){
    if (!formArea || !profile) return;
    const card = $(`.mc-card[data-profile="${profile}"]`);
    const embed = $('#mc-embed-' + profile);

    // Si no hay embed, muestra mensaje
    const holder = document.createElement('div');
    holder.className = 'mc-form-wrap';
    holder.setAttribute('data-profile', profile);

    formArea.setAttribute('aria-busy','true');
    formArea.innerHTML = '';
    formArea.appendChild(holder);

    if (embed && embed.innerHTML.trim()){
      holder.innerHTML = embed.innerHTML;
    } else {
      holder.innerHTML = `
        <div class="mc-form-fallback">
          <p>No se pudo cargar el formulario de <strong>${profile}</strong>.</p>
          <p>Si el problema persiste, contacta al equipo.</p>
        </div>`;
    }

    // Enfocar título accesible si existe
    const head = $('h1, h2, h3, legend, [role="heading"]', holder);
    if (head){ head.setAttribute('tabindex','-1'); }

    // Scroll al formulario
    try{
      holder.scrollIntoView({ behavior: 'smooth', block: 'start' });
      setTimeout(() => head && head.focus({ preventScroll: true }), 300);
    }catch(_){}

    // Contact Form 7 re-init (si está disponible)
    try{
      if (window.wpcf7 && typeof window.wpcf7.init === 'function') {
        // CF7 5.8+
        $$('form.wpcf7-form', holder).forEach(f => window.wpcf7.init(f));
      } else if (window.wpcf7 && window.wpcf7.api && typeof window.wpcf7.api.initForm === 'function') {
        // compat versiones viejas
        $$('form.wpcf7-form', holder).forEach(f => window.wpcf7.api.initForm(f));
      }
      document.dispatchEvent(new CustomEvent('wpcf7init', { detail: { target: $('form', holder) }, bubbles: true }));
    }catch(_){}

    // Analítica opcional
    try{ window.dataLayer && window.dataLayer.push({ event: 'mc_select_profile', profile }); }catch(_){}

    formArea.setAttribute('aria-busy','false');
  }

  // Click/teclado en cards
  function bindCardEvents(){
    $$('.mc-card').forEach(card => {
      // Evitar múltiples binds
      if (card.dataset.bound === '1') return;
      card.dataset.bound = '1';

      const go = () => {
        if (card.classList.contains('is-disabled')) return;
        const p = card.dataset.profile;
        if (!p) return;
        history.pushState({ p }, '', '#'+p);
        setActiveCard(p);
        loadForm(p);
      };

      card.addEventListener('click', go);
      card.addEventListener('keydown', e => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); go(); }
      });

      // Accesibilidad básica
      card.setAttribute('role','button');
      card.setAttribute('tabindex','0');
      card.setAttribute('aria-controls','mc-form-area');
      card.setAttribute('aria-expanded','false');
    });
  }

  // Abre desde hash al cargar o al navegar atrás/adelante
  function openFromHash(){
    const p = getHashProfile();
    if (!p) return;
    const card = $(`.mc-card[data-profile="${p}"]`);
    if (!card || card.classList.contains('is-disabled')) return;
    setActiveCard(p);
    loadForm(p);
  }

  // Init
  document.addEventListener('DOMContentLoaded', () => {
    // Preparar cards (deshabilitar las que no tengan form)
    gateCards();
    bindCardEvents();

    // Si hay hash válido, abrir
    openFromHash();

    // Si no hay hash pero quieres abrir la primera card habilitada por defecto:
    // const first = $('.mc-card:not(.is-disabled)');
    // if (first && !location.hash) { first.click(); }
  });

  // Volver/adelante del navegador conserva estado
  window.addEventListener('popstate', openFromHash);
})();
