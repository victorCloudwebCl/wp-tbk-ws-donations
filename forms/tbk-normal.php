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
        
        $tx_step = "Ir al formulario de pago";

        /** Monto de la transacción */
        $amount = isset($_GET["amount"]) ? $_GET["amount"] : '10000';

        /** Orden de compra de la tienda */
        $buyOrder = date("Ymd-Hms-").rand(1000,5000); 
        // Generar OC duplicadas.
        //$buyOrder = 9999;
        
        //Control de OC duplicadas.
        $checkFile =  dirname( dirname(__FILE__) ).'/log/ocinit/'.$buyOrder.'.txt';
        
        if (file_exists($checkFile)){
                echo "
                    <div class=\"error\">
                    <h3>Transacción rechazada.</h3>
                    <b> No se ha cargado dinero de tu cuenta o tarjeta.</b><br>
                    Número de orden: <b>".$buyOrder."</b>
                    Posibles causas:<br>
                                Las posibles causas de este rechazo son:<br>
                                - Error en el ingreso de los datos de su tarjeta de Crédito o Débito (fecha y/o código de seguridad).<br>
                                - Su tarjeta de Crédito o Débito no cuenta con saldo suficiente.<br>
                                - Tarjeta aun no habilitada en el sistema financiero.<br>
                                <br>
                    </div>
                                ";                                
                die;
            }
            
       
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
            $button_name = "Pagar";
        
        break;

    case "getResult":
  
        //cuando getResult
        if (!isset($_POST["token_ws"]))
            break;

        /** Token de la transacción */
        $token = filter_input(INPUT_POST, 'token_ws');
        
        $request = array(
            "token" => filter_input(INPUT_POST, 'token_ws')
        );

        /** Rescatamos resultado y datos de la transaccion */
        $result = $webpay->getNormalTransaction()->getTransactionResult($token);
        
                
                //El archivo de log se genera por primera vez sólo cuando hay un getResult.
                $createFileName =  dirname( dirname(__FILE__) ).'/log/ocinit/'.$result->buyOrder.'.txt';
                
                $sameFile = fopen($createFileName,'a');
                fwrite($sameFile,'getTransactionResult------------------------------');
                fwrite($sameFile,'REQUEST:');
                fwrite ($sameFile,print_r($request, true));
                fwrite($sameFile,'RESPONSE:');
                fwrite ($sameFile,print_r($result, true));
                fclose($sameFile);
        
        /** Verificamos resultado  de transacción */
        
        
        if ($result->detailOutput->responseCode === 0) {

            $tx_step = "Autoriza tu pago.";
            
            switch ($result->detailOutput->paymentTypeCode){
                case ("VD"):
                    $tipoPagoDisplay = "Débito";
                    $tipoCuotasDisplay = "Venta débito";
                    $numeroCuotasDisplay = "00 (sin cuotas)";
                    break;
                case ("VN"):
                    $tipoPagoDisplay = "Crédito, sin cuotas";
                    $tipoCuotasDisplay = "Sin cuotas";
                    $numeroCuotasDisplay = "00";
                    break;
                case ("VC"):
                    $tipoPagoDisplay = "Crédito";
                    $tipoCuotasDisplay = "Cuotas normales";
                    $numeroCuotasDisplay = $result->detailOutput->sharesNumber;
                    break;
                case ("SI"):
                    $tipoPagoDisplay = "Crédito";
                    $tipoCuotasDisplay = "Sin interés";
                    $numeroCuotasDisplay = "3 cuotas sin interés";
                    break;
                case ("S2"):
                    $tipoPagoDisplay = "Crédito";
                    $tipoCuotasDisplay = "Sin interés";
                    $numeroCuotasDisplay = "2 cuotas sin interés";
                    break;
                case ("nc"):
                    $tipoPagoDisplay = "Crédito sin interés";
                    $tipoCuotasDisplay = "Sin interés";
                    $numeroCuotasDisplay = "--"; // <--- SEGÚN CONTRATO
                    break;
                
            }
           
            /** propiedad de HTML5 (web storage), que permite almacenar datos en nuestro navegador web */
            
            echo '<script>
                localStorage.setItem("authorizationCode", "'.$result->detailOutput->authorizationCode.'");
                localStorage.setItem("amount", "'.$result->detailOutput->amount.'");
                localStorage.setItem("buyOrder", "'.$result->buyOrder.'");
                localStorage.setItem("accountingDate", "'.$result->accountingDate.'");
                localStorage.setItem("transactionDate", "'.$result->transactionDate.'");
                localStorage.setItem("sessionId", "'.$result->sessionId.'");
                localStorage.setItem("cardNumber", "'.$result->cardDetail->cardNumber.'");
                localStorage.setItem("cardExpirationDate", "'.$result->cardDetail->cardExpirationDate.'");
                localStorage.setItem("tipoPagoDisplay", "'.$tipoPagoDisplay.'");
                localStorage.setItem("tipoCuotasDisplay", "'.$tipoCuotasDisplay.'");
                localStorage.setItem("numeroCuotasDisplay", "'.$numeroCuotasDisplay.'");
                </script>';
           
            
            // HTML de la página de transición (cuadro blanco, envío de form auto.)
            $message = '<div id="transicion" style="position:fixed;top:0;left:0;width:100%;height:100%; background-image:url("https:&#47;&#47;webpay3g.transbank.cl&#47;webpayserver&#47;imagenes&#47;background.gif";z-index:100!important"></div>
                        <style>   body{position:relative;}</style>
                ';

            $next_page = $result->urlRedirection;
            $button_name = "Ver voucher &raquo;";
            
            
        } else {
            
            $tx_step = "Donación rechazada.";
            $motivo = ($result->detailOutput->responseCode);
            $motivoDirecto = utf8_decode($result->detailOutput->responseDescription);
            $buyOrder = ($result->detailOutput->buyOrder);
            
            
            switch  (true){
        
                    case stristr ($motivo,'-1'):
                        $motivo = '<p>Rechazo de Transbank</p>';
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

            $message = "<div class=\"error\">
                        <h3>Transacción Rechazada n° ".$buyOrder."</h3>
                                Posibles causas:<br>
                                Las posibles causas de este rechazo son:<br>
                                - Error en el ingreso de los datos de su tarjeta de Crédito o Débito (fecha y/o código de seguridad).<br>
                                - Su tarjeta de Crédito o Débito no cuenta con saldo suficiente.<br>
                                - Tarjeta aun no habilitada en el sistema financiero<br>
                                <br>
                                Código de Orden: <b>'.$buyOrder.'</b><br>
                                </div>
                                <script>console.log('".$motivo."--".$motivoDirecto."');</script>
                                ";
            $next_page = '';
        }

        break;
    
    case "end":
        
        //Si no está TBK_TOKEN en el POST, la llamada viene desde un éxito.
        
        $post_array = true;
        $tx_step = "Gracias por tu donación";
        $request = "";
        $result = $_POST;
        $message = '<p>Transacion finalizada. Muchas gracias por tu aporte.<br>
                    <p>Número de Orden:<b id="buyOrder"></b><br>
                    Monto de la donación: $ <b id="amount"></b><br>
                    Código de autorización: <b id="authorizationCode"></b><br>
                    Fecha de transacción: <b id="transactionDate"></b><br>
                    Tipo de pago: <b id="tipoPagoDisplay"></b><br>
                    Cuotas:<b id="numeroCuotasDisplay"></b><br>
                    Últimos 4 dígitos de la tarjeta utilizada:<b id="cardNumber"></b><br>

                    <script>
                    buyOrder = document.getElementById("buyOrder");
                    buyOrder.innerHTML = localStorage.getItem("buyOrder");
                    
                    amount = document.getElementById("amount");
                    amount.innerHTML = localStorage.getItem("amount");
                    
                    authorizationCode = document.getElementById("authorizationCode");
                    authorizationCode.innerHTML = localStorage.getItem("authorizationCode");
                    
                    transactionDate = document.getElementById("transactionDate");
                    transactionDate.innerHTML = localStorage.getItem("transactionDate");
                    
                    tipoPagoDisplay = document.getElementById("tipoPagoDisplay");
                    tipoPagoDisplay.innerHTML = localStorage.getItem("tipoPagoDisplay");
                    
                     
                    numeroCuotasDisplay = document.getElementById("numeroCuotasDisplay");
                    numeroCuotasDisplay.innerHTML = localStorage.getItem("numeroCuotasDisplay");
                    
                    cardNumber = document.getElementById("cardNumber");
                    cardNumber.innerHTML = localStorage.getItem("cardNumber");

                    </script>
                    ';
        
        
        $next_page = "";
        $button_name = "";
        
                $sameFile =  dirname( dirname(__FILE__) ).'/log/ocinit/acknowledgeTransaction.txt';
                $sameFile = fopen($sameFile,'a');
                fwrite($sameFile, 'acknowledgeTransaction------------------------------');
                fwrite($sameFile,'REQUEST:');
                fwrite ($sameFile,print_r($_REQUEST, true));
                fwrite($sameFile,'RESPONSE:');
                fwrite ($sameFile,print_r($result, true));
                fclose($sameFile);
        
        //Si está TBK_TOKEN, la llamada a "end" viene de una ANULACIÓN.
        //TBK devuelve el número de orden en la llamada POST, junto con el token y ID de sesión.
        
        if ( isset($_POST["TBK_TOKEN"]) ){
        $tx_step = "Transacción rechazada";
        
        $message = "Número de orden: <b> ".$_POST["TBK_ORDEN_COMPRA"].". </b><br>
                    <b>No se ha cargado dinero de tu cuenta.</b><br>
                                Las posibles causas de este rechazo son:<br>
                                - Error en el ingreso de los datos de su tarjeta de Crédito o Débito (fecha y/o código de seguridad).<br>
                                - Su tarjeta de Crédito o Débito no cuenta con saldo suficiente.<br>
                                - Tarjeta aun no habilitada en el sistema financiero.<br>
                                <br>";

        $next_page = $sample_baseurl."/donacion-monetaria/";
        $button_name = "Reintentar donación";
        }
        
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


// Error en certificado (y otros)
// No se puede iniciar sesión en Transbank.

if (!isset($request) || !isset($result) || !isset($message) || !isset($next_page)) {

 $tx_step = "Transacción rechazada";
        
        $message = "Número de orden: <b> ".$request[buyOrder].". </b><br>
                    <b>No se ha cargado dinero de tu cuenta.</b><br>
                                Las posibles causas de este rechazo son:<br>
                                - Error en el ingreso de los datos de su tarjeta de Crédito o Débito (fecha y/o código de seguridad).<br>
                                - Su tarjeta de Crédito o Débito no cuenta con saldo suficiente.<br>
                                - Tarjeta aun no habilitada en el sistema financiero.<br>
                                <br>";
}


/* Respuesta de Salida - Vista WEB ********************** */
?>

<h3><?php echo $tx_step; ?></h3>
<p><?php  echo $message; ?></p>

<?php if (strlen($next_page) && $post_array) { ?>

        <form action="<?php echo $next_page; ?>" method="post" id="donationsForm" >
            <input type="hidden" name="authorizationCode" id="authorizationCode" value="">
            <input type="hidden" name="amount" id="amount" value="">
            <input type="hidden" name="buyOrder" id="buyOrder" value="">
            <input type="submit" value="<?php echo $button_name; ?>">
        </form>

        <script>
                document.body.appendChild(document.getElementById("transicion"))
                document.getElementById("donationsForm").submit();;
         </script>
        
 <script>
 
        /*global localStorage*/    
            var authorizationCode = localStorage.getItem('authorizationCode');
            document.getElementById("authorizationCode").value = authorizationCode;
                      
            var amount = localStorage.getItem('amount');
            document.getElementById("amount").value = amount;
            
            var buyOrder = localStorage.getItem('buyOrder');
            document.getElementById("buyOrder").value = buyOrder;
            
</script>
       
        
<?php } elseif (strlen($next_page)) {


//Init Transaction form -------------
?>
    
    <form action="<?php echo $next_page; ?>" method="post" id="donationsForm">
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
<h3>otros</h3>
</div>

<script>
    /*global localStorage*/
    var lsdump = document.getElementById("lsdump");
    lsdump.innerHTML = JSON.stringify (localStorage);
</script>

<br>
<a href=".">&laquo; volver a index</a>

