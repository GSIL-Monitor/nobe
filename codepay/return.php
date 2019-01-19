<?php
/* *
 * 功能：码支付客服端同步通知页面(可不处理任何业务 为辅助业务实现)
 * 版本：1.0
 * 日期：2016-12-23
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 * 该代码仅供学习和研究码支付接口使用，只是提供一个参考。
 *************************页面功能说明*************************
 * 支付成功后客户默认会跳转到该页面 该页面可做业务 也可不做处理 为了示范我们做了业务仅供参考。
 *
 * 处理业务您需要考虑如下问题如无法解决不建议您在此页面做业务处理：
 * 1：必须要区分是否已经执行成功。不要与异步重复处理
 * 2：需要考虑并发导致数据脏读的可能。

 * 什么时候会跳转？
 * 只要检测到付款成功就会跳转,同步跟异步是并发进行。
 *
 * 以下为不跳转的可能情况：
 * 用户关闭页面：不跳转
 * 未注册通知：不跳转 (默认注册了通知 如果修改了前端需保留那部分功能)
 * 软件版未监听：不跳转 (认证版不用关心)
 *
 * 此页面不处理业务有什么影响？
 * 答：充值 购买之类的没任何影响，对于必须要付款后立即展示用户的一些卡券之类的有影响。
 *
 *
 */
require_once("codepay_config.php"); //导入配置文件
require_once("includes/MysqliDb.class.php");//导入mysqli连接
require_once("includes/M.class.php");//导入mysqli操作类
require_once("lib/codepay_notify.class.php"); //导入通知类


//计算得出通知验证结果
$codepayNotify = new CodepayNotify($codepay_config);
$verify_result = $codepayNotify->verifyAll(); //这里验证的是全部参数 这样软件端也能调试

if ($verify_result) { //验证成功
    /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
    //请在这里加上商户的业务逻辑程序代

    //——请根据您的业务逻辑来编写程序（以下代码仅作参考）——
    //获取码支付的通知返回参数，可参考技术文档中异步通知参数列表
    $result = '充值成功';
    //下面的代码我们注释了直接显示的成功  因为 同步跟异步的业务部分需要相同
    // 否则这边执行了但没更改业务 会导致异步那边无法执行业务

//    $result = DemoHandle($_GET); //调用示例业务代码 处理业务获得返回值 传递的参数为post或get参数
//
//    if ($result == 'ok' || $result == 'success') { //如果返回的是业务处理完成
//
//        $result = '充值成功';
//
//    } else {
//        //下面是存在错误 方便调试
//        //logResult($result); //错误写入到日志文本中 用于追查问题
//        $error_msg = defined('DEBUG') && DEBUG ? $result : ''; //正式环境 直接打印no 不返回任何错误信息
//        $result = '充值失败';
//    }


//——请根据您的业务逻辑来编写程序（以上代码仅作参考）——
/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

} else {  //验证失败
    $result = "充值失败";
    $error_msg = defined('DEBUG') && DEBUG ? "签名验证失败了" : '';
    //调试用，写文本函数记录程序运行情况是否正常
    //logResult("验证失败了");
}

$return_url = "/index.php/Home/User/my.html";
?>
<!DOCTYPE html>
<html>
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8">
    <meta http-equiv="Content-Language" content="zh-cn">
    <meta name="apple-mobile-web-app-capable" content="no"/>
    <meta name="apple-touch-fullscreen" content="yes"/>
    <meta name="format-detection" content="telephone=no,email=no"/>
    <meta name="apple-mobile-web-app-status-bar-style" content="white">
    <meta name="viewport"
          content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
    <title>支付详情</title>
    <link href="css/wechat_pay.css" rel="stylesheet" media="screen">
    <link rel="stylesheet" type="text/css" media="screen" href="css/font-awesome.min.css">
    <style>
        .text-success {
            color: #468847;
            font-size: 2.33333333em;
        }

        .text-fail {
            color: #ff0c13;
            font-size: 2.33333333em;
        }

        .text-center {
            text-align: center;
        }

        .text-left {
            text-align: left;
        }

        .error {

            display: block;
            padding: 9.5px;
            margin: 0 0 10px;
            font-size: 13px;
            line-height: 1.42857143;
            color: #333;
            word-break: break-all;
            word-wrap: break-word;
            background-color: #f5f5f5;
            border: 1px solid #ccc;
            border-radius: 4px;

        }
    </style>
</head>

<body>
<div class="body">
    <h1 class="mod-title">
        <span class="ico_log ico-<?php echo (int)$_GET['type'] ?>"></span>
    </h1>

    <div class="mod-ct">
        <div class="order">
        </div>
        <div class="amount" id="money">￥<?php echo (float)$_GET["money"]; ?></div>
        <h1 class="text-center text-<?php echo($result != '充值成功' ? 'fail' : 'success'); ?>"><strong><i
                    class="fa fa-check fa-lg"></i> <?php echo $result; ?></strong></h1>
        <?php echo($error_msg ? "以下错误信息关闭调试模式可隐藏：<div class='error text-left'>{$error_msg}</div>" : ''); ?>
        <div class="detail detail-open" id="orderDetail" style="display: block;">
            <dl class="detail-ct" id="desc">
                <dt>金额</dt>
                <dd><?php echo (float)$_GET["money"] ?></dd>
                <dt>商户订单：</dt>
                <dd><?php echo htmlentities($_GET["pay_id"]) ?></dd>
                <dt>流水号：</dt>
                <dd><?php echo htmlentities($_GET["pay_no"]) ?></dd>
                <dt>付款时间：</dt>
                <dd><?php echo date("Y-m-d H:i:s", (int)$_GET["pay_time"]) ?></dd>
                <dt>状态</dt>
                <dd><?php echo $result; ?></dd>
            </dl>


        </div>

        <div class="tip-text">
        </div>


    </div>
    <div class="foot">
        <div class="inner">
            <p>如未到账请联系我们</p>
        </div>
    </div>

</div>
<div class="copyRight">
    <p>支付合作：<a href="http://codepay.fateqq.com/" target="_blank">码支付</a></p>
</div>
<script>
    setTimeout(function () {
        //这里可以写一些后续的业务
        window.location.href = '<?php echo $return_url?>';
    }, 3000);//3秒后跳转
</script>
</body>
</html>



