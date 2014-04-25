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

if ( !defined( "InShopNC" ) )
{
		exit( "Access Invalid!" );
}
class CacheFile extends Cache
{

		public function __construct( $params = array( ) )
		{
				$this->params['expire'] = c( "cache.expire" );
				$this->params['path'] = BasePath."/cache";
				$this->enable = TRUE;
		}

		private function init( )
		{
				return TRUE;
		}

		private function isConnected( )
		{
				return $this->enable;
		}

		public function get( $key, $path = NULL )
		{
				$filename = realpath( $this->_path( $key ) );
				if ( is_file( $filename ) )
				{
						return require( $filename );
				}
		}

		public function set( $key, $value, $path = NULL, $expire = NULL )
		{
				$filename = $this->_path( $key );
				if ( !write_file( $filename, $value ) )
				{
						return FALSE;
				}
				return TRUE;
		}

		public function rm( $key, $path = NULL )
		{
				$filename = realpath( $this->_path( $key ) );
				if ( is_file( $filename ) )
				{
						@unlink( $filename );
						return TRUE;
				}
				return FALSE;
		}

		private function _path( $key )
		{
				switch ( strtolower( $key ) )
				{
				case "" :
						$path = BasePath."/cache";
						break;
				default :
						$path = BasePath."/cache";
				}
				return $path."/".$key.".php";
		}

}

?>
