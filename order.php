<?php
namespace POINT_OF_SALE;

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

class Service extends SuperRest
{
	function get()
	{
		session_start();
		App::connect();
		$this->setAllowHeader();

		if( isset( $_GET['id'] ) && !empty( $_GET['id'] ) )
		{
			$order = order::get( $_GET['id']  );

			if( $order )
			{
				return $this->sendStatus( 200 )->json( $order->toArray() );
			}
			return $this->sendStatus( 404 )->json(array('error'=>'The element wasn\'t found'));
		}


		$constraints = $this->getAllConstraints( order::getAllProperties() );

		$constraints_str = count( $constraints ) > 0 ? join(' AND ',$constraints ) : '1';
		$pagination	= $this->getPagination();

		$sql_orders	= 'SELECT SQL_CALC_FOUND_ROWS order.*
			FROM `order`
			WHERE '.$constraints_str.'
			LIMIT '.$pagination->limit.'
			OFFSET '.$pagination->offset;
		$info	= DBTable::getArrayFromQuery( $sql_orders );
		$total	= DBTable::getTotalRows();
		return $this->sendStatus( 200 )->json(array("total"=>$total,"data"=>$info));
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
}

$l = new Service();
$l->execute();
