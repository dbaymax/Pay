<?php
/**
 * Created by PhpStorm.
 * User: hemingyang
 * Date: 2016/5/19
 * Time: 9:30
 */

namespace Common\Pay\PayStrategy;
use Common\Pay\Pay;
use Common\Pay\Model\Model;
/**
 * 支付抽象基类
 * Class Pay
 * @package PayStrategy
 */
abstract class BasePay
{

    const MODE_SELF  = 1; //自己充值
    const MODE_PROXY = 2; //代理充值

    const MIN_SUM     = 10;//最小充值金额
    const MIN_SUM_IOS = 6;//ios最小充值金额

    //数据库操作类
    protected $Model ;
    //支付成功操作，管理类
    protected $paySuccessManager;

    //构造方法，实例化数据库操作对象
    public function __construct()
    {
        $this->Model = new  Model();
    }

    //抽象方法
    abstract function pay($arg);
    abstract function callback();
    abstract function rechargeFail();
    abstract function rechargeSuccess();


    /**
     * 抛出支付异常
     * @param $msg  错误信息
     * @param $code  1.web端支付错误  2,app端支付错误
     * @throws \Common\Pay\Exception\PayException
     */
    protected  function error($msg,$code=1){
        throw new \Common\Pay\Exception\PayException($msg,$code);
    }

    /**
     * 随机生成字符串
     */
    private function generateStr($number){
        $str = null;
        $strPol = "ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789abcdefghijklmnopqrstuvwxyz";
        $max = strlen($strPol)-1;

        for($i=0;$i<$number;$i++){
            $str.=$strPol[rand(0,$max)];//rand($min,$max)生成介于min和max两个数之间的一个随机整数
        }

        return $str;
    }


    /*
	 * 创建订单
	 * */
    protected function createOrder($post){

        $this->validateSum($post);
        $data = $this->setRechargeInfo($post);
        $data = $this->parseCoin($data,$post);
        $rs   = $this->addOrder($data,$post);
        return  $rs;

    }

    protected function validateSum($post){
        //转int 类型
        $post['RMB'] = intval($post['RMB']);
        if(!is_int($post['RMB']) || !$post['payType'] ) {
            $this->error('支付参数错误');
        }
        if ($post['RMB'] < self::MIN_SUM) {
            //充值范围外
            $this->error('充值范围外');
        }
    }

    protected function addOrder($data,$post){
        $rs = $this->Model->add($data);
        if($rs){
            //订单生成成功
            $id = $this->Model->getLastInsID();
            $data['id'] = $id;
            $data['payType'] = $post['payType'];
            $this->isCPS($data);
            return $data;
        }
        $this->error('订单生成失败');
    }

    protected function setRechargeInfo($post){

        $data = array();

        $chargeMode = $post['agent'] ? self::MODE_PROXY : self::MODE_SELF;
        //根据充值模型，添加充值人数据
        switch($chargeMode){
            case self::MODE_PROXY : $data['rechargeUserId']   = $post['agent'];
                                    $data['rechargeUsername'] = $this->Model->getUsernameById($post['agent']);
                                    $data['rechargeMode'] = self::MODE_PROXY;
                                    break;
            case self::MODE_SELF  : $data['rechargeUserId']   = $post['userId'];
                                    $data['rechargeUsername'] = $this->Model->getUsernameById($post['userId']);
                                    $data['rechargeMode'] = self::MODE_SELF;
                                    break;
            default : $this->error('模型错误');
        }
        //被充值人id和用户名
        $data['userId']   = $post['userId'];
        $data['username'] = $this->Model->getUsernameById($post['userId']);

        //充值金额
        $data['ramount'] = PAY::readConfig('IS_TEST') ? 0.02 : $post['RMB'];
        

        //订单号 当前时间搓+22位随机字符
        $data['number'] = time().$this->generateStr(22);
        //下单时间
        $data['created'] = time();
        $data['orderStatus'] = 0;

        return $data;
    }

    protected  function  parseCoin($data,$post){
        //RMB 兑换成星币
        $v_b = $post['RMB'] * $post['ratio'];
        $data['coin'] = $v_b;
        return $data;
    }

    /**
     * 支付成功后,写入用户数据失败,属于掉单
     */
    protected function rechargeError($data,$reason=''){
        empty($reason) && $reason = '充值成功，但是数据库操作失败';
        $this->Model->rechargeError($data,$reason);
        return $reason;
    }

    /**
     * 支付成功，进行数据库操作
     */
    protected  function paySuccess($data,$payType)
    {
        $this->paySuccessManager = new \Common\Pay\PaySuccess\PaySuccessManager($payType);
        $rs = $this->paySuccessManager->paySuccess($data);
        return $rs;
    }

    protected function getPaySucessError(){
        return $this->paySuccessManager->getError();
    }



    /**
     * 是否是 CPS 过来的用户 充值
     */
    private function isCPS($data){
        if(isset($_COOKIE['subid']) && !empty($_COOKIE['subid'])){
            $this->Model->isCPS($data);
        }
    }



    /*测试方法*/
    /*
    public function testCallback()
    {
        //总金额
        $data['total_fee'] = 24800;
        //商户订单号 我们生成的订单号
        $data['number'] = '1463982334P447GPI3tkEMjN8217V2cr';
        //订单uid
        $data['userId'] = 2;
        //支付宝交易号 订单号
        $data['payNumber'] = 'sd12asd5w4q12d1as';
        $data['payType'] = Pay::APP_STORE;

        $rs = $this->paySuccess($data,Pay::APP_STORE);
        if($rs){
            echo 'scuccess';
            exit;
        }
        else{
            echo 'fail';
            $this->rechargeFail($data,$this->getPaySucessError());
        }
    }
    */


}