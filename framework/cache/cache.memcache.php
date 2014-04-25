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
class CacheMemcache extends Cache
{

		private $config = NULL;
		private $type = NULL;
		private $prefix = NULL;

		public function __construct( )
		{
				$this->config = c( "memcache" );
				if ( extension_loaded( "memcache" ) )
				{
				}
				if ( !is_array( $this->config[1] ) )
				{
						throw_exception( "memcache failed to load" );
				}
				$this->init( );
		}

		private function init( )
		{
				$this->prefix = $this->config['prefix'] ? $this->config['prefix'] : substr( md5( $_SERVER['HTTP_HOST'] ), 0, 6 )."_";
				$this->handler = new Memcache( );
				if ( function_exists( "memcache_add_server" ) )
				{
						foreach ( $this->config as $key => $conf )
						{
								if ( is_numeric( $key ) )
								{
										$this->enable = $this->handler->addServer( $conf['host'], $conf['port'], $conf['pconnect'] ? TRUE : FALSE );
								}
						}
				}
				else
				{
						$func = $this->config[1]['pconnect'] ? "pconnect" : "connect";
						$this->enable = $this->handler->$func( $this->config[1]['host'], $this->config[1]['port'] );
				}
		}

		public function isConnected( )
		{
				return $this->enable;
		}

		public function set( $key, $value, $type = "", $ttl = SESSION_EXPIRE )
		{
				if ( !$this->enable )
				{
						return FALSE;
				}
				$this->type = $type;
				return $this->handler->set( $this->_key( $key ), $value, MEMCACHE_COMPRESSED, $ttl );
		}

		public function get( $key, $type = "" )
		{
				if ( !$this->enable )
				{
						return FALSE;
				}
				$this->type = $type;
				return $this->handler->get( $this->_key( $key ) );
		}

		public function rm( $key, $type = "" )
		{
				$this->type = $type;
				return $this->handler->delete( $this->_key( $key ) );
		}

		public function clear( )
		{
				return $this->handler->flush( );
		}

		private function _key( $str )
		{
				return $this->prefix.$this->type.$str;
		}

		public function inc( $key, $step = 1 )
		{
				return $this->handler->increment( $this->_key( $key ), $step );
		}

		public function dec( $key, $step = 1 )
		{
				return $this->handler->decrement( $this->_key( $key ), $step );
		}

}

?>
