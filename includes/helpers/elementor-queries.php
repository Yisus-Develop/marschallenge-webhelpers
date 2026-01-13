<?php
if (!defined('ABSPATH')) exit;

/**
 * === Elementor Query por ACF Relationship (genérico) =======================
 * Usa un "registro" de Query IDs → { post_type, acf_field } para armar post__in
 * desde el post actual (por ejemplo single de landing_paises).
 *
 * Cómo usar en Elementor:
 *  - Source: el CPT de destino (coincide con 'post_type')
 *  - Query ID: uno de los registrados abajo (ej: empresas_pais, instituciones_pais)
 *  - Order By en Elementor: "Default" (para no chocar con 'post__in')
 *
 * Requisitos:
 *  - El campo ACF (relationship) devuelve IDs (return_format = id).
 *  - La consulta se ejecuta sobre el "post actual" (ej: la landing del país).
 */

/* -----------------------------------------------------------
 * 1) Registro de queries (puedes ampliarlo sin tocar el core)
 *    - 'query_id' => ['post_type' => '...', 'acf_field' => '...']
 *    - acf_field puede ser simple o "path" con puntos (group.subcampo)
 * ----------------------------------------------------------- */
function mc_rel_queries_registry() {
    $registry = [
        // (lo que ya tienes)
        'empresas_pais' => [
            'post_type' => 'empresas_aliadas',
            // mejor directo al subcampo si es un Group:
            'acf_field' => 'lp_empresas.empresas', // <-- si tu relación está dentro del group
            'post_type_context' => 'landing_paises',
            'strict_empty' => true,
        ],

        'instituciones_pais' => [
            'post_type' => 'instituciones',
            'acf_field' => 'lp_instituciones',     // o 'lp_edu.instituciones' si está dentro de group
            'post_type_context' => 'landing_paises',
            'strict_empty' => true,
        ],

        // === HÍBRIDO: si hay Relationship → úsalo; si no → taxonomía country compartida
        'empresas_pais_hibrido' => [
            'post_type'         => 'empresas_aliadas',
            'acf_field'         => 'lp_empresas.empresas', // o 'lp_empresas' si devuelves IDs directamente
            'tax_fallback'      => 'country',
            'post_type_context' => 'landing_paises',
            'posts_per_page'    => -1,
            'orderby_fallback'  => 'title',   // o 'menu_order title' si activas page-attributes
            'order_fallback'    => 'ASC',
            'strict_empty'      => true,
        ],

        // (ejemplo extra por si lo necesitas para logos también)
        'logos_pais_hibrido' => [
             'post_type'         => 'logos',
             'acf_field'         => 'lp_logos',          // opcional
             'tax_fallback'      => 'country',
            'post_type_context' => 'landing_paises',
            'orderby_fallback'  => 'title',
            'order_fallback'    => 'ASC',
            'strict_empty'      => true,
        ],
    ];

    return apply_filters('mc_relation_queries_registry', $registry);
}


/* -----------------------------------------------------------
 * 2) Helper: obtener un ACF por "path" (group.subcampo)
 *    Devuelve el valor (idealmente array de IDs para Relationship)
 * ----------------------------------------------------------- */
function mc_get_acf_by_path($path, $post_id) {
    if (!function_exists('get_field')) return null;
    if (!$path) return null;

    $parts = explode('.', $path);
    // Primer nivel: pedirlo a ACF
    $data = get_field(array_shift($parts), $post_id);

    // Descender por keys si es un array (group, subcampos)
    foreach ($parts as $p) {
        if (is_array($data) && array_key_exists($p, $data)) {
            $data = $data[$p];
        } else {
            return null;
        }
    }
    return $data;
}

/* -----------------------------------------------------------
 * 3) Motor: aplica 'post__in' (ACF) y si está vacío → fallback taxonomía
 *     Conf soportada:
 *     - post_type            (req)
 *     - acf_field            (opc) path con puntos (group.subcampo)
 *     - tax_fallback         (opc) 'country'  o  ['taxonomy'=>'country']
 *     - post_type_context    (opc) ej. 'landing_paises'
 *     - posts_per_page       (opc) default -1
 *     - orderby_fallback     (opc) ej. 'title' o 'menu_order title'
 *     - order_fallback       (opc) 'ASC'|'DESC' (default 'ASC')
 *     - strict_empty         (opc) true → si no hay ACF ni tax → 0 resultados
 * ----------------------------------------------------------- */
function mc_apply_relation_query($query, $conf) {
    // Limitar por contexto si aplica
    if (!empty($conf['post_type_context']) && !is_singular($conf['post_type_context'])) return;

    $current_id = get_the_ID();
    if (!$current_id) return;

    $posts_per_page = isset($conf['posts_per_page']) ? intval($conf['posts_per_page']) : -1;

    /* 1) Intentar por ACF Relationship (si está definido) */
    $ids = [];
    if (!empty($conf['acf_field'])) {
        $raw = mc_get_acf_by_path($conf['acf_field'], $current_id);
        $ids = array_values(array_filter(array_map('intval', (array) $raw)));
    }

    if (!empty($ids)) {
        // Modo MANUAL (respeta el orden del Relationship)
        $query->set('post_type', $conf['post_type']);
        $query->set('post__in', $ids);
        $query->set('orderby', 'post__in');
        $query->set('ignore_sticky_posts', true);
        $query->set('posts_per_page', $posts_per_page);
        return;
    }

    /* 2) Fallback por TAXONOMÍA (si se pidió) */
    if (!empty($conf['tax_fallback'])) {
        $tax = is_array($conf['tax_fallback']) ? ($conf['tax_fallback']['taxonomy'] ?? '') : $conf['tax_fallback'];
        if ($tax) {
            $term_ids = wp_get_object_terms($current_id, $tax, ['fields' => 'ids']);
            if (!empty($term_ids) && !is_wp_error($term_ids)) {
                $query->set('post_type', $conf['post_type']);
                $query->set('posts_per_page', $posts_per_page);
                $query->set('tax_query', [[
                    'taxonomy' => $tax,
                    'field'    => 'term_id',
                    'terms'    => $term_ids,
                ]]);
                // Orden para el fallback
                $query->set('orderby', $conf['orderby_fallback'] ?? 'title');
                $query->set('order',   $conf['order_fallback']   ?? 'ASC');
                return;
            }
        }
    }

    /* 3) Sin ACF ni tax: decidir qué hacer */
    if (!empty($conf['strict_empty'])) {
        $query->set('post_type', $conf['post_type']);
        $query->set('post__in', [0]);         // fuerza 0 resultados
        $query->set('posts_per_page', 0);
    }
    // Si no es strict, dejamos que Elementor haga su query por defecto.
}


/* -----------------------------------------------------------
 * 4) Registrar dinámicamente todos los Query IDs
 * ----------------------------------------------------------- */
add_action('init', function () {
    $registry = mc_rel_queries_registry();
    foreach ($registry as $qid => $conf) {
        add_action("elementor/query/{$qid}", function ($query) use ($conf) {
            // Si no hay ACF, salir
            if (!function_exists('get_field')) return;

            // Si definiste un contexto, verifica (ej. single de landing_paises)
            if (!empty($conf['post_type_context'])) {
                if (!is_singular($conf['post_type_context'])) return;
            }

            mc_apply_relation_query($query, $conf);
        });
    }
}, 20);
