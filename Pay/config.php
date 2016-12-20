<?php


return array(

        //是否是测试，钱是0.01,ios验证调试地址
        'IS_TEST' => false,

     //支付的配置
        'PAY_CONFIG'        => array(
                'alipay'=>array(
                    //合作身份者ID
                        'partner'            => '',
                        //收款支付宝账号，以2088开头由16位纯数字组成的字符串，一般情况下收款账号就是签约账号
                        'seller_id'     	 => '',
                        //商户的私钥,
                        'private_key_path'	 => PAY_THIRD_PATH.'alipayPay/key/rsa_private_key.pem',
                        //支付宝的公钥，
                        'ali_public_key_path'=> PAY_THIRD_PATH.'alipayPay/key/alipay_public_key.pem',
                        // 服务器异步通知页面路径
                        'notify_url'         => 'http://'.$_SERVER['HTTP_HOST']."/Api/Pay/alipayCallback/",
                        // 页面跳转同步通知页面路径
                        'return_url'         => 'http://'.$_SERVER['HTTP_HOST']."/Api/Pay/alipayReturn/",
                        //签名方式
                        'sign_type'          => strtoupper('RSA'),
                        //字符编码格式 目前支持 gbk 或 utf-8
                        'input_charset'      => strtolower('utf-8'),
                        // 支付类型 ，无需修改
                        'payment_type'       => "1",
                        // 产品类型，无需修改
                        'service'            => "create_direct_pay_by_user",
                        //ca证书路径地址，用于curl中ssl校验
                        'cacert'             => PAY_THIRD_PATH.'alipayPay/cacert.pem',
                            // 防钓鱼时间戳  若要使用请调用类文件submit中的query_timestamp函数
                        'anti_phishing_key'  => "",
                        // 客户端的IP地址 非局域网的外网IP地址，如：221.0.0.1
                        'exter_invoke_ip'    => "",
                ),
                //微信支付
                'weixinPay'=>array(
                        'APPID'         => '',
	                    'MCHID'         => '',
	                    'KEY'           => '',
	                    'APPSECRET'     => '',
                        'notify_url'    => 'http://'.$_SERVER['HTTP_HOST']."/Api/Pay/wxPayCallback/"
                ),
                //微信手机支付
                'weixinAppPay'=>array(
                        'APPID'         => '',
                        'MCHID'         => '',
                        'KEY'           => '',
                        'APPSECRET'     => '',
                        'notify_url'    => 'http://'.$_SERVER['HTTP_HOST']."/Pay/wxPayCallbackApp/"
                ),
                //易宝支付
                'yibaoPay'=>array(
                        'p1_MerId'	    => "",																										#测试使用
                        'merchantKey'	=> "",
                        'logName'	    => "YeePay_HTML.log",
                        'notifyUrl'     => 'http://'.$_SERVER['HTTP_HOST'].'/Api/Pay/ybPayCallback'
                ),
                //手机一键支付
                'yibaoAppPay'=>array(
                    //易宝商户ID
                    'key_id'        => '',
                    //易宝商户私钥
                    'prv_key'=>'',
                    //易宝商户公钥
                    'pub_key'=>'',
                    //易宝公钥
                    'yee_pub_key'=>'',
                    //易宝用户直冲回调地址
                    'url_callback'  => 'http://'.$_SERVER['SERVER_NAME'].'/Api/Pay/ybPayAppCallback/',
                    //易宝代理充值回调地址
//                    'url_callback_daili'  => 'http://'.$_SERVER['SERVER_NAME'].'/Recharge/Index/agent_recharge_callback/d/',
                    //回调地址
//                    'NOTIFY_URL'            => 'http://'.$_SERVER['SERVER_NAME'].'/Interfaces/Pay/index/pay_src/2/'

                )
        )
)
    ;
