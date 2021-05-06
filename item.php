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


		$user = app::getUserFromSession();

		if( !$user )
			return $this->sendStatus( 401 )->json(array("error"=>'Por favor iniciar sesion'));


		$this->is_debug = false;
		$extra_join = '';
		$extra_sort = array();

		if( !empty( $_GET['category_type'] ) )
		{
			$extra_join = 'JOIN category ON category.id = item.category_id AND category.type = "'.$_GET['category_type'].'"';
			$extra_sort = array('category.name');
		}


		return $this->genericGet("item",[],$extra_join,$extra_sort);
	}

	/*
	function delete()
	{
		try
		{
			return $this->genericDelete("item");
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
