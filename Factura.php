<?php
namespace APP;

include_once('nusoap.php');
use AKOU\SystemException;

class Factura
{
	public function __construct($usuario = NULL, $contrasena=NULL )
	{
		$this->total			= 0;
		$this->subtotal			= 0;
		$this->total_descuento	= 0;
		//$this->version_cfdi =
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

		$this->client = new \nusoap_client("$this->server_URL/Digifact.asmx?WSDL", 'wsdl');
	}

	public function set_receptor_CFDI($rfc, $razon_social, $num_reg_id_trib, $uso_cfdi, $pais, $email, $mensaje_PDF)
	{
		$this->receptor_CFDI = array
		(
			'RFC'			=> $rfc,
			'RazonSocial'	=> htmlentities($razon_social,ENT_QUOTES,'UTF-8'),
			'NumRegIdTrib'	=> $num_reg_id_trib,
			'UsoCfdi'		=> $uso_cfdi,
			'Pais'			=> $pais,
			'Email'			=> $email
		);
		if( $mensaje_PDF )
			$this->set_receptor_CFDI['MensajePDF'] = $mensaje_PDF;
	}

			/*
			 * G01	Adquisición de mercancias	Sí	Sí
G02	Devoluciones, descuentos o bonificaciones	Sí	Sí
G03	Gastos en general	Sí	Sí
I01	Construcciones	Sí	Sí
I02	Mobilario y equipo de oficina por inversiones	Sí	Sí
I03	Equipo de transporte	Sí	Sí
I04	Equipo de computo y accesorios	Sí	Sí
I05	Dados, troqueles, moldes, matrices y herramental	Sí	Sí
I06	Comunicaciones telefónicas	Sí	Sí
I07	Comunicaciones satelitales	Sí	Sí
I08	Otra maquinaria y equipo	Sí	Sí
D01	Honorarios médicos, dentales y gastos hospitalarios.	Sí	No
D02	Gastos médicos por incapacidad o discapacidad	Sí	No
D03	Gastos funerales.	Sí	No
D04	Donativos.	Sí	No
D05	Intereses reales efectivamente pagados por créditos hipotecarios (casa habitación).	Sí	No
D06	Aportaciones voluntarias al SAR.	Sí	No
D07	Primas por seguros de gastos médicos.	Sí	No
D08	Gastos de transportación escolar obligatoria.	Sí	No
D09	Depósitos en cuentas para el ahorro, primas que tengan como base planes de pensiones.	Sí	No
D10	Pagos por servicios educativos (colegiaturas)	Sí	No
P01	Por definir	Sí	Sí
			 */
			//$usoCfdi = 'D10';


	function setDatosCfdi()
	{

		$this->datos_CFDI = array(
			'FormadePago'			=> $this->forma_de_pago, //01,99
//			'Version'				=> $this->version_cfdi,
			'Moneda'				=> $this->moneda,
			'Subtotal'				=> sprintf("%0.6f",$this->subtotal),
			'Total'					=> sprintf("%0.6f",$this->total),
			'Descuento'				=> sprintf("%0.6f",$this->descuento),
			'CondicionesDePago'		=> $this->condiciones_de_pago, //"Contado"
			'Serie'					=> $this->serie,
			'TipodeComprobante'		=> $this->tipo_de_comprobante, //"I" deveria ser este,"FA", I ingreso
			'TipoCambio'			=> $this->tipo_cambio,
			'LugarDeExpedicion'		=> $this->codigo_postal,
			'MetodoPago'			=> $this->metodo_de_pago, //PUE,PPD
			'DatosAdicionales'		=> $this->datos_adicionales
		);

		if( !empty( $this->mensaje_PDF) )
			$this->datos_CFDI['MensajePDF'] = $this->mensaje_PDF;
	}

	//Valor Unitario no lleva iva
	//$iva es en la forma 0.08
	function agregar_concepto($valor_unitario, $iva, $clave_prod_serv, $cantidad, $unidad, $clave_unidad, $descripcion, $descuento)
	{
		$importe		= $cantidad*$valor_unitario;
		$base			= $importe-$descuento;
		$iva_declarar	= $base*$iva;
		$this->total_descuento	+= $descuento;

		$impuesto_traslado = array
		(
			'Impuesto'		=> '002',
			'TipoFactor'	=> 'Tasa',
			'Base'			=> sprintf("%0.6f",$base),
			'TasaOCuota'	=> sprintf("%0.6f",$this->iva),
			'Importe'		=> sprintf("%0.6f",$iva_declarar),
		);

		$this->conceptos[] = array(
			'ClaveProdServ'	=> $clave_prod_serv,
			'Cantidad'		=> sprintf("%0.6f",$cantidad ),
			'Importe'		=> sprintf("%0.6f",$importe ),
//			'Total'			=> (float) $total,
			'Unidad'		=> htmlentities($unidad,ENT_QUOTES,'UTF-8'),
			'Descuento'		=> $descuento,
			'claveUnidad'	=> $clave_unidad,
			'Descripcion'	=> htmlentities($descripcion,ENT_QUOTES,'UTF-8'),
			'ValorUnitario'	=> sprintf("%0.6f",$valor_unitario),
			'Traslados'		=> array
			(
				'ImpuestoTrasladado'	=> $impuesto_traslado
			)
		);

		$this->subtotal					+= $importe;
		$this->total					+= ($importe+$iva_declarar-$descuento);
	}

	public function getError(){
		return $this->error;
	}

	/**
	* @summary: Regresa el body del request que se enviara a sicofi
	*/
	public function getBody()
	{
		return array
		(
			'CFDIRequest' => array
			(
				'XMLAddenda'	=> $this->credenciales['XMLAddenda'],
				'Usuario'		=> $this->credenciales['Usuario'],
				'Contrasena'	=> $this->credenciales['Contrasena'],
				'DatosCFDI'		=> $this->datos_CFDI,
				'ReceptorCFDI'	=> $this->receptor_CFDI,
				'ConceptosCFD'	=> array
				(
					'Conceptos' => array('ConceptoCFDI' => $this->conceptos )
				),
				'CFDIRelacion' => array
				(
					'Relacionados' => array
					(
						'CFDISRelacionado' => array
						(
							array('UUID' => "UUID" ),
							array('UUID' => "UUID2" ),
						)
					)
				)
			)
		);
	}

	public function facturar()
	{
		$request_body = $this->getBody();

		error_log(print_r(  $request_body, true ) );

		$response = $this->client->call
		(
			'GeneraCFDIV33',
			$request_body,
			"uri:$this->server_URL/Digifact.asmx?WSDL",
			"uri:$this->server_URL/Digifact.asmx?WSDL/GeneraCFDIV33"
		);

		if( $response == false )
		{
			throw new SystemException('Ocurrio un error al facturar '.$this->client->getError() );
		}


		if ($this->client->fault)
		{
			error_log('Fault is '.print_r( $this->client->fault, true ) );
			throw new SystemException('Ocurrio un error '.$this->client->getError());
		}

		$this->CFDI_result = $response['GeneraCFDIV33Result'];

		if( $response['GeneraCFDIV33Result']['CFDICorrecto'] == "true")
		{
			return true;
		}
		else
		{
			throw new SystemException('Ocurrio un error al facturar '.print_r($response['GeneraCFDIV33Result'],true));
		}
		return $response;
	}

	public function getCFDIResult()
	{
		return $this->CFDI_result;
	}

	public function getUUID()
	{
		return $this->CFDI_result['UUID'];
	}

	public function getXML()
	{
		return $this->CFDI_result['XMLCFDI'];
	}

	public function generarPDF()
	{
		$params = array("pdfrequest" => array(
			"Usuario" => $this->credenciales['Usuario'],
			"Contrasena" => $this->credenciales['Contrasena'],
			"UUID" => $this->getUUID(),
			"Timbrado" => false,
			"CFDI" => true
		));

		$this->pdf_response = $this->client->call("GeneraPDFCFDIV33", $params, "uri:$this->server_URL/Digifact.asmx?WSDL", "uri:$this->server_URL/Digifact.asmx?WSDL/GeneraPDFCFDIV33");

		if($this->pdf_response["GeneraPDFCFDIV33Result"]["PDFCorrecto"] == "true")
		{
			return true;
		}
		else
		{
		//	error_log("ocurrio un error al generar el archivo el error es:".print_r($this->pdf_response["GeneraPDFCFDIV33Result"],true));
		}
		return false;
	}

	public function getPDF()
	{
		return $this->pdf_response['GeneraPDFCFDIV33Result']['PDF'];
	}
}
