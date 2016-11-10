<?php

/**
 * @author     Allware Ltda. (http://www.allware.cl)
 * @copyright  2015 Transbank S.A. (http://www.tranbank.cl)
 * @date       Jan 2015
 * @license    GNU LGPL
 * @version    1.0
 */

class WebPayConfig {

    private $params = array();

    /**
     * Constructor de WebPayConfig
     * */
    function __construct($params) {
        $this->params = $params;
    }

    /**
     * Retorna parametros de configuración
     * */
    public function getParams() {
        return $this->params;
    }

    /**
     * Retorna parametro de configuración
     * */
    public function getParam($name) {
        return $this->params[$name];
    }

    /**
     * Retorna parametros de configuración (INTEGRACIÓN por defecto)
     * */
    public function getModo() {
        $modo = $this->params["MODO"];
        if (!isset($modo) || $modo == "") {
            $modo = "INTEGRACION";
        }
        return $modo;
    }

}