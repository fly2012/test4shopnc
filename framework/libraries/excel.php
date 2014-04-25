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

class Excel
{

		private $header = "<?xml version=\"1.0\" encoding=\"UTF-8\"?\\>\r\n\t<Workbook xmlns=\"urn:schemas-microsoft-com:office:spreadsheet\"\r\n\txmlns:x=\"urn:schemas-microsoft-com:office:excel\"\r\n\txmlns:ss=\"urn:schemas-microsoft-com:office:spreadsheet\"\r\n\txmlns:html=\"http://www.w3.org/TR/REC-html40\">";
		private $footer = "</Workbook>";
		private $lines = array( );
		private $worksheets = array( );
		private $cellstyle = array( );
		private $default_cellformat = "String";

		public function __construct( )
		{
				$this->cellstyle['Default'] = "<Style ss:ID=\"Default\" ss:Name=\"Normal\">\r\n\t\t\t   <Alignment ss:Vertical=\"Center\"/>\r\n\t\t\t   <Borders/>\r\n\t\t\t   <Font ss:FontName=\"宋体\" x:CharSet=\"134\" ss:Size=\"11\" ss:Color=\"#000000\"/>\r\n\t\t\t   <Interior/>\r\n\t\t\t   <NumberFormat/>\r\n\t\t\t   <Protection/>\r\n\t\t\t  </Style>";
		}

		private function addRow( $array )
		{
				$cells = "";
				foreach ( $array as $k => $v )
				{
						$style_str = "";
						if ( !empty( $v['styleid'] ) )
						{
								$style_str = "ss:StyleID=\"".$v['styleid']."\"";
						}
						$format_str = $this->default_cellformat;
						if ( !empty( $v['format'] ) )
						{
								$format_str = $v['format'];
						}
						$cells .= "<Cell ".$style_str." ><Data ss:Type=\"{$format_str}\">{$v['data']}</Data></Cell>\n";
				}
				$this->lines[] = "<Row>\n".$cells."</Row>\n";
		}

		public function addArray( $array )
		{
				$this->lines = array( );
				foreach ( ( array )$array as $k => $v )
				{
						$this->addRow( $v );
				}
		}

		public function addWorksheet( $sheettitle )
		{
				$sheettitle = preg_replace( "/[\\\\|:|\\/|\\?|\\*|\\[|\\]]/", "", $sheettitle );
				$this->worksheets[] = "\n<Worksheet ss:Name=\"".$sheettitle."\">\n<Table ss:DefaultRowHeight=\"20\">\n<Column ss:Index=\"1\" ss:AutoFitWidth=\"0\"/>\n".implode( "\n", $this->lines )."</Table>\n</Worksheet>\n";
		}

		public function setStyle( $style_arr )
		{
				if ( empty( $style_arr ) )
				{
						return FALSE;
				}
				$id = $style_arr['id'];
				unset( $style_arr['id'] );
				$style_str = "<Style ss:ID=\"".$id."\">";
				foreach ( $style_arr as $k => $v )
				{
						$tmp = "";
						foreach ( ( array )$v as $k_item => $v_item )
						{
								$tmp .= " ss:".$k_item."=\"{$v_item}\"";
						}
						$style_str .= "<".$k." ".$tmp."/>";
				}
				$this->cellstyle[$id] = $style_str."</Style>";
		}

		public function setDefaultFormat( $format_str )
		{
				if ( empty( $style_arr ) )
				{
						return FALSE;
				}
				$this->default_cellformat = $format_str;
		}

		public function generateXML( $filename )
		{
				$encoded_filename = urlencode( $filename );
				$encoded_filename = str_replace( "+", "%20", $encoded_filename );
				$ua = $_SERVER['HTTP_USER_AGENT'];
				header( "Content-Type: application/vnd.ms-excel" );
				if ( preg_match( "/MSIE/", $ua ) )
				{
						header( "Content-Disposition: attachment; filename=\"".$encoded_filename.".xls\"" );
				}
				else if ( preg_match( "/Firefox/", $ua ) )
				{
						header( "Content-Disposition: attachment; filename*=\"utf8''".$filename.".xls\"" );
				}
				else
				{
						header( "Content-Disposition: attachment; filename=\"".$filename.".xls\"" );
				}
				header( "Cache-Control: max-age=0" );
				echo stripslashes( $this->header );
				echo "\n<Styles>";
				foreach ( ( array )$this->cellstyle as $k => $v )
				{
						echo "\n".$v;
				}
				echo "\n</Styles>";
				echo implode( "\n", $this->worksheets );
				echo $this->footer;
		}

		public function charset( $content, $from = "gbk", $to = "utf-8" )
		{
				$from = strtoupper( $from ) == "UTF8" ? "utf-8" : $from;
				$to = strtoupper( $to ) == "UTF8" ? "utf-8" : $to;
				if ( strtoupper( $from ) === strtoupper( $to ) || empty( $content ) )
				{
						return $content;
				}
				if ( function_exists( "mb_convert_encoding" ) )
				{
						if ( is_array( $content ) )
						{
								$content = var_export( $content, TRUE );
								$content = mb_convert_encoding( $content, $to, $from );
								eval( "\$content = ".$content.";" );
								return $content;
						}
						return mb_convert_encoding( $content, $to, $from );
				}
				if ( function_exists( "iconv" ) )
				{
						if ( is_array( $content ) )
						{
								$content = var_export( $content, TRUE );
								$content = iconv( $from, $to, $content );
								eval( "\$content = ".$content.";" );
								return $content;
						}
						return iconv( $from, $to, $content );
				}
				return $content;
		}

}

if ( !defined( "InShopNC" ) )
{
		exit( "Access Invalid!" );
}
?>
