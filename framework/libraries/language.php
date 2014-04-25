<?php
/**
 * 
 *
 * Framework 核心框架
 * 
 *
 * @copyright  Copyright (c) 2007-2013 ShopNC Inc. (http://www.shopnc.net)
 * @license    http://www.shopnc.net/
 * @link       http://www.shopnc.net/
 * @since      File available since Release v1.1
 */

final class Language
{

		private static $language_content = array( );

		public static function getGBK( $key )
		{
				if ( strtoupper( CHARSET ) == "GBK" && !empty( $key ) )
				{
						if ( is_array( $key ) )
						{
								$result = var_export( $key, TRUE );
								$result = iconv( "UTF-8", "GBK", $result );
								eval( "\$result = ".$result.";" );
								return $result;
						}
						$result = iconv( "UTF-8", "GBK", $key );
				}
				return $result;
		}

		public static function getUTF8( $key )
		{
				if ( !empty( $key ) )
				{
						if ( is_array( $key ) )
						{
								$result = var_export( $key, TRUE );
								$result = iconv( "GBK", "UTF-8", $result );
								eval( "\$result = ".$result.";" );
								return $result;
						}
						$result = iconv( "GBK", "UTF-8", $key );
				}
				return $result;
		}

		public static function get( $key, $charset = "" )
		{
				$result = self::$language_content[$key] ? self::$language_content[$key] : "";
				if ( strtoupper( CHARSET ) == "UTF-8" || strtoupper( $charset ) == "UTF-8" )
				{
						return $result;
				}
				if ( strtoupper( CHARSET ) == "GBK" && !empty( $result ) )
				{
						$result = iconv( "UTF-8", "GBK", $result );
				}
				return $result;
		}

		public static function set( $key, $value )
		{
				self::$language_content[$key] = $value;
				return TRUE;
		}

		public static function read( $file )
		{
				str_replace( "，", ",", $file );
				$tmp = explode( ",", $file );
				foreach ( $tmp as $v )
				{
						if ( defined( "ProjectName" ) && ProjectName != "" )
						{
								$tmp_file = BasePath.DS.ProjectName.DS."language".DS.LANG_TYPE.DS.$v.".php";
						}
						else
						{
								$tmp_file = BasePath.DS."language".DS.LANG_TYPE.DS.$v.".php";
						}
						if ( file_exists( $tmp_file ) )
						{
								require( $tmp_file );
								if ( !empty( $lang ) || is_array( $lang ) )
								{
										self::$language_content = array_merge( self::$language_content, $lang );
								}
								unset( $lang );
						}
				}
				return TRUE;
		}

		public static function getLangContent( $charset = "" )
		{
				$result = self::$language_content;
				if ( strtoupper( CHARSET ) == "UTF-8" || strtoupper( $charset ) == "UTF-8" )
				{
						return $result;
				}
				if ( strtoupper( CHARSET ) == "GBK" && !empty( $result ) || is_array( $result ) )
				{
						foreach ( $result as $k => $v )
						{
								$result[$k] = iconv( "UTF-8", "GBK", $v );
						}
				}
				return $result;
		}

		public static function appendLanguage( $lang )
		{
				if ( !empty( $lang ) || is_array( $lang ) )
				{
						self::$language_content = array_merge( self::$language_content, $lang );
				}
		}

}

if ( !defined( "InShopNC" ) )
{
		exit( "Access Invalid!" );
}
?>
