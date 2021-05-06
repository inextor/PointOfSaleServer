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

	function getInfo($order_array)
	{
		$order_props		= ArrayUtils::getItemsProperties($order_array,'id','store_id','client_user_id','cashier_user_id','shipping_address_id','billing_address_id');

		$store_array		= store::search(array('id'=>$order_props['store_id']), false, 'id');
		$user_ids			= array_merge($order_props['client_user_id'],$order_props['cashier_user_id']);
		$user_array			= user::search(array('id'=>$user_ids), false, 'id');
		$order_item_array	= order_item::search(array('order_id'=>$order_props['id']), false, 'id');
		$order_item_props	= ArrayUtils::getItemsProperties($order_item_array, 'id','item_id' );

		$item_array			= item::search(array('id'=>$order_item_props['item_id']),false,'id');

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

			foreach($order_items as $order_item )
			{
				$item			= $item_array[ $order_item['item_id'] ];
				$category		= $category_array[ $item['category_id'] ];
				//$records		= isset( $serial_number_record_array[$order_item['id'] ] ) ? $serial_number_record_array[$order_item['id'] ] : array();

				$items_result[]=array
				(
					'order_item'=>$order_item,
					'item'=>$item,
					'category'=>$category
				//	'records'=>$records
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
}

$l = new Service();
$l->execute();
