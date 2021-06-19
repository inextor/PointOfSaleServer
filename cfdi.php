<?php

class client
{
	function __construct()
	{
		if( $usuario == NULL )
    	{
    		$this->credenciales = array
    		(
    			'XMLAddenda'	=> ''
    			,'Usuario'	=> 'demo1@sicofi.com.mx'
    			,'Contrasena'	=> 'demodemoD'
    		);
    		$this->server_URL = 'http://demo.sicofi.com.mx/SicofiWS33';
    	}
    	else
    	{
    		$this->credenciales = array();
        	$this->credenciales['XMLAddenda'] = '';
        	$this->credenciales['Usuario'] = $usuario;
        	$this->credenciales['Contrasena'] = $contrasena;
    		$this->server_URL = 'https://cfd.sicofi.com.mx/SicofiWS33';
    	}

        // "usuario" => "demo1@sicofi.com.mx", "contrasena" => "demodemoD", "serverURL" => "http://demo.sicofi.com.mx/SicofiWS33"
        // $this->server_URL = $test ? 'http://demo.sicofi.com.mx/SicofiWS33' : 'https://pac1.sicofi.com.mx/SicofiWS33';
        $this->client = new nusoap_client("$this->server_URL/Digifact.asmx?WSDL", 'wsdl');
        $this->conceptos = array();
        $this->impuestos = array();
	}
}
