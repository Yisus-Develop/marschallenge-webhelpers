<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/**
 * Shortcode: [webh_acf_accordion field="pagina-participantes" post_id=""]
 * Requiere ACF PRO (have_rows/get_sub_field).
 */
add_shortcode( 'webh_acf_accordion', function( $atts ) {
    $atts = shortcode_atts( [
        'field'   => 'pagina-participantes',
        'post_id' => null,
    ], $atts, 'webh_acf_accordion' );

    if ( ! function_exists( 'have_rows' ) ) {
        return '<!-- webHelpers: ACF PRO es requerido para este shortcode. -->';
    }

    $post_id = $atts['post_id'] ? intval( $atts['post_id'] ) : get_the_ID();
    $field   = sanitize_key( $atts['field'] );
    if ( empty( $field ) || ! have_rows( $field, $post_id ) ) {
        return '<!-- webHelpers: Repeater vacío o inexistente. -->';
    }

    if ( function_exists( 'webh_enqueue_accordion_assets' ) ) {
        webh_enqueue_accordion_assets();
    }

    ob_start(); ?>
    <div class="webh-accordion" data-webh-acc>
        <?php $i = 0; while ( have_rows( $field, $post_id ) ) : the_row();
            $title = get_sub_field( 'titulo' );
            $desc  = get_sub_field( 'descripcion' );
            $btn   = get_sub_field( 'boton' );
            $link  = get_sub_field( 'link_boton' );

            $safe_title = esc_html( (string) $title );
            $safe_desc  = wp_kses_post( nl2br( (string) $desc ) );
            $safe_btn   = esc_html( (string) $btn );
            
            $safe_link  = esc_url( (string) $link );
            // Si WPML está activo y el link es interno, intentamos traducirlo
            if ( function_exists( 'wpml_object_id' ) && defined('ICL_LANGUAGE_CODE') && ! empty( $safe_link ) ) {
                $home_url = home_url();
                if ( strpos( $safe_link, $home_url ) !== false ) {
                    $safe_link = apply_filters( 'wpml_permalink', $safe_link, ICL_LANGUAGE_CODE );
                }
            }

            $content_id = 'webh-acc-panel-' . esc_attr( $i ) . '-' . esc_attr( $post_id );
        ?>
        <div class="webh-acc-item">
            <button class="webh-acc-button" type="button" aria-expanded="false" aria-controls="<?php echo $content_id; ?>">
                <span class="webh-acc-title"><?php echo $safe_title; ?></span>
                <span class="webh-acc-icon" aria-hidden="true">
    <img src="<?php echo WEBHELPERS_URL . 'assets/images/icon-arrow.svg'; ?>" alt="" />
</span>
            </button>
            <div class="webh-acc-panel" id="<?php echo $content_id; ?>" hidden>
                <?php if ( $safe_desc ) : ?>
                    <div class="webh-acc-content"><?php echo $safe_desc; ?></div>
                <?php endif; ?>
                <?php if ( $safe_btn && $safe_link ) : ?>
                    <p class="webh-acc-actions">
                        <a class="webh-acc-btn" href="<?php echo $safe_link; ?>"><?php echo $safe_btn; ?></a>
                    </p>
                <?php endif; ?>
            </div>
        </div>
        <?php $i++; endwhile; ?>
    </div>
    <?php
    return ob_get_clean();
});
