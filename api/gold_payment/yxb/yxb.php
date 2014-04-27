<?php
/**
 * 网银在线接口类
 *
 * 
 * @copyright  Copyright (c) 2007-2013 ShopNC Inc. (http://www.shopnc.net)
 * @license    http://www.shopnc.net
 * @link       http://www.shopnc.net
 * @since      File available since Release v1.1
 */
defined('InShopNC') or exit('Access Invalid!');

class yxb{
	/**
	 * 英雄币交易平台
	 *
	 * @var string
	 */
    private $gateway   = 'http://www.yxcoin.net/index.php/home/user/shoppay-';
	/**
	 * 支付接口标识
	 *
	 * @var string
	 */
    private $code      = 'yxb';
    /**
	 * 支付接口配置信息
	 *
	 * @var array
	 */
    private $payment;
     /**
	 * 订单信息
	 *
	 * @var array
	 */
    private $order;
    /**
     * 发送至交易平台的参数
     *
     * @var array
     */
    private $parameter;

    public function __construct($payment_info, $order_info){
            $this->yxb($payment_info,$order_info);
    }
    public function yxb($payment_info = array(),$order_info = array()){
            if(!empty($payment_info) and !empty($order_info)){
                    $this->payment	= $payment_info;
                    $this->order	= $order_info;
            }
    }
    
    /**
     * 支付跳转链接
     *
     */
    public function get_payurl(){
        $url = $this->gateway;
        //~ echo '<pre>';
        //~ print_r($this->payment);
        //~ echo '</pre>';
        //~ exit;
        $oid = $this->order['order_sn'];
        $oamount = $this->order['order_amount'];
        $sid = $this->payment['payment_config']['yxb_account'];
        $skey =$this->payment['payment_config']['yxb_key'];
        $check = md5($oid.$oamount.$sid.$skey);

        $v_url = SiteUrl."/api/payment/yxb/return_url.php";

        $url.= "oid-".$oid."-sid-".$sid."-oamount-".$oamount."-check-".$check."-rurl-".base64_encode($v_url);
      return $url;
    }
    
	/**
	 * 返回地址验证
	 *
	 * @param 
	 * @return array
	 */
	public function return_verify(){
		$param	= $_GET;
		$param['act']	= "";//将系统的控制参数置空，防止因为加密验证出错
		$param['op']	= "";
    $skey =$this->payment['payment_config']['yxb_key'];
    $oid = $param['oid'];
    $sid = $param['sid'];
    $check = $param['check'];

    if($check == md5($oid.$sid.$skey)) {
      return true;
    }
		return false;
	}
    
    /**
     * 获取订单更新参数数组
     *
     * @param array $param
     * @return array
     */
    public function getUpdateParam($param){
            
            $return_array = array(
                    'payment_time'=>time(),
                    'order_state'=>20
            );
            
            return $return_array;
    }
}
