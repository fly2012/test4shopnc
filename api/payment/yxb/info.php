<?php
$info_array = array(
	'code'=>'yxb',//基本信息
	'name'=>'英雄币交易平台',
	'content'=>'提供英雄币交易平台作为支付手段',
	'is_online'=>'1',
	'website'   => 'http://www.yxcoin.net/',
	'version'   => '1.0',
	'currency'  => '英雄币',
	'config'    => array(
                'yxb_account'   => array(//帐号
                    'text'  => '交易平台账户ID',
                    'desc'  => '填写交易平台账户ID，详见 账户信息->交易安全设置',
                    'type'  => 'text',
                ),
                'yxb_key'   => array(//帐号
                    'text'  => '交易平台账户支付密钥',
                    'desc'  => '填写交易平台账户密钥，详见 账户信息->交易安全设置',
                    'type'  => 'text',
                ),
    ),
);
