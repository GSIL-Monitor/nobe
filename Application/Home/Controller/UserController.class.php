<?php

namespace Home\Controller;
use Think\Controller;
header('content-type:text/html;charset=utf-8');
class UserController extends CommonController {
	public function sale_buy() {
		if (!$_GET['id']) {
			echo "<script>alert('ID异常');";
			echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/sale_list';";
			echo "</script>";
			exit;
		} 
		$incomelog = M("incomelog") -> where(array('id' => $_GET['id'])) -> find();
		$this -> assign('res', $incomelog);
		$this -> display();
	} 

	public function buylog() {
		if (!$_GET['id']) {
			echo "<script>alert('ID异常');";
			echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/sale_list';";
			echo "</script>";
			exit;
		} 

		$incomelog = M("incomelog") -> where(array('id' => $_GET['id'])) -> find();
		$state = $incomelog['state'] + 1;
		M("incomelog") -> where(array('commitid' => $incomelog['commitid'])) -> save(array('state' => $state));
		if ($state == 6) {
			$buyer = M("incomelog") -> where(array('commitid' => $incomelog['commitid'], 'orderid' => 2)) -> find();
			$userinfo = M("menber") -> where(array('uid' => $buyer['userid'])) -> find();
			$left = bcadd($userinfo['chargebag'], $incomelog['income'], 2);
			M("menber") -> where(array('uid' => $buyer['userid'])) -> save(array('chargebag' => $left));
		} 
		echo "<script>alert('操作成功');";
		echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/sale_list';";
		echo "</script>";
		exit;
	} 
	// state 0 出售中   4 已购买
	public function buy() {
		if (!$_GET['id']) {
			echo "<script>alert('ID异常');";
			echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/sale_list';";
			echo "</script>";
			exit;
		} 
		$incomelog = M("incomelog") -> where(array('id' => $_GET['id'])) -> find();
		if (session('uid') == $incomelog['userid']) {
			echo "<script>alert('不能购买自己的挂买');";
			echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/sale_list';";
			echo "</script>";
			exit;
		} 
		$userinfo = M("menber") -> where(array('uid' => session('uid'))) -> find();
		$logid = $incomelog['id'];
		$commitid = date('YmdHis') . rand(100, 999);
		M("incomelog") -> where(array('id' => $_GET['id'])) -> save(array('commitid' => $commitid, 'state' => 4));
		unset($incomelog['id']);
		$incomelog['userid'] = session('uid');
		// $incomelog['tel'] =$userinfo['tel'];
		// $incomelog['weixin'] =$userinfo['weixin'];
		// $incomelog['username'] =$userinfo['name'];
		$incomelog['state'] = 4;
		$incomelog['orderid'] = 2;
		$incomelog['commitid'] = $commitid;
		M("incomelog") -> add($incomelog);

		echo "<script>alert('操作成功，请联系卖家');";
		echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/sale_list';";
		echo "</script>";
		exit;
	} 

	public function sale_list() {
		$data['type'] = 12;
		$data['state'] = 0;
		$data['commitid'] = 0;
		$list = M("incomelog") -> where($data) -> select(); 
		// 交易中数据
		$con['type'] = 12;
		$con['state'] = array('in', array(4, 5, 6));
		$con['userid'] = session('uid');
		$listing = M("incomelog") -> where($con) -> select();
		$this -> assign('listing', $listing);
		$this -> assign('res', $list);
		$this -> display();
	} 
	// 售卖
	public function my_sale() {
		if ($_POST['income']) {
			foreach ($_POST as $v) {
				if (!$v) {
					echo "<script>alert('请填写完整');";
					echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/my_sale';";
					echo "</script>";
					exit;
				} 
			} 
			$menber = M("menber");
			$userinfo = $menber -> where(array('uid' => session('uid'))) -> select();

			if ($userinfo[0]['chargebag'] < $_POST['income']) {
				echo "<script>alert('积分不足');";
				echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/my';";
				echo "</script>";
				exit;
			} 
			$left = bcsub($userinfo[0]['chargebag'], $_POST['income'], 2);
			$menber -> where(array('uid' => session('uid'))) -> save(array('chargebag' => $left));
			$data = $_POST;
			$data['type'] = 12;
			$data['state'] = 0;
			$data['reson'] = '积分挂买';
			$data['addymd'] = date('Y-m-d', time());
			$data['addtime'] = time();
			$data['orderid'] = 1;
			$data['userid'] = session('uid');
			$data['commitid'] = '';
			M("incomelog") -> add($data);
			echo "<script>alert('挂买成功');";
			echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/sale_list';";
			echo "</script>";
			exit;
		} 
		$this -> display();
	} 

	public function test() {
		$uid = 1;
		$str = $this -> get_category($uid, 1);
		$newstr = substr($str, 0, strlen($str)-1);
		$nextuser = M("menber") -> where(array('uid' => array('in', $newstr))) -> select();
		if ($nextuser[0]) {
			foreach ($nextuser as $key => $value) {
				if ($value['uid'] == $uid) {
					continue;
				} 
				if ($value['fuids']) {
					$newstrs = substr($value['fuids'], 0, strlen($value['fuids'])-1);
					$array = array_reverse(explode(',', $newstrs));
					foreach ($array as $k1 => $v1) {
						if ($v1 == $uid) {
							$temp[$k1][] = $value;
						} 
					} 
				} 
			} 
		} 

		print_r($temp);
		die;
		print_r($nextuser);
		die;
	} 
	// 获取指定分类的所有子分类 键为ID，值为分类名
	function getCateKv($categoryID) {
		// 初始化ID数组,赋值当前分类
		$array[] = M('cate') -> where("id={$categoryID}") -> getField("cateName");
		do {
			$ids = '';
			$where['pid'] = array('in', $categoryID);
			$cate = M('cate') -> where($where) -> select();
			echo M('cate') -> _sql();
			foreach ($cate as $k => $v) {
				$array[$v['id']] = $v['cateName'];
				$ids .= ',' . $v['id'];
			} 
			$ids = substr($ids, 1, strlen($ids));
			$categoryID = $ids;
		} while (!empty($cate));
		$ids = implode(',', $array); 
		// return $ids; //  返回字符串
		return $array; //返回数组
	} 

	function get_category($category_id , $level) {
		$category_ids = $category_id . ",";
		$child_category = M("menber") -> where(array('fuid' => $category_id)) -> select();
		$levels = $level + 1;
		// print_r($level);
		foreach($child_category as $key => $val) {
			$item[$val['uid']] = $child_category[$key];
			$category_ids .= $this -> get_category($val["uid"], $levels);
		} 
		return $category_ids;
	} 
	// 获取某个分类的所有子分类
	function getSubs($categorys, $catId = 0, $level = 1) {
		$subs = array();
		foreach($categorys as $item) {
			M("menber") -> where(array('uid' => 1)) -> find();
			if ($item['uid'] == $catId) {
				$item['level'] = $level;
				$subs[] = $item;
				$subs = array_merge($subs, getSubs($categorys, $item['fuid'], $level + 1));
			} 
		} 
		return $subs;
	} 

	public function reg() { // 注册下级
		if ($_POST['tel'] && $_POST['pwd']) {
			if (preg_match("/^1[34578]{1}\d{9}$/", $_POST['tel'])) {
			} else {
				echo "<script>alert('请用手机号码注册');";
				if ($_POST['num'] == 100) {
					echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/reg100';";
				} else {
					echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/reg200';";
				} 
				echo "</script>";
				exit;
			} 
			if ($_POST['pwd'] != $_POST['pwd11']) {
				echo "<script>alert('密码不一致');";
				if ($_POST['num'] == 100) {
					echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/reg100';";
				} else {
					echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/reg200';";
				} 
				echo "</script>";
				exit;
			} 
			$menber = M('menber'); 
			// 用户名
			$res_user = $menber -> where(array('tel' => $_POST['tel'])) -> select();
			if ($res_user[0]) {
				echo "<script>alert('用户电话已存在');";
				if ((int)$_POST['num'] == 100) {
					echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/reg100';";
				} else {
					echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/reg200';";
				} 
				echo "</script>";
				exit;
			} 
			// 金额
			/**
			 * $res_menber =$menber->where(array('uid'=>session('uid')))->select();
			 * $chargebag = bcsub($res_menber[0]['chargebag'],$_POST['num'],2);
			 * if($res_menber[0]['chargebag'] < $_POST['num']){
			 * echo "<script>alert('积分不足');";
			 * if((int)$_POST['num'] == 100){
			 * echo "window.location.href='".__ROOT__."/index.php/Home/User/reg100';";
			 * }else{
			 * echo "window.location.href='".__ROOT__."/index.php/Home/User/reg200';";
			 * }
			 * echo "</script>";
			 * exit;
			 * }
			 * $menber->where(array('uid'=>session('uid')))->save(array('chargebag'=>$chargebag));
			 */
			$data['name'] = $_POST['name'];
			$data['tel'] = $_POST['tel'];
			$data['pwd'] = $_POST['pwd'];
			$data['pwd2'] = $_POST['pwd2'];
			$data['type'] = 1;
			$data['fuid'] = session('uid');
			$data['addtime'] = time();
			$data['addymd'] = date('Y-m-d', time());
			$data['chargebag'] = 10 ;
			if ($_POST['num'] == 100) {
				$data['dongbag'] = 1 ;
			} else {
				$data['dongbag'] = 2 ;
			} 

			$res = $menber -> add($data);

			$income = M('incomelog');
			$data['type'] = 5;
			$data['state'] = 2;
			$data['reson'] = '注册下级';
			$data['addymd'] = date('Y-m-d', time());
			$data['addtime'] = time();
			$data['orderid'] = $res;
			$data['userid'] = session('uid');
			$data['income'] = $_POST['num'];
			if ($_POST['num'] > 0) {
				$income -> add($data);
			} 

					if ($res) {
				$fidUserinfo = $menber -> where(array('uid' => session('uid'))) -> select();
				$fuids = $fidUserinfo[0]['fuids'];
				$arrfuids = explode(",", substr($fuids, 0, -1));
				$holdnum = 10;
				if (count($arrfuids) > $holdnum) {
					$tmp = count($arrfuids) - $holdnum;

					for($ti = 0;$ti < $tmp;$ti++) {
						// echo  "##".$arrfuids[$ti];
						// echo "||".$ti;
						unset($arrfuids[$ti]);
					} 
				} 
				$fuids = implode(',', $arrfuids) . ',';
				if ($fuids) {
					$fuids = ',' . $fuids . $res . ',';
				} else {
					$fuids = ',' . $res . ',';
				} 
				$menber -> where(array('uid' => $res)) -> save(array('fuids' => $fuids)); 
			} 
			echo "<script>alert('用户" . $_POST['name'] . "注册成功');";
			if ((int)$_POST['num'] == 100) {
				echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/my';";
			} else {
				echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/my';";
			} 
			echo "</script>";
			exit;
		} 

		$this -> display();
	} 

	public function popularize() {
		$url = 'http://'.$_SERVER['HTTP_HOST'] . "/index.php/Home/Login/reg/fid/" . session('uid') . ".html";
		$this -> assign('uid', session('uid'));
		$this -> assign('url', $url);
		$this -> display();
	} 

	public function notice_detail() {
		$article = M('article') -> where(array('aid' => $_GET['id'])) -> find();
		$this -> assign('res', $article);
		$this -> display();
	} 

	public function notice() {
		$article = M('article') -> select();
		$this -> assign('res', $article);
		$this -> display();
	} 

	public function my() {
		$menber = M("menber");
		$userinfo = $menber -> where(array('uid' => session('uid'))) -> find();
		$orderlog = M("orderlog") -> where(array('userid' => session('uid'))) -> find();
		if ($orderlog['logid']) {
			if ($orderlog['states'] == 1) {
				$msg = "未发货";
			} else {
				$msg = "已发货";
			} 
		} else {
			$msg = "暂无信息";
		} 
		if ($userinfo['fuid']) {
			$fid = $menber -> where(array('uid' => $userinfo['fuid'])) -> find();
			$fuidname = $fid['name'];
		} else {
			$fuidname = "暂无";
		} 
		$zhiwei = M("member_type") -> where(array('id' => $userinfo['usertype'])) -> getField("typename");
		$this -> assign('zhiwei', $zhiwei);
		$this -> assign('fuidname', $fuidname);
		$this -> assign('msg', $msg);
		$this -> display();
	} 

	/**
	 * 积分充值
	 */
	public function recharge() {
		$money = $_GET['money'];
		date_default_timezone_set('Asia/Shanghai');
		header("Content-type: text/html; charset=utf-8");
		$pay_memberid = "10071"; //商户ID
		$pay_orderid = date("YmdHis") . rand(1000, 9999); //订单号
		$pay_amount = $money; //交易金额
		$pay_applydate = date("Y-m-d H:i:s"); //订单时间
		$pay_bankcode = "WXZF"; //银行编码
		$uid = session('uid');

		$order = M("incomelog");
		$data['state'] = 0;
		$data['type'] = 0;
		$data['reson'] = "充值";
		$data['addymd'] = date("Y-m-d H:i:s", time());
		$data['addtime'] = time();
		$data['userid'] = session('uid');
		$data['income'] = $money;
		$data['orderid'] = $pay_orderid;
		$data['cont'] = 'http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF'] . '?' . $_SERVER['QUERY_STRING'];
		$logid = $order -> add($data);

		$pay_notifyurl = "http://www.898tj.com/index.php/Home/Login/pay/token/admin123/id/$logid"; //服务端返回地址
		$pay_callbackurl = "http://www.898tj.com/index.php/Home/Login/login"; //页面跳转返回地址
		$Md5key = "4ql4b2k6y534476d3xjztd9t3k8avc"; //密钥
		$tjurl = "http://www.zhizhufu.com.cn/Pay_Index.html"; //网关提交地址
		$jsapi = array("pay_memberid" => $pay_memberid,
			"pay_orderid" => $pay_orderid,
			"pay_amount" => $pay_amount,
			"pay_applydate" => $pay_applydate,
			"pay_bankcode" => $pay_bankcode,
			"pay_notifyurl" => $pay_notifyurl,
			"pay_callbackurl" => $pay_callbackurl,
			);

		ksort($jsapi);
		$md5str = "";
		foreach ($jsapi as $key => $val) {
			$md5str = $md5str . $key . "=" . $val . "&";
		} 
		// echo($md5str . "key=" . $Md5key."<br>");
		$sign = strtoupper(md5($md5str . "key=" . $Md5key));
		$jsapi["pay_md5sign"] = $sign;
		$jsapi["pay_tongdao"] = 'Ucwxscan'; //通道
		$jsapi["pay_tradetype"] = 900021; //通道类型   900021 微信支付，900022 支付宝支付
		$jsapi["pay_productname"] = '会员服务'; //商品名称
		// print_r($jsapi);die;
		$data = http_build_query($jsapi);
		$options = array('http' => array('method' => 'POST',
				'header' => 'Content-type:application/x-www-form-urlencoded',
				'content' => $data,
				'timeout' => 15 * 60 // 超时时间（单位:s）
				)
			);
		$context = stream_context_create($options);
		$result = file_get_contents($tjurl, false, $context);
		$result = json_decode($result);

		$this -> assign("img", $result -> code_img_url);

		$this -> display();
	} 

	/**
	 * 个人资料
	 */
	public function my_data() {
		$this -> display();
	} 

	/**
	 * 完善资料
	 */
	public function complete() {
		if ($_POST['pwd'] && $_POST['pwd2']) {
			$data = $_POST;
			M("menber") -> where(array('uid' => session('uid'))) -> save($data);
			echo "<script>";
			echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/complete';";
			echo "</script>";
			exit;
		} 
		$this -> display();
	} 

	/**
	 * 退本 1收益 2充值 3静态提现  4动态体现  5 注册下级 6下单购买 7积分提现 8静态转账 9升级密天使奖励 10静态收益 11 动态收益
	 */
	public function width_draw() {
		$lilv = M("config") -> where(array('id' => 18)) -> find();
		$lilv = $lilv['value'];
		if ($_POST) {
			if ($_POST['num'] <= 0) {
				echo "<script>alert('请输入正确金额在');";
				echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/width_draw';";
				echo "</script>";
				exit;
			} 
			$menber = M('menber');
			$res_user = $menber -> where(array('uid' => session('uid'))) -> select();
			if ($res_user[0]['pwd2'] != $_POST['pwd2']) {
				echo "<script>alert('二级密码错误');";
				echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/width_draw';";
				echo "</script>";
				exit;
			} 

			if ($_POST['num'] < 100) {
				echo "<script>alert('提现额度小于100');";
				echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/width_draw';";
				echo "</script>";
				exit;
			} 

			if ($_POST['num'] > 200) {
				echo "<script>alert('提现额度大于200');";
				echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/width_draw';";
				echo "</script>";
				exit;
			} 

			$income = M('incomelog');
			$istoday = $income -> where(array('type' => 7, 'userid' => session('uid'), 'addymd' => date('Y-m-d', time()))) -> find();
			if ($istoday['userid']) {
				echo "<script>alert('每日提现允许一次');";
				echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/width_draw';";
				echo "</script>";
				exit;
			} 

			$left = bcsub($res_user[0]['chargebag'], $_POST['num'], 2);

			$lilcv = $lilv;
			$fei = bcmul($_POST['num'], $lilcv, 2);
			$left = bcsub($left, $fei, 2);
			if ($left > 0) {
				$re = $menber -> where(array('uid' => session('uid'))) -> save(array('chargebag' => $left));
				if ($re) {
					$income = M('incomelog');
					$data['type'] = 7;
					$data['state'] = 0;
					$data['reson'] = '积分提现';
					$data['addymd'] = date('Y-m-d', time());
					$data['addtime'] = time();
					$data['orderid'] = session('uid');
					$data['userid'] = session('uid');
					$data['income'] = $_POST['num'];
					$income -> add($data);
					$resreson = "积分提现" . $_POST['num'] . "元";
					echo "<script>alert('" . $resreson . "待管理员确认');";
					echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/my';";
					echo "</script>";
					exit;
				} 
			} else {
				echo "<script>alert('余额不足');";
				echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/my';";
				echo "</script>";
				exit;
			} 
		} 
		$this -> assign('lilv', $lilv);
		$this -> display();
	} 

	/**
	 * 静态   1收益 2充值 3静态提现  4动态体现  5 注册下级 6下单购买 7积分体现 8积分转账 9复投码转账 10分红收益 11 动态收益
	 */
	public function sy_jing() {
		$incomelog = M('incomelog');
		$con['userid'] = session('uid');
		$con['type'] = array('in', array(3, 5, 8, 9, 10, 12));
		$con['state'] = array('in', array(1, 2, 6));
		$res = $incomelog -> where($con) -> order(" id DESC ") -> limit(18) -> select();
		$this -> assign('res', $res);
		$this -> display();
	} 

	/**
	 * 动态  1收益 2充值 3静态提现  4动态体现  5 注册下级 6下单购买 7积分体现 8积分转账 9复投码转账 10静态收益 11 动态收益
	 */
	public function sy_dong() {
		$incomelog = M('incomelog');
		$con['userid'] = session('uid');
		// $con['type']  =array('in',array(4,9,11));
		$con['type'] = 11;

		$res = $incomelog -> where($con) -> order(" id DESC ") -> limit(18) -> select();
		$this -> assign('res', $res);
		$this -> display();
	} 

	public function futou() {
		if(IS_POST){
			//create order 
			$wxpay = M("wxpay");
			$d['uid'] = session('uid');
			$d['fee'] = I('post.price')*100;
			$d['pay_no'] = time().session('uid').mt_rand(1111,9999);
			if($wxpay->add($d)){
				session('payinfo',$d);
				redirect("/wxPay/wxpay.php");
			}
			exit;
		}
		$this -> assign('user', session('uid'));
		$this -> display();
	} 

	private function getflilv($count) {
		$configboj = M('config');
		if ($count > 1 && $count < 4) { // 1
			$lilv = $configboj -> where(array('id' => 3)) -> select();
			return $lilv[0]['value'];
		} elseif ($count > 3 && $count < 8) { // 2
			$lilv = $configboj -> where(array('id' => 4)) -> select();
			return $lilv[0]['value'];
		} elseif ($count > 7 && $count < 12) { // 3
			$lilv = $configboj -> where(array('id' => 5)) -> select();
			return $lilv[0]['value'];
		} elseif ($count > 11 && $count < 16) { // 4
			$lilv = $configboj -> where(array('id' => 6)) -> select();
			return $lilv[0]['value'];
		} elseif ($count > 15 && $count < 20) { // 5
			$lilv = $configboj -> where(array('id' => 7)) -> select();
			return $lilv[0]['value'];
		} elseif ($count > 20 && $count < 22) { // 6
			$lilv = $configboj -> where(array('id' => 8)) -> select();
			return $lilv[0]['value'];
		} else {
			return 0 ;
		} 
	} 

	public function suBuyBi() {
		$bi = 50;
		$userid = 28;
	} 

	public function isTiXian($userid, $num) {
		$config = M('config'); 
		// 是否最大金额
		$nummax = $config -> where(array('id' => 15)) -> select();
		if ($num < $nummax[0]['value']) {
			return "最低提现金额为" . $nummax[0]['value'];
		} 
		// 最大次数
		$timemax = $config -> where(array('id' => 16)) -> select();
		$nowday = date("Y-m-d", time());
		$cond['addymd'] = $nowday;
		$cond['userid'] = $userid;
		$cond['type'] = array('in', array(3, 4));
		$times = M('incomelog') -> where($cond) -> select();
		$last = count($times);
		if ($last > $timemax[0]['value']) {
			return "最大提次数为" . $timemax[0]['value'];
		} else {
			return '';
		} 
	} 
	/**
	 * 我的团队
	 */
	public function my_group() {
		$uid = session('uid');
		$str = $this -> get_category($uid, 1);
		$newstr = substr($str, 0, strlen($str)-1);
		$nextuser = M("menber") -> where(array('uid' => array('in', $newstr))) -> select();
		if ($nextuser[0]) {
			foreach ($nextuser as $key => $value) {
				if ($value['uid'] == $uid) {
					continue;
				} 
				if ($value['fuids']) {
					$newstrs = substr($value['fuids'], 0, strlen($value['fuids'])-1);
					$array = array_reverse(explode(',', $newstrs));
					foreach ($array as $k1 => $v1) {
						if ($v1 == $uid) {
							$temp[$k1][] = $value;
						} 
					} 
				} 
			} 
		} 

		$this -> assign('res', $temp);
		$this -> display();
	} 

	private function changeTimes($times) {
		if ($times == 1) {
			return "一";
		} elseif ($times == 2) {
			return "二";
		} elseif ($times == 3) {
			return "三";
		} elseif ($times == 4) {
			return "四";
		} elseif ($times == 5) {
			return "五";
		} elseif ($times == 6) {
			return "六";
		} 
	} 

	function getMenuTree($arrCat, $parent_id = 0, $level = 0) {
		static $arrTree = array(); //使用static代替global
		if (empty($arrCat)) return false;
		$level++;
		foreach($arrCat as $key => $value) {
			if ($value['parent_id' ] == $parent_id) {
				$value[ 'level'] = $level;
				$arrTree[] = $value;
				unset($arrCat[$key]); //注销当前节点数据，减少已无用的遍历
				getMenuTree($arrCat, $value[ 'id'], $level);
			} 
		} 

		return $arrTree;
	} 

	/**
	 * 复投互转
	 */
	public function transfer_futou() {
		if ($_POST) {
			if ($_POST['num'] <= 0) {
				echo "<script>alert('金额不正确');";
				echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/transfer_futou';";
				echo "</script>";
				exit;
			} 
			$menber = M('menber');
			$res_user = $menber -> where(array('uid' => session('uid'))) -> select();
			if ($res_user[0]['pwd2'] != $_POST['pwd2']) {
				echo "<script>alert('二级密码不正确');";
				echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/transfer_futou';";
				echo "</script>";
				exit;
			} 
			$res_user1 = $menber -> where(array('tel' => $_POST['tel'])) -> select();
			if ($res_user1[0]['name'] != $_POST['name']) {
				echo "<script>alert('账户不正确');";
				echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/transfer_futou';";
				echo "</script>";
				exit;
			} 
			if ($res_user[0]['mif'] < $_POST['num']) {
				echo "<script>alert('余额不足');";
				echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/transfer_futou';";
				echo "</script>";
				exit;
			} 
			if ($res_user[0]['tel'] == $_POST['tel']) {
				echo "<script>alert('自己不能给自己转账');";
				echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/transfer_futou';";
				echo "</script>";
				exit;
			} 
			// 处理自己
			$chargebagmy = bcsub($res_user[0]['mif'], $_POST['num'], 2);
			$menber -> where(array('uid' => session('uid'))) -> save(array('mif' => $chargebagmy));
			$income = M('incomelog');
			$logdata['type'] = 9 ;
			$logdata['state'] = 2 ;
			$logdata['reson'] = '复投码转账' ;
			$logdata['addymd'] = date('Y-m-d', time()) ;
			$logdata['addtime'] = time() ;
			$logdata['orderid'] = $res_user1[0]['uid'] ;
			$logdata['userid'] = session('uid');
			$logdata['income'] = $_POST['num'];
			$income -> add($logdata); 
			// 处理他人
			$chargebaghim = bcadd($res_user1[0]['mif'], $_POST['num'], 2);
			$menber -> where(array('uid' => $res_user1[0]['uid'])) -> save(array('mif' => $chargebaghim));

			$logdata['type'] = 9;
			$logdata['state'] = 1 ;
			$logdata['reson'] = '复投码转账' ;
			$logdata['addymd'] = date('Y-m-d', time()) ;
			$logdata['addtime'] = time();
			$logdata['orderid'] = session('uid');
			$logdata['userid'] = $res_user1[0]['uid'];
			$logdata['income'] = $_POST['num'];
			$income -> add($logdata);
			echo "<script>alert('转账成功');";
			echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/my';";
			echo "</script>";
			exit;
		} 
		$this -> display();
	} 

	/**
	 * 积分互转 1收益 2充值 3静态提现  4动态体现  5 注册下级 6下单购买 7积分体现 8积分转账 9复投码转账 10静态收益 11 动态收益
	 */
	public function transfer_jifen() {
		$lilv = M("config") -> where(array('id' => 19)) -> find();
		$lilv = $lilv['value'];
		if ($_POST) {
			if ($_POST['num'] <= 0) {
				echo "<script>alert('金额不正确');";
				echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/transfer_jifen';";
				echo "</script>";
				exit;
			} 
			$menber = M('menber');
			$res_user = $menber -> where(array('uid' => session('uid'))) -> select();
			if ($res_user[0]['pwd2'] != $_POST['pwd2']) {
				echo "<script>alert('二级密码不正确');";
				echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/transfer_jifen';";
				echo "</script>";
				exit;
			} 
			$res_user1 = $menber -> where(array('tel' => $_POST['tel'])) -> select();
			if ($res_user1[0]['name'] != $_POST['name']) {
				echo "<script>alert('账户名不正确');";
				echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/transfer_jifen';";
				echo "</script>";
				exit;
			} 

			$fei = bcmul($_POST['num'], $lilv, 2);
			$left = bcsub($res_user[0]['chargebag'], $fei, 2);

			if ($left < $_POST['num']) {
				echo "<script>alert('积分不足');";
				echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/transfer_jifen';";
				echo "</script>";
				exit;
			} 
			if ($res_user[0]['tel'] == $_POST['tel']) {
				echo "<script>alert('自己不能给自己转账');";
				echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/transfer_jifen';";
				echo "</script>";
				exit;
			} 
			// 处理自己
			$chargebagmy = bcsub($left, $_POST['num'], 2);
			$menber -> where(array('uid' => session('uid'))) -> save(array('chargebag' => $chargebagmy));
			$income = M('incomelog');
			$logdata['type'] = 8 ;
			$logdata['state'] = 2 ;
			$logdata['reson'] = '积分转账' ;
			$logdata['addymd'] = date('Y-m-d', time()) ;
			$logdata['addtime'] = time();
			$logdata['orderid'] = $res_user1[0]['uid'] ;
			$logdata['userid'] = session('uid');
			$logdata['income'] = bcadd($_POST['num'], $fei, 2);
			$income -> add($logdata); 
			// 处理他人
			$chargebaghim = bcadd($res_user1[0]['chargebag'], $_POST['num'], 2);
			$menber -> where(array('uid' => $res_user1[0]['uid'])) -> save(array('chargebag' => $chargebaghim));

			$logdata['type'] = 8 ;
			$logdata['state'] = 1 ;
			$logdata['reson'] = '积分转账' ;
			$logdata['addymd'] = date('Y-m-d', time()) ;
			$logdata['addtime'] = time() ;
			$logdata['orderid'] = session('uid');
			$logdata['userid'] = $res_user1[0]['uid'];
			$logdata['income'] = $_POST['num'];
			$income -> add($logdata);
			echo "<script>alert('转账成功');";
			echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/my';";
			echo "</script>";
			exit;
		} 
		$this -> assign('lilv', $lilv);
		$this -> display();
	} 

	private function savelog($data) {
		$incomelog = M('incomelog');
		return $incomelog -> add($data);
	} 

	public function payRecord() { // 充值记录
		$incomelog = M('incomelog');
		$condtion['userid'] = session('uid');
		$condtion['type'] = 2;
		$condtion['state'] = 1;
		$res = $incomelog -> order('id DESC') -> where($condtion) -> select();
		$this -> assign('res', $res);
		$this -> display();
	} 

	public function cancel() {
		$incomelog = M('incomelog');
		$condtion['uid'] = session('uid');
		$condtion['id'] = $_GET['id'];
		$res = $incomelog -> where($condtion) -> select();
		$income = $res[0]['income'];
		if ($income <= 0) {
			echo "<script>alert('取消失败');";
			echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/cashRecord';";
			echo "</script>";
			exit;
		} 
		$menber = M('menber');
		$useinfo = $menber -> where(array('uid' => session('uid'))) -> select();
		// $res_usermoney = $useinfo[0]['incomebag']+$income;
		$res_usermoney = bcadd($useinfo[0]['incomebag'], $income, 2);
		$menber -> where(array('uid' => session('uid'))) -> save(array('incomebag' => $res_usermoney));
		$incomelog -> where(array('id' => $_GET['id'])) -> save(array('state' => 3));
		echo "<script>alert('操作成功');";
		echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/cashRecord';";
		echo "</script>";
		exit;
	} 

	public function cashRecord() { // 提现记录
		$incomelog = M('incomelog');
		$condtion['userid'] = session('uid');
		$condtion['type'] = 3;
		// $condtion['state']=2;
		$res = $incomelog -> order('id DESC') -> where($condtion) -> select();
		$this -> assign('res', $res);
		$this -> display();
	} 

	public function cashDetail() { // 资金明细
		$incomelog = M('incomelog');
		$condtion['userid'] = session('uid');
		$condtion['type'] = array('gt', 0);
		$res = $incomelog -> order('id DESC') -> where($condtion) -> select();
		$this -> assign('res', $res);
		$this -> display();
	} 

	public function switchMoney() { // 钱包互转
		if ($_POST['chargebag']) { // 处理充值钱包转入到收益钱包
			if ($_POST['chargebag'] <= 0) {
				echo "<script>alert('请输入正确金额');";
				echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/switchMoney';";
				echo "</script>";
				exit;
			} 
			// 处理充值钱包转入到收益钱包
			$menber = M('menber');
			$useinfo = $menber -> where(array('uid' => session('uid'))) -> select();
			if ($useinfo[0]['chargebag'] > $_POST['chargebag']) {
				// $chargebag =$useinfo[0]['chargebag']-$_POST['chargebag'];
				$chargebag = bcsub($useinfo[0]['chargebag'], $_POST['chargebag'], 2);
				$data['chargebag'] = $chargebag;
				// $incomebag =$useinfo[0]['incomebag']+$_POST['chargebag'];
				$incomebag = bcadd($useinfo[0]['incomebag'], $_POST['chargebag'], 2);
				$data['incomebag'] = $incomebag;
				$menber -> where(array('uid' => session('uid'))) -> save($data);
				echo "<script>alert('转入成功');";
				echo "window.location.href='" . __ROOT__ . "/index.php/Home/Index/index';";
				echo "</script>";
				exit;
			} else {
				echo "<script>alert('账户余额不足');";
				echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/switchMoney';";
				echo "</script>";
				exit;
			} 
		} 
		// 收益钱包转入到充值钱包
		if ($_POST['incomebag']) {
			if ($_POST['incomebag'] <= 0) {
				echo "<script>alert('请输入正确金额');";
				echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/switchMoney';";
				echo "</script>";
				exit;
			} 
			// 处理充值钱包转入到收益钱包
			$menber = M('menber');
			$useinfo = $menber -> where(array('uid' => session('uid'))) -> select();
			if ($useinfo[0]['incomebag'] > $_POST['incomebag']) {
				// $chargebag =$useinfo[0]['chargebag']+$_POST['incomebag'];
				$chargebag = bcadd($useinfo[0]['chargebag'], $_POST['incomebag'], 2);
				$data['chargebag'] = $chargebag;
				// $incomebag =$useinfo[0]['incomebag']-$_POST['incomebag'];
				$incomebag = bcsub($useinfo[0]['incomebag'], $_POST['incomebag'], 2);
				$data['incomebag'] = $incomebag;
				$menber -> where(array('uid' => session('uid'))) -> save($data);
				echo "<script>alert('转入成功');";
				echo "window.location.href='" . __ROOT__ . "/index.php/Home/Index/index';";
				echo "</script>";
				exit;
			} else {
				echo "<script>alert('账户余额不足');";
				echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/switchMoney';";
				echo "</script>";
				exit;
			} 
		} 
		$this -> display();
	} 

	public function transfer() {
		$type = $_GET['type'];
		if ($type == 1) {
			$title = "升级密天使奖励";
			$action = "transfers_dong";
		} else {
			$title = "静态转账";
			$action = "transfers_jing";
			$type = 2;
		} 
		$this -> assign('title', $title);
		$this -> assign('type', $type);
		$this -> assign('action', $action);
		$this -> display();
	} 

	public function transferto() {
		$type = $_GET['type'];
		$menber = M('menber');
		if ($_POST['num'] > 0) {
			$userinfo = $menber -> where(array('uid' => session('uid'))) -> select();
			if ($_POST['pwd2'] != $userinfo[0]['pwd2']) {
				echo "<script>alert('二级密码错误');";
				echo "</script>";
				$this -> display();
				exit();
			} 

			if ($type == 1) { // 动态钱包
				if ($_POST['num'] > $userinfo[0]['dongbag']) {
					echo "<script>alert('动态钱包余额不足');";
					echo "</script>";
					$this -> display();
					exit();
				} 

				$left = bcsub($userinfo[0]['dongbag'] , $_POST['num'], 2);
				$menber -> where(array('uid' => session('uid'))) -> save(array('dongbag' => $left));
			} else {
				if ($_POST['num'] > $userinfo[0]['jingbag']) {
					echo "<script>alert('静态钱包余额不足');";
					echo "</script>";
					$this -> display();
					exit();
				} 
				$left = bcsub($userinfo[0]['jingbag'] , $_POST['num'], 2);
				$menber -> where(array('uid' => session('uid'))) -> save(array('jingbag' => $left));
			} 

			$dongbug = bcadd($userinfo[0]['chargebag'] , $_POST['num'], 2);
			$menber -> where(array('uid' => session('uid'))) -> save(array('chargebag' => $dongbug));
			echo "<script>alert('转入成功');";
			echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/my';";
			echo "</script>";
			exit;
		} 
		if ($type == 1) {
			$title = "动态钱包 转 充值钱包";
		} else {
			$title = "静态钱包 转 充值钱包";
			$type = 2;
		} 
		$this -> assign('title', $title);
		$this -> assign('type', $type);
		$this -> display();
	} 

	public function touch() {
		$type = isset($_GET['type']) ? $_GET['type'] : 1 ;
		if ($type == 1) {
			$filename = "kefu.jpg";
			$msg = "联系客服";
		} else {
			$msg = "联系客服";
			$filename = "kefu.jpg";
		} 
		$this -> assign('msg', $msg);
		$this -> assign('filename', $filename);
		$this -> display();
	} 

	public function inputnum() {
		if ($_POST['num'] > 0) {
			echo "<script>";
			echo "window.location.href='" . __ROOT__ . "/index.php/Home/Pay/getQrCode/money/" . $_POST['num'] . "';";
			echo "</script>";
			exit;
		} 
		$this -> display();
	} 

	public function inputzhifu() {
		echo "<script>alert('支付宝暂未开通');";
		echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/my';";
		echo "</script>";
		exit;

		if ($_POST['num'] > 0) {
			echo "<script>";
			echo "window.location.href='" . __ROOT__ . "/index.php/Home/User/recharge/money/" . $_POST['num'] . "';";
			echo "</script>";
			exit;
		} 
		$this -> display();
	} 
} 
