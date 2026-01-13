<?php
if ( ! defined('ABSPATH') ) exit;

/**
 * Shortcode: [mc_tabs_title_sync]
 * - Sincroniza el H1 con la pestaña activa del Tabs (JS minificado).
 * - Muestra una ayuda compacta (chip) sólo:
 *    · en el editor de Elementor
 *    · o en front-end si el usuario es ADMIN (manage_options)
 * - Forzar visibilidad/estado: doc="1" | doc="open"
 *    Ej: [mc_tabs_title_sync doc="open"]
 */
add_action('init', function(){
  add_shortcode('mc_tabs_title_sync', function($atts){
    static $done = false;
    if ($done) return ''; // evita inyectar dos veces en la misma página
    $done = true;

    $a = shortcode_atts([
      'doc'  => '',   // '', '1', 'open'
      'open' => ''    // compat
    ], $atts, 'mc_tabs_title_sync');

    // ¿Editor de Elementor?
    $is_editor = false;
    if ( class_exists('\Elementor\Plugin') ) {
      try {
        $inst = \Elementor\Plugin::$instance ?? null;
        if ( $inst && isset($inst->editor) && method_exists($inst->editor,'is_edit_mode') ) {
          $is_editor = (bool) $inst->editor->is_edit_mode();
        }
      } catch (\Throwable $e) {}
    }

    // ¿Admin logueado?
    $is_admin_user = current_user_can('manage_options');

    // ¿Forzar doc por shortcode?
    $force_doc = in_array(strtolower($a['doc']), ['1','open'], true) || strtolower($a['open']) === '1';

    // Mostrar doc si: editor || admin || forzado
    $show_doc = $is_editor || $is_admin_user || $force_doc;
    $doc_open = strtolower($a['doc']) === 'open' || strtolower($a['open']) === '1';

    ob_start();

    // ===== Ayuda compacta (chip) — sólo para editor/admin/forzado =====
    if ($show_doc): ?>
<style>
  .mc-tabs-hint{display:<?php echo $show_doc ? 'block' : 'none'; ?>; margin-top:12px}
  .mc-tabs-hint summary.mc-chip{
    list-style:none; display:inline-flex; align-items:center; gap:6px;
    background:#eef5ff; border:1px solid #cfe1ff; color:#174ea6;
    padding:6px 10px; border-radius:999px; font:500 12px/1.2 system-ui,Segoe UI,Roboto,sans-serif; cursor:pointer;
  }
  .mc-tabs-hint summary.mc-chip::-webkit-details-marker{display:none}
  .mc-tabs-hint .mc-doc{
    margin-top:10px; padding:12px; border:1px dashed #cfe1ff; background:#f9fbff; border-radius:8px
  }
  .mc-tabs-hint .mc-doc pre{white-space:pre-wrap; margin:.5em 0 0}
  .mc-tabs-hint .mc-doc code{background:#f0f3f8; padding:1px 5px; border-radius:4px}
</style>
<details class="mc-tabs-hint" <?php echo $doc_open ? 'open' : ''; ?>>
  <summary class="mc-chip">
    <svg viewBox="0 0 24 24" width="14" height="14" fill="none" stroke="currentColor"><path d="M12 20v-8m0-4h.01M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
    MC Tabs · ayuda
  </summary>
  <div class="mc-doc">
    <strong>MC · Dynamic Title para Tabs</strong>
    <ol>
      <li><b>Tabs (widget)</b> → Avanzado → <code>ID de CSS</code> = <code>lp-tabs</code></li>
      <li><b>Heading (título)</b> → Avanzado → Atributos:
<pre>data-tab-title-source | #lp-tabs
data-home-index       | 1
data-home-prefix      | Mars Challenge
data-country-source   | .elementor-widget-post-title .elementor-heading-title
data-home-layout      | stack
data-tab-override-2   | Educación en %country%
data-tab-override-3   | Aliados en %country%
data-tab-override-4   | Agenda de %country%
data-tab-override-5   | Contacto – %country%</pre>
        <small>Tokens: <code>%country%</code> / <code>{{country}}</code> / <code>[[country]]</code> y <code>{{tab}}</code>.</small>
      </li>
    </ol>
    <hr>
    <b>Tips rápidos</b>
    <ul>
      <li><i>“Conoce más” sólo si hay URL:</i> al botón pon clase <code>mc-cta</code> y el Link con <i>ACF Path: URL</i>.</li>
      <li><i>Ocultar descripción vacía:</i> al Text Editor pon clase <code>mc-desc</code> + <i>ACF Path: Text</i>.</li>
    </ul>
  </div>
</details>
<?php endif; ?>

<!-- === JS minificado: sincroniza el H1 con la pestaña activa (Tabs nuevo y clásico) === -->
<script>
!function(){function e(e){return String(e).replace(/[&<>\"']/g,(function(e){return{"&":"&amp;","<":"&lt;",">":"&gt;","\"":"&quot;","'":"&#39;"}[e]}))}function t(e){let t=e.getAttribute("data-country")||"";if(!t||/^\[.*\]$/.test(t)){const n=e.getAttribute("data-country-source")||".elementor-widget-post-title .elementor-heading-title, h1.entry-title",r=document.querySelector(n);t=r?r.textContent.trim():(document.title||"").split(/[–|\-|\|]/)[0].trim()}return t}function n(e,{country:t,tab:n}){return e?String(e).replace(/\{\{\s*country\s*\}\}|\[\[\s*country\s*\]\]|%country%/gi,t).replace(/\{\{\s*tab\s*\}\}/gi,n):e}function r(o){const a=o.getAttribute("data-tab-title-source")||"#lp-tabs",i=document.querySelector(a);if(!i)return;const l=o.querySelector(".elementor-heading-title, h1, h2, h3, h4, h5, h6");if(!l)return;const s=parseInt(o.getAttribute("data-home-index")||"1",10),c=o.getAttribute("data-home-prefix")||"",u=(o.getAttribute("data-home-layout")||"").toLowerCase(),d=i.querySelector(".e-n-tabs-heading"),f=()=>{const e=d&&d.querySelector('.e-n-tab-title[aria-selected="true"]');if(e)return parseInt(e.getAttribute("data-tab-index")||"1",10);const t=i.querySelectorAll(".elementor-tab-title");for(let e=0;e<t.length;e++)if(t[e].classList.contains("elementor-active"))return e+1;return 1},b=()=>{const e=d&&d.querySelector('.e-n-tab-title[aria-selected="true"] .e-n-tab-title-text');if(e)return e.textContent.trim();const t=i.querySelector(".elementor-tab-title.elementor-active");return t?t.textContent.trim():""};function m(){const a=f(),m=b(),y=t(o),g=o.getAttribute("data-tab-override-"+a);g?l.textContent=n(g,{country:y,tab:m}):a===s?"stack"===u?l.innerHTML='<span class="mc-title-prefix">'+e(c)+'</span> <span class="mc-title-country">'+e(y)+"</span>":l.textContent=(c+" "+y).trim():l.textContent=m}(d||i).addEventListener("click",(e=>{e.target.closest(".e-n-tab-title,.elementor-tab-title")&&setTimeout(m,0)})),(d||i).addEventListener("keydown",(e=>{"Enter"!==e.key&&" "!==e.key&&"Spacebar"!==e.key||setTimeout(m,0)})),new MutationObserver(m).observe(i,{subtree:!0,attributes:!0,attributeFilter:["class","aria-selected"]}),m()}function o(){document.querySelectorAll("[data-tab-title-source],#lp-dyn-title").forEach(r)}"loading"===document.readyState?document.addEventListener("DOMContentLoaded",o):o(),window.elementorFrontend&&window.elementorFrontend.on&&(window.elementorFrontend.on("frontend:init",o),window.elementorFrontend.on("components:init",o),window.elementorFrontend.on("document:loaded",o))}();
</script>

<!-- === Ajustes funcionales: CTA visible sólo con URL + ocultar descripción vacía === -->
<style>
/* Oculta el botón si el <a> no tiene URL real */
.elementor-element.mc-cta a.elementor-button-link[href=""],
.elementor-element.mc-cta a.elementor-button-link:not([href]),
.elementor-element.mc-cta a.elementor-button-link[href="#"]{
  display:none !important;
}
</style>
<script>
document.addEventListener('DOMContentLoaded',function(){
  // Seguridad extra para CTA sin URL (oculta el widget entero si no hay href)
  document.querySelectorAll('.elementor-element.mc-cta').forEach(function(w){
    var a=w.querySelector('a.elementor-button-link'); if(!a) return;
    var href=(a.getAttribute('href')||'').trim();
    if(!href || href==='#'){ w.style.display='none'; }
  });
  // Ocultar descripción si quedó vacía (limpiando &nbsp;)
  document.querySelectorAll('.elementor-element.mc-desc .elementor-widget-container')
    .forEach(function(box){
      var t=(box.innerText||'').replace(/\u00A0/g,' ').trim();
      if(!t){ var el=box.closest('.elementor-element'); if(el) el.style.display='none'; }
    });
});
</script>
<?php
    return ob_get_clean();
  });
});
