<?php
if ( ! defined( 'ABSPATH' ) ) { exit; }

add_action( 'init', function() {
    wp_register_style( 'webh-acf-accordion', WEBHELPERS_URL . 'assets/css/webh-acf-accordion.css', [], WEBHELPERS_VERSION );
    wp_register_script( 'webh-acf-accordion', WEBHELPERS_URL . 'assets/js/webh-acf-accordion.js', [], WEBHELPERS_VERSION, true );
});

function webh_enqueue_accordion_assets() {
    wp_enqueue_style( 'webh-acf-accordion' );
    wp_enqueue_script( 'webh-acf-accordion' );
}
