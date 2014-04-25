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

class Page
{

		private $page_name = "curpage";
		private $total_num = 1;
		private $page_url = "";
		private $each_num = 10;
		private $now_page = 1;
		private $total_page = 1;
		private $style = 2;
		private $ajax = 0;
		private $pre_home = "";
		private $pre_last = "";
		private $pre_page = "";
		private $next_page = "";
		private $left_html = "<li>";
		private $right_html = "</li>";
		private $left_current_html = "<li>";
		private $right_current_html = "</li>";
		private $left_ellipsis_html = "<li>";
		private $right_ellipsis_html = "</li>";
		private $left_inside_a_html = "";
		private $right_inside_a_html = "";

		public function __construct( )
		{
				Language::read( "core_lang_index" );
				$lang = Language::getlangcontent( );
				$this->pre_home = $lang['first_page'];
				$this->pre_last = $lang['last_page'];
				$this->pre_page = $lang['pre_page'];
				$this->next_page = $lang['next_page'];
				$this->setNowPage( $_GET[$this->page_name] );
				$this->setPageUrl( );
		}

		public function get( $key )
		{
				return $this->$key;
		}

		public function set( $key, $value )
		{
				return $this->$key = $value;
		}

		public function setPageName( $page_name )
		{
				$this->page_name = $page_name;
				return TRUE;
		}

		public function setNowPage( $page )
		{
				$this->now_page = 0 < intval( $page ) ? intval( $page ) : 1;
				return TRUE;
		}

		public function setEachNum( $num )
		{
				$this->each_num = 0 < intval( $num ) ? intval( $num ) : 10;
				return TRUE;
		}

		public function setStyle( $style )
		{
				$this->style = $style == "admin" ? 2 : $style;
				return TRUE;
		}

		public function setTotalNum( $total_num )
		{
				$this->total_num = $total_num;
				return TRUE;
		}

		public function getNowPage( )
		{
				return $this->now_page;
		}

		public function getTotalPage( )
		{
				return $this->total_page;
		}

		public function getTotalNum( )
		{
				return $this->total_num;
		}

		public function getEachNum( )
		{
				return $this->each_num;
		}

		public function getLimitStart( )
		{
				if ( $this->getNowPage( ) == 1 )
				{
						$tmp = 0;
						return $tmp;
				}
				$tmp = ( $this->getNowPage( ) - 1 ) * $this->getEachNum( );
				return $tmp;
		}

		public function getLimitEnd( )
		{
				$tmp = $this->getNowPage( ) * $this->getEachNum( );
				if ( $this->getTotalNum( ) < $tmp )
				{
						$tmp = $this->getTotalNum( );
				}
				return $tmp;
		}

		public function setTotalPage( )
		{
				$this->total_page = ceil( $this->getTotalNum( ) / $this->getEachNum( ) );
		}

		public function show( $style = NULL )
		{
				$this->setTotalPage( );
				if ( !is_null( $style ) )
				{
						$this->style = $style;
				}
				$html_page = "";
				switch ( $this->style )
				{
				case "1" :
						$this->left_current_html = "<li><span class=\"currentpage\">";
						$this->right_current_html = "</span></li>";
						$this->left_inside_a_html = "<span>";
						$this->right_inside_a_html = "</span>";
						$html_page .= "<ul>";
						if ( $this->getNowPage( ) == 1 )
						{
								$html_page .= "<li>".$this->left_inside_a_html.$this->pre_page.$this->right_inside_a_html."</li>";
						}
						else
						{
								$html_page .= "<li><a class=\"demo\" href=\"".$this->page_url.( $this->getNowPage( ) - 1 )."\">".$this->left_inside_a_html.$this->pre_page.$this->right_inside_a_html."</a></li>";
						}
						if ( $this->getNowPage( ) == $this->getTotalPage( ) || $this->getTotalPage( ) == 0 )
						{
								$html_page .= "<li>".$this->left_inside_a_html.$this->next_page.$this->right_inside_a_html."</li>";
						}
						else
						{
								$html_page .= "<li><a class=\"demo\" href=\"".$this->page_url.( $this->getNowPage( ) + 1 )."\">".$this->left_inside_a_html.$this->next_page.$this->right_inside_a_html."</a></li>";
						}
						$html_page .= "</ul>";
						break;
				case "2" :
						$this->left_current_html = "<li><span class=\"currentpage\">";
						$this->right_current_html = "</span></li>";
						$this->left_inside_a_html = "<span>";
						$this->right_inside_a_html = "</span>";
						$html_page .= "<ul>";
						if ( $this->getNowPage( ) == 1 )
						{
								$html_page .= "<li>".$this->left_inside_a_html.$this->pre_home.$this->right_inside_a_html."</li>";
								$html_page .= "<li>".$this->left_inside_a_html.$this->pre_page.$this->right_inside_a_html."</li>";
						}
						else
						{
								$html_page .= "<li><a class=\"demo\" href=\"".$this->page_url."1\">".$this->left_inside_a_html.$this->pre_home.$this->right_inside_a_html."</a></li>";
								$html_page .= "<li><a class=\"demo\" href=\"".$this->page_url.( $this->getNowPage( ) - 1 )."\">".$this->left_inside_a_html.$this->pre_page.$this->right_inside_a_html."</a></li>";
						}
						$html_page .= $this->getNowBar( );
						if ( $this->getNowPage( ) == $this->getTotalPage( ) || $this->getTotalPage( ) == 0 )
						{
								$html_page .= "<li>".$this->left_inside_a_html.$this->next_page.$this->right_inside_a_html."</li>";
								$html_page .= "<li>".$this->left_inside_a_html.$this->pre_last.$this->right_inside_a_html."</li>";
						}
						else
						{
								$html_page .= "<li><a class=\"demo\" href=\"".$this->page_url.( $this->getNowPage( ) + 1 )."\">".$this->left_inside_a_html.$this->next_page.$this->right_inside_a_html."</a></li>";
								$html_page .= "<li><a class=\"demo\" href=\"".$this->page_url.$this->getTotalPage( )."\">".$this->left_inside_a_html.$this->pre_last.$this->right_inside_a_html."</a></li>";
						}
						$html_page .= "</ul>";
						break;
				case "3" :
						$this->left_current_html = "<li><span class=\"currentpage\">";
						$this->right_current_html = "</span></li>";
						$this->left_inside_a_html = "<span>";
						$this->right_inside_a_html = "</span>";
						$html_page .= "<ul>";
						if ( $this->getNowPage( ) == 1 )
						{
								$html_page .= "<li>".$this->left_inside_a_html.$this->pre_page.$this->right_inside_a_html."</li>";
						}
						else
						{
								$html_page .= "<li><a class=\"demo\" href=\"".$this->page_url.( $this->getNowPage( ) - 1 )."\">".$this->left_inside_a_html.$this->pre_page.$this->right_inside_a_html."</a></li>";
						}
						$html_page .= $this->getNowBar( );
						if ( $this->getNowPage( ) == $this->getTotalPage( ) || $this->getTotalPage( ) == 0 )
						{
								$html_page .= "<li>".$this->left_inside_a_html.$this->next_page.$this->right_inside_a_html."</li>";
								$html_page .= "<li>".$this->left_inside_a_html.$this->pre_last.$this->right_inside_a_html."</li>";
						}
						else
						{
								$html_page .= "<li><a class=\"demo\" href=\"".$this->page_url.( $this->getNowPage( ) + 1 )."\">".$this->left_inside_a_html.$this->next_page.$this->right_inside_a_html."</a></li>";
						}
						$html_page .= "</ul>";
				}
				return $html_page;
		}

		private function getNowBar( )
		{
				if ( 7 <= $this->getNowPage( ) )
				{
						$begin = $this->getNowPage( ) - 2;
				}
				else
				{
						$begin = 1;
				}
				if ( $this->getNowPage( ) + 5 < $this->getTotalPage( ) )
				{
						$end = $this->getNowPage( ) + 5;
				}
				else
				{
						$end = $this->getTotalPage( );
				}
				$result = "";
				if ( 1 < $begin )
				{
						$result .= $this->setPageHtml( 1, 1 ).$this->setPageHtml( 2, 2 );
						$result .= $this->left_ellipsis_html."<span>...</span>".$this->right_ellipsis_html;
				}
				$i = $begin;
				for ( ;	$i <= $end;	++$i	)
				{
						$result .= $this->setPageHtml( $i, $i );
				}
				if ( $end < $this->getTotalPage( ) )
				{
						$result .= $this->left_ellipsis_html."<span>...</span>".$this->right_ellipsis_html;
				}
				return $result;
		}

		private function setPageHtml( $page_name, $page )
		{
				if ( $this->getNowPage( ) == $page )
				{
						$result = $this->left_current_html.$page.$this->right_current_html;
						return $result;
				}
				$result = $this->left_html."<a class='demo' href='".$this->page_url.$page."'>".$this->left_inside_a_html.$page_name.$this->right_inside_a_html."</a>".$this->right_html;
				return $result;
		}

		private function setPageUrl( )
		{
				$uri = request_uri( );
				$GLOBALS['_SERVER']['REQUEST_URI'] = $uri;
				if ( empty( $_SERVER['QUERY_STRING'] ) )
				{
						$this->page_url = $_SERVER['REQUEST_URI']."?".$this->page_name."=";
						return TRUE;
				}
				if ( stristr( $_SERVER['QUERY_STRING'], $this->page_name."=" ) )
				{
						$this->page_url = str_replace( $this->page_name."=".$this->now_page, "", $_SERVER['REQUEST_URI'] );
						$last = $this->page_url[strlen( $this->page_url ) - 1];
						if ( $last == "?" || $last == "&" )
						{
								$this->page_url .= $this->page_name."=";
								return TRUE;
						}
						$this->page_url .= "&".$this->page_name."=";
						return TRUE;
				}
				$this->page_url = $_SERVER['REQUEST_URI']."&".$this->page_name."=";
				return TRUE;
		}

}

if ( !defined( "InShopNC" ) )
{
		exit( "Access Invalid!" );
}
?>
