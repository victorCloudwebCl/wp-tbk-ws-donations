<?php

/**
 * @author     Allware Ltda. (http://www.allware.cl)
 * @copyright  2015 Transbank S.A. (http://www.tranbank.cl)
 * @date       Jan 2015
 * @license    GNU LGPL
 * @version    1.0
 */

require_once(__DIR__ . '/soap/soap-wsse.php');
require_once(__DIR__ . '/soap/soap-validation.php');
require_once(__DIR__ . '/soap/soapclient.php');

include('webpay-normal.php');
include('webpay-config.php');

class WebPaySOAP {

    var $config, $webpayNormal;

    /**
     * Constuctor
     * */
    function __construct($params) {

        $this->config = new WebPayConfig($params);
    }

    public function getNormalTransaction() {
        if ($this->webpayNormal == null) {
            $this->webpayNormal = new WebPayNormal($this->config);
        }
        return $this->webpayNormal;
    }

    /**
     * Envia por método POST el token
     * */
    public function redirect($url, $data) {
        echo "<form action='" . $url . "' method='POST' name='webpayForm'>";
        foreach ($data as $name => $value) {
            echo "<input type='hidden' name='" . htmlentities($name) . "' value='" . htmlentities($value) . "'>";
        }
        echo "</form>"
        . "<script language='JavaScript'>"
        . "document.webpayForm.submit();"
        . "</script>";
    }

}

class baseBean {
    
}

class getTransactionResult {

    var $tokenInput; //string

}

class getTransactionResultResponse {

    var $return; //transactionResultOutput

}