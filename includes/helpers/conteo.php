<?php
/**
 * Conteo integral de textos para estimar traducción (WPML/DeepL).
 * Cubre: post_title, post_excerpt, post_content, slugs; Elementor; ACF (posts/terms/options);
 * menús; widgets de texto; descripciones de taxonomías; opciones varias.
 */
defined('ABSPATH') || exit;

/* =========================
 * POLYFILLS (seguro en WP viejos)
 * ========================= */
if (!function_exists('wp_get_word_count_type')) {
  function wp_get_word_count_type() { return 'words'; }
}
if (!function_exists('wp_word_count')) {
  function wp_word_count($text, $type = null) {
    $type = $type ?: wp_get_word_count_type();
    $text = (string)$text;
    $text = wp_strip_all_tags($text);
    $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, get_bloginfo('charset') ?: 'UTF-8');
    $text = trim($text);
    if ($type === 'characters_including_spaces') return mb_strlen($text, 'UTF-8');
    if ($type === 'characters_excluding_spaces') return mb_strlen(preg_replace('/\s+/u','',$text), 'UTF-8');
    // words (Unicode)
    if (preg_match_all('/\p{L}[\p{L}\p{Mn}\p{Pd}\']*/u', $text, $m)) return count($m[0]);
    return 0;
  }
}

/* =========================
 * HELPERS GENERALES
 * ========================= */
function mcwc_norm($txt){
  if (!is_string($txt) || $txt==='') return '';
  $txt = do_shortcode($txt);
  $txt = wp_strip_all_tags($txt);
  $txt = preg_replace('/\s+/u',' ', $txt);
  return trim($txt);
}
function mcwc_wc($txt){ return wp_word_count(mcwc_norm($txt), wp_get_word_count_type()); }

/* =========================
 * ELEMENTOR: extraer texto del JSON _elementor_data
 * ========================= */
function mcwc_extract_elementor_text($json){
  if (empty($json)) return '';
  if (is_string($json)) { $data = json_decode($json, true); if (json_last_error()!==JSON_ERROR_NONE) return ''; }
  else { $data = $json; }
  $bucket = [];
  $text_keys = [
    'title','text','content','editor','description','button_text','headline','html',
    'caption','before_text','after_text','label','placeholder','alt','subtitle'
  ];
  $walk = function($node) use (&$walk,&$bucket,$text_keys){
    if (!is_array($node)) return;
    if (isset($node['settings']) && is_array($node['settings'])) {
      foreach ($text_keys as $k) {
        if (!empty($node['settings'][$k]) && is_string($node['settings'][$k])) {
          $v = mcwc_norm($node['settings'][$k]);
          if ($v!=='') $bucket[] = $v;
        }
      }
    }
    foreach (['elements','innerElements','children'] as $ck) {
      if (!empty($node[$ck]) && is_array($node[$ck])) {
        foreach ($node[$ck] as $child) $walk($child);
      }
    }
    if (isset($node[0]) && is_array($node[0])) { foreach ($node as $child) $walk($child); }
  };
  $walk($data);
  return implode("\n", array_filter(array_map('trim', $bucket)));
}

/* =========================
 * ACF: extracción genérica (post/term/options)
 * ========================= */
function mcwc_acf_collect_from_array($arr, $depth=0){
  if ($depth>6) return []; // evita loops profundos
  $out = [];
  foreach ((array)$arr as $k=>$v){
    if (is_string($v)){
      $t = mcwc_norm($v);
      if ($t!=='') $out[] = $t;
    } elseif (is_array($v)){
      $out = array_merge($out, mcwc_acf_collect_from_array($v, $depth+1));
    } // objetos/ids/booleans = ignorar
  }
  return $out;
}

function mcwc_collect_acf_for_post($post_id){
  if (!function_exists('get_fields')) return [];
  $f = get_fields($post_id); if (!$f) return [];
  return mcwc_acf_collect_from_array($f);
}
function mcwc_collect_acf_for_term($term_id, $taxonomy){
  if (!function_exists('get_fields')) return [];
  $f = get_fields($taxonomy.'_'.$term_id); if (!$f) return [];
  return mcwc_acf_collect_from_array($f);
}
function mcwc_collect_acf_options(){
  if (!function_exists('get_fields')) return [];
  $f = get_fields('option'); if (!$f) return [];
  return mcwc_acf_collect_from_array($f);
}

/* =========================
 * COLECTORES POR ORIGEN
 * ========================= */

// 1) Posts/CPTs (auto-detect de CPT públicos)
function mcwc_collect_posts($args=[]){
  $defaults = [
    'include_post_types' => null, // null → detectar automáticamente CPT públicos
    'include_titles'     => true,
    'include_excerpts'   => true,
    'include_slugs'      => true,
    'include_elementor'  => true,
    'include_postmeta'   => false, // heurístico (desactivado por defecto)
    'include_acf'        => true,
    'posts_per_page'     => -1,
  ];
  $o = array_merge($defaults, $args);

  if ($o['include_post_types']===null){
    $pts = get_post_types(['public'=>true],'names');
    unset($pts['attachment']); // fuera adjuntos
  } else {
    $pts = (array)$o['include_post_types'];
  }

  $q = new WP_Query([
    'post_type'      => $pts,
    'post_status'    => 'publish',
    'posts_per_page' => $o['posts_per_page'],
    'fields'         => 'ids',
    'no_found_rows'  => true,
  ]);

  $rows = [];
  if ($q->have_posts()){
    foreach ($q->posts as $pid){
      $texts = [];

      $post = get_post($pid);
      if ($o['include_titles'])   $texts[] = get_the_title($pid);
      if ($o['include_excerpts']) $texts[] = get_the_excerpt($pid);
      $texts[] = $post ? $post->post_content : '';
      if ($o['include_slugs'] && $post)    $texts[] = $post->post_name;

      if ($o['include_elementor']){
        $el = get_post_meta($pid, '_elementor_data', true);
        if (!empty($el)) $texts[] = mcwc_extract_elementor_text($el);
      }

      if ($o['include_acf']){
        $acf_txts = mcwc_collect_acf_for_post($pid);
        if ($acf_txts) $texts = array_merge($texts, $acf_txts);
      }

      if ($o['include_postmeta']){
        // Heurística: meta públicas (no empiezan por _) y parecen texto corto/medio
        $metas = get_post_meta($pid);
        foreach ($metas as $k=>$vals){
          if (str_starts_with($k,'_')) continue;
          foreach ((array)$vals as $v){
            if (is_string($v) && strlen($v) >= 3 && strlen($v) <= 10000){
              $texts[] = $v;
            }
          }
        }
      }

      $joined = mcwc_norm(implode("\n", array_filter($texts)));
      $count  = mcwc_wc($joined);

      $rows[] = [
        'id'      => $pid,
        'type'    => get_post_type($pid),
        'title'   => get_the_title($pid),
        'url'     => get_permalink($pid),
        'words'   => $count,
      ];
    }
  }
  return $rows;
}

// 2) Menús (nav_menu_items) – cuenta label/titles
function mcwc_collect_menus(){
  $menus = wp_get_nav_menus();
  $total = [];
  foreach ($menus as $menu){
    $items = wp_get_nav_menu_items($menu->term_id);
    if (!$items) continue;
    foreach ($items as $it){
      $txts = [];
      $txts[] = $it->title ?? '';
      if (!empty($it->attr_title)) $txts[] = $it->attr_title;
      if (!empty($it->description)) $txts[] = $it->description;
      $joined = mcwc_norm(implode("\n",$txts));
      $total[] = [
        'scope' => 'menu',
        'ref'   => $menu->name.' #'.$it->ID,
        'words' => mcwc_wc($joined),
      ];
    }
  }
  return $total;
}

// 3) Widgets de texto (clásicos) – widget_text
function mcwc_collect_text_widgets(){
  $opt = get_option('widget_text'); // array de widgets de texto
  $rows = [];
  if (is_array($opt)){
    foreach ($opt as $k=>$cfg){
      if (!is_array($cfg)) continue;
      $txt = '';
      if (!empty($cfg['title'])) $txt .= $cfg['title']."\n";
      if (!empty($cfg['text']))  $txt .= $cfg['text'];
      $rows[] = [
        'scope' => 'widget_text',
        'ref'   => 'text:'.$k,
        'words' => mcwc_wc($txt),
      ];
    }
  }
  // Elementor widgets globales son posts (elementor_library), ya entran por mcwc_collect_posts()
  return $rows;
}

// 4) Taxonomías: descripciones y ACF de términos
function mcwc_collect_terms(){
  $rows = [];
  $taxes = get_taxonomies(['public'=>true],'objects');
  foreach ($taxes as $tax){
    $terms = get_terms(['taxonomy'=>$tax->name,'hide_empty'=>false]);
    if (is_wp_error($terms)) continue;
    foreach ($terms as $t){
      $txts = [];
      if (!empty($t->description)) $txts[] = $t->description;
      // slugs también cuentan si piensas traducirlos
      $txts[] = $t->slug;
      // ACF de término
      $acf = mcwc_collect_acf_for_term($t->term_id, $tax->name);
      if ($acf) $txts = array_merge($txts, $acf);
      $rows[] = [
        'scope' => 'term:'.$tax->name,
        'ref'   => $t->name.' (#'.$t->term_id.')',
        'words' => mcwc_wc(implode("\n", $txts)),
      ];
    }
  }
  return $rows;
}

// 5) Opciones (ACF Options + strings sueltas)
function mcwc_collect_options(){
  $rows = [];
  // ACF Options
  $acf_opts = mcwc_collect_acf_options();
  if ($acf_opts){
    $rows[] = [
      'scope' => 'options:acf',
      'ref'   => 'ACF Options',
      'words' => mcwc_wc(implode("\n",$acf_opts)),
    ];
  }
  // Widgets del theme (custom) u otras opciones textuales podrían mapearse aquí si las conoces
  return $rows;
}

/* =========================
 * ADMIN UI
 * ========================= */
add_action('admin_menu', function(){
  add_management_page('Word Count Report (Full)', 'Word Count Report', 'manage_options', 'mc-word-count-report-full', 'mcwc_render_report_full');
});

function mcwc_render_report_full(){
  if (!current_user_can('manage_options')) return;

  // params rápidos por URL
  $langs_destino = isset($_GET['langs']) ? max(1, intval($_GET['langs'])) : 3; // EN/PT/FR
  $factor_credito = isset($_GET['factor']) ? max(1, floatval($_GET['factor'])) : 2.0; // DeepL ~2
  $include_meta = isset($_GET['meta']) ? (bool)intval($_GET['meta']) : false;

  echo '<div class="wrap"><h1>Word Count Report (Full Mapping)</h1>';
  echo '<p>Idiomas destino: <strong>'.$langs_destino.'</strong> · Factor créditos/palabra: <strong>'.$factor_credito.'</strong> · Incluir postmeta heurístico: <strong>'.($include_meta?'Sí':'No').'</strong></p>';
  echo '<p><a href="'.esc_url(add_query_arg(['langs'=>3,'factor'=>2,'meta'=>0])).'">Preset rápido (3 idiomas, DeepL=2, sin meta)</a> · ';
  echo '<a href="'.esc_url(add_query_arg(['langs'=>5,'factor'=>2,'meta'=>1])).'">Preset amplio (5 idiomas, con meta)</a></p>';

  $totals = [
    'posts'   => 0,
    'menus'   => 0,
    'widgets' => 0,
    'terms'   => 0,
    'options' => 0,
  ];

  // POSTS/CPTs
  $post_rows = mcwc_collect_posts([
    'include_post_types' => null, // auto-detect
    'include_postmeta'   => $include_meta,
  ]);
  foreach ($post_rows as $r) $totals['posts'] += $r['words'];

  // MENÚS
  $menu_rows = mcwc_collect_menus();
  foreach ($menu_rows as $r) $totals['menus'] += $r['words'];

  // WIDGETS TEXTO
  $widget_rows = mcwc_collect_text_widgets();
  foreach ($widget_rows as $r) $totals['widgets'] += $r['words'];

  // TÉRMINOS
  $term_rows = mcwc_collect_terms();
  foreach ($term_rows as $r) $totals['terms'] += $r['words'];

  // OPCIONES
  $opt_rows = mcwc_collect_options();
  foreach ($opt_rows as $r) $totals['options'] += $r['words'];

  $total_origen = array_sum($totals);
  $creditos_estimados = (int)round($total_origen * $langs_destino * $factor_credito);

  echo '<h2>Resumen</h2>';
  echo '<ul>';
  echo '<li><strong>Total palabras (origen):</strong> '.number_format_i18n($total_origen).'</li>';
  echo '<li>Posts/CPTs: '.number_format_i18n($totals['posts']).'</li>';
  echo '<li>Menús: '.number_format_i18n($totals['menus']).'</li>';
  echo '<li>Widgets (texto): '.number_format_i18n($totals['widgets']).'</li>';
  echo '<li>Taxonomías (descripciones + ACF): '.number_format_i18n($totals['terms']).'</li>';
  echo '<li>Opciones (ACF Options): '.number_format_i18n($totals['options']).'</li>';
  echo '</ul>';

  echo '<h2>Estimación de créditos (WPML + motor automático)</h2>';
  echo '<p style="font-size:1.15em"><strong>'.number_format_i18n($creditos_estimados).'</strong> créditos aproximados ';
  echo '(Idiomas destino: '.$langs_destino.' × Factor: '.$factor_credito.').</p>';
  echo '<p><em>Nota:</em> WPML + ATE tiene memoria de traducción: cadenas repetidas no vuelven a cobrarse.</p>';

  // Tabla de posts (top 50 por peso)
  usort($post_rows, fn($a,$b)=> $b['words'] <=> $a['words']);
  $top = array_slice($post_rows, 0, 50);

  echo '<hr><h2>Páginas/Entradas/CPT (Top 50 por palabras)</h2>';
  echo '<table class="widefat striped"><thead><tr><th>ID</th><th>Tipo</th><th>Título</th><th>Palabras</th><th>URL</th></tr></thead><tbody>';
  foreach ($top as $r){
    echo '<tr>';
    echo '<td>'.intval($r['id']).'</td>';
    echo '<td>'.esc_html($r['type']).'</td>';
    echo '<td>'.esc_html($r['title']).'</td>';
    echo '<td>'.number_format_i18n($r['words']).'</td>';
    echo '<td><a href="'.esc_url($r['url']).'" target="_blank" rel="noopener">ver</a></td>';
    echo '</tr>';
  }
  echo '</tbody></table>';

  // Totales por otros orígenes
  $render_simple = function($title, $rows, $cols=['scope','ref','words']){
    if (!$rows) return;
    echo '<h3>'.$title.'</h3><table class="widefat striped"><thead><tr>';
    foreach ($cols as $c) echo '<th>'.esc_html(ucfirst($c)).'</th>';
    echo '</tr></thead><tbody>';
    foreach ($rows as $r){
      echo '<tr>';
      foreach ($cols as $c){
        $val = $r[$c] ?? '';
        if ($c==='words') $val = number_format_i18n(intval($val));
        echo '<td>'.esc_html($val).'</td>';
      }
      echo '</tr>';
    }
    echo '</tbody></table>';
  };

  $render_simple('Menús (items)', $menu_rows);
  $render_simple('Widgets de texto', $widget_rows);
  $render_simple('Términos (taxonomías)', $term_rows);
  $render_simple('Opciones (ACF Options)', $opt_rows, ['scope','ref','words']);

  echo '<p style="margin-top:12px"><small>Parámetros: añade <code>&langs=3&factor=2&meta=1</code> a la URL para ajustar idiomas, factor de créditos y si incluir postmeta heurístico.</small></p>';
  echo '</div>';
}
