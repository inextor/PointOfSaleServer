<?php
namespace APP;

include_once( __DIR__.'/app.php' );
include_once( __DIR__.'/akou/src/ArrayUtils.php');
include_once( __DIR__.'/SuperRest.php');

use AKOU\ArrayUtils;
use \akou\DBTable;
use \akou\ValidationException;
use \akou\LoggableException;
use \akou\SessionException;


class Service extends SuperRest
{
	function get()
	{
		session_start();
		App::connect();
		$this->setAllowHeader();

		$name = $_GET['report_name'];

		try
		{
			if( is_callable(array($this, $name) ))
			{
				return $this->$name();
			}
			else
			{
				throw new ValidationException('No se encontro la funcion');
			}
		}
		catch(LoggableException $e)
		{
			return $this->sendStatus( $e->code )->json(array("error"=>$e->getMessage()));
		}
		catch(\Exception $e)
		{
			return $this->sendStatus( 500 )->json(array("error"=>$e->getMessage()));
		}
	}

	function getBotellasPorEtiqueta()
	{
		$almacen_str = '';

		if( !empty($_GET['store_id']) )
			$almacen_str = 'AND botella.store_id = "'.DBTable::escape($_GET['store_id']).'"';

		$user = app::getUserFromSession();

		if( $user == null )
			throw new SessionException('Por favor iniciar Sesión');

		//$sql = 'SELECT
		//	category_id,
		//	COUNT(*) as total_botellas,
		//	SUM( IF(marbete_id IS NULL, 0, 1) ) AS con_marbete,
		//	SUM( IF(marbete_id IS NULL, 1, 0) ) AS sin_marbete,
		//	SUM( IF( tipo_de_consumo LIKE "BOTELLA", 1, 0 )) AS consumo_botella,
		//	SUM( IF( tipo_de_consumo LIKE "COPEO", 1, 0 )) AS consumo_copeo
		//	FROM botella
		//	JOIN category ON botella.category_id = category.id AND category.empresa_id = '.$user->empresa_id.'
		//	WHERE botella.estatus = "EN_ALMACEN" '.$almacen_str.'
		//	GROUP BY botella.category_id';

		//$array = DBTable::getArrayFromQuery( $sql );
		//return $this->sendStatus(200)->json( $array );
		return $this->sendStatus(200)->json( array() );
	}


	/*
	* required $_GET['date1']
	* ,$_GET['date2']
	* ,$_GET['date3']
	*
	*/

	function getStockHistoryByTagAndPriceTypes()
	{
		$category_type	= empty( $_GET['category_type'] )? '' : $_GET['category_type'];

		$category_joins = '';

		if( !empty( $category_type ) )
		{
			$category_joins = '
				JOIN `item` ON stock_record.item_id = item.id
				JOIN `category` ON item.category_id = item.category_id
					AND category.type = "'.DBTable::escape( $category_type ).'"';
		}

		//Envios recibidos
		$sql_entradas = 'SELECT stock_record.item_id, SUM( stock_record.movement_qty) AS total
			FROM stock_record
			'.$category_joins.'
			WHERE shipping_item_id IS NOT NULL AND movement_type = "POSITIVE"
				AND stock_record.store_id = "'.DBTable::escape($_GET['store_id'] ).'"
				AND stock_record.created BETWEEN "'.DBTable::escape( $_GET['date1'] ).'" AND "'.DBTable::escape($_GET['date2']).'"
			GROUP BY item_id';

		//Envios echos
		$sql_salidas = 'SELECT stock_record.item_id,SUM( stock_record.movement_qty ) as total
			FROM stock_record
			'.$category_joins.'
			JOIN shipping_item ON shipping_item.id = stock_record.shipping_item_id
			WHERE shipping_item_id IS NOT NULL AND movement_type = "NEGATIVE"
				AND stock_record.created BETWEEN "'.DBTable::escape( $_GET['date1'] ).'" AND "'.DBTable::escape($_GET['date2']).'"
				AND stock_record.store_id = "'.DBTable::escape($_GET['store_id'] ).'"
			GROUP BY item_id';

		#error_log('SQL_SALIDAS'.$sql_salidas);

		$sql_orders_by_tag = 'SELECT stock_record.item_id, `order`.tag,SUM( stock_record.movement_qty ) AS total
			FROM stock_record
			JOIN order_item ON `order_item`.item_id = stock_record.item_id
			JOIN `order` ON `order`.id = order_item.order_id
			'.$category_joins.'
			WHERE stock_record.store_id = "'.DBTable::escape($_GET['store_id'] ).'"
				AND stock_record.created BETWEEN "'.DBTable::escape( $_GET['date1'] ).'" AND "'.DBTable::escape($_GET['date2']).'"
			GROUP BY stock_record.item_id, `order`.tag';


		$sql_orders_by_price_type = 'SELECT stock_record.item_id,stock_record.store_id, `order`.price_type_id,SUM( stock_record.movement_qty ) AS total
			FROM stock_record
			JOIN order_item ON `order_item`.id = stock_record.order_item_id
			JOIN `order` ON `order`.id = order_item.order_id
			'.$category_joins.'
			WHERE stock_record.store_id = "'.DBTable::escape($_GET['store_id'] ).'"
				AND stock_record.created BETWEEN "'.DBTable::escape( $_GET['date1'] ).'" AND "'.DBTable::escape($_GET['date2']).'"
			GROUP BY stock_record.item_id,stock_record.store_id, `order`.price_type_id';





		$sql_items = 'SELECT DISTINCT item.* FROM stock_record JOIN item ON item.id = stock_record.item_id
		JOIN category ON item.category_id = item.category_id
		'.$category_joins.'
		WHERE stock_record.store_id = "'.DBTable::escape($_GET['store_id'] ).'"
			AND stock_record.created BETWEEN "'.DBTable::escape( $_GET['date1'] ).'" AND "'.DBTable::escape($_GET['date2']).'"';

		$items = DBTable::getArrayFromQuery( $sql_items );

		$entradas			= DBTable::getArrayFromQuery( $sql_entradas,'item_id');
		$salidas			= DBTable::getArrayFromQuery( $sql_salidas, 'item_id');
		$ordenes_by_tag		= DBTable::getArrayFromQueryGroupByIndex( $sql_orders_by_tag, 'item_id' );
		$ordenes_by_price	= DBTable::getArrayFromQueryGroupByIndex( $sql_orders_by_price_type,'item_id');

		$this->sendStatus(200)->json(array(
			'entradas'	=> $entradas,
			'salidas'	=> $salidas,
			'orders_by_tag'	=> $ordenes_by_tag,
			'orders_by_price_type'	=> $ordenes_by_price,
			'items'		=> $items
		));
	}

	function getBotellasPorAlmacen()
	{
		$almacen_str = '';

		$user = app::getUserFromSession();

		if( $user == null )
			throw new SessionException('Por favor iniciar Sesión');

		$sql = 'SELECT
			botella.almacen_id,
			COUNT(*) as total_botellas,
			SUM( IF(marbete_id IS NULL, 0, 1) ) AS con_marbete,
			SUM( IF(marbete_id IS NULL, 1, 0) ) AS sin_marbete,
			SUM( IF( tipo_de_consumo LIKE "COPEO", 1, 0 )) AS consumo_copeo
			FROM botella
			JOIN etiqueta ON botella.etiqueta_id = etiqueta.id AND etiqueta.empresa_id = '.$user->empresa_id.'
			WHERE botella.estatus = "EN_ALMACEN" '.$almacen_str.'
			GROUP BY botella.almacen_id';

		//$array = DBTable::getArrayFromQuery( $sql );
		//return $this->sendStatus(200)->json( $array );
		return $this->sendStatus(200)->json( array() );
	}

	// getRangoDebotellasSinMarbete
	function getRangoDeBotellasSinMarbete()
	{
		if( empty( $_GET['etiqueta_id'] ) )
			throw new ValidationException('Por favor especificar la etiqueta');

		$botella_inicial_constraint = '';
		if( !empty( $_GET['botella_inicial'] ) )
			$botella_inicial_constraint = ' AND codigo_botella >= "'.DBTable::escape($_GET['botella_inicial']).'"';

		$cantidad = intVal( $_GET['cantidad'] );

		$sql = 'SELECT *
			FROM botella
			WHERE etiqueta_id = "'.DBTable::escape($_GET['etiqueta_id']).'"
				'.$botella_inicial_constraint.'
				AND marbete_id IS NULL
				AND almacen_id = "'.DBTable::escape($_GET['almacen_id']).'"
				AND estatus = "EN_ALMACEN"
			ORDER BY codigo_botella ASC LIMIT '.$cantidad;

		$botella_array = DBTable::getArrayFromQuery( $sql );
		$result = array();
		$previous = null;


		$botellas_info = array();

		$previous	= null;
		$index		= 0;

		foreach($botella_array as $botella )
		{
			if( $previous == null OR $botella['codigo_botella'] >($previous+1) )
			{
				$index = count( $botellas_info );

				$botellas_info[] = array(
					'botella_inicial' => $botella['codigo_botella'],
					'cantidad'=> 1
				);
				$previous = $botella['codigo_botella'];
				continue;
			}

			$botellas_info[ $index ]['cantidad']++;
			$previous = $botella['codigo_botella'];
		}
		return $this->sendStatus(200)->json( $botellas_info );
	}

	function getCategoryStock()
	{
		$constraints = array();

		if( !empty( $_GET['type'] ) )
			$constraints[] = 'category.type = "'.DBTable::escape($_GET['type']).'"';

		$where_str = '';

		if( !empty( $_GET['store_id'] ) )
		{
			$constraints[] = '"'.DBTable::escape($_GET['store_id']).'"';
			$where_str = 'WHERE store_id = "'.DBTable::escape($_GET['store_id'] ).'"';
		}

		$stock_ids 	= 'SELECT MAX(id) AS max_id,store_id,item_id FROM stock_record '.$where_str.' GROUP BY item_id, store_id';

		$ids_array	= DBTable::getArrayFromQuery($stock_ids, 'max_id');

		$ids = ArrayUtils::getItemsProperty($ids_array,'max_id');

		if( empty( $ids_array ) )
			return $this->sendStatus(200)->json(array('total'=>0,'data'=>array()));

		$constraints_str = count( $constraints ) > 0 ? join(' AND ',$constraints ) : '1';

		$sql = 'SELECT category.*, SUM( stock_record.qty ) AS total
			FROM category
			JOIN item ON item.category_id = category.id
			LEFT JOIN stock_record ON item.id = stock_record.item_id AND stock_record.id IN ('.DBTAble::escapeArrayValues( $ids ).')
			WHERE '.$constraints_str.'
			GROUP BY category.id ORDER BY category.name ASC';


		$array = DBTable::getArrayFromQuery($sql);
		return $this->sendStatus(200)->json( $array );
	}

	function getStoreStock()
	{
		$stock_ids 	= 'SELECT MAX(id) AS max_id,store_id,item_id FROM stock_record GROUP BY item_id, store_id';

		$ids_array	= DBTable::getArrayFromQuery($stock_ids, 'max_id');

		$ids = ArrayUtils::getItemsProperty($ids_array,'max_id');

		if( empty( $ids_array ) )
			return $this->sendStatus(200)->json(array('total'=>0,'data'=>array()));

		$sql = 'SELECT store.*, SUM( stock_record.qty ) AS total
			FROM store
			LEFT JOIN stock_record ON store.id = stock_record.store_id AND stock_record.id IN ('.DBTAble::escapeArrayValues( $ids ).')
			LEFT JOIN item ON item.id = stock_record.item_id
			LEFT JOIN category ON category.id = item.category_id '.(!empty($_GET['type']) ? 'AND category.type = "'.DBTable::escape($_GET['type']).'"':'').'
			GROUP BY store.id ORDER BY store.name ASC';

		$array	= DBTable::getArrayFromQuery($sql);
		return $this->sendStatus(200)->json( $array );
	}

	function getOrdersReportByTag()
	{
		$constraints = array();

		if( !empty( $_GET['date1'] ) )
			$constraints[] = '`order`.created >= "'.DBTable::escape( $_GET['date1'] ).'"';

		if( !empty( $_GET['date2'] ) )
			$constraints[] = '`order`.created <= "'.DBTable::escape( $_GET['date2'] ).'"';

		if( !empty( $_GET['store_id'] ) )
		{
			$constraints[] = '`order`.store_id = "'.DBTable::escape( $_GET['store_id'] ).'"';
		}

		$constraints_str = count( $constraints ) > 0 ? 'WHERE '.join(' AND ',$constraints ) : '';

		$sql = 'SELECT `order`.tag, SUM(order_item.qty) AS total
			FROM `order` JOIN order_item ON order_item.order_id = `order`.id
			'.$constraints_str.'
			GROUP BY `order`.tag';

		$array = DBTable::getArrayFromQuery( $sql );

		return $this->sendStatus(200)->json( $array );
	}

	function getTotalSalesByStore()
	{
		$constraints = array();

		if( !empty( $_GET['date1'] ) )
			$constraints[] = '`order`.created >= "'.DBTable::escape( $_GET['date1'] ).'"';

		if( !empty( $_GET['date2'] ) )
			$constraints[] = '`order`.created <= "'.DBTable::escape( $_GET['date2'] ).'"';

		$constraints[] = 'delivery_status="DELIVERED"';

		$constraints_str = count( $constraints ) > 0 ? 'WHERE '.join(' AND ',$constraints ) : '';

		$sql = 'SELECT `order`.store_id,store.name, SUM( total) AS total, SUM( pending_amount ) as pending
			FROM `order`
			JOIN store ON `order`.store_id = store.id
			'.$constraints_str.'
			GROUP BY `order`.store_id';

		$array = DBTable::getArrayFromQuery( $sql );

		return $this->sendStatus(200)->json( $array );

	}
}

$l = new Service();
$l->execute();
