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
class YbPay extends BasePay
{

    private $yibaoConfig ;

    public function __construct()
    {
        parent::__construct();
        $yibaoConfig= Pay::readConfig('PAY_CONFIG.yibaoPay');
        $this->yibaoConfig = $yibaoConfig;

        $p1_MerId    = $yibaoConfig['p1_MerId'];
        $merchantKey = $yibaoConfig['merchantKey'];
        $logName     = $yibaoConfig['logName'] ;
        $logPath     = PAY_LOG_PATH;
        $str = <<<EOT
        <?php
        #	商户编号p1_MerId,以及密钥merchantKey 需要从易宝支付平台获得
        \$p1_MerId			= '$p1_MerId';																										#测试使用
        \$merchantKey	    = '$merchantKey';
        \$logName	        = '$logPath$logName';
EOT;
        file_put_contents(PAY_THIRD_PATH.'yibaoPay/merchantProperties.php',$str);
    }

    /**
    * 易宝支付 回调
    * */
    public function callback()
    {
        $result = $this->notify();
        if($result){
            //订单支付 uid
            $data['userId'] = $_REQUEST['r8_MP'];
            //商户订单Id (我们自己的订单号)
            $data['number'] = $_REQUEST['r6_Order'];
            //易宝支订单号
            $data['payNumber'] = $_REQUEST['r2_TrxId'];
            //支付金额
            $data['total_fee'] = $_REQUEST['r3_Amt']*100;
            $data['payType'] = Pay::YIBAO_PAY;

            $rs = AOP::triggerSingleton(Pay::AOP_PAY_SUCCESS,$this,['data'=>$data]);
            if(!$rs){
                $this->rechargeFail();
            }
            else{
                $this->rechargeSuccess();
            }

        }
    }

    private function notify(){
        include PAY_THIRD_PATH.'yibaoPay/yeepayCommon.php';
        #	只有支付成功时易宝支付才会通知商户.
        ##支付成功回调有两次，都会通知到在线支付请求参数中的p8_Url上：浏览器重定向;服务器点对点通讯.

        #	解析返回参数.
        $return = getCallBackValue($r0_Cmd,$r1_Code,$r2_TrxId,$r3_Amt,$r4_Cur,$r5_Pid,$r6_Order,$r7_Uid,$r8_MP,$r9_BType,$hmac);

        #	判断返回签名是否正确（True/False）
        $bRet = CheckHmac($r0_Cmd,$r1_Code,$r2_TrxId,$r3_Amt,$r4_Cur,$r5_Pid,$r6_Order,$r7_Uid,$r8_MP,$r9_BType,$hmac);
        #	以上代码和变量不需要修改.

        #	校验码正确.
        if($bRet){
            if($r1_Code=="1"){

                #	需要比较返回的金额与商家数据库中订单的金额是否相等，只有相等的情况下才认为是交易成功.
                #	并且需要对返回的处理进行事务控制，进行记录的排它性处理，在接收到支付结果通知后，判断是否进行过业务逻辑处理，不要重复进行业务逻辑处理，防止对同一条交易重复发货的情况发生.

                if($r9_BType=="1"){
//                    echo "交易成功";
//                    echo  "<br />在线支付页面返回";
                }elseif($r9_BType=="2"){
                    #如果需要应答机制则必须回写流,以success开头,大小写不敏感.
//                    echo "success";
//                    echo "<br />交易成功";
//                    echo  "<br />在线支付服务器返回";
                }
                return true;
            }
        }else{
//            echo "交易信息被篡改";
            return false;
        }
    }


    /**
     * 支付成功后,写入用户数据成功
     */
    public function rechargeSuccess()
    {
        echo 'SUCCESS';
        exit;
    }

    /**
     * 支付成功后,写入用户数据失败
     */
    public function rechargeFail()
    {
        echo 'FAIL';
        exit;
    }


    public function pay($post)
    {
        $data = $this->createOrder($post);
        $this->doPay($data);
    }


    /**
      * 调起易宝支付
      * */
    private  function doPay($data){

            include PAY_THIRD_PATH.'yibaoPay/yeepayCommon.php';
            #	商家设置用户购买商品的支付信息.
            ##易宝支付平台统一使用GBK/GB2312编码方式,参数如用到中文，请注意转码

            #	商户订单号,选填.
            ##若不为""，提交的订单号必须在自身账户交易中唯一;为""时，易宝支付会自动生成随机的商户订单号.
            $p2_Order					= $data['number'];

            #	支付金额,必填.
            ##单位:元，精确到分.
            $p3_Amt						= $data['ramount'];

            #	交易币种,固定值"CNY".
            $p4_Cur						= "CNY";

            #	商品名称
            ##用于支付时显示在易宝支付网关左侧的订单产品信息.
            $p5_Pid						= iconv("UTF-8", "GBK", '充值'.$data['coin'].'币');

            #	商品种类
            $p6_Pcat					= iconv("UTF-8", "GB2312", '用户'.$data['userId'].'充值'.$data['coin'].'币');

            #	商品描述
            $p7_Pdesc					= iconv("UTF-8", "GB2312", '充值'.$data['coin'].'币');

            #	商户接收支付成功数据的地址,支付成功后易宝支付会向该地址发送两次成功通知.
            $p8_Url						= $this->yibaoConfig['notifyUrl'];
//        $p8_Url						= 'http://www.wanwanyl.com/index.php/Pay/ybPayCallback';

            #	送货地址
            $p9_SAF						= null;

            #	商户扩展信息
            ##商户可以任意填写1K 的字符串,支付成功时将原样返回.
            $pa_MP                      = Pay::readConfig('IS_TEST') ? 'test' : $data['userId'];

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
        header('Content-type:text/html;charset=GBK');
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


    public function isSuccess()
    {
        //商户订单Id (我们自己的订单号)
        $data['number'] = $_POST['r6_Order'];
        //易宝支订单号
        $data['payNumber'] = $_POST['r2_TrxId'];

        $detail = $this->Model->findOrderByMany($data['number'],$data['payNumber']);

        return $detail;

    }

}