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

function thumb( $goods, $type = "" )
{
		if ( !in_array( $type, array( "tiny", "small", "mid", "max", "240x240" ) ) )
		{
				$type = "small";
		}
		if ( is_array( $goods ) )
		{
				if ( array_key_exists( "goods_images", $goods ) )
				{
						$goods['goods_image'] = $goods['goods_images'];
				}
				else if ( array_key_exists( "apic_cover", $goods ) )
				{
						$goods['goods_image'] = $goods['apic_cover'];
				}
				if ( empty( $goods['goods_image'] ) )
				{
						return SiteUrl."/".defaultgoodsimage( $type );
				}
				$a = explode( ".", $goods['goods_image'] );
				$ext = end( $a );
				$file = str_ireplace( array(
						"_tiny.".$ext,
						"_small.".$ext,
						"_mid.".$ext,
						"_max.".$ext,
						"_240x240.".$ext
				), "", $goods['goods_image'] );
				$fname = basename( $file );
				if ( preg_match( "/^(\\d+_)/", $fname ) )
				{
						$store_id = substr( $fname, 0, strpos( $fname, "_" ) );
				}
				else
				{
						$store_id = $goods['store_id'];
				}
				if ( c( "thumb.save_type" ) == 1 )
				{
						if ( !file_exists( BasePath."/".ATTACH_GOODS."/".$store_id."/".$file.( $type == "" ? "" : "_".$type.".".$ext ) ) )
						{
								if ( file_exists( BasePath."/".ATTACH_GOODS."/".$store_id."/".$file."_mid.".$ext ) )
								{
										$type = "mid";
								}
								else
								{
										return SiteUrl."/".defaultgoodsimage( $type );
								}
						}
						$thumb_host = c( "thumb_url" )."/".ATTACH_GOODS;
				}
				else if ( c( "thumb.save_type" ) == 2 )
				{
						if ( !file_exists( BasePath."/".ATTACH_GOODS."/".$store_id."/".$file.( $type == "" ? "" : "_".$type.".".$ext ) ) )
						{
								if ( file_exists( BasePath."/".ATTACH_GOODS."/".$store_id."/".$file."_mid.".$ext ) )
								{
										$type = "mid";
								}
								else
								{
										return c( "thumb_url" ).str_replace( ATTACH_PATH, "", defaultgoodsimage( $type ) );
								}
						}
						$thumb_host = c( "thumb_url" ).str_replace( ATTACH_PATH, "", ATTACH_GOODS );
				}
				else
				{
						$thumb_host = c( "thumb_url" )."/".ATTACH_GOODS;
				}
				return $thumb_host."/".$store_id."/".$file.( $type == "" ? "" : "_".$type.".".$ext );
		}
}

function cthumb( $file, $type = "", $store_id = FALSE )
{
		if ( !in_array( $type, array( "tiny", "small", "mid", "max", "240x240" ) ) )
		{
				$type = "small";
		}
		if ( empty( $file ) )
		{
				return SiteUrl."/".defaultgoodsimage( $type );
		}
		$a = explode( ".", $file );
		$ext = end( $a );
		$file = str_ireplace( array(
				"_tiny.".$ext,
				"_small.".$ext,
				"_mid.".$ext,
				"_max.".$ext,
				"_240x240.".$ext
		), "", $file );
		$fname = basename( $file );
		if ( $store_id === FALSE )
		{
				return $file.( $type == "" ? "" : "_".$type.".".$ext );
		}
		if ( preg_match( "/^(\\d+_)/", $fname ) )
		{
				$store_id = substr( $fname, 0, strpos( $fname, "_" ) );
		}
		if ( c( "thumb.save_type" ) == 1 )
		{
				if ( is_numeric( $store_id ) )
				{
				}
				if ( !file_exists( BasePath."/".ATTACH_GOODS."/".$store_id."/".$file.( $type == "" ? "" : "_".$type.".".$ext ) ) )
				{
						if ( file_exists( BasePath."/".ATTACH_GOODS."/".$store_id."/".$file."_mid.".$ext ) )
						{
								$type = "mid";
						}
						else
						{
								return SiteUrl."/".defaultgoodsimage( $type );
						}
				}
				$thumb_host = c( "thumb_url" )."/".ATTACH_GOODS;
		}
		else if ( c( "thumb.save_type" ) == 2 )
		{
				if ( !file_exists( BasePath."/".ATTACH_GOODS."/".$store_id."/".$file.( $type == "" ? "" : "_".$type.".".$ext ) ) )
				{
						if ( file_exists( BasePath."/".ATTACH_GOODS."/".$store_id."/".$file."_mid.".$ext ) )
						{
								$type = "mid";
						}
						else
						{
								return c( "thumb_url" ).str_replace( ATTACH_PATH, "", defaultgoodsimage( $type ) );
						}
				}
				$thumb_host = c( "thumb_url" ).str_replace( ATTACH_PATH, "", ATTACH_GOODS );
		}
		else
		{
				$thumb_host = c( "thumb_url" )."/".ATTACH_GOODS;
		}
		return $thumb_host."/".$store_id."/".$file.( $type == "" ? "" : "_".$type.".".$ext );
}

function gthumb( $imgurl = "", $type = "" )
{
		if ( !in_array( $type, array( "small", "mid", "max" ) ) )
		{
				$type = "small";
		}
		if ( empty( $imgurl ) )
		{
				return SiteUrl."/".defaultgoodsimage( $type );
		}
		$a = explode( ".", $imgurl );
		$ext = end( $a );
		$file = str_ireplace( array(
				"_small.".$ext,
				"_mid.".$ext,
				"_max.".$ext
		), "", $imgurl );
		$fname = basename( $file );
		if ( !file_exists( BasePath."/".ATTACH_GROUPBUY."/".$file.( $type == "" ? "" : "_".$type.".".$ext ) ) )
		{
				if ( BasePath."/".ATTACH_GROUPBUY."/".$file."_small".$ext )
				{
						return SiteUrl."/".ATTACH_GROUPBUY."/".$file."_small.".$ext;
				}
				return SiteUrl."/".defaultgoodsimage( $type );
		}
		return SiteUrl."/".ATTACH_GROUPBUY."/".$file.( $type == "" ? "" : "_".$type.".".$ext );
}

function orderStateInfo( $state_code, $refund_state = 0 )
{
		$lang = Language::getlangcontent( );
		$lang_string = "";
		$state_code = intval( $state_code );
		switch ( $state_code )
		{
		case 0 :
				$lang_string = $lang['has_been_canceled'];
				return $lang_string;
		case 10 :
				$lang_string = $lang['pending_payment'];
				return $lang_string;
		case 11 :
				$lang_string = $lang['pending_recive'];
				return $lang_string;
		case 20 :
				$lang_string = $lang['paid'];
				return $lang_string;
		case 30 :
				$lang_string = $lang['shipped'];
				return $lang_string;
		case 40 :
				$lang_string = $lang['completed'];
				if ( !( $refund_state == 2 ) )
				{
						break;
				}
				$lang_string = $lang['refund_completed'];
				return $lang_string;
		case 50 :
				$lang_string = $lang['to_be_confirmed'];
				return $lang_string;
		case 60 :
				$lang_string = $lang['to_be_shipped'];
				return $lang_string;
				$lang_string = $lang['completed'];
		}
		return $lang_string;
}

function getCreditArr( $credit )
{
		if ( $GLOBALS['setting_config']['creditrule'] != "" )
		{
				$credit_arr = unserialize( $GLOBALS['setting_config']['creditrule'] );
		}
		$max_credit = 0;
		if ( !empty( $credit_arr ) || is_array( $credit_arr ) )
		{
				foreach ( $credit_arr as $k => $v )
				{
						if ( !empty( $v ) )
						{
								if ( is_array( $v ) )
								{
										foreach ( $v as $son_k => $son_v )
										{
												if ( $son_v[0] <= $credit && $credit <= $son_v[1] )
												{
														return array(
																"grade" => $k,
																"songrade" => $son_k
														);
												}
												$max_credit = $son_v[1];
										}
								}
						}
				}
		}
		if ( $max_credit < $credit )
		{
				return array( "grade" => "crown", "songrade" => 5 );
		}
		return array( );
}

if ( !defined( "InShopNC" ) )
{
		exit( "Access Invalid!" );
}
?>
