<?php
namespace APP;

include_once( __DIR__.'/app.php' );
include_once( __DIR__.'/akou/src/ArrayUtils.php');
include_once( __DIR__.'/SuperRest.php');

use \akou\ArrayUtils;
use \akou\DBTable;
use \akou\SystemException;
use \akou\LoggableException;
use \akou\ValidationException;
use \akou\SessionException;


class Service extends SuperRest
{
	function get()
	{
		session_start();
		App::connect();
		$this->setAllowHeader();
		$user = app::getUserFromSession();

		if( !$user )
			return $this->sendStatus( 401 )->json(array("error"=>'Por favor iniciar sesion'));

		return $this->genericGet("order");
	}

	function getInfo($order_array )
	{
		$order_props		= ArrayUtils::getItemsProperties($order_array,'id','store_id','client_user_id','cashier_user_id','shipping_address_id','billing_address_id');

		$store_array		= store::search(array('id'=>$order_props['store_id']), false, 'id');
		$user_ids			= array_merge($order_props['client_user_id'],$order_props['cashier_user_id']);
		$user_array		= user::search(array('id'=>$user_ids),false,'id');
		$order_item_array		= order_item::search(array('order_id'=>$order_props['id']),false,'id');
		$order_item_props	= ArrayUtils::getItemsProperties($order_item_array,'id','item_id','item_option_id','item_extra_id');

		$item_array			= item::search(array('id'=>$order_item_props['item_id']),false,'id');
		$item_option_array	= item_options::search(array('id'=>$order_item_props['item_option_id']),false, 'id');
		$item_extra_array	= item_extras::search(array('id'=>$order_item_props['item_extra_id']),false, 'id');

		$category_ids		= ArrayUtils::getItemsProperty($item_array, 'category_id' );
		$category_array		= category::search(array('id'=>$category_ids),false,'id');

		$address_array		=  address::search(array('id'=>array_merge($order_props['shipping_address_id'],$order_props['billing_address_id'])),false,'id');
		$order_item_grouped = ArrayUtils::groupByIndex( $order_item_array, 'order_id');
		$result = array();

		foreach($order_array as $order)
		{
			$order_items	= empty( $order_item_grouped[ $order['id'] ] ) ? array() : $order_item_grouped[ $order['id'] ];

			$client_user	= empty( $order['client_user_id'] ) ? null : $user_array[$order['client_user_id']];
			$cashier_user	= empty( $order['cashier_user_id'] ) ? null : $user_array[$order['cashier_user_id']];

			if( $client_user )
				$client_user['password'] = '';

			if( $cashier_user )
				$cashier_user['password'] = '';

			$order_item_info_array = array();
			foreach($order_items as $order_item)
			{
				$item = $item_array[ $order_item['item_id'] ];
				$item_option = $item_option_array[ $order_item['item_option_id'] ];
				$item_extra	= $item_extra_array[ $order_item['item_extra_id'] ];

				$order_item_info_array[] = array(
					'order_item'	=> $order_item,
					'item'			=> $item,
					'item_option'	=> $item_option,
					'item_extra'	=> $item_extra
				)
			}

			$result = array(
				'order'=>$order,
				'items' => $order_items,
			);
		}
	}


	function post()
	{
		$this->setAllowHeader();
		$params = $this->getMethodParams();
		app::connect();
		DBTable::autocommit(false );

		try
		{
			$user = app::getUserFromSession();
			if( $user == null )
				throw new ValidationException('Please login');

			$is_assoc	= $this->isAssociativeArray( $params );
			$result		= $this->batchInsert( $is_assoc  ? array($params) : $params );
			DBTable::commit();
			return $this->sendStatus( 200 )->json( $is_assoc ? $result[0] : $result );
		}
		catch(LoggableException $e)
		{
			DBTable::rollback();
			return $this->sendStatus( $e->code )->json(array("error"=>$e->getMessage()));
		}
		catch(Exception $e)
		{
			DBTable::rollback();
			return $this->sendStatus( 500 )->json(array("error"=>$e->getMessage()));
		}
	}

	function put()
	{
		$this->setAllowHeader();
		$params = $this->getMethodParams();
		app::connect();
		DBTable::autocommit( false );

		try
		{
			$user = app::getUserFromSession();
			if( $user == null )
				throw new ValidationException('Please login');

			$is_assoc	= $this->isAssociativeArray( $params );
			$result		= $this->batchUpdate( $is_assoc  ? array($params) : $params );
			DBTable::commit();
			return $this->sendStatus( 200 )->json( $is_assoc ? $result[0] : $result );
		}
		catch(LoggableException $e)
		{
			DBTable::rollback();
			return $this->sendStatus( $e->code )->json(array("error"=>$e->getMessage()));
		}
		catch(Exception $e)
		{
			DBTable::rollback();
			return $this->sendStatus( 500 )->json(array("error"=>$e->getMessage()));
		}

	}

	function batchUpdate($order_info_array)
	{
		$orden_ids = array();

		foreach( $orden_info_array as $orden_info )
		{
			if( empty( $orden_info['orden']['id'] ) )
			{
				$orden = $this->insertOrden( $orden_info );
				$orden_ids[] = $orden->id;
			}
			else
			{
				$orden = $this->updateOrden( $orden_info );
				$orden_ids[] = $orden->id;
			}
		}

		$orden_array = orden::search(array('id'=>$orden_ids),false,'id');
		return $this->getOrdenInfo( $orden_array );
	}

	function batchInsert($array)
	{
		$results = array();

		foreach($array as $params )
		{
			$properties = order::getAllPropertiesExcept('created','updated','id');

			$order = new order();
			$order->assignFromArray( $params, $properties );
			$order->unsetEmptyValues( DBTable::UNSET_BLANKS );

			if( !$order->insert() )
			{
					throw new ValidationException('An error Ocurred please try again later',$order->_conn->error );
			}

			$results [] = $order->toArray();
		}

		return $results;
	}

	function updateOrden($order_info)
	{
		$orden->id = $orden_info['orden']['id'];
		$props = order::getAllPropertiesExcept('created','updated','id');
		$orden->assignFromArray( $orden_info['orden'], $props );

		$orden->setWhereString( true );

		if( !$orden->update() )
		{
			throw new SystemException('Ocurrio un error, por favor intente más tarde. '.$orden->_conn->error);
		}

		if( empty($orden_info['items'] )  )
			throw new SystemException('Items cant be empty');

		//foreach($orden_info['items'] as $oi)
		//{
		//	$order_item	= new orden_item();

		//	if( empty( $oi['order_item']['id'] ) )
		//	{
		//		$this->insertOrderItem( $orden, $od['order_item'] );
		//	}
		//	else
		//	{
		//		$this->updateOrderItem( $orden, $od['order_item'] );
		//		//Magic here
		//	}
		//}
	}

	//function insertOrderItem($order_item_params)
	//{
	//	$order_item = new order_item();
	//	$order_item->assignFromArray( $order_item_params );

	//	$order->unsetEmptyValues( DBTable::UNSET_BLANKS );

	//	if( !$order_item->insertDb() )
	//	{
	//		throw new SystemException('Ocurrio un error, por favor intente más tarde. '.$order_item->_conn->error);
	//	}

	//	return $order_item;
	//}

	//function insertOrderItem($order_item_params)
	//{
	//	$order_item = new order_item();
	//	$props	= order_item::getAllPropertiesExcept('id','created','updated');
	//	$order_item->assignFromArray( $order_item_params, $props );

	//	$order->unsetEmptyValues( DBTable::UNSET_BLANKS );

	//	if( !$order_item->insertDb() )
	//	{
	//		throw new SystemException('Ocurrio un error, por favor intente más tarde. '.$order_item->_conn->error);
	//	}

	//	return $order_item;
	//}

	function updateOrderItem($order_item_params)
	{
		$order_item = new order_item();
		$order_item->id = $order_item_params['id'];
		$order_item->setWhereString(true);

		$props	= order_item::getAllPropertiesExcept('id','created','updated');
		$order_item->assignFromArray( $order_item_params, $props );

		$order->unsetEmptyValues( DBTable::UNSET_BLANKS );

		if( !$order_item->update($props) )
		{
			throw new SystemException('Ocurrio un error, por favor intente más tarde. '.$order_item->_conn->error);
		}

		return $order_item;
	}
}

$l = new Service();
$l->execute();
