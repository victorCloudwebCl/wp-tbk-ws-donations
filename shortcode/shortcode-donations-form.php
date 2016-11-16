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
		$email = isset($_GET["email"])? filter_var($_GET["email"], FILTER_SANITIZE_EMAIL): '';
		$amount = isset($_GET["amount"])? filter_var($_GET["amount"], FILTER_SANITIZE_NUMBER_INT): '';

		$estilo = ABS_DIR."/css/style.css";

	
        // Just return the code.
        
        // **Formulario inicial (para enviar email y monto)
		return '
		<link rel="stylesheet" type="text/css" media="screen" href="'.ABS_URL.'/css/style.css">
		<div class="wp-tbk-donations">
			<form action="'.$baseurl.'/procesar-donacion/" method="GET">
			<p>Tu email (opcional):</p>
			<input type="email" name="email" placeholder="Ej nombre@cnjoven.cl"><br>
			<p>Monto de tu donación (requerido): </p>
			<input type="number" name="amount" min="500" placeholder="Ej $20.000" required><br>
			<input type="submit">
		</form></div>';
    }
}