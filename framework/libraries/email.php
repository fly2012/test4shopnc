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

final class Email
{

		private $email_server = NULL;
		private $email_port = 25;
		private $email_user = NULL;
		private $email_password = NULL;
		private $email_from = NULL;
		private $email_delimiter = "\n";
		private $site_name = NULL;

		public function get( $key )
		{
				if ( !empty( $this->$key ) )
				{
						return $this->$key;
				}
				return FALSE;
		}

		public function set( $key, $value )
		{
				if ( !isset( $this->$key ) )
				{
						$this->$key = $value;
						return TRUE;
				}
				return FALSE;
		}

		public function send( $email_to, $subject, $message, $from = "" )
		{
				$message = base64_encode( $this->html( $subject, $message ) );
				$email_to = $this->to( $email_to );
				$header = $this->header( $from );
				if ( !( $fp = fsockopen( $this->email_server, $this->email_port, $errno, $errstr, 30 ) ) )
				{
						$this->resultLog( $this->email_server.":".$this->email_port." CONNECT - Unable to connect to the SMTP server" );
						return FALSE;
				}
				stream_set_blocking( $fp, TRUE );
				$lastmessage = fgets( $fp, 512 );
				if ( substr( $lastmessage, 0, 3 ) != "220" )
				{
						$this->resultLog( $this->email_server.":".$this->email_port.$lastmessage );
						return FALSE;
				}
				fputs( $fp, "EHLO shopnc\r\n" );
				$lastmessage = fgets( $fp, 512 );
				if ( substr( $lastmessage, 0, 3 ) != 220 && substr( $lastmessage, 0, 3 ) != 250 )
				{
						$this->resultLog( $this->email_server.":".$this->email_port.( " HELO/EHLO - ".$lastmessage ) );
						return FALSE;
				}
				if ( substr( $lastmessage, 0, 3 ) == 220 )
				{
						$lastmessage = fgets( $fp, 512 );
						if ( substr( $lastmessage, 0, 3 ) != 250 )
						{
								$this->resultLog( $this->email_server.":".$this->email_port.( " HELO/EHLO - ".$lastmessage ) );
								return FALSE;
						}
				}
				do
				{
						if ( !( substr( $lastmessage, 3, 1 ) != "-" ) )
						{
								if ( empty( $lastmessage ) )
								{
										break;
										break;
								}
						}
						else
						{
								break;
						}
						$lastmessage = fgets( $fp, 512 );
				} while ( 1 );
				fputs( $fp, "AUTH LOGIN\r\n" );
				$lastmessage = fgets( $fp, 512 );
				if ( substr( $lastmessage, 0, 3 ) != 334 )
				{
						$this->resultLog( $this->email_server.":".$this->email_port.( " AUTH LOGIN - ".$lastmessage ) );
						return FALSE;
				}
				fputs( $fp, base64_encode( $this->email_user )."\r\n" );
				$lastmessage = fgets( $fp, 512 );
				if ( substr( $lastmessage, 0, 3 ) != 334 )
				{
						$this->resultLog( $this->email_server.":".$this->email_port.( " USERNAME - ".$lastmessage ) );
						return FALSE;
				}
				fputs( $fp, base64_encode( $this->email_password )."\r\n" );
				$lastmessage = fgets( $fp, 512 );
				if ( substr( $lastmessage, 0, 3 ) != 235 )
				{
						$this->resultLog( $this->email_server.":".$this->email_port.( " PASSWORD - ".$lastmessage ) );
						return FALSE;
				}
				fputs( $fp, "MAIL FROM: <".preg_replace( "/.*\\<(.+?)\\>.*/", "\\1", $this->email_from ).">\r\n" );
				$lastmessage = fgets( $fp, 512 );
				if ( substr( $lastmessage, 0, 3 ) != 250 )
				{
						fputs( $fp, "MAIL FROM: <".preg_replace( "/.*\\<(.+?)\\>.*/", "\\1", $this->email_from ).">\r\n" );
						$lastmessage = fgets( $fp, 512 );
						if ( substr( $lastmessage, 0, 3 ) != 250 )
						{
								$this->resultLog( $this->email_server.":".$this->email_port.( " MAIL FROM - ".$lastmessage ) );
								return FALSE;
						}
				}
				fputs( $fp, "RCPT TO: <".preg_replace( "/.*\\<(.+?)\\>.*/", "\\1", $email_to ).">\r\n" );
				$lastmessage = fgets( $fp, 512 );
				if ( substr( $lastmessage, 0, 3 ) != 250 )
				{
						fputs( $fp, "RCPT TO: <".preg_replace( "/.*\\<(.+?)\\>.*/", "\\1", $email_to ).">\r\n" );
						$lastmessage = fgets( $fp, 512 );
						$this->resultLog( $this->email_server.":".$this->email_port.( " RCPT TO - ".$lastmessage ) );
						return FALSE;
				}
				fputs( $fp, "DATA\r\n" );
				$lastmessage = fgets( $fp, 512 );
				if ( substr( $lastmessage, 0, 3 ) != 354 )
				{
						$this->resultLog( $this->email_server.":".$this->email_port.( " DATA - ".$lastmessage ) );
						return FALSE;
				}
				fputs( $fp, "Date: ".gmdate( "r" )."\r\n" );
				fputs( $fp, "To: ".$email_to."\r\n" );
				fputs( $fp, "Subject: ".$subject."\r\n" );
				fputs( $fp, $header."\r\n" );
				fputs( $fp, "\r\n\r\n" );
				fputs( $fp, "{$message}\r\n.\r\n" );
				$lastmessage = fgets( $fp, 512 );
				if ( substr( $lastmessage, 0, 3 ) != 250 )
				{
						$this->resultLog( $this->email_server.":".$this->email_port.( " END - ".$lastmessage ) );
				}
				fputs( $fp, "QUIT\r\n" );
				return TRUE;
		}

		public function send_sys_email( $email_to, $subject, $message )
		{
				$this->set( "email_server", $GLOBALS['setting_config']['email_host'] );
				$this->set( "email_port", $GLOBALS['setting_config']['email_port'] );
				$this->set( "email_user", $GLOBALS['setting_config']['email_id'] );
				$this->set( "email_password", $GLOBALS['setting_config']['email_pass'] );
				$this->set( "email_from", $GLOBALS['setting_config']['email_addr'] );
				$this->set( "site_name", $GLOBALS['setting_config']['site_name'] );
				$result = $this->send( $email_to, $subject, $message );
				return $result;
		}

		private function html( $subject, $message )
		{
				$message = preg_replace( "/href\\=\"(?!http\\:\\/\\/)(.+?)\"/i", "href=\"".SiteUrl."\\1\"", $message );
				$tmp .= "<html><head>";
				$tmp .= "<meta http-equiv=\"Content-Type\" content=\"text/html; charset=".CHARSET."\">";
				$tmp .= "<title>".$subject."</title>";
				$tmp .= "</head><body>";
				$tmp .= $subject."<br />".$message;
				$tmp .= "</body></html>";
				$message = $tmp;
				unset( $tmp );
				return $message;
		}

		private function to( $email_to )
		{
				$email_to = preg_match( "/^(.+?) \\<(.+?)\\>\$/", $email_to, $mats ) ? $this->email_user ? "=?".CHARSET."?B?".base64_encode( $mats[1] ).( "?= <".$mats['2'].">" ) : $mats[2] : $email_to;
				return $email_to;
		}

		private function subject( $subject )
		{
				$subject = "=?".CHARSET."?B?".base64_encode( preg_replace( "/[\r|\n]/", "", "[".$this->site_name."] ".$subject ) )."?=";
				return $subject;
		}

		private function message( $message )
		{
				$message = chunk_split( base64_encode( str_replace( "\n", "\r\n", str_replace( "\r", "\n", str_replace( "\r\n", "\n", str_replace( "\n\r", "\r", $message ) ) ) ) ) );
				return $message;
		}

		private function header( $from = "" )
		{
				if ( $from == "" )
				{
						$from = "=?".CHARSET."?B?".base64_encode( $this->site_name )."?= <".$this->email_from.">";
				}
				else
				{
						$from = preg_match( "/^(.+?) \\<(.+?)\\>\$/", $from, $mats ) ? "=?".CHARSET."?B?".base64_encode( $mats[1] ).( "?= <".$mats['2'].">" ) : $from;
				}
				$header = "From: ".$fro."{$this->email_delimiter}";
				$header .= "X-Priority: 3".$this->email_delimiter;
				$header .= "X-Mailer: ShopNC ".$this->email_delimiter;
				$header .= "MIME-Version: 1.0".$this->email_delimiter;
				$header .= "Content-type: text/html; ";
				$header .= "charset=".CHARSET."{$this->email_delimiter}";
				$header .= "Content-Transfer-Encoding: base64".$this->email_delimiter;
				$header .= "Message-ID: <".gmdate( "YmdHs" ).".".substr( md5( $message.microtime( ) ), 0, 6 ).rand( 100000, 999999 )."@".$_SERVER['HTTP_HOST'].( ">".$this->email_delimiter );
				return $header;
		}

		private function resultLog( $msg )
		{
				if ( DeBug === TRUE )
				{
						$fp = fopen( "txt.txt", "a+" );
						fwrite( $fp, $msg );
						fclose( $fp );
						return TRUE;
				}
				return TRUE;
		}

}

if ( !defined( "InShopNC" ) )
{
		exit( "Access Invalid!" );
}
?>
