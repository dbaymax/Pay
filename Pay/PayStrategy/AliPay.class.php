<?php
/**
 * Created by PhpStorm.
     * User: hemingyang
 * Date: 2016/5/18
 * Time: 20:03
 */

namespace Common\Pay\PayStrategy;
use Common\Pay\Pay;
use Common\AOP\AOP;

class AliPay extends BasePay
{

    public function callback()
    {
        $result = $this->notify();
        if($result){
            //总金额
            $data['total_fee'] = $result['total_fee']*100;
            //商户订单号 我们生成的订单号
            $data['number'] = $result['out_trade_no'];
//            //订单uid
//            $data['uid'] = $result['body'];
            //支付宝交易号 订单号
            $data['payNumber'] = $result['trade_no'];
            $data['payType'] = Pay::ZHIFUBAO_PAY;

            $rs = AOP::triggerSingleton(Pay::AOP_PAY_SUCCESS,$this,['data'=>$data]);

            if($rs){
                $this->rechargeSuccess();
            }
            else{
                $this->rechargeFail();
            }
        }
    }


    /**
     * 支付成功后,写入用户数据成功
     */
    public function rechargeSuccess()
    {
        //支付宝返回
        echo "success";		//请不要修改或删除
        exit;
    }

    /**
     * 支付成功后,写入用户数据失败
     */
    public function rechargeFail()
    {
        echo 'Fail';
        exit;
    }

    private function notify(){
        require_once(PAY_THIRD_PATH."alipayPay/lib/alipay_notify.class.php");

        $alipay_config = Pay::readConfig('PAY_CONFIG.alipay');

        //计算得出通知验证结果
        $alipayNotify = new \AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyNotify();
        if($verify_result) {//验证成功
            $out_trade_no = $_POST['out_trade_no'];
            //支付宝交易号
            $trade_no = $_POST['trade_no'];
            //交易状态
            $trade_status = $_POST['trade_status'];
            if($_POST['trade_status'] == 'TRADE_FINISHED') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //请务必判断请求时的total_fee、seller_id与通知时获取的total_fee、seller_id为一致的
                //如果有做过处理，不执行商户的业务程序

                //注意：
                //退款日期超过可退款期限后（如三个月可退款），支付宝系统发送该交易状态通知

                //调试用，写文本函数记录程序运行情况是否正常
                //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
            }
            else if ($_POST['trade_status'] == 'TRADE_SUCCESS') {
                //判断该笔订单是否在商户网站中已经做过处理
                //如果没有做过处理，根据订单号（out_trade_no）在商户网站的订单系统中查到该笔订单的详细，并执行商户的业务程序
                //请务必判断请求时的total_fee、seller_id与通知时获取的total_fee、seller_id为一致的
                //如果有做过处理，不执行商户的业务程序

                //注意：
                //付款完成后，支付宝系统发送该交易状态通知

                //调试用，写文本函数记录程序运行情况是否正常
                //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
            }
            return $_POST;
        }
        else {
            //验证失败
//            echo "fail";
            //调试用，写文本函数记录程序运行情况是否正常
            //logResult("这里写入想要调试的代码变量值，或其他运行的结果记录");
            return false;
        }
    }

    public function pay($post)
    {
        $data = $this->createOrder($post);
        $this->doPay($data);
    }

    private  function doPay($data){
        header("Content-type:text/html;charset=utf-8");
        require_once(PAY_THIRD_PATH."alipayPay/lib/alipay_submit.class.php");

        $alipay_config = Pay::readConfig('PAY_CONFIG.alipay');
        /**************************请求参数**************************/
        //商户订单号，商户网站订单系统中唯一订单号，必填
        $out_trade_no = $data['number'];
        //订单名称，必填
        $subject = '充值'.$data['coin'].'星币';
        //付款金额，必填
        $total_fee = $data['ramount'];
//        $total_fee = 0.01;
        //商品描述，可空
        $body = $data['userId'];
        /************************************************************/
        //构造要请求的参数数组，无需改动
        $parameter = array(
            "service"       => $alipay_config['service'],
            "partner"       => $alipay_config['partner'],
            "seller_id"  => $alipay_config['seller_id'],
            "payment_type"	=> $alipay_config['payment_type'],
            "notify_url"	=> $alipay_config['notify_url'],
            "return_url"	=> $alipay_config['return_url'],

            "anti_phishing_key"=>$alipay_config['anti_phishing_key'],
            "exter_invoke_ip"=>$alipay_config['exter_invoke_ip'],
            "out_trade_no"	=> $out_trade_no,
            "subject"	=> $subject,
            "total_fee"	=> $total_fee,
            "body"	=> $body,
            "_input_charset"	=> trim(strtolower($alipay_config['input_charset']))
            //其他业务参数根据在线开发文档，添加参数.文档地址:https://doc.open.alipay.com/doc2/detail.htm?spm=a219a.7629140.0.0.kiX33I&treeId=62&articleId=103740&docType=1
            //如"参数名"=>"参数值"
        );
        //建立请求
        $alipaySubmit = new \AlipaySubmit($alipay_config);
        $html_text = $alipaySubmit->buildRequestForm($parameter,"get", "确认");
        echo $html_text;
    }

    public function isSuccess()
    {
        if($_GET){
            //商家订单号 (我们自己的订单号)
            $data['number'] = $_GET['out_trade_no'];
            //支付宝交易号
            $data['payNumber'] = $_GET['trade_no'];
            //交易单 用户id
            if($_GET['trade_status'] == 'TRADE_FINISHED' || $_GET['trade_status'] == 'TRADE_SUCCESS') {
                $detail = $this->Model->findOrderByMany($data['number'],$data['payNumber']);
                return $detail;
            }
        }
        return false;
    }





}