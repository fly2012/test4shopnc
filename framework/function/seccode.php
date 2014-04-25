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

function makeSeccode( $nchash )
{
		$seccode = random( 6, 1 );
		$seccodeunits = "";
		$s = sprintf( "%04s", base_convert( $seccode, 10, 23 ) );
		$seccodeunits = "ABCEFGHJKMPRTVXY2346789";
		if ( $seccodeunits )
		{
				$seccode = "";
				$i = 0;
				for ( ;	$i < 4;	++$i	)
				{
						$unit = ord( $s[$i] );
						$seccode .= 48 <= $unit && $unit <= 57 ? $seccodeunits[$unit - 48] : $seccodeunits[$unit - 87];
				}
		}
		setnccookie( "seccode".$nchash, encrypt( strtoupper( $seccode )."\t".( time( ) - 180 )."\t".$nchash, MD5_KEY ) );
		return $seccode;
}

function checkSeccode( $nchash, $value )
{
		list( $checkvalue, $checktime, $checkidhash ) = explode( "\t", decrypt( cookie( "seccode".$nchash ), MD5_KEY ) );
		return $checkidhash == $nchash;
}

if ( !defined( "InShopNC" ) )
{
		exit( "Access Invalid!" );
}
?>
