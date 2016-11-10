<?php
/**
 * Plugin Name:       Transbank WebServices donations shortcode form
 * Plugin URI:        http://www.cloudweb.cl
 * Description:       [tbk_donate]
 * Version:           0.1.2
 * Author:            Víctor Mellado
 * Author URI:        http://ahmadawais.com/
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       ABS
 *
 * @link              http://www.cloudweb.cl
 * @package           ABS
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Define global constants.
 *
 * @since 1.0.0
 */
// Plugin version.
if ( ! defined( 'ABS_VERSION' ) ) {
    define( 'ABS_VERSION', '2.0.0' );
}

if ( ! defined( 'ABS_NAME' ) ) {
    define( 'ABS_NAME', trim( dirname( plugin_basename( __FILE__ ) ), '/' ) );
}

if ( ! defined('ABS_DIR' ) ) {
    define( 'ABS_DIR', WP_PLUGIN_DIR . '/' . ABS_NAME );
}

if ( ! defined('ABS_URL' ) ) {
    define( 'ABS_URL', WP_PLUGIN_URL . '/' . ABS_NAME );
}

/**
 * Link.
 *
 * @since 1.0.0
 */
if ( file_exists( ABS_DIR . '/shortcode/shortcode-link.php' ) ) {
    require_once( ABS_DIR . '/shortcode/shortcode-link.php' );
}


/**
 * Donations form.
 *
 * @since 0.0.0
 */
if ( file_exists( ABS_DIR . '/shortcode/shortcode-donations-form.php' ) ) {
    require_once( ABS_DIR . '/shortcode/shortcode-donations-form.php' );
}


/**
 * Donations form-process
 *
 * @since 0.0.0
 */
if ( file_exists( ABS_DIR . '/shortcode/shortcode-donations-process.php' ) ) {
    require_once( ABS_DIR . '/shortcode/shortcode-donations-process.php' );
}
