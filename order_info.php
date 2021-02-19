<?php
namespace APP;

include_once( __DIR__.'/app.php' );
include_once( __DIR__.'/akou/src/ArrayUtils.php');
include_once( __DIR__.'/SuperRest.php');

use \akou\ArrayUtils;


class Service extends SuperRest
{
	function get()
	{
		session_start();
		App::connect();
		$this->setAllowHeader();

		return $this->genericGet("order");
	}

	function getInfo($order_array)
	{
		$order_props		= ArrayUtils::getItemsProperties($order_array,'id','store_id','client_user_id','cashier_user_id');
		$user_ids			= array_merge($order_props['client_user_id'],$order_props['cashier_user_id']);
		$user_array			= user::search(array('id'=>$user_ids), false, 'id');

		$store_array		= store::search(array('id'=>$order_props['store_id']), false, 'id');
		$order_item_array	= order_item::search(array('order_id'=>$order_props['id']), false, 'id');

		$order_item_props	= ArrayUtils::getItemsProperties($order_item_array, 'id','item_id' );

		$item_array			= item::search(array('id'=>$order_item_props['item_id']),false,'id');

		$category_ids		= ArrayUtils::getItemsProperty($item_array, 'category_id' );
		$category_array		= category::search(array('id'=>$category_ids),false,'id');

		$result = array();
		$grouped_items		= ArrayUtils::groupByProperty( $order_item_array, 'order_id' );

		#$serial_number_record_array = serial_number_record::searchGroupByIndex(array('order_item_id'=>$order_item_props['id']),false,'order_item_id');

		//$this->debug('snra',$serial_number_record_array);

		foreach( $order_array as $order )
		{
			$client_user			= empty( $order['client_user_id'] ) ? null : $user_array[$order['client_user_id']];
			$cashier_user  	= empty( $order['cashier_user_id'] ) ? null : $user_array[$order['cashier_user_id']];

			if( $client_user )
				$client_user['password'] = '';

			$cashier_user['password'] = '';

			$order_items	= empty( $grouped_items[ $order['id'] ] ) ? array() : $grouped_items[ $order['id'] ];

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


			$result[] = array
			(
				'client'			=> $client_user,
				'cashier'	=> $cashier_user,
				'items'			=> $items_result,
				'order'			=> $order,
				'store'			=> $store_array[ $order['store_id'] ]
			);
		}

		return $result;
	}
}
$l = new Service();
$l->execute();
