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

		private static $instance = NULL;
		private static $link = NULL;
		private static $query_num = 0;
		private static $query_id = NULL;
		private static $seq_prefix = "SEQ_";
		private static $table = NULL;

		private function __construct( )
		{
				if ( !extension_loaded( "oci8" ) )
				{
						$error = "Db Error: oracle is not install";
						throw_exception( $error );
				}
		}

		public static function getInstance( )
		{
				if ( self::$instance === NULL || !self::$instance instanceof Db )
				{
						self::$instance = new Db( );
						self::$instance->connect( );
				}
				return self::$instance;
		}

		public function connect( )
		{
				$conf = c( "db.write" );
				$conn_str = sprintf( "(DESCRIPTION = (ADDRESS = (PROTOCOL = TCP) (HOST = %s)(PORT = %s)) (CONNECT_DATA= (SID = %s)))", $conf['dbhost'], $conf['dbport'], $conf['dbname'] );
				self::$link = oci_pconnect( $con_user, $con_pwd, $conn_str, str_replace( "-", "", $con_charset ) );
				if ( !self::$link )
				{
						throw_exception( oci_error( ) );
				}
		}

		private static function free( )
		{
				oci_free_statement( self::$query_id );
				self::$query_id = NULL;
		}

		public static function query( $sql )
		{
				self::getinstance( );
				if ( self::$debug )
				{
						adduptime( "queryStartTime" );
				}
				if ( self::$query_id )
				{
						self::free( );
				}
				self::$query_id = oci_parse( self::$link, $sql );
				if ( FALSE === oci_execute( self::$query_id, OCI_COMMIT_ON_SUCCESS ) )
				{
						return FALSE;
				}
				return TRUE;
		}

		public static function getAll( $sql )
		{
				self::getinstance( );
				if ( self::$query_id )
				{
						self::free( );
				}
				self::$query_id = oci_parse( self::$link, $sql );
				if ( FALSE === oci_execute( self::$query_id, OCI_COMMIT_ON_SUCCESS ) )
				{
						return FALSE;
				}
				$result = array( );
				while ( $tmp = oci_fetch_array( self::$query_id, OCI_BOTH ) )
				{
						$result[] = array_change_key_case( $tmp );
				}
				return $result;
		}

		public static function debug( $sql )
		{
				adduptime( "queryEndTime" );
				Log::record( $sql." [ RunTime:".adduptime( "queryStartTime", "queryEndTime", 6 )."s ]", Log::SQL );
		}

		public static function select( $param, $obj_page = "" )
		{
				self::getinstance( );
				static $_cache = array( );
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
								$tmp_sql .= $param['join_type']." ".DBPRE.$tmp_table[$i]." ".$tmp_table[$i]." ON ".$param['join_on'][$i - 1]." ";
						}
						$sql = "SELECT ".$param['field']." FROM ".DBPRE.$tmp_table[0]." ".$tmp_table[0]." ".$tmp_sql." ".$param['where'].$param['where_group'].$param['where_order'];
						$count_sql = "SELECT ".$param['count']." as count FROM ".DBPRE.$tmp_table[0]." ".$tmp_table[0]." ".$tmp_sql." ".$param['where'].$param['where_group'];
				}
				else
				{
						$sql = "SELECT ".$param['field']." FROM ".DBPRE.$param['table']." ".$param['table']." ".$param['where'].$param['where_group'].$param['where_order'];
						$count_sql = "SELECT ".$param['count']." count FROM ".DBPRE.$param['table']." ".$param['table']." ".$param['where'].$param['where_group'];
				}
				if ( $obj_page instanceof Page )
				{
						$count_query = self::query( $count_sql );
						$count_fetch = mysqli_fetch_array( $count_query );
						$obj_page->setTotalNum( $count_fetch['count'] );
						$param['limit'] = $obj_page->getLimitStart( ).",".$obj_page->getEachNum( );
				}
				if ( $param['limit'] != "" )
				{
						$sql .= " limit ".$param['limit'];
				}
				if ( !empty( $limit ) )
				{
						$limit = explode( ",", $limit );
						if ( 1 < count( $limit ) )
						{
								$limitStr = "(numrow>".$limit[0].") AND (numrow<=".( $limit[0] + $limit[1] ).")";
						}
						else
						{
								$limitStr = "(numrow>0 AND numrow<=".$limit[0].")";
						}
				}
				if ( $limitStr )
				{
						return " WHERE ".$limitStr;
				}
				return "";
		}

		public static function insert( $table_name, $insert_array = array( ) )
		{
				self::getinstance( );
				if ( !is_array( $insert_array ) )
				{
						return FALSE;
				}
				self::$table = $table_name;
				$fields = array( );
				$value = array( );
				foreach ( $insert_array as $key => $val )
				{
						$fields[] = self::parsekey( $key );
						$value[] = self::parsevalue( $val );
				}
				$sql = "INSERT INTO ".DBPRE.$table_name." (".implode( ",", $fields ).") VALUES(".implode( ",", $value ).")";
				$result = self::$instance->query( $sql );
				$insert_id = self::getlastid( );
				if ( $insert_id )
				{
						return $insert_id;
				}
				return $result;
		}

		public static function insertAll( $table_name, $insert_array = array( ) )
		{
				self::getinstance( );
				if ( !is_array( $insert_array[0] ) )
				{
						return FALSE;
				}
				self::$table = $table_name;
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
				$result = self::$instance->query( $sql );
				$insert_id = self::getlastid( );
				if ( $insert_id )
				{
						return $insert_id;
				}
				return $result;
		}

		public static function update( $table_name, $update_array = array( ), $where = "" )
		{
				self::getinstance( );
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
				$sql = "UPDATE ".DBPRE.$table_name." SET ".$string_value." ".$where;
				$result = self::$instance->query( $sql );
				return $result;
		}

		public static function delete( $table_name, $where = "" )
		{
				self::getinstance( );
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
						$sql = "DELETE FROM ".DBPRE.$table_name." ".$where;
						$result = self::$instance->query( $sql );
						return $result;
				}
				$error = "Db Error: the condition of delete is empty!";
				throw_exception( $error );
		}

		public static function getLastId( )
		{
				self::getinstance( );
				$sequence = self::$seq_prefix.self::$table;
				$sql = "SELECT ".$sequence.".currval val FROM dual";
				$currval = self::getall( $sql );
				if ( $currval[0]['val'] )
				{
						return $currval[0]['val'];
				}
				return 0;
		}

		public static function getRow( $param, $fields = "*" )
		{
				self::getinstance( );
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
				$sql = "SELECT ".$fields." FROM ".DBPRE.$table." WHERE ".$where;
				if ( self::$query_id )
				{
						self::free( );
				}
				self::$query_id = oci_parse( self::$link, $sql );
				if ( FALSE === oci_execute( self::$query_id, OCI_COMMIT_ON_SUCCESS ) )
				{
						return FALSE;
				}
				$result = array( );
				oci_fetch_all( self::$query_id, $result, 0, -1, OCI_FETCHSTATEMENT_BY_ROW );
				return array_change_key_case( $result[0], CASE_LOWER );
		}

		public static function replace( $table_name, $replace_array = array( ) )
		{
		}

		public static function getCount( $table, $condition = NULL )
		{
				self::getinstance( );
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
				$sql = "SELECT COUNT(*) as count FROM ".DBPRE.$table." ".$table.( isset( $where ) ? $where : "" );
				$result = self::getall( $sql );
				return $result[0]['count'];
		}

		public static function execute( $sql )
		{
				self::getinstance( );
				$result = self::$instance->query( $sql );
				return $result;
		}

		public static function getQueryNum( )
		{
				return self::$query_num;
		}

		public static function showTables( )
		{
				self::getinstance( );
				$result = self::getall( "select table_name from user_tables" );
				$info = array( );
				foreach ( $result as $key => $val )
				{
						$info[$key] = current( &$val );
				}
				return $info;
		}

		public static function showCreateTable( $table )
		{
				return array( );
		}

		public static function showColumns( $table )
		{
				self::getinstance( );
				$result = self::getall( "DESC ".strtoupper( DBPRE.$table ) );
				print_r( $result );
				exit( );
		}

		public static function getServerInfo( )
		{
				self::getinstance( );
				$result = mysqli_get_server_info( self::$mysqli );
				return $result;
		}

		public static function parseKey( &$key )
		{
				$key = trim( $key );
				return $key;
		}

		public static function parseValue( $value )
		{
				$value = addslashes( stripslashes( $value ) );
				$value = "'".$value."'";
				return $value;
		}

}

if ( !defined( "InShopNC" ) )
{
		exit( "Access Invalid!" );
}
?>
