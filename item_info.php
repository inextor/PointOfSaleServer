<?php
namespace APP;

include_once( __DIR__.'/app.php' );
include_once( __DIR__.'/akou/src/ArrayUtils.php');
include_once( __DIR__.'/SuperRest.php');

use \akou\ArrayUtils;
use \akou\DBTable;
use AKOU\SystemException;
use AKOU\ValidationException;
use AKOU\LoggableException;

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

		$item_extra_array	= item_extra::searchGroupByIndex(array('item_id'=>$item_props['id']),false,'item_id');
		$item_option_array	= item_option::searchGroupByIndex(array('item_id'=>$item_props['id']),false,'item_id');
		$item_attributes_array	= item_attribute::searchGroupByIndex(array('item_id'=>$item_props['id']),false,'item_id');

		$result = array();
		$price_array	= array();

		$stock_record_array = array();

		if( !empty( $item_array ) )
		{
			$price_array = price::searchGroupByIndex(array('item_id'=>$item_props['id']),false,'item_id');
			//$this->debug('price_array',$price_array);
			//error_log('NO ENTRA');
			$stock_sql 	= 'SELECT MAX(id) AS max_id,store_id,item_id
				FROM stock_record
				WHERE item_id IN ('.DBTable::escapeArrayValues( $item_props['id']).')
				GROUP BY item_id, store_id';

			$ids_array	= DBTable::getArrayFromQuery($stock_sql, 'max_id');
			$stock_record_array = stock_record::searchGroupByIndex(array('id'=>array_keys($ids_array)),false,'item_id');
		}

		foreach($item_array as $item)
		{
			$category = $category_array[ $item['category_id'] ];
			$stock_records	= isset( $stock_record_array[$item['id']] ) ? $stock_record_array[$item['id']] : array();

			$prices		= isset( $price_array[ $item['id'] ] ) ? $price_array[ $item['id'] ] : array();
			$attributes	= isset( $item_attributes_array[ $item['id'] ] )? $item_attributes_array[ $item['id'] ] : array();
			$extras		= isset( $item_extra_array[ $item['id'] ] )? $item_extra_array[ $item['id'] ] : array();
			$options	= isset( $item_option_array[ $item['id'] ] )? $item_option_array[ $item['id'] ] : array();

			$result[] = array(
				'item'=>$item,
				'category'=>$category,
				'records'=>$stock_records,
				'prices'=> $prices,
				'options'=> $options,
				'extras'=> $extras,
				'attributes'=> $attributes
			);
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
			$result		= $this->batchInsert( $is_assoc  ? array($params) : $params );
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
		catch(\Exception $e)
		{
			DBTable::rollback();
			return $this->sendStatus( 500 )->json(array("error"=>$e->getMessage()));
		}

	}

	function batchInsert($array)
	{
		$result = array();
		foreach($array as $params )
		{
			$item = new item();
			$item->assignFromArray($params['item']);

			if( !$item->insert() )
			{
				throw new SystemException('Ocurrio un error por favor intentar mas tarde');
			}

			$this->updateAttributes($item, $params['attributes']);
			$this->updateItemExtra($item, $params['extras']);
			$this->updateOptions( $item, $params['options']);

			$result[] = $item->toArray();
		}
		return $result;
	}

	function batchUpdate($array)
	{
		$result = array();
		foreach($array as $params )
		{
			$item = item::get( $params['item']['id'] );
			if( $item == null )
				throw new SystemException('Ocurrio un error por favor intentar mas tarde. ');

			$item->assignFromArray($params['item']);

			if( !$item->update() )
			{
				throw new SystemException('Ocurrio un error por favor intentar mas tarde');
			}

			$this->updateAttributes($item, $params['attributes']);
			$this->updateItemExtra($item, $params['extras']);
			$this->updateOptions( $item, $params['options']);

			$result[] = $item->toArray();
		}
		return $result;
	}

	function updateItemExtra($item, $item_extra_params )
	{
		if( empty( $item_extras_params )  )
			return array();

		foreach($item_extra_params as $params )
		{
			$item_extra = new item_extra();
			$item_extra->assignFromArray($params);
			$item_extra->item_id = $item->id;

			if( $item_extra->id  )
			{
				if( $item_extra->item_id == $item->id )
				{
					throw new ValidationException('El id del item no corresponde');
				}
				if( !  $item_extra->updateDb() )
				{
					throw new ValidationException('Ocurrio un error por favor intentar mas tarde');
				}
			}
			else if( ! $item_extra->insertDb()  )
			{
				throw new ValidationException('Ocurrio un error por favor intentar mas tarde');
			}
		}
	}

	function updateOptions($item, $item_option_params )
	{
		if( empty( $item_options_params )  )
			return array();

		foreach($item_option_params as $params)
		{
			$item_option = new item_option();
			$item_option->assignFromArray($params);
			$item_option->item_id = $item->id;

			if( $item_option->id  )
			{
				if( $item_option->item_id == $item->id )
				{
					throw new ValidationException('El id del item no corresponde');
				}
				if( !  $item_option->updateDb() )
				{
					throw new ValidationException('Ocurrio un error por favor intentar mas tarde');
				}
			}
			else if( ! $item_option->insertDb()  )
			{
				throw new ValidationException('Ocurrio un error por favor intentar mas tarde');
			}
		}
	}

	function updateAttributes($item, $item_attributes_params)
	{
		if( empty( $item_attributes_params )  )
			return array();

		foreach($item_attributes_params as $params )
		{
			$item_attribute = new item_attribute();
			$item_attribute->assignFromArray($params);
			$item_attribute->item_id = $item->id;

			if( $item_attribute->id  )
			{
				if( $item_attribute->item_id == $item->id )
				{
					throw new ValidationException('El id del item no corresponde');
				}
				if( !  $item_attribute->updateDb() )
				{
					throw new ValidationException('Ocurrio un error por favor intentar mas tarde');
				}
			}
			else if( ! $item_attribute->insertDb()  )
			{
				throw new ValidationException('Ocurrio un error por favor intentar mas tarde');
			}
		}
	}
}
$l = new Service();
$l->execute();
