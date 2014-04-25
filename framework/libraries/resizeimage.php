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

class ResizeImage
{

		private $type = NULL;
		private $width = NULL;
		private $height = NULL;
		private $resize_width = NULL;
		private $resize_height = NULL;
		private $cut = 0;
		private $srcimg = NULL;
		private $dstimg = NULL;
		public $relative_dstimg = NULL;
		private $im = NULL;
		private $small_ext = "_small.";

		public function newImg( $img, $wid, $hei, $c, $small_ext = "_small.", $dst_img = "" )
		{
				$this->srcimg = $img;
				$this->resize_width = $wid;
				$this->resize_height = $hei;
				$this->cut = $c;
				$this->small_ext = $small_ext;
				$this->dstimg = $dst_img;
				$this->type = substr( strrchr( $this->srcimg, "." ), 1 );
				$this->initi_img( );
				$this->dst_img( );
				$this->width = imagesx( $this->im );
				$this->height = imagesy( $this->im );
				$resize_ratio = $this->resize_width / $this->resize_height;
				$ratio = $this->width / $this->height;
				if ( $this->cut == "1" )
				{
						if ( $resize_ratio <= $ratio )
						{
								$newimg = imagecreatetruecolor( $this->resize_width, $this->resize_height );
								imagecopyresampled( $newimg, $this->im, 0, 0, 0, 0, $this->resize_width, $this->resize_height, $this->height * $resize_ratio, $this->height );
								imagejpeg( $newimg, $this->dstimg, 90 );
						}
						if ( $ratio < $resize_ratio )
						{
								$newimg = imagecreatetruecolor( $this->resize_width, $this->resize_height );
								imagecopyresampled( $newimg, $this->im, 0, 0, 0, 0, $this->resize_width, $this->resize_height, $this->width, $this->width / $resize_ratio );
								imagejpeg( $newimg, $this->dstimg, 90 );
						}
				}
				else
				{
						if ( 0 < $ratio )
						{
								$this->resize_height = $this->resize_width * $this->height / $this->width;
						}
						else
						{
								$this->resize_width = $this->resize_height * $this->width / $this->height;
						}
						$newimg = imagecreatetruecolor( $this->resize_width, $this->resize_height );
						imagecopyresampled( $newimg, $this->im, 0, 0, 0, 0, $this->resize_width, $this->resize_height, $this->width, $this->height );
						imagejpeg( $newimg, $this->dstimg, 90 );
				}
				imagedestroy( $this->im );
				imagedestroy( $newimg );
		}

		private function initi_img( )
		{
				if ( $this->type == "jpg" )
				{
						$this->im = imagecreatefromjpeg( $this->srcimg );
				}
				if ( $this->type == "gif" )
				{
						$this->im = imagecreatefromgif( $this->srcimg );
				}
				if ( $this->type == "png" )
				{
						$this->im = imagecreatefrompng( $this->srcimg );
				}
				if ( $this->type == "jpeg" )
				{
						$this->im = imagecreatefromjpeg( $this->srcimg );
				}
		}

		private function dst_img( )
		{
				if ( $this->dstimg == "" )
				{
						$full_length = strlen( $this->srcimg );
						$type_length = strlen( $this->type );
						$name_length = $full_length - $type_length;
						$name = substr( $this->srcimg, 0, $name_length - 1 );
						$this->dstimg = $name.".".$this->type.$this->small_ext.$this->type;
				}
				else
				{
						$line = str_replace( "\\", "/", $this->srcimg );
						$img = explode( "/", $line );
						$file = explode( ".", $img[count( $img ) - 1] );
						$this->dstimg = $this->dstimg."/".$file[0].".".$file[1].$this->small_ext.$file[1];
				}
				$this->relative_dstimg = str_replace( BasePath, "", $this->dstimg );
		}

}

if ( !defined( "InShopNC" ) )
{
		exit( "Access Invalid!" );
}
?>
