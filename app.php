<?php

namespace POINT_OF_SALE;

include_once( __DIR__.'/akou/src/LoggableException.php' );
include_once( __DIR__.'/akou/src/Utils.php' );
include_once( __DIR__.'/akou/src/DBTable.php' );
include_once( __DIR__.'/akou/src/RestController.php' );
include_once( __DIR__.'/akou/src/ArrayUtils.php' );
include_once( __DIR__.'/akou/src/Image.php' );
include_once( __DIR__.'/SuperRest.php');
//include_once( __DIR__.'/schema.php');

use \akou\DBTable;
use \akou\Utils;
use \akou\LoggableException;
use \akou\SystemException;
use \akou\ValidationException;
use \akou\RestController;
use \akou\NotFoundException;
use \akou\SessionException;

date_default_timezone_set('UTC');
//error_reporting(E_ERROR | E_PARSE);
Utils::$DEBUG 				= TRUE;
Utils::$DEBUG_VIA_ERROR_LOG	= TRUE;
#Utils::$LOG_CLASS			= '\bitacora';
#Utils::$LOG_CLASS_KEY_ATTR	= 'titulo';
#Utils::$LOG_CLASS_DATA_ATTR	= 'descripcion';

class App
{
	const DEFAULT_EMAIL					= '';
	const LIVE_DOMAIN_PROTOCOL			= 'http://';
	const LIVE_DOMAIN					= '';
	const DEBUG							= FALSE;
	const APP_SUBSCRIPTION_COST			= '20.00';

	public static $GENERIC_MESSAGE_ERROR	= 'Please verify details and try again later';
	public static $image_directory 		= './user_images';
	public static $attachment_directory = './user_files';
	public static $is_debug				= false;

	public static function connect()
	{
		DBTable::$_parse_data_types = TRUE;

		 if( !isset( $_SERVER['SERVER_ADDR'])  || $_SERVER['SERVER_ADDR'] =='127.0.0.1' || $_SERVER['SERVER_ADDR'] == '2806:1000:8201:71d:42b0:76ff:fed9:5901')
		{
				$__user		 = 'root';
				$__password	 = 'asdf';
				$__db		 = 'pointofsale';
				$__host		 = '127.0.0.1';
				$__port		 = '3306';
				app::$image_directory = './user_images';
				app::$attachment_directory = './user_files';
				app::$is_debug	= true;
		}
		else
		{
				Utils::$DEBUG_VIA_ERROR_LOG	= FALSE;
				Utils::$LOG_LEVEL			= Utils::LOG_LEVEL_ERROR;
				Utils::$DEBUG				= FALSE;
				Utils::$DB_MAX_LOG_LEVEL	= Utils::LOG_LEVEL_ERROR;
				app::$is_debug	= false;

				$__user		  = 'root';
				$__password	  = 'pointofsale';
				$__db			= 'archbel';
				$__host		  = '127.0.0.1';
				$__port		  = '3306';

				app::$image_directory = './user_images';
				app::$attachment_directory = './user_files';
		}

		$mysqli = new \mysqli($__host, $__user, $__password, $__db, $__port );
		if( $mysqli->connect_errno )
		{
			echo "Failed to connect to MySQL: (" . $mysqli->connect_errno . ") " . $mysqli->connect_error;
			exit();
		}

		date_default_timezone_set('UTC');

		$mysqli->query("SET NAMES 'utf8';");
		$mysqli->query("SET time_zone = '+0:00'");
		$mysqli->set_charset('utf8');


		DBTable::$connection							= $mysqli;
		DBTable::$connection				= $mysqli;
		DBTable::importDbSchema('POINT_OF_SALE');

	}

	static function getPasswordHash( $password, $timestamp )
	{
		return sha1($timestamp.$password.'sdfasdlfkjasld');
	}

	/* https://stackoverflow.com/questions/40582161/how-to-properly-use-bearer-tokens */

	static function getAuthorizationHeader(){
		$headers = null;
		if (isset($_SERVER['Authorization'])) {
			$headers = trim($_SERVER["Authorization"]);
		}
		else if (isset($_SERVER['HTTP_AUTHORIZATION'])) { //Nginx or fast CGI
			$headers = trim($_SERVER["HTTP_AUTHORIZATION"]);
		} elseif (function_exists('apache_request_headers')) {
			$requestHeaders = apache_request_headers();
			// Server-side fix for bug in old Android versions (a nice side-effect of this fix means we don't care about capitalization for Authorization)
			$requestHeaders = array_combine(array_map('ucwords', array_keys($requestHeaders)), array_values($requestHeaders));
			//print_r($requestHeaders);
			if (isset($requestHeaders['Authorization'])) {
				$headers = trim($requestHeaders['Authorization']);
			}
		}
		return $headers;
	}
	/**
	 * get access token from header
	 * */
	static function getBearerToken() {
		$headers = App::getAuthorizationHeader();
		// HEADER: Get the access token from the header
		if (!empty($headers)) {
			if (preg_match('/Bearer\s(\S+)/', $headers, $matches)) {
				return $matches[1];
			}
		}
		return null;
	}

	//static function getOrganizationFromDomain()
	//{
	//	$returned_var = app::getCustomHttpReferer();
	//	$domain_url 	= parse_url( $returned_var );

	//	$domain_name	= $domain_url[ 'host' ];

	//	$domain = domain::searchFirst(array('name'=>$domain_name) );

	//	if( $domain )
	//		return organization::get( $domain->organization_id);

	//	return null;

	//}

	static function getUserFromSession()
	{
		/*
		if( !empty( $_SESSION['id_usuario'] ) )
		{
			$usuario = new usuario();
			$usuario->id = $_SESSION['id_usuario'];
			if( $usuario->load() )
				return $usuario;

		}
		*/
		$token = App::getBearerToken();
		if( $token == null )
			return null;

		return App::getUserFromToken( $token );
	}

	static function getRandomString($length)
	{
		$characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
		$charactersLength = strlen($characters);

		$randomString = '';
		for ($i = 0; $i < $length; $i++) {
			$randomString .= $characters[rand(0, $charactersLength - 1)];
		}
		return $randomString;
	}

	static function getUserFromToken($token)
	{
		if( $token == null )
			return null;

		$user	= new user();
		$session		= new session();
		$session->id	= $token;
		//$session->estatus = 'SESION_ACTIVA';
		$session->setWhereString();


		if( $session->load() )
		{
			$user = new user();
			$user->id = $session->user_id;

			if( $user->load(true) )
			{
				return $user;
			}
		}
		return null;
	}

	static function getCustomHttpReferer()
	{
		$return_var	 = FALSE;

		if( isset( $_SERVER['HTTP_REFERER'] ) )
		{
			$return_var = $_SERVER['HTTP_REFERER'];
		}
		else if( isset( $_SERVER['HTTP_ORIGIN'] ) )
		{
			$return_var = $_SERVER['HTTP_ORIGIN'];
		}
		else if( isset( $_SERVER['HTTP_HOST'] ) )
		{
			$return_var = $_SERVER['HTTP_HOST'];
		}
		else if( isset( $GLOBALS['domain'] ) )
		{

			if
			(
				isset( $GLOBALS['domain']['scheme'] )
				&&
				isset( $GLOBALS['domain']['host'] )
				&&
				isset( $GLOBALS['domain']['path'] )
			)
			{
				$return_var = $GLOBALS['domain']['scheme'] .
				'://' .
				$GLOBALS['domain'].
				$GLOBALS['domain']['path'];
			}
			else
			{
			}
		}

		if( empty( $return_var ) )
		{
			if( !empty( $_GET['domain'] ) )
			{
				$return_var = 'http://'.$_GET['domain'];
			}
		}

		if( !empty( $return_var ) )
		{
			$return_var = str_replace( 'www.', '', $return_var );
		}
		return $return_var;
	}
}

