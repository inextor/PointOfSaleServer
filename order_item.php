<?php
namespace APP;

include_once( __DIR__.'/app.php' );
include_once( __DIR__.'/akou/src/ArrayUtils.php');
include_once( __DIR__.'/SuperRest.php');

use \akou\DBTable;
use \akou\ValidationException;
use \akou\LoggableException;
use AKOU\SystemException;

class Service extends SuperRest
{
	function get()
	{
		session_start();
		App::connect();
		$this->setAllowHeader();
		$this->is_debug = true;

		return $this->genericGet("order_item");
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

			error_log('GET is '.print_r( $params, true ) );

			$is_assoc	= $this->isAssociativeArray( $params );
			$result		= $this->batchInsert( $is_assoc  ? array($params) : $params );

			if( !DBTable::commit() )
			{
				error_log('Ocurrio error en comit');
			}
			else
			{
				error_log('COmmit exitoso');
			}

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

	function batchInsert($order_item_array)
	{
		$results = array();

		foreach($order_item_array as $oi )
		{
			$results[] = app::saveOrderItem($oi)->toArray();
		}
		return $results;
	}
}
$l = new Service();
$l->execute();
