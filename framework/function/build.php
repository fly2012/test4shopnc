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

function build( )
{
		$args = func_get_args( );
		extract( $args[0] );
		$compile = "";
		$list = array(
				BasePath."/framework/core/runtime.php",
				BasePath."/framework/core/base.php",
				BasePath."/framework/core/db.php",
				BasePath."/framework/core/model.php",
				BasePath."/framework/cache/cache.php",
				BasePath."/framework/cache/cache.file.php",
				BasePath."/framework/db/".strtolower( $dbdriver ).".php",
				BasePath."/framework/function/goods.php",
				BasePath."/framework/function/seccode.php",
				BasePath."/framework/libraries/email.php",
				BasePath."/framework/libraries/language.php",
				BasePath."/framework/libraries/log.php",
				BasePath."/framework/libraries/page.php",
				BasePath."/framework/libraries/security.php",
				BasePath."/framework/libraries/validate.php",
				BasePath."/framework/libraries/upload.php",
				BasePath."/framework/function/core.php",
				BasePath."/framework/tpl/nc.php",
				BasePath."/control/control.php",
				BasePath."/language/".$lang_type."/core_lang_index.php"
		);
		if ( !empty( $cache_type ) || strtolower( $cache_type ) != "file" )
		{
				$list[] = BasePath."/framework/cache/cache.".strtolower( $cache_type ).".php";
		}
		foreach ( $list as $file )
		{
				if ( file_exists( $file ) )
				{
						$compile .= compile( $file );
				}
				else
				{
						exit( str_replace( BasePath, "", $file )." isn't exists!" );
				}
		}
		$compile .= compile( $lang_file );
		$compile .= "\nLanguage::appendLanguage(\$lang);";
		$compile .= "\nBase::run();";
		file_put_contents( RUNCOREPATH, compress_code( "<?php defined('InShopNC') or exit('Access Invalid!');".$compile ) );
}

function compile( $filename )
{
		$content = file_get_contents( $filename );
		$content = preg_replace( "/\\/\\/\\[NC_SKIPBUILD\\](.*?)\\/\\/\\[\\/NC_SKIPBUILD\\]/s", "", $content );
		$content = str_ireplace( "defined('InShopNC') or exit('Access Invalid!')", "", $content );
		$content = substr( trim( $content ), 5 );
		if ( "?>" == substr( $content, -2 ) )
		{
				$content = substr( $content, 0, -2 );
		}
		return $content;
}

function compress_code( $content )
{
		$strip_str = "";
		$tokens = token_get_all( $content );
		$last_space = FALSE;
		$i = 0;
		$j = count( $tokens );
		for ( ;	$i < $j;	++$i	)
		{
				if ( is_string( $tokens[$i] ) )
				{
						$last_space = FALSE;
						$strip_str .= $tokens[$i];
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
								$strip_str .= " ";
								$last_space = TRUE;
								break;
						default :
								$last_space = FALSE;
								$strip_str .= $tokens[$i][1];
						}
				}
		}
		return $strip_str;
}

if ( !defined( "InShopNC" ) )
{
		exit( "Access Invalid!" );
}
?>
