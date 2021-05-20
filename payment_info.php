<?php
namespace APP;

include_once( __DIR__.'/app.php' );
include_once( __DIR__.'/akou/src/ArrayUtils.php');
include_once( __DIR__.'/SuperRest.php');

use \akou\DBTable;
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
		$user = app::getUserFromSession();

		if( $user == null )
			throw new SessionException('Please login');

		$extra_constraints=array();
		$extra_joins = '';
		$extra_sort = array();
		$this->is_debug = true;

		if( !empty($_GET['order_id']) )
		{
			$extra_joins = 'JOIN bank_movement ON bank_movement.payment_id = payment.id
				JOIN bank_movement_order ON bank_movement_order.bank_movement_id = bank_movement.id
					AND bank_movement_order.order_id = "'.DBTable::escape( $_GET['order_id'] ).'"';
		}

		return $this->genericGet("payment",$extra_constraints,$extra_joins,$extra_sort);
	}

	function getInfo($payment_array)
	{
		$result = array();

		$payment_prop				= ArrayUtils			::getItemsProperties($payment_array,'id','paid_by_user_id', 'received_by_user_id');
		$bank_movement_array		= bank_movement			::search(array('payment_id'=>$payment_prop['id']),false,'id');
		$bank_movement_grouped 		= ArrayUtils			::groupByIndex($bank_movement_array,'payment_id');
		$bank_movement_order_array	= bank_movement_order	::search(array('bank_movement'=>array_keys($bank_movement_array)), false, 'id');

		$bmo_group_array		= ArrayUtils::groupByIndex($bank_movement_order_array,'bank_movement_id');

		foreach($payment_array as $payment)
		{
			$bm_array		= $bank_movement_grouped[ $payment['id'] ];

			$movements	= array();

			foreach($bm_array as $bank_movement)
			{
				$bmoa_grouped = $bmo_group_array[ $bank_movement['id'] ]??array();
				$movements[] = array
				(
					'bank_movement'=>$bank_movement,
					'bank_movement_orders'=>$bmoa_grouped,
				);
			}

			$result[] = array
			(
				'payment'=>$payment,
				'movements'=>$movements
			);
		}

		return $result;
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
				throw new SessionException('Please login');

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

	function batchInsert($array)
	{
		$user	= app::getUserFromSession();
		$bank_movement_order_props = bank_movement_order::getAllPropertiesExcept('id','created','updated','bank_movement_id');
		$result	= array();

		foreach($array as $payment_info_array)
		{
			$payment = new payment();
			$payment->assignFromArray($payment_info_array['payment']);

			if( $payment->payment_amount == 0 )
			{
				throw new ValidationException('El pago debe ser al menos de 1 peso');
			}

			$payment->created_by_user_id = $user->id;

			if( !$payment->insert() )
			{
				throw new SystemException('Ocurrio un error por favor intetar mas tarde. '.$payment->getError());
			}

			foreach($payment_info_array['movements'] as $bank_movement_info_array)
			{
				$bank_movement						= new bank_movement();
				$bank_movement->assignFromArray( $bank_movement_info_array['bank_movement'] );
				$bank_movement->payment_id			= $payment->id;
				$bank_movement->received_by_user_id	= $user->id;
				$bank_movement->type 				= 'income';

				if( !$bank_movement->insertDb() )
				{
					throw new SystemException('Ocurrio un error por favor intetar mas tarde. '.$bank_movement->getError());
				}

				foreach( $bank_movement_info_array['bank_movement_orders'] as $bank_movement_order_data)
				{
					$bank_movement_order			=  new bank_movement_order();
					$bank_movement_order->assignFromArray( $bank_movement_order_data, $bank_movement_order_props );

					if(empty( $bank_movement_order->order_id)  )
					{
						throw new ValidationException('El id de la orden no puede estar vacio');
					}

					$order = order::get( $bank_movement_order->order_id );

					if( empty( $order ) )
					{
						throw new SystemException('Ocurrio un error no se encontro la orden con id.'.$bank_movement_order->order_id );
					}

					//Lo estoy haciendo por si se equivocan al asignar el exchange_rate, la comparacion no es necesaria
					if( $bank_movement_order->currency_id !== $order->currency_id )
					{
						$bank_movement_order->amount	= $bank_movement_order->currency_amount*$bank_movement_order->exchange_rate;
					}
					else
					{
						$bank_movement_order->amount	= $bank_movement_order->currency_amount;
					}

					$order->amount_paid += $bank_movement_order->amount;

					if( ($order->total - $order->amount_paid) <= 0.01 )
					{
						$order->paid_status = 'PAID';
					}
					else
					{
						$order->paid_status = 'PARTIALLY_PAID';
					}

					if( !$order->updateDb('amount_paid','paid_status') )
					{
						throw new SystemException('Ocurrio un error por favor intentar mas tarde. '.$order->getError());
					}

					$bank_movement_order->bank_movement_id 		= $bank_movement->id;
					$bank_movement_order->payment_id			= $payment->id;
					$bank_movement_order->created_by_user_id	= $user->id;
					$bank_movement_order->updated_by_user_id	= $user->id;

					if( !$bank_movement_order->insertDb() )
					{
						throw new SystemException('Ocurrio un error por favor intentar mas tarde. '.$bank_movement_order->getError());
					}
				}
			}

			$result[] = $payment->toArray();
		}

		return  $result;
	}
}

$l = new Service();
$l->execute();
