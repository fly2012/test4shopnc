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

class Db
{

		private static $instance_master = NULL;
		private static $instance_slave = NULL;
		private static $link = NULL;
		private static $query_num = 0;

		private function __construct( )
		{
				if ( !class_exists( "PDO" ) )
				{
						throw_exception( "Db Error: pdo_mysql is not install" );
				}
		}

		public static function getInstance( $dotype = "read" )
		{
				if ( !in_array( $dotype, array( "read", "write" ) ) )
				{
						$dotype = "read";
				}
				$conf = c( "db.".$dotype );
				if ( $dotype == "write" )
				{
						$instance =& self::$instance_master;
				}
				else
				{
						$instance =& self::$instance_slave;
				}
				if ( $instance === NULL || !$instance instanceof Db )
				{
						$instance = new Db( );
						$instance->connect( $conf );
				}
		}

		public function connect( $conf )
		{
				$dsn = sprintf( "mysql:host=%s;port=%s;dbname=%s", $conf['dbhost'], $conf['dbport'], $conf['dbname'] );
				try
				{
						self::$link = new PDO( $dsn, $conf['dbuser'], $conf['dbpwd'] );
				}
				catch ( PDOException $e )
				{
						throw_exception( $e->getMessage( ) );
				}
				switch ( strtoupper( $conf['dbcharset'] ) )
				{
				case "UTF-8" :
						$query_string = "\r\n\t\t                 SET CHARACTER_SET_CLIENT = utf8,\r\n\t\t                 CHARACTER_SET_CONNECTION = utf8,\r\n\t\t                 CHARACTER_SET_DATABASE = utf8,\r\n\t\t                 CHARACTER_SET_RESULTS = utf8,\r\n\t\t                 CHARACTER_SET_SERVER = utf8,\r\n\t\t                 COLLATION_CONNECTION = utf8_general_ci,\r\n\t\t                 COLLATION_DATABASE = utf8_general_ci,\r\n\t\t                 COLLATION_SERVER = utf8_general_ci,\r\n\t\t                 sql_mode=''";
						break;
				case "GBK" :
						$query_string = "\r\n\t\t   \t\t\t    SET CHARACTER_SET_CLIENT = gbk,\r\n\t\t                 CHARACTER_SET_CONNECTION = gbk,\r\n\t\t                 CHARACTER_SET_DATABASE = gbk,\r\n\t\t                 CHARACTER_SET_RESULTS = gbk,\r\n\t\t                 CHARACTER_SET_SERVER = gbk,\r\n\t\t                 COLLATION_CONNECTION = gbk_chinese_ci,\r\n\t\t                 COLLATION_DATABASE = gbk_chinese_ci,\r\n\t\t                 COLLATION_SERVER = gbk_chinese_ci,\r\n\t\t                 sql_mode=''";
						break;
				default :
						$error = "Db Error: charset is Invalid";
						throw_exception( $error );
				}
				$result = self::$link->exec( $query_string );
				if ( $result === FALSE )
				{
						$error = "Db Error: PDO query error code is ".self::$link->errorCode( );
						throw_exception( $error );
				}
		}

		public static function debug( $sql )
		{
				adduptime( "queryEndTime" );
				Log::record( $sql." [ RunTime:".adduptime( "queryStartTime", "queryEndTime", 6 )."s ]", Log::SQL );
		}

		public static function query( $sql, $type = "exec" )
		{
				self::getinstance( "read" );
				if ( c( "debug" ) )
				{
						adduptime( "queryStartTime" );
				}
				switch ( $type )
				{
				case "query" :
						$result = self::$link->query( $sql );
						break;
				case "exec" :
						$result = self::$link->exec( $sql );
				}
				if ( c( "debug" ) )
				{
						self::debug( $sql );
				}
				++self::$query_num;
				if ( $result === FALSE )
				{
						$error = "Db Error: PDO query error code is ".self::$link->errorCode( );
						throw_exception( $error );
				}
				return $result;
		}

		public static function getAll( $sql )
		{
				self::getinstance( "read" );
				$result = self::$instance_slave->query( $sql, "query" );
				if ( $result === FALSE )
				{
						return FALSE;
				}
				$array = $result->fetchAll( );
				if ( !empty( $array ) )
				{
						return $array;
				}
		}

		public static function select( $param, $obj_page = "" )
		{
				static $_cache = array( );
				self::getinstance( "read" );
				if ( empty( $param ) )
				{
						$error = "Db Error: select param is empty!";
						throw_exception( $error );
				}
				if ( empty( $param['field'] ) )
				{
						$param['field'] = "*";
				}
				if ( empty( $param['count'] ) )
				{
						$param['count'] = "count(*)";
				}
				if ( isset( $param['index'] ) )
				{
						$param['index'] = "USE INDEX (".$param['index'].")";
				}
				if ( trim( $param['where'] ) != "" )
				{
						if ( strtoupper( substr( trim( $param['where'] ), 0, 5 ) ) != "WHERE" )
						{
								if ( strtoupper( substr( trim( $param['where'] ), 0, 3 ) ) == "AND" )
								{
										$param['where'] = substr( trim( $param['where'] ), 3 );
								}
								$param['where'] = "WHERE ".$param['where'];
						}
				}
				else
				{
						$param['where'] = "";
				}
				$param['where_group'] = "";
				if ( !empty( $param['group'] ) )
				{
						$param['where_group'] .= " group by ".$param['group'];
				}
				$param['where_order'] = "";
				if ( !empty( $param['order'] ) )
				{
						$param['where_order'] .= " order by ".$param['order'];
				}
				$tmp_table = explode( ",", $param['table'] );
				if ( !empty( $tmp_table ) || 1 < count( $tmp_table ) )
				{
						if ( count( $tmp_table ) - 1 != count( $param['join_on'] ) )
						{
								$error = "Db Error: join number is wrong!";
								throw_exception( $error );
						}
						foreach ( $tmp_table as $key => $val )
						{
								$tmp_table[$key] = trim( $val );
						}
						$i = 1;
						for ( ;	$i < count( $tmp_table );	++$i	)
						{
								$tmp_sql .= $param['join_type']." `".DBPRE.$tmp_table[$i]."` as `".$tmp_table[$i]."` ON ".$param['join_on'][$i - 1]." ";
						}
						$sql = "SELECT ".$param['field']." FROM `".DBPRE.$tmp_table[0]."` as `".$tmp_table[0]."` ".$tmp_sql." ".$param['where'].$param['where_group'].$param['where_order'];
						$count_sql = "SELECT ".$param['count']." as count FROM `".DBPRE.$tmp_table[0]."` as `".$tmp_table[0]."` ".$tmp_sql." ".$param['where'].$param['where_group'];
				}
				else
				{
						$sql = "SELECT ".$param['field']." FROM `".DBPRE.$param['table']."` as `".$param['table']."` ".$param['index']." ".$param['where'].$param['where_group'].$param['where_order'];
						$count_sql = "SELECT ".$param['count']." as count FROM `".DBPRE.$param['table']."` as `".$param['table']."` ".$param['index']." ".$param['where'].$param['where_group'];
				}
				if ( $obj_page instanceof Page )
				{
						$count_query = self::query( $count_sql, "query" );
						$count_fetch = $count_query->fetch( );
						$obj_page->setTotalNum( $count_fetch['count'] );
						$param['limit'] = $obj_page->getLimitStart( ).",".$obj_page->getEachNum( );
				}
				if ( $param['limit'] != "" )
				{
						$sql .= " limit ".$param['limit'];
				}
				if ( $param['cache'] !== FALSE )
				{
						$key = is_string( $param['cache_key'] ) ? $param['cache_key'] : md5( $sql );
						if ( isset( $_cache[$key] ) )
						{
								return $_cache[$key];
						}
				}
				$result = self::$instance_slave->query( $sql, "query" );
				if ( $result === FALSE )
				{
						return FALSE;
				}
				$array = $result->fetchAll( );
				if ( !empty( $array ) )
				{
						return $array;
				}
		}

		public static function getRow( $param, $fields = "*" )
		{
				self::getinstance( "write" );
				$table = $param['table'];
				$wfield = $param['field'];
				$value = $param['value'];
				if ( is_array( $wfield ) )
				{
						$where = array( );
						foreach ( $wfield as $k => $v )
						{
								$where[] = $v."='".$value[$k]."'";
						}
						$where = implode( " and ", $where );
				}
				else
				{
						$where = $wfield."='".$value."'";
				}
				$sql = "SELECT ".$fields." FROM `".DBPRE.$table."` WHERE ".$where;
				$result = self::$instance_master->query( $sql, "query" );
				if ( $result === FALSE )
				{
						return FALSE;
				}
				$array = $result->fetch( );
				if ( !empty( $array ) )
				{
						return $array;
				}
		}

		public static function insert( $table_name, $insert_array = array( ) )
		{
				self::getinstance( "write" );
				if ( !is_array( $insert_array ) )
				{
						return FALSE;
				}
				$fields = array( );
				$value = array( );
				foreach ( $insert_array as $key => $val )
				{
						$fields[] = self::parsekey( $key );
						$value[] = self::parsevalue( $val );
				}
				$sql = "INSERT INTO `".DBPRE.$table_name."` (".implode( ",", $fields ).") VALUES(".implode( ",", $value ).")";
				$result = self::$instance_master->query( $sql, "exec" );
				$insert_id = self::$link->lastInsertId( );
				if ( $insert_id )
				{
						return $insert_id;
				}
				return $result;
		}

		public static function insertAll( $table_name, $insert_array = array( ) )
		{
				self::getinstance( "write" );
				if ( !is_array( $insert_array[0] ) )
				{
						return FALSE;
				}
				$fields = array_keys( $insert_array[0] );
				array_walk( &$fields, array(
						self,
						"parseKey"
				) );
				$values = array( );
				foreach ( $insert_array as $data )
				{
						$value = array( );
						foreach ( $data as $key => $val )
						{
								$val = self::parsevalue( $val );
								if ( is_scalar( $val ) )
								{
										$value[] = $val;
								}
						}
						$values[] = "(".implode( ",", $value ).")";
				}
				$sql = "INSERT INTO `".DBPRE.$table_name."` (".implode( ",", $fields ).") VALUES ".implode( ",", $values );
				$result = self::$instance_master->query( $sql, "exec" );
				$insert_id = self::$link->lastInsertId( );
				if ( $insert_id )
				{
						return $insert_id;
				}
				return $result;
		}

		public static function update( $table_name, $update_array = array( ), $where = "" )
		{
				self::getinstance( "write" );
				if ( !is_array( $update_array ) )
				{
						return FALSE;
				}
				$string_value = "";
				foreach ( $update_array as $k => $v )
				{
						if ( is_array( $v ) )
						{
								switch ( $v['sign'] )
								{
								case "increase" :
										$string_value .= " ".$k." = {$k} + ".$v['value'].",";
										break;
								case "decrease" :
										$string_value .= " ".$k." = {$k} - ".$v['value'].",";
										break;
								case "calc" :
										$string_value .= " ".$k." = ".$v['value'].",";
										break;
								default :
										$string_value .= " ".$k." = '".$v['value']."',";
								}
						}
						else
						{
								$string_value .= " ".$k." = '".$v."',";
						}
				}
				$string_value = trim( trim( $string_value ), "," );
				if ( trim( $where ) != "" && strtoupper( substr( trim( $where ), 0, 5 ) ) != "WHERE" )
				{
						if ( strtoupper( substr( trim( $where ), 0, 3 ) ) == "AND" )
						{
								$where = substr( trim( $where ), 3 );
						}
						$where = " WHERE ".$where;
				}
				$sql = "UPDATE `".DBPRE.$table_name."` AS `".$table_name."` SET ".$string_value." ".$where;
				$result = self::$instance_master->query( $sql, "exec" );
				return $result;
		}

		public static function delete( $table_name, $where = "" )
		{
				self::getinstance( "write" );
				if ( trim( $where ) != "" )
				{
						if ( strtoupper( substr( trim( $where ), 0, 5 ) ) != "WHERE" )
						{
								if ( strtoupper( substr( trim( $where ), 0, 3 ) ) == "AND" )
								{
										$where = substr( trim( $where ), 3 );
								}
								$where = " WHERE ".$where;
						}
						$sql = "DELETE FROM `".DBPRE.$table_name."` ".$where;
						$result = self::$instance_master->query( $sql, "exec" );
						return $result;
				}
				$error = "Db Error: the condition of delete is empty!";
				throw_exception( $error );
		}

		public static function getQueryNum( )
		{
				return self::$query_num;
		}

		public static function replace( $table_name, $replace_array = array( ) )
		{
				self::getinstance( "write" );
				if ( !empty( $replace_array ) )
				{
						foreach ( $replace_array as $k => $v )
						{
								$string_field .= " ".$k." ,";
								$string_value .= " '".$v."',";
						}
						$string_field = trim( trim( $string_field ), "," );
						$string_value = trim( trim( $string_value ), "," );
						$sql = "REPLACE INTO `".DBPRE.$table_name."` (".$string_field.") VALUES(".$string_value.")";
						$result = self::$instance_master->query( $sql, "exec" );
						return $result;
				}
				return FALSE;
		}

		public static function getCount( $table, $condition = NULL )
		{
				self::getinstance( "read" );
				if ( !empty( $condition ) || is_array( $condition ) )
				{
						$where = "";
						foreach ( $condition as $key => $val )
						{
								self::parsekey( $key );
								$val = self::parsevalue( $val );
								$where .= " AND ".$key."=".$val;
						}
						$where = " WHERE ".substr( $where, 4 );
				}
				else if ( is_string( $condition ) )
				{
						if ( strtoupper( substr( trim( $condition ), 0, 3 ) ) == "AND" )
						{
								$where = " WHERE ".substr( trim( $condition ), 4 );
						}
						else
						{
								$where = " WHERE ".$condition;
						}
				}
				$sql = "SELECT COUNT(*) as `count` FROM `".DBPRE.$table."` as `".$table."` ".( isset( $where ) ? $where : "" );
				$result = self::$instance_slave->query( $sql, "query" );
				$result = $result->fetch( );
				return $result['count'];
		}

		public static function showTables( )
		{
				self::getinstance( );
				$sql = "SHOW TABLES";
				$result = self::$instance_slave->query( $sql, "query" );
				if ( $result === FALSE )
				{
						return FALSE;
				}
				$array = $result->fetchAll( );
				if ( !empty( $array ) )
				{
						return $array;
				}
		}

		public static function showCreateTable( $table )
		{
				self::getinstance( );
				$sql = "SHOW CREATE TABLE ".DBPRE.$table;
				$result = self::$instance_slave->query( $sql, "query" );
				$result = $result->fetch( );
				return $result['Create Table'];
		}

		public static function showColumns( $table )
		{
				self::getinstance( );
				$sql = "SHOW COLUMNS FROM `".DBPRE.$table."`";
				$result = self::$instance_slave->query( $sql, "query" );
				if ( $result === FALSE )
				{
						return FALSE;
				}
				$array = $result->fetchAll( );
				$result_array = array( );
				if ( !empty( $array ) || is_array( $array ) )
				{
						foreach ( $array as $k => $v )
						{
								$result_array[$v['Field']] = array(
										"name" => $v['Field'],
										"type" => $v['Type'],
										"null" => $v['Null'],
										"default" => $v['Default'],
										"primary" => strtolower( $v['Key'] ) == "pri",
										"autoinc" => strtolower( $v['Extra'] ) == "auto_increment"
								);
						}
				}
				return $result_array;
		}

		public static function parseKey( &$key )
		{
				$key = trim( $key );
				if ( !preg_match( "/[,'\\\"\\*\\(\\)`.\\s]/", $key ) )
				{
						$key = "`".$key."`";
				}
				return $key;
		}

		public static function parseValue( $value )
		{
				$value = addslashes( stripslashes( $value ) );
				$value = "'".$value."'";
				return $value;
		}

		public static function execute( $sql )
		{
				self::getinstance( "write" );
				$result = self::$instance_master->query( $sql, "exec" );
				return $result;
		}

		public static function getServerInfo( )
		{
		}

		public static function getLastId( )
		{
				self::getinstance( );
				return self::$link->lastInsertId( );
		}

}

if ( !defined( "InShopNC" ) )
{
		exit( "Access Invalid!" );
}
?>
