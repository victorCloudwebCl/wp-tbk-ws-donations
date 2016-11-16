<?php
/**
 * @author     Allware Ltda. (http://www.allware.cl)
 * @copyright  2015 Transbank S.A. (http://www.tranbank.cl)
 * @date       Jan 2015
 * @license    GNU LGPL
 * @version    2.0.1
 */

require_once(dirname(dirname(__FILE__)).'/libwebpay/webpay.php' );
require_once(dirname(dirname(__FILE__)).'/certificates/cert-normal.php' );

/** Configuracion parametros de la clase Webpay */
$sample_baseurl = $_SERVER['REQUEST_SCHEME'] . "://" . $_SERVER['HTTP_HOST'];

$configuration = new Configuration();
$configuration->setEnvironment($certificate['environment']);
$configuration->setCommerceCode($certificate['commerce_code']);
$configuration->setPrivateKey($certificate['private_key']);
$configuration->setPublicCert($certificate['public_cert']);
$configuration->setWebpayCert($certificate['webpay_cert']);

/** Creacion Objeto Webpay */
$webpay = new Webpay($configuration);

$action = isset($_GET["action"]) ? $_GET["action"] : 'init';

$post_array = false;

switch ($action) {

    default:
        
        $tx_step = "Confirma tu donación";

        /** Monto de la transacción */
        $amount = isset($_GET["amount"]) ? $_GET["amount"] : '10000';

        /** Orden de compra de la tienda */
        $buyOrder = rand();

        /** Código comercio de la tienda entregado por Transbank */
        $sessionId = uniqid();
        
        /** URL de retorno */
        $urlReturn = $sample_baseurl."/procesar-donacion/?action=getResult";
        
        /** URL Final */
	    $urlFinal  = $sample_baseurl."/procesar-donacion/?action=end";

        $request = array(
            "amount"    => $amount,
            "buyOrder"  => $buyOrder,
            "sessionId" => $sessionId,
            "urlReturn" => $urlReturn,
            "urlFinal"  => $urlFinal,
        );


        /** Iniciamos Transaccion */
        $result = $webpay->getNormalTransaction()->initTransaction($amount, $buyOrder, $sessionId, $urlReturn, $urlFinal);
        
        /** Verificamos respuesta de inicio en webpay */
        if (!empty($result->token) && isset($result->token)) {
            $message = "<div><p>Monto de tu donación: <b>".$amount."</b></p><p>Número de orden:<b>".$buyOrder."</div>";
            
            $token = $result->token;
            $next_page = $result->url;
        } else {
            $message = "Webpay no disponible";
        }
            
            $button_name = "Continuar &raquo;";
        
        break;

    case "getResult":
        
        
        
        if (!isset($_POST["token_ws"]))
            break;

        /** Token de la transacción */
        $token = filter_input(INPUT_POST, 'token_ws');
        
        $request = array(
            "token" => filter_input(INPUT_POST, 'token_ws')
        );

        /** Rescatamos resultado y datos de la transaccion */
        $result = $webpay->getNormalTransaction()->getTransactionResult($token);
        
        
        /** Verificamos resultado  de transacción */
        if ($result->detailOutput->responseCode === 0) {

            /** propiedad de HTML5 (web storage), que permite almacenar datos en nuestro navegador web */
            echo '<script>window.localStorage.clear();</script>';
            echo '<script>localStorage.setItem("authorizationCode", '.$result->detailOutput->authorizationCode.')</script>';
            echo '<script>localStorage.setItem("amount", '.$result->detailOutput->amount.')</script>';
            echo '<script>localStorage.setItem("buyOrder", '.$result->buyOrder.')</script>';

            $tx_step = "Pago recibido.";
            $message = "<p>Tu donación ha sido <b>aceptada</b> por Webpay.<br>
                        Código de orden <b>".$result->buyOrder."</b><br>
                        Monto de la donación <b>$ ".$result->detailOutput->amount."</b><br>
                        Código de autorización: <b> ".$result->detailOutput->authorizationCode."</b><br>
                        Fecha de la operación: <b>".$result->detailOutput->accountingDate."</b><br>
                        Tipo de pago: <b>".$result->detailOutput->paymentTypeCode."</b><br>
                        Cantidad de cuotas: <b>".$result->detailOutput->sharesNumber."</b><br>
                        4 últimos dígitos de la tarjeta bancaria: <b>".$result->cardNumber->cardDetail->cardNumber."</b><br>
                        </p>";
            $next_page = $result->urlRedirection;
            $button_name = "Generar y ver Voucher &raquo;";
            
        } else {
            
            
            $motivo = ($result->detailOutput->responseCode);
            
            switch  (true){
        
                    case stristr ($motivo,'-1'):
                        $motivo = '<p>fue rechazada por Webpay.<br>
                                Código de Orden'.$buyOrder.'
                                Posibles causas:<br>
                                Las posibles causas de este rechazo son:<br>
                                - Error en el ingreso de los datos de su tarjeta de Crédito o Débito (fecha y/o código de seguridad).<br>
                                - Su tarjeta de Crédito o Débito no cuenta con saldo suficiente.<br>
                                - Tarjeta aun no habilitada en el sistema financiero';
                        break;
                    case stristr ($motivo,'-2'):
                        $motivo = '<p>La transacción debe reintentarse.</p>';
                        break;
                    case stristr ($motivo,'-3'):
                        $motivo = 'hubo un error en la transacción';
                        break;
                    case stristr ($motivo,'-4'):
                        $motivo = 'La transacción fue rechazada por Webpay.';
                        break;
                    case stristr ($motivo,'-5'):
                        $motivo = 'Rechazo por error de tasa.';  
                        break;
                    case stristr ($motivo,'-6'):
                        $motivo = 'excede el cupo máximo mensual. Por favor reintenta con un monto menor.';
                        break;
                    case stristr ($motivo,'-7'):
                        $motivo = 'excede el límite diario por transacción.';
                        break;
                    case stristr ($motivo,'-8'):
                        $motivo = "el rubro no está autorizado.";
                        break;
                                                
                    default:
                        $motivo = "pasó demasiado tiempo (Timeout Error), generaste una petición con la misma Orden, o Webpay no se encuentra disponible en este momento.";
                        break;
                };

            $message = "El pago <b>falló</b>, debido a que <b>".$motivo."</b>";
            
            $next_page = '';
        }

        break;
    
    case "end":
        
        $post_array = true;
        $tx_step = "Gracias por tu donación";
        $request = "";
        $result = $_POST;
        $message = "<p>Transacion finalizada. Muchas gracias por tu aporte.</p>
                    <p></p>";
        $next_page = $sample_baseurl."/procesar-donacion/?action=nullify";
        $button_name = "Anular donación. &raquo;";

        break;   

    
    case "nullify":

        $tx_step = "Anulación de donación";
        
        $request = $_POST;
        
        /** Codigo de Comercio */
        $commercecode = null;

        /** Código de autorización de la transacción que se requiere anular */
        $authorizationCode = filter_input(INPUT_POST, 'authorizationCode');

        /** Monto autorizado de la transacción que se requiere anular */
        $amount =  filter_input(INPUT_POST, 'amount');

        /** Orden de compra de la transacción que se requiere anular */
        $buyOrder =  filter_input(INPUT_POST, 'buyOrder');
        
        /** Monto que se desea anular de la transacción */
        $nullifyAmount = $amount;

        $request = array(
            "authorizationCode" => $authorizationCode, // Código de autorización
            "authorizedAmount" => $amount, // Monto autorizado
            "buyOrder" => $buyOrder, // Orden de compra
            "nullifyAmount" => $nullifyAmount, // idsession local
            "commercecode" => $configuration->getCommerceCode(), // idsession local
        );
        
        $result = $webpay->getNullifyTransaction()->nullify($authorizationCode, $amount, $buyOrder, $nullifyAmount, $commercecode);
        
        /** Verificamos resultado  de transacción */
        if (!isset($result->authorizationCode)) {
            $message = "<p>Webpay no disponible. Por favor intente más tarde.</p>
                        <p>Número de orden a anular: <b>".$buyOrder."</b> (Código de autorización: <b>".$authorizationCode."</b>).</p>
                        ";
        } else {
            $message = "Transaci&oacute;n Finalizada";
        }

        $next_page = '';
        
        break;
}

if (!isset($request) || !isset($result) || !isset($message) || !isset($next_page)) {

    $result = "Ocurri&oacute; un error al procesar tu solicitud";
    echo "<div style = 'background-color:lightgrey;'><h3>result</h3>$result;</div><br/><br/>";
    echo "<a href='.'>&laquo; Regresar a CNJoven</a>";
    die;
}

/* Respuesta de Salida - Vista WEB ********************** */
?>

<h3><?php echo $tx_step; ?></h3>
<p><?php  echo $message; ?></p>


<?php if (strlen($next_page) && $post_array) { ?>

        <form action="<?php echo $next_page; ?>" method="post">
            <input type="hidden" name="authorizationCode" id="authorizationCode" value="">
            <input type="hidden" name="amount" id="amount" value="">
            <input type="hidden" name="buyOrder" id="buyOrder" value="">
            <input type="submit" value="<?php echo $button_name; ?>">
        </form>


        <script>
        /*global localStorage*/    
            var authorizationCode = localStorage.getItem('authorizationCode');
            document.getElementById("authorizationCode").value = authorizationCode;
            
            var amount = localStorage.getItem('amount');
            document.getElementById("amount").value = amount;
            
            var buyOrder = localStorage.getItem('buyOrder');
            document.getElementById("buyOrder").value = buyOrder;
            
            localStorage.clear();
            
        </script>
        
<?php } elseif (strlen($next_page)) { ?>
    <form action="<?php echo $next_page; ?>" method="post">
    <input type="hidden" name="token_ws" value="<?php echo ($token); ?>">
    <input type="submit" value="<?php echo $button_name; ?>">
</form>
<?php } ?>

<div class="logdatos">
<h3>Request</h3>
<pre><?php print_r($request)?></pre>
<h3>Result</h3>
<pre><?php  var_dump($result)?></pre>
<H3>$_GET</H3>
<pre><?php print_r($_GET)?></pre>
<H3>$_POST</H3>
<pre><?php print_r($_POST)?></pre>
<h3>$_REQUEST</h3>
<pre><?php print_r($_REQUEST)?></pre>
<h3>LocalStorage</h3>
<pre id="lsdump"></pre>
</div>
<script>
    /*global localStorage*/
    var lsdump = document.getElementById("lsdump");
    lsdump.innerHTML = JSON.stringify (localStorage);
</script>

<br>
<a href=".">&laquo; volver a index</a>