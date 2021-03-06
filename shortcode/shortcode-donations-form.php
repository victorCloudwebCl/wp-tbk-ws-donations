<?php
/**
 * tbk_donate shortcode
 *
 * Write [tbk_donate] in your post editor to render this shortcode.
 *
 * @package	 ABS
 * @since    1.0.0
 */

if ( ! function_exists( 'tbk_donations_form' ) ) {
    // Add the action.
    add_action( 'plugins_loaded', function() {
        // Add the shortcode.
        add_shortcode( 'tbk_donate', 'tbk_donations_form' );
    });


			
	/**
     * tbk_donations shortcode function.
     *
     * @return mixed
     * @since  1.0.0
     */
    function tbk_donations_form() {
		$email = isset($_GET["email"])? filter_var($_GET["email"], FILTER_SANITIZE_EMAIL): 'donanteanonimo@cnjoven.cl';
		$amount = isset($_GET["amount"])? filter_var($_GET["amount"], FILTER_SANITIZE_NUMBER_INT): 5000;

	
        // Just return the code.
		return '
		<style>
		input {border:orange solid thin!important;}
		</style>
		<form action="'.$baseurl.'/procesar-donacion/" method="GET">
		<p>Tu email:</p>
		<input type="email" name="email"  value="'.$email.'"><br>
		<p>Monto de tu donación: </p>
		<input type="number" name="amount" min="500" value="'.$amount.'"><br>
		<input type="submit">
		</form>
		';
    }
}