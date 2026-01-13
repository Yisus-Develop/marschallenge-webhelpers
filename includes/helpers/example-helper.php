<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

/** Sanea un string y aplica un valor por defecto si está vacío */
function webh_safe_text( $value, $default = '' ) {
    $value = is_string( $value ) ? $value : '';
    $value = wp_strip_all_tags( $value );
    return $value !== '' ? $value : $default;
}

/** ID único seguro para atributos HTML */
function webh_unique_id( $prefix = 'webh-' ) {
    return $prefix . wp_generate_uuid4();
}
