<?php
if (!defined('ABSPATH')) exit;

// Assets para tarjetas+CF7
add_action('wp_enqueue_scripts', function () {
  wp_register_style ('mc-registro',      WEBHELPERS_URL . 'assets/css/mc-registro.css', [], WEBHELPERS_VERSION);
  wp_register_script('mc-registro',      WEBHELPERS_URL . 'assets/js/mc-registro.js',  [], WEBHELPERS_VERSION, true);
  wp_register_script('mc-registro-cf7',  WEBHELPERS_URL . 'assets/js/mc-registro-cf7.js', ['jquery'], WEBHELPERS_VERSION, true);
  wp_localize_script('mc-registro-cf7','MCREG',[ 'ajax' => admin_url('admin-ajax.php') ]);

  wp_enqueue_script('jquery'); // <-- fuerza jQuery

  wp_localize_script('mc-registro-cf7','MCGEO', [
    'cities' => esc_url_raw( rest_url('mcgeo/v1/cities') ),
  ]);

// 4) cadenas traducibles
wp_localize_script('mc-registro-cf7', 'MCI18N', [
  'hint_select_country' => __('Selecciona un país para buscar o escribe tu ciudad.', 'webhelpers'),
  'hint_type_2'         => __('Escribe 2+ letras para buscar (si no aparece, escríbela tal cual).', 'webhelpers'),
  'hint_type_exact'     => __('Escribe tu ciudad tal cual aparece en tu documento.', 'webhelpers'),
  'hint_no_results'     => __('No hay sugerencias para esa búsqueda. Puedes escribir tu ciudad tal cual.', 'webhelpers'),
  'hint_no_service'     => __('No hay sugerencias ahora. Escribe tu ciudad tal cual.', 'webhelpers'),
  'city_placeholder'    => __('Escribe 2+ letras para buscar…', 'webhelpers'),
  'country_other'       => __('Otro…', 'webhelpers'),
]);


wp_enqueue_script('mc-registro-cf7');

});

add_shortcode('mc_registro', function($atts){
  wp_enqueue_style('mc-registro');
  wp_enqueue_script('mc-registro');
  wp_enqueue_script('mc-registro-cf7');

  // 👉 Mapea PERFIL → ID de CF7 (pon aquí los tuyos)
  // Usamos wpml_object_id para que el ID cambie según el idioma
  $cf7_ids = [
    'zer'         => 2759, 
    'mentor'      => 3069,    
    'institucion' => 3070,
    'empresa'     => 3071,
    'ciudad'      => 3072,
    'pioneer'     => 3073,
    'pais'        => 3074,
  ];

  $cf7_map = [];
  foreach ($cf7_ids as $slug => $id) {
      if (function_exists('wpml_object_id')) {
          $cf7_map[$slug] = apply_filters('wpml_object_id', $id, 'wpcf7_contact_form', true);
      } else {
          $cf7_map[$slug] = $id;
      }
  }

  $cf7_map = apply_filters('mc_registro_cf7_map', $cf7_map);

  // Pasa el mapa a JS (deshabilita tarjetas que no tengan ID)
  wp_add_inline_script('mc-registro', 'window.__MC_FORM_MAP = '.wp_json_encode($cf7_map).';', 'before');

  // Tarjetas - Internacionalizadas con WPML (usando nombres únicos)
  $cards = [
    [
      'slug'  => 'zer',
      'title' => icl_t('webhelpers', 'card_zer_title', 'JÓVENES (ZERS)'),
      'desc'  => icl_t('webhelpers', 'card_zer_desc', 'Tengo 15 a 29 años y quiero participar en un hackathon.'),
      'cta'   => icl_t('webhelpers', 'card_zer_cta', 'Unirme como Zer'),
      'emoji' => '🧑‍🚀',
      'img_id' => 2937 
    ],
    [
      'slug'  => 'mentor',
      'title' => icl_t('webhelpers', 'card_mentor_title', 'MAESTRO O MENTOR'),
      'desc'  => icl_t('webhelpers', 'card_mentor_desc', 'Quiero guiar e inspirar a los Zers.'),
      'cta'   => icl_t('webhelpers', 'card_mentor_cta', 'Ser maestro/mentor'),
      'emoji' => '👩‍🏫',
      'img_id' => 2934 
    ],
    
    [
      'slug'  => 'institucion',
      'title' => icl_t('webhelpers', 'card_inst_title', 'INSTITUCIÓN EDUCATIVA'),
      'desc'  => icl_t('webhelpers', 'card_inst_desc', 'Represento a una escuela, universidad o centro técnico.'),
      'cta'   => icl_t('webhelpers', 'card_inst_cta', 'Registrar institución'),
      'emoji' => '🏫',
      'img_id' => 2940 
    ],
    
    [
      'slug'  => 'empresa',
      'title' => icl_t('webhelpers', 'card_empresa_title', 'EMPRESA'),
      'desc'  => icl_t('webhelpers', 'card_empresa_desc', 'Quiero colaborar con recursos, mentoría o desafíos.'),
      'cta'   => icl_t('webhelpers', 'card_empresa_cta', 'Conectar empresa'),
      'emoji' => '💼',
      'img_id' => 2939 
    ],
    
    [
      'slug'  => 'ciudad',
      'title' => icl_t('webhelpers', 'card_ciudad_title', 'CIUDAD / GOBIERNO LOCAL'),
      'desc'  => icl_t('webhelpers', 'card_ciudad_desc', 'Mi ciudad quiere ser una Mars Challenge City.'),
      'cta'   => icl_t('webhelpers', 'card_ciudad_cta', 'Postular ciudad'),
      'emoji' => '🏙',
      'img_id' => 2938 
    ],
    
    [
      'slug'  => 'pioneer',
      'title' => icl_t('webhelpers', 'card_pioneer_title', 'MISSION PARTNER / PIONEER'),
      'desc'  => icl_t('webhelpers', 'card_pioneer_desc', 'Quiero liderar Mars Challenge en mi país.'),
      'cta'   => icl_t('webhelpers', 'card_pioneer_cta', 'Ser pionero'),
      'emoji' => '🚩',
      'img_id' => 2936
    ],
    
    [
      'slug'  => 'pais',
      'title' => icl_t('webhelpers', 'card_pais_title', 'PAIS'),
      'desc'  => icl_t('webhelpers', 'card_pais_desc', 'Mi país quiere ser una parte de Mars Challenge.'),
      'cta'   => icl_t('webhelpers', 'card_pais_cta', 'Postular país'),
      'emoji' => '🌍',
      'img_id' => 2935
    ],
  ];

  ob_start(); ?>


<section  class="mc-form-area" aria-live="polite" aria-busy="false">
    <div class="mc-form-placeholder"><?php echo icl_t('webhelpers', 'placeholder', 'Selecciona un perfil para continuar.'); ?></div>
  </section>


<section class="mc-cards" aria-label="<?php echo esc_attr(icl_t('webhelpers', 'aria_label', 'Elige tu perfil')); ?>">
  <?php foreach ($cards as $card): ?>
    <?php $imgUrl = !empty($card['img_id']) ? wp_get_attachment_image_url((int)$card['img_id'], 'medium') : ''; ?>
    <article class="mc-card" data-profile="<?php echo esc_attr($card['slug']); ?>" role="button" tabindex="0" aria-controls="mc-form-area">
      <header><h3><?php echo esc_html($card['title']); ?></h3></header>
      <div class="mc-card-body">
        <div class="mc-visual">
          <?php if ($imgUrl): ?>
            <img class="mc-card-img" src="<?php echo esc_url($imgUrl); ?>" alt="" loading="lazy">
          <?php else: ?>
            <div class="mc-emoji" aria-hidden="true"><?php echo esc_html($card['emoji']); ?></div>
          <?php endif; ?>
        </div>
        <p><?php echo esc_html($card['desc']); ?></p>
        <button class="mc-cta" type="button"><?php echo esc_html($card['cta']); ?></button>
      </div>
    </article>
  <?php endforeach; ?>
</section>



  <section id="mc-form-area" class="mc-form-area" aria-live="polite" aria-busy="false">
   
  </section>

  <!-- Embeds ocultos (CF7 ya renderizado en servidor) -->
  <div class="mc-embeds" style="display:none">
    <?php foreach ($cf7_map as $slug => $id): if ($id): ?>
      <div id="mc-embed-<?php echo esc_attr($slug); ?>">
        <?php echo do_shortcode('[contact-form-7 id="'.intval($id).'" html_id="cf7-'.$slug.'"]'); ?>
      </div>
    <?php endif; endforeach; ?>
  </div>
  <?php
  return ob_get_clean();

});


// Imprime <option> de la taxonomía 'country' (valor = ISO-2 si existe, si no, el slug)
add_shortcode('mc_country_options', function(){
  $terms = get_terms([
    'taxonomy'   => 'country',
    'hide_empty' => false,
    'orderby'    => 'name',
    'order'      => 'ASC'
  ]);
  if (is_wp_error($terms) || empty($terms)) return '';
  $out = '';
  foreach ($terms as $t) {
    $iso2  = get_term_meta($t->term_id, 'iso2', true); // opcional
    $value = $iso2 ? strtoupper($iso2) : $t->slug;
    $out  .= '<option value="'.esc_attr($value).'" data-slug="'.esc_attr($t->slug).'">'.esc_html($t->name).'</option>';
  }
  return $out;
});

// (Alias opcional si en CF7 escribiste [mc_pais_options])
add_shortcode('mc_pais_options', function(){ return do_shortcode('[mc_country_options]'); });

// Asegurar que CF7 procese shortcodes dentro del formulario
add_filter('wpcf7_form_elements', function($form_html){
  return do_shortcode($form_html);
});

add_shortcode('mc_country_other_option', function(){
  return '<option value="__other__">'.esc_html__('Otro…', 'webhelpers').'</option>';
});
