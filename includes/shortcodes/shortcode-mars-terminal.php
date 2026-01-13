<?php
if (!defined('ABSPATH')) exit;

/**
 * Shortcode: [webh_mars_terminal id="123" root="#mars-hero"]
 * - id: ID del post (CPT terminal_mars) donde guardas CSS/JS
 * - root: selector del contenedor HTML en Elementor (#id o .clase)
 *
 * Este shortcode YA NO imprime HTML. Solo inyecta CSS/JS con scope al root.
 */

function webh_mars_terminal_shortcode($atts) {
  ob_start();
  ?>
  <style>
  .mch-container {
    --main-bg: #1D142B;
    --main-border: #4A477A;
    --hover-bg: #2C2133;
    --yellow: #FBBD2F;
    --text: #595B59;
    --header-bg: #fff;
    --header-text: #232323;
    font-family: 'Determination Mono Web', monospace;
    background: var(--main-bg);
    border: 2px solid var(--main-border);
    border-radius: 13px;
    width: 100%;
    max-width: 1050px;
    margin: 0 auto;
    padding: 0 0 32px 0;
    position: relative;
    overflow: visible;
}

/* Barra ventana */
.mch-windowbar {
    display: flex;
    align-items: center;
    gap: 8px;
    background: var(--header-bg);
    padding: 10px 0 10px 16px;
    border-bottom: 2px solid var(--main-border);
    border-radius: 12px 12px 0 0;
    height: 30px;
    position: relative;
}
.mch-dot { display:inline-block; width:15px; height:15px; border-radius:4px; margin-right:6px; margin-top: 2px;}
.mch-dot-close { background:#ff3b3b; }
.mch-dot-min   { background:#FBBD2F; }
.mch-window-title {
    flex:1; text-align:center; font-size: 17px; color: #232323; font-weight: normal; letter-spacing: 0.03em; font-family: inherit; margin-left: -56px;
    overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}

/* Header columnas */
.mch-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    background: var(--main-border);
    color: var(--header-bg);
    font-size: 15px;
    font-weight: normal;
    letter-spacing: 0.01em;
    padding: 13px 38px 13px 28px;
    border-bottom: 1.7px solid var(--main-border);
}
.mch-header-item {
    flex: 1;
    text-align: left;
    color: var(--header-bg);
    opacity: 0.96;
    transition: color .18s;
    padding: 0 10px;
    font-weight: normal;
    overflow: hidden;
    text-overflow: ellipsis;
}
.mch-header-item.active {
    color: var(--yellow);
    text-shadow: 0 0 2px #fbbd2f44;
}
.mch-header-item:nth-child(1) { flex-basis: 26%; }
.mch-header-item:nth-child(2) { flex-basis: 48%; }
.mch-header-item:nth-child(3) { flex-basis: 26%; }

/* Filas tabla */
.mch-content { padding: 0 26px 0 19px; margin-top: 8px; }
.mch-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    padding: 21px 0 20px 0;
    border-bottom: 1.6px solid #292047;
    border-radius: 0;
    cursor: pointer;
    background: var(--main-bg);
    color: var(--text);
    font-size: 1em;
    position: relative;
    transition: background .17s, color .14s;
    font-weight: normal;
}
.mch-row:last-child { border-bottom: none; }

.mch-row.active,
.mch-row.hovering {
    background-color: #2C2133 !important;
}
.mch-row.active .mch-title,
.mch-row.hovering .mch-title,
.mch-row.active .mch-description,
.mch-row.hovering .mch-description,
.mch-row.active .mch-action a,
.mch-row.hovering .mch-action a {
    color: #FBBD2F !important;
    font-weight: normal !important;
}

.mch-title { flex-basis: 32%; color: inherit; font-weight: normal; font-size: 1em;     line-height: 100%; padding: 0 14px 0 2px; }
.mch-description { flex-basis: 50%; color: inherit; padding: 0 10px; font-size: 1em;    line-height: 100%; font-weight: normal; }
.mch-action { flex-basis: 18%; text-align: right;    line-height: 100%; padding: 0 12px 0 0; }
.mch-action a {
    color: var(--yellow);
    text-decoration: underline;
    line-height: 100%;
    font-size: 1em;
    transition: color .16s;
    font-weight: normal;
}
.mch-action a:hover { color: #fff; text-shadow: 0 0 2px #fff3; font-weight: normal; }

.mch-header-item { white-space: nowrap; }
.mch-windowbar { min-width: 0; }
.mch-windowbar { min-width: 0; }
.mch-window-title {
  flex: 1 1 auto; margin-left: 0; min-width: 0;
  overflow: hidden; text-overflow: ellipsis; white-space: nowrap;
}

/* Responsive Desktop */
@media (max-width: 900px) {
    .mch-container { font-size: 12.2px; }
    .mch-header { font-size: 12px; padding: 8px 8px 8px 8px; }
    .mch-content { padding: 0 2px; }
}
/* Mobile: table stacked + labels */
@media (max-width: 700px) {
  .mch-header { display: none; }
  .mch-row { flex-direction: column; align-items: flex-start; padding: 10px 2px;}
  .mch-title, .mch-description, .mch-action {
    flex-basis: 100%; width: 100%; padding: 0; text-align: left; margin-bottom: 7px;
    position: relative;
  }
  .mch-action { margin-bottom: 0; }
  .mch-title::before,
  .mch-description::before,
  .mch-action::before {
    content: attr(data-label);
    display: block;
    font-size: 12px;
    color: #b5b4c5;
    opacity: .7;
    margin-bottom: 2px;
    letter-spacing: .02em;
    text-transform: none;
    font-family: inherit;
    font-weight: normal;
  }
}


.mch-popup {
  display: none !important;
  left: 0; top: 0;
  /* width: lo que necesites, height: auto o lo que uses */
}
.mch-popup.active { display: block !important; }

.mch-close-popup:hover, .mch-close-popup:focus {
  outline: 2px solid #FBBD2F !important;
  box-shadow: 0 0 8px #FBBD2F66 !important;
  cursor: pointer !important;
}



  </style>
  <script>

document.addEventListener('DOMContentLoaded', function(){
// Helper corto
const $$ = s => Array.from(document.querySelectorAll(s));

// Limpia la clase 'active' en todos los popups si está en móvil (previene popup abierto por defecto)
if (window.innerWidth <= 700) {
  $$('.mch-popup.active').forEach(p => p.classList.remove('active'));
}

// 1. Filas de la tabla
const filas = $$('.mch-row');
if (filas.length) {
    // Solo la primera inicia como activa si ninguna tiene 'active'
    if (![...filas].some(r => r.classList.contains('active'))) {
        filas[0].classList.add('active');
    }
    filas.forEach(row => {
        row.addEventListener('click', () => {
            filas.forEach(r => r.classList.remove('active'));
            row.classList.add('active');
        });
        row.addEventListener('mouseenter', () => row.classList.add('hovering'));
        row.addEventListener('mouseleave', () => row.classList.remove('hovering'));
        row.setAttribute('tabindex', '0');
        row.addEventListener('keydown', ev => {
            if (ev.key === 'Enter' || ev.key === ' ') {
                ev.preventDefault(); row.click();
            }
        });
    });
}

// 2. Popup inicial solo en desktop
const initialPopup = document.querySelector('.mch-popup[data-slug]');
if (initialPopup && window.innerWidth > 700) {
    initialPopup.classList.add('active');
}

// 3. Click en fila: muestra el popup relacionado y autocierra en móvil
$$('.mch-row').forEach(row => {
    row.addEventListener('click', () => {
        const slug = row.getAttribute('data-slug');
        if (!slug) return;
        $$('.mch-popup.active').forEach(p => p.classList.remove('active'));
        const popup = document.querySelector(`.mch-popup[data-slug="${slug}"]`);
        if (popup) {
            popup.classList.add('active');
            autoClosePopupInMobile(popup); // ← SOLO aquí va
        }
    });
});

// 4. Click en cerrar dentro del popup
$$('.mch-close-popup').forEach(btn => {
    btn.addEventListener('click', function(ev) {
        ev.stopPropagation();
        let popup = this.closest('.mch-popup');
        if (popup) popup.classList.remove('active');
        // Después de 100ms, si ningún popup está activo, reactiva el inicial solo en desktop
        setTimeout(() => {
            if (
                !document.querySelector('.mch-popup.active') &&
                initialPopup &&
                window.innerWidth > 700
            ) {
                initialPopup.classList.add('active');
            }
        }, 100);
    });
});

// 5. Función autocierre para móvil
function autoClosePopupInMobile(popup, ms = 8000) {
    if (window.innerWidth <= 700 && popup) {
        setTimeout(() => {
            if (popup.classList.contains('active')) {
                popup.classList.remove('active');
            }
        }, ms);
    }
}
});


  </script>
  <!-- Aquí puedes imprimir el HTML base si quieres, o dejar solo el CSS/JS y el HTML lo pones en Elementor -->
  <?php
  return ob_get_clean();
}
add_shortcode('webh_mars_terminal', 'webh_mars_terminal_shortcode');
