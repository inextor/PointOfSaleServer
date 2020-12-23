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
			$brand = brand::get( $_GET['id']  );

			if( $brand )
			{
				return $this->sendStatus( 200 )->json( $brand->toArray() );
			}
			return $this->sendStatus( 404 )->json(array('error'=>'The element wasn\'t found'));
		}


		$constraints = $this->getAllConstraints( brand::getAllProperties() );

		$constraints_str = count( $constraints ) > 0 ? join(' AND ',$constraints ) : '1';
		$pagination	= $this->getPagination();

		$sql_brands	= 'SELECT SQL_CALC_FOUND_ROWS brand.*
			FROM `brand`
			WHERE '.$constraints_str.'
			LIMIT '.$pagination->limit.'
			OFFSET '.$pagination->offset;
		$info	= DBTable::getArrayFromQuery( $sql_brands );
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
			$properties = brand::getAllPropertiesExcept('created','updated','id','tiempo_actualizacion','tiempo_creacion');

			$brand = new brand();
			$brand->assignFromArray( $params, $properties );
			$brand->unsetEmptyValues( DBTable::UNSET_BLANKS );

			if( !$brand->insert() )
			{
					throw new ValidationException('An error Ocurred please try again later',$brand->_conn->error );
			}

			$results [] = $brand->toArray();
		}

		return $results;
	}

	function batchUpdate($array)
	{
		$results = array();
		$insert_with_ids = false;

		foreach($array as $index=>$params )
		{
			$properties = brand::getAllPropertiesExcept('created','updated','tiempo_actualizacion','tiempo_creacion');

			$brand = brand::createFromArray( $params );

			if( $insert_with_ids )
			{
				if( !empty( $brand->id ) )
				{
					if( $brand->load(true) )
					{
						$brand->assignFromArray( $params, $properties );
						$brand->unsetEmptyValues( DBTable::UNSET_BLANKS );

						if( !$brand->update($properties) )
						{
							throw new ValidationException('It fails to update element #'.$brand->id);
						}
					}
					else
					{
						if( !$brand->insertDb() )
						{
							throw new ValidationException('It fails to update element at index #'.$index);
						}
					}
				}
			}
			else
			{
				if( !empty( $brand->id ) )
				{
					$brand->setWhereString( true );

					$properties = brand::getAllPropertiesExcept('id','created','updated','tiempo_creacion','tiempo_actualizacion');
					$brand->unsetEmptyValues( DBTable::UNSET_BLANKS );

					if( !$brand->updateDb( $properties ) )
					{
						throw new ValidationException('An error Ocurred please try again later',$brand->_conn->error );
					}

					$brand->load(true);

					$results [] = $brand->toArray();
				}
				else
				{
					$brand->unsetEmptyValues( DBTable::UNSET_BLANKS );
					if( !$brand->insert() )
					{
						throw new ValidationException('An error Ocurred please try again later',$brand->_conn->error );
					}

					$results [] = $brand->toArray();
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
				$brand = new brand();
				$brand->id = $_GET['id'];

				if( !$brand->load(true) )
				{
					throw new NotFoundException('The element was not found');
				}

				if( !$brand->deleteDb() )
				{
					throw new SystemException('An error occourred, please try again later');
				}

			}
			DBTable::commit();
			return $this->sendStatus( 200 )->json( $brand->toArray() );
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
