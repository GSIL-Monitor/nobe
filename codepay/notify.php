<?php
//error_reporting(E_ALL & ~ E_NOTICE); //过滤脚本错误

//ini_set("display_errors", "On");  //显示脚本错误提示
//error_reporting(E_ALL | E_STRICT); //开启全部脚本错误提示
/**
 * 功能：码支付服务器异步通知页面 (建议放置外网)
 * 版本：1.0
 * 日期：2016-12-23
 * 说明：
 * 以下代码只是为了方便商户测试而提供的样例代码，商户可以根据自己网站的需要，按照技术文档编写,并非一定要使用该代码。
 * 该代码仅供学习和研究码支付接口使用，只是提供一个参考。
 *************************业务处理调试说明*************************
 * 1：该页面不建议在本机电脑测试，请到服务器上做测试。请确保外部可以访问该页面。
 * 2：您可以使用我们的软件端中【手动充值】进行调试。
 * 3：该页面调试工具请使用写文本函数logResult，该函数已被默认开启，见codepay_notify_class.php中的函数verifyNotify
 * 4：创建该页面文件时，请留心该页面文件中无任何HTML代码及空格。
 * 5：如果没有收到该页面返回的 ok或者success 信息，码支付会在24小时内按一定的时间策略重发通知
 *************************注意*****************
 *如果您在接口集成过程中遇到问题，可以按照下面的途径来解决
 *1、开发文档中心（https://codepay.fateqq.com/apiword/）
 *2、商户帮助中心（https://codepay.fateqq.com/help/）
 *3、联系客服（https://codepay.fateqq.com/msg.html）
 */
require_once("codepay_config.php"); //导入配置文件
require_once("includes/MysqliDb.class.php");//导入mysqli连接
require_once("includes/M.class.php");//导入mysqli操作类

function createLinkstring($data){
    $sign='';
    foreach ($data AS $key => $val) {
        if ($sign) $sign .= '&'; //第一个字符串签名不加& 其他加&连接起来参数
        $sign .= "$key=$val"; //拼接为url参数形式
    }
}
/**
 * 业务处理演示
 * @param $data 接收到的POST参数
 * @return string 返回处理的结果
 */
function DemoHandle($data)
{ //业务处理例子 返回一些字符串
    $pay_id = $data['pay_id']; //需要充值的ID 或订单号 或用户名
    $money = (float)$data['money']; //实际付款金额
    $price = (float)$data['price']; //订单的原价
    $type = (int)$data['type']; //支付方式
    $pay_no = $data['pay_no']; //支付流水号
    $param = $data['param']; //自定义参数 原封返回 您创建订单提交的自定义参数
    $pay_time = (int)$data['pay_time']; //付款时间戳
    $pay_tag = $data['tag']; //支付备注 仅支付宝才有 其他支付方式全为0或空
    $status = 2; //业务处理状态 这里就全设置为2  如有必要区分是否业务同时处理了可以处理完再更新该字段为其他值
    $creat_time = time(); //创建数据的时间戳


    if ($money <= 0 || empty($pay_id) || $pay_time <= 0 || empty($pay_no)) {
        return '缺少必要的一些参数'; //测试数据中 唯一标识必须包含这些
    }


    //实例化mysqli 操作库 需在php.ini启用mysqli 启用方法：删除extension=php_mysqli.dll前面的 ; (分号)重启web服务器
    //MYSQL用户需要拥有INSERT update权限
    $m = new M();

    //以下参数为不小心删除了导致无法执行做准备 没有太多实际意义 就是些初始值

    
    if (!defined('DB_AUTOCOMMIT')) defined('DB_AUTOCOMMIT', false); //默认使用事物 回滚
    if (!defined('DEBUG')) defined('DEBUG', false); //默认启用调试模式 但这里如果读不到就不启用了

    //初始化结束

    /**
     * 检测订单是否已经处理
     *
     * 以下代码需要安装我们的测试数据才可正常运行。 仅是个参考 请根据需求自行开发
     * 如有开发经验可参考我们的API自行编写否则建议安装测试数据来修改
     * --------------------------------------
     * ★★★ 以插入方式判断订单是否存在 默认只数据库引擎为:InnoDB才会补单 优点：简单高效 兼容性强。
     * ---------------------------------------
     * 开启处理业务失败补单方法：
     * 默认已经开启但数据库引擎需要为:InnoDB 否则业务失败不会再第2次执行
     * 打开codepay_config.php配置文件搜索DB_AUTOCOMMIT修改为define('DB_AUTOCOMMIT', false);
     *
     * 不使用InnoDB引擎也不会影响使用。业务这步调试好后不成功几率几乎不可能出现,除非你的SQL存在问题。
     *----------------------------------------
     *
     * ★★ 以订单状态标识判断是否已经处理 这是最常用的方式。
     * 但步骤繁多(需要考虑脏读的可能) 为了新手易懂下面使用插入方式
     *---------------------------------------
     *---------------------------------------
     * 我不想这样处理业务有其他方式吗？
     * 有的,这只是个示范
     */

    $m->db->autocommit(DB_AUTOCOMMIT);//默认不自动提交 即事物开启 只针对InnoDB引擎有效

    /**
     * 插入到用户付款记录默认codepay_order表使用了2种唯一索引来区分是否已经存在。确保业务只执行一次
     * 以下为作为识别是否已经执行过此笔订单 建议保留 否则您必须确保业务已经处理
     */
    $insertSQL = "INSERT INTO `" . DB_PREFIX . "codepay_order` (`pay_id`, `money`, `price`, `type`, `pay_no`, `param`, `pay_time`, `pay_tag`, `status`, `creat_time`)values(?,?,?,?,?,?,?,?,?,?)";
    $stmt = $m->prepare($insertSQL);//预编译SQL语句
    if (!$stmt) {
        return "数据表:" . DB_PREFIX . "codepay_order  不存在 可能需要重新安装";
    }
    $stmt->bind_param('sddissisii', $pay_id, $money, $price, $type, $pay_no, $param, $pay_time, $pay_tag, $status, $creat_time); //防止SQL注入
    $rs = $stmt->execute(); //执行SQL
	
    if ($rs && $stmt->affected_rows >= 1) { //插入成功 是首次通知 可以执行业务
        mysqli_stmt_close($stmt); //关闭上次的预编译
          $stmt = $m->prepare("update " . DB_PREFIX . "menber set jingbag=jingbag+{$money} where uid=?");
          $stmt->bind_param('s', $pay_id);  //$pay_id 为您传递的参数 可以是用户ID 用户名 订单号。
		 
          $rs=$stmt->execute();
		  mysqli_stmt_close($stmt); //关闭上次的预编译
		  
		  $insertSQL1 = "INSERT INTO `" . DB_PREFIX . "incomelog` (`type`, `state`, `reson`, `addymd`, `addtime`, `orderid`, `userid`, `income`)values(?,?,?,?,?,?,?,?)";
            
		  $stmt = $m->prepare($insertSQL1);//预编译SQL语句
		 
          if (!$stmt) {
          return "数据表:" . DB_PREFIX . "incomelog  不存在 可能需要重新安装";
         }
		  $type="2";
		  $state="1";
		  $reson="在线充值";
		  $addymd=date('Y-m-d', time());
		  $addtime=time();
		  $orderid="";
		  
         $stmt->bind_param('ssssssss', $type,$state,$reson,$addymd,$addtime,$orderid,$pay_id,$money); //防止SQL注入
         
		 $rs = $stmt->execute(); //执行SQL
	    
		 
        if ($stmt->error != '') { //捕获错误 这一般是数据表不存在造成
            $result = sprintf("数据表存在问题 ：%s SQL: %s 参数：%s ", $stmt->error, $sql, createLinkstring($data));
            mysqli_stmt_close($stmt); //关闭预编译
            $m->rollback();//回滚
            return $result;
        }

        //$stmt->bind_param('s', $pay_id); //绑定参数 防止注入
        //$rs = $stmt->execute(); //执行SQL语句

        if ($rs && $stmt->affected_rows >= 1) {

            if (!DB_AUTOCOMMIT) $m->db->commit(); //提交事物 保存数据
            mysqli_stmt_close($stmt); //关闭预编译


            return 'ok'; //业务处理完成 。

        } else { //如果下次还要处理则应该开启事物 数据库引擎为InnoDB 不支持事物该笔订单是无法再执行到业务处理这个步骤除非是使用订单状态标识区分
            $error_msg = $stmt->error;
            if ($error_msg == '' && $stmt->affected_rows <= 0) {
                $error_msg = '该用户可能不存在 请核对 如果默认的演示只存在admin用户 需要你更改codepay_config.php 最下面3个参数为你的用户表信息';
            }
            $result = sprintf("业务处理失败了 ：%s SQL: %s 参数：%s ", $error_msg, $sql, createLinkstring($data));
            $m->rollback();//回滚
        }

    } else if ($stmt->errno == 1062) {

        return 'success';
        //已经存在 表示已经执行过 直接返回ok或success 不要再通知了.
        //如果不支持事物 就算之前执行失败了也是直接返回成功。

    } else {
        $m->rollback();//错误回滚
        if ($stmt->errno == 1146) { //不存在测试数据表
            $result = '您还未安装测试数据 无法使用业务处理示范'; //需在网页执行 install.php 安装测试数据 如访问：http://您的网站/codepay/install.php
        } else {
            $result = sprintf("比较严重的错误必须处理 ：%s SQL: %s 参数：%s \r\nMYSQL信息：%s", $stmt->error, $insertSQL, createLinkstring($data), createLinkstring($stmt));
        }
    }
    mysqli_stmt_close($stmt); //关闭预编译
    return $result;
}


$codepay_key = $codepay_config['key']; //这是您的密钥

$isPost = true; //默认为POST传入

if (empty($_POST)) { //如果GET访问
    $_POST = $_GET;  //POST访问 为服务器或软件异步通知  不需要返回HTML
    $isPost = false; //标记为GET访问  需要返回HTML给用户
}
ksort($_POST); //排序post参数
reset($_POST); //内部指针指向数组中的第一个元素

$sign = ''; //加密字符串初始化

foreach ($_POST AS $key => $val) {
    if ($val == '' || $key == 'sign') continue; //跳过这些不签名
    if ($sign) $sign .= '&'; //第一个字符串签名不加& 其他加&连接起来参数
    $sign .= "$key=$val"; //拼接为url参数形式
}
$pay_id = $_POST['pay_id']; //需要充值的ID 或订单号 或用户名
$money = (float)$_POST['money']; //实际付款金额
$price = (float)$_POST['price']; //订单的原价
$param = $_POST['param']; //自定义参数
$type = (int)$_POST['type']; //支付方式
$pay_no = $_POST['pay_no'];//流水号
if (!$_POST['pay_no'] || md5($sign . $codepay_key) != $_POST['sign']) { //不合法的数据
    if ($isPost) exit('fail');  //返回失败 继续补单
    $result = '支付失败';
    $pay_id = "支付失败";
    $pay_no = "支付失败";
    if ($type < 1) $type = 1;
} else { //合法的数据
    //业务处理

    $result = DemoHandle($_POST); //调用示例业务代码 处理业务获得返回值


    if ($result == 'ok' || $result == 'success') { //返回的是业务处理完成

        if (!DEBUG) ob_clean(); //如果非调试模式 清除之前残留的东西直接打印成功
        if ($isPost) exit($result); //服务器访问 业务处理完成 下面不执行了
        $result = '支付成功';
    } else {
        $error_msg = defined('DEBUG') && DEBUG ? $result : 'no'; //调试模式显示 否则输出no
        if ($isPost) exit($error_msg);  //服务器访问 返回给服务器
        $result = '支付失败';
    }

    $return_url = $_SERVER["SERVER_PORT"] == '80' ? '/' : '//' . $_SERVER['SERVER_NAME'];


    //以下为GET访问 返回HTML结果给用户

}

if ((int)$codepay_config['go_time'] < 1) $codepay_config['go_time'] = 3;

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
        <span class="ico_log ico-<?php echo (int)$type ?>"></span>
    </h1>

    <div class="mod-ct">
        <div class="order">
        </div>
        <div class="amount" id="money">￥<?php echo $money; ?></div>
        <h1 class="text-center text-<?php echo($result != '支付成功' ? 'fail' : 'success'); ?>"><strong><i
                    class="fa fa-check fa-lg"></i> <?php echo $result; ?></strong></h1>
        <?php echo($error_msg ? "以下错误信息关闭调试模式可隐藏：<div class='error text-left'>{$error_msg}</div>" : ''); ?>
        <div class="detail detail-open" id="orderDetail" style="display: block;">
            <dl class="detail-ct" id="desc">
                <dt>金额</dt>
                <dd><?php echo $money ?></dd>
                <dt>商户订单：</dt>
                <dd><?php echo htmlentities($pay_id) ?></dd>
                <dt>流水号：</dt>
                <dd><?php echo htmlentities($pay_no) ?></dd>
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
        <?php if($codepay_config['go_url']){
        ?>

        window.location.href = '<?php echo $codepay_config['go_url']?>'; //跳转

        <?php
        }?>

    }, <?php echo((int)$codepay_config['go_time'] * 1000)?>);//默认3秒后跳转
</script>
</body>
</html>



