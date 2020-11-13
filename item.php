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
			$item = item::get( $_GET['id']  );

			if( $item )
			{
				$info = $this->getInfo( array( $item->toArray() ));

				return $this->sendStatus( 200 )->json( $info[0] );
			}
			return $this->sendStatus( 404 )->json(array('error'=>'The element wasn\'t found'));
		}


		$constraints = $this->getAllConstraints( item::getAllProperties() );

		$constraints_str = count( $constraints ) > 0 ? join(' AND ',$constraints ) : '1';
		$pagination	= $this->getPagination();

		$sql_items	= 'SELECT SQL_CALC_FOUND_ROWS item.*
			FROM `item`
			WHERE '.$constraints_str.'
			LIMIT '.$pagination->limit.'
			OFFSET '.$pagination->offset;
		$result = DBTable::getArrayFromQuery( $sql_items );
		$total	= DBTable::getTotalRows();
		$info = $this->getInfo( $result );
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
			$properties = item::getAllPropertiesExcept('created','updated','id','tiempo_actualizacion','tiempo_creacion');

			$item = new item();
			$item->assignFromArray( $params['item'], $properties );
			$item->unsetEmptyValues( DBTable::UNSET_BLANKS );

			if( !$item->insert() )
			{
					throw new ValidationException('An error Ocurred please try again later',$item->_conn->error );
			}

			$results [] = $item->toArray();
		}

		return $results;
	}

	function batchUpdate($array)
	{
		$results = array();
		$insert_with_ids = false;

		foreach($array as $index=>$params )
		{
			$properties = item::getAllPropertiesExcept('created','updated','tiempo_actualizacion','tiempo_creacion');

			$item = item::createFromArray( $params['item'] );

			if( $insert_with_ids )
			{
				if( !empty( $item->id ) )
				{
					if( $item->load(true) )
					{
						$item->assignFromArray( $params, $properties );
						$item->unsetEmptyValues( DBTable::UNSET_BLANKS );

						if( !$item->update($properties) )
						{
							throw new ValidationException('It fails to update element #'.$item->id);
						}
					}
					else
					{
						if( !$item->insertDb() )
						{
							throw new ValidationException('It fails to update element at index #'.$index);
						}
					}
				}
			}
			else
			{
				if( !empty( $item->id ) )
				{
					$item->setWhereString( true );

					$properties = item::getAllPropertiesExcept('id','created','updated','tiempo_creacion','tiempo_actualizacion');
					$item->unsetEmptyValues( DBTable::UNSET_BLANKS );

					if( !$item->updateDb( $properties ) )
					{
						throw new ValidationException('An error Ocurred please try again later',$item->_conn->error );
					}

					$item->load(true);

					$results [] = $item->toArray();
				}
				else
				{
					$item->unsetEmptyValues( DBTable::UNSET_BLANKS );
					if( !$item->insert() )
					{
						throw new ValidationException('An error Ocurred please try again later',$item->_conn->error );
					}

					$results [] = $item->toArray();
				}
			}
		}

		return $results;
	}

	function getInfo($item_array)
	{
		$result = array();
		$props	= ArrayUtils::getItemsProperties($item_array,'id');

		$price_array	= array();

		if(!empty( $_GET['store_id'] ) )
			$price_array = price::searchGroupByIndex(array('store_id'=>$_GET['store_id'],'item_id'=>$props['id']),false,'item_id');

		foreach( $item_array as $item)
		{
			$result[] = array(
				'item'=> $item,
				'prices'=> isset( $price_array[ $item['id'] ] ) ? $price_array[ $item['id'] ] : []
			);
		}
		return $result;
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
				$item = new item();
				$item->id = $_GET['id'];

				if( !$item->load(true) )
				{
					throw new NotFoundException('The element was not found');
				}

				if( !$item->deleteDb() )
				{
					throw new SystemException('An error occourred, please try again later');
				}

			}
			DBTable::commit();
			return $this->sendStatus( 200 )->json( $item->toArray() );
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
