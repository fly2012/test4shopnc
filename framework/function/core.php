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

function setNcCookie( $name, $value, $expire = "3600", $path = "", $domain = "", $secure = FALSE )
{
		if ( empty( $path ) )
		{
				$path = "/";
		}
		if ( empty( $domain ) )
		{
				$domain = c( "subdomain_suffix" ) ? c( "subdomain_suffix" ) : "";
		}
		if ( !defined( "NC_ADMIN" ) && NC_ADMIN === TRUE || $_GET['admin'] == 1 )
		{
				$domain = "";
		}
		$name = defined( "COOKIE_PRE" ) ? COOKIE_PRE.$name : strtoupper( substr( md5( MD5_KEY ), 0, 4 ) )."_".$name;
		$expire = intval( $expire ) ? intval( $expire ) : intval( SESSION_EXPIRE ) ? intval( SESSION_EXPIRE ) : 3600;
		$result = setcookie( $name, $value, time( ) + $expire, $path, $domain, $secure );
		$GLOBALS['_COOKIE'][$name] = $value;
}

function cookie( $name = "" )
{
		$name = defined( "COOKIE_PRE" ) ? COOKIE_PRE.$name : strtoupper( substr( md5( MD5_KEY ), 0, 4 ) )."_".$name;
		return $_COOKIE[$name];
}

function showMessage( $msg, $url = "", $show_type = "html", $msg_type = "succ", $is_show = 1, $time = 2000 )
{
		Language::read( "core_lang_index" );
		$lang = Language::getlangcontent( );
		$url = $url != "" ? $url : getreferer( );
		$msg_type = in_array( $msg_type, array( "succ", "error" ) ) ? $msg_type : "error";
		switch ( $show_type )
		{
		case "json" :
				$return = "{";
				$return .= "\"msg\":\"".$msg."\",";
				$return .= "\"url\":\"".$url."\"";
				$return .= "}";
				echo $return;
				exit( );
		case "exception" :
				echo "<!DOCTYPE html><html><head>";
				echo "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".CHARSET."\" />";
				echo "<title>".SiteUrl."</title>";
				echo "<style type=\"text/css\">body { font-family: \"Verdana\";padding: 0; margin: 0;}h2 { font-size: 12px; line-height: 30px; border-bottom: 1px dashed #CCC; padding-bottom: 8px;width:800px; margin: 20px 0 0 150px;}dl { float: left; display: inline; clear: both; padding: 0; margin: 10px 20px 20px 150px;}dt { font-size: 14px; font-weight: bold; line-height: 40px; color: #333; padding: 0; margin: 0; border-width: 0px;}dd { font-size: 12px; line-height: 40px; color: #333; padding: 0px; margin:0;}</style></head><body>";
				echo "<h2>".$lang['error_info']."</h2>";
				echo "<dl>";
				echo "<dd>".$msg."</dd>";
				echo "<dt><p /></dt>";
				echo "<dd>".$lang['error_notice_operate']."</dd>";
				echo "<dd><p /><p /><p /><p /></dd>";
				echo "<dd><p /><p /><p /><p />Copyright 2007-2013 , All Rights Reserved ".$lang['company_name']."</dd>";
				echo "</dl></body></html>";
				exit( );
		case "javascript" :
				echo "<script>";
				echo "alert('".$msg."');";
				echo "location.href='".$url."'";
				echo "</script>";
				exit( );
		case "tenpay" :
				echo "<html><head><meta name=\"TENCENT_ONLINE_PAYMENT\" content=\"China TENCENT\"><script language=\"javascript\">";
				echo "window.location.href='".$url."';";
				echo "</script></head><body></body></html>";
				exit( );
		}
		if ( is_array( $url ) )
		{
				foreach ( $url as $k => $v )
				{
						$url[$k]['url'] = $v['url'] ? $v['url'] : getreferer( );
				}
		}
		Language::read( "msg" );
		Tpl::setdir( "" );
		Tpl::output( "html_title", Language::get( "nc_html_title" ) );
		Tpl::output( "msg", $msg );
		Tpl::output( "url", $url );
		Tpl::output( "msg_type", $msg_type );
		Tpl::output( "is_show", $is_show );
		Tpl::showpage( "msg", "msg_layout", $time );
		exit( );
}

function showDialog( $message = "", $url = "", $alert_type = "error", $extrajs = "", $time = 2 )
{
		if ( empty( $_GET['inajax'] ) )
		{
				if ( $url == "reload" )
				{
						$url = "";
				}
				showmessage( $message.$extrajs, $url, "html", $alert_type, 1, $time * 1000 );
		}
		$message = str_replace( "'", "\\'", strip_tags( $message ) );
		$paramjs = "";
		if ( $url == "reload" )
		{
				$paramjs = "window.location.reload()";
		}
		else if ( $url != "" )
		{
				$paramjs = "window.location.href ='".$url."'";
		}
		if ( $paramjs )
		{
				$paramjs = "function (){".$paramjs."}";
		}
		else
		{
				$paramjs = "null";
		}
		$modes = array( "error" => "alert", "succ" => "succ", "notice" => "notice", "js" => "js" );
		$cover = $alert_type == "error" ? 1 : 0;
		$extra = '';
		$extra .= "showDialog('".$message."', '".$modes[$alert_type]."', null, ".( $paramjs ? $paramjs : "null" ).", ".$cover.", null, null, null, null, ".( is_numeric( $time ) ? $time : "null" ).", null);";
		$extra = $extra ? "<script type=\"text/javascript\" reload=\"1\">".$extra."</script>" : "";
		if ( $extrajs != "" && substr( trim( $extrajs ), 0, 8 ) != "<script>" )
		{
				$extrajs = "<script type=\"text/javascript\" reload=\"1\">".$extrajs."</script>";
		}
		$extra .= $extrajs;
		ob_end_clean( );
		@header( "Expires: -1" );
		@header( "Cache-Control: no-store, private, post-check=0, pre-check=0, max-age=0", FALSE );
		@header( "Pragma: no-cache" );
		@header( "Content-type: text/xml; charset=".CHARSET );
		$string = "<?xml version=\"1.0\" encoding=\"".CHARSET."\"?>\r\n";
		$string .= "<root><![CDATA[".$message.$extra."]]></root>";
		echo $string;
		exit( );
}

function redirect( $url = "" )
{
		if ( empty( $url ) )
		{
				if ( !empty( $_REQUEST['ref_url'] ) )
				{
						$url = $_REQUEST['ref_url'];
				}
				else
				{
						$url = getreferer( );
				}
		}
		header( "Location: ".$url );
		exit( );
}

function getReferer( )
{
		if ( empty( $_SERVER['HTTP_REFERER'] ) )
		{
				return "";
		}
		return $_SERVER['HTTP_REFERER'];
}

function encrypt( $txt, $key = "" )
{
		if ( empty( $key ) || empty( $txt ) )
		{
				return $txt;
		}
		$chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_.";
		$ikey = "-x6g6ZWm2G9g_vr0Bo.pOq3kRIxsZ6rm";
		$nh1 = rand( 0, 64 );
		$nh2 = rand( 0, 64 );
		$nh3 = rand( 0, 64 );
		$ch1 = $chars[$nh1];
		$ch2 = $chars[$nh2];
		$ch3 = $chars[$nh3];
		$nhnum = $nh1 + $nh2 + $nh3;
		$knum = 0;
		$i = 0;
		while ( isset( $key[$i] ) )
		{
				$knum += ord( $key[$i++] );
		}
		$mdKey = substr( md5( md5( md5( $key.$ch1 ).$ch2.$ikey ).$ch3 ), $nhnum % 8, $knum % 8 + 16 );
		$txt = base64_encode( $txt );
		$txt = str_replace( array( "+", "/", "=" ), array( "-", "_", "." ), $txt );
		$tmp = "";
		$j = 0;
		$k = 0;
		$tlen = strlen( $txt );
		$klen = strlen( $mdKey );
		$i = 0;
		for ( ;	$i < $tlen;	++$i	)
		{
				$k = $k == $klen ? 0 : $k;
				$j = ( $nhnum + strpos( $chars, $txt[$i] ) + ord( $mdKey[$k++] ) ) % 64;
				$tmp .= $chars[$j];
		}
		$tmplen = strlen( $tmp );
		$tmp = substr_replace( $tmp, $ch3, $nh2 % ++$tmplen, 0 );
		$tmp = substr_replace( $tmp, $ch2, $nh1 % ++$tmplen, 0 );
		$tmp = substr_replace( $tmp, $ch1, $knum % ++$tmplen, 0 );
		return $tmp;
}

function decrypt( $txt, $key = "" )
{
		if ( empty( $key ) || empty( $txt ) )
		{
				return $txt;
		}
		$chars = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789-_.";
		$ikey = "-x6g6ZWm2G9g_vr0Bo.pOq3kRIxsZ6rm";
		$knum = 0;
		$i = 0;
		$tlen = strlen( $txt );
		while ( isset( $key[$i] ) )
		{
				$knum += ord( $key[$i++] );
		}
		$ch1 = $txt[$knum % $tlen];
		$nh1 = strpos( $chars, $ch1 );
		$txt = substr_replace( $txt, "", $knum % $tlen--, 1 );
		$ch2 = $txt[$nh1 % $tlen];
		$nh2 = strpos( $chars, $ch2 );
		$txt = substr_replace( $txt, "", $nh1 % $tlen--, 1 );
		$ch3 = $txt[$nh2 % $tlen];
		$nh3 = strpos( $chars, $ch3 );
		$txt = substr_replace( $txt, "", $nh2 % $tlen--, 1 );
		$nhnum = $nh1 + $nh2 + $nh3;
		$mdKey = substr( md5( md5( md5( $key.$ch1 ).$ch2.$ikey ).$ch3 ), $nhnum % 8, $knum % 8 + 16 );
		$tmp = "";
		$j = 0;
		$k = 0;
		$tlen = strlen( $txt );
		$klen = strlen( $mdKey );
		$i = 0;
		for ( ;	$i < $tlen;	++$i	)
		{
				$k = $k == $klen ? 0 : $k;
				$j = strpos( $chars, $txt[$i] ) - $nhnum - ord( $mdKey[$k++] );
				while ( $j < 0 )
				{
						$j += 64;
				}
				$tmp .= $chars[$j];
		}
		$tmp = str_replace( array( "-", "_", "." ), array( "+", "/", "=" ), $tmp );
		return trim( base64_decode( $tmp ) );
}

function getIp( )
{
		if ( $_SERVER['HTTP_CLIENT_IP'] && $_SERVER['HTTP_CLIENT_IP'] != "unknown" )
		{
				$ip = $_SERVER['HTTP_CLIENT_IP'];
				return $ip;
		}
		if ( $_SERVER['HTTP_X_FORWARDED_FOR'] && $_SERVER['HTTP_X_FORWARDED_FOR'] != "unknown" )
		{
				$ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
				return $ip;
		}
		$ip = $_SERVER['REMOTE_ADDR'];
		return $ip;
}

function setTimeZone( $time_zone )
{
		$zonelist = array( "-12" => "Pacific/Kwajalein", "-11" => "Pacific/Samoa", "-10" => "US/Hawaii", "-9" => "US/Alaska", "-8" => "America/Tijuana", "-7" => "US/Arizona", "-6" => "America/Mexico_City", "-5" => "America/Bogota", "-4" => "America/Caracas", "-3.5" => "Canada/Newfoundland", "-3" => "America/Buenos_Aires", "-2" => "Atlantic/St_Helena", "-1" => "Atlantic/Azores", "0" => "Europe/Dublin", "1" => "Europe/Amsterdam", "2" => "Africa/Cairo", "3" => "Asia/Baghdad", "3.5" => "Asia/Tehran", "4" => "Asia/Baku", "4.5" => "Asia/Kabul", "5" => "Asia/Karachi", "5.5" => "Asia/Calcutta", "5.75" => "Asia/Katmandu", "6" => "Asia/Almaty", "6.5" => "Asia/Rangoon", "7" => "Asia/Bangkok", "8" => "Asia/Shanghai", "9" => "Asia/Tokyo", "9.5" => "Australia/Adelaide", "10" => "Australia/Canberra", "11" => "Asia/Magadan", "12" => "Pacific/Auckland" );
		if ( function_exists( "date_default_timezone_set" ) )
		{
				if ( !empty( $zonelist[$time_zone] ) )
				{
						date_default_timezone_set( $zonelist[$time_zone] );
				}
				else
				{
						date_default_timezone_set( "Asia/Shanghai" );
				}
		}
}

function Model($model = NULL)
{
		static $_cache = array();
        
		if ( !is_null( $model ) || isset( $_cache[$model] ) )
		{
			//echo '1';目前缓存有问题,自动重新加载
				//return $_cache[$model];
		}
		$file_name = BasePath.DS."model".DS.$model.".model.php";

		$class_name = $model."Model";
		if ( !file_exists( $file_name ) )
		{
			//echo '2';
				return $_cache[$model] = new Model( $model );
		}
        //var_dump($model);
		require_once($file_name);
		if ( !class_exists( $class_name ) )
		{
				$error = "Model Error:  Class ".$class_name." is not exists!";
				throw_exception( $error );
		}
		else
		{
		}
		return $_cache[$model] = new $class_name( );
}

function readDirList( $path )
{
		if ( is_dir( $path ) )
		{
				$handle = @opendir( $path );
				$dir_list = array( );
				if ( $handle )
				{
						while ( FALSE !== ( $dir = readdir( $handle ) ) )
						{
								if ( ( $dir != "." ) && ( $dir != ".." ) && is_dir( $path.DS.$dir ) )
								{
									$dir_list[] = $dir;
								}
						}
						return $dir_list;
				}
				return FALSE;
		}
		return FALSE;
}

function replaceSpecialChar( $string )
{
		$str = str_replace( "\r\n", "", $string );
		$str = str_replace( "\t", "    ", $string );
		$str = str_replace( "\n", "", $string );
		return $string;
}

function showEditor( $id, $value = "", $width = "700px", $height = "300px", $style = "visibility:hidden;", $upload_state = "true", $media_open = FALSE )
{
		$media = "";
		if ( $media_open )
		{
				$media = ", 'flash', 'media'";
		}
		$items = "['source', '|', 'fullscreen', 'undo', 'redo', 'print', 'cut', 'copy', 'paste',\r\n\t\t\t'plainpaste', 'wordpaste', '|', 'justifyleft', 'justifycenter', 'justifyright',\r\n\t\t\t'justifyfull', 'insertorderedlist', 'insertunorderedlist', 'indent', 'outdent', 'subscript',\r\n\t\t\t'superscript', '|', 'selectall', 'clearhtml','quickformat','|',\r\n\t\t\t'formatblock', 'fontname', 'fontsize', '|', 'forecolor', 'hilitecolor', 'bold',\r\n\t\t\t'italic', 'underline', 'strikethrough', 'lineheight', 'removeformat', '|', 'image'".$media.", 'table', 'hr', 'emoticons', 'link', 'unlink', '|', 'about']";
		echo "<textarea id=\"".$id."\" name=\"".$id."\" style=\"width:".$width.";height:".$height.";".$style."\">".$value."</textarea>";
		echo "\r\n<script src=\"".RESOURCE_PATH."/kindeditor/kindeditor-min.js\" charset=\"utf-8\"></script>\r\n<script src=\"".RESOURCE_PATH."/kindeditor/lang/zh_CN.js\" charset=\"utf-8\"></script>\r\n<script>\r\n\tvar KE;\r\n  KindEditor.ready(function(K) {\r\n        KE = K.create(\"textarea[name='".$id."']\", {\r\n\t\t\t\t\t\titems : ".$items.",\r\n\t\t\t\t\t\tcssPath : \"".RESOURCE_PATH."/kindeditor/themes/default/default.css\",\r\n\t\t\t\t\t\tallowImageUpload : ".$upload_state.",\r\n\t\t\t\t\t\tallowFlashUpload : false,\r\n\t\t\t\t\t\tallowMediaUpload : false,\r\n\t\t\t\t\t\tallowFileManager : false,\r\n\t\t\t\t\t\tsyncType:\"form\",\r\n\t\t\t\t\t\tafterCreate : function() {\r\n\t\t\t\t\t\t\tvar self = this;\r\n\t\t\t\t\t\t\tself.sync();\r\n\t\t\t\t\t\t},\r\n\t\t\t\t\t\tafterChange : function() {\r\n\t\t\t\t\t\t\tvar self = this;\r\n\t\t\t\t\t\t\tself.sync();\r\n\t\t\t\t\t\t},\r\n\t\t\t\t\t\tafterBlur : function() {\r\n\t\t\t\t\t\t\tvar self = this;\r\n\t\t\t\t\t\t\tself.sync();\r\n\t\t\t\t\t\t}\r\n        });\r\n\t\t\tKE.appendHtml = function(id,val) {\r\n\t\t\t\tthis.html(this.html() + val);\r\n\t\t\t\tif (this.isCreated) {\r\n\t\t\t\t\tvar cmd = this.cmd;\r\n\t\t\t\t\tcmd.range.selectNodeContents(cmd.doc.body).collapse(false);\r\n\t\t\t\t\tcmd.select();\r\n\t\t\t\t}\r\n\t\t\t\treturn this;\r\n\t\t\t}\r\n\t});\r\n</script>\r\n\t";
		return TRUE;
}

function getDirSize( $path, $size = 0 )
{
		$dir = @dir( $path );
		while ( !empty( $dir->path ) || !empty( $dir->handle ) || ( $filename = $dir->read( ) ) )
		{
				if ( !( $filename != "." ) && !( $filename != ".." ) )
				{
						if ( is_dir( $path.DS.$filename ) )
						{
								$size += getdirsize( $path.DS.$filename );
						}
						else
						{
								$size += filesize( $path.DS.$filename );
						}
				}
		}
		if ( $size )
		{
				return $size;
		}
		return 0;
}

function delCacheFile( $dir )
{
		if ( strpos( $dir, ".." ) !== FALSE )
		{
				return FALSE;
		}
		$path = BasePath.DS."cache".DS.$dir;
		if ( is_dir( $path ) )
		{
				$file_list = array( );
				readFileList( $path, $file_list );
				if ( !empty( $file_list ) )
				{
						foreach ( $file_list as $v )
						{
								if ( basename( $v ) != "index.html" )
								{
										@unlink( $v );
								}
						}
						return TRUE;
				}
		}
		else if ( basename( $path ) != "index.html" )
		{
				@unlink( $path );
		}
		return TRUE;
}

function readFileList( $path, &$file_list, $ignore_dir = array( ) )
{
		$path = rtrim( $path, "/" );
		if ( is_dir( $path ) )
		{
				$handle = @opendir( $path );
				if ( $handle )
				{
						while ( FALSE !== ( $dir = readdir( $handle ) ) )
						{
								if (  $dir != "."  &&  $dir != ".."  && in_array( $dir, $ignore_dir ) == false)
								{
										if ( is_file( $path.DS.$dir ) )
										{
												$file_list[] = $path.DS.$dir;
										}
										else if ( is_dir( $path.DS.$dir ) )
										{
												readFileList( $path.DS.$dir, $file_list, $ignore_dir );
										}
								}
						}
						@closedir( $handle );
				}
				else
				{
						return FALSE;
				}
		}
		return FALSE;
}

function ncPriceFormat( $price )
{
		$price_format = number_format( $price, 2, ".", "" );
		return $price_format;
}

function ncUrl( $param = array( ), $type = "", $domain = "" )
{
		if ( !is_array( $param ) && empty( $param ) )
		{
				return SiteUrl;
		}
		$array = array( "coupon_info", "goods", "groupbuy", "article", "store", "activity", "store_nav", "document" );
		if ( $GLOBALS['setting_config']['rewrite_enabled'] == "1" && in_array( $type, $array ) )
		{
				$url = $type."-";
				foreach ( $param as $key => $val )
				{
						if ( !( $key != "act" ) && !( $key != "op" ) )
						{
								$url .= $val."-";
						}
				}
				$url = rtrim( $url, "-" ).".html";
		}
		else
		{
				$url = "index.php?";
				foreach ( $param as $key => $val )
				{
						$url .= $key."=".$val."&";
				}
				$url = rtrim( $url, "&" );
		}
		if ( !empty( $domain ) )
		{
				$url = ncdomainurl( $type, $url, $domain );
		}
		return $url;
}

function ncDomainUrl( $type, $domain_url, $domain )
{
		if ( $GLOBALS['setting_config']['enabled_subdomain'] == "1" && !empty( $domain ) )
		{
				switch ( $type )
				{
				case "store" :
						$domain_url = "http://".$domain.".".$GLOBALS['setting_config']['subdomain_suffix']."/";
						return $domain_url;
				case "goods" :
						$domain_url = "http://".$domain.".".$GLOBALS['setting_config']['subdomain_suffix']."/".$domain_url;
				}
		}
		return $domain_url;
}

function subdomain( )
{
		$store_id = 0;
		if ( $GLOBALS['setting_config']['enabled_subdomain'] == "1" )
		{
				$line = @explode( $GLOBALS['setting_config']['subdomain_suffix'], $_SERVER['HTTP_HOST'] );
				$line = trim( $line[0], "." );
				if ( empty( $line ) || strtolower( $line ) == "www" )
				{
						return 0;
				}
				$model_store = model( "store" );
				$store_info = $model_store->shopStore( array(
						"store_domain" => $line
				), "store_id" );
				if ( 0 < $store_info['store_id'] )
				{
						$store_id = $store_info['store_id'];
						$GLOBALS['_GET']['id'] = $store_info['store_id'];
				}
		}
		return $store_id;
}

function ncReplaceText( $message, $param )
{
		if ( !is_array( $param ) )
		{
				return FALSE;
		}
		$param['mail_send_time'] = date( "Y-m-d H:i" );
		foreach ( $param as $k => $v )
		{
				$message = str_replace( "{\$".$k."}", $v, $message );
		}
		return $message;
}
/**
 * 字符截取 支持UTF8/GBK
 * @param $string
 * @param $length
 * @param $dot
 */
/*
function str_cut($string, $length, $dot = '') {
	$strlen = strlen($string);
	if($strlen/2 <= $length) return $string;
	$string = str_replace(array(' ',' ',' ',' ', '&', '"', '\'', '??, '??, '??, '<', '>', '·', '??), array(' ',' ',' ',' ', '&', '"', "'", '??, '??, '??, '<', '>', '·', '??), $string);
        $strcut = '';
        $n = $tn = $noc = 0;
        if(strtolower(CHARSET) == 'utf-8') {
                while($n < $strlen) {
                        $t = ord($string[$n]);
                        if($t == 9 || $t == 10 || (32 <= $t && $t <= 126)) {
                                $tn = 1; ++$n; $noc += 0.5;
                        } elseif(194 <= $t && $t <= 223) {
                                $tn = 2; $n += 2; $noc += 1;
                        } elseif(224 <= $t && $t <= 239) {
                                $tn = 3; $n += 3; $noc += 1;
                        } elseif(240 <= $t && $t <= 247) {
                                $tn = 4; $n += 4; $noc += 1;
                        } elseif(248 <= $t && $t <= 251) {
                                $tn = 5; $n += 5; $noc += 1;
                        } elseif($t == 252 || $t == 253) {
                                $tn = 6; $n += 6; $noc += 1;
                        } else {
                                ++$n;
                        }
                        if($noc >= $length) {
                                if($n < $strlen) ++$noc;
                                break;
                        }
                }
        } else {
                while($n < $strlen) {
                        if(ord($string[$n]) > 127) {
                                $tn = 2; $n += 2; $noc += 1;
                        } else{
                                $tn = 1; ++$n; $noc += 0.5;
                        }
                        if($noc >= $length) {
                                if($n < $strlen) ++$noc;
                                break;
                        }
                }
        }
        if($noc > $length && !empty($dot)) {
                $n -= $tn;
                $strcut = substr($string, 0, $n);
                $strcut .= $dot;
        }else{
                $strcut = substr($string, 0, $n);
        }
        $strcut = str_replace(array('&', '"', "'", '??, '??, '??, '<', '>', '·', '??), array('&', '"', '\'', '??, '??, '??, '<', '>', '·', '??), $strcut);
        return $strcut;
}*/

function str_cut( $string, $length, $dot = "" )
{
		$string = str_replace(
			array( "&nbsp;", "&amp;", "&quot;", "&#039;", "&ldquo;", "&rdquo;", "&mdash;", "&lt;", "&gt;", "&middot;", "&hellip;" ),
			//array( " ", "&", "\"", "'", "??, "??, "??, "<", ">", "·", """),
			array(' ','&','"',"'", '“', '”', '—', '<', '>', '·', '…'),
			$string
		);
//array(' ','        ','　',' ', '&', '"', ''', '“', '”', '—', '<', '>', '·', '…'),
//array(' ',' ',' ',' ', '&', '"', "'", '“', '”', '—', '<', '>', '·', '…')
		$strlen = strlen( $string );
		if ( $strlen <= $length )
		{
				return $string;
		}
		$maxi = $length - strlen( $dot );
		$strcut = "";
		if ( strtolower( CHARSET ) == "utf-8" )
		{
				$n = $tn = $noc = 0;
				while ( $n < $strlen )
				{
						$t = ord( $string[$n] );
						if ( $t == 9 || $t == 10 || 32 <= $t && $t <= 126 )
						{
								$tn = 1;
								++$n;
								++$noc;
						}
						else if ( 194 <= $t && $t <= 223 )
						{
								$tn = 2;
								$n += 2;
								$noc += 2;
						}
						else if ( 224 <= $t && $t < 239 )
						{
								$tn = 3;
								$n += 3;
								$noc += 2;
						}
						else if ( 240 <= $t && $t <= 247 )
						{
								$tn = 4;
								$n += 4;
								$noc += 2;
						}
						else if ( 248 <= $t && $t <= 251 )
						{
								$tn = 5;
								$n += 5;
								$noc += 2;
						}
						else if ( $t == 252 || $t == 253 )
						{
								$tn = 6;
								$n += 6;
								$noc += 2;
						}
						else
						{
								++$n;
						}
						if ( !( $maxi <= $noc ) )
						{
								continue;
						}
						break;
				}
				if ( $maxi < $noc )
				{
						$n -= $tn;
				}
				$strcut = substr( $string, 0, $n );
		}
		else
		{
				$dotlen = strlen( $dot );
				$maxi = $length - $dotlen;
				$i = 0;
				for ( ;	$i < $maxi;	++$i	)
				{
						$strcut .= 127 < ord( $string[$i] ) ? $string[$i].$string[++$i] : $string[$i];
				}
		}
		$strcut = str_replace( array( "&", "\"", "'", "<", ">" ), array( "&amp;", "&quot;", "&#039;", "&lt;", "&gt;" ), $strcut );
		return $strcut.$dot;
}

function unicodeToUtf8( $str, $order = "little" )
{
		$utf8string = "";
		$n = strlen( $str );
		$i = 0;
		for ( ;	$i < $n;	++$i	)
		{
				if ( $order == "little" )
				{
						$val = str_pad( dechex( ord( $str[$i + 1] ) ), 2, 0, STR_PAD_LEFT ).str_pad( dechex( ord( $str[$i] ) ), 2, 0, STR_PAD_LEFT );
				}
				else
				{
						$val = str_pad( dechex( ord( $str[$i] ) ), 2, 0, STR_PAD_LEFT ).str_pad( dechex( ord( $str[$i + 1] ) ), 2, 0, STR_PAD_LEFT );
				}
				$val = intval( $val, 16 );
				++$i;
				$c = "";
				if ( $val < 127 )
				{
						$c .= chr( $val );
				}
				else if ( $val < 2048 )
				{
						$c .= chr( 192 | $val / 64 );
						$c .= chr( 128 | $val % 64 );
				}
				else
				{
						$c .= chr( 224 | $val / 64 / 64 );
						$c .= chr( 128 | $val / 64 % 64 );
						$c .= chr( 128 | $val % 64 );
				}
				$utf8string .= $c;
		}
		if ( ord( substr( $utf8string, 0, 1 ) ) == 239 && ord( substr( $utf8string, 1, 2 ) ) == 187 && ord( substr( $utf8string, 2, 1 ) ) == 191 )
		{
				$utf8string = substr( $utf8string, 3 );
		}
		return $utf8string;
}

function request_uri( )
{
		if ( isset( $_SERVER['REQUEST_URI'] ) )
		{
				$uri = $_SERVER['REQUEST_URI'];
				return $uri;
		}
		if ( isset( $_SERVER['argv'] ) )
		{
				$uri = $_SERVER['PHP_SELF']."?".$_SERVER['argv'][0];
				return $uri;
		}
		$uri = $_SERVER['PHP_SELF']."?".$_SERVER['QUERY_STRING'];
		return $uri;
}

function get_image_type( $str )
{
		$a = explode( ".", $str );
		return end( $a );
}

function addUpTime( $start, $end = "", $dec = 3 )
{
		static $_info = array( );
		if ( !empty( $end ) )
		{
				if ( !isset( $_info[$end] ) )
				{
						$_info[$end] = microtime( TRUE );
				}
				return number_format( $_info[$end] - $_info[$start], $dec );
		}
		$_info[$start] = microtime( TRUE );
}

function C( $key )
{
		if ( strpos( $key, "." ) )
		{
				$key = explode( ".", $key );
				if ( isset( $key[2] ) )
				{
						return $GLOBALS['setting_config'][$key[0]][$key[1]][$key[2]];
				}
				return $GLOBALS['setting_config'][$key[0]][$key[1]];
		}
		return $GLOBALS['setting_config'][$key];
}

function defaultGoodsImage( $key )
{
		return ATTACH_COMMON.DS.c( "default_goods_image" )."_".$key.".".get_image_type( c( "default_goods_image" ) );
}

function getMemberAvatar( $member_avatar )
{
		if ( empty( $member_avatar ) )
		{
				return SiteUrl.DS.ATTACH_COMMON.DS.c( "default_user_portrait" );
		}
		if ( file_exists( BasePath.DS.ATTACH_AVATAR.DS.$member_avatar ) )
		{
				return SiteUrl.DS.ATTACH_AVATAR.DS.$member_avatar;
		}
		return SiteUrl.DS.ATTACH_COMMON.DS.c( "default_user_portrait" );
}

function getStoreLogo( $store_logo )
{
		if ( empty( $store_logo ) )
		{
				return SiteUrl.DS.ATTACH_COMMON.DS.$GLOBALS['setting_config']['default_store_logo'];
		}
		if ( file_exists( BasePath.DS.ATTACH_STORE.DS.$store_logo ) )
		{
				return SiteUrl.DS.ATTACH_STORE.DS.$store_logo;
		}
		return SiteUrl.DS.ATTACH_COMMON.DS.$GLOBALS['setting_config']['default_store_logo'];
}

function getMicroshopUrl( )
{
		$microshop_url = c( "microshop_url" );
		if ( !empty( $microshop_url ) )
		{
				return $microshop_url;
		}
		return SiteUrl.DS."modules".DS."microshop";
}

function getMicroshopPersonalImageUrl( $personal_info, $type = "" )
{
		$ext_array = array( "list", "tiny" );
		$personal_image_array = array( );
		$personal_image_list = explode( ",", $personal_info['commend_image'] );
		if ( !empty( $personal_image_list ) )
		{
				if ( is_array( $personal_image_list ) )
				{
						foreach ( $personal_image_list as $value )
						{
								if ( !empty( $type ) || in_array( $type, $ext_array ) )
								{
										$file_name = explode( ".", $value );
										$ext = end( $file_name );
										$value .= "_".$type.".".$ext;
								}
								$personal_image_array[] = SiteUrl.DS.ATTACH_MICROSHOP.DS.$personal_info['commend_member_id'].DS.$value;
						}
						return $personal_image_array;
				}
				if ( !empty( $type ) || in_array( $type, $ext_array ) )
				{
						$file_name = explode( ".", $personal_info['commend_image'] );
						$ext = end( $file_name );
						$personal_info['commend_image'] .= "_".$type.".".$ext;
				}
				$personal_image_array[] = SiteUrl.DS.ATTACH_MICROSHOP.DS.$personal_info['commend_member_id'].DS.$personal_info['commend_image'];
		}
		return $personal_image_array;
}

function import( $libname, $file_ext = ".php" )
{
		if ( strstr( $libname, "." ) )
		{
				$path = str_replace( ".", "/", $libname );
		}
		else
		{
				$path = "libraries/".$libname;
		}
		if ( substr( $libname, 0, 1 ) == "." )
		{
				$base_dir = BasePath."/";
				$path = ltrim( str_replace( "libraries/", "", $path ), "/" );
		}
		else
		{
				$base_dir = BasePath."/framework/";
		}
		if ( strstr( $path, "#" ) )
		{
				$path = str_replace( "#", ".", $path );
		}
		if ( preg_match( "/^[\\w\\d\\/_.]+\$/i", $path ) )
		{
				$file = realpath( $base_dir.$path.$file_ext );
		}
		else
		{
				$file = FALSE;
		}
		if ( !$file )
		{
				exit( $path.$file_ext." isn't exists!" );
		}
		require_once( $file );
}

function random( $length, $numeric = 0 )
{
		$seed = base_convert( md5( microtime( ).$_SERVER['DOCUMENT_ROOT'] ), 16, $numeric ? 10 : 35 );
		$seed = $numeric ? str_replace( "0", "", $seed )."012340567890" : $seed."zZ".strtoupper( $seed );
		$hash = "";
		$max = strlen( $seed ) - 1;
		$i = 0;
		for ( ;	$i < $length;	++$i	)
		{
				$hash .= $seed[mt_rand( 0, $max )];
		}
		return $hash;
}

function template( $tplpath, $project = "" )
{
		if ( $project == "" )
		{
				return BasePath."/templates/".TPL_NAME."/".$tplpath.".php";
		}
		return BasePath."/".$project."/templates/".$tplpath.".php";
}

function chksubmit( )
{
		$submit = isset( $_POST['form_submit'] ) ? $_POST['form_submit'] : $_GET['form_submit'];
		if ( $submit == "ok" )
		{
				return TRUE;
		}
		return FALSE;
}

function parsesmiles( $message )
{
		$smilescache_file = BasePath.DS."data".DS."smilies".DS."smilies.php";
		if ( file_exists( $smilescache_file ) )
		{
				include( $smilescache_file );
				if ( strtoupper( CHARSET ) == "GBK" )
				{
						$smilies_array = Language::getgbk( $smilies_array );
				}
				if ( !empty( $smilies_array ) || is_array( $smilies_array ) )
				{
						$imagesurl = RESOURCE_PATH.DS."js".DS."smilies".DS."images".DS;
						$replace_arr = array( );
						foreach ( $smilies_array['replacearray'] as $key => $smiley )
						{
								$replace_arr[$key] = "<img src=\"".$imagesurl.$smiley['imagename']."\" title=\"".$smiley['desc']."\" border=\"0\" alt=\"".$imagesurl.$smiley['desc']."\" />";
						}
						$message = preg_replace( $smilies_array['searcharray'], $replace_arr, $message );
				}
		}
		return $message;
}

function showValidateError( $error )
{
		if ( !empty( $_GET['inajax'] ) )
		{
				foreach ( explode( "<br/>", $error ) as $v )
				{
						if ( trim( $v != "" ) )
						{
								showdialog( $v, "", "error", "", 3 );
						}
				}
		}
		else
		{
				showdialog( $error, "", "error", "", 3 );
		}
}

function check_repeat( $key, $ttl = 30 )
{
		$key = "rp_".$key;
		if ( !is_numeric( $ttl ) )
		{
				$ttl = 30;
		}
		if ( !is_numeric( cookie( $key ) ) )
		{
				$return = FALSE;
		}
		else if ( intval( time( ) - cookie( $key ) ) < $ttl )
		{
				$return = TRUE;
		}
		else
		{
				$return = FALSE;
		}
		setnccookie( $key, time( ) );
		return $return;
}

function log_times( $key, $op = "cookie", $expire = "" )
{
		$key = "tm_".$key;
		if ( $op == "cookie" )
		{
				$value = !is_numeric( cookie( $key ) ) ? 1 : intval( cookie( $key ) ) + 1;
				setnccookie( $key, $value, $expire );
				return $value;
		}
		if ( $op == "session" )
		{
				$value = NULL;
				return $value;
		}
		if ( $op == "memcache" )
		{
				$value = NULL;
		}
		return $value;
}

function lazypage( $delay_eachnum, $delay_page, $count, $ispage = FALSE, $page_nowpage = 1, $page_eachnum = 1, $page_limitstart = 1 )
{
		$hasmore = TRUE;
		$limitstart = 0;
		if ( $ispage )
		{
				do
				{
						if ( $delay_eachnum < $page_eachnum )
						{
								$page_totlepage = ceil( $count / $page_eachnum );
								$limitstart = $page_limitstart + ( $delay_page - 1 ) * $delay_eachnum;
								if ( $page_nowpage < $page_totlepage )
								{
										if ( $page_eachnum / $delay_eachnum <= $delay_page )
										{
												$hasmore = FALSE;
										}
										if ( !$hasmore )
										{
												if ( !( 0 < $page_eachnum % $delay_eachnum ) )
												{
														break;
												}
												else
												{
														$delay_eachnum = $page_eachnum % $delay_eachnum;
												}
										}
								}
								else
								{
										$showcount = ( $page_totlepage - 1 ) * $page_eachnum + $delay_eachnum * $delay_page;
										if ( !( $count <= $showcount ) )
										{
												break;
										}
										$hasmore = FALSE;
								}
						}
						else
						{
								$hasmore = FALSE;
						}
				}while ( 0 );
				/*
				else
				{
						if ( $count <= $delay_page * $delay_eachnum )
						{
								$hasmore = FALSE;
						}
						$limitstart = ( $delay_page - 1 ) * $delay_eachnum;
				} while ( 0 );*/
		}
		return array(
				"hasmore" => $hasmore,
				"limitstart" => $limitstart,
				"delay_eachnum" => $delay_eachnum
		);
}

function F( $name, $value = "", $path = "cache", $ext = ".php" )
{
		static $_cache = array( );
		if ( isset( $_cache[$name.$path] ) )
		{
				return $_cache[$name.$path];
		}
		$filename = BasePath."/".$path."/".$name.$ext;
		if ( !empty( $value ) )
		{
				$dir = dirname( $filename );
				if ( !is_dir( $dir ) )
				{
						mkdir( $dir );
				}
				return write_file( $filename, $value );
		}
		if ( is_file( $filename ) )
		{
				$value = include( $filename );
				$_cache[$name.$path] = $value;
				return $value;
		}
		$value = FALSE;
		return $value;
}

function write_file( $filepath, $data, $mode = NULL )
{
		if ( is_array( $data ) )
		{
				$data = var_export( $data, TRUE );
		}
		else if ( !is_scalar( $data ) )
		{
				return FALSE;
		}
		$data = "<?php defined('InShopNC') or exit('Access Invalid!'); return ".$data."\n?>";
		$mode = $mode == "append" ? FILE_APPEND : NULL;
		if ( FALSE === file_put_contents( $filepath, compress_code( $data ), $mode ) )
		{
				return FALSE;
		}
		return TRUE;
}

function mk_dir( $dir, $mode = "0777" )
{
		if ( is_dir( $dir ) || @mkdir( $dir, $mode ) )
		{
				return TRUE;
		}
		if ( !mk_dir( dirname( $dir ), $mode ) )
		{
				return FALSE;
		}
		return mkdir( $dir, $mode );
}

function pagecmd( $cmd = "", $arg = "" )
{
		if ( !class_exists( "page" ) )
		{
				import( "page" );
		}
		static $page = NULL;
		if ( $page == NULL )
		{
				$page = new Page( );
		}
		switch ( strtolower( $cmd ) )
		{
		case "seteachnum" :
				$page->setEachNum( $arg );
				break;
		case "settotalnum" :
				$page->setTotalNum( $arg );
				break;
		case "setstyle" :
				$page->setStyle( $arg );
				break;
		case "show" :
				return $page->show( $arg );
		case "obj" :
				return $page;
		case "gettotalnum" :
				return $page->getTotalNum( );
		case "gettotalpage" :
				return $page->getTotalPage( );
		}
}

function throw_exception( $error )
{
		showmessage( $error, "", "exception" );
}

function halt( $error )
{
		showmessage( $error, "", "exception" );
}

function compress_code( $content )
{
		$stripStr = "";
		$tokens = token_get_all( $content );
		$last_space = FALSE;
		$i = 0;
		$j = count( $tokens );
		for ( ;	$i < $j;	++$i	)
		{
				if ( is_string( $tokens[$i] ) )
				{
						$last_space = FALSE;
						$stripStr .= $tokens[$i];
				}
				else
				{
						switch ( $tokens[$i][0] )
						{
						case T_COMMENT :
						case T_DOC_COMMENT :
						case T_WHITESPACE :
								if ( $last_space )
								{
										break;
								}
								$stripStr .= " ";
								$last_space = TRUE;
								break;
						default :
								$last_space = FALSE;
								$stripStr .= $tokens[$i][1];
						}
				}
		}
		return $stripStr;
}

function get_obj_instance( $class, $method = "", $args = array( ) )
{
		static $_cache = array( );
		$key = $class.$method.( empty( $args ) ? NULL : md5( serialize( $args ) ) );
		if ( isset( $_cache[$key] ) )
		{
				return $_cache[$key];
		}
		if ( class_exists( $class ) )
		{
				$obj = new $class( );
				if ( method_exists( $obj, $method ) )
				{
						if ( empty( $args ) )
						{
								$_cache[$key] = $obj->$method( );
						}
						else
						{
								$_cache[$key] = call_user_func_array( array(
										$obj,
										$method
								), $args );
						}
				}
				else
				{
						$_cache[$key] = $obj;
				}
				return $_cache[$key];
		}
		throw_exception( "Class ".$class." isn't exists!" );
}

function H( $key, $value = "", $cache_type = "", $expire = NULL, $args = NULL )
{
		static $cache = array( );
		$cache_type = $cache_type ? $cache_type : "file";
		$obj_cache = Cache::getinstance( $cache_type, $args );
		if ( $value !== "" )
		{
				if ( is_null( $value ) )
				{
						$result = $obj_cache->rm( $key );
						if ( $result )
						{
								unset( $cache[$cache_type."_".$key] );
						}
						return $result;
				}
				if ( $value === TRUE )
				{
						$obj_cache->rm( $key );
				}
				$list = model( "cache" )->call( $key );
				$obj_cache->set( $key, $list, NULL, $expire );
				$cache[$cache_type."_".$key] = $list;
				if ( $value === TRUE )
				{
						return $list;
				}
				return TRUE;
		}
		if ( isset( $cache[$cache_type."_".$key] ) )
		{
				return $cache[$cache_type."_".$key];
		}
		$value = $obj_cache->get( $key );
		$cache[$cache_type."_".$key] = $value;
		return $value;
}

function array_under_reset( $array, $key, $type = 1 )
{
		if ( is_array( $array ) )
		{
				$tmp = array( );
				foreach ( $array as $v )
				{
						if ( $type === 1 )
						{
								$tmp[$v[$key]] = $v;
						}
						else if ( $type === 2 )
						{
								$tmp[$v[$key]][] = $v;
						}
				}
				return $tmp;
		}
		return $array;
}

function rcache( $key = NULL, $prefix = "", $unserialize = TRUE )
{
		if ( empty( $key ) || c( "cache.type" ) == "file" )
		{
				return FALSE;
		}
		$obj_cache = Cache::getinstance( c( "cache.type" ) );
		$data = $obj_cache->get( $key, $prefix );
		if ( $unserialize )
		{
				return unserialize( $data );
		}
		return $data;
}

function wcache( $key = NULL, $data = array( ), $prefix = "", $ttl = 0, $serialize = TRUE )
{
		if ( empty( $key ) || c( "cache.type" ) == "file" )
		{
				return FALSE;
		}
		$obj_cache = Cache::getinstance( c( "cache.type" ), $args );
		if ( $ttl !== 0 )
		{
				$ttl = c( "session_expire" );
		}
		$obj_cache->set( $key, $serialize ? serialize( $data ) : $data, $prefix, $ttl );
		return TRUE;
}

function rec( $rec_id = NULL )
{
		if ( !is_numeric( $rec_id ) )
		{
				return;
		}
		$string = "";
		if ( !( $info = f( $rec_id, "", "cache/rec_position" ) ) )
		{
				$model = model( "rec_position" );
				$info = $model->where( array(
						"rec_id" => $rec_id
				) )->find( );
				if ( !f( $rec_id, $info, "cache/rec_position" ) )
				{
				}
		}
		else
		{
				$info['content'] = unserialize( $info['content'] );
				if ( $info['content']['target'] == 2 )
				{
						$target = "target=\"_blank\"";
				}
				else
				{
						$target = "";
				}
				if ( $info['pic_type'] == 0 )
				{
						foreach ( ( array )$info['content']['body'] as $v )
						{
								$href = "";
								if ( $v['url'] != "" )
								{
										$href = "href=\"".$v['url']."\"";
								}
								$string .= "<li><a ".$target." {$href}>{$v['title']}</a></li>";
						}
				}
				else
				{
						$width = $height = "";
						if ( is_numeric( $info['content']['width'] ) )
						{
								$width = "width=\"".$info['content']['width']."\"";
						}
						if ( is_numeric( $info['content']['height'] ) )
						{
								$height = "height=\"".$info['content']['height']."\"";
						}
						foreach ( ( array )$info['content']['body'] as $v )
						{
								if ( $info['pic_type'] == 1 )
								{
										$v['title'] = str_replace( "SiteUrl", SiteUrl, $v['title'] );
								}
								$href = "";
								if ( $v['url'] != "" )
								{
										$href = "href=\"".$v['url']."\"";
								}
								$string .= "<li><a ".$target." {$href}><img {$width} {$height} src=\"{$v['title']}\"></a></li>";
						}
				}
		}
		return "<ul>".$string."</ul>";
}

function L( $key = "" )
{
		if ( class_exists( "Language" ) )
		{
				return Language::get( $key );
		}
}

if ( !defined( "InShopNC" ) )
{
		exit( "Access Invalid!" );
}
if ( !function_exists( "memory_get_usage" ) )
{
		function memory_get_usage( )
		{
				return 0;
		}
}
?>
