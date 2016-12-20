<?php

/**
 * Created by PhpStorm.
 * User: ASUS-PC
 * Date: 2016/3/25
 * Time: 11:38
 */


class yibaoPay
{

    /*
     * 调起易宝支付
     * */
    public function pay($data){
        include 'yeepayCommon.php';
        #	商家设置用户购买商品的支付信息.
        ##易宝支付平台统一使用GBK/GB2312编码方式,参数如用到中文，请注意转码

        #	商户订单号,选填.
        ##若不为""，提交的订单号必须在自身账户交易中唯一;为""时，易宝支付会自动生成随机的商户订单号.
        $p2_Order					= $data['orderno'];

        #	支付金额,必填.
        ##单位:元，精确到分.
        $p3_Amt						= $data['rmb'];
//        $p3_Amt						= 0.01;

        #	交易币种,固定值"CNY".
        $p4_Cur						= "CNY";

        #	商品名称
        ##用于支付时显示在易宝支付网关左侧的订单产品信息.
        $p5_Pid						= iconv("UTF-8", "GBK", '充值'.$data['coin'].'V币');

        #	商品种类
        $p6_Pcat					= iconv("UTF-8", "GBK", '用户'.$data['uid'].'充值'.$data['coin'].'V币');

        #	商品描述
        $p7_Pdesc					= iconv("UTF-8", "GBK", '充值'.$data['coin'].'V币');

        #	商户接收支付成功数据的地址,支付成功后易宝支付会向该地址发送两次成功通知.
        $p8_Url						= 'http://'.$_SERVER['HTTP_HOST'].'/Pay/ybPayCallback';
//        $p8_Url						= 'http://www.wanwanyl.com/index.php/Pay/ybPayCallback';

        #	送货地址
        $p9_SAF						= null;

        #	商户扩展信息
        ##商户可以任意填写1K 的字符串,支付成功时将原样返回.
        $pa_MP						= $data['uid'];

        #	支付通道编码
        ##默认为""，到易宝支付网关.若不需显示易宝支付的页面，直接跳转到各银行、神州行支付、骏网一卡通等支付页面，该字段可依照附录:银行列表设置参数值.
        $pd_FrpId					= null;

        #	订单有效期
        ##默认为"7": 7天;
        $pm_Period	= "7";

        #	订单有效期单位
        ##默认为"day": 天;
        $pn_Unit	= "day";

        #	应答机制
        ##默认为"1": 需要应答机制;
        $pr_NeedResponse	= "1";

        #调用签名函数生成签名串

        $hmac = getReqHmacString($p2_Order,$p3_Amt,$p4_Cur,$p5_Pid,$p6_Pcat,$p7_Pdesc,$p8_Url,$p9_SAF,$pa_MP,$pd_FrpId,$pm_Period,$pn_Unit,$pr_NeedResponse);
        echo <<<EOT
            <html>
            <head>
            <title></title>
            </head>
            <body onLoad="document.yeepay.submit();" >
            <form name='yeepay' action='$reqURL_onLine' method='get'>
                <input type='hidden' name='p0_Cmd'					value='$p0_Cmd'>
                <input type='hidden' name='p1_MerId'				value='$p1_MerId'>
                <input type='hidden' name='p2_Order'				value='$p2_Order'>
                <input type='hidden' name='p3_Amt'					value='$p3_Amt'>
                <input type='hidden' name='p4_Cur'					value='$p4_Cur'>
                <input type='hidden' name='p5_Pid'					value='$p5_Pid'>
                <input type='hidden' name='p6_Pcat'					value='$p6_Pcat'>
                <input type='hidden' name='p7_Pdesc'				value='$p7_Pdesc'>
                <input type='hidden' name='p8_Url'					value='$p8_Url'>
                <input type='hidden' name='p9_SAF'					value='$p9_SAF'>
                <input type='hidden' name='pa_MP'					value='$pa_MP'>
                <input type='hidden' name='pd_FrpId'				value='$pd_FrpId'>
                <input type='hidden' name='pm_Period'				value='$pm_Period'>
                <input type='hidden' name='pn_Unit'				    value='$pn_Unit'>
                <input type='hidden' name='pr_NeedResponse'	        value='$pr_NeedResponse'>
                <input type='hidden' name='hmac'					value='$hmac'>
            </form>
            </body>
            </html>
EOT;

    }

}