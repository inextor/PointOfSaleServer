<?php
namespace APP;

include_once( __DIR__.'/app.php' );
include_once( __DIR__.'/akou/src/ArrayUtils.php');
include_once( __DIR__.'/SuperRest.php');

class Service extends SuperRest
{
	function get()
	{
		session_start();
		App::connect();
		$this->setAllowHeader();

		$user = app::getUserFromSession();

		if( !$user )
			return $this->sendStatus(401)->json(array('error'=>'Por favor iniciar sesion'));

		$extra_constraints = array();

		if( $user->type == "CLIENT" )
			$extra_constraints[] = 'order.client_user_id= '.$user->id;

		return $this->genericGet("order");
	}
}
$l = new Service();
$l->execute();
