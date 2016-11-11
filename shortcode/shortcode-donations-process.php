<?php
/**
 * tbk_donations shortcode
 *
 * Write [tbk_process_donate] in your post editor to render this shortcode.
 *
 * @package	 ABS
 * @since    1.0.0
 */

if ( ! function_exists( 'tbk_donations_process' ) ) {
		
		// Add the action.
    	add_action( 'plugins_loaded', function() {
        // Add the shortcode.
        add_shortcode( 'tbk_process_donate', 'tbk_donations_process' );
		});
    
	/**
     * tbk_donations_process shortcode function.
     *
     * @return mixed
     * @since  1.0.0
     */
     

    function tbk_donations_process() {
        include ( dirname( dirname(__FILE__) ).'/forms/tbk-normal.php');

    } //** Funcion tbk_donations_process
}

?>
