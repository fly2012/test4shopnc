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

if ( !defined( "InShopNC" ) )
{
		exit( "Access Invalid!" );
}
if ( !is_array( $config ) )
{
		exit( "config params error" );
}
global $config;
define( "SiteUrl", $config['site_url'] );
define( "CHARSET", $config['db'][1]['dbcharset'] );
define( "DBDRIVER", $config['dbdriver'] );
define( "SESSION_EXPIRE", $config['session_expire'] );
define( "LANG_TYPE", $config['lang_type'] );
define( "COOKIE_PRE", $config['cookie_pre'] );
define( "CORE_PATH", BasePath."/framework" );
define( "TPL_NAME", $config['tpl_name'] );
define( "BASE_TPL_PATH", BasePath."/templates/".TPL_NAME );
define( "RESOURCE_PATH", SiteUrl."/resource" );

if ( !isset( $config['product_indate'] ) )
{
		$config['product_indate'] = 7;
}
if ( defined( "NC_ADMIN" ) && NC_ADMIN === TRUE )
{
		define( "DBPRE", $db_pre ? $db_pre : $config['tablepre'] );
}
else if ( DBDRIVER == "oci8" )
{
		define( "DBPRE", $config['tablepre'] );
}
else
{
		define( "DBPRE", $config['db'][1]['dbname']."`.`".$config['tablepre'] );
}
define( "ATTACH_PATH", "upload" );
define( "ATTACH_COMMON", "upload/common" );
define( "ATTACH_AVATAR", "upload/avatar" );
define( "ATTACH_STORE", "upload/store" );
define( "ATTACH_GOODS", "upload/store/goods" );
define( "ATTACH_FLEAS", "upload/flea/goods" );
define( "ATTACH_AUTH", "upload/auth" );
define( "ATTACH_MOBILE", "upload/mobile" );
define( "ATTACH_LINK", "upload/link" );
define( "ATTACH_ARTICLE", "upload/article" );
define( "ATTACH_BRAND", "upload/brand" );
define( "ATTACH_ADV", "upload/adv" );
define( "ATTACH_ACTIVITY", "upload/activity" );
define( "ATTACH_COUPON", "upload/coupon" );
define( "ATTACH_WATERMARK", "upload/watermark" );
define( "ATTACH_POINTPROD", "upload/pointprod" );
define( "ATTACH_SPEC", "upload/spec" );
define( "ATTACH_GROUPBUY", "upload/groupbuy" );
define( "ATTACH_SLIDE", "upload/store/slide" );
define( "ATTACH_VOUCHER", "upload/voucher" );
define( "ATTACH_MICROSHOP", "upload/microshop" );
define( "ATTACH_MALBUM", "upload/member" );
if ( defined( "ProjectName" ) && ProjectName != "" )
{
		define( "TEMPLATES_PATH", SiteUrl."/".ProjectName."/templates" );
}
else
{
		define( "TEMPLATES_PATH", SiteUrl."/templates/".TPL_NAME );
}

//var_dump($GLOBALS['_GET']);echo '-<br />';

//php5.4废弃globals属性..so..兼容之..
//$GLOBALS['_GET']['act'] = $_GET['act'] ? strtolower( $_GET['act'] ) : $_POST['act'] ? strtolower( $_POST['act'] ) : "index";
//$GLOBALS['_GET']['op'] = $_GET['op'] ? strtolower( $_GET['op'] ) : $_POST['op'] ? strtolower( $_POST['op'] ) : "index";
            //isset($_GET['act']) ? strtolower( $_GET['act'] ) : isset($_POST['act']) ? strtolower( $_POST['act'] ) : "index";
            //isset($_GET['op']) ? strtolower( $_GET['op'] ) : isset($_POST['op']) ? strtolower( $_POST['op'] ) : "index";
            if(isset($_GET['act'])){
                $_GET['act'] = strtolower( $_GET['act'] ) ; 
            }elseif(isset($_POST['act'] )){
                $_GET['act'] = strtolower( $_POST['act'] );
            }
            else{
                $_GET['act'] = "index";
            }
            if(isset($_GET['op'])){
                $_GET['op'] = strtolower( $_GET['op'] ) ;
            }elseif(isset($_POST['op'])){
                $_GET['op'] = strtolower( $_POST['op'] );
            }else{
                $_GET['op'] = "index";
            }
//var_dump($GLOBALS['_GET']);echo '--<br />';

$ignore = array( "article_content", "pgoods_body", "doc_content", "content", "sn_content", "goods_body", "store_description", "input_group_intro", "remind_content", "note_content", "ref_url", "qq_appcode", "adv_pic_url", "adv_word_url", "adv_slide_url", "appcode" );
if ( !class_exists( "Security" ) )
{
		require( BasePath."/framework/libraries/security.php" );
}

//同上,兼容之
//  $GLOBALS['_GET'] = !empty( $_GET ) ? Security::getaddslashesforinput( $_GET, $ignore ) : array();
//  $GLOBALS['_POST'] = !empty( $_POST ) ? Security::getaddslashesforinput( $_POST, $ignore ) : array();
//  $GLOBALS['_REQUEST'] = !empty( $_REQUEST ) ? Security::getaddslashesforinput( $_REQUEST, $ignore ) : array();
$_GET= !empty( $_GET ) ? Security::getaddslashesforinput( $_GET, $ignore ) : array();
$_POST= !empty( $_POST ) ? Security::getaddslashesforinput( $_POST, $ignore ) : array();
$_REQUEST= !empty( $_REQUEST ) ? Security::getaddslashesforinput( $_REQUEST, $ignore ) : array();
if ( $config['gzip'] == 1 && function_exists( "ob_gzhandler" ) && $_GET['inajax'] != 1 )
{
		ob_start("ob_gzhandler");
}
else
{
		ob_start();
}
require( BasePath."/framework/function/core.php" );
require( BasePath."/framework/core/db.php" );
require( BasePath."/framework/db/".strtolower( $config['dbdriver'] ).".php" );
require( BasePath."/framework/core/model.php" );
require( BasePath."/framework/cache/cache.php" );
require( BasePath."/framework/cache/cache.file.php" );
require( BasePath."/framework/libraries/language.php" );
require( BasePath."/framework/libraries/validate.php" );
require( BasePath."/framework/libraries/page.php" );
require( BasePath."/framework/libraries/upload.php" );
require( BasePath."/framework/libraries/email.php" );
require( BasePath."/framework/libraries/log.php" );
require( BasePath."/framework/function/goods.php" );
require( BasePath."/framework/function/seccode.php" );
require( BasePath."/framework/tpl/nc.php" );
require( BasePath."/control/control.php" );

require( BasePath."/framework/core/base.php" );
Language::read("core_lang_index");

Base::run();

?>
