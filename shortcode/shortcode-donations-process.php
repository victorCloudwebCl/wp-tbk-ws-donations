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
			/**
			 * @author     Allware Ltda. (http://www.allware.cl)
			 * @copyright  2015 Transbank S.A. (http://www.tranbank.cl)
			 * @date       Jan 2015
			 * @license    GNU LGPL
			 * @version    1.0
			 */
									 
			if ( file_exists( ABS_DIR . '/libwebpay/webpay.php' ) ) {
				require_once( ABS_DIR . '/libwebpay/webpay.php' );
				echo '<script>console.log ("libwebpay ok")</script>';
			}	
			if ( file_exists( ABS_DIR . '/certificates/cert-normal.php' ) ) {
				require_once( ABS_DIR . '/certificates/cert-normal.php' );
				echo '<script>console.log ("cert-normal ok")</script>';
			}
			
			//**Verificar si existe el archivo de logs, si no, crear uno nuevo***********************
					$logFile = ABS_DIR . '/log/logfile.txt';
	
					if (file_exists($logFile)) {
						  $fh = fopen($logFile, 'a');
						  echo '<script>console.log ("logfile existe.")</script>';
						} else {
						  $fh = fopen($logFile, 'a');
						  echo '<script>console.log ("logfile creado.")</script>';
						  fwrite ($fh,"***Logfile creado en:'.date(DATE_RFC2822).\n");
						  fwrite ($fh,"sessionID;			buyOrder;			amount;		email;			InicioSesion;		Resultado;\n");
						}
						fclose($fh);			

			

			/* Configuracion parametros de la clase Webpay */
			//$baseurl = $_SERVER['REQUEST_SCHEME']."://".$_SERVER['HTTP_HOST'].$_SERVER['PHP_SELF'];
			$baseurl = site_url();
			
			$webpay_settings = array(
				"MODO" => "INTEGRACION",
				"PRIVATE_KEY" => $certificate['private_key'],
				"PUBLIC_CERT" => $certificate['public_cert'],
				"WEBPAY_CERT" => $certificate['webpay_cert'],
				"COMMERCE_CODE" => $certificate['commerce_code'],
				"URL_RETURN" => $baseurl."/procesar-donacion?action=result",
				"URL_FINAL" => $baseurl."/procesar-donacion?action=end",
			);
			
			/* Creacion Objeto Webpay */
			$webpay = new WebPaySOAP($webpay_settings); // Crea objeto WebPay
			$webpay = $webpay->getNormalTransaction(); // Crea Transaccion Normal

			$action = isset($_GET["action"])? $_GET["action"]: 'init';
			
			
			$email = isset($_GET["email"])? filter_var($_GET["email"], FILTER_SANITIZE_EMAIL): 'anonimo@cnjoven.cl';
			$amount = isset($_GET["amount"])? filter_var($_GET["amount"], FILTER_SANITIZE_NUMBER_INT): 5000;

			$bot_tx = "Continuar";
			

	
			/* Según acción, paso */
			switch ($action) {

				/*PASO DEFAULT: Confirmar datos de donación*/
				default:
					
					$tx_step = "Confirma los datos";
					$bot_tx = "Ir al servidor seguro de Transbank";
					
				
					//Generar la request que se enviará a Transbank.
					$request = array(
							"amount"    => $amount,      // monto a cobrar
							"buyOrder"  => round(microtime(true)*1000),    // numero orden de compra
							"sessionId" => uniqid(), // idsession local
						);
						
					//Generar orden de compra duplicada para certificación.
					//$request["buyOrder"] = 99999; 
						
					

					// Iniciamos Transaccion
					$result = $webpay->initTransaction($request["amount"], $request["sessionId"], $request["buyOrder"]);
					$webpay_token = $result["token_ws"];
					

					// Verificamos respuesta de inicio en webpay
					if (strlen($webpay_token)) {
						
       					
       					//Chequeo de duplicidad de OC **************
						echo '<script>console.log("'.$request["buyOrder"].'")</script>';
					    

       					// RESPUESTA DE INICIO DE SESIÓN OK.
       					$message = '<p> Monto de la donación: <b>&#36;'.number_format($amount,0,",",".").'</b><p>
									<p> Orden de compra: <b>'.$request["buyOrder"].'</b>
									<p> Email: <b>'.$email.'</b><p>
									<p> Presiona el botón para realizar el pago, o ve hacia atrás con tu navegador para modificarlos.</p>';
						$opStatus = 'TBK - Inicio de sesión OK.';
						$resultadoLoginInfo = $request['sessionId'].";		".$request['buyOrder'].";		".$request['amount'].";		".$email.";		".$opStatus.";		".$result['token_ws'].";			 ".$result['url'].";			";
						
						$next_page = $result["url"];
						
						
					   	//Comprobar que la OC no esté duplicada
					   	//Si lo está, no redirigir a Transbank y mostrar pantalla de error.
						    
						    if( exec('grep '.escapeshellarg($request["buyOrder"]).' '.$logFile)) {
						       echo '<script>console.log("'.$request["buyOrder"].' DUPLICADO!")</script>';
						       $tx_step = "No se puede realizar.";
						       $message = "No podemos realizar esta operación, debido a que la orden de compra se encuentra duplicada. Presiona el botón para generar una nueva orden de compra.";
						       $bot_tx = "Reintentar";
						       $next_page = '';
						       //unset ($webpay_token); //Invalida el token de Transbank que esté vigente.
							   $next_page_title = "Reintentar";
						       
						       
								// Registrar el evento en el log.
									if (file_exists($logFile)) {
										  $fh = fopen($logFile, 'a');
										  echo '<script>console.log ("TBK - Exito.")</script>';
										  fwrite ($fh,"Sesión no iniciada (OC Duplicada)\n");
										  fclose($fh);
										}
						    }			

				
						
					
					} else {
							// TBK no respondió con un token, o no respondió nada.
							
							$tx_step = "Ha ocurrido un error";
							$message = "
							<p>WebPay no se encuentra disponible. </p>
							<p>Por favor inténtalo nuevamente o ponte en contacto con nosotros a través del formulario de contacto o los números de teléfono indicados.</p>
							";
							var_dump ($response);
							
					}
					
								// Escribir en el log el resultado de inicio sesión.
								if (file_exists($logFile)) {
										  $fh = fopen($logFile, 'a');
										  echo '<script>console.log ("Datos OK.")</script>';
										  fwrite ($fh,$resultadoLoginInfo);
										  fclose($fh);	
								}
						
					break;

				
				// action = result
				case "result":
					
							
					
					
					$tx_step = "Resultado de tu aporte.";
					if (!isset($_POST["token_ws"])) break;
					
					$webpay_token = $_POST["token_ws"];
					$request = array( 
						"token"  => $_POST["token_ws"]
					);

					// Rescatamos resultado y datos de la transaccion
					$result = $webpay->getTransactionResult($request["token"]);  


					// Verificamos resultado del pago
					if ($result->detailOutput->responseCode===0) {
								$bot_tx = "Ver voucher de Transbank";
								$message = '<p>Pago <b>aceptado y recibido</b> por Webpay. </p> 
											<p> Si deseas ver el voucher, haz clic en el botón.</p>
											<p> Número de orden: '.$result->buyOrder;
								$next_page = $result->urlRedirection;
								$next_page_title = "Finalizar Pago";
								
								$emailtxt = '
											<h1>¡Muchas gracias por tu aporte!</h1>
											<p>Monto de la donación:<b>'.number_format($amount,0,",",".").'</b></p>
											<p>Email:<b>'.$email.'</b></p>
											<p>Número de orden:<b>'.$result->buyOrder.'</b>
											<p>Fundación Cerro Navia Joven
											';
											
								$email_recipients = array ('',$email);
													
								$headers[] = 'From: Cerro Navia Joven <noresponder@cnjoven.cl>';
								$headers[] = 'Return-Path: <noresponder@cnjoven.cl>';
								$headers[] = 'Content-Type: text/html; charset=UTF-8';
								$headers[] = 'Bcc: victor@cloudweb.cl';
								
								wp_mail( $email_recipients, 'Aporte recibido vía WebPayPlus', $emailtxt, $headers);
						
						// Escribir el log con la información de sesión creada por Tbk.
							if (file_exists($logFile)) {
								  $fh = fopen($logFile, 'a');
								  echo '<script>console.log ("TBK - Exito.")</script>';
								  fwrite ($fh,"exito\n");
								  fclose($fh);
							}
							
					} else {
						$bot_tx = "Volver";
						$message = "Pago <b>rechazado</b> por WebPayPlus - ".utf8_decode($result->detailOutput->responseDescription);
									$next_page='';
						// Escribir el log con la información de sesión creada por Tbk.
							if (file_exists($logFile)) {
								  $fh = fopen($logFile, 'a');
								  echo '<script>console.log ("Rechazado.")</script>';
								  fwrite ($fh,$request['sessionId'].";		".$request['buyOrder'].";		".$request['amount'].";		".$email."		Rechazado por Tbk (".utf8_decode($result->detailOutput->responseDescription).")\n");
								fclose($fh);
							}
							
					}

					break;
					
			case "end":
						$tx_step = "Muchas gracias";
						$bot_tx = "Volver";
						$request= '';
						$result = $_POST;
						$message = "Transacion Finalizada";
						$next_page=$baseurl;
						break;
		
			
}/*fin switch*/

	
		return '
		<h2>'.$tx_step.'</h2>
		<p>'.$message.'</p>
		<form action="'.$next_page.'" method="post">
		<input type="hidden" name="token_ws" value="'.$webpay_token.'">
		<input type="submit" value="'.$bot_tx.'">
		</form>
		<a href="'.$baseurl.'/donacion-monetaria?amount='.$amount.'&email='.$email.'">Hacer una donación diferente</a>
		';
	
		}				
			
    }