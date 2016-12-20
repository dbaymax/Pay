<?php
/**
 * Created by PhpStorm.
 * User: hemingyang
 * Date: 2016/5/19
 * Time: 9:24
 */

namespace Common\Pay\PayStrategy;
use Common\Pay\Pay;

class IapPay extends BasePay
{


    public function pay($post)
    {
        $data = $this->createOrder($post);
        Pay::success($data);
    }

    /*
    * 创建订单
    * */
    public function createOrder($post){

        $this->validateIosSum($post);
        $data = parent::setRechargeInfo($post);
        PAY::readConfig('IS_TEST') && $data['ramount'] = $post['RMB'];
        $data = $this->parseIosCoin($data,$post);
        $rs   = parent::addOrder($data,$post);
        //订单生成成功
        $rsdata['product_id'] = $rs['number'];
        return $rsdata;
    }

    private function validateIosSum($post){
        //转int 类型
        $post['RMB'] = intval($post['RMB']);
        if(!is_int($post['RMB']) || !$post['payType'] ) {
            $this->error('支付参数错误',2);
        }
        if ($post['RMB'] < parent::MIN_SUM_IOS) {
            //充值范围外
            $this->error('充值范围外',2);
        }
    }

    /**
     * 根据传入的rmb返回对应vb数量
     * @param $rmb
     * @return mixed
     */
    private function parseIosCoin($data,$post){
        $vbArray = array(
            6=>6*0.7*$post['ratio'],
            18=>18*0.7*$post['ratio'],
            30=>30*0.7*$post['ratio'],
            50=>50*0.7*$post['ratio'],
            98=>98*0.7*$post['ratio'],
            248=>248*0.7*$post['ratio'],
            298=>298*0.7*$post['ratio'],
            488=>488*0.7*$post['ratio'],
            998=>998*0.7*$post['ratio'],
            1498=>1498*0.7*$post['ratio']
        );
        $data['coin'] = $vbArray[$post['RMB']];
        !$data['coin'] && $this->error('充值范围外',2);
        return $data;
    }

    /**
     * 返回字段：
     * original_purchase_date  原始购买日期 2016-04-28 03:16:09 America/Los_Angeles
     * purchase_date_ms 	   购买时间	  1461838569145
     * quantity				   数量
     * product_id			   产品id
     * transaction_id		   商户订单号
     * @param $payType
     */
    public function callback()
    {
        $redis = Redis();
        $callNum = $redis->get('iap_'.$_REQUEST['product_id']);
        if($callNum >=5){
            $this->error('error.',2);
        }
        $redis->incr('iap_'.$_REQUEST['product_id']);

        $data['receipt-data'] = $_REQUEST['receipt'];
//        $data['receipt-data'] = 'ewoJInNpZ25hdHVyZSIgPSAiQTJmY1MxODEwZFR6K3JsV0lscllGK29YSDJZekRQNEJlOHBVNW10Wm5LaXJ6SE5RMEhST090TWZXeDM2S0VEMGczT3E1K2lnbGw0T0Y3MjV6TjRKTWJzZkRvbGFxYmNxaXlHV1BSdUUvR25Ra2twZ3pqUDNrUFhWUTAwM01GMFcyK2FDNnJLZ1FHZi9EYTVSdVltV0U4ZmtiakRFNHdaZTh1VVFqc3h6NE5QNUdWVXViV2xEWjZTRWFjT01CcVZ5cmx4YVI2eTduUjJxZjdrcGhHM3MrWWNwMTJyRmZpOTZZUkYyckx1RU5RT1o1eC8rMWpRMThIaXkyNTk3djZzb3J0aTE4WlNkYU1rS0kwd25yeFVxdVBySnBQUElrMHFodzZnSTg2SHluTzBZV1FtTVVzYUxLMjdXd3hrdVE5YnFOcVVRMHlEZlVUT1NDL2ppTkFNN1plZ0FBQVdBTUlJRmZEQ0NCR1NnQXdJQkFnSUlEdXRYaCtlZUNZMHdEUVlKS29aSWh2Y05BUUVGQlFBd2daWXhDekFKQmdOVkJBWVRBbFZUTVJNd0VRWURWUVFLREFwQmNIQnNaU0JKYm1NdU1Td3dLZ1lEVlFRTERDTkJjSEJzWlNCWGIzSnNaSGRwWkdVZ1JHVjJaV3h2Y0dWeUlGSmxiR0YwYVc5dWN6RkVNRUlHQTFVRUF3dzdRWEJ3YkdVZ1YyOXliR1IzYVdSbElFUmxkbVZzYjNCbGNpQlNaV3hoZEdsdmJuTWdRMlZ5ZEdsbWFXTmhkR2x2YmlCQmRYUm9iM0pwZEhrd0hoY05NVFV4TVRFek1ESXhOVEE1V2hjTk1qTXdNakEzTWpFME9EUTNXakNCaVRFM01EVUdBMVVFQXd3dVRXRmpJRUZ3Y0NCVGRHOXlaU0JoYm1RZ2FWUjFibVZ6SUZOMGIzSmxJRkpsWTJWcGNIUWdVMmxuYm1sdVp6RXNNQ29HQTFVRUN3d2pRWEJ3YkdVZ1YyOXliR1IzYVdSbElFUmxkbVZzYjNCbGNpQlNaV3hoZEdsdmJuTXhFekFSQmdOVkJBb01Da0Z3Y0d4bElFbHVZeTR4Q3pBSkJnTlZCQVlUQWxWVE1JSUJJakFOQmdrcWhraUc5dzBCQVFFRkFBT0NBUThBTUlJQkNnS0NBUUVBcGMrQi9TV2lnVnZXaCswajJqTWNqdUlqd0tYRUpzczl4cC9zU2cxVmh2K2tBdGVYeWpsVWJYMS9zbFFZbmNRc1VuR09aSHVDem9tNlNkWUk1YlNJY2M4L1cwWXV4c1FkdUFPcFdLSUVQaUY0MWR1MzBJNFNqWU5NV3lwb041UEM4cjBleE5LaERFcFlVcXNTNCszZEg1Z1ZrRFV0d3N3U3lvMUlnZmRZZUZScjZJd3hOaDlLQmd4SFZQTTNrTGl5a29sOVg2U0ZTdUhBbk9DNnBMdUNsMlAwSzVQQi9UNXZ5c0gxUEttUFVockFKUXAyRHQ3K21mNy93bXYxVzE2c2MxRkpDRmFKekVPUXpJNkJBdENnbDdaY3NhRnBhWWVRRUdnbUpqbTRIUkJ6c0FwZHhYUFEzM1k3MkMzWmlCN2o3QWZQNG83UTAvb21WWUh2NGdOSkl3SURBUUFCbzRJQjF6Q0NBZE13UHdZSUt3WUJCUVVIQVFFRU16QXhNQzhHQ0NzR0FRVUZCekFCaGlOb2RIUndPaTh2YjJOemNDNWhjSEJzWlM1amIyMHZiMk56Y0RBekxYZDNaSEl3TkRBZEJnTlZIUTRFRmdRVWthU2MvTVIydDUrZ2l2Uk45WTgyWGUwckJJVXdEQVlEVlIwVEFRSC9CQUl3QURBZkJnTlZIU01FR0RBV2dCU0lKeGNKcWJZWVlJdnM2N3IyUjFuRlVsU2p0ekNDQVI0R0ExVWRJQVNDQVJVd2dnRVJNSUlCRFFZS0tvWklodmRqWkFVR0FUQ0IvakNCd3dZSUt3WUJCUVVIQWdJd2diWU1nYk5TWld4cFlXNWpaU0J2YmlCMGFHbHpJR05sY25ScFptbGpZWFJsSUdKNUlHRnVlU0J3WVhKMGVTQmhjM04xYldWeklHRmpZMlZ3ZEdGdVkyVWdiMllnZEdobElIUm9aVzRnWVhCd2JHbGpZV0pzWlNCemRHRnVaR0Z5WkNCMFpYSnRjeUJoYm1RZ1kyOXVaR2wwYVc5dWN5QnZaaUIxYzJVc0lHTmxjblJwWm1sallYUmxJSEJ2YkdsamVTQmhibVFnWTJWeWRHbG1hV05oZEdsdmJpQndjbUZqZEdsalpTQnpkR0YwWlcxbGJuUnpMakEyQmdnckJnRUZCUWNDQVJZcWFIUjBjRG92TDNkM2R5NWhjSEJzWlM1amIyMHZZMlZ5ZEdsbWFXTmhkR1ZoZFhSb2IzSnBkSGt2TUE0R0ExVWREd0VCL3dRRUF3SUhnREFRQmdvcWhraUc5Mk5rQmdzQkJBSUZBREFOQmdrcWhraUc5dzBCQVFVRkFBT0NBUUVBRGFZYjB5NDk0MXNyQjI1Q2xtelQ2SXhETUlKZjRGelJqYjY5RDcwYS9DV1MyNHlGdzRCWjMrUGkxeTRGRkt3TjI3YTQvdncxTG56THJSZHJqbjhmNUhlNXNXZVZ0Qk5lcGhtR2R2aGFJSlhuWTR3UGMvem83Y1lmcnBuNFpVaGNvT0FvT3NBUU55MjVvQVE1SDNPNXlBWDk4dDUvR2lvcWJpc0IvS0FnWE5ucmZTZW1NL2oxbU9DK1JOdXhUR2Y4YmdwUHllSUdxTktYODZlT2ExR2lXb1IxWmRFV0JHTGp3Vi8xQ0tuUGFObVNBTW5CakxQNGpRQmt1bGhnd0h5dmozWEthYmxiS3RZZGFHNllRdlZNcHpjWm04dzdISG9aUS9PamJiOUlZQVlNTnBJcjdONFl0UkhhTFNQUWp2eWdhWndYRzU2QWV6bEhSVEJoTDhjVHFBPT0iOwoJInB1cmNoYXNlLWluZm8iID0gImV3b0pJbTl5YVdkcGJtRnNMWEIxY21Ob1lYTmxMV1JoZEdVdGNITjBJaUE5SUNJeU1ERTJMVEEyTFRNd0lEQTBPalU0T2pJMUlFRnRaWEpwWTJFdlRHOXpYMEZ1WjJWc1pYTWlPd29KSW5WdWFYRjFaUzFwWkdWdWRHbG1hV1Z5SWlBOUlDSmpaakV4TVdFeFpqTXpOVFZqT1RJNE5qRmhZbU14WW1VNE5ERTVZbVV5WldReU5HUTVOell5SWpzS0NTSnZjbWxuYVc1aGJDMTBjbUZ1YzJGamRHbHZiaTFwWkNJZ1BTQWlNVEF3TURBd01ESXlNRGcxT0RVM05DSTdDZ2tpWW5aeWN5SWdQU0FpTVNJN0Nna2lkSEpoYm5OaFkzUnBiMjR0YVdRaUlEMGdJakV3TURBd01EQXlNakE0TlRnMU56UWlPd29KSW5GMVlXNTBhWFI1SWlBOUlDSXhJanNLQ1NKdmNtbG5hVzVoYkMxd2RYSmphR0Z6WlMxa1lYUmxMVzF6SWlBOUlDSXhORFkzTWpnM09UQTFOVEV6SWpzS0NTSjFibWx4ZFdVdGRtVnVaRzl5TFdsa1pXNTBhV1pwWlhJaUlEMGdJamc0T1RKRk5FWXhMVU5FUTBVdE5ERkJRaTA1UmtNNUxUYzNNVGhGUTBGQ1JETkZSQ0k3Q2draWNISnZaSFZqZEMxcFpDSWdQU0FpZUdKZk5pSTdDZ2tpYVhSbGJTMXBaQ0lnUFNBaU1URXlPVGN5TVRVM09TSTdDZ2tpWW1sa0lpQTlJQ0pqYjIwdWQyRnVkMkZ1TG5Ob2RXNTNaV2xuWlNJN0Nna2ljSFZ5WTJoaGMyVXRaR0YwWlMxdGN5SWdQU0FpTVRRMk56STROemt3TlRVeE15STdDZ2tpY0hWeVkyaGhjMlV0WkdGMFpTSWdQU0FpTWpBeE5pMHdOaTB6TUNBeE1UbzFPRG95TlNCRmRHTXZSMDFVSWpzS0NTSndkWEpqYUdGelpTMWtZWFJsTFhCemRDSWdQU0FpTWpBeE5pMHdOaTB6TUNBd05EbzFPRG95TlNCQmJXVnlhV05oTDB4dmMxOUJibWRsYkdWeklqc0tDU0p2Y21sbmFXNWhiQzF3ZFhKamFHRnpaUzFrWVhSbElpQTlJQ0l5TURFMkxUQTJMVE13SURFeE9qVTRPakkxSUVWMFl5OUhUVlFpT3dwOSI7CgkiZW52aXJvbm1lbnQiID0gIlNhbmRib3giOwoJInBvZCIgPSAiMTAwIjsKCSJzaWduaW5nLXN0YXR1cyIgPSAiMCI7Cn0=';
//        $_REQUEST['product_id']  = '1467287947BTF0OTEzaaFpJF0DYm2S1h';
        $url = Pay::readConfig('IS_TEST') ? 'https://sandbox.itunes.apple.com/verifyReceipt' : 'https://buy.itunes.apple.com/verifyReceipt';

        $data = json_encode($data);
        $ch = curl_init($url);
        curl_setopt($ch,CURLOPT_RETURNTRANSFER,1);//显示输出结果
        curl_setopt($ch,CURLOPT_SSL_VERIFYPEER,0);//ssl证书认证
        curl_setopt($ch,CURLOPT_SSL_VERIFYHOST,0);//严格认证
        curl_setopt($ch,CURLOPT_POST,1);//post请求
        curl_setopt($ch,CURLOPT_POSTFIELDS,$data);//post数据字段
        $rs = curl_exec($ch);

        $redis->set('iap_url',$url);
        $redis->incr('callNums');
        $redis->lPush('callList',$_REQUEST['product_id'].'__'.$_REQUEST['receipt'].'__'.$rs);

        $reciept = json_decode($rs,1);
        //传入receipt信息，检查订单
        $this->checkReceipt($reciept,$_REQUEST['product_id']);
        /*
        $rs = $this->checkReceipt($reciept,$_REQUEST['product_id']);
        if($rs){
            echo json_encode(array('state'=>1,'data'=>array('product_id'=>$_REQUEST['product_id'])));
        }else{
            echo json_encode(array('state'=>0,'msg'=>'验证失败'));
        }
        */

    }


    /**
     * 检查苹果返回信息
     */
    private  function checkReceipt($receipt,$number)
    {
        $order = $this->Model->findOrder($number);
        //检查status
        //状态错误，返回错误信息
        if($receipt['status']!=0){
            $this->error('error',2);
        }
        //检查orderno
        if(!$order){
            $this->error('error',2);
        }
        //检查金额
        $moneySum = preg_match('/^mb_(\d+)/',$receipt['receipt']['product_id'],$matches);
        if($matches[1]!=$order['ramount']){
            $this->error('error',2);
        }
        //上面全部正确以后，验证unique_identifier,如果存在，非法订单
        $unique = $this->Model->findPayNumber($receipt['receipt']['transaction_id']);
        if($unique){
            $this->error('error',2);
        }
        //全部检查通过后，走成功通道
        $data['total_fee'] = $order['ramount']*100;
        $data['number']    = $order['number'];
        $data['userId']    = $order['userId'];
        $data['payNumber'] = $receipt['receipt']['transaction_id'];
        $data['payType']   = Pay::APP_STORE;

        $rs = $this->paySuccess($data,Pay::APP_STORE);

        $rs ? $this->rechargeSuccess(['product_id'=>$_REQUEST['product_id'],'uid'=>$data['userId']])
            : $this->rechargeFail($data,$this->getPaySucessError(),$_REQUEST['product_id']) ;

    }


    /**
     * 支付成功后,写入用户数据成功
     */
    public function rechargeSuccess($data)
    {
        $value = M('Member')->where(['userId'=>$data['uid']])->getField('coin');
        Pay::success(array('product_id'=>$data['product_id'],'mb'=>$value));
    }

    /**
     * 支付成功后,写入用户数据失败
     */
    public function rechargeFail($data,$reasn='',$product_id='')
    {
        parent::rechargeError($data,$reasn);
        Pay::fail(array('product_id'=>$product_id));
    }

}