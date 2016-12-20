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
class WxPay extends BasePay
{

    public function __construct()
    {
        parent::__construct();
        ini_set('date.timezone','Asia/Shanghai');
        require_once PAY_THIRD_PATH."weixinPay/lib/WxPay.Api.php";
        require_once PAY_THIRD_PATH.'weixinPay/lib/WxPay.Notify.php';
        require_once PAY_THIRD_PATH."weixinPay/example/WxPay.NativePay.php";

        $wxConfig = Pay::readConfig('PAY_CONFIG.weixinPay');
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
            //$data['userId'] = $result['attach'];
            //微信支付订单号
            $data['payNumber'] = $result['transaction_id'];
            $data['payType'] = Pay::WEIXIN_PAY;
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

        $url2 = $this->IMG($data);
        $url2 = urlencode($url2);
        $url2 = "http://paysdk.weixin.qq.com/example/qrcode.php?data=".$url2;
//		echo '<img alt="模式二扫码支付" src="http://paysdk.weixin.qq.com/example/qrcode.php?data='.$url2.'" style="width:150px;height:150px;"/>';
        $data['url'] = $url2;
        ob_start();
        ob_implicit_flush(0);
        include PAY_HTML_PATH.'weixinPay.html';
        // 获取并清空缓存
        $content = ob_get_clean();
        header('Content-type:text/html;charset=UTF-8');
        echo $content;
    }

    /**
     * 微信扫一扫二维码
     * */
    private function IMG($data){

        $notify = new \NativePay();
        //模式二
        /**
         * 流程：
         * 1、调用统一下单，取得code_url，生成二维码
         * 2、用户扫描二维码，进行支付
         * 3、支付完成之后，微信服务器会通知支付成功
         * 4、在支付成功通知中需要查单确认是否真正支付成功（见：notify.php）
         */

        $wxConfig = Pay::readConfig('PAY_CONFIG.weixinPay');

        //微信支付 金额是 1:100
        $data['rmb'] = $data['ramount'] * 100;
        $input = new \WxPayUnifiedOrder();
        $input->SetBody("充值".$data['ramount']);
        //用户id 支付成功后会原样返回
        Pay::readConfig('IS_TEST') ? $input->SetAttach('test') : $input->SetAttach($data['userId']);

        $input->SetOut_trade_no($data['number']);
//        if($data['userId']==232){
//            $input->SetTotal_fee(1);
//        }
//        else{
//            $input->SetTotal_fee($data['rmb']);
//        }
        $input->SetTotal_fee($data['rmb']);
        $input->SetTime_start(date("YmdHis"));
        $input->SetTime_expire(date("YmdHis", time() + 600));
        $input->SetGoods_tag("充值".$data['ramount']);
        $input->SetNotify_url($wxConfig['notify_url']);
        $input->SetTrade_type("NATIVE");
        $input->SetProduct_id($data['number']);
        $input->SetSign();
        $result = $notify->GetPayUrl($input);
        $url2 = $result["code_url"];
        return $url2;
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