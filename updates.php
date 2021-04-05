<?php
namespace APP;

include_once( __DIR__.'/app.php' );
include_once( __DIR__.'/akou/src/ArrayUtils.php');
include_once( __DIR__.'/SuperRest.php');

use \akou\Utils;
use \akou\DBTable;
use \akou\RestController;
use \akou\ArrayUtils;
use \akou\ValidationException;
use \akou\LoggableException;
use \akou\SystemException;
use \akou\SessionException;

class Service extends SuperRest
{
	function post()
	{
		session_start();
		App::connect();
		$this->setAllowHeader();

		$params = $this->getMethodParams();
		$name = $params['method'];

		DBTable::autocommit(false);
		try
		{
			if( is_callable(array($this, $name) ))
			{
				$result = $this->$name();
				DBTable::commit();
				return $result;
			}
			else
			{
				throw new ValidationException('No se encontro la función '.$name);
			}
		}
		catch(LoggableException $e)
		{
			DBTable::rollback();
			return $this->sendStatus( $e->code )->json(array("error"=>$e->getMessage()));
		}
		catch(\Exception $e)
		{
			DBTable::rollback();
			return $this->sendStatus( 500 )->json(array("error"=>$e->getMessage()));
		}
	}
	function markShippingAsSent()
	{
		$user = app::getUserFromSession();

		if( !$user  )
			throw new ValidationException('Por favor iniciar sesion');

		$params = $this->getMethodParams();

		$shipping = shipping::get( $params['shipping_id'] );

		if( $shipping->status !== 'PENDING' )
		{
			throw new ValidationException('El envio ya fue enviado');
		}

		if( $shipping  == null )
		{
			throw new ValidationException('No se encontro el envio. Por favor intente mas tarde');
		}

		$shipping_items_array = shipping_item::search(array('shipping_id'=>$shipping->id));

		foreach($shipping_items_array as $shipping_item)
		{
			$pallet = $shipping_item->pallet_id ? pallet::get( $shipping_item->pallet_id ) : null;
			$box = $shipping_item->box_id ? box::get( $shipping_item->box_id ) : null;

			if( $pallet )
			{
				$pallet_content_array = pallet_content::search(array('pallet_id'=>$pallet->id));
				foreach($pallet_content_array as $pallet_content)
				{
					$box = box::get( $pallet_content->box_id );
					$box_content_array =box_content::search(array('box_id'=>$box->id));
					foreach($box_content_array as $box_content)
					{
						$this->debug('user',$user );
						app::sendShippingBoxContent($shipping,$shipping_item,$box,$box_content,$user);
					}

					if( !$box->update('store_id') )
					{
						throw new SystemException('Ocurrio un error por favor intentar mas tarde'.$box->getError());
					}
				}
				$pallet->store_id = $shipping->to_store_id;
				$pallet->update('store_id');
			}
			else if( $box )
			{
				$box_content_array =box_content::search(array('box_id'=>$box->id));
				foreach($box_content_array as $box_content)
				{
					app::sendShippingBoxContent($shipping,$shipping_item, $box,$box_content,$user);
				}

				if( !$box->update('store_id') )
				{
					throw new SystemException('Ocurrio un error por favor intentar mas tarde'.$box->getError());
				}
			}
			else
			{
				app::sendShippingItem( $shipping, $shipping_item, $user );
			}
		}

		$shipping->status = 'SENT';
		$shipping->updated_by_user_id = $user->id;

		if(	!$shipping->update('status','updated_by_user_id') )
		{
			throw new SystemException('Ocurrio un error por favor intentar mas tarde. '.$shipping->getError());
		}

		$user_array = user::search(array('store_id'=>$shipping->to_store_id,'type'=>'USER'),true,'id');

		if( count( $user_array ) == 0 )
		{
			error_log('No hay usuario asignado al alamacen con id '.$shipping->to_store_id);
		}

		foreach($user_array as $user )
		{
			$push_notification				= new push_notification();
			$push_notification->object_type	= 'shipping';
			$push_notification->object_id	= $shipping->id;
			$push_notification->app_path	= '/view-shipping/'.$shipping->id;
			$push_notification->title		= 'Cargamento enviado';
			$push_notification->body		= 'Por favor ponerse al pendiente';
			$push_notification->link		= app::$endpoint.'#/view-shipping/'.$shipping->id;
			$push_notification->user_id		= $user->id;

			if( !$push_notification->insertDb() )
			{
				error_log('Fallo guardar la notificaciones');
			}

			app::sendNotification( $push_notification, array_keys($user_array) );
		}


		return $this->sendStatus(200)->json( $shipping->toArray() );
	}

	function recibirShipping()
	{
		$user = app::getUserFromSession();

		$params = $this->getMethodParams();
		$shipping = shipping::get( $params['shipping_id'] );


		if( $shipping->status == 'DELIVERED' )
			throw new ValidationException('El envio ya fue recibido previamente');

		if( $shipping ->status != 'SENT' )
		{
			throw new ValidationException('El envio primero se tiene que marcar como enviado');
		}

		if( $shipping  == null )
		{
			throw new ValidationException('No se encontro el envio. Por favor intente mas tarde');
		}

		$shipping_items_array = shipping_item::search(array('shipping_id'=>$shipping->id));

		foreach($shipping_items_array as $shipping_item)
		{
			$received_items_count = 0;
			$pallet = $shipping_item->pallet_id ? pallet::get( $shipping_item->pallet_id ) : null;
			$box = $shipping_item->box_id ? box::get( $shipping_item->box_id ) : null;

			if( $pallet )
			{
				$pallet_content_array = pallet_content::search(array('pallet_id'=>$pallet->id));
				foreach($pallet_content_array as $pallet_content)
				{
					$box = box::get( $pallet_content->box_id );
					$box_content_array =box_content::search(array('box_id'=>$box->id));
					foreach($box_content_array as $box_content)
					{
						$qtys = $params['quantities']['content_id-'.$box_content->id];
						app::receiveShippingBoxContent($shipping,$shipping_item,$box,$box_content,$qtys['qty'],$user);
						$received_items_count = $qtys['qty'];
					}

					$box->store_id = $shipping->to_store_id;

					if( !$box->update('store_id') )
					{
						throw new SystemException('Ocurrio un error por favor intentar mas tarde'.$box->getError());
					}
				}
				$pallet->store_id = $shipping->to_store_id;
				$pallet->update('store_id');
			}
			else if( $box )
			{
				$box_content_array =box_content::search(array('box_id'=>$box->id));
				foreach($box_content_array as $box_content)
				{
					$qtys = $params['quantities']['content_id-'.$box_content->id];
					app::receiveShippingBoxContent($shipping,$shipping_item,$box,$box_content,$qtys['qty'],$user);
					$received_items_count = $qtys['qty'];
				}

				$box->store_id = $shipping->to_store_id;

				if( !$box->update('store_id') )
				{
					throw new SystemException('Ocurrio un error por favor intentar mas tarde'.$box->getError());
				}
			}
			else
			{

			}

			$shipping_item->received_qty = $received_items_count;

			if( !$shipping_item->update('received_qty') )
			{
				throw new ValidationException('Ocurrio un error al guardar la informacion por favor intentar mas tarde');
			}
		}
		$shipping->status = 'DELIVERED';
		$shipping->updated_by_user_id = $user->id;
		if( !$shipping->update('status','updated_by_user_id') )
		{
			throw new SystemException('Ocurrio un error por favor intentar mas tarde. '.$shipping->getError());
		}

		$user_array = user::search(array('store_id'=>$shipping->to_store_id,'type'=>'USER'));

		foreach($user_array as $user )
		{
			$push_notification				= new push_notification();
			$push_notification->object_type	= 'shipping';
			$push_notification->object_id	= $shipping->id;
			$push_notification->app_path	= '/view-shipping/'.$shipping->id;
			$push_notification->link		= app::$endpoint.'#/view-shipping/'.$shipping->id;
			$push_notification->user_id		= $user->id;

			if( !$push_notification->insertDb() )
			{
				error_log('Fallo guardar la notificaciones');
			}
		}

		$this->sendStatus(200)->json( $shipping );
	}

	function closeProduction()
	{
		$params = $this->getMethodParams();

		$user = app::getUserFromSession();

		if( !$user )
			throw new SessionException('Por favor iniciar sesion');


		$production = production::get( $params['production_id'] );
		if( $production == null )
			throw new ValidationException("No se encontro la produccion");


		$production->status = 'CLOSED';
		if( !$production->update('status') )
		{
			throw new SystemException('ocurrio un error por favor intentar mas tarde '.$production->getError());
		}
		return $this->sendStatus( 200 )->json( $production->toArray() );
	}

	function asignarRangoMarbeteCajas()
	{
		$params = $this->getMethodParams();
		$user = app::getUserFromSession();

		if( !$user )
			throw new SessionException('Por favor iniciar sesion');


		foreach( $params['boxes'] as $box_info )
		{
			if( empty( $box_info['box']['id'] ) )
			{
				throw new ValidationException('El id de la caja no puede ser nula');
			}

			$box = box::get( $box_info['box']['id'] );
			if( $box->serial_number_range_end !== null )
			{
				throw new ValidationException('Ya se registraron los marbetes para la caja "'.$box->id.'"');
			}

			$box->serial_number_range_start = $params['serial_number_range_start'];
			$box->serial_number_range_end	= $params['serial_number_range_end'];
			$box->updated_by_user_id = $user->id;

			if( !$box->update('serial_number_range_end','serial_number_range_start') )
			{
				error_log( $box->getLastQuery() );
				throw new SystemException('Ocurrio un error por favor intentar mas tarde'.$box->getError());
			}
		}

		return true;
	}

	function markOrderAsDelivered()
	{
		$user=  app::getUserFromSession();
		if( !$user )
			throw new SessionException("por favor iniciar sesion");

		$params = $this->getMethodParams();

		$order = order::get( $params['order_id'] );

		if( !$order )
		{
			throw new ValidationException('La orden no se encontro');
		}

		if( $order->delivery_status == 'DELIVERED')
			throw new ValidationException('La orden ya ha sido entregada previamente');

		app::updateOrderTotal($order->id);

		$order_item_array = order_item::search(array('delivery_status'=>"PENDING"));

		foreach($order_item_array as $order_item )
		{
			$order_item->status = 'DELIVERED';
			app::extractOrderItem($order_item, $user);
		}


		$order->delivery_status = 'DELIVERED';
		$order->updated_by_user_id = $user->id;

		if( !$order->update('delivery_status','updated_by_user_id') )
		{
			throw new SystemException('Ocurrio un error, por favor intentar mas tarde. '.$order->getError());
		}


		$push_notification = new push_notification();
		$push_notification->user_id = $order->created_by_user_id;
		$push_notification->title = 'Nueva Venta';
		$push_notification->body = 'Nueva venta para '.$order->client_user_id.' en la sucursal '.$order->store_id;
		$push_notification->icon_image_id = 51;
		$push_notification->object_type = 'order';
		$push_notification->app_path = '/view-order/'.$order->id;
		$push_notification->object_id = $order->id;
		$push_notification->insertDb();

		//app::sendNotification($notification,array($order->cashier_user_id));

		$order->load(true);

		return $this->sendStatus(200)->json( $order->toArray() );
	}

	function sendNotification()
	{
		if( empty( $_GET['push_notification_id'] ) )
		{
			throw new ValidationException('El id de la notificacion no puede estar vacio');
		}

		$push_notification = push_notification::get( $_GET['push_notification_id'] );

		if( !$push_notification )
		{
			throw new ValidationException('No se encontro la notification');
		}

		app::sendNotification( $push_notification, array( $push_notification->user_id ) );
	}

	function copyPricesFromStoreToStore()
	{
		$params = $this->getMethodParams();

		if( empty($params['from_store'] ) )
			throw new ValidationException('from store cant be empty');

		if( empty($params['to_store'] ) )
			throw new ValidationException('to store cant be empty');

		$from_store_id = DBTable::escape( $params['from_store_id'] );
		$to_store_id = DBTable::escape( $params['to_store_id'] );

		$sql = 'INSERT INTO `price` (item_id,"'.$to_store_id.'",price ) VALUES
					SELECT p.item_id,p.store_id,p.price FROM price AS p WHERE p.store_id = "'.$from_store_id.'"
					ON DUPLICATE KEY UPDATE price = p.price';

		$result = DBTable::query( $sql );
		if( $result )
			return $this->sendStatus(200)->json(true);
		else
			return $this->sendStatus(500)->json(DBTable::$connection->error );
	}

function removeBoxFromPallet()
	{
		$user = app::getUserFromSession();

		if( !$user )
			throw new SessionException("Por favor iniciar session");

		$params = $this->getMethodParams();

		if( empty( $params['box_id'] ) )
			throw new ValidationException('El id de la caja no puede estar vacio');

		$pallet_content = pallet_content::searchFirst(array('box_id'=>$params['box_id'],'status'=>'ACTIVE') );

		if( $pallet_content == null )
		{
			throw new ValidationException('No se encontro la caja en ninguna tarima');
		}

		$pallet_content->status = 'REMOVED';

		$pallet_content->updated_by_user_id = $user->id;

		if( !$pallet_content->update('status','updated_by_user_id') )
		{
			throw new SystemException('Ocurrio un error por favor intentar mas tarde. '.$pallet_content->getError() );
		}

		error_log('query'.$pallet_content->getLastQuery());


		return $this->sendStatus(200)->json( true );
	}

	function markPushNotificationsAsRead()
	{
		$user = app::getUserFromSession();

		if( $user == null )
			throw new SessionException('Por favor iniciar sesion');

		$sql = 'UPDATE push_notification SET read_status = "READ" WHERE user_id = "'.DBTable::escape($user->id).'"';
		DBTable::query( $sql );
		return $this->sendStatus(200)->json(true);
	}

	function closeStocktake($stocktake)
	{
		$user = app::getUserFromSession();

		if( $user == null )
			throw new SessionException('Por favor iniciar sesion');

		if( empty( $_GET['id']  ) )
		{
			throw new ValidationException('El id no puede estar vacio');
		}
		$stocktake	= stocktake::get($_GET['id']);

		if( $stocktake == null )
			throw new ValidationException('No se encontro la toma de inventario con id "'.$_GET['id'].'"');


		$stocktake_scan_array		= stocktake_scan::search(array('stocktake_id'=>$stocktake->id),true,'id');
		$stocktake_scan_props		= ArrayUtils::getItemsProperties($stocktake_scan_array,'pallet_id','box_id','box_content_id','item_id');
		$pallet_array				= pallet::search(array('id'=>$stocktake_scan_props['pallet_id']),true,'id');
		$pallet_content_array		= pallet_content::search(array('pallet_id'=>array_keys($pallet_array),'status'=>'ACTIVE'),true,'id');


		$pallet_content_box_ids	= ArrayUtils::getItemsProperty($pallet_content_array,'box_id');
		$box_ids					= array_merge( $pallet_content_box_ids, isset( $stocktake_scan_props['box_id'] ) ?  $stocktake_scan_props['box_id'] : array() );

		$box_array			= box::search(array('id'=>$box_ids),false,'id');
		$box_content_array	=box_content::search(array('box_id'=>array_keys($box_array)),false,'id');

		$stocktake_item_array			= stocktake_item::search(array('stocktake_id'=>$stocktake->id),true,'id');
		$stocktake_item_by_cc			= ArrayUtils::getDictionaryByIndex($stocktake_item_array, 'box_content_id');
		$stocktake_scan_by_item			= ArrayUtils::getDictionaryByIndex($stocktake_scan_array, 'item_id');
		$stocktake_item_by_item			= ArrayUtils::getDictionaryByIndex($stocktake_item_array,'item_id');

		foreach($box_content_array as $box_content)
		{
			$stocktake_item = isset( $stocktake_item_by_cc[ $box_content['id'] ] ) ? $stocktake_item_by_cc[ $box_content['id'] ] : null;

			if( $stocktake_item == null )
			{
				error_log('Esto no debio suceder');
				//Agregar de mas aqui //Es cuando encuentran cosas que no habia
				continue;
			}

			$stocktake_item->current_qty		= $stocktake_item->creation_qty;
			$stocktake_item->updated_by_user_id	= $user->id;

			if( ! $stocktake_item->update('current_qty','updated_by_user_id') )
			{
				throw new SystemException('Ocurrio un error por favor intente mas tarde');
			}
		}

		$stocktake_scan_grouped_by_item_id	= ArrayUtils::groupByIndex($stocktake_scan_by_item,'item_id');

		foreach( $stocktake_scan_grouped_by_item_id	 as $item_id=>$ss_array)
		{
			$sum = 0;
			$stocktake_item = null;

			foreach($ss_array as $stocktake_scan)
			{
				$sum += $stocktake_scan->qty;
			}

			if( isset( $stocktake_item_by_item[ $item_id ] ) )
			{
				$stocktake_item = $stocktake_item_by_item[ $item_id ];
				$stocktake_item->current_qty = 'sum';
				$stocktake_item->updated_by_user_id = $user->id;

				if( $stocktake_item->update('current_qty','updated_by_user_id') )
					throw new SystemException('Ocurrio un error por favor intentar mas tarde. '.$stocktake_item->getError());
			}
			else
			{
				//Nuevo
			}
		}

		$stocktake->updated_by_user_id = $user->id;
		$stocktake->status = 'CLOSED';
		$stocktake->update('status','updated_by_user_id');
	}

	function approveBill()
	{

		$user = app::getUserFromSession();
		if( $user == null )
			throw new ValidationException('Por favor iniciar sesion');

		$params = $this->getMethodParams();

		$bill = bill::get($params['id'] );

		if( $bill == null )
			throw new ValidationException('No se encontro el recibo');


		$bill->accepted_status = 'ACCEPTED';
		$bill->updated_by_user_id = $user->id;
		$bill->approved_by_user_id = $user->id;
		$bill->update('accepted_status','updated_by_user_id','approved_by_user_id');

		return $this->sendStatus(200)->json( $bill->toArray() );
	}

	function rejectBill()
	{

		$user = app::getUserFromSession();
		if( $user == null )
			throw new ValidationException('Por favor iniciar sesion');

		$params = $this->getMethodParams();

		$bill = bill::get($params['id'] );

		if( $bill == null )
			throw new ValidationException('No se encontro el recibo');


		$bill->accepted_status = 'REJECTED';
		$bill->updated_by_user_id = $user->id;
		$bill->update('accepted_status','updated_by_user_id');

		return $this->sendStatus(200)->json( $bill->toArray() );
	}
	function copyPrices()
	{
		$user = app::getUserFromSession();

		if( !$user )
			throw new SessionException('Por favor iniciar sesion');

		$params = $this->getMethodParams();

		$this->debug('params', $params );

		$sql = 'INSERT INTO price ( store_id,item_id, price, price_type_id, updated_by_user_id )
					(
						SELECT  "'.DBTable::escape($params['to_store_id']).'" AS s_id,
							p.item_id,
							p.price,
							p.price_type_id ,
							"'.DBTable::escape( $user->id ).'"
						FROM price AS p
						WHERE p.store_id= "'.DBTable::escape($params['from_store_id']).'"
					)
				ON DUPLICATE KEY UPDATE price.price = p.price, updated_by_user_id = "'.DBTable::escape( $user->id).'"';

		error_log( $sql );

		$result = DBTable::query( $sql );
		if( DBTable::$connection->error )
		{
			throw new SystemException( DBTable::$connection->error );
		}
		error_log( print_r( $result, true)  );

		return $this->sendStatus(200)->json(true);
	}
//Surtir Orden
	function fullfillOrder()
	{

		$user = app::getUserFromSession();
		if( !$user )
			throw new LogicException('Por favor iniciar sesion');

		$params = $this->getMethodParams();
		$order = order::get( $params['order_id'] );

		if( $order == null )
		{
			throw new ValidationException('Ocurrio un error  por favor intentar mas tarde. '.DBTable::$connection->error);
		}




		foreach( $params['items'] as $oif)
		{
			$order_item_fullfillment = order_item_fullfillment::createFromArray( $oif );
			if( $order_item_fullfillment->qty == 0 )
			{
				continue;
			}

			$order_item	= order_item::searchFirst(array('order_id'=>$params['order_id'], 'item_id'=>$order_item_fullfillment->item_id ));

			$order_item_fullfillment_array = order_item_fullfillment::search(array('order_id'=>$order->id,'item_id'=>$order_item_fullfillment->item_id));

			$fullfilled_qty = 0;

			foreach($order_item_fullfillment_array as $tmp_oif)
			{
				$fullfilled_qty+= $tmp_oif->qty;
			}

			if( $fullfilled_qty+$order_item_fullfillment->qty > $order_item->qty )
			{
				throw new ValidationException('La cantidad entregada supera lo especificado en la orden');
			}

			$box_content	=box_content::get( $order_item_fullfillment->box_content_id);

			if( !$order_item )
			{
				throw new SystemException('Ocurrio un error por favor intentar mas tarde','No se encontro el item');
			}

			if( !$box_content )
				throw new SystemException('Ocurrio un error por favor intentar mas tarde','No se encontro el contenedor ');

			//No debe pasar nunca
			if( $box_content->box_id !== $order_item_fullfillment->box_id )
				throw new SystemException('La caja no corresponde');

			if( $box_content->qty < $order_item_fullfillment->qty )
			{
				throw new ValidationException('La caja no contine la cantidad de articulos solicitados');
			}

			if( !$order_item_fullfillment->insertDb() )
			{
				throw new ValidationException('Ocurrio un error por favor intentar mas tarde. '.$order_item_fullfillment->getError());
			}

			app::reduceFullfillInfo($order, $order_item_fullfillment, $user );
		}

		return $this->sendStatus(200)->json(true);
	}

	function removeBox()
	{
		$user = app::getUserFromSession();
		if( !$user )
			throw new SessionException('Por favor iniciar session');

		$params = $this->getMethodParams();

		if( empty( $params['note'] ) )
		{
			throw new ValidationException('La nota no puede estar vacia');
		}


		$box = box::get( $params['box_id'] );

		if( !$box )
			throw new ValidationException('La caja no se encontro');

		$box->status = 'DELETED';

		if( !$box->update('status') )
		{
			throw new SystemException('Ocurrio al actualizar el estatus de la caja por favor intentar mas tarde. '.$box->getError());
		}

		$stocktake = null;

		if( !empty( $params['stocktake'] ) )
		{
			$stocktake = stocktake::get( $params['stocktake_id']);
		}

		$temp_box_content_array  =box_content::search(array('box_id'=>array($box->id)),true);
		$box_content_array = ArrayUtils::removeElementsWithValueInProperty($temp_box_content_array,'qty',0);

		foreach($box_content_array as $box_content)
		{
			if( $box_content->qty > 0 )
			{
				error_log('Removing');
				app::addBoxContentMerma($stocktake,$box,$box_content,$params['note'],$user );
			}
			else{
				error_log('Box content doesnt have articles');
			}
		}

		$this->sendStatus(200)->json(true);
	}

	function adjustStock()
	{
		$user = app::getUserFromSession();
		if( !$user )
			throw new SessionException('Por favor iniciar session');


		$params = $this->getMethodParams();

		$results = array();

		foreach($params['stock_records'] as $stock_record)
		{
			app::adjustStock($stock_record['item_id'], $stock_record['store_id'], $user->id, $stock_record['qty'], 'Ajuste de inventario');
			$results[] = $stock_record;
		}

		return $results;
	}
}

$l = new Service();
$l->execute();
