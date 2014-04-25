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

class Security
{

		public static function getToken( )
		{
				$token = substr( md5( substr( time( ), 0, -7 ).md5( MD5_KEY ) ), 8, 8 );
				echo "<input type='hidden' name='formhash' value='".$token."' />";
		}

		public static function checkToken( )
		{
				$token = $_POST['formhash'];
				$input_token = substr( md5( substr( time( ), 0, -7 ).md5( MD5_KEY ) ), 8, 8 );
				if ( $token != $input_token )
				{
						$error = "token is error!";
						throw_exception( $error );
				}
				return TRUE;
		}

		public static function fliterHtmlSpecialChars( $string )
		{
				$string = preg_replace( "/&amp;((#(\\d{3,5}|x[a-fA-F0-9]{4})|[a-zA-Z][a-z0-9]{2,5});)/", "&\\1", str_replace( array( "&", "\"", "<", ">" ), array( "&amp;", "&quot;", "&lt;", "&gt;" ), $string ) );
				return $string;
		}

		public static function getAddslashesForInput(&$array, $ignore = array( ))
		{
				if ( !function_exists( "htmlawed" ) )
				{
						require( BasePath."/framework/libraries/htmlawed.php" );
				}
                //$_array = array();
                //var_dump($array);exit;
				if ( !empty( $array ) )
				{
						while (list( $k, $v ) = each($array))
						{
								if ( is_string( $v ) )
								{
										if ( $k != "statistics_code" )
										{
										  //echo '原来的:'.$v.'<br>';
												if ( !in_array( $k, $ignore ) )
												{
														$v = self::fliterhtmlspecialchars( $v );
                                                        
												}
												else
												{
														if ( get_magic_quotes_gpc( ) )
														{
																$v = stripslashes( $v );
														}
														$v = htmlawed( $v );
												}
												if ( $k == "ref_url" )
												{
														$v = str_replace( "&amp;", "&", $v );
												}
                                                //echo '过滤的'.$v.'<br>';
										}
										if ( !get_magic_quotes_gpc( ) )
										{
												$_array[$k] = addslashes( trim( $v ) );
										}
										else
										{
												$_array[$k] = trim( $v );
										}
                                       // print_r($_array);break;
								}
								else if ( is_array( $v ) )
								{
										if ( $k == "statistics_code" )
										{
												$array[$k] = $v;
										}
										else
										{
												$array[$k] = self::getaddslashesforinput( $v );
										}
								}
						}
                        //echo '1';
                        //var_dump($array);
                        
						return $array;
				}
				return FALSE;
		}

}

if ( !defined( "InShopNC" ) )
{
		exit( "Access Invalid!" );
}
?>
