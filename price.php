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


class Service extends SuperRest
{
	function get()
	{
		session_start();
		App::connect();
		$this->setAllowHeader();

		if( isset( $_GET['id'] ) && !empty( $_GET['id'] ) )
		{
			$price = price::get( $_GET['id']  );

			if( $price )
			{
				return $this->sendStatus( 200 )->json( $price->toArray() );
			}
			return $this->sendStatus( 404 )->json(array('error'=>'The element wasn\'t found'));
		}

		$constraints = $this->getAllConstraints( price::getAllProperties() );

		$constraints_str = count( $constraints ) > 0 ? join(' AND ',$constraints ) : '1';
		$pagination	= $this->getPagination();

		$sql_prices	= 'SELECT SQL_CALC_FOUND_ROWS price.*
			FROM `price`
			WHERE '.$constraints_str.'
			LIMIT '.$pagination->limit.'
			OFFSET '.$pagination->offset;
		$info	= DBTable::getArrayFromQuery( $sql_prices );
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


	function batchInsert($array)
	{
		$results = array();

		foreach($array as $params )
		{
			$properties = price::getAllPropertiesExcept('created','updated','id','tiempo_actualizacion','tiempo_creacion');

			$price = new price();
			$price->store_id = $params['store_id'];
			$price->item_id = $params['item_id'];
			$price->price_type_id = $params['price_type_id'];
			$price->setWhereString(false);

			$user = app::getUserFromSession();

			if( !$price->load() )
			{
				$price->assignFromArray($params, $properties );
				$price->created_by_user_id = $user->id;

				if( !$price->insert() )
				{
					throw new ValidationException('An error Ocurred please try again later',$price->_conn->error );
				}
			}
			else
			{
				$price->setWhereString(true);
				$price->assignFromArray($params, $properties );
				$price->updated_by_user_id = $user->id;

				if( !$price->update( $properties ) )
				{
					throw new ValidationException('An error Ocurred please try again later',$price->_conn->error );
				}
			}

			$results [] = $price->toArray();
		}

		return $results;
	}

	/*
	function delete()
	{
		try
		{
			app::connect();
			DBTable::autocommit( false );

			$user = app::getUserFromSession();
			if( $user == null )
				throw new ValidationException('Please login');

			if( empty( $_GET['id'] ) )
			{
				$price = new price();
				$price->id = $_GET['id'];

				if( !$price->load(true) )
				{
					throw new NotFoundException('The element was not found');
				}

				if( !$price->deleteDb() )
				{
					throw new SystemException('An error occourred, please try again later');
				}

			}
			DBTable::commit();
			return $this->sendStatus( 200 )->json( $price->toArray() );
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
	*/
}
$l = new Service();
$l->execute();
