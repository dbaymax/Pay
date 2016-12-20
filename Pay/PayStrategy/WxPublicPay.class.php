<?php
/**
 * Created by PhpStorm.
 * User: hemingyang
 * Date: 2016/5/19
 * Time: 9:24
 */

namespace Common\Pay\PayStrategy;
use Common\Pay\Pay;

class WxPublicPay extends BasePay
{

    public function __construct()
    {
        parent::__construct();
        ini_set('date.timezone','Asia/Shanghai');
        require_once PAY_THIRD_PATH."weixinPay/lib/WxPay.JsApiPay.php";
        require_once PAY_THIRD_PATH.'weixinPay/lib/WxPay.Notify.php';

        $wxConfig = Pay::readConfig('PAY_CONFIG.weixinPay');
        \WxPayApi::setConfig($wxConfig['APPID'],$wxConfig['MCHID'],$wxConfig['KEY'],$wxConfig['APPSECRET']);
    }

    /**
     * 微信支付 回调验证
     * */
    public function callback()
    {
        //走的wx网页支付的回调地址
    }


    /**
     * 支付成功后,写入用户数据成功
     */
    public function rechargeSuccess()
    {
        exit;
    }

    /**
     * 支付成功后,写入用户数据失败
     */
    public function rechargeFail()
    {
        exit;
    }


    public function pay($post)
    {
    }

    public function jsPay($post,$openid)
    {
        $data = $this->createOrder($post);
        return $this->doPay($data ,$openid);
    }

    /**
    * 微信支付
    * */
    private  function doPay($data ,$openid){
        $wxConfig = Pay::readConfig('PAY_CONFIG.weixinPay');
        $tools = new \JsApiPay();
        $openId = $openid;
        //②、统一下单

        $data['rmb'] = $data['ramount'] * 100;
        $input = new \WxPayUnifiedOrder();
        $input->SetBody("星币");
        $input->SetAttach("星币");
        $input->SetOut_trade_no($data['number']);
        $input->SetTotal_fee($data['rmb']);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag("星币");
        $input->SetNotify_url($wxConfig['notify_url']);
        $input->SetTrade_type("JSAPI");
        $input->SetOpenid($openId);
        $order = \WxPayApi::unifiedOrder($input);
        $jsApiParameters = $tools->GetJsApiParameters($order);
        return $jsApiParameters;
    }



    /*
	 * 微信订单查询
	 * */
    public function orderQuery($number,$orderType){

        $input = new \WxPayOrderQuery();
        //按商家自己的订单号查询
        $orderType == 'out_trade_no' && $input->SetOut_trade_no($number);
        //按微信订单号查询
        $orderType == 'transaction_id' &&  $input->SetTransaction_id($number);
        $result = \WxPayApi::orderQuery($input,6);

        //交易状态 SUCCESS 成功
        if($result && $result['trade_state'] == 'SUCCESS' && $result['result_code'] == 'SUCCESS' && $result['return_code'] == 'SUCCESS'){
            return true;
        }
        return false;
    }

}