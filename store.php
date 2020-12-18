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
			$store = store::get( $_GET['id']  );

			if( $store )
			{
				return $this->sendStatus( 200 )->json( $store->toArray() );
			}
			return $this->sendStatus( 404 )->json(array('error'=>'The element wasn\'t found'));
		}


		$constraints = $this->getAllConstraints( store::getAllProperties() );

		$constraints_str = count( $constraints ) > 0 ? join(' AND ',$constraints ) : '1';
		$pagination	= $this->getPagination();

		$sql_stores	= 'SELECT SQL_CALC_FOUND_ROWS store.*
			FROM `store`
			WHERE '.$constraints_str.'
			LIMIT '.$pagination->limit.'
			OFFSET '.$pagination->offset;
		$info	= DBTable::getArrayFromQuery( $sql_stores );
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
			$properties = store::getAllPropertiesExcept('created','updated','id','tiempo_actualizacion','tiempo_creacion');

			$store = new store();
			$store->assignFromArray( $params, $properties );
			$store->unsetEmptyValues( DBTable::UNSET_BLANKS );

			if( !$store->insert() )
			{
					throw new ValidationException('An error Ocurred please try again later',$store->_conn->error );
			}

			$results [] = $store->toArray();
		}

		return $results;
	}

	function batchUpdate($array)
	{
		$results = array();
		$insert_with_ids = false;

		foreach($array as $index=>$params )
		{
			$properties = store::getAllPropertiesExcept('created','updated','tiempo_actualizacion','tiempo_creacion');

			$store = store::createFromArray( $params );

			if( $insert_with_ids )
			{
				if( !empty( $store->id ) )
				{
					if( $store->load(true) )
					{
						$store->assignFromArray( $params, $properties );
						$store->unsetEmptyValues( DBTable::UNSET_BLANKS );

						if( !$store->update($properties) )
						{
							throw new ValidationException('It fails to update element #'.$store->id);
						}
					}
					else
					{
						if( !$store->insertDb() )
						{
							throw new ValidationException('It fails to update element at index #'.$index);
						}
					}
				}
			}
			else
			{
				if( !empty( $store->id ) )
				{
					$store->setWhereString( true );

					$properties = store::getAllPropertiesExcept('id','created','updated','tiempo_creacion','tiempo_actualizacion');
					$store->unsetEmptyValues( DBTable::UNSET_BLANKS );

					if( !$store->updateDb( $properties ) )
					{
						throw new ValidationException('An error Ocurred please try again later',$store->_conn->error );
					}

					$store->load(true);

					$results [] = $store->toArray();
				}
				else
				{
					$store->unsetEmptyValues( DBTable::UNSET_BLANKS );
					if( !$store->insert() )
					{
						throw new ValidationException('An error Ocurred please try again later',$store->_conn->error );
					}

					$results [] = $store->toArray();
				}
			}
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
				$store = new store();
				$store->id = $_GET['id'];

				if( !$store->load(true) )
				{
					throw new NotFoundException('The element was not found');
				}

				if( !$store->deleteDb() )
				{
					throw new SystemException('An error occourred, please try again later');
				}

			}
			DBTable::commit();
			return $this->sendStatus( 200 )->json( $store->toArray() );
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
