<?php
namespace APP;

include_once( __DIR__.'/app.php' );
include_once( __DIR__.'/akou/src/ArrayUtils.php');
include_once( __DIR__.'/SuperRest.php');

use \akou\ArrayUtils;
use \akou\DBTable;


class Service extends SuperRest
{
	function get()
	{
		session_start();
		App::connect();
		$this->setAllowHeader();
		$this->is_debug = true;
		$extra_join = '';
		$extra_sort = array();

		$constraints = array();

		if( !empty( $_GET['category_name'] ) )
		{
			$escaped_name = DBTable::escape( trim( $_GET['category_name'] ) );
			$constraints[] = 'category.name LIKE "'.$escaped_name.'%" OR item.name LIKE "'.$escaped_name.'%" ';
		}



		if( !empty( $_GET['category_type'] ) || !empty( $_GET['category_name']) )
		{
			$extra_join = 'JOIN category ON category.id = item.category_id ' ;

			if( !empty( $_GET['category_type'])  )
				$extra_join.=' AND category.type = "'.$_GET['category_type'].'"';

			$extra_sort = array('category.name');
		}

		return $this->genericGet("item",$constraints,$extra_join,$extra_sort);
	}

	function getInfo($item_array)
	{
		$item_props= ArrayUtils::getItemsProperties($item_array, 'id','category_id');
		$category_array = category::search(array('id'=>$item_props['category_id']),false,'id');
		$result = array();
		$price_array	= array();

		$stock_record_array = array();

		if( !empty( $item_array ) )
		{
			$price_array = price::searchGroupByIndex(array('item_id'=>$item_props['id']),false,'item_id');
			//$this->debug('price_array',$price_array);
			//error_log('NO ENTRA');
			$stock_sql 	= 'SELECT MAX(id) AS max_id,store_id,item_id FROM stock_record WHERE item_id IN ('.DBTable::escapeArrayValues( $item_props['id']).') GROUP BY item_id, store_id';
			$ids_array	= DBTable::getArrayFromQuery($stock_sql, 'max_id');
			$stock_record_array = stock_record::searchGroupByIndex(array('id'=>array_keys($ids_array)),false,'item_id');
		}

		foreach($item_array as $item)
		{
			$category = $category_array[ $item['category_id'] ];
			$stock_records	= isset( $stock_record_array[$item['id']] ) ? $stock_record_array[$item['id']] : array();
			$prices		= isset( $price_array[ $item['id'] ] ) ? $price_array[ $item['id'] ] : array();

			$result[] = array(
				'item'=>$item,
				'category'=>$category,
				'records'=>$stock_records,
				'prices'=> $prices
			);
		}

		return $result;
	}
}
$l = new Service();
$l->execute();
