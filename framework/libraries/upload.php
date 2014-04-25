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

class UploadFile
{

		private $save_path = NULL;
		private $allow_type = array
		(
				0 => "gif",
				1 => "jpg",
				2 => "jpeg",
				3 => "bmp",
				4 => "png",
				5 => "swf",
				6 => "tbi"
		);
		private $max_size = "1024";
		private $thumb_width = 0;
		private $thumb_height = 0;
		private $thumb_ext = FALSE;
		private $upload_file = NULL;
		private $ifremove = FALSE;
		public $file_name = NULL;
		private $ext = NULL;
		private $new_ext = NULL;
		private $default_dir = "upload";
		public $error = "";
		public $thumb_image = NULL;
		private $if_show_error = FALSE;
		private $if_show_error_one = FALSE;
		private $fprefix = NULL;

		public function __construct( )
		{
				Language::read( "core_lang_index" );
		}

		public function set( $key, $value )
		{
				$this->$key = $value;
		}

		public function get( $key )
		{
				return $this->$key;
		}

		public function upfile( $field )
		{
				$this->upload_file = $_FILES[$field];
				if ( $this->upload_file['tmp_name'] == "" )
				{
						$this->setError( Language::get( "cant_find_temporary_files" ) );
						return FALSE;
				}
				$error = $this->fileInputError( );
				if ( !$error )
				{
						return FALSE;
				}
				if ( !is_uploaded_file( $this->upload_file['tmp_name'] ) )
				{
						$this->setError( Language::get( "upload_file_attack" ) );
						return FALSE;
				}
				if ( $this->upload_file['size'] == 0 )
				{
						$error = Language::get( "upload_file_size_none" );
						$this->setError( $error );
						return FALSE;
				}
				if ( $this->max_size * 1024 < $this->upload_file['size'] )
				{
						$error = Language::get( "upload_file_size_cant_over" ).$this->max_size."KB";
						$this->setError( $error );
						return FALSE;
				}
				$tmp_ext = explode( ".", $this->upload_file['name'] );
				$tmp_ext = $tmp_ext[count( $tmp_ext ) - 1];
				$this->ext = strtolower( $tmp_ext );
				if ( !in_array( $this->ext, $this->allow_type ) )
				{
						$error = Language::get( "image_allow_ext_is" ).implode( ",", $this->allow_type );
						$this->setError( $error );
						return FALSE;
				}
				if ( !( $image_info = @getimagesize( $this->upload_file['tmp_name'] ) ) )
				{
						$error = Language::get( "upload_image_is_not_image" );
						$this->setError( $error );
						return FALSE;
				}
				$this->save_path = $this->setPath( );
				if ( empty( $this->file_name ) )
				{
						$this->setFileName( );
				}
				$ifresize = FALSE;
				if ( $this->thumb_width && $this->thumb_height && $this->thumb_ext )
				{
						$thumb_width = explode( ",", $this->thumb_width );
						$thumb_height = explode( ",", $this->thumb_height );
						$thumb_ext = explode( ",", $this->thumb_ext );
						if ( count( $thumb_width ) == count( $thumb_height ) && count( $thumb_height ) == count( $thumb_ext ) )
						{
								$ifresize = TRUE;
						}
				}
				if ( $ifresize )
				{
						$i = 0;
						for ( ;	$i < count( $thumb_width );	++$i	)
						{
								if ( $image_info[0] < $thumb_width[$i] )
								{
										$thumb_width[$i] = $image_info[0];
								}
								if ( $image_info[1] < $thumb_height[$i] )
								{
										$thumb_height[$i] = $image_info[1];
								}
								$thumb_wh = $thumb_width[$i] / $thumb_height[$i];
								$src_wh = $image_info[0] / $image_info[1];
								if ( $thumb_wh <= $src_wh )
								{
										$thumb_height[$i] = $thumb_width[$i] * ( $image_info[1] / $image_info[0] );
								}
								else
								{
										$thumb_width[$i] = $thumb_height[$i] * ( $image_info[0] / $image_info[1] );
								}
						}
				}
				if ( $this->if_show_error )
				{
						echo "<script type='text/javascript'>alert('".( $this->if_show_error_one ? $error : $this->error )."');history.back();</script>";
						exit( );
				}
				if ( @copy( $this->upload_file['tmp_name'], BasePath.DS.$this->save_path.DS.$this->file_name ) )
				{
						if ( $ifresize )
						{
								require_once( BasePath."/framework/libraries/resizeimage.php" );
								$resizeImage = new ResizeImage( );
								$save_path = BasePath.DS.$this->save_path;
								$i = 0;
								for ( ;	$i < count( $thumb_width );	++$i	)
								{
										$resizeImage->newImg( $save_path.DS.$this->file_name, $thumb_width[$i], $thumb_height[$i], $this->resize_cut, $thumb_ext[$i].".", $save_path );
										if ( $i == 0 )
										{
												$resize_image = explode( "/", $resizeImage->relative_dstimg );
												$this->thumb_image = $resize_image[count( $resize_image ) - 1];
										}
								}
						}
						if ( $this->ifremove && is_file( BasePath.DS.$this->save_path.DS.$this->file_name ) )
						{
								@unlink( BasePath.DS.$this->save_path.DS.$this->file_name );
						}
						return TRUE;
				}
				$this->setError( Language::get( "upload_file_fail" ) );
				return FALSE;
		}

		private function fileInputError( )
		{
				switch ( $this->upload_file['error'] )
				{
				case 0 :
						return TRUE;
				case 1 :
						$this->setError( Language::get( "upload_file_size_over" ) );
						return FALSE;
				case 2 :
						$this->setError( Language::get( "upload_file_size_over" ) );
						return FALSE;
				case 3 :
						$this->setError( Language::get( "upload_file_is_not_complete" ) );
						return FALSE;
				case 4 :
						$this->setError( Language::get( "upload_file_is_not_uploaded" ) );
						return FALSE;
				case 6 :
						$this->setError( Language::get( "upload_dir_chmod" ) );
						return FALSE;
				case 7 :
						$this->setError( Language::get( "upload_file_write_fail" ) );
						return FALSE;
				}
				return TRUE;
		}

		private function setPath( )
		{
				if ( !is_dir( BasePath.DS.$this->default_dir ) )
				{
						$dir = $this->default_dir;
						$dir = str_replace( "/", DS, $dir );
						$dir_array = explode( DS, $dir );
						$tmp_base_path = BasePath;
						foreach ( $dir_array as $k => $v )
						{
								$tmp_base_path = $tmp_base_path.DS.$v;
								if ( is_dir( $tmp_base_path ) || @mkdir( $tmp_base_path, 493 ) )
								{
										continue;
								}
								$this->setError( Language::get( "upload_file_mkdir" ).$tmp_base_path.Language::get( "upload_file_mkdir_fail" ) );
								return FALSE;
						}
						unset( $dir );
						unset( $dir_array );
						unset( $tmp_base_path );
				}
				@chmod( BasePath.DS.$this->default_dir, 493 );
				$test_file = BasePath.DS.$this->default_dir.DS."index.htm";
				$fp = @fopen( $test_file, "wb+" );
				if ( $fp === FALSE )
				{
						$this->setError( Language::get( "upload_file_dir" ).$this->default_dir.Language::get( "upload_file_dir_cant_touch_file" ) );
						return FALSE;
				}
				@fclose( $fp );
				@unlink( $test_file );
				return $this->default_dir;
		}

		private function setFileName( )
		{
				$this->file_name = ( empty( $this->fprefix ) ? "" : $this->fprefix."_" ).md5( uniqid( rand( ), TRUE ) ).".".( $this->new_ext == "" ? $this->ext : $this->new_ext );
		}

		private function setError( $error )
		{
				$this->error = $error;
		}

		public function getSysSetPath( )
		{
				switch ( c( "image_dir_type" ) )
				{
				case "1" :
						$subpath = "";
						break;
				case "2" :
						$subpath = date( "Y", time( ) )."/";
						break;
				case "3" :
						$subpath = date( "Y", time( ) )."/".date( "m", time( ) )."/";
						break;
				case "4" :
						$subpath = date( "Y", time( ) )."/".date( "m", time( ) )."/".date( "d", time( ) )."/";
				}
				return $subpath;
		}

}

if ( !defined( "InShopNC" ) )
{
		exit( "Access Invalid!" );
}
?>
