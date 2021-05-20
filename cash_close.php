<?php
namespace APP;

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
use \akou\SessionException;


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

		$result[] = array();

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
			'created_by_user_id'			=> $cash_close->created_by_user_id,
			'created'.DBTable::GE_SYMBOL	=> $cash_close->since,
			'created'.DBTable::LE_SYMBOL	=> $cash_close->closed
		);

		$bank_movement_array	= bank_movement::search( $bm_search );
		$cashier_fund			= cashier_fund::search( $bm_search );
		$payment_array			= payment::search( $bm_search );

		return array(
			'payments'		=> $payment_array,
			'movements'		=> $bank_movement_array,
			'funds'			=> $cashier_fund,
		);
	}

	function getPreviousDate($user)
	{
		$cash_close_prev	= cash_close::getArrayFromQuery('SELECT * FROM cash_close WHERE created_by_user_id = '.$user->id.' ORDER BY id DESC LIMIT 1');

		$prev_date = NULL;

		if( count( $cash_close_prev) )
			$prev_date = $cash_close_prev[0]->closed;

		$dates	= array();

		if( $prev_date != NULL )
		{
			$session	= session::search('SELECT * FROM session WHERE user_id = "'.$user->id.'" created >= "'.$prev_date.'" ORDER BY created ASC LIMIT 1');

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

			$sql = 'SELECT * FROM fund WHERE user_id = '.$user->id.' AND created >= "'.$prev_date.'" ORDER BY id created ASC LIMIT 1';
			$fund_array = fund::getArrayFromQuery( $sql );

			if( !empty( $fund_array ) )
			{
				$dates[] = $fund_array[0]->created;
			}
		}
		else
		{
			$session	= session::search('SELECT * FROM session WHERE user_id = "'.$user->id.'" ORDER BY created ASC LIMIT 1');

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

			$sql = 'SELECT * FROM fund WHERE user_id = '.$user->id.' ORDER BY id created ASC LIMIT 1';
			$fund_array = fund::getArrayFromQuery( $sql );

			if( !empty( $fund_array ) )
			{
				$dates[] = $fund_array[0]->created;
			}
		}
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

	function batchInsert($array)
	{
		$user = app::getUserFromSession();

		foreach($array as $params )
		{
			$cash_close = new cash_close();
			$cash_close->created_by_user_id = $user->id;
			$cash_close->closed = date('Y-m-d H:i:s');
			$prev_value = $this->getPreviousDate();
		}
		return $this->genericInsert($array,"cash_close");
	}

	function batchUpdate($array)
	{
		$insert_with_ids = false;
		return $this->genericUpdate($array, "cash_close", $insert_with_ids );
	}

	/*
	function delete()
	{
		try
		{
			return $this->genericDelete("cash_close");
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
