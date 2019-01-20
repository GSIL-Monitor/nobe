<?php
namespace Home\Controller;

use Think\Controller;

header('content-type:text/html;charset=utf-8');
class IndexController extends CommonController
{
    // public function _initialize(){
    // if($_GET['openid']){
    // $menber =M('menber');
    // $user=$menber->where(array('openid'=>$_GET['openid']))->select();
    // S('name',$user[0]['name']);
    // S('userid',$user[0]['id']);
    // S('nickname',$user[0]['nickname']);
    // }
    // }
    // 主页
    public function index()
    {
        // $article = M('article');
        // $intro = $article -> order('aid DESC') -> where(array('type' => 1)) -> select();
        // $this -> assign('intro', $intro[0]);
        // $this -> display();
        $article = M('product');
        $intro   = $article->order('id DESC')->where(array('type' => 1))->select();
        $this->assign('intro', $intro);
        $this->display();
    }
    // 列表
    public function shop()
    {
        $article = M('product');
        $intro   = $article->order('id DESC')->where(array('type' => 1))->select();
        $this->assign('intro', $intro);
        $this->display();
    }
    // 详情
    public function detail()
    {
        $article = M('product');
        $intro   = $article->where(array('id' => $_GET['id']))->find();
        if ($_POST['num']) {
            $users = M("menber")->where(array('uid' => session('uid')))->find();

            $pro = M("product")->where(array('id' => $_GET['id']))->find();

            if ($_GET['paytype'] == "1" && $users['jingbag'] < $pro['price']) {
                //购物钱包
                echo "<script>alert('购物钱包余额不足!即将跳转至充值页面。');";
                echo "window.location.href='" . __ROOT__ . "/index.php/Home/user/futou.html';";
                echo "</script>";
                exit;
            } elseif ($_GET['paytype'] == "2" && $users['chargebag'] < $pro['price']) {
                //积分钱包
                echo "<script>alert('积分钱包余额不足，请使用余额购买！');";
                echo "window.location.href='" . __ROOT__ . "/index.php/Home/Index/detail/id/" . $_GET['id'] . "';";
                echo "</script>";
                exit;
            }

            $order['userid']       = session('uid');
            $order['productid']    = $pro['id'];
            $order['productname']  = $pro['name'];
            $order['productmoney'] = $pro['price'];
            $order['states']       = 1;
            $order['orderid']      = $_POST['num'];
            $order['addtime']      = time();
            $order['addymd']       = date("Y-m-d", time());
            $order['num']          = $_POST['num'];
            $order['prices']       = $pro['price'];
            $order['totals']       = $pro['price'];
            $order['option']       = $_POST['addr'] . ',' . $_POST['tel'] . ',' . $_POST['name'] . ',' . $_POST['youbian'];
            if ($_POST['num'] > 0) {
                M("orderlog")->add($order);
            }

            $income          = M('incomelog');
            $data['type']    = 6;
            $data['state']   = 2;
            $data['reson']   = '下单购买';
            $data['addymd']  = date('Y-m-d', time());
            $data['addtime'] = time();
            $data['orderid'] = session('uid');
            $data['userid']  = session('uid');
            $data['income']  = $pro['price'];
            if ($pro['price'] > 0) {
                $income->add($data);
            }
            $menber   = M("menber");
	        $userinfo = $menber->where(array('uid' => session('uid')))->find();
	        // 查看该产品是否是升级产品，该用户是否升级(密友升级为密天使)
	        // if ($users['usertype'] == 1 && $intro['uppro'] == 1) {
	        //     if ($_GET['paytype'] == "1") {
	        //         $jingbag = bcsub($userinfo['jingbag'], $pro['price'], 2);
	        //         $menber->where(array('uid' => session('uid')))->save(array('jingbag' => $jingbag, 'usertype' => 2));
	        //     } else {
	        //         $chargebag = bcsub($userinfo['chargebag'], $pro['price'], 2);
	        //         $menber->where(array('uid' => session('uid')))->save(array('chargebag' => $chargebag, 'usertype' => 2));
	        //     }

	        // } else {
	            if ($_GET['paytype'] == "1") {
	                $jingbag = bcsub($userinfo['jingbag'], $pro['price'], 2);
	                $menber->where(array('uid' => session('uid')))->save(array('jingbag' => $jingbag));
	            } else {
	                $chargebag = bcsub($userinfo['chargebag'], $pro['price'], 2);
	                $menber->where(array('uid' => session('uid')))->save(array('chargebag' => $chargebag));
	            }

	        // }
            //添加分销
            $this->addDis($intro['uppro']);

            echo "<script>alert('购买成功');";
            echo "window.location.href='" . __ROOT__ . "/index.php/Home/Index/index';";
            echo "</script>";
            exit;
        }
        $this->assign('intro', $intro);
        $this->assign('id', $_GET['id']);
        $this->display();
    }

    /**
     * 添加分销
     */
    private function addDis()
    {
    	$menber = M("menber");
    	$uid = session('uid');
    	$users = M("menber")->where(array('uid' => $uid))->find();
    	$pro = M("product")->where(array('id' => $_GET['id']))->find();
    	$config = M('config')->getField('id,value');
    	     
    	
    	// 一、二级分销,升级产品才参与二级分销
    	if($pro['uppro'] == 1) {
    		// 发放奖励(10级)
	        // 获得当前用户的 推荐人
	        // 移除自身
	        $struids = str_replace("," . session('uid'), '', $users['fuids']);
	        if (strlen($struids) > 0) {
	            // 清除空数据并转换为数组
	            $arrfuids = array_filter(explode(",", $struids));

	            $arrfuids = array_reverse($arrfuids);

	            //重消
	            $resultcx = M('config')->where("id = '20'")->order('id asc')->find();

	            $result10 = M('config')->where("id >= 3 and id <= 12")->order('id asc')->select();

	            foreach ($arrfuids as $key => $val) {
	            	if($val == 0) {
	            		continue;
	            	}
	            	$bonus = M('member_type')->where(["id"=>$users['usertype']])->getField('bonus');
	            	if($users['dongbag'] >= $config[23]) {
	            		$bonus+=$config[23];
	            	}
	            	
	            	$price = $pro['price'] * ($result10[$key]['value']+$bonus)/100;
	                $menber->where(array('uid' => $val))->setInc("chargebag", $price * (1 - $resultcx['value']));
	                $menber->where(array('uid' => $val))->setInc("jingbag", $price * $resultcx['value']);
	                $incomelog0['userid']  = $val;
	                $incomelog0['type']    = 11;
	                $incomelog0['state']   = 1;
	                $incomelog0['reson']   = session('uid') . "购买产品(重消)";
	                $incomelog0['addymd']  = date('Y-m-d', time());
	                $incomelog0['addtime'] = time();
	                $incomelog0['income']  = $price;
	                M("incomelog")->add($incomelog0);
	                unset($incomelog0);
	                // 增加发奖记录
	                $incomelog['userid']  = $val;
	                $incomelog['type']    = 9;
	                $incomelog['state']   = 1;
	                $incomelog['reson']   = session('uid') . "购买产品";
	                $incomelog['addymd']  = date('Y-m-d', time());
	                $incomelog['addtime'] = time();
	                $incomelog['income']  = $price;
	                M("incomelog")->add($incomelog);
	                unset($incomelog);
	            }
	        }
    	}
    	//股东分红
    	$gudongList = $menber->where(['dongbag'=>['gt',$config[23]]])->fetchsql(false)->getField('uid,dongbag');
    	     
    	foreach ($gudongList as $uid => $dongbag) {
    		$dongbag = $dongbag - $config[23];
    	   
        	$price = $pro['price'] * $dongbag/100;
        	     
            $menber->where(array('uid' => $val))->setInc("chargebag", $price * (1 - $resultcx['value']));
            $menber->where(array('uid' => $val))->setInc("jingbag", $price * $resultcx['value']);
            $incomelog0['userid']  = $val;
            $incomelog0['type']    = 11;
            $incomelog0['state']   = 1;
            $incomelog0['reson']   = session('uid') . "股东分红(重消)";
            $incomelog0['addymd']  = date('Y-m-d', time());
            $incomelog0['addtime'] = time();
            $incomelog0['income']  = $price;
            M("incomelog")->add($incomelog0);
            unset($incomelog0);
            // 增加发奖记录
            $incomelog['userid']  = $val;
            $incomelog['type']    = 9;
            $incomelog['state']   = 1;
            $incomelog['reson']   = session('uid') . "股东分红";
            $incomelog['addymd']  = date('Y-m-d', time());
            $incomelog['addtime'] = time();
            $incomelog['income']  = $price;
            M("incomelog")->add($incomelog);
            unset($incomelog);
        }
    	//二、四级代理
    	$arrfuids = array_filter(explode(",", $users['fuids']));
    	
    	$gudong  = $config[23] + $config[24];
    	foreach ($arrfuids as $key => $value) {
    		$userinfo = $menber->where(array('uid' => $value))->find();
    		$subQuery0   = $menber->field('uid')->where("fuids like '%," . $value . ",%'")->select(false);
	        $sumconsume0 = M('orderlog')->where("userid in(" . $subQuery0 . ")")->count();
	        $upnum = M('member_type')->where("`order`<$sumconsume0")->order('id desc')->getField('id');
	        //print_r($sumconsume0."|0|");
	        if ($upnum > $userinfo['usertype']) {
	            // 升级密大使
	            $menber->where(array('uid' => $value))->save(array('usertype' => $upnum, 'typeuptime' => time()));
	        }
	        if($sumconsume0>$config[21]){
	        	$menber->where(array('uid' => $value))->save(array('dongbag' => $config[23]));
	        }
	        $sumconsume1 = M('orderlog')->field('userid,count(*) as count')->where("userid in(" . $subQuery0 . ")")->group('userid')->fetchsql(false)->getField('userid,count(*)');
	        $max = max($sumconsume1);
	             
	        if($sumconsume0>$config[21] && $sumconsume0 - $max>$config[22]){
	        	$menber->where(array('uid' => $value))->save(array('dongbag' => $gudong));
	        }
    	}
    	$result10 = M('config')->where("id >= 25 and id <= 34")->order('id asc')->select();
    	//三、股东分红
    	foreach ($arrfuids as $key => $value) {
    		$dongbag = $menber->where(['uid'=>$value])->getField('dongbag');
    		$otherfuids = $menber->where(['uid'=>$value])->fetchsql(true)->getField('fuids');
    		$count = $menber->where(['dongbag'=>['gt',0], 'uid'=>['in',trim($otherfuids)]])->count();
    		if($count > 0 && $dongbag >= $gudong) {
    			$menber->where(array('uid' => $value))->setField('dongbag', $gudong+$result10[$count-1]['value']);
    		}
    	}

    }

    /**
     * 公司简介
     */
    public function introduce()
    {
        $article = M('article');
        $intro   = $article->order('aid DESC')->where(array('type' => 5))->select();
        $this->assign('intro', $intro[0]);
        $this->display();
    }

    /**
     * 公告
     */
    public function advertising()
    {
        $article = M('article');
        $intro   = $article->where(array('aid' => $_GET['id']))->select();
        $this->assign('intro', $intro[0]);
        $this->display();
    }

    /**
     * 值班团队
     */
    public function gruop()
    {
        $article = M('article');
        $intro   = $article->where(array('aid' => $_GET['id']))->select();
        $this->assign('intro', $intro[0]);
        $this->display();
    }

    /**
     * 分析专家
     */
    public function professor()
    {
        $article = M('article');
        $intro   = $article->where(array('aid' => $_GET['id']))->select();
        $this->assign('intro', $intro[0]);
        $this->display();
    }
    // 我的产品
    public function financial()
    {
        $orderlog = M('orderlog');
        $result   = $orderlog->join('p_product ON p_orderlog.productid=p_product.id')->where(array('userid' => session('uid')))->select();
        foreach ($result as $k => $v) {
            if ($v['states'] == 0) {
                $v['total']     = $v['prices'] * $v['num'];
                $data['wait'][] = $v;
            }
            if ($v['states'] == 1) {
                $v['total']       = $v['prices'] * $v['num'];
                $data['coming'][] = $v;
            }
            if ($v['states'] == 2) {
                $v['total']         = $v['prices'] * $v['num'];
                $data['comoever'][] = $v;
            }
        }
        $this->assign('res', $data);
        $this->display();
    }

    public function K()
    {
        $rite = M("rite")->order("id desc")->limit(7)->select();
        $this->assign('seven', $rite);
        $this->display();
    }

    public function choose()
    {
        $log = M('incomelog')->order('id DESC')->where(array('userid' => session('uid'), 'type' => 2))->select();
        $this->assign('log', $log);
        $this->display();
    }

    public function qrcode()
    {
        Vendor('phpqrcode.phpqrcode');
        $id = I('get.id');
        // 生成二维码图片
        $object = new \QRcode();
        $url    = "http://" . $_SERVER['HTTP_HOST'] . '/index.php/Admin/Article/editearticle/id/' . $id; //网址或者是文本内容

        $level                = 3;
        $size                 = 5;
        $errorCorrectionLevel = intval($level); //容错级别
        $matrixPointSize      = intval($size); //生成图片大小
        $object->png($url, false, $errorCorrectionLevel, $matrixPointSize, 2);
    }

    public function address()
    {
        $users = M("menber")->where(array('uid' => session('uid')))->find();
        $pro   = M("product")->where(array('id' => $_GET['id']))->find();

        if ($_GET['paytype'] == "1" && $users['jingbag'] < $pro['price']) {
            //购物钱包
            echo "<script>alert('余额钱包余额不足!即将跳转至充值页面。');";
            echo "window.location.href='" . __ROOT__ . "/index.php/Home/user/futou.html';";
            echo "</script>";
            exit;
        } elseif ($_GET['paytype'] == "2" && $users['chargebag'] < $pro['price']) {
            //积分钱包
            echo "<script>alert('积分钱包余额不足!');";
            echo "window.location.href='" . __ROOT__ . "/index.php/Home/Index/detail/id/" . $_GET['id'] . "';";
            echo "</script>";
            exit;
        }

        $this->display();
    }

    private function isaddceng($cen)
    {
        if (in_array($cen, array(1, 3, 7, 15, 31, 63, 127, 255, 511))) {
            return 1;
        } else {
            return 0;
        }
    }

    private function getceng($count)
    {
        if ($count == 0) {
            // 1
            return 1;
        } elseif ($count >= 1 && $count < 3) {
            // 2
            return 2;
        } elseif ($count >= 3 && $count < 7) {
            // 3
            return 3;
        } elseif ($count >= 7 && $count < 15) {
            // 4
            return 4;
        } elseif ($count >= 15 && $count < 31) {
            // 5
            return 5;
        } elseif ($count >= 31 && $count < 63) {
            // 6
            return 6;
        } elseif ($count >= 63 && $count < 127) {
            // 7
            return 7;
        } elseif ($count >= 127 && $count < 255) {
            // 8
            return 8;
        } elseif ($count >= 255 && $count < 511) {
            // 9
            return 9;
        } elseif ($count >= 511 && $count < 1024) {
            // 10
            return 10;
        }
    }
    // 1首页 2公告 3值班团队 4分析专家 5公司简介  gruop
    public function types()
    {
        $type = isset($_GET['type']) ? $_GET['type'] : 2;
        if ($type == 2) {
            $title = "公告列表";
        } elseif ($type == 3) {
            $title = "值班团队";
        } elseif ($type == 4) {
            $title = "分析专家";
        }
        $article = M('article');
        $intro   = $article->order('aid DESC')->where(array('type' => $type))->select();
        $this->assign('title', $title);
        $this->assign('res', $intro);
        $this->display();
    }

    /**
     * 获取当前页面完整URL地址
     */
    private function get_url()
    {
        $sys_protocal = isset($_SERVER['SERVER_PORT']) && $_SERVER['SERVER_PORT'] == '443' ? 'https://' : 'http://';
        $php_self     = $_SERVER['PHP_SELF'] ? $_SERVER['PHP_SELF'] : $_SERVER['SCRIPT_NAME'];
        $path_info    = isset($_SERVER['PATH_INFO']) ? $_SERVER['PATH_INFO'] : '';
        $relate_url   = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : $php_self . (isset($_SERVER['QUERY_STRING']) ? '?' . $_SERVER['QUERY_STRING'] : $path_info);
        return $sys_protocal . (isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : '') . $relate_url;
    }

    private function getlists($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $result = curl_exec($ch);
        curl_close($ch);
        return json_decode($result, true);
    }

    private function curlget($url)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        // 执行并获取HTML文档内容
        $output = curl_exec($ch);
        // 释放curl句柄
        curl_close($ch);
        return json_decode($output, true);
    }
}
