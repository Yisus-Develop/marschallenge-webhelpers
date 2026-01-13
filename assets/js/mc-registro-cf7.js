(function($){
  'use strict';

  // ===== i18n helper (lee cadenas localizadas desde PHP con wp_localize_script) =====
  const T = (k, fallback='') => (window.MCI18N && MCI18N[k]) || fallback;

  // ===== Constantes / Utils =====
  const OTHER = '__other__'; // token único para "Otro…"
  const debounce = (fn, wait=300) => { let t; return (...a)=>{ clearTimeout(t); t=setTimeout(()=>fn(...a), wait); }; };

  // Mostrar / ocultar campo "pais_otro"
  function toggleOtro($select, $inputOtro){
    const v = $select.val();
    const $wrap = $inputOtro.closest('.wpcf7-form-control-wrap');
    const show = (v === OTHER);
    $inputOtro.toggle(show).prop('required', show);
    if ($wrap.length) $wrap.toggle(show);
    if (!show) $inputOtro.val('');
  }

  function parseCitiesPayload(data){
    const arr = Array.isArray(data) ? data : (data.items || data.data || data.cities || data.results || []);
    return arr.map(it => it?.name || it?.city || it?.title || it?.label || it?.nombre).filter(Boolean);
  }

  async function fetchCities(countryParam, stateParam, q, limit){
    const url = new URL(MCGEO.cities);
    url.searchParams.set('country', countryParam);
    if (stateParam) url.searchParams.set('state', stateParam);
    if (q) url.searchParams.set('q', q);
    url.searchParams.set('limit', limit || (q ? 100 : 500));
    const res = await fetch(url.toString(), { credentials: 'same-origin' });
    if (!res.ok) throw new Error('HTTP '+res.status);
    return parseCitiesPayload(await res.json());
  }

  function ensureCountBadge($scope){
    let $badge = $scope.find('.mc-city-count');
    if (!$badge.length){
      $scope.find('.mc-ciudad-input').after('<span class="mc-city-count"></span>');
      $badge = $scope.find('.mc-city-count');
    }
    return $badge;
  }

  function setCount($scope, n){
    const $badge = ensureCountBadge($scope);
    if (n && n > 0){ $badge.text(`(${n})`).css('opacity', .0).show(); }
    else { $badge.text('').hide(); }
  }

  function setHint($scope, msg){
    let $hint = $scope.find('.mc-city-hint');
    if (!$hint.length){
      $hint = $('<small class="mc-city-hint" style="display:block;margin-top:4px;opacity:.75"></small>');
      $scope.find('.mc-ciudad-input').after($hint);
    }
    $hint.text(msg || '').toggle(!!msg);
  }

  // Siempre habilitar ciudad (input + fieldset ancestros)
  function forceEnableCity($scope){
    const $input = $scope.find('.mc-ciudad-input');
    $input.prop('disabled', false).removeAttr('disabled').prop('readonly', false).removeClass('is-disabled');
    $input.parents('fieldset[disabled]').each(function(){
      this.disabled = false; this.removeAttribute('disabled');
    });
  }

  // Observadores para evitar que otro script vuelva a poner disabled
  function installCityGuards($scope){
    const input = $scope.find('.mc-ciudad-input')[0];
    if (!input) return;

    const mo1 = new MutationObserver(muts=>{
      muts.forEach(m=>{
        if (m.attributeName === 'disabled' && input.disabled){
          input.disabled = false; input.removeAttribute('disabled');
        }
      });
    });
    mo1.observe(input, { attributes: true, attributeFilter: ['disabled'] });

    $scope.find('fieldset').toArray().forEach(fs=>{
      const mo2 = new MutationObserver(muts=>{
        muts.forEach(m=>{
          if (m.attributeName === 'disabled' && fs.disabled){
            fs.disabled = false; fs.removeAttribute('disabled');
            forceEnableCity($scope);
          }
        });
      });
      mo2.observe(fs, { attributes: true, attributeFilter: ['disabled'] });
    });
  }

  // ===== Setup por formulario =====
  function setupForm(form){
    const $form = $(form);

    // Datalist único por form
    let $dl = $form.find('[id^="mc-ciudades"]');
    if (!$dl.length){
      const id = 'mc-ciudades-' + Math.random().toString(36).slice(2);
      const $input = $form.find('.mc-ciudad-input');
      $input.attr('list', id);
      $dl = $('<datalist>').attr('id', id).appendTo($input.parent());
    }

    // Placeholder traducible
    $form.find('.mc-ciudad-input')
      .attr('placeholder', T('city_placeholder','Escribe 2+ letras para buscar…'));

    // Habilitar y proteger
    forceEnableCity($form);
    installCityGuards($form);

    // Estado inicial de "pais_otro"
    const $pais = $form.find('.mc-pais');
    toggleOtro($pais, $form.find('.mc-pais-otro'));

    // Mensaje inicial según país actual
    const val = $pais.val();
    if (!val){
      setHint($form, T('hint_select_country','Selecciona un país para buscar o escribe tu ciudad.'));
    } else if (val === OTHER){
      setHint($form, T('hint_type_exact','Escribe tu ciudad tal cual aparece en tu documento.'));
    } else {
      setHint($form, T('hint_type_2','Escribe 2+ letras para buscar (si no aparece, escríbela tal cual).'));
    }
  }

  // ===== Eventos =====

  // Cambio de PAÍS
  $(document).on('change', '.mc-pais', function(){
    const $form  = $(this).closest('form');
    const $dl    = $form.find('[id^="mc-ciudades"]');
    const val    = $(this).val();

    // Toggle "otro"
    toggleOtro($(this), $form.find('.mc-pais-otro'));

    // Reset de ciudad
    $form.find('.mc-ciudad-input').val('');
    $dl.empty();
    setCount($form, 0);

    // Siempre habilitado
    forceEnableCity($form);
    setTimeout(()=> forceEnableCity($form), 0);
    setTimeout(()=> forceEnableCity($form), 250);

    // Mensajes traducidos
    if (!val){
      setHint($form, T('hint_select_country','Selecciona un país para buscar o escribe tu ciudad.'));
      return;
    }
    if (val === OTHER){
      setHint($form, T('hint_type_exact','Escribe tu ciudad tal cual aparece en tu documento.'));
      return;
    }
    setHint($form, T('hint_type_2','Escribe 2+ letras para buscar (si no aparece, escríbela tal cual).'));
  });

  // Búsqueda con debounce (2+ letras) — datalist
  const doSearch = debounce(async function(input){
    const $form  = $(input).closest('form');
    const $pais  = $form.find('.mc-pais');
    const $dl    = $form.find('[id^="mc-ciudades"]');

    const country = $pais.val();
    const slug    = $pais.find('option:selected').data('slug') || '';
    const q       = (input.value || '').trim();

    if (!country || country===OTHER || q.length < 2){
      $dl.empty(); setCount($form, 0);
      if (country && country!==OTHER && q.length < 2){
        setHint($form, T('hint_type_2','Escribe 2+ letras para buscar (si no aparece, escríbela tal cual).'));
      }
      return;
    }

    try{
      let items = await fetchCities(country, '', q, 100);
      if (!items.length && slug && slug!==country){ items = await fetchCities(slug, '', q, 100); }
      if (!items.length && country.length===2){ items = await fetchCities(country.toUpperCase(), '', q, 100); }

      let html = '';
      items.forEach(name => { html += `<option value="${name}">`; });
      $dl.html(html);
      setCount($form, items.length);

      if (!items.length) setHint($form, T('hint_no_results','No hay sugerencias para esa búsqueda. Puedes escribir tu ciudad tal cual.'));
      else setHint($form, '');
    }catch(e){
      $dl.empty(); setCount($form, 0);
      setHint($form, T('hint_no_service','No hay sugerencias ahora. Escribe tu ciudad tal cual.'));
    }
  }, 300);

  $(document).on('input', '.mc-ciudad-input', function(){ doSearch(this); });

  // ===== Copiar UI -> hidden CF7 antes de enviar =====
  document.addEventListener('submit', (ev) => {
    const form = ev.target.closest('.wpcf7 form');
    if (!form) return;
    const paisUI   = form.querySelector('.mc-pais');
    const ciudadUI = form.querySelector('.mc-ciudad-input');
    const paisH    = form.querySelector('input[name="pais_cf7"]');
    const ciudadH  = form.querySelector('input[name="ciudad_cf7"]');
    if (paisUI && paisH) {
      const v = (paisUI.value === OTHER)
        ? (form.querySelector('.mc-pais-otro')?.value || '')
        : (paisUI.value || '');
      paisH.value = v;
    }
    if (ciudadUI && ciudadH) ciudadH.value = ciudadUI.value || '';
  }, true);

  // ===== Inits =====
  $(function(){
    $('.wpcf7 form').each(function(){ setupForm(this); });
  });

  // CF7 reinit (cuando CF7 vuelve a montar el form)
  document.addEventListener('wpcf7init', function(e){ setupForm(e.target); }, false);

  // Redirección CF7 (modo sin Paso 2: siempre confirmación)
  document.addEventListener('wpcf7mailsent', function(e) {
    try {
      const form = e.target; if (!form) return;
      const perfil = (form.querySelector('[name="perfil"]')?.value || '').toLowerCase();
      const base = location.origin + location.pathname.split('#')[0].split('?')[0];
      const url = base + '?step=confirmacion&perfil=' + encodeURIComponent(perfil);
      location.href = url;
    } catch(err) {}
  }, false);

})(jQuery);
