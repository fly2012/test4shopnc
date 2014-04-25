<?php
/**
 * 
 *
 * Framework ºËÐÄ¿ò¼Ü
 * 
 *
 * @copyright  Copyright (c) 2007-2013 ShopNC Inc. (http://www.shopnc.net)
 * @license    http://www.shopnc.net/
 * @link       http://www.shopnc.net/
 * @since      File available since Release v1.1
 */

class Cache
{

		protected $params = NULL;
		protected $enable = NULL;
		protected $handler = NULL;

		public function connect( $type, $args = array( ) )
		{
				if ( empty( $type ) )
				{
						$type = c( "cache.type" );
				}
				$type = strtolower( $type );
				$class = "Cache".ucwords( $type );
				if ( !class_exists( $class ) )
				{
						import( "cache.cache#".$type );
				}
				return new $class( $args );
		}

		public static function getInstance( )
		{
				$args = func_get_args( );
				return get_obj_instance( "Cache", "connect", $args );
		}

}

if ( !defined( "InShopNC" ) )
{
		exit( "Access Invalid!" );
}
?>
