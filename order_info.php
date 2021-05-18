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

	function getInfo($order_array)
	{
		$order_props		= ArrayUtils::getItemsProperties($order_array,'id','store_id','client_user_id','cashier_user_id','shipping_address_id','billing_address_id');

		$store_array		= store::search(array('id'=>$order_props['store_id']), false, 'id');
		$user_ids			= array_merge($order_props['client_user_id'],$order_props['cashier_user_id']);
		$user_array			= user::search(array('id'=>$user_ids), false, 'id');
		$order_item_array	= order_item::search(array('order_id'=>$order_props['id'],'status'=>'ACTIVE'), false, 'id');
		$order_item_props	= ArrayUtils::getItemsProperties($order_item_array, 'id','item_id');
		$item_array			= item::search(array('id'=>$order_item_props['item_id']),false,'id');
		$item_option_array	= item_option::search(array('item_id'=>$order_item_props['item_id']),false, 'id');

		$item_option_value_array			= item_option_value::search(array('item_option_id'=>array_keys( $item_option_array ) ), false, 'id');

		$item_ids_from_values				= ArrayUtils::getItemsProperty($item_option_value_array,'item_id');
		$grouped_item_option_array 		= ArrayUtils::groupByIndex($item_option_array,'item_id');
		$grouped_item_option_value_array	= ArrayUtils::groupByIndex($item_option_value_array,'item_option_id');
		$items_from_values_array				= item::search(array('id'=>$item_ids_from_values),false,'id');

		$category_ids		= ArrayUtils::getItemsProperty($item_array, 'category_id' );
		$category_array		= category::search(array('id'=>$category_ids),false,'id');

		$address_array		=  address::search(array('id'=>array_merge($order_props['shipping_address_id'],$order_props['billing_address_id'])),false,'id');
		$order_item_grouped	= ArrayUtils::groupByIndex( $order_item_array, 'order_id');
		$result = array();

		foreach( $order_array as $order )
		{
			$order_items	= empty( $order_item_grouped[ $order['id'] ] ) ? array() : $order_item_grouped[ $order['id'] ];

			$client_user	= empty( $order['client_user_id'] ) ? null : $user_array[$order['client_user_id']];
			$cashier_user	= empty( $order['cashier_user_id'] ) ? null : $user_array[$order['cashier_user_id']];

			if( $client_user )
				$client_user['password'] = '';

			if( $cashier_user )
				$cashier_user['password'] = '';

			$order_items	= empty( $order_item_grouped[ $order['id'] ] ) ? array() : $order_item_grouped[ $order['id'] ];

			$items_result = array();

			foreach($order_items as $order_item)
			{
				$item				= $item_array[ $order_item['item_id'] ];
				$category			= $category_array[ $item['category_id'] ];
				$io_array			= $grouped_item_option_array[ $order_item['item_id'] ]??array();

				$item_option_info_array	= array();

				foreach($io_array as $item_option)
				{

					$iov	= $grouped_item_option_value_array[ $item_option['id'] ]??array();
					$item_option_value_info_array = array();

					foreach($iov as $item_option_value)
					{
						$item_option_value_info_array = array
						(
							'item_option_value'	=>	$item_option_value,
							'item'				=> $items_from_values_array[ $item_option_value['item_id'] ],
						);
					}

					$item_option_info_array[] = array
					(
						'item_option'	=>	$item_option,
						'values'		=>	$item_option_value_info_array
					);
				}

				$items_result[]=array
				(
					'order_item'=> $order_item,
					'item'		=> $item,
					'category'	=> $category,
					'options'	=> $item_option_info_array,
				);
			}

			$order_info	= array
			(
				'client'	=> $client_user,
				'cashier'	=> $cashier_user,
				'items'		=> $items_result,
				'order'		=> $order,
				'store'		=> $store_array[ $order['store_id'] ]
			);

			if( $order['shipping_address_id'] )
				$order_info['shipping_address'] = $address_array[ $order['shipping_address_id'] ];

			if( $order['billing_address_id'] )
				$order_info['billing_address'] = $address_array[ $order['billing_address_id'] ];

			$result[] = $order_info;
		}

		return $result;
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
			$info 		= $this->batchInsert( $is_assoc  ? array($params) : $params );
			$result		= $this->getInfo($info);
			DBTable::commit();
			return $this->sendStatus( 200 )->json( $is_assoc ? $result[0] : $result );
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

	function put()
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
			$info		= $this->batchUpdate( $is_assoc  ? array($params) : $params );
			$result		= $this->getInfo($info);
			DBTable::commit();
			return $this->sendStatus( 200 )->json( $is_assoc ? $result[0] : $result );
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


	function batchUpdate($array)
	{
		$props = order::getAllPropertiesExcept('store_id','delivery_status','paid_status','total','subtotal','tax','amount_paid','created_by_user_id','updated_by_user_id','created','updated');
		$this->debug('Props',$props);

		$results = array();

		foreach($array as $order_data )
		{
			$order = order::get( $order_data['order']['id'] );
			$order->assignFromArray($order_data['order'],$props);

			if( !$order->update( $props ) )
			{
				throw new SystemException('Ocurrio un error por favor intetar mas tarde. '.$order->getError());
			}

			$order_item_array = array();

			foreach($order_data['items'] as $order_item_values )
			{
				$order_item_values['order_item']['order_id'] = $order->id;
				$order_item_array[] = app::saveOrderItem( $order_item_values['order_item'] )->toArray();
			}

			$order_items_ids = ArrayUtils::getItemsProperty($order_item_array,'id');

			//Borramos todos los que no estan
			if( !empty( $order_item_array ) )
			{
				DBTable::query('UPDATE order_item SET status = "DELETED" WHERE order_id = "'.$order->id.'" AND id NOT IN ('.DBTable::escapeArrayValues( $order_items_ids ).')');
			}

			app::updateOrderTotal( $order->id );
			$results[] = $order->toArray();
		}
		return $results;
	}

	function batchInsert($array)
	{
		$user = app::getUserFromSession();

		$optional_values	=array( 'tota'=>0,'subtotal' =>0 ,'tax'=>0 );
		$system_values		=array( 'cashier_user_id' => $user->id );
		$new_result = array();

		$order_properties= order::getAllPropertiesExcept(array('id','created','updated','tiempo_creacion','tiempo_actualizacion','updated_by_user_id','created_by_user_id'));

		foreach( $array as $order_values )
		{
			$order = new order();
			$order->assignFromArray( $optional_values );
			$order->assignFromArray($order_values['order'], $order_properties);
			$order->assignFromArray($system_values);
			$order->unsetEmptyValues( DBTable::UNSET_BLANKS );

			$this->is_debug = true;

			if( $user )
			{
				$user_array = array('updated_by_user_id'=>$user->id,'created_by_user_id'=>$user->id);
				$order->assignFromArray( $user_array );
			}

			if( !$order->insert() )
			{

				$this->debug('fallo',$order->toArray());
				throw new SystemException('Ocurrio un error por favor intente mas tarde. '.$order->getError() );
			}

			foreach($order_values['items'] as $order_item_values )
			{
				$order_item_values['order_item']['order_id'] = $order->id;
				app::saveOrderItem( $order_item_values['order_item'] )->toArray();
			}

			app::updateOrderTotal( $order->id );
			$order->load(true);

			$new_result[] = $order->toArray();
		}

		return $new_result;
	}
}

$l = new Service();
$l->execute();
