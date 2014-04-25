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

class Log
{

		public static $log = array( );

		const SQL = "SQL";
		const ERR = "ERR";

		public static function record( $message, $level = self::ERR )
		{
				$now = date( "Y-m-d H:i:s" );
				self::$log[] = "[".$now."] {$level}: {$message}\r\n";
		}

}

if ( !defined( "InShopNC" ) )
{
		exit( "Access Invalid!" );
}
?>
