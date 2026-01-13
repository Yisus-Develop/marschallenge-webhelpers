<?php
/*
Plugin Name: webHelpers
Description: Colección modular de shortcodes y utilidades para WordPress (2025-ready). Incluye acordeón ACF listo para usar.
Version: 1.0.6
Author: Enlaweb
Text Domain: webhelpers
Requires at least: 6.0
Requires PHP: 7.4
*/

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'WEBHELPERS_VERSION', '1.0.6' );
define( 'WEBHELPERS_PATH', plugin_dir_path( __FILE__ ) );
define( 'WEBHELPERS_URL',  plugin_dir_url( __FILE__ ) );

add_action( 'plugins_loaded', function() {
    load_plugin_textdomain( 'webhelpers', false, dirname( plugin_basename( __FILE__ ) ) . '/languages' );
});

require_once WEBHELPERS_PATH . 'includes/routes.php';

// Inicializar el sistema de actualizaciones desde GitHub
if ( is_admin() ) {
    require_once WEBHELPERS_PATH . 'includes/class-eweb-github-updater.php';
    new EWEB_GitHub_Updater( __FILE__, 'Yisus-Develop', 'marschallenge-webhelpers' );
}





