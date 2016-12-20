<?php


return array(

        //是否是测试，钱是0.01,ios验证调试地址
        'IS_TEST' => false,

     //支付的配置
        'PAY_CONFIG'        => array(
                'alipay'=>array(
                    //合作身份者ID
                        'partner'            => '2088221163169650',
                        //收款支付宝账号，以2088开头由16位纯数字组成的字符串，一般情况下收款账号就是签约账号
                        'seller_id'     	 => '2088221163169650',
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
                        'APPID'         => 'wx08122becfa876171',
	                    'MCHID'         => '1324110901',
	                    'KEY'           => 'wLQPnSH5T7qxZ06b6WzPuhKp3zgvd5G0',
	                    'APPSECRET'     => 'bd212c819b360576e22cc102fa235ebd',
                        'notify_url'    => 'http://'.$_SERVER['HTTP_HOST']."/Api/Pay/wxPayCallback/"
                ),
                //微信手机支付
                'weixinAppPay'=>array(
                        'APPID'         => 'wx8ca68606e508068d',
                        'MCHID'         => '1316229901',
                        'KEY'           => '42C6qpIBjDHOwSzbsEX1CEUcr4kDh4I1',
                        'APPSECRET'     => '19d7c5dfadde67ecd6e7a439b4cdcd32',
                        'notify_url'    => 'http://'.$_SERVER['HTTP_HOST']."/Pay/wxPayCallbackApp/"
                ),
                //易宝支付
                'yibaoPay'=>array(
                        'p1_MerId'	    => "10012819450",																										#测试使用
                        'merchantKey'	=> "25KqRSS29R3SZftz906k6j0596296Z468i64148bQem826974o32GXF02531",
                        'logName'	    => "YeePay_HTML.log",
                        'notifyUrl'     => 'http://'.$_SERVER['HTTP_HOST'].'/Api/Pay/ybPayCallback'
                ),
                //手机一键支付
                'yibaoAppPay'=>array(
                    //易宝商户ID
                    'key_id'        => '10012819450',
                    //易宝商户私钥
                    'prv_key'=>'MIICdgIBADANBgkqhkiG9w0BAQEFAASCAmAwggJcAgEAAoGBAIx+3ocn60wnKPpFymXd6nLly2V5RffwkmtI4KZ7n8KEOXGjtEBzYqAJtVxLJxag1pZJ50+9M40l3ElDl65qSW+Tq19AkU1VrhvQhMLzCDw/XVkV3ECXUB9HQ9jbYL9lgDKJDy+fx3F8LvN+n2CJNwzEfYdWnjQELDmuhrUZtjEXAgMBAAECgYAFw7rbrs6fvEeZJT3tuhJBjp4u1rABQVSwpDfbfN0MPSKiQAZIUrOsP1m6pLbA3e6QEg40nl28H96O/v/9Pw0uP/1FX2ZMmDG3K+bWmoKvRYeNDGQBDipb+2G1k5MtkVtw01Fy/VUhUf1e6dN1n0Akl/X8DSOavu8M+eY500GaEQJBAOOZemRU+gBGKJ9z61Y0cT79eBHsRpC9/6KX/Rd8A0NHOiPLi8yYWPVQiqMZ3tKvt3NQ0zpa3KxUFMSZh2afH08CQQCeBulFXuJ4sl0X7m0ML9/s+FaxAsjKLD4h/MjjVXD3wubOV4IMfA14zuaZ/JAFYPs+1zXcIRRE6xkdB/Tpsh+5AkBG9QKDZTrL+xOPIsSsC42C5eMZM2CMn6+jMV9mgvNBdmNZ5YugLZ8OXB3c26Psa3v2J6yy9MD3uP8AjBz6kYFhAkEAh0PBLqjuT4PVHaPvYYwlL3DOu8t3VV6TfIIk7jp3bQw+hgbvgYI6AduQFeTS3lfKF1sddiQ2dluKbogeAl9+uQJASMORoHT6XJJ1knYu4KHEUSsRwd1CrOugs+9sHY4O0Fvv48iB83y6tMHo3BiD6CDLE4x6ByRVw5zEXNnRs3XydQ==',
                    //易宝商户公钥
                    'pub_key'=>'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCMft6HJ+tMJyj6Rcpl3epy5ctleUX38JJrSOCme5/ChDlxo7RAc2KgCbVcSycWoNaWSedPvTONJdxJQ5euaklvk6tfQJFNVa4b0ITC8wg8P11ZFdxAl1AfR0PY22C/ZYAyiQ8vn8dxfC7zfp9giTcMxH2HVp40BCw5roa1GbYxFwIDAQAB',
                    //易宝公钥
                    'yee_pub_key'=>'MIGfMA0GCSqGSIb3DQEBAQUAA4GNADCBiQKBgQCWVfdYSCyp109kmmXsOnKZ3MptRd2fgWn2upx32RRD0dm6xUMhSucgqPerknbENthcJjybl1odyRaA3vV/Zjs45cMVIOJjwFxr8LmHezuCJV50onzW+v0tH8EUZt2Hud/UFeDmC2jJh/uU8vRSVsV8JhnQBCRRuOR91DRT3Up5UQIDAQAB',
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