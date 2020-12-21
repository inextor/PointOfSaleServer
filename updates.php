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
use \akou\SystemException;
use \akou\SessionException;


class Service extends SuperRest
{
	function post()
	{
		session_start();
		App::connect();
		$this->setAllowHeader();

		$params = $this->getMethodParams();
		$name = $params['method'];

		try
		{

			if( is_callable(array($this, $name) )){
				return $this->$name();
			}
			else
			{
				throw new ValidationException('No se encontro la funcion '.$name);
			}
		}
		catch(LoggableException $e)
		{
			return $this->sendStatus( $e->code )->json(array("error"=>$e->getMessage()));
		}
		catch(Exception $e)
		{
			return $this->sendStatus( 500 )->json(array("error"=>$e->getMessage()));
		}
	}

	function copyPrices()
	{
		$user = app::getUserFromSession();

		if( !$user )
			throw new SessionException('Por favor iniciar sesion');

		$params = $this->getMethodParams();

		$this->debug('params', $params );

		$sql = 'INSERT INTO price ( store_id,item_id, price, price_type_id, updated_by_user_id )
					(
						SELECT  "'.DBTable::escape($params['to_store_id']).'" AS s_id,
							p.item_id,
							p.price,
							p.price_type_id ,
							"'.DBTable::escape( $user->id ).'"
						FROM price AS p
						WHERE p.store_id= "'.DBTable::escape($params['from_store_id']).'"
					)
				ON DUPLICATE KEY UPDATE price.price = p.price, updated_by_user_id = "'.DBTable::escape( $user->id).'"';

		error_log( $sql );

		$result = DBTable::query( $sql );
		if( DBTable::$connection->error )
		{
			throw new SystemException( DBTable::$connection->error );
		}
		error_log( print_r( $result, true)  );

		return $this->sendStatus(200)->json(true);
	}
}

$l = new Service();
$l->execute();
