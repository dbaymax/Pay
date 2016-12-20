<?php
/**
 * Created by PhpStorm.
 * User: hemingyang
 * Date: 2016/5/19
 * Time: 11:03
 */

/**
 * 支付统一入口
 */

namespace Common\Pay;
use Common\Pay\Exception\PayException;

/**
 *
 * Class Pay
 * @package Common\Pay
 */

class Pay
{
    const ZHIFUBAO_PAY = 1;
    const WEIXIN_PAY = 2;
    const WEIXIN_PAY_APP = 22;
    const WEIXIN_PAY_Public = 222;
    const YIBAO_PAY = 3;
    const YIBAO_PAY_APP = 33;
    const APP_STORE = 4;

    const AOP_PAY_SUCCESS = 'pay_success';


    //每种支付对应的配置名
    private static $payTypeToConfig = array(
        self::ZHIFUBAO_PAY      => 'ZHIFUBAO_PAY',
        self::WEIXIN_PAY        => 'WEIXIN_PAY',
        self::WEIXIN_PAY_APP    => 'WEIXIN_PAY_APP',
        self::YIBAO_PAY         => 'YIBAO_PAY',
        self::YIBAO_PAY_APP     => 'YIBAO_PAY_APP',
        self::APP_STORE         => 'APP_STORE',
        self::WEIXIN_PAY_Public => 'WEIXIN_PAY_PUBLIC',
    );

    //支付的名称
    private static $payType = array(
        self::ZHIFUBAO_PAY      => '支付宝',
        self::WEIXIN_PAY        => '微信',
        self::WEIXIN_PAY_APP    => '微信',
        self::YIBAO_PAY         => '易宝',
        self::YIBAO_PAY_APP     => '易宝',
        self::APP_STORE         => 'app store',
        self::WEIXIN_PAY_Public => '公众号',
    );

    //类名
    private static $payStrategy = array(
        self::ZHIFUBAO_PAY      => 'AliPay',
        self::WEIXIN_PAY        => 'WxPay',
        self::WEIXIN_PAY_APP    => 'WxAppPay',
        self::YIBAO_PAY         => 'YbPay',
        self::YIBAO_PAY_APP     => 'YbAppPay',
        self::APP_STORE         => 'IapPay',
        self::WEIXIN_PAY_Public => 'WxPublicPay',
    );

    public static function getPayType($payType)
    {
        return isset(self::$payType[$payType]) ? self::$payType[$payType] : false;
    }

    /**
     *成功
     */
    public static function success($data)
    {
        $result = array(
            'code' => 200,
            'data' => $data
        );
        echo json_encode($result);
        exit;
    }

    /**
     * 失败
     * @param $data
     */
    public static function fail($data)
    {
        $result = array(
            'code' => 400,
            'data' => $data
        );
        echo json_encode($result);
        exit;
    }


    /**
     * 支付工厂方法
     *
     * @param $payType
     * @return bool
     */
    public static function payFactory($payType)
    {
        self::setConst();
        return self::getStrategy($payType);
    }


    //初始化支付类
    private static function getStrategy($payType){
        $strategy = isset(self::$payStrategy[$payType]) ? self::$payStrategy[$payType] : false;;
        if(!$strategy) return false;
        $className = 'Common\\Pay\\PayStrategy\\' . $strategy;
        if (class_exists($className)) {
            $class = new $className;
        } else {
            return false;
        }
        return $class;
    }

    public static function setConst()
    {
        defined('PAY_THIRD_PATH') or define('PAY_THIRD_PATH', __DIR__ . '/ThirdPay/');
        defined('PAY_HTML_PATH') or define('PAY_HTML_PATH', __DIR__ . '/Html/');
        defined('PAY_LOG_PATH') or define('PAY_LOG_PATH', __DIR__ . '/Log/');
    }


    /**
     * 读配置
     */
    public static function readConfig($name)
    {
        static $config = array();
        empty($config) &&   self::loadConfig($config);

        if(is_numeric($name)){
            $name = 'PAY_SUCCESS.'.self::$payTypeToConfig[$name];
        }

        //二维数组
        if(strpos($name,'.')){
            $arr = explode('.',$name);
            if(isset($config[$arr[0]][$arr[1]])) {
                $arr[0] = strtoupper($arr[0]);
                return $config[$arr[0]][$arr[1]];
            }
            else{
                throw new PayException('配置错误');
            }
        }
        else{
            $name = strtoupper($name);
            return $config[$name];
        }

        throw new PayException('未读取到配置信息');
    }

    public static  function loadConfig(&$config){
        $config =  include __DIR__.'/config.php';
    }


}
