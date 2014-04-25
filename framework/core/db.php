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

class ModelDb
{

		protected $comparison = array
		(
				"eq" => "=",
				"neq" => "<>",
				"gt" => ">",
				"egt" => ">=",
				"lt" => "<",
				"elt" => "<=",
				"notlike" => "NOT LIKE",
				"like" => "LIKE"
		);
		protected $selectSql = "SELECT%DISTINCT% %FIELD% FROM %TABLE%%INDEX%%JOIN%%WHERE%%GROUP%%HAVING%%ORDER%%LIMIT% %UNION%";

		public function select( $options = array( ) )
		{
				static $_cache = array( );
				$sql = $this->buildSelectSql( $options );
				if ( $options['cache'] !== FALSE )
				{
						$key = is_string( $cache['cache_key'] ) ? $cache['cache_key'] : md5( $sql );
						if ( isset( $_cache[$key] ) )
						{
								return $_cache[$key];
						}
				}
				$result = DB::getall( $sql );
				if ( $options['cache'] !== FALSE && !isset( $_cache[$key] ) )
				{
						$_cache[$key] = $result;
				}
				return $result;
		}

		public function buildSelectSql( $options = array( ) )
		{
				if ( is_numeric( $options['page'] ) )
				{
						$page = pagecmd( "obj" );
						if ( $options['limit'] !== 1 )
						{
								$options['limit'] = $page->getLimitStart( ).",".$page->getEachNum( );
						}
				}
				$sql = $this->parseSql( $this->selectSql, $options );
				return $sql;
		}

		public function parseSql( $sql, $options = array( ) )
		{
				$sql = str_replace( array( "%TABLE%", "%DISTINCT%", "%FIELD%", "%JOIN%", "%WHERE%", "%GROUP%", "%HAVING%", "%ORDER%", "%LIMIT%", "%UNION%", "%INDEX%" ), array(
						$this->parseTable( $options ),
						$this->parseDistinct( isset( $options['distinct'] ) ? $options['distinct'] : FALSE ),
						$this->parseField( isset( $options['field'] ) ? $options['field'] : "*" ),
						$this->parseJoin( isset( $options['on'] ) ? $options : array( ) ),
						$this->parseWhere( isset( $options['where'] ) ? $options['where'] : "" ),
						$this->parseGroup( isset( $options['group'] ) ? $options['group'] : "" ),
						$this->parseHaving( isset( $options['having'] ) ? $options['having'] : "" ),
						$this->parseOrder( isset( $options['order'] ) ? $options['order'] : "" ),
						$this->parseLimit( isset( $options['limit'] ) ? $options['limit'] : "" ),
						$this->parseUnion( isset( $options['union'] ) ? $options['union'] : "" ),
						$this->parseIndex( isset( $options['index'] ) ? $options['index'] : "" )
				), $sql );
				return $sql;
		}

		protected function parseUnion( )
		{
				return "";
		}

		protected function parseIndex( $value )
		{
				if ( empty( $value ) )
				{
						return "";
				}
				return " USE INDEX (".$value.") ";
		}

		protected function parseValue( $value )
		{
				if ( is_string( $value ) || is_numeric( $value ) )
				{
						$value = "'".$this->escapeString( $value )."'";
						return $value;
				}
				if ( isset( $value[0] ) && is_string( $value[0] ) && strtolower( $value[0] ) == "exp" )
				{
						$value = $this->escapeString( $value[1] );
						return $value;
				}
				if ( is_array( $value ) )
				{
						$value = array_map( array(
								$this,
								"parseValue"
						), $value );
						return $value;
				}
				if ( is_null( $value ) )
				{
						$value = "null";
				}
				return $value;
		}

		protected function parseField( $fields )
		{
				if ( is_string( $fields ) && strpos( $fields, "," ) )
				{
						$fields = explode( ",", $fields );
				}
				if ( is_array( $fields ) )
				{
						$array = array( );
						foreach ( $fields as $key => $field )
						{
								if ( !is_numeric( $key ) )
								{
										$array[] = $this->parseKey( $key )." AS ".$this->parseKey( $field );
								}
								else
								{
										$array[] = $this->parseKey( $field );
								}
						}
						$fieldsStr = implode( ",", $array );
						return $fieldsStr;
				}
				if ( is_string( $fields ) && !empty( $fields ) )
				{
						$fieldsStr = $this->parseKey( $fields );
						return $fieldsStr;
				}
				$fieldsStr = "*";
				return $fieldsStr;
		}

		protected function parseTable( $options )
		{
				if ( $options['on'] )
				{
						return;
				}
				$tables = $options['table'];
				if ( is_array( $tables ) )
				{
						$array = array( );
						foreach ( $tables as $table => $alias )
						{
								if ( !is_numeric( $table ) )
								{
										$array[] = $this->parseKey( $table )." ".$this->parseKey( $alias );
								}
								else
								{
										$array[] = $this->parseKey( $table );
								}
						}
						$tables = $array;
				}
				else if ( is_string( $tables ) )
				{
						$tables = explode( ",", $tables );
						array_walk( $tables, array(
								$this,
								"parseKey"
						) );
				}
				return implode( ",", $tables );
		}

		protected function parseWhere( $where )
		{
				$whereStr = "";
				if ( is_string( $where ) )
				{
						$whereStr = $where;
				}
				else if ( is_array( $where ) )
				{
						if ( isset( $where['_op'] ) )
						{
								$operate = " ".strtoupper( $where['_op'] )." ";
								unset( $where['_op'] );
						}
						else
						{
								$operate = " AND ";
						}
						foreach ( $where as $key => $val )
						{
								$whereStr .= "( ";
								if ( !( 0 === strpos( $key, "_" ) ) )
								{
										if ( !preg_match( "/^[A-Z_\\|\\&\\-.a-z0-9]+\$/", trim( $key ) ) )
										{
												throw_exception( $error );
										}
										$multi = isset( $val['_multi'] );
										$key = trim( $key );
										if ( strpos( $key, "|" ) )
										{
												$array = explode( "|", $key );
												$str = array( );
												foreach ( $array as $m => $k )
												{
														$v = $multi ? $val[$m] : $val;
														$str[] = "(".$this->parseWhereItem( $this->parseKey( $k ), $v ).")";
												}
												$whereStr .= implode( " OR ", $str );
										}
										else if ( strpos( $key, "&" ) )
										{
												$array = explode( "&", $key );
												$str = array( );
												foreach ( $array as $m => $k )
												{
														$v = $multi ? $val[$m] : $val;
														$str[] = "(".$this->parseWhereItem( $this->parseKey( $k ), $v ).")";
												}
												$whereStr .= implode( " AND ", $str );
										}
										else
										{
												$whereStr .= $this->parseWhereItem( $this->parseKey( $key ), $val );
										}
								}
								$whereStr .= " )".$operate;
						}
						$whereStr = substr( $whereStr, 0, 0 - strlen( $operate ) );
				}
				if ( empty( $whereStr ) )
				{
						return "";
				}
				return " WHERE ".$whereStr;
		}

		protected function parseWhereItem( $key, $val )
		{
				$whereStr = "";
				if ( is_array( $val ) )
				{
						if ( is_string( $val[0] ) )
						{
								if ( preg_match( "/^(EQ|NEQ|GT|EGT|LT|ELT|NOTLIKE|LIKE)\$/i", $val[0] ) )
								{
										$whereStr .= $key." ".$this->comparison[strtolower( $val[0] )]." ".$this->parseValue( $val[1] );
										return $whereStr;
								}
								if ( "exp" == strtolower( $val[0] ) )
								{
										$whereStr .= " (".$key." ".$val[1].") ";
										return $whereStr;
								}
								if ( preg_match( "/IN/i", $val[0] ) )
								{
										if ( isset( $val[2] ) && "exp" == $val[2] )
										{
												$whereStr .= $key." ".strtoupper( $val[0] )." ".$val[1];
												return $whereStr;
										}
										if ( empty( $val[1] ) )
										{
												$whereStr .= $key." ".strtoupper( $val[0] )."('')";
												return $whereStr;
										}
										if ( is_string( $val[1] ) )
										{
												$val[1] = explode( ",", $val[1] );
												$zone = implode( ",", $this->parseValue( $val[1] ) );
												$whereStr .= $key." ".strtoupper( $val[0] )." (".$zone.")";
												return $whereStr;
										}
										if ( is_array( $val[1] ) )
										{
												$zone = implode( ",", $this->parseValue( $val[1] ) );
												$whereStr .= $key." ".strtoupper( $val[0] )." (".$zone.")";
												return $whereStr;
										}
								}
								else if ( preg_match( "/BETWEEN/i", $val[0] ) )
								{
										$data = is_string( $val[1] ) ? explode( ",", $val[1] ) : $val[1];
										$whereStr .= " (".$key." ".strtoupper( $val[0] )." ".$this->parseValue( $data[0] )." AND ".$this->parseValue( $data[1] )." )";
										return $whereStr;
								}
								else
								{
										$error = "Model Error: args ".$val[0]." is error!";
										throw_exception( $error );
										return $whereStr;
								}
						}
						else
						{
								$count = count( $val );
								if ( in_array( strtoupper( trim( $val[$count - 1] ) ), array( "AND", "OR", "XOR" ) ) )
								{
										$rule = strtoupper( trim( $val[$count - 1] ) );
										$count -= 1;
								}
								else
								{
										$rule = "AND";
								}
								$i = 0;
								for ( ;	$i < $count;	++$i	)
								{
										$data = is_array( $val[$i] ) ? $val[$i][1] : $val[$i];
										if ( "exp" == strtolower( $val[$i][0] ) )
										{
												$whereStr .= "(".$key." ".$data.") ".$rule." ";
										}
										else
										{
												$op = is_array( $val[$i] ) ? $this->comparison[strtolower( $val[$i][0] )] : "=";
												$whereStr .= "(".$key." ".$op." ".$this->parseValue( $data ).") ".$rule." ";
										}
								}
								$whereStr = substr( $whereStr, 0, -4 );
								return $whereStr;
						}
				}
				else
				{
						$whereStr .= $key." = ".$this->parseValue( $val );
				}
				return $whereStr;
		}

		protected function parseLimit( $limit )
		{
				if ( !empty( $limit ) )
				{
						return " LIMIT ".$limit." ";
				}
				return "";
		}

		protected function parseJoin( $options = array( ) )
		{
				$joinStr = "";
				if ( FALSE === strpos( $options['table'], "," ) )
				{
						return;
				}
				$table = explode( ",", $options['table'] );
				$on = explode( ",", $options['on'] );
				$join = $options['join'];
				$joinStr .= $table[0];
				$i = 0;
				for ( ;	$i < count( $table ) - 1;	++$i	)
				{
						$joinStr .= " ".( $join[$i] ? $join[$i] : "LEFT JOIN" )." ".$table[$i + 1]." ON ".( $on[$i] ? $on[$i] : "" );
				}
				return $joinStr;
		}

		public function delete( $options = array( ) )
		{
				$sql = "DELETE ".$this->parseAttr( $options )." FROM ".$this->parseTable( $options ).$this->parseWhere( isset( $options['where'] ) ? $options['where'] : "" ).$this->parseOrder( isset( $options['order'] ) ? $options['order'] : "" ).$this->parseLimit( isset( $options['limit'] ) ? $options['limit'] : "" );
				if ( stripos( $sql, "where" ) === FALSE && $options['where'] !== TRUE )
				{
						return FALSE;
				}
				return DB::execute( $sql );
		}

		public function update( $data, $options )
		{
				$sql = "UPDATE ".$this->parseAttr( $options ).$this->parseTable( $options ).$this->parseSet( $data ).$this->parseWhere( isset( $options['where'] ) ? $options['where'] : "" ).$this->parseOrder( isset( $options['order'] ) ? $options['order'] : "" ).$this->parseLimit( isset( $options['limit'] ) ? $options['limit'] : "" );
				if ( stripos( $sql, "where" ) === FALSE && $options['where'] !== TRUE )
				{
						return FALSE;
				}
				return DB::execute( $sql );
		}

		public function parseAttr( $options )
		{
				if ( $Tmp_0 )
				{
						if ( in_array( isset( $options['attr'], $options['attr'] ), array( "LOW_PRIORITY", "QUICK", "IGNORE", "DELAYED", "HIGH_PRIORITY", "SQL_CACHE", "SQL_NO_CACHE" ) ) )
						{
								return $options['attr']." ";
						}
				}
				else
				{
						return "";
				}
		}

		public function clear( $options )
		{
				$sql = "TRUNCATE TABLE ".$this->parseTable( $options );
				return DB::execute( $sql );
		}

		public function insert( $data, $options = array( ), $replace = FALSE )
		{
				$values = $fields = array( );
				foreach ( $data as $key => $val )
				{
						$value = $this->parseValue( $val );
						if ( is_scalar( $value ) )
						{
								$values[] = $value;
								$fields[] = $this->parseKey( $key );
						}
				}
				$sql = ( $replace ? "REPLACE " : "INSERT " ).$this->parseAttr( $options )." INTO ".$this->parseTable( $options )." (".implode( ",", $fields ).") VALUES (".implode( ",", $values ).")";
				return DB::execute( $sql );
		}

		public function getLastId( )
		{
				return DB::getlastid( );
		}

		public function insertAll( $datas, $options = array( ), $replace = FALSE )
		{
				if ( !is_array( $datas[0] ) )
				{
						return FALSE;
				}
				$fields = array_keys( $datas[0] );
				array_walk( $fields, array(
						$this,
						"parseKey"
				) );
				$values = array( );
				foreach ( $datas as $data )
				{
						$value = array( );
						foreach ( $data as $key => $val )
						{
								$val = $this->parseValue( $val );
								if ( is_scalar( $val ) )
								{
										$value[] = $val;
								}
						}
						$values[] = "(".implode( ",", $value ).")";
				}
				$sql = ( $replace ? "REPLACE" : "INSERT" )." INTO ".$this->parseTable( $options )." (".implode( ",", $fields ).") VALUES ".implode( ",", $values );
				return DB::execute( $sql );
		}

		protected function parseOrder( $order )
		{
				if ( is_array( $order ) )
				{
						$array = array( );
						foreach ( $order as $key => $val )
						{
								if ( is_numeric( $key ) )
								{
										$array[] = $this->parseKey( $val );
								}
								else
								{
										$array[] = $this->parseKey( $key )." ".$val;
								}
						}
						$order = implode( ",", $array );
				}
				if ( !empty( $order ) )
				{
						return " ORDER BY ".$order;
				}
				return "";
		}

		protected function parseGroup( $group )
		{
				if ( !empty( $group ) )
				{
						return " GROUP BY ".$group;
				}
				return "";
		}

		protected function parseHaving( $having )
		{
				if ( !empty( $having ) )
				{
						return " HAVING ".$having;
				}
				return "";
		}

		protected function parseDistinct( $distinct )
		{
				if ( !empty( $distinct ) )
				{
						return " DISTINCT ";
				}
				return "";
		}

		protected function parseSet( $data )
		{
				foreach ( $data as $key => $val )
				{
						$value = $this->parseValue( $val );
						if ( is_scalar( $value ) )
						{
								$set[] = $this->parseKey( $key )."=".$value;
						}
				}
				return " SET ".implode( ",", $set );
		}

		public function escapeString( $str )
		{
				$str = addslashes( stripslashes( $str ) );
				return $str;
		}

		protected function parseKey( &$key )
		{
				return $key;
		}

}

if ( !defined( "InShopNC" ) )
{
		exit( "Access Invalid!" );
}
?>
