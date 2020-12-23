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
			$provider = provider::get( $_GET['id']  );

			if( $provider )
			{
				return $this->sendStatus( 200 )->json( $provider->toArray() );
			}
			return $this->sendStatus( 404 )->json(array('error'=>'The element wasn\'t found'));
		}


		$constraints = $this->getAllConstraints( provider::getAllProperties() );

		$constraints_str = count( $constraints ) > 0 ? join(' AND ',$constraints ) : '1';
		$pagination	= $this->getPagination();

		$sql_providers	= 'SELECT SQL_CALC_FOUND_ROWS provider.*
			FROM `provider`
			WHERE '.$constraints_str.'
			LIMIT '.$pagination->limit.'
			OFFSET '.$pagination->offset;
		$info	= DBTable::getArrayFromQuery( $sql_providers );
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
			$properties = provider::getAllPropertiesExcept('created','updated','id','tiempo_actualizacion','tiempo_creacion');

			$provider = new provider();
			$provider->assignFromArray( $params, $properties );
			$provider->unsetEmptyValues( DBTable::UNSET_BLANKS );

			if( !$provider->insert() )
			{
					throw new ValidationException('An error Ocurred please try again later',$provider->_conn->error );
			}

			$results [] = $provider->toArray();
		}

		return $results;
	}

	function batchUpdate($array)
	{
		$results = array();
		$insert_with_ids = false;

		foreach($array as $index=>$params )
		{
			$properties = provider::getAllPropertiesExcept('created','updated','tiempo_actualizacion','tiempo_creacion');

			$provider = provider::createFromArray( $params );

			if( $insert_with_ids )
			{
				if( !empty( $provider->id ) )
				{
					if( $provider->load(true) )
					{
						$provider->assignFromArray( $params, $properties );
						$provider->unsetEmptyValues( DBTable::UNSET_BLANKS );

						if( !$provider->update($properties) )
						{
							throw new ValidationException('It fails to update element #'.$provider->id);
						}
					}
					else
					{
						if( !$provider->insertDb() )
						{
							throw new ValidationException('It fails to update element at index #'.$index);
						}
					}
				}
			}
			else
			{
				if( !empty( $provider->id ) )
				{
					$provider->setWhereString( true );

					$properties = provider::getAllPropertiesExcept('id','created','updated','tiempo_creacion','tiempo_actualizacion');
					$provider->unsetEmptyValues( DBTable::UNSET_BLANKS );

					if( !$provider->updateDb( $properties ) )
					{
						throw new ValidationException('An error Ocurred please try again later',$provider->_conn->error );
					}

					$provider->load(true);

					$results [] = $provider->toArray();
				}
				else
				{
					$provider->unsetEmptyValues( DBTable::UNSET_BLANKS );
					if( !$provider->insert() )
					{
						throw new ValidationException('An error Ocurred please try again later',$provider->_conn->error );
					}

					$results [] = $provider->toArray();
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
				$provider = new provider();
				$provider->id = $_GET['id'];

				if( !$provider->load(true) )
				{
					throw new NotFoundException('The element was not found');
				}

				if( !$provider->deleteDb() )
				{
					throw new SystemException('An error occourred, please try again later');
				}

			}
			DBTable::commit();
			return $this->sendStatus( 200 )->json( $provider->toArray() );
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
