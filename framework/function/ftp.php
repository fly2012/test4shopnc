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

function remote_ftp( $path, $file, $ifdel = TRUE )
{
		$image_full_path = $path."/".$file;
		ftpcmd( "upload", $image_full_path );
		$_ext = ".".get_image_type( $image_full_path );
		if ( !ftpcmd( "error" ) )
		{
				ftpcmd( "upload", $image_full_path."_max".$_ext );
		}
		if ( !ftpcmd( "error" ) )
		{
				ftpcmd( "upload", $image_full_path."_mid".$_ext );
		}
		if ( !ftpcmd( "error" ) )
		{
				ftpcmd( "upload", $image_full_path."_small".$_ext );
		}
		if ( !ftpcmd( "error" ) )
		{
				ftpcmd( "upload", $image_full_path."_tiny".$_ext );
		}
		if ( !ftpcmd( "error" ) )
		{
				ftpcmd( "upload", $image_full_path."_240x240".$_ext );
		}
		if ( !ftpcmd( "error" ) )
		{
				if ( $ifdel )
				{
						@unlink( BasePath."/".$image_full_path );
						@unlink( BasePath."/".$image_full_path."_max".$_ext );
						@unlink( BasePath."/".$image_full_path."_mid".$_ext );
						@unlink( BasePath."/".$image_full_path."_small".$_ext );
						@unlink( BasePath."/".$image_full_path."_tiny".$_ext );
						@unlink( BasePath."/".$image_full_path."_240x240".$_ext );
				}
				return c( "ftp_access_url" )."/".$path;
		}
		return FALSE;
}

function ftpcmd( $cmd, $arg1 = "" )
{
		import( "libraries.ftp" );
		static $ftp = NULL;
		$ftpon = c( "ftp_open" );
		if ( !$ftpon )
		{
				if ( $cmd == "error" )
				{
						return -101;
				}
				return 0;
		}
		if ( $ftp == NULL )
		{
				$ftp =& NcFtp::instance( );
		}
		if ( !$ftp->enabled )
		{
				return $ftp->error( );
		}
		if ( $ftp->enabled && !$ftp->connectid )
		{
				$ftp->connect( );
		}
		switch ( $cmd )
		{
		case "upload" :
				return $ftp->upload( BasePath."/".$arg1, $arg1 );
		case "delete" :
				return $ftp->ftp_delete( $arg1 );
		case "close" :
				return $ftp->ftp_close( );
		case "error" :
				return $ftp->error( );
		case "object" :
				return $ftp;
		}
		return FALSE;
}

function getremotefile( $file )
{
		@set_time_limit( 0 );
		$file = $file."?".time( ).rand( 1000, 9999 );
		$str = @implode( "", @file( $file ) );
		if ( !$str )
		{
				$str = dfsockopen( $file );
		}
		return $str;
}

function dfsockopen( $url, $limit = 0, $post = "", $cookie = "", $bysocket = FALSE, $ip = "", $timeout = 15, $block = TRUE, $encodetype = "URLENCODE", $allowcurl = TRUE )
{
		return _dfsockopen( $url, $limit, $post, $cookie, $bysocket, $ip, $timeout, $block, $encodetype, $allowcurl );
}

function _dfsockopen( $url, $limit = 0, $post = "", $cookie = "", $bysocket = FALSE, $ip = "", $timeout = 15, $block = TRUE, $encodetype = "URLENCODE", $allowcurl = TRUE )
{
		$return = "";
		$matches = parse_url( $url );
		$scheme = $matches['scheme'];
		$host = $matches['host'];
		$path = $matches['path'] ? $matches['path'].( $matches['query'] ? "?".$matches['query'] : "" ) : "/";
		$port = !empty( $matches['port'] ) ? $matches['port'] : 80;
		if ( function_exists( "curl_init" ) && function_exists( "curl_exec" ) && $allowcurl )
		{
				$ch = curl_init( );
				if ( $ip )
				{
						curl_setopt( $ch, CURLOPT_HTTPHEADER, array(
								"Host: ".$host
						) );
				}
				curl_setopt( $ch, CURLOPT_URL, $scheme."://".( $ip ? $ip : $host ).":".$port.$path );
				curl_setopt( $ch, CURLOPT_RETURNTRANSFER, 1 );
				if ( $post )
				{
						curl_setopt( $ch, CURLOPT_POST, 1 );
						if ( $encodetype == "URLENCODE" )
						{
								curl_setopt( $ch, CURLOPT_POSTFIELDS, $post );
						}
						else
						{
								parse_str( $post, &$postarray );
								curl_setopt( $ch, CURLOPT_POSTFIELDS, $postarray );
						}
				}
				if ( $cookie )
				{
						curl_setopt( $ch, CURLOPT_COOKIE, $cookie );
				}
				curl_setopt( $ch, CURLOPT_CONNECTTIMEOUT, $timeout );
				curl_setopt( $ch, CURLOPT_TIMEOUT, $timeout );
				$data = curl_exec( $ch );
				$status = curl_getinfo( $ch );
				$errno = curl_errno( $ch );
				curl_close( $ch );
				if ( $errno || $status['http_code'] != 200 )
				{
						return;
				}
				if ( !$limit )
				{
						return $data;
				}
				return substr( $data, 0, $limit );
		}
		else
		{
				if ( $post )
				{
						$out = "POST ".$path." HTTP/1.0\r\n";
						$header = "Accept: */*\r\n";
						$header .= "Accept-Language: zh-cn\r\n";
						$boundary = $encodetype == "URLENCODE" ? "" : "; boundary=".trim( substr( trim( $post ), 2, strpos( trim( $post ), "\n" ) - 2 ) );
						$header .= $encodetype == "URLENCODE" ? "Content-Type: application/x-www-form-urlencoded\r\n" : "Content-Type: multipart/form-data".$boundary."\r\n";
						$header .= "User-Agent: ".$_SERVER['HTTP_USER_AGENT']."\r\n";
						$header .= "Host: ".$host.":{$port}\r\n";
						$header .= "Content-Length: ".strlen( $post )."\r\n";
						$header .= "Connection: Close\r\n";
						$header .= "Cache-Control: no-cache\r\n";
						$header .= "Cookie: ".$cookie."\r\n\r\n";
						$out .= $header.$post;
				}
				else
				{
						$out = "GET ".$path." HTTP/1.0\r\n";
						$header = "Accept: */*\r\n";
						$header .= "Accept-Language: zh-cn\r\n";
						$header .= "User-Agent: ".$_SERVER['HTTP_USER_AGENT']."\r\n";
						$header .= "Host: ".$host.":{$port}\r\n";
						$header .= "Connection: Close\r\n";
						$header .= "Cookie: ".$cookie."\r\n\r\n";
						$out .= $header;
				}
				$fpflag = 0;
				if ( !( $fp = @fsocketopen( $ip ? $ip : $host, $port, $errno, $errstr, $timeout ) ) )
				{
						$context = array(
								"http" => array(
										"method" => $post ? "POST" : "GET",
										"header" => $header,
										"content" => $post,
										"timeout" => $timeout
								)
						);
						$context = stream_context_create( $context );
						$fp = @fopen( $scheme."://".( $ip ? $ip : $host ).":".$port.$path, "b", FALSE, $context );
						$fpflag = 1;
				}
				if ( !$fp )
				{
						return "";
				}
				stream_set_blocking( $fp, $block );
				stream_set_timeout( $fp, $timeout );
				@fwrite( $fp, $out );
				$status = stream_get_meta_data( $fp );
				if ( !$status['timed_out'] )
				{
						do
						{
						} while ( !feof( $fp ) || !$fpflag || ( !( $header = @fgets( $fp ) ) && !( $header == "\r\n" ) || !( $header == "\n" ) ) );
						$stop = FALSE;
						while ( !feof( $fp ) || !$stop )
						{
								$data = fread( $fp, $limit == 0 || 8192 < $limit ? 8192 : $limit );
								$return .= $data;
								if ( $limit )
								{
										$limit -= strlen( $data );
										$stop = $limit <= 0;
								}
						}
				}
				@fclose( $fp );
		}
		return $return;
}

function fsocketopen( $hostname, $port = 80, &$errno, &$errstr, $timeout = 15 )
{
		$fp = "";
		if ( function_exists( "fsockopen" ) )
		{
				$fp = @fsockopen( $hostname, $port, &$errno, &$errstr, $timeout );
				return $fp;
		}
		if ( function_exists( "pfsockopen" ) )
		{
				$fp = @pfsockopen( $hostname, $port, &$errno, &$errstr, $timeout );
				return $fp;
		}
		if ( function_exists( "stream_socket_client" ) )
		{
				$fp = @stream_socket_client( $hostname.":".$port, &$errno, &$errstr, $timeout );
		}
		return $fp;
}

if ( !defined( "InShopNC" ) )
{
		exit( "Access Invalid!" );
}
?>
