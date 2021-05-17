<?php
namespace APP;

include_once( __DIR__.'/app.php' );
include_once( __DIR__.'/akou/src/ArrayUtils.php');
include_once( __DIR__.'/SuperRest.php');

use \akou\DBTable;
use \akou\ValidationException;
use \akou\LoggableException;
use AKOU\SystemException;

class Service extends SuperRest
{
	function get()
	{
		session_start();
		App::connect();
		$this->setAllowHeader();

		return $this->genericGet("order");
	}
}
$l = new Service();
$l->execute();
