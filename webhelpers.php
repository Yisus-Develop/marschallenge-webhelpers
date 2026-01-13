<?php
/**
 * Plugin Name: EWEB - Mars Challenge Webhelpers
 * Description: Colección modular de shortcodes y utilidades para Mars Challenge (2025-ready). Incluye soporte para WPML, ACF y actualizaciones automáticas.
 * Version: 1.0.8
 * Author: Yisus Develop
 * Author URI: https://github.com/Yisus-Develop
 * License: GPL v2 or later
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Text Domain: webhelpers
 * Domain Path: /languages
 */

if ( ! defined( 'ABSPATH' ) ) { exit; }

define( 'WEBHELPERS_VERSION', '1.0.8' );
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





