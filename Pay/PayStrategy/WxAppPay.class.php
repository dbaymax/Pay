<?php
/**
 * Created by PhpStorm.
 * User: hemingyang
 * Date: 2016/5/19
 * Time: 9:24
 */

namespace Common\Pay\PayStrategy;
use Common\Pay\Pay;
use Common\AOP\AOP;

class WxAppPay extends BasePay
{

    public function __construct()
    {
        parent::__construct();
        ini_set('date.timezone','Asia/Shanghai');
        require_once PAY_THIRD_PATH."weixinPay/lib/WxPay.Api.php";
        require_once PAY_THIRD_PATH.'weixinPay/lib/WxPay.Notify.php';
        require_once PAY_THIRD_PATH."weixinPay/example/WxPay.NativePay.php";

        $wxConfig = Pay::readConfig('PAY_CONFIG.weixinAppPay');
        \WxPayApi::setConfig($wxConfig['APPID'],$wxConfig['MCHID'],$wxConfig['KEY'],$wxConfig['APPSECRET']);
    }

    /**
     * 微信支付 回调验证
     * */
    public function callback()
    {
        $notify = new \WxPayNotify();
        $result = $notify->Handle(false);
        //业务结果 == SUCCESS && 返回状态码 == SUCCESS
        if($result && $result['result_code'] == 'SUCCESS' && $result['return_code'] == 'SUCCESS'){
            //总金额
            $data['total_fee'] = $result['total_fee'];
            //现金支付金额
            $data['cash_fee'] = $result['cash_fee'];
            //商户订单号 我们生成的订单号
            $data['number'] = $result['out_trade_no'];
            //attach 返回的是 充值用户id
//            $data['userId'] = $result['attach'];
            //微信支付订单号
            $data['payNumber'] = $result['transaction_id'];
            //获取支付方式 PC or APP
            $data['payType'] = Pay::WEIXIN_PAY_APP;
            /*
            $trade_type = $result['trade_type'];
            $trade_type == 'APP' ? $trade_type = 'APP' : $trade_type = 'PC';
            */
            //支付成功 返回通知 查询返回的该订单是否交易成功 防止有人异常操作
            $result = $this->orderQuery($result['transaction_id'],'transaction_id');
            if($result){
                $rs = AOP::triggerSingleton(Pay::AOP_PAY_SUCCESS,$this,['data'=>$data]);
                !$rs && $this->rechargeFail();
            }
        }
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
        $data = $this->createOrder($post);
        $this->doPay($data);
    }

    /**
     * 微信支付
     * */
    private  function doPay($data){

        $result = $this->wxAndroid($data);
        if($result) {
            Pay::success($result);
        }
        else{
            $this->error('签名失败',2);
        }
    }

    /*
    * 安卓 APP
    * */
    public function wxAndroid($data){
        $wxConfig = Pay::readConfig('PAY_CONFIG.weixinAppPay');

        //微信支付 金额是 1:100
        $data['rmb'] = $data['ramount'] * 100;
        $wxPayApi = new \WxPayApi();
        $input = new \WxPayUnifiedOrder();
        $input->SetOut_trade_no($data['number']);
        $input->SetBody("充值".$data['ramount']);
        $input->SetDetail("充值".$data['ramount']);
        $input->SetTotal_fee($data['rmb']);
//        $input->SetTotal_fee(1);
        //用户id 支付成功后会原样返回
        $input->SetAttach($data['userId']);
        $input->SetNotify_url($wxConfig['notify_url']);
        $input->SetTrade_type('APP');
        $result = $wxPayApi->unifiedOrder($input,6);

        $temp_result = array(
            'appid' => $result['appid'],
            'noncestr' => $result['nonce_str'],
            'package'   => 'Sign=WXPay',
            'partnerid' => $result['mch_id'],
            'timestamp' => time(),
            'prepayid'  => $result['prepay_id']
        );
        $sign = $input->AppSign($temp_result);
        $temp_result['sign'] = $sign;
        return $temp_result;

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