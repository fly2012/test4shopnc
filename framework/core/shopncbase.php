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

class ShopNCBase
{

		public $setting_config = array( );

		public function __construct( )
		{
				if ( !defined( "SiteUrl" ) )
				{
						try
						{
								if ( file_exists( BasePath.DS."config.ini.php" ) )
								{
										require_once( BasePath.DS."config.ini.php" );
										if ( !empty( $config ) || is_array( $config ) )
										{
												$site_url = $config['site_url'];
												$version = $config['version'];
												$setup_date = $config['setup_date'];
												$gip = $config['gip'];
												$dbtype = $config['dbdriver'];
												$dbcharset = $config['db'][1]['dbcharset'];
												$dbserver = $config['db'][1]['dbhost'];
												$dbserver_port = $config['db'][1]['dbport'];
												$dbname = $config['db'][1]['dbname'];
												$db_pre = $config['tablepre'];
												$dbuser = $config['db'][1]['dbuser'];
												$dbpasswd = $config['db'][1]['dbpwd'];
												$session_expire = $config['session_expire'];
												$lang_type = $config['lang_type'];
												$cookie_pre = $config['cookie_pre'];
												$tpl_name = $config['tpl_name'];
										}
										define( "SiteUrl", $site_url );
										define( "GZIP", $gip );
										define( "CHARSET", $dbcharset );
										define( "DBTYPE", $dbtype );
										define( "SESSION_EXPIRE", $session_expire );
										define( "LANG_TYPE", $lang_type );
										define( "DBPRE", $dbname."`.`".$db_pre );
										define( "MYSQL_RESULT_TYPE", 1 );
										ini_set( "allow_url_fopen", "1" );
										define( "ATTACH_PATH", "upload" );
										define( "ATTACH_COMMON", ATTACH_PATH."/common" );
										define( "RESOURCE_PATH", SiteUrl."/resource" );
										define( "CORE_PATH", BasePath."/framework" );
										$this->setting_config = $this->getSetting( $config );
										$this->setTimeZone($this->setting_config['time_zone']);
										$this->paramFliter();
										if ( defined( "GZIP" ) && GZIP == 1 && function_exists( "ob_gzhandler" ) )
										{
												ob_start( "ob_gzhandler" );
										}
										else
										{
												ob_start( );
										}
								}
								else
								{
										$error = "config.ini.php isn't exists!";
										throw new Exception( $error );
								}
						}
						catch ( Exception $e )
						{
								showmessage( $e->getMessage( ), "", "exception" );
						}
				}
		}

		private function database( )
		{
				try
				{
						if ( file_exists( BasePath.DS."framework".DS."db".DS.DBTYPE.".php" ) )
						{
								require_once( BasePath.DS."framework".DS."core".DS."db.php" );
								require_once( BasePath.DS."framework".DS."core".DS."model.php" );
								require_once( BasePath.DS."framework".DS."cache".DS."cache.php" );
								require_once( BasePath.DS."framework".DS."db".DS.DBTYPE.".php" );
								require_once( BasePath.DS."framework".DS."libraries".DS."log.php" );
								return TRUE;
						}
						$error = "Base Error: db file isn't exists!";
						throw new Exception( $error );
				}
				catch ( Exception $e )
				{
						showmessage( $e->getMessage( ), "", "exception" );
				}
				return TRUE;
		}

		public function getSetting( $config )
		{
				global $setting_config;
				$nc_config = $config;
				$nc_config['db']['read'] = $nc_config['db'][1];
				$nc_config['db']['write'] = $nc_config['db'][1];
				$setting_config = $nc_config;
				$this->database( );
				if ( empty( $this->setting_config ) )
				{
						$setting = ( $setting = f( "setting" ) ) ? $setting : h( "setting", TRUE, "file" );
						if ( $nc_config['thumb']['save_type'] == 1 )
						{
								$nc_config['thumb_url'] = SiteUrl;
						}
						else if ( $nc_config['thumb']['save_type'] == 2 && preg_match( "/^http:\\/\\/[\\.\\-\\w]+/", $nc_config['thumb']['url'] ) )
						{
								$nc_config['thumb_url'] = $nc_config['thumb']['url'];
						}
						else if ( $nc_config['thumb']['save_type'] == 3 && $setting['ftp_open'] )
						{
								$nc_config['thumb_url'] = $setting['ftp_access_url'];
						}
						else
						{
								$nc_config['thumb_url'] = SiteUrl;
						}
						$this->setting_config = array_merge_recursive( $setting, $nc_config );
				}
				$setting_config = $this->setting_config;
				return $this->setting_config;
		}

		public function getModel( $model_name )
		{
				return model( $model_name );
		}

		public function paramFliter( )
		{
				$magic_quotes = get_magic_quotes_gpc( );
				$GLOBALS['_GET'] = $magic_quotes ? $_GET : $this->getFliter( $_GET );
				$GLOBALS['_POST'] = $magic_quotes ? $_POST : $this->getFliter( $_POST );
				return TRUE;
		}

		public function getFliter( $var )
		{
				if ( is_array( $var ) )
				{
						foreach ( $var as $key => $val )
						{
								$var[$key] = $this->getFliter( $val );
						}
						return $var;
				}
				$var = addslashes( $var );
				return $var;
		}

		public function setTimeZone( $time_zone )
		{
				$zonelist = array( "-12" => "Pacific/Kwajalein", "-11" => "Pacific/Samoa", "-10" => "US/Hawaii", "-9" => "US/Alaska", "-8" => "America/Tijuana", "-7" => "US/Arizona", "-6" => "America/Mexico_City", "-5" => "America/Bogota", "-4" => "America/Caracas", "-3.5" => "Canada/Newfoundland", "-3" => "America/Buenos_Aires", "-2" => "Atlantic/St_Helena", "-1" => "Atlantic/Azores", "0" => "Europe/Dublin", "1" => "Europe/Amsterdam", "2" => "Africa/Cairo", "3" => "Asia/Baghdad", "3.5" => "Asia/Tehran", "4" => "Asia/Baku", "4.5" => "Asia/Kabul", "5" => "Asia/Karachi", "5.5" => "Asia/Calcutta", "5.75" => "Asia/Katmandu", "6" => "Asia/Almaty", "6.5" => "Asia/Rangoon", "7" => "Asia/Bangkok", "8" => "Asia/Shanghai", "9" => "Asia/Tokyo", "9.5" => "Australia/Adelaide", "10" => "Australia/Canberra", "11" => "Asia/Magadan", "12" => "Pacific/Auckland" );
				if ( function_exists( "date_default_timezone_set" ) )
				{
						if ( !empty( $zonelist[$time_zone] ) )
						{
								date_default_timezone_set( $zonelist[$time_zone] );
								return TRUE;
						}
						date_default_timezone_set( "Asia/Shanghai" );
				}
				return TRUE;
		}

}

if ( !defined( "InShopNC" ) )
{
		exit( "Access Invalid!" );
}
?>
