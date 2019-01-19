<?php

namespace Home\Controller;
use Think\Controller;
header('content-type:text/html;charset=utf-8');
class WxPayController{

	public $mchid;
    public $appid;
    public $apiKey;
	
	public function __construct($mchid, $appid, $key)
    {
		$this->mchid = C('WX_MCHID');
        $this->appid = C('WX_APPID');
        $this->apiKey =C('WX_KEY');
    }
	
   public function callback() {
	   //file_put_contents(time(),file_get_contents('php://input'));
	   $result = $this->notify();
		if($result){
			//完成你的逻辑
			//例如连接数据库，获取付款金额$result['cash_fee']，获取订单号$result['out_trade_no']，修改数据库中的订单状态等;
			
			
			$wxpay = M("wxpay")->where(array('pay_no'=>$result['out_trade_no']))->find();
			
			if($wxpay && $wxpay['status'] == 0 && $wxpay['fee'] == $result['cash_fee']){
				
				$wxpay['status'] = 1;
				$wxpay['openid'] = $result['openid'];
				$wxpay['tid'] = 	$result['transaction_id'];
				$wxpay['s_time'] = date('Y-m-d H:i:s');
				M("wxpay")->save($wxpay);
				$u['uid'] = $wxpay['uid'];
				M('menber')->where($u)->setInc('jingbag',($wxpay['fee']/100));
			}
		}
	   exit;
    }
	
	
	protected function notify()
    {
        $config = array(
            'mch_id' => $this->mchid,
            'appid' => $this->appid,
            'key' => $this->apiKey,
        );
        $postStr = file_get_contents('php://input');
        $postObj = simplexml_load_string($postStr, 'SimpleXMLElement', LIBXML_NOCDATA);
        if ($postObj === false) {
            die('parse xml error');
        }
        if ($postObj->return_code != 'SUCCESS') {
            die($postObj->return_msg);
        }
        if ($postObj->result_code != 'SUCCESS') {
            die($postObj->err_code);
        }
        $arr = (array)$postObj;
        unset($arr['sign']);
        if ($this->getSign($arr, $config['key']) == $postObj->sign) {
            echo '<xml><return_code><![CDATA[SUCCESS]]></return_code><return_msg><![CDATA[OK]]></return_msg></xml>';
            return $arr;
        }
    }
    /**
     * 获取签名
     */
    protected  function getSign($params, $key)
    {
        ksort($params, SORT_STRING);
        $unSignParaString = $this->formatQueryParaMap($params, false);
        $signStr = strtoupper(md5($unSignParaString . "&key=" . $key));
        return $signStr;
    }
    protected  function formatQueryParaMap($paraMap, $urlEncode = false)
    {
        $buff = "";
        ksort($paraMap);
        foreach ($paraMap as $k => $v) {
            if (null != $v && "null" != $v) {
                if ($urlEncode) {
                    $v = urlencode($v);
                }
                $buff .= $k . "=" . $v . "&";
            }
        }
        $reqPar = '';
        if (strlen($buff) > 0) {
            $reqPar = substr($buff, 0, strlen($buff) - 1);
        }
        return $reqPar;
    }
}
