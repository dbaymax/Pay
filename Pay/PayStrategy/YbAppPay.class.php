<?php
/**
 * Created by PhpStorm.
 * User: hemingyang
 * Date: 2016/5/19
 * Time: 9:24
 */

namespace Common\Pay\PayStrategy;
use Common\Pay\Pay;
use Common\Pay\ThirdPay\yeepay\yeepayMPay;
use Common\AOP\AOP;
class YbAppPay extends BasePay
{

    public function pay($post)
    {

        $data = $this->createOrder($post);

        $yeepay_config = Pay::readConfig('PAY_CONFIG.yibaoAppPay');

//        $url_callback = $yeepay_config['url_callback'];
//        $url_data['user_id'] = $data['userId'];
//        $callback = $url_callback.base64_encode(json_encode($url_data));

        $url = $this->submitPayData($data['userId'],$data['coin'],$data['ramount'],$data['number'],$yeepay_config['url_callback']);          //提交支付表单到易宝接口
        header('location:'.$url);
    }

    /**
     * 发送易宝请求数据获取支付链接
     * @param int user_id 用户ID
     * @param string name 资费项目名称
     * @param int money 资费价格
     * @param string order 订单ID
     * @return string url 连接地址
     */
    private function submitPayData($user_id,$coin,$money,$order,$callback){
        $yeepay_config = Pay::readConfig('PAY_CONFIG.yibaoAppPay');
        $pay                = new yeepayMPay(                                               //实例化易宝支付类
            $yeepay_config['key_id'],                                                       //传入易宝商户KEY_ID
            $yeepay_config['pub_key'],                                                      //传入易宝商户开放密钥PUB_KEY
            $yeepay_config['prv_key'],                                                      //传入易宝商户私钥PRV_KEY
            $yeepay_config['yee_pub_key']                                                   //传入易宝开放密钥
        );                                                                                  //易宝支付类实例化结束

        $order_id           = $order;                                 //网页支付的订单在订单有效期内可以进行多次支付请求，但是需要注意的是每次请求的业务参数都要一致，交易时间也要保持一致。否则会报错“订单与已存在的订单信息不符”
        $transtime          = time();                                                       //交易时间，是每次支付请求的时间，注意此参数在进行多次支付的时候要保持一致。
        $product_catalog    = '53';                                                         //商品类编码是我们业管根据商户业务本身的特性进行配置的业务参数。
        $identity_id        = $user_id.time();                                                     //用户身份标识，是生成绑卡关系的因素之一，在正式环境此值不能固定为一个，要一个用户有唯一对应一个用户标识，以防出现盗刷的风险且一个支付身份标识只能绑定5张银行卡
        $identity_type      = 0;                                                            //支付身份标识类型码
        $user_ip            = get_client_ip();                                              //此参数不是固定的商户服务器ＩＰ，而是用户每次支付时使用的网络终端IP，否则的话会有不友好提示：“检测到您的IP地址发生变化，请注意支付安全”。
        $user_ua            = $_SERVER['HTTP_USER_AGENT'];                                  //用户ua
        $callbackurl        = $callback;    //商户后台系统回调地址，前后台的回调结果一样
        $fcallbackurl       = $callback;    //商户前台系统回调地址，前后台的回调结果一样
        $product_name       = '万万娱乐--币';                                       //出于风控考虑，请按下面的格式传递值：应用-商品名称，如“诛仙-3 阶成品天琊”
        $product_desc       = $coin.'--币';                                              //商品描述
        $terminaltype       = 3;                                                            //商户付款类型
        $terminalid         = '05-16-DC-59-C2-34';                                          //其他支付身份信息
        $amount             = intval($money*100);                                           //订单金额单位为分，支付时最低金额为2分，因为测试和生产环境的商户都有手续费（如2%），易宝支付收取手续费如果不满1分钱将按照1分钱收取。
        $cardno             = '';                                                           //用户身份证号码
        $idcardtype         = '';                                                           //证件类型
        $idcard             = '';
        $owner              = '';
        $url                = $pay->webPay(
            $order_id,
            $transtime,
            $amount,
            $cardno,
            $idcardtype,
            $idcard,
            $owner,
            $product_catalog,
            $identity_id,
            $identity_type,
            $user_ip,
            $user_ua,
            $callbackurl,
            $fcallbackurl,
            $currency=156,
            $product_name,
            $product_desc,
            $terminaltype,
            $terminalid,
            $orderexp_date=60
        );
        return $url;                                                                        //返回URL地址
    }


    /**
     * 充值成功调用
     * @return void
     */
    function callback()
    {
        $yeepay_config = Pay::readConfig('PAY_CONFIG.yibaoAppPay');
        $pay = new yeepayMPay(
            $yeepay_config['key_id'],
            $yeepay_config['pub_key'],
            $yeepay_config['prv_key'],
            $yeepay_config['yee_pub_key']
        );
        $return = $pay->callback($_REQUEST['data'], $_REQUEST['encryptkey']);           //调用易宝类的回调函数
        if ($return['status'] == 1) {                                                     //判断用户是否支付成功

            $data['total_fee'] = $return['amount'];
            //商户订单号 我们生成的订单号
            $data['number'] = $return['orderid'];
//            //attach 返回的是 充值用户id
//            $data['userId'] = $data['user_id'];
            //微信支付订单号
            $data['payNumber'] = $return['yborderid'];//暂时不知道 ， 测试后才知道
            $data['payType'] = Pay::YIBAO_PAY_APP;
            $rs = $rs = AOP::triggerSingleton(Pay::AOP_PAY_SUCCESS,$this,['data'=>$data]);
            if ($rs) {
                $this->rechargeSuccess();
            } else {
                $this->rechargeFail();
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

    public function isSuccess()
    {
        $yeepay_config = Pay::readConfig('PAY_CONFIG.yibaoAppPay');
        $pay = new yeepayMPay(
            $yeepay_config['key_id'],
            $yeepay_config['pub_key'],
            $yeepay_config['prv_key'],
            $yeepay_config['yee_pub_key']
        );
        $return = $pay->callback($_REQUEST['data'], $_REQUEST['encryptkey']);           //调用易宝类的回调函数
        if ($return['status'] == 1) {
            $data['payNumber'] = $return['yborderid'];
            $data['number']    = $return['orderid'];
            $detail = $this->Model->findOrderByMany($data['number'],$data['payNumber']);
            return $detail;
        }
        return false;
    }
}