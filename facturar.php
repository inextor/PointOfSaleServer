<?php

namespace APP;

include_once( __DIR__.'/app.php' );
//include_once( __DIR__.'/facturacion/CFDI.php');
include_once( __DIR__.'/Factura.php');

use \akou\DBTable;
use \akou\LoggableException;
use \akou\ValidationException;
use \akou\SessionException;


class Service extends SuperRest
{
	function get()
	{
		//Metodo de pago is uno de
		// service.getMetodoPago = function(metodoPago){
		//	switch(metodoPago){
		//	case "Cheque":
		//		return "02";
		//	case "Efectivo":
		//		return "01";
		//	case "TarjetaCredito":
		//		return "04";
		//	case "TarjetaDebito":
		//		return "28";
		//	case "Transferencia":
		//		return "03";
		//	}
		//}

		$params = $_GET;
		try
		{
			$this->setAllowHeader();
			app::connect();

			$user = app::getUserFromSession();

			if( !$user )
				throw new SessionException('Por favor iniciar sesion');

			if( empty( $params['id'] ) )
				throw new ValidationException('El id de la orden no puede estar vacio');

			$order		= order::get( $params['id'] );

			if( $order->paid_status !== 'PAID' )
			{
				throw new ValidationException('La orden todavía no ha sido pagada');
			}

			//$billing_address = address::get($order->billing_address_id );
			//$bank_movement_order_array = bank_movement_order::search(array('order_id'=>$order->id),true,'bank_movement_id');
			//$bank_movement_array	= bank_movement::search(array('id'=>array_keys($bank_movement_order_array)),true);
			//$metodo_de_pago = null;
			//foreach($bank_movement_array as $bank_movement)
			//{
			//	if( $bank_movement->transaction_type == 'DEBIT_CARD' )
			//	{

			//	}
			//}


			$order_item_array	= order_item::search(array('order_id'=>$order->id));
			$factura	= new Factura();

			//$factura->subtotal		= $order->subtotal;
			//$factura->total			= $order->total;
			$factura->serie			= 'A';
			//$factura->descuento		= $order->discount;
			$factura->codigo_postal	= $order->sat_codigo_postal;
			$factura->tipo_cambio	= 0;//$order->exchange_rate;
			$factura->moneda		= $order->currency_id;
			//$factura->subtotal		= $order->subtotal;
			$factura->tipo_de_comprobante	= 'I';//I => Ingreso, E=> Egreso, E es para devoluciones cuando ya se facturo
			$factura->metodo_de_pago		= 'PUE';
			$factura->usoCfdi			= $order->sat_uso_cfdi;
			$factura->forma_de_pago		= '01';
			$factura->condiciones_de_pago	= 'Contado';
			$factura->iva			= sprintf("%0.6f",$order->tax_percent);
			$factura->serie	= 'A';//Es el número de serie que utiliza el contribuyente para control interno	de	su	información.
			//Este	campo	acepta	de	1	hasta	25 caracteres alfanuméricos.
			//Al parecer puede ser cualquier cosa la mayoria de las veces	99% solo ponen "A" he visto "B" y "a"
			$factura->datos_adicionales = '';
			$factura->num_reg_id_trib	= '';

			$num_reg_id_trib = '';
			$pais = 'MEXICO';
			$mensaje_PDF = '';

			$factura->set_receptor_CFDI
			(
				$order->sat_rfc,
				$order->sat_razon_social,
				$num_reg_id_trib,
				$order->sat_uso_cfdi,
				$pais,
				$order->sat_email,
				$mensaje_PDF
			);


			foreach($order_item_array as $order_item)
			{
				$item= item::get($order_item->item_id);
				// $servicio = servicio::get($dv->item_id);
				$unidad_medida= unidad_medida_sat::get( $item->unidad_medida_sat_id );
				$category	= category::get( $item->category_id );
				$item		= item::get( $order_item->item_id );

				$factura->agregar_concepto
				(
					$order_item->unitary_price,
					$order->tax_percent,
					$item->clave_sat,
					$order_item->qty,
					$unidad_medida->nombre, //Unidad
					$unidad_medida->id,//Clave Unidad
					($category ? $category->name : '').' '.$item->name,//Descripcion
					$order_item->discount
				);
			}

			$factura->setDatosCfdi();
			$factura_exitosa = $factura->facturar();
			error_log(print_r( $factura_exitosa, true ) );
			DBTable::commit();
			return $this->sendStatus( 200 )->json(true);
		}
		catch(LoggableException $e)
		{
			DBTable::rollback();
			return $this->sendStatus( $e->code )->json(array('error'=>$e->getMessage()));
		}
		catch(\Exception $d)
		{
			DBTable::rollback();
			return $this->sendStatus( 500 )->json(array('error'=>'Ocurrio un error por favor intente mas tarde '.$d->getMessage()));
		}
		DBTable::rollback();
		return $this->sendStatus( 500 )->json(array('error'=>'Ocurrio un error por favor intente mas tarde','dev'=>"llego al final quien sabe que paso"));
	}
}

$l = new Service();
$l->execute();
