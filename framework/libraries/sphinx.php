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

class SphinxClient
{

		public $_host = NULL;
		public $_port = NULL;
		public $_offset = NULL;
		public $_limit = NULL;
		public $_mode = NULL;
		public $_weights = NULL;
		public $_sort = NULL;
		public $_sortby = NULL;
		public $_min_id = NULL;
		public $_max_id = NULL;
		public $_filters = NULL;
		public $_groupby = NULL;
		public $_groupfunc = NULL;
		public $_groupsort = NULL;
		public $_groupdistinct = NULL;
		public $_maxmatches = NULL;
		public $_cutoff = NULL;
		public $_retrycount = NULL;
		public $_retrydelay = NULL;
		public $_anchor = NULL;
		public $_indexweights = NULL;
		public $_ranker = NULL;
		public $_maxquerytime = NULL;
		public $_fieldweights = NULL;
		public $_overrides = NULL;
		public $_select = NULL;
		public $_error = NULL;
		public $_warning = NULL;
		public $_connerror = NULL;
		public $_reqs = NULL;
		public $_mbenc = NULL;
		public $_arrayresult = NULL;
		public $_timeout = NULL;

		public function SphinxClient( )
		{
				$this->_host = "localhost";
				$this->_port = 9312;
				$this->_path = FALSE;
				$this->_socket = FALSE;
				$this->_offset = 0;
				$this->_limit = 20;
				$this->_mode = SPH_MATCH_ALL;
				$this->_weights = array( );
				$this->_sort = SPH_SORT_RELEVANCE;
				$this->_sortby = "";
				$this->_min_id = 0;
				$this->_max_id = 0;
				$this->_filters = array( );
				$this->_groupby = "";
				$this->_groupfunc = SPH_GROUPBY_DAY;
				$this->_groupsort = "@group desc";
				$this->_groupdistinct = "";
				$this->_maxmatches = 1000;
				$this->_cutoff = 0;
				$this->_retrycount = 0;
				$this->_retrydelay = 0;
				$this->_anchor = array( );
				$this->_indexweights = array( );
				$this->_ranker = SPH_RANK_PROXIMITY_BM25;
				$this->_maxquerytime = 0;
				$this->_fieldweights = array( );
				$this->_overrides = array( );
				$this->_select = "*";
				$this->_error = "";
				$this->_warning = "";
				$this->_connerror = FALSE;
				$this->_reqs = array( );
				$this->_mbenc = "";
				$this->_arrayresult = FALSE;
				$this->_timeout = 0;
		}

		public function __destruct( )
		{
				if ( $this->_socket !== FALSE )
				{
						fclose( $this->_socket );
				}
		}

		public function GetLastError( )
		{
				return $this->_error;
		}

		public function GetLastWarning( )
		{
				return $this->_warning;
		}

		public function IsConnectError( )
		{
				return $this->_connerror;
		}

		public function SetServer( $host, $port = 0 )
		{
				assert( is_string( $host ) );
				if ( $host[0] == "/" )
				{
						$this->_path = "unix://".$host;
				}
				else if ( substr( $host, 0, 7 ) == "unix://" )
				{
						$this->_path = $host;
				}
				else
				{
						assert( is_int( $port ) );
						$this->_host = $host;
						$this->_port = $port;
						$this->_path = "";
				}
		}

		public function SetConnectTimeout( $timeout )
		{
				assert( is_numeric( $timeout ) );
				$this->_timeout = $timeout;
		}

		public function _Send( $handle, $data, $length )
		{
				if ( feof( $handle ) || fwrite( $handle, $data, $length ) !== $length )
				{
						$this->_error = "connection unexpectedly closed (timed out?)";
						$this->_connerror = TRUE;
						return FALSE;
				}
				return TRUE;
		}

		public function _MBPush( )
		{
				$this->_mbenc = "";
				if ( ini_get( "mbstring.func_overload" ) & 2 )
				{
						$this->_mbenc = mb_internal_encoding( );
						mb_internal_encoding( "latin1" );
				}
		}

		public function _MBPop( )
		{
				if ( $this->_mbenc )
				{
						mb_internal_encoding( $this->_mbenc );
				}
		}

		public function _Connect( )
		{
				if ( $this->_socket !== FALSE )
				{
						if ( !@feof( $this->_socket ) )
						{
								return $this->_socket;
						}
						$this->_socket = FALSE;
				}
				$errno = 0;
				$errstr = "";
				$this->_connerror = FALSE;
				if ( $this->_path )
				{
						$host = $this->_path;
						$port = 0;
				}
				else
				{
						$host = $this->_host;
						$port = $this->_port;
				}
				if ( $this->_timeout <= 0 )
				{
						$fp = @fsockopen( $host, $port, &$errno, &$errstr );
				}
				else
				{
						$fp = @fsockopen( $host, $port, &$errno, &$errstr, $this->_timeout );
				}
				if ( !$fp )
				{
						if ( $this->_path )
						{
								$location = $this->_path;
						}
						else
						{
								$location = "{$this->_host}:{$this->_port}";
						}
						$errstr = trim( $errstr );
						$this->_error = "connection to ".$location." failed (errno={$errno}, msg={$errstr})";
						$this->_connerror = TRUE;
						return FALSE;
				}
				if ( !$this->_Send( $fp, pack( "N", 1 ), 4 ) )
				{
						fclose( $fp );
						$this->_error = "failed to send client protocol version";
						return FALSE;
				}
				list( , $v ) = unpack( "N*", fread( $fp, 4 ) );
				$v = ( integer )$v;
				if ( $v < 1 )
				{
						fclose( $fp );
						$this->_error = "expected searchd protocol version 1+, got version '".$v."'";
						return FALSE;
				}
				return $fp;
		}

		public function _GetResponse( $fp, $client_ver )
		{
				$response = "";
				$len = 0;
				$header = fread( $fp, 8 );
				if ( strlen( $header ) == 8 )
				{
						list( $status, $ver, $len ) = array_values( unpack( "n2a/Nb", $header ) );
						$left = $len;
						while ( 0 < $left && !feof( $fp ) )
						{
								$chunk = fread( $fp, $left );
								if ( $chunk )
								{
										$response .= $chunk;
										$left -= strlen( $chunk );
								}
						}
				}
				if ( $this->_socket === FALSE )
				{
						fclose( $fp );
				}
				$read = strlen( $response );
				if ( !$response && $read != $len )
				{
						$this->_error = $len ? "failed to read searchd response (status=".$status.", ver={$ver}, len={$len}, read={$read})" : "received zero-sized searchd response";
						return FALSE;
				}
				if ( $status == SEARCHD_WARNING )
				{
						list( , $wlen ) = unpack( "N*", substr( $response, 0, 4 ) );
						$this->_warning = substr( $response, 4, $wlen );
						return substr( $response, 4 + $wlen );
				}
				if ( $status == SEARCHD_ERROR )
				{
						$this->_error = "searchd error: ".substr( $response, 4 );
						return FALSE;
				}
				if ( $status == SEARCHD_RETRY )
				{
						$this->_error = "temporary searchd error: ".substr( $response, 4 );
						return FALSE;
				}
				if ( $status != SEARCHD_OK )
				{
						$this->_error = "unknown status code '".$status."'";
						return FALSE;
				}
				if ( $ver < $client_ver )
				{
						$this->_warning = sprintf( "searchd command v.%d.%d older than client's v.%d.%d, some options might not work", $ver >> 8, $ver & 255, $client_ver >> 8, $client_ver & 255 );
				}
				return $response;
		}

		public function SetLimits( $offset, $limit, $max = 0, $cutoff = 0 )
		{
				assert( is_int( $offset ) );
				assert( is_int( $limit ) );
				assert( 0 <= $offset );
				assert( 0 < $limit );
				assert( 0 <= $max );
				$this->_offset = $offset;
				$this->_limit = $limit;
				if ( 0 < $max )
				{
						$this->_maxmatches = $max;
				}
				if ( 0 < $cutoff )
				{
						$this->_cutoff = $cutoff;
				}
		}

		public function SetMaxQueryTime( $max )
		{
				assert( is_int( $max ) );
				assert( 0 <= $max );
				$this->_maxquerytime = $max;
		}

		public function SetMatchMode( $mode )
		{
				assert( $mode == SPH_MATCH_EXTENDED2 );
				$this->_mode = $mode;
		}

		public function SetRankingMode( $ranker )
		{
				assert( $ranker == SPH_RANK_PROXIMITY );
				$this->_ranker = $ranker;
		}

		public function SetSortMode( $mode, $sortby = "" )
		{
				assert( $mode == SPH_SORT_EXPR );
				assert( is_string( $sortby ) );
				assert( 0 < strlen( $sortby ) );
				$this->_sort = $mode;
				$this->_sortby = $sortby;
		}

		public function SetWeights( $weights )
		{
				assert( is_array( $weights ) );
				foreach ( $weights as $weight )
				{
						assert( is_int( $weight ) );
				}
				$this->_weights = $weights;
		}

		public function SetFieldWeights( $weights )
		{
				assert( is_array( $weights ) );
				foreach ( $weights as $name => $weight )
				{
						assert( is_string( $name ) );
						assert( is_int( $weight ) );
				}
				$this->_fieldweights = $weights;
		}

		public function SetIndexWeights( $weights )
		{
				assert( is_array( $weights ) );
				foreach ( $weights as $index => $weight )
				{
						assert( is_string( $index ) );
						assert( is_int( $weight ) );
				}
				$this->_indexweights = $weights;
		}

		public function SetIDRange( $min, $max )
		{
				assert( is_numeric( $min ) );
				assert( is_numeric( $max ) );
				assert( $min <= $max );
				$this->_min_id = $min;
				$this->_max_id = $max;
		}

		public function SetFilter( $attribute, $values, $exclude = FALSE )
		{
				assert( is_string( $attribute ) );
				assert( is_array( $values ) );
				assert( count( $values ) );
				if ( is_array( $values ) && count( $values ) )
				{
						foreach ( $values as $value )
						{
								assert( is_numeric( $value ) );
						}
						$this->_filters[] = array(
								"type" => SPH_FILTER_VALUES,
								"attr" => $attribute,
								"exclude" => $exclude,
								"values" => $values
						);
				}
		}

		public function SetFilterRange( $attribute, $min, $max, $exclude = FALSE )
		{
				assert( is_string( $attribute ) );
				assert( is_numeric( $min ) );
				assert( is_numeric( $max ) );
				assert( $min <= $max );
				$this->_filters[] = array(
						"type" => SPH_FILTER_RANGE,
						"attr" => $attribute,
						"exclude" => $exclude,
						"min" => $min,
						"max" => $max
				);
		}

		public function SetFilterFloatRange( $attribute, $min, $max, $exclude = FALSE )
		{
				assert( is_string( $attribute ) );
				assert( is_float( $min ) );
				assert( is_float( $max ) );
				assert( $min <= $max );
				$this->_filters[] = array(
						"type" => SPH_FILTER_FLOATRANGE,
						"attr" => $attribute,
						"exclude" => $exclude,
						"min" => $min,
						"max" => $max
				);
		}

		public function SetGeoAnchor( $attrlat, $attrlong, $lat, $long )
		{
				assert( is_string( $attrlat ) );
				assert( is_string( $attrlong ) );
				assert( is_float( $lat ) );
				assert( is_float( $long ) );
				$this->_anchor = array(
						"attrlat" => $attrlat,
						"attrlong" => $attrlong,
						"lat" => $lat,
						"long" => $long
				);
		}

		public function SetGroupBy( $attribute, $func, $groupsort = "@group desc" )
		{
				assert( is_string( $attribute ) );
				assert( is_string( $groupsort ) );
				assert( $func == SPH_GROUPBY_ATTRPAIR );
				$this->_groupby = $attribute;
				$this->_groupfunc = $func;
				$this->_groupsort = $groupsort;
		}

		public function SetGroupDistinct( $attribute )
		{
				assert( is_string( $attribute ) );
				$this->_groupdistinct = $attribute;
		}

		public function SetRetries( $count, $delay = 0 )
		{
				assert( 0 <= $count );
				assert( 0 <= $delay );
				$this->_retrycount = $count;
				$this->_retrydelay = $delay;
		}

		public function SetArrayResult( $arrayresult )
		{
				assert( is_bool( $arrayresult ) );
				$this->_arrayresult = $arrayresult;
		}

		public function SetOverride( $attrname, $attrtype, $values )
		{
				assert( is_string( $attrname ) );
				assert( in_array( $attrtype, array(
						SPH_ATTR_INTEGER,
						SPH_ATTR_TIMESTAMP,
						SPH_ATTR_BOOL,
						SPH_ATTR_FLOAT,
						SPH_ATTR_BIGINT
				) ) );
				assert( is_array( $values ) );
				$this->_overrides[$attrname] = array(
						"attr" => $attrname,
						"type" => $attrtype,
						"values" => $values
				);
		}

		public function SetSelect( $select )
		{
				assert( is_string( $select ) );
				$this->_select = $select;
		}

		public function ResetFilters( )
		{
				$this->_filters = array( );
				$this->_anchor = array( );
		}

		public function ResetGroupBy( )
		{
				$this->_groupby = "";
				$this->_groupfunc = SPH_GROUPBY_DAY;
				$this->_groupsort = "@group desc";
				$this->_groupdistinct = "";
		}

		public function ResetOverrides( )
		{
				$this->_overrides = array( );
		}

		public function Query( $query, $index = "*", $comment = "" )
		{
				assert( empty( $this->_reqs ) );
				$this->AddQuery( $query, $index, $comment );
				$results = $this->RunQueries( );
				$this->_reqs = array( );
				if ( !is_array( $results ) )
				{
						return FALSE;
				}
				$this->_error = $results[0]['error'];
				$this->_warning = $results[0]['warning'];
				if ( $results[0]['status'] == SEARCHD_ERROR )
				{
						return FALSE;
				}
				return $results[0];
		}

		public function _PackFloat( $f )
		{
				$t1 = pack( "f", $f );
				list( , $t2 ) = unpack( "L*", $t1 );
				return pack( "N", $t2 );
		}

		public function AddQuery( $query, $index = "*", $comment = "" )
		{
				$this->_MBPush( );
				$req = pack( "NNNNN", $this->_offset, $this->_limit, $this->_mode, $this->_ranker, $this->_sort );
				$req .= pack( "N", strlen( $this->_sortby ) ).$this->_sortby;
				$req .= pack( "N", strlen( $query ) ).$query;
				$req .= pack( "N", count( $this->_weights ) );
				foreach ( $this->_weights as $weight )
				{
						$req .= pack( "N", ( integer )$weight );
				}
				$req .= pack( "N", strlen( $index ) ).$index;
				$req .= pack( "N", 1 );
				$req .= sphpacku64( $this->_min_id ).sphpacku64( $this->_max_id );
				$req .= pack( "N", count( $this->_filters ) );
				foreach ( $this->_filters as $filter )
				{
						$req .= pack( "N", strlen( $filter['attr'] ) ).$filter['attr'];
						$req .= pack( "N", $filter['type'] );
						switch ( $filter['type'] )
						{
						case SPH_FILTER_VALUES :
								$req .= pack( "N", count( $filter['values'] ) );
								foreach ( $filter['values'] as $value )
								{
										$req .= sphpacki64( $value );
								}
								break;
						case SPH_FILTER_RANGE :
								$req .= sphpacki64( $filter['min'] ).sphpacki64( $filter['max'] );
								break;
						case SPH_FILTER_FLOATRANGE :
								$req .= $this->_PackFloat( $filter['min'] ).$this->_PackFloat( $filter['max'] );
								break;
						default :
								assert( TRUE );
						}
						$req .= pack( "N", $filter['exclude'] );
				}
				$req .= pack( "NN", $this->_groupfunc, strlen( $this->_groupby ) ).$this->_groupby;
				$req .= pack( "N", $this->_maxmatches );
				$req .= pack( "N", strlen( $this->_groupsort ) ).$this->_groupsort;
				$req .= pack( "NNN", $this->_cutoff, $this->_retrycount, $this->_retrydelay );
				$req .= pack( "N", strlen( $this->_groupdistinct ) ).$this->_groupdistinct;
				if ( empty( $this->_anchor ) )
				{
						$req .= pack( "N", 0 );
				}
				else
				{
						$a =& $this->_anchor;
						$req .= pack( "N", 1 );
						$req .= pack( "N", strlen( $a['attrlat'] ) ).$a['attrlat'];
						$req .= pack( "N", strlen( $a['attrlong'] ) ).$a['attrlong'];
						$req .= $this->_PackFloat( $a['lat'] ).$this->_PackFloat( $a['long'] );
				}
				$req .= pack( "N", count( $this->_indexweights ) );
				foreach ( $this->_indexweights as $idx => $weight )
				{
						$req .= pack( "N", strlen( $idx ) ).$idx.pack( "N", $weight );
				}
				$req .= pack( "N", $this->_maxquerytime );
				$req .= pack( "N", count( $this->_fieldweights ) );
				foreach ( $this->_fieldweights as $field => $weight )
				{
						$req .= pack( "N", strlen( $field ) ).$field.pack( "N", $weight );
				}
				$req .= pack( "N", strlen( $comment ) ).$comment;
				$req .= pack( "N", count( $this->_overrides ) );
				foreach ( $this->_overrides as $key => $entry )
				{
						$req .= pack( "N", strlen( $entry['attr'] ) ).$entry['attr'];
						$req .= pack( "NN", $entry['type'], count( $entry['values'] ) );
						foreach ( $entry['values'] as $id => $val )
						{
								assert( is_numeric( $id ) );
								assert( is_numeric( $val ) );
								$req .= sphpacku64( $id );
								switch ( $entry['type'] )
								{
								case SPH_ATTR_FLOAT :
										$req .= $this->_PackFloat( $val );
										break;
								case SPH_ATTR_BIGINT :
										$req .= sphpacki64( $val );
										break;
								default :
										$req .= pack( "N", $val );
								}
						}
				}
				$req .= pack( "N", strlen( $this->_select ) ).$this->_select;
				$this->_MBPop( );
				$this->_reqs[] = $req;
				return count( $this->_reqs ) - 1;
		}

		public function RunQueries( )
		{
				if ( empty( $this->_reqs ) )
				{
						$this->_error = "no queries defined, issue AddQuery() first";
						return FALSE;
				}
				$this->_MBPush( );
				if ( !( $fp = $this->_Connect( ) ) )
				{
						$this->_MBPop( );
						return FALSE;
				}
				$nreqs = count( $this->_reqs );
				$req = join( "", $this->_reqs );
				$len = 4 + strlen( $req );
				$req = pack( "nnNN", SEARCHD_COMMAND_SEARCH, VER_COMMAND_SEARCH, $len, $nreqs ).$req;
				if ( !$this->_Send( $fp, $req, $len + 8 ) && !( $response = $this->_GetResponse( $fp, VER_COMMAND_SEARCH ) ) )
				{
						$this->_MBPop( );
						return FALSE;
				}
				$this->_reqs = array( );
				return $this->_ParseSearchResponse( $response, $nreqs );
		}

		public function _ParseSearchResponse( $response, $nreqs )
		{
				$p = 0;
				$max = strlen( $response );
				$results = array( );
				$ires = 0;
				for ( ;	$ires < $nreqs && $p < $max;	++$ires	)
				{
						$results[] = array( );
						$result =& $results[$ires];
						$result['error'] = "";
						$result['warning'] = "";
						list( , $status ) = unpack( "N*", substr( $response, $p, 4 ) );
						$p += 4;
						$result['status'] = $status;
						if ( $status != SEARCHD_OK )
						{
								list( , $len ) = unpack( "N*", substr( $response, $p, 4 ) );
								$p += 4;
								$message = substr( $response, $p, $len );
								$p += $len;
								if ( $status == SEARCHD_WARNING )
								{
										$result['warning'] = $message;
								}
								else
								{
										$result['error'] = $message;
								}
						}
						else
						{
								$fields = array( );
								$attrs = array( );
								list( , $nfields ) = unpack( "N*", substr( $response, $p, 4 ) );
								$p += 4;
								while ( 0 < $nfields-- && $p < $max )
								{
										list( , $len ) = unpack( "N*", substr( $response, $p, 4 ) );
										$p += 4;
										$fields[] = substr( $response, $p, $len );
										$p += $len;
								}
								$result['fields'] = $fields;
								list( , $nattrs ) = unpack( "N*", substr( $response, $p, 4 ) );
								$p += 4;
								while ( 0 < $nattrs-- && $p < $max )
								{
										list( , $len ) = unpack( "N*", substr( $response, $p, 4 ) );
										$p += 4;
										$attr = substr( $response, $p, $len );
										$p += $len;
										list( , $type ) = unpack( "N*", substr( $response, $p, 4 ) );
										$p += 4;
										$attrs[$attr] = $type;
								}
								$result['attrs'] = $attrs;
								list( , $count ) = unpack( "N*", substr( $response, $p, 4 ) );
								$p += 4;
								list( , $id64 ) = unpack( "N*", substr( $response, $p, 4 ) );
								$p += 4;
								$idx = -1;
								while ( 0 < $count-- && $p < $max )
								{
										++$idx;
										if ( $id64 )
										{
												$doc = sphunpacku64( substr( $response, $p, 8 ) );
												$p += 8;
												list( , $weight ) = unpack( "N*", substr( $response, $p, 4 ) );
												$p += 4;
										}
										else
										{
												list( $doc, $weight ) = array_values( unpack( "N*N*", substr( $response, $p, 8 ) ) );
												$p += 8;
												$doc = sphfixuint( $doc );
										}
										$weight = sprintf( "%u", $weight );
										if ( $this->_arrayresult )
										{
												$result['matches'][$idx] = array(
														"id" => $doc,
														"weight" => $weight
												);
										}
										else
										{
												$result['matches'][$doc]['weight'] = $weight;
										}
										$attrvals = array( );
										foreach ( $attrs as $attr => $type )
										{
												if ( $type == SPH_ATTR_BIGINT )
												{
														$attrvals[$attr] = sphunpacki64( substr( $response, $p, 8 ) );
														$p += 8;
												}
												else if ( $type == SPH_ATTR_FLOAT )
												{
														list( , $uval ) = unpack( "N*", substr( $response, $p, 4 ) );
														$p += 4;
														list( , $fval ) = unpack( "f*", pack( "L", $uval ) );
														$attrvals[$attr] = $fval;
												}
												else
												{
														list( , $val ) = unpack( "N*", substr( $response, $p, 4 ) );
														$p += 4;
														if ( $type & SPH_ATTR_MULTI )
														{
																$attrvals[$attr] = array( );
																$nvalues = $val;
																do
																{
																		if ( !( 0 < $nvalues-- ) && !( $p < $max ) )
																		{
																				list( , $val ) = unpack( "N*", substr( $response, $p, 4 ) );
																				$p += 4;
																				$attrvals[$attr][] = sphfixuint( $val );
																		}
																} while ( 1 );
														}
														$attrvals[$attr] = sphfixuint( $val );
												}
										}
										if ( $this->_arrayresult )
										{
												$result['matches'][$idx]['attrs'] = $attrvals;
										}
										else
										{
												$result['matches'][$doc]['attrs'] = $attrvals;
										}
								}
								list( $total, $total_found, $msecs, $words ) = array_values( unpack( "N*N*N*N*", substr( $response, $p, 16 ) ) );
								$result['total'] = sprintf( "%u", $total );
								$result['total_found'] = sprintf( "%u", $total_found );
								$result['time'] = sprintf( "%.3f", $msecs / 1000 );
								$p += 16;
								while ( !( 0 < $words-- ) && !( $p < $max ) )
								{
										list( , $len ) = unpack( "N*", substr( $response, $p, 4 ) );
										$p += 4;
										$word = substr( $response, $p, $len );
										$p += $len;
										list( $docs, $hits ) = array_values( unpack( "N*N*", substr( $response, $p, 8 ) ) );
										$p += 8;
										$result['words'][$word] = array(
												"docs" => sprintf( "%u", $docs ),
												"hits" => sprintf( "%u", $hits )
										);
								}
						}
				}
				$this->_MBPop( );
				return $results;
		}

		public function BuildExcerpts( $docs, $index, $words, $opts = array( ) )
		{
				assert( is_array( $docs ) );
				assert( is_string( $index ) );
				assert( is_string( $words ) );
				assert( is_array( $opts ) );
				$this->_MBPush( );
				if ( !( $fp = $this->_Connect( ) ) )
				{
						$this->_MBPop( );
						return FALSE;
				}
				if ( !isset( $opts['before_match'] ) )
				{
						$opts['before_match'] = "<b>";
				}
				if ( !isset( $opts['after_match'] ) )
				{
						$opts['after_match'] = "</b>";
				}
				if ( !isset( $opts['chunk_separator'] ) )
				{
						$opts['chunk_separator'] = " ... ";
				}
				if ( !isset( $opts['limit'] ) )
				{
						$opts['limit'] = 256;
				}
				if ( !isset( $opts['around'] ) )
				{
						$opts['around'] = 5;
				}
				if ( !isset( $opts['exact_phrase'] ) )
				{
						$opts['exact_phrase'] = FALSE;
				}
				if ( !isset( $opts['single_passage'] ) )
				{
						$opts['single_passage'] = FALSE;
				}
				if ( !isset( $opts['use_boundaries'] ) )
				{
						$opts['use_boundaries'] = FALSE;
				}
				if ( !isset( $opts['weight_order'] ) )
				{
						$opts['weight_order'] = FALSE;
				}
				$flags = 1;
				if ( $opts['exact_phrase'] )
				{
						$flags |= 2;
				}
				if ( $opts['single_passage'] )
				{
						$flags |= 4;
				}
				if ( $opts['use_boundaries'] )
				{
						$flags |= 8;
				}
				if ( $opts['weight_order'] )
				{
						$flags |= 16;
				}
				$req = pack( "NN", 0, $flags );
				$req .= pack( "N", strlen( $index ) ).$index;
				$req .= pack( "N", strlen( $words ) ).$words;
				$req .= pack( "N", strlen( $opts['before_match'] ) ).$opts['before_match'];
				$req .= pack( "N", strlen( $opts['after_match'] ) ).$opts['after_match'];
				$req .= pack( "N", strlen( $opts['chunk_separator'] ) ).$opts['chunk_separator'];
				$req .= pack( "N", ( integer )$opts['limit'] );
				$req .= pack( "N", ( integer )$opts['around'] );
				$req .= pack( "N", count( $docs ) );
				foreach ( $docs as $doc )
				{
						assert( is_string( $doc ) );
						$req .= pack( "N", strlen( $doc ) ).$doc;
				}
				$len = strlen( $req );
				$req = pack( "nnN", SEARCHD_COMMAND_EXCERPT, VER_COMMAND_EXCERPT, $len ).$req;
				if ( !$this->_Send( $fp, $req, $len + 8 ) && !( $response = $this->_GetResponse( $fp, VER_COMMAND_EXCERPT ) ) )
				{
						$this->_MBPop( );
						return FALSE;
				}
				$pos = 0;
				$res = array( );
				$rlen = strlen( $response );
				$i = 0;
				for ( ;	$i < count( $docs );	++$i	)
				{
						list( , $len ) = unpack( "N*", substr( $response, $pos, 4 ) );
						$pos += 4;
						if ( $rlen < $pos + $len )
						{
								$this->_error = "incomplete reply";
								$this->_MBPop( );
								return FALSE;
						}
						$res[] = $len ? substr( $response, $pos, $len ) : "";
						$pos += $len;
				}
				$this->_MBPop( );
				return $res;
		}

		public function BuildKeywords( $query, $index, $hits )
		{
				assert( is_string( $query ) );
				assert( is_string( $index ) );
				assert( is_bool( $hits ) );
				$this->_MBPush( );
				if ( !( $fp = $this->_Connect( ) ) )
				{
						$this->_MBPop( );
						return FALSE;
				}
				$req = pack( "N", strlen( $query ) ).$query;
				$req .= pack( "N", strlen( $index ) ).$index;
				$req .= pack( "N", ( integer )$hits );
				$len = strlen( $req );
				$req = pack( "nnN", SEARCHD_COMMAND_KEYWORDS, VER_COMMAND_KEYWORDS, $len ).$req;
				if ( !$this->_Send( $fp, $req, $len + 8 ) && !( $response = $this->_GetResponse( $fp, VER_COMMAND_KEYWORDS ) ) )
				{
						$this->_MBPop( );
						return FALSE;
				}
				$pos = 0;
				$res = array( );
				$rlen = strlen( $response );
				list( , $nwords ) = unpack( "N*", substr( $response, $pos, 4 ) );
				$pos += 4;
				$i = 0;
				for ( ;	$i < $nwords;	++$i	)
				{
						list( , $len ) = unpack( "N*", substr( $response, $pos, 4 ) );
						$pos += 4;
						$tokenized = $len ? substr( $response, $pos, $len ) : "";
						$pos += $len;
						list( , $len ) = unpack( "N*", substr( $response, $pos, 4 ) );
						$pos += 4;
						$normalized = $len ? substr( $response, $pos, $len ) : "";
						$pos += $len;
						$res[] = array(
								"tokenized" => $tokenized,
								"normalized" => $normalized
						);
						if ( $hits )
						{
								list( $ndocs, $nhits ) = array_values( unpack( "N*N*", substr( $response, $pos, 8 ) ) );
								$pos += 8;
								$res[$i]['docs'] = $ndocs;
								$res[$i]['hits'] = $nhits;
						}
						if ( !( $rlen < $pos ) )
						{
								continue;
						}
						$this->_error = "incomplete reply";
						$this->_MBPop( );
						return FALSE;
				}
				$this->_MBPop( );
				return $res;
		}

		public function EscapeString( $string )
		{
				$from = array( "\\", "(", ")", "|", "-", "!", "@", "~", "\"", "&", "/", "^", "\$", "=" );
				$to = array( "\\\\", "\\(", "\\)", "\\|", "\\-", "\\!", "\\@", "\\~", "\\\"", "\\&", "\\/", "\\^", "\\\$", "\\=" );
				return str_replace( $from, $to, $string );
		}

		public function UpdateAttributes( $index, $attrs, $values, $mva = FALSE )
		{
				assert( is_string( $index ) );
				assert( is_bool( $mva ) );
				assert( is_array( $attrs ) );
				foreach ( $attrs as $attr )
				{
						assert( is_string( $attr ) );
				}
				assert( is_array( $values ) );
				foreach ( $values as $id => $entry )
				{
						assert( is_numeric( $id ) );
						assert( is_array( $entry ) );
						assert( count( $entry ) == count( $attrs ) );
						foreach ( $entry as $v )
						{
								if ( $mva )
								{
										assert( is_array( $v ) );
										foreach ( $v as $vv )
										{
												assert( is_int( $vv ) );
										}
								}
								else
								{
										assert( is_int( $v ) );
								}
						}
				}
				$req = pack( "N", strlen( $index ) ).$index;
				$req .= pack( "N", count( $attrs ) );
				foreach ( $attrs as $attr )
				{
						$req .= pack( "N", strlen( $attr ) ).$attr;
						$req .= pack( "N", $mva ? 1 : 0 );
				}
				$req .= pack( "N", count( $values ) );
				foreach ( $values as $id => $entry )
				{
						$req .= sphpacku64( $id );
						foreach ( $entry as $v )
						{
								$req .= pack( "N", $mva ? count( $v ) : $v );
								if ( $mva )
								{
										foreach ( $v as $vv )
										{
												$req .= pack( "N", $vv );
										}
								}
						}
				}
				if ( !( $fp = $this->_Connect( ) ) )
				{
						return -1;
				}
				$len = strlen( $req );
				$req = pack( "nnN", SEARCHD_COMMAND_UPDATE, VER_COMMAND_UPDATE, $len ).$req;
				if ( !$this->_Send( $fp, $req, $len + 8 ) )
				{
						return -1;
				}
				if ( !( $response = $this->_GetResponse( $fp, VER_COMMAND_UPDATE ) ) )
				{
						return -1;
				}
				list( , $updated ) = unpack( "N*", substr( $response, 0, 4 ) );
				return $updated;
		}

		public function Open( )
		{
				if ( $this->_socket !== FALSE )
				{
						$this->_error = "already connected";
						return FALSE;
				}
				if ( !( $fp = $this->_Connect( ) ) )
				{
						return FALSE;
				}
				$req = pack( "nnNN", SEARCHD_COMMAND_PERSIST, 0, 4, 1 );
				if ( !$this->_Send( $fp, $req, 12 ) )
				{
						return FALSE;
				}
				$this->_socket = $fp;
				return TRUE;
		}

		public function Close( )
		{
				if ( $this->_socket === FALSE )
				{
						$this->_error = "not connected";
						return FALSE;
				}
				fclose( $this->_socket );
				$this->_socket = FALSE;
				return TRUE;
		}

		public function Status( )
		{
				$this->_MBPush( );
				if ( !( $fp = $this->_Connect( ) ) )
				{
						$this->_MBPop( );
						return FALSE;
				}
				$req = pack( "nnNN", SEARCHD_COMMAND_STATUS, VER_COMMAND_STATUS, 4, 1 );
				if ( !$this->_Send( $fp, $req, 12 ) && !( $response = $this->_GetResponse( $fp, VER_COMMAND_STATUS ) ) )
				{
						$this->_MBPop( );
						return FALSE;
				}
				$res = substr( $response, 4 );
				$p = 0;
				list( $rows, $cols ) = array_values( unpack( "N*N*", substr( $response, $p, 8 ) ) );
				$p += 8;
				$res = array( );
				$i = 0;
				for ( ;	$i < $rows;	++$i	)
				{
						$j = 0;
						for ( ;	$j < $cols;	++$j	)
						{
								list( , $len ) = unpack( "N*", substr( $response, $p, 4 ) );
								$p += 4;
								$res[$i][] = substr( $response, $p, $len );
								$p += $len;
						}
				}
				$this->_MBPop( );
				return $res;
		}

}

function sphPackI64( $v )
{
		assert( is_numeric( $v ) );
		if ( 8 <= PHP_INT_SIZE )
		{
				$v = ( integer )$v;
				return pack( "NN", $v >> 32, $v & 4.29497e+009 );
		}
		if ( is_int( $v ) )
		{
				return pack( "NN", $v < 0 ? -1 : 0, $v );
		}
		if ( function_exists( "bcmul" ) )
		{
				if ( bccomp( $v, 0 ) == -1 )
				{
						$v = bcadd( "18446744073709551616", $v );
				}
				$h = bcdiv( $v, "4294967296", 0 );
				$l = bcmod( $v, "4294967296" );
				return pack( "NN", ( double )$h, ( double )$l );
		}
		$p = max( 0, strlen( $v ) - 13 );
		$lo = abs( ( double )substr( $v, $p ) );
		$hi = abs( ( double )substr( $v, 0, $p ) );
		$m = $lo + $hi * 1.31613e+009;
		$q = floor( $m / 4.29497e+009 );
		$l = $m - $q * 4.29497e+009;
		$h = $hi * 2328 + $q;
		if ( $v < 0 )
		{
				if ( $l == 0 )
				{
						$h = 4.29497e+009 - $h;
				}
				else
				{
						$h = 4.29497e+009 - $h;
						$l = 4.29497e+009 - $l;
				}
		}
		return pack( "NN", $h, $l );
}

function sphPackU64( $v )
{
		assert( is_numeric( $v ) );
		if ( 8 <= PHP_INT_SIZE )
		{
				assert( 0 <= $v );
				if ( is_int( $v ) )
				{
						return pack( "NN", $v >> 32, $v & 4.29497e+009 );
				}
				if ( function_exists( "bcmul" ) )
				{
						$h = bcdiv( $v, 4.29497e+009, 0 );
						$l = bcmod( $v, 4.29497e+009 );
						return pack( "NN", $h, $l );
				}
				$p = max( 0, strlen( $v ) - 13 );
				$lo = ( integer )substr( $v, $p );
				$hi = ( integer )substr( $v, 0, $p );
				$m = $lo + $hi * 1316134912;
				$l = $m % 0;
				$h = $hi * 2328 + ( integer )( $m / 4.29497e+009 );
				return pack( "NN", $h, $l );
		}
		if ( is_int( $v ) )
		{
				return pack( "NN", 0, $v );
		}
		if ( function_exists( "bcmul" ) )
		{
				$h = bcdiv( $v, "4294967296", 0 );
				$l = bcmod( $v, "4294967296" );
				return pack( "NN", ( double )$h, ( double )$l );
		}
		$p = max( 0, strlen( $v ) - 13 );
		$lo = ( double )substr( $v, $p );
		$hi = ( double )substr( $v, 0, $p );
		$m = $lo + $hi * 1.31613e+009;
		$q = floor( $m / 4.29497e+009 );
		$l = $m - $q * 4.29497e+009;
		$h = $hi * 2328 + $q;
		return pack( "NN", $h, $l );
}

function sphUnpackU64( $v )
{
		list( $hi, $lo ) = array_values( unpack( "N*N*", $v ) );
		if ( 8 <= PHP_INT_SIZE )
		{
				if ( $hi < 0 )
				{
						$hi += 1;
				}
				if ( $lo < 0 )
				{
						$lo += 1;
				}
				if ( $hi <= 2147483647 )
				{
						return ( $hi << 32 ) + $lo;
				}
				if ( function_exists( "bcmul" ) )
				{
						return bcadd( $lo, bcmul( $hi, "4294967296" ) );
				}
				$C = 100000;
				$h = ( ( integer )( $hi / $C ) << 32 ) + ( integer )( $lo / $C );
				$l = ( $hi % $C << 32 ) + $lo % $C;
				if ( $C < $l )
				{
						$h += ( integer )( $l / $C );
						$l %= $C;
				}
				if ( $h == 0 )
				{
						return $l;
				}
				return sprintf( "%d%05d", $h, $l );
		}
		if ( $hi == 0 )
		{
				if ( 0 < $lo )
				{
						return $lo;
				}
				return sprintf( "%u", $lo );
		}
		$hi = sprintf( "%u", $hi );
		$lo = sprintf( "%u", $lo );
		if ( function_exists( "bcmul" ) )
		{
				return bcadd( $lo, bcmul( $hi, "4294967296" ) );
		}
		$hi = ( double )$hi;
		$lo = ( double )$lo;
		$q = floor( $hi / 1e+007 );
		$r = $hi - $q * 1e+007;
		$m = $lo + $r * 4.9673e+006;
		$mq = floor( $m / 1e+007 );
		$l = $m - $mq * 1e+007;
		$h = $q * 4.29497e+009 + $r * 429 + $mq;
		$h = sprintf( "%.0f", $h );
		$l = sprintf( "%07.0f", $l );
		if ( $h == "0" )
		{
				return sprintf( "%.0f", ( double )$l );
		}
		return $h.$l;
}

function sphUnpackI64( $v )
{
		list( $hi, $lo ) = array_values( unpack( "N*N*", $v ) );
		if ( 8 <= PHP_INT_SIZE )
		{
				if ( $hi < 0 )
				{
						$hi += 1;
				}
				if ( $lo < 0 )
				{
						$lo += 1;
				}
				return ( $hi << 32 ) + $lo;
		}
		if ( $hi == 0 )
		{
				if ( 0 < $lo )
				{
						return $lo;
				}
				return sprintf( "%u", $lo );
		}
		if ( $hi == -1 )
		{
				if ( $lo < 0 )
				{
						return $lo;
				}
				return sprintf( "%.0f", $lo - 4.29497e+009 );
		}
		$neg = "";
		$c = 0;
		if ( $hi < 0 )
		{
				$hi = ~$hi;
				$lo = ~$lo;
				$c = 1;
				$neg = "-";
		}
		$hi = sprintf( "%u", $hi );
		$lo = sprintf( "%u", $lo );
		if ( function_exists( "bcmul" ) )
		{
				return $neg.bcadd( bcadd( $lo, bcmul( $hi, "4294967296" ) ), $c );
		}
		$hi = ( double )$hi;
		$lo = ( double )$lo;
		$q = floor( $hi / 1e+007 );
		$r = $hi - $q * 1e+007;
		$m = $lo + $r * 4.9673e+006;
		$mq = floor( $m / 1e+007 );
		$l = $m - $mq * 1e+007 + $c;
		$h = $q * 4.29497e+009 + $r * 429 + $mq;
		if ( $l == 10000000 )
		{
				$l = 0;
				$h += 1;
		}
		$h = sprintf( "%.0f", $h );
		$l = sprintf( "%07.0f", $l );
		if ( $h == "0" )
		{
				return $neg.sprintf( "%.0f", ( double )$l );
		}
		return $neg.$h.$l;
}

function sphFixUint( $value )
{
		if ( 8 <= PHP_INT_SIZE )
		{
				if ( $value < 0 )
				{
						$value += 1;
				}
				return $value;
		}
		return sprintf( "%u", $value );
}

if ( !defined( "InShopNC" ) )
{
		exit( "Access Invalid!" );
}
define( "SEARCHD_COMMAND_SEARCH", 0 );
define( "SEARCHD_COMMAND_EXCERPT", 1 );
define( "SEARCHD_COMMAND_UPDATE", 2 );
define( "SEARCHD_COMMAND_KEYWORDS", 3 );
define( "SEARCHD_COMMAND_PERSIST", 4 );
define( "SEARCHD_COMMAND_STATUS", 5 );
define( "SEARCHD_COMMAND_QUERY", 6 );
define( "VER_COMMAND_SEARCH", 278 );
define( "VER_COMMAND_EXCERPT", 256 );
define( "VER_COMMAND_UPDATE", 258 );
define( "VER_COMMAND_KEYWORDS", 256 );
define( "VER_COMMAND_STATUS", 256 );
define( "VER_COMMAND_QUERY", 256 );
define( "SEARCHD_OK", 0 );
define( "SEARCHD_ERROR", 1 );
define( "SEARCHD_RETRY", 2 );
define( "SEARCHD_WARNING", 3 );
define( "SPH_MATCH_ALL", 0 );
define( "SPH_MATCH_ANY", 1 );
define( "SPH_MATCH_PHRASE", 2 );
define( "SPH_MATCH_BOOLEAN", 3 );
define( "SPH_MATCH_EXTENDED", 4 );
define( "SPH_MATCH_FULLSCAN", 5 );
define( "SPH_MATCH_EXTENDED2", 6 );
define( "SPH_RANK_PROXIMITY_BM25", 0 );
define( "SPH_RANK_BM25", 1 );
define( "SPH_RANK_NONE", 2 );
define( "SPH_RANK_WORDCOUNT", 3 );
define( "SPH_RANK_PROXIMITY", 4 );
define( "SPH_RANK_MATCHANY", 5 );
define( "SPH_RANK_FIELDMASK", 6 );
define( "SPH_SORT_RELEVANCE", 0 );
define( "SPH_SORT_ATTR_DESC", 1 );
define( "SPH_SORT_ATTR_ASC", 2 );
define( "SPH_SORT_TIME_SEGMENTS", 3 );
define( "SPH_SORT_EXTENDED", 4 );
define( "SPH_SORT_EXPR", 5 );
define( "SPH_FILTER_VALUES", 0 );
define( "SPH_FILTER_RANGE", 1 );
define( "SPH_FILTER_FLOATRANGE", 2 );
define( "SPH_ATTR_INTEGER", 1 );
define( "SPH_ATTR_TIMESTAMP", 2 );
define( "SPH_ATTR_ORDINAL", 3 );
define( "SPH_ATTR_BOOL", 4 );
define( "SPH_ATTR_FLOAT", 5 );
define( "SPH_ATTR_BIGINT", 6 );
define( "SPH_ATTR_MULTI", 1073741824 );
define( "SPH_GROUPBY_DAY", 0 );
define( "SPH_GROUPBY_WEEK", 1 );
define( "SPH_GROUPBY_MONTH", 2 );
define( "SPH_GROUPBY_YEAR", 3 );
define( "SPH_GROUPBY_ATTR", 4 );
define( "SPH_GROUPBY_ATTRPAIR", 5 );
?>
