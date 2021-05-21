<?php
namespace APP;

include_once( __DIR__.'/app.php' );
include_once( __DIR__.'/akou/src/ArrayUtils.php');
include_once( __DIR__.'/SuperRest.php');

use \akou\DBTable;
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

		$extra_constraints=array();
		$extra_joins = '';
		$extra_sort = array();
		$this->is_debug = true;
		return $this->genericGet("cash_close",$extra_constraints,$extra_joins,$extra_sort);
	}

	function getInfo($cash_close_array)
	{

		$result = array();

		foreach($cash_close_array as $cash_close_value )
		{
			$result[] = $this->getCashCloseInfo( $cash_close_value );
		}

		return $result;
	}

	function getCashCloseInfo($cash_close_value)
	{
		$cash_close			= cash_close::get( $cash_close_value['id'] );

		$bm_search			= array
		(
			'received_by_user_id'			=> $cash_close->created_by_user_id,
			'created'.DBTable::GE_SYMBOL	=> $cash_close->since,
			'created'.DBTable::LE_SYMBOL	=> $cash_close->created
		);


		$funds_search		= array(
			'created_by_user_id'			=> $cash_close->created_by_user_id,
			'created'.DBTable::GE_SYMBOL	=> $cash_close->since,
			'created'.DBTable::LE_SYMBOL	=> $cash_close->created
		);

		$bank_movement_array	= bank_movement::search( $bm_search );
		$funds					= fund::search( $funds_search );
		$payment_array			= payment::search( $funds_search );

		error_log('PaymentSql'.payment::getSearchSql($funds_search) );

		$user					= user::get( $cash_close->created_by_user_id );
		$store					= null;

		if( $user->store_id )
			$store = store::get( $user->store_id );

		$sale_item_sql			=  '
			SELECT
				order_item.item_id,
				concat( IFNULL(category.name,"") , " ", item.name ) AS name,
				order_item.is_free_of_charge,
				SUM( order_item.qty ) AS qty,
				SUM( order_item.total ) AS total,
				order_item.unitary_price
			FROM `order`
			JOIN order_item ON order_item.order_id = `order`.id
			JOIN item ON item.id = order_item.item_id
			LEFT JOIN category ON category.id = item.category_id
			WHERE
				`order`.status	= "ACTIVE"
				AND `order`.system_activated BETWEEN "'.$cash_close->since.'" AND "'.$cash_close->created.'"
				AND `order`.cashier_user_id = "'.$cash_close->created_by_user_id.'"
			GROUP BY item_id, is_free_of_charge, unitary_price';

		$item_sales		= DBTable::getArrayFromQuery( $sale_item_sql );

		$search_array	= array
		(
			'cashier_user_id'						=> $cash_close->created_by_user_id,
			'status'								=> 'ACTIVE',
			'system_activated'.DBTable::GE_SYMBOL	=> $cash_close->since,
			'system_activated'.DBTable::LE_SYMBOL	=> $cash_close->created
		);

		$order_array	= order::search( $search_array );

		return array(
			'cash_close'	=> $cash_close_value,
			'payments'		=> $payment_array,
			'movements'		=> $bank_movement_array,
			'funds'			=> $funds,
			'user'			=> $user->toArray(user::getAllPropertiesExcept('password') ),
			'item_sales'	=> $item_sales,
			'orders'		=> $order_array,
			'store'		=> $store->toArray()
		);
	}

	function getPreviousDate($user)
	{
		$sql = 'SELECT * FROM cash_close WHERE created_by_user_id = '.$user->id.' ORDER BY id DESC LIMIT 1';
		$cash_close_prev	= cash_close::getArrayFromQuery( $sql );



		$prev_date = NULL;
		$dates = array();

		if( !empty(  $cash_close_prev ) )
		{
			$prev_date = $cash_close_prev[0]->created;
		}

		if( $prev_date != NULL )
		{
			$session	= session::getArrayFromQuery('SELECT * FROM `session` WHERE user_id = "'.$user->id.'" AND created >= "'.$prev_date.'" ORDER BY created ASC LIMIT 1');

			if( count( $session ) )
			{
				$dates[] = $session[0]->created;
			}

			$sql = 'SELECT * FROM bank_movement WHERE received_by_user_id='.$user->id.' AND created >= "'.$prev_date.'" ORDER BY created ASC LIMIT 1';
			$bank_movement_array = bank_movement::getArrayFromQuery( $sql );

			if( count( $bank_movement_array ) )
			{
				$dates[] = $bank_movement_array[0]->created;
			}

			$sql = 'SELECT * FROM fund WHERE created_by_user_id = '.$user->id.' AND created >= "'.$prev_date.'" ORDER BY created ASC LIMIT 1';
			$fund_array = fund::getArrayFromQuery( $sql );

			if( !empty( $fund_array ) )
			{
				$dates[] = $fund_array[0]->created;
			}
		}
		else
		{
			$session	= session::getArrayFromQuery('SELECT * FROM `session` WHERE user_id = "'.$user->id.'" ORDER BY created ASC LIMIT 1');

			if( count( $session ) )
			{
				$dates[] = $session[0]->created;
			}

			$sql = 'SELECT * FROM bank_movement WHERE received_by_user_id='.$user->id.' ORDER BY created ASC LIMIT 1';
			$bank_movement_array = bank_movement::getArrayFromQuery( $sql );

			if( count( $bank_movement_array ) )
			{
				$dates[] = $bank_movement_array[0]->created;
			}

			$sql = 'SELECT * FROM fund WHERE created_by_user_id = '.$user->id.' AND created >= "'.$prev_date.'"  ORDER BY id ASC LIMIT 1';
			$fund_array = fund::getArrayFromQuery( $sql );

			if( !empty( $fund_array ) )
			{
				$dates[] = $fund_array[0]->created;
			}
		}

		if( $prev_date && count( $dates ) == 0 )
		{
			return $prev_date;
		}

		return min( $dates );
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
			if( !$is_assoc )
			{
				throw new ValidationException('No soporta operaciones en batch');
			}

			$prev_date 						= $this->getPreviousDate($user);
			$cash_close 					= new cash_close();
			$cash_close->created_by_user_id = $user->id;
			$cash_close->end				= $params['cash_close']['end'];
			$cash_close->created			= NULL;
			//$cash_close->created			= date('Y-m-d H:i:s');
			$cash_close->since				= $prev_date;
			$user_time						= strtotime($cash_close->end);
			$diff							= time() - $user_time;
			$since_time						= strtotime( $prev_date )-$diff;
			$cash_close->start				= date('Y-m-d H:i:s',$since_time );
			$cash_close->unsetEmptyValues(DBTable::UNSET_BLANKS);


			if( !$cash_close->insert() )
			{
				throw new SystemException('Ocurrio un error por favor intenta mas tarde');
			}

			$info = $this->getInfo(array( $cash_close->toArray() ));

			DBTable::commit();
			return $this->sendStatus( 200 )->json( $info[0] );
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
}

$l = new Service();
$l->execute();
