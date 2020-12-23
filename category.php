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
			$category = category::get( $_GET['id']  );

			if( $category )
			{
				return $this->sendStatus( 200 )->json( $category->toArray() );
			}
			return $this->sendStatus( 404 )->json(array('error'=>'The element wasn\'t found'));
		}


		$constraints = $this->getAllConstraints( category::getAllProperties() );

		$constraints_str = count( $constraints ) > 0 ? join(' AND ',$constraints ) : '1';
		$pagination	= $this->getPagination();

		$sql_categorys	= 'SELECT SQL_CALC_FOUND_ROWS category.*
			FROM `category`
			WHERE '.$constraints_str.'
			LIMIT '.$pagination->limit.'
			OFFSET '.$pagination->offset;
		$info	= DBTable::getArrayFromQuery( $sql_categorys );
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
			$properties = category::getAllPropertiesExcept('created','updated','id','tiempo_actualizacion','tiempo_creacion');

			$category = new category();
			$category->assignFromArray( $params, $properties );
			$category->unsetEmptyValues( DBTable::UNSET_BLANKS );

			if( !$category->insert() )
			{
					throw new ValidationException('An error Ocurred please try again later',$category->_conn->error );
			}

			$results [] = $category->toArray();
		}

		return $results;
	}

	function batchUpdate($array)
	{
		$results = array();
		$insert_with_ids = false;

		foreach($array as $index=>$params )
		{
			$properties = category::getAllPropertiesExcept('created','updated','tiempo_actualizacion','tiempo_creacion');

			$category = category::createFromArray( $params );

			if( $insert_with_ids )
			{
				if( !empty( $category->id ) )
				{
					if( $category->load(true) )
					{
						$category->assignFromArray( $params, $properties );
						$category->unsetEmptyValues( DBTable::UNSET_BLANKS );

						if( !$category->update($properties) )
						{
							throw new ValidationException('It fails to update element #'.$category->id);
						}
					}
					else
					{
						if( !$category->insertDb() )
						{
							throw new ValidationException('It fails to update element at index #'.$index);
						}
					}
				}
			}
			else
			{
				if( !empty( $category->id ) )
				{
					$category->setWhereString( true );

					$properties = category::getAllPropertiesExcept('id','created','updated','tiempo_creacion','tiempo_actualizacion');
					$category->unsetEmptyValues( DBTable::UNSET_BLANKS );

					if( !$category->updateDb( $properties ) )
					{
						throw new ValidationException('An error Ocurred please try again later',$category->_conn->error );
					}

					$category->load(true);

					$results [] = $category->toArray();
				}
				else
				{
					$category->unsetEmptyValues( DBTable::UNSET_BLANKS );
					if( !$category->insert() )
					{
						throw new ValidationException('An error Ocurred please try again later',$category->_conn->error );
					}

					$results [] = $category->toArray();
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
				$category = new category();
				$category->id = $_GET['id'];

				if( !$category->load(true) )
				{
					throw new NotFoundException('The element was not found');
				}

				if( !$category->deleteDb() )
				{
					throw new SystemException('An error occourred, please try again later');
				}

			}
			DBTable::commit();
			return $this->sendStatus( 200 )->json( $category->toArray() );
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
