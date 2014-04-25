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

class GdImage
{

		private $src_image_name = "";
		private $jpeg_quality = 90;
		private $save_file = "";
		private $wm_image_name = "";
		private $wm_image_pos = 1;
		private $wm_image_transition = 20;
		private $wm_text = "";
		private $wm_text_size = 20;
		private $wm_text_angle = 4;
		private $wm_text_pos = 3;
		private $wm_text_font = "";
		private $wm_text_color = "#cccccc";

		public function get( $key )
		{
				$result = $this->$key ? $this->$key : "";
				return $result;
		}

		public function set( $key, $value )
		{
				$this->$key = $value;
				return TRUE;
		}

		public function setWatermark( $watermark )
		{
				$this->set( "jpeg_quality", $watermark['jpeg_quality'] );
				if ( $watermark['wm_image_name'] != "" )
				{
						$this->set( "wm_image_name", BasePath.DS.ATTACH_WATERMARK.DS.$watermark['wm_image_name'] );
				}
				$this->set( "wm_image_pos", $watermark['wm_image_pos'] );
				$this->set( "wm_image_transition", $watermark['wm_image_transition'] );
				$this->set( "wm_text", $watermark['wm_text'] );
				$this->set( "wm_text_size", $watermark['wm_text_size'] );
				$this->set( "wm_text_angle", $watermark['wm_text_angle'] );
				$this->set( "wm_text_pos", $watermark['wm_text_pos'] );
				$this->set( "wm_text_font", BasePath.DS."resource".DS."font".DS.$watermark['wm_text_font'].".ttf" );
				$this->set( "jpeg_quality", $watermark['jpeg_quality'] );
				$this->set( "wm_text_color", $watermark['wm_text_color'] );
				return TRUE;
		}

		public function create( $filename = "" )
		{
				@ini_set( "memory_limit", "-1" );
				if ( $filename )
				{
						$this->src_image_name = strtolower( trim( $filename ) );
						$this->save_file = $this->src_image_name;
				}
				$src_image_type = $this->get_type( $this->src_image_name );
				$src_image = $this->createImage( $src_image_type, $this->src_image_name );
				if ( !$src_image )
				{
						return FALSE;
				}
				$src_image_w = imagesx( $src_image );
				$src_image_h = imagesy( $src_image );
				if ( $this->wm_image_name )
				{
						$this->wm_image_name = strtolower( trim( $this->wm_image_name ) );
						$wm_image_type = $this->get_type( $this->wm_image_name );
						$wm_image = $this->createImage( $wm_image_type, $this->wm_image_name );
						$wm_image_w = imagesx( $wm_image );
						$wm_image_h = imagesy( $wm_image );
						$temp_wm_image = $this->getPos( $src_image_w, $src_image_h, $this->wm_image_pos, $wm_image );
						$wm_image_x = $temp_wm_image['dest_x'];
						$wm_image_y = $temp_wm_image['dest_y'];
						imagecopymerge( $src_image, $wm_image, $wm_image_x, $wm_image_y, 0, 0, $wm_image_w, $wm_image_h, $this->wm_image_transition );
				}
				if ( $this->wm_text )
				{
						$temp_wm_text = $this->getPos( $src_image_w, $src_image_h, $this->wm_text_pos );
						$wm_text_x = $temp_wm_text['dest_x'];
						$wm_text_y = $temp_wm_text['dest_y'];
						if ( preg_match( "/([a-f0-9][a-f0-9])([a-f0-9][a-f0-9])([a-f0-9][a-f0-9])/i", $this->wm_text_color, $color ) )
						{
								$red = hexdec( $color[1] );
								$green = hexdec( $color[2] );
								$blue = hexdec( $color[3] );
								$wm_text_color = imagecolorallocate( $src_image, $red, $green, $blue );
						}
						else
						{
								$wm_text_color = imagecolorallocate( $src_image, 255, 255, 255 );
						}
						imagettftext( $src_image, $this->wm_text_size, $this->wm_angle, $wm_text_x, $wm_text_y, $wm_text_color, $this->wm_text_font, $this->wm_text );
				}
				if ( $this->save_file )
				{
						switch ( $this->get_type( $this->save_file ) )
						{
						case "gif" :
								$src_img = imagegif( $src_image, $this->save_file );
								break;
						case "jpeg" :
								$src_img = imagejpeg( $src_image, $this->save_file, $this->jpeg_quality );
								break;
						case "jpg" :
								$src_img = imagejpeg( $src_image, $this->save_file, $this->jpeg_quality );
								break;
						case "png" :
								$src_img = imagepng( $src_image, $this->save_file );
								break;
						default :
								$src_img = imagejpeg( $src_image, $this->save_file, $this->jpeg_quality );
						}
				}
				else
				{
						if ( $src_image_type == "jpg" )
						{
								$src_image_type = "jpeg";
						}
						@header( "Content-type: image/".$src_image_type );
						switch ( $src_image_type )
						{
						case "gif" :
								$src_img = imagegif( $src_image );
								break;
						case "jpg" :
								$src_img = imagejpeg( $src_image, "", $this->jpeg_quality );
								break;
						case "jpeg" :
								$src_img = imagejpeg( $src_image, "", $this->jpeg_quality );
								break;
						case "png" :
								$src_img = imagepng( $src_image );
								break;
								$src_img = imagejpeg( $src_image, "", $this->jpeg_quality );
						}
				}
				imagedestroy( $src_image );
				return TRUE;
		}

		private function createImage( $type, $img_name )
		{
				if ( !$type )
				{
						$type = $this->get_type( $img_name );
				}
				switch ( $type )
				{
				case "gif" :
						if ( !function_exists( "imagecreatefromgif" ) )
						{
								break;
						}
						$tmp_img = @imagecreatefromgif( $img_name );
						return $tmp_img;
				case "jpg" :
						$tmp_img = imagecreatefromjpeg( $img_name );
						return $tmp_img;
				case "jpeg" :
						$tmp_img = imagecreatefromjpeg( $img_name );
						return $tmp_img;
				case "png" :
						$tmp_img = imagecreatefrompng( $img_name );
						return $tmp_img;
						$tmp_img = imagecreatefromstring( $img_name );
				}
				return $tmp_img;
		}

		private function getPos( $sourcefile_width, $sourcefile_height, $pos, $wm_image = "" )
		{
				if ( $wm_image )
				{
						$insertfile_width = imagesx( $wm_image );
						$insertfile_height = imagesy( $wm_image );
				}
				else
				{
						$lineCount = explode( "\r\n", $this->wm_text );
						$fontSize = imagettfbbox( $this->wm_text_size, $this->wm_text_angle, $this->wm_text_font, $this->wm_text );
						$insertfile_width = $fontSize[2] - $fontSize[0];
						$insertfile_height = count( $lineCount ) * ( $fontSize[1] - $fontSize[3] );
				}
				switch ( $pos )
				{
				case 0 :
						$dest_x = $sourcefile_width / 2 - $insertfile_width / 2;
						$dest_y = $sourcefile_height / 2 - $insertfile_height / 2;
						break;
				case 1 :
						$dest_x = 0;
						if ( $this->wm_text )
						{
								$dest_y = $insertfile_height;
						}
						else
						{
								$dest_y = 0;
						}
						break;
				case 2 :
						$dest_x = $sourcefile_width - $insertfile_width;
						if ( $this->wm_text )
						{
								$dest_y = $insertfile_height;
						}
						else
						{
								$dest_y = 0;
						}
						break;
				case 3 :
						$dest_x = $sourcefile_width - $insertfile_width;
						if ( $this->wm_text )
						{
								$dest_y = $insertfile_height;
						}
						else
						{
								$dest_y = 0;
						}
						break;
				case 4 :
						$dest_x = 0;
						$dest_y = $sourcefile_height / 2 - $insertfile_height / 2;
						break;
				case 5 :
						$dest_x = ( $sourcefile_width - $insertfile_width ) / 2;
						$dest_y = $sourcefile_height / 2 - $insertfile_height / 2;
						break;
				case 6 :
						$dest_x = $sourcefile_width - $insertfile_width;
						$dest_y = $sourcefile_height / 2 - $insertfile_height / 2;
						break;
				case 7 :
						$dest_x = 0;
						$dest_y = $sourcefile_height - $insertfile_height;
						break;
				case 8 :
						$dest_x = ( $sourcefile_width - $insertfile_width ) / 2;
						$dest_y = $sourcefile_height - $insertfile_height;
						break;
						$dest_x = $sourcefile_width - $insertfile_width;
						$dest_y = $sourcefile_height - $insertfile_height;
				}
				return array(
						"dest_x" => $dest_x,
						"dest_y" => $dest_y
				);
		}

		private function gb2utf8( $gb )
		{
				if ( !trim( $gb ) )
				{
						return $gb;
				}
				$filename = "./gb2312.txt";
				$tmp = file( $filename );
				$codetable = array( );
				while ( list( $key, $value ) = each( &$tmp ) )
				{
						$codetable[hexdec( substr( $value, 0, 6 ) )] = substr( $value, 7, 6 );
				}
				$utf8 = "";
				while ( $gb )
				{
						if ( 127 < ord( substr( $gb, 0, 1 ) ) )
						{
								$tthis = substr( $gb, 0, 2 );
								$gb = substr( $gb, 2, strlen( $gb ) - 2 );
								$utf8 .= $this->u2utf8( hexdec( $codetable[hexdec( bin2hex( $tthis ) ) - 32896] ) );
						}
						else
						{
								$tthis = substr( $gb, 0, 1 );
								$gb = substr( $gb, 1, strlen( $gb ) - 1 );
								$utf8 .= $this->u2utf8( $tthis );
						}
				}
				return $utf8;
		}

		public function u2utf8( $c )
		{
				$str = "";
				if ( $c < 128 )
				{
						$str .= $c;
						return $str;
				}
				if ( $c < 2048 )
				{
						$str .= chr( 192 | $c >> 6 );
						$str .= chr( 128 | $c & 63 );
						return $str;
				}
				if ( $c < 65536 )
				{
						$str .= chr( 224 | $c >> 12 );
						$str .= chr( 128 | $c >> 6 & 63 );
						$str .= chr( 128 | $c & 63 );
						return $str;
				}
				if ( $c < 2097152 )
				{
						$str .= chr( 240 | $c >> 18 );
						$str .= chr( 128 | $c >> 12 & 63 );
						$str .= chr( 128 | $c >> 6 & 63 );
						$str .= chr( 128 | $c & 63 );
				}
				return $str;
		}

		private function get_type( $img_name )
		{
				$name_array = explode( ".", $img_name );
				if ( preg_match( "/\\.(jpg|jpeg|gif|png)\$/", $img_name, $matches ) )
				{
						$type = strtolower( $matches[1] );
						return $type;
				}
				$type = "string";
				return $type;
		}

}

if ( !defined( "InShopNC" ) )
{
		exit( "Access Invalid!" );
}
?>
