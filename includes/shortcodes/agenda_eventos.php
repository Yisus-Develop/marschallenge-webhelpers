<?php
// ===== Helper: post_id actual (compatible con Elementor) =====
if (!function_exists('mc_el_current_post_id')) {
  function mc_el_current_post_id() {
    if (function_exists('get_the_ID')) {
      $maybe = get_the_ID();
      if (!empty($maybe)) return $maybe;
    }
    if (class_exists('\Elementor\Plugin')) {
      try {
        $inst = \Elementor\Plugin::$instance ?? null;
        if ($inst && isset($inst->documents) && method_exists($inst->documents, 'get_current')) {
          $doc = $inst->documents->get_current();
          if ($doc && method_exists($doc, 'get_main_id')) {
            $pid = $doc->get_main_id();
            if (!empty($pid)) return $pid;
          }
        }
      } catch (\Throwable $e) {}
    }
    if (function_exists('get_queried_object_id')) {
      $q = get_queried_object_id();
      if (!empty($q)) return $q;
    }
    return 0;
  }
}

/**
 * Shortcode: [mc_agenda_eventos_inline]
 *
 * Atributos:
 *  - scope="future|past|all"  (default: future)
 *  - post_id=""               (forzar ID de landing si lo usas en plantillas globales)
 *  - repeater="evento_datos"  (tu repeater)
 *  - class="", accent="#f7b500", btn_text="Conoce más"
 *  - debug="", "1" o "open"   (panel de depuración solo para editores)
 */
add_action('init', function () {
  add_shortcode('mc_agenda_eventos_inline', function ($atts) {
    static $assets_done = false;

    // === i18n textdomain (ajústalo al de tu plugin/tema) ===
    $td = 'marschallenge';

    $a = shortcode_atts([
      'scope'       => 'all',
      'post_id'     => '',
      'repeater'    => 'evento_datos',
      'class'       => '',
      'accent'      => '#f7b500',
      'btn_text'    => 'Conoce más',
      'debug'       => '',
    ], $atts, 'mc_agenda_eventos_inline');

    // Resolver post_id
    $post_id = $a['post_id'] !== '' ? intval($a['post_id']) : mc_el_current_post_id();
    if (!$post_id) {
      return current_user_can('edit_pages')
        ? '<div class="mc-agenda">'.esc_html__('No se pudo resolver el post_id. Usa', $td).' <code>post_id="123"</code> '.esc_html__('o coloca el shortcode en la landing.', $td).'</div>'
        : '';
    }

    if (!function_exists('get_field')) {
      return current_user_can('edit_pages')
        ? '<div class="mc-agenda">'.esc_html__('ACF no está activo.', $td).'</div>'
        : '';
    }

    // Leer repeater
    $rows = get_field($a['repeater'], $post_id);
    $rows_count = (is_array($rows) ? count($rows) : 0);

    // Mapas traducibles
    $types_map = apply_filters('mc_agenda_types_map', [
      'hackatones' => esc_html__('Hackatones', $td),
      'talleres'   => esc_html__('Talleres',   $td),
      'charlas'    => esc_html__('Charlas',    $td),
      'networking' => esc_html__('Networking', $td),
    ]);

    $format_map = apply_filters('mc_agenda_format_map', [
      'presencial' => esc_html__('Presencial',           $td),
      'virtual'    => esc_html__('Virtual',              $td),
      'hibrido'    => esc_html__('Presencial / Virtual', $td),
    ]);

    // Etiquetas traducibles
    $label_todos        = esc_html__('Todos', $td);
    $label_no_eventos_1 = esc_html__('No hay eventos aún.', $td);
    $label_no_eventos_2 = esc_html__('No hay eventos para mostrar.', $td);

    // Botón (si el usuario no pasa uno custom, usamos cadena traducible)
    $btn_text = ($a['btn_text'] !== 'Conoce más')
      ? $a['btn_text']
      : apply_filters('mc_agenda_btn_text', esc_html__('Conoce más', $td));

    // Fechas
    $tz       = wp_timezone();
    $now_ts   = (new DateTimeImmutable('now', $tz))->getTimestamp();
    $items    = [];
    $tipos_presentes = [];

    // Acepta 'Y-m-d H:i:s' (tu config) y también 'Y-m-d'
    $parse_dt = function($str) use($tz) {
      $str = trim((string)$str);
      if ($str === '') return null;
      $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i:s', $str, $tz);
      if (!$dt) $dt = DateTimeImmutable::createFromFormat('Y-m-d H:i', $str, $tz);
      if (!$dt) $dt = DateTimeImmutable::createFromFormat('Y-m-d', $str, $tz);
      return $dt ?: null;
    };

    if ($rows_count) {
      foreach ($rows as $r) {
        $title  = trim((string)($r['nombre_evento'] ?? ''));
        $tipo   = trim((string)($r['tipo_de_evento'] ?? ''));
        $format = trim((string)($r['event_format']   ?? ''));
        $start  = (string)($r['event_start']         ?? '');
        $end    = (string)($r['event_end']           ?? '');
        $place  = trim((string)($r['lugarsede']      ?? ''));
        $url    = esc_url((string)($r['event_url']   ?? ''));

        if ($title === '' || $start === '') continue;

        $dt = $parse_dt($start);
        if (!$dt) continue;
        $ts = $dt->getTimestamp();

        // Filtrar por scope
        $keep = true;
        if ($a['scope'] === 'future') $keep = ($ts >= $now_ts);
        elseif ($a['scope'] === 'past') $keep = ($ts < $now_ts);
        if (!$keep) continue;

        $dt_end = $parse_dt($end);
        $ts_end = $dt_end ? $dt_end->getTimestamp() : null;

        $day   = date_i18n('j', $ts);
        $month = date_i18n('M', $ts); // localizable por WP
        $time  = date_i18n('H:i', $ts).($ts_end ? '–'.date_i18n('H:i', $ts_end) : '');

        $tipo_slug  = ($tipo !== '' ? sanitize_title($tipo) : '');
        $tipo_label = $types_map[$tipo] ?? ($tipo ? ucfirst($tipo) : '');
        if ($tipo_slug) $tipos_presentes[$tipo_slug] = $tipo_label;

        $format_label = $format_map[$format] ?? ($format ? ucfirst($format) : '');

        $items[] = [
          'ts'      => $ts,
          'day'     => $day,
          'month'   => $month,
          'time'    => $time,
          'title'   => $title,
          'place'   => $place,
          'format'  => $format_label,
          'url'     => $url,
          'tipo'    => $tipo_slug,
          'tipo_lb' => $tipo_label,
        ];
      }
    }

    // Orden: futuros↑ / pasados↓
    usort($items, function($A,$B) use($a){
      return $a['scope']==='past' ? ($B['ts'] <=> $A['ts']) : ($A['ts'] <=> $B['ts']);
    });

    // Render
    ob_start();

    // Estilos + JS una sola vez
    if (!$assets_done) {
      $assets_done = true; ?>
<style>
  .mc-agenda{ --mc-accent: var(--mc-accent-fallback, #f7b500); --mc-ink:#221b29; --mc-muted:#6b6b6b }
  .mc-agenda .mc-filter{display:flex;flex-wrap:wrap;gap:10px;margin:0 0 18px}
  .mc-agenda .mc-chip{padding:8px 14px;border:2px solid var(--mc-accent);border-radius:999px;background:#fff;color:var(--mc-ink);cursor:pointer;font:600 13px/1.1 system-ui,Segoe UI,Roboto,sans-serif;transition:all .15s ease}
  .mc-agenda .mc-chip:hover{transform:translateY(-1px)}
  .mc-agenda .mc-chip.is-active{background:var(--mc-accent);color:#111;border-color:var(--mc-accent)}
  .mc-agenda .mc-grid{display:grid;gap:14px}
  @media(min-width:680px){ .mc-agenda .mc-grid{grid-template-columns:1fr 1fr} }
  @media(min-width:1024px){ .mc-agenda .mc-grid{grid-template-columns:1fr 1fr 1fr} }
  .mc-agenda .mc-card{display:grid;grid-template-columns:64px 1fr;gap:12px;border:1px solid #eee;border-radius:12px;background:#fff;padding:14px 14px;box-shadow:0 1px 0 rgba(0,0,0,.03)}
  .mc-agenda .mc-date{display:flex;flex-direction:column;align-items:center;justify-content:center;width:64px;min-height:64px;border-radius:10px;background:rgba(247,181,0,.12);border:2px solid var(--mc-accent); color:#111}
  .mc-agenda .mc-date .mc-day{font:700 22px/1 system-ui,Segoe UI,Roboto}
  .mc-agenda .mc-date .mc-mon{font:700 12px/1 system-ui;text-transform:uppercase;letter-spacing:.04em}
  .mc-agenda .mc-body .mc-top{display:flex;flex-wrap:wrap;gap:6px 10px;align-items:center; margin:-2px 0 6px}
  .mc-agenda .mc-title{font:700 16px/1.2 system-ui,Segoe UI,Roboto;color:var(--mc-ink); margin:0}
  .mc-agenda .mc-badges{display:flex;flex-wrap:wrap;gap:6px}
  .mc-agenda .mc-badge{padding:4px 8px;border-radius:999px;background:#f2f2f2;color:#333;font:600 11px/1 system-ui}
  .mc-agenda .mc-badge.mc-type{background:rgba(247,181,0,.16); color:#111; border:1px solid rgba(247,181,0,.45)}
  .mc-agenda .mc-meta{color:var(--mc-muted);font:500 13px/1.45 system-ui;margin-top:2px}
  .mc-agenda .mc-meta .mc-dot::before{content:"•";margin:0 8px;opacity:.4}
  .mc-agenda .mc-cta{margin-top:8px}
  .mc-agenda .mc-cta a.elementor-button-link{display:inline-block;padding:10px 14px;border-radius:10px;border:1px solid var(--mc-accent);text-decoration:none}
  .mc-agenda .mc-cta a.elementor-button-link[href=""], .mc-agenda .mc-cta a.elementor-button-link:not([href]), .mc-agenda .mc-cta a.elementor-button-link[href="#"]{display:none}
</style>
<script>
document.addEventListener('click',function(ev){
  var btn = ev.target.closest('.mc-agenda .mc-chip'); if(!btn) return;
  var wrap = btn.closest('.mc-agenda'); if(!wrap) return;
  var slug = btn.getAttribute('data-type') || 'all';
  wrap.querySelectorAll('.mc-chip').forEach(function(b){ b.classList.toggle('is-active', b===btn); });
  wrap.querySelectorAll('.mc-card').forEach(function(card){
    if(slug==='all'){ card.style.display=''; return; }
    var t = (card.getAttribute('data-type')||'').split(' ');
    card.style.display = t.includes(slug) ? '' : 'none';
  });
});
</script>
<?php }

    // Wrapper
    $classes = 'mc-agenda'.($a['class'] ? ' '.esc_attr($a['class']) : '');
    printf('<div class="%s" style="--mc-accent-fallback:%s" data-source="repeater" data-scope="%s">', esc_attr($classes), esc_attr($a['accent']), esc_attr($a['scope']));

    // Si no hay filas
    if (!$rows_count) {
      echo '<div class="mc-agenda">'.esc_html($label_no_eventos_1).'</div></div>';
      return ob_get_clean();
    }

    // Si no hay items tras el filtro/scope
    if (empty($items)) {
      echo '<div class="mc-agenda">'.esc_html($label_no_eventos_2).'</div></div>';
      return ob_get_clean();
    }

    // Chips
    echo '<div class="mc-filter" role="tablist">';
    echo '<button class="mc-chip is-active" data-type="all">'.esc_html($label_todos).'</button>';
    if (!empty($tipos_presentes)) {
      asort($tipos_presentes);
      foreach ($tipos_presentes as $slug => $label) {
        echo '<button class="mc-chip" data-type="'.esc_attr($slug).'">'.esc_html($label).'</button>';
      }
    }
    echo '</div>';

    // Grid
    echo '<div class="mc-grid">';
    foreach ($items as $it) {
      $type_attr = trim($it['tipo']);
      echo '<article class="mc-card" data-type="'.esc_attr($type_attr).'">';
        echo '<div class="mc-date"><div class="mc-day">'.esc_html($it['day']).'</div><div class="mc-mon">'.esc_html($it['month']).'</div></div>';
        echo '<div class="mc-body">';
          echo '<div class="mc-top">';
            echo '<h3 class="mc-title">'.esc_html($it['title']).'</h3>';
            echo '<div class="mc-badges">';
              if (!empty($it['tipo_lb']))  echo '<span class="mc-badge mc-type">'.esc_html($it['tipo_lb']).'</span>';
              if (!empty($it['format']))   echo '<span class="mc-badge">'.esc_html($it['format']).'</span>';
            echo '</div>';
          echo '</div>';
          echo '<div class="mc-meta">';
            if (!empty($it['time']))  echo '<span class="mc-time">'.esc_html($it['time']).'</span>';
            if (!empty($it['place'])) echo '<span class="mc-dot"></span><span class="mc-place">'.esc_html($it['place']).'</span>';
          echo '</div>';
          echo '<div class="mc-cta">';
            if (!empty($it['url'])) {
              echo '<a class="elementor-button-link elementor-button" href="'.esc_url($it['url']).'" target="_blank" rel="noopener">'.esc_html($btn_text).'</a>';
            }
          echo '</div>';
        echo '</div>';
      echo '</article>';
    }
    echo '</div>'; // grid

    echo '</div>'; // wrapper

    return ob_get_clean();
  });
});
