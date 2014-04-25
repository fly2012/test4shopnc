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

class Model
{

		protected $name = "";
		protected $table_prefix = "";
		protected $init_table = NULL;
		protected $table_name = "";
		protected $options = array( );
		protected $pk = "id";
		protected $db = NULL;
		protected $fields = array( );
		protected $unoptions = TRUE;

		public function __construct( $table = NULL )
		{
				if ( !is_null( $table ) )
				{
						$this->table_name = $table;
						$this->tableInfo( $table );
				}
				$this->table_prefix = DBPRE;
				if ( !is_object( $this->db ) )
				{
						$this->db = new ModelDb( );
				}
		}

		public function tableInfo( $table )
		{
				if ( empty( $table ) )
				{
						return FALSE;
				}
				if ( c( "check_field" ) )
				{
						$this->fields[$table] = f( $table, $this->fields[$table], "cache/fields" );
						if ( !$this->fields[$table] )
						{
								$fields = DB::showcolumns( $table );
								if ( !$fields )
								{
										return FALSE;
								}
								$this->fields[$table] = array_keys( $fields );
								foreach ( $fields as $k => $v )
								{
										if ( $v['primary'] )
										{
												$this->fields[$table]['_pk'] = $k;
												if ( stripos( $v['type'], "int" ) !== FALSE )
												{
														$this->fields[$table]['_pk_type'] = "int";
												}
												if ( $v['autoinc'] )
												{
														$this->fields[$table]['_autoinc'] = TRUE;
												}
										}
								}
								f( $table, $this->fields[$table], "cache/fields" );
						}
				}
				else if ( file_exists( BasePath."/cache/fields/_pk.php" ) )
				{
						$this->fields = require( BasePath."/cache/fields/_pk.php" );
				}
				else
				{
						$full_table = Db::showtables( );
						$_pk_array = array( );
						$count = strlen( c( "tablepre" ) );
						foreach ( $full_table as $v_table )
						{
								if ( !( substr( $v_table[0], 0, $count ) != c( "tablepre" ) ) )
								{
										$tb = str_replace( c( "tablepre" ), "", $v_table[0] );
										$fields = DB::showcolumns( $tb );
										foreach ( ( array )$fields as $k => $v )
										{
												if ( !$v['primary'] )
												{
														continue;
												}
												$_pk_array[$tb] = $k;
												break;
										}
								}
						}
						$this->fields = $_pk_array;
						f( "_pk", $_pk_array, "cache/fields" );
				}
				return $this->fields[$table];
		}

		public function __call( $method, $args )
		{
				if ( in_array( strtolower( $method ), array( "table", "order", "where", "on", "limit", "having", "group", "lock", "distinct", "index", "attr" ), TRUE ) )
				{
						$this->options[strtolower( $method )] = $args[0];
						if ( strtolower( $method ) == "table" )
						{
								if ( strpos( $args[0], "," ) !== FALSE )
								{
										$args[0] = explode( ",", $args[0] );
										$this->table_name = "";
										foreach ( ( array )$args[0] as $value )
										{
												$this->tableInfo( $value );
										}
								}
								else
								{
										$this->table_name = $args[0];
										$this->fields = array( );
										$this->tableInfo( $args[0] );
								}
						}
						return $this;
				}
				if ( in_array( strtolower( $method ), array( "page" ), TRUE ) )
				{
						if ( is_numeric( $args[1] ) || 0 < $args[1] )
						{
								if ( 0 < $args[0] )
								{
										$this->options[strtolower( $method )] = $args[0];
										pagecmd( "setEachNum", $args[0] );
										$this->unoptions = FALSE;
										pagecmd( "setTotalNum", $args[1] );
										return $this;
								}
								$args[0] = 10;
						}
						if ( $args[0] == NULL )
						{
								return $this;
						}
						if ( !is_numeric( $args[0] ) && $args[0] <= 0 )
						{
								$args[0] = 10;
						}
						$this->options[strtolower( $method )] = $args[0];
						pagecmd( "setEachNum", $args[0] );
						$this->unoptions = FALSE;
						pagecmd( "setTotalNum", $this->get_field( "COUNT(*) AS nc_count" ) );
						return $this;
				}
				if ( in_array( strtolower( $method ), array( "min", "max", "count", "sum", "avg" ), TRUE ) )
				{
						$field = isset( $args[0] ) ? $args[0] : "*";
						return $this->get_field( strtoupper( $method )."(".$field.") AS nc_".$method );
				}
				if ( strtolower( substr( $method, 0, 6 ) ) == "getby_" )
				{
						$field = substr( $method, 6 );
						$where[$field] = $args[0];
						return $this->where( $where )->find( );
				}
				if ( strtolower( substr( $method, 0, 7 ) ) == "getfby_" )
				{
						$name = substr( $method, 7 );
						$where[$name] = $args[0];
						if ( strpos( $args[1], "," ) !== FALSE )
						{
								$args[1] = substr( $args[1], 0, strpos( $args[1], "," ) );
						}
						return $this->where( $where )->get_field( $args[1] );
				}
				$error = "Model Error:  Function ".$method." is not exists!";
				throw_exception( $error );
		}

		public function select( $options = array( ) )
		{
				if ( is_string( $options ) || is_numeric( $options ) )
				{
						$pk = $this->get_pk( );
						if ( strpos( $options, "," ) )
						{
								$where[$pk] = array(
										"IN",
										$options
								);
						}
						else
						{
								$where[$pk] = $this->fields[$this->table_name]['_pk_type'] == "int" ? intval( $options ) : $options;
						}
						$options = array( );
						$options['where'] = $where;
				}
				$options = $this->parse_options( $options );
				if ( empty( $options['where'] ) && empty( $options['limit'] ) )
				{
						$options['limit'] = 30;
				}
				else if ( $options['where'] !== TRUE && empty( $options['limit'] ) )
				{
						$options['limit'] = 1000;
				}
				$resultSet = $this->db->select( $options );
				if ( FALSE === $resultSet )
				{
						return FALSE;
				}
				if ( empty( $resultSet ) )
				{
						return;
				}
				return $resultSet;
		}

		protected function parse_options( $options = array() )
		{
				if ( is_array( $options ) )
				{
						$options = array_merge( $this->options, $options );
				}
                //$this->options = array(); /* 查询过后清空sql表达式组�?避免影响下次查询 原版本SHOPNC木有该语句*/
				if ( !isset( $options['table'] ) )
				{
						$options['table'] = $this->getTableName();/* 自动获取表名 */
				}
				else if ( FALSE !== strpos( trim( $options['table'], ", " ), "," ) )
				{
						foreach ( explode( ",", trim( $options['table'], ", " ) ) as $val )
						{
								$tmp[] = $this->getTableName( $val )." AS `".$val."`";
						}
						$options['table'] = implode( ",", $tmp );
				}
				else
				{
						$options['table'] = $this->getTableName( $options['table'] );
				}
				if ( !( $options['where'] === TRUE ) || is_array( $options['where'] ) && c( "check_field" ) )
				{
						if ( empty( $this->fields ) )
						{
								$error = "Model Error:  Table name is empty !";
								throw_exception( $error );
						}
						foreach ( (array)$options['where'] as $key => $val )
						{
								if ( $this->table_name == "" )
								{
										break;
								}
								if ( !strpos( $key, "|" ) )
								{
										if ( strpos( $key, "&" ) )
										{
												break;
										}
										if ( in_array( $key, array( "_op" ) ) )
										{
												break;
												break;
										}
								}
								else
								{
										break;
								}
                                //$_yumiao =$this->fields[$this->table_name];
                                /*
								if ( in_array( $key, $this->fields[$this->table_name], TRUE ) )
								{
								}
								if ( is_scalar( $val ) || is_array( $val ) )
								{
										//unset( is_array( $val )[$key] );
								}*/
                                if(in_array($key, $this->fields, true) && is_scalar($val))
                                {
                                    //$this->parse_type($options['where'], $key);
                                }
                                
						}
				}
				if ( $this->unoptions === TRUE )
				{
						$this->options = array( );
						return $options;
				}
				$this->unoptions = TRUE;
				return $options;
		}

		public function get_field( $field, $sepa = NULL )
		{
				$options['field'] = $field;
				$options = $this->parse_options( $options );
				if ( strpos( $field, "," ) )
				{
						$resultSet = $this->db->select( $options );
						if ( !empty( $resultSet ) )
						{
								$_field = explode( ",", $field );
								$field = array_keys( $resultSet[0] );
								$move = $_field[0] == $_field[1] ? FALSE : TRUE;
								$key = array_shift( $field );
								$key2 = array_shift( $field );
								$cols = array( );
								$count = count( $_field );
								foreach ( $resultSet as $result )
								{
										$name = $result[$key];
										if ( $move )
										{
												unset( $result[$key] );
										}
										if ( 2 == $count )
										{
												$cols[$name] = $result[$key2];
										}
										else
										{
												$cols[$name] = is_null( $sepa ) ? $result : implode( $sepa, $result );
										}
								}
								return $cols;
						}
				}
				else
				{
						$options['limit'] = 1;
						$result = $this->db->select( $options );
						if ( !empty( $result ) )
						{
								return reset( $result[0] );
						}
				}
		}

		public function find( $options = NULL )
		{
				if ( is_numeric( $options ) || is_string( $options ) )
				{
						$where[$this->get_pk( )] = $options;
						$options = array( );
						$options['where'] = $where;
				}
				else if ( !empty( $options ) )
				{
						return FALSE;
				}
				$options['limit'] = 1;
				$options = $this->parse_options( $options );
				$result = $this->db->select( $options );
				if ( FALSE === $result )
				{
						return FALSE;
				}
				if ( empty( $result ) )
				{
						return;
				}
				return $result[0];
		}

		public function delete( $options = array( ) )
		{
				if ( is_numeric( $options ) || is_string( $options ) )
				{
						$pk = $this->get_pk( );
						if ( strpos( $options, "," ) )
						{
								$where[$pk] = array(
										"IN",
										$options
								);
						}
						else
						{
								$where[$pk] = $this->fields['_pk_type'] == "int" ? intval( $options ) : $options;
								$pkValue = $options;
						}
						$options = array( );
						$options['where'] = $where;
				}
				$options = $this->parse_options( $options );
				$result = $this->db->delete( $options );
				if ( FALSE !== $result )
				{
						return TRUE;
				}
				return $result;
		}

		public function update( $data = "", $options = array( ) )
		{
				if ( empty( $data ) )
				{
						return FALSE;
				}
				if ( c( "check_field" ) )
				{
						$data = $this->chk_field( $data );
				}
				$options = $this->parse_options( $options );
				if ( !isset( $options['where'] ) )
				{
						if ( isset( $data[$this->get_pk( )] ) )
						{
								$pk = $this->get_pk( );
								$where[$pk] = $data[$pk];
								$options['where'] = $where;
								$pkValue = $data[$pk];
								unset( $data[$pk] );
						}
						else
						{
								return FALSE;
						}
				}
				$result = $this->db->update( $data, $options );
				if ( FALSE !== $result )
				{
						return TRUE;
				}
				return $result;
		}

		public function insert( $data = "", $replace = FALSE, $options = array( ) )
		{
				if ( empty( $data ) )
				{
						return FALSE;
				}
				$options = $this->parse_options( $options );
				if ( c( "check_field" ) )
				{
						$data = $this->chk_field( $data );
				}
				$result = $this->db->insert( $data, $options, $replace );
				if ( FALSE !== $result )
				{
						$insertId = $this->getLastId( );
						if ( $insertId )
						{
								return $insertId;
						}
				}
				return $result;
		}

		public function insertAll( $dataList, $options = array( ), $replace = FALSE )
		{
				if ( empty( $dataList ) )
				{
						return FALSE;
				}
				$options = $this->parse_options( $options );
				if ( c( "check_field" ) )
				{
						foreach ( $dataList as $key => $data )
						{
								$dataList[$key] = $this->chk_field( $data );
						}
				}
				$result = $this->db->insertAll( $dataList, $options, $replace );
				if ( FALSE !== $result )
				{
						return TRUE;
				}
				return $result;
		}

		public function query( $sql )
		{
				return DB::getall( $sql );
		}

		public function execute( $sql )
		{
				return DB::execute( $sql );
		}

		public function clear( )
		{
				if ( !$this->table_name || !$this->options['table'] )
				{
						return FALSE;
				}
				$options = $this->parse_options( );
				return $this->db->clear( $options );
		}

		protected function getTableName( $table = NULL )
		{
				if ( is_null( $table ) )
				{
						$return = "`".$this->table_prefix.$this->table_name."`";
						return $return;
				}
				$return = "`".$this->table_prefix.$table."`";
				return $return;
		}

		public function getLastId( )
		{
				return $this->db->getLastId( );
		}

		public function field( $field, $except = FALSE )
		{
				if ( TRUE === $field )
				{
						$fields = $this->getFields( );
						$field = $fields ? $fields : "*";
				}
				else if ( $except )
				{
						if ( is_string( $field ) )
						{
								$field = explode( ",", $field );
						}
						$fields = $this->getFields( );
						$field = $fields ? array_diff( $fields, $field ) : $field;
				}
				$this->options['field'] = $field;
				return $this;
		}

		public function getFields( )
		{
				if ( $this->fields )
				{
						$fields = $this->fields;
						unset( $fields['_autoinc'] );
						unset( $fields['_pk'] );
						unset( $fields['_type'] );
						return $fields;
				}
				return FALSE;
		}

		public function join( $join )
		{
				if ( FALSE !== strpos( $join, "," ) )
				{
						foreach ( explode( ",", $join ) as $key => $val )
						{
								if ( in_array( strtolower( $val ), array( "left", "inner", "right" ) ) )
								{
										$this->options['join'][] = strtoupper( $val )." JOIN";
								}
								else
								{
										$this->options['join'][] = "LEFT JOIN";
								}
						}
				}
				else if ( in_array( strtolower( $join ), array( "left", "inner", "right" ) ) )
				{
						$this->options['join'][] = strtoupper( $join )." JOIN";
				}
				return $this;
		}

		public function get_pk( )
		{
				if ( c( "check_field" ) )
				{
						if ( isset( $this->fields[$this->table_name]['_pk'] ) )
						{
								return $this->fields[$this->table_name]['_pk'];
						}
						return $this->pk;
				}
				if ( isset( $this->fields[$this->table_name] ) )
				{
						return $this->fields[$this->table_name];
				}
				return $this->pk;
		}

		protected function chk_field( $data )
		{
				if ( !empty( $this->fields[$this->table_name] ) )
				{
						foreach ( $data as $key => $val )
						{
								if ( !in_array( $key, $this->fields[$this->table_name], TRUE ) )
								{
										unset( $data[$key] );
								}
						}
				}
				return $data;
		}

		public function setInc( $field, $step = 1 )
		{
				return $this->set_field( $field, array(
						"exp",
						$field."+".$step
				) );
		}

		public function setDec( $field, $step = 1 )
		{
				return $this->set_field( $field, array(
						"exp",
						$field."-".$step
				) );
		}

		public function set_field( $field, $value = "" )
		{
				if ( is_array( $field ) )
				{
						$data = $field;
				}
				else
				{
						$data[$field] = $value;
				}
				return $this->update( $data );
		}

		public function showpage( $style = NULL )
		{
				return pagecmd( "show", $style );
		}

		public function cls( )
		{
				$this->options = array( );
				$this->table_name = "";
				return $this;
		}

}

?>
