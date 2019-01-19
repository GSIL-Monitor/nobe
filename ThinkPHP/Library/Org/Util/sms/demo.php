<?php
require_once 'autoload.php';



$wlwx_config = array();
$wlwx_config['CUST_CODE'] = "830157";
$wlwx_config['CUST_PWD'] = "Y9RPD2I1RD";
$wlwx_config['SP_CODE'] = "";
$wlwx_config['NEED_REPORT'] = "yes";
$wlwx_config['UID'] = "";
$wlwx_config['SMS_HOST'] = 'http://123.58.255.70';
// retry times
$wlwx_config['RETRY_TIMES'] = 3;
// 短信
$wlwx_config['URI_SEND_COMMON_SMS'] = $wlwx_config['SMS_HOST'] . ":8860/sendSms";
$wlwx_config['URI_SEND_VARIANT_SMS'] = $wlwx_config['SMS_HOST'] . ":8860/sendVariantSms";
$wlwx_config['URI_GET_TOKEN'] = $wlwx_config['SMS_HOST'] . ":8860/getToken";
$wlwx_config['URI_GET_REPORT'] = $wlwx_config['SMS_HOST'] . ":8860/getReport";
$wlwx_config['URI_GET_MO'] = $wlwx_config['SMS_HOST'] . ":8860/getMO";
$wlwx_config['URI_QUERY_ACCOUNT'] = $wlwx_config['SMS_HOST'] . ":8860/QueryAccount";
$wlwx_config['URI_SMS_TEMPLATE'] = $wlwx_config['SMS_HOST'] . ":8860/requestSmsTemplate";
$wlwx_config['URI_SEND_MULTI_SMS'] = $wlwx_config['SMS_HOST'] . ":8861";
$GLOBALS['WLWX_CONFIG'] = $wlwx_config;

$smsOperator = new SmsOperator();
//开发者亦可在构造函数中填入配置项
//$smsOperator = new SmsOperator($cust_code, $cust_pwd, $sp_code, $need_report, $uid);

// 发送普通短信
echo "发送普通短信\n";
$data1['destMobiles'] = '15210787651';
$data1['content'] = '【未来无线】您的验证码为：170314。如非本人操作，请忽略。';
$result = $smsOperator->send_comSms($data1);
print_r($result);

exit;

//发送变量短信
echo "发送变量短信\n";
$params = array();
//VariantSms类用于封装发送号码以及参数变量
array_push($params,new VariantSms("15960XXX654",array("长乐","25")));
array_push($params,new VariantSms("18650XXX293",array("上杭","23")));
$data2['content'] = "\${mobile}用户您好，今天\${var1}的天气，晴，温度\${var2}度，事宜外出。";
$data2['params'] = $params;
$result = $smsOperator->send_varSms($data2);
print_r($result);

//获取状态报告
echo "获取状态报告\n";
$result = $smsOperator->get_report();
print_r($result);

//获取用户上行
echo "获取用户上行\n";
$result = $smsOperator->get_mo();
print_r($result);

//获取账户余额
echo "获取账户余额\n";
$result = $smsOperator->get_account();
print_r($result);

//创建短信模板
echo "创建短信模板\n";
$template = "([\S\s]*)用户您好，请记住您的验证码([\S\s]*)。";
$result = $smsOperator->send_template($template);
print_r($result);

//批量发送短信
$multiSmsOperator = new MultiSmsOperator();
//开发者亦可在构造函数中填入配置项，注本处的uid为用户账号
//$multiSmsOperator = new MultiSmsOperator($uid, $cust_pwd, $srcphone);
echo "批量发送短信\n";
$msg = array();
//MultiSms类用于封装发送号码以及内容
array_push($msg,new MultiSms("15960XXX654","【未来无线】您的验证码为：170314。如非本人操作，请忽略。"));
array_push($msg,new MultiSms("18650XXX293","【未来无线】您的验证码为：170315。如非本人操作，请忽略。"));
$data3['msg'] = $msg;
$result = $multiSmsOperator->send_multiSms($data3);
print_r($result);