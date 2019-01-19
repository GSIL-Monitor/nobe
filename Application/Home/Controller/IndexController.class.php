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

            if ($users['usertype'] == 1 && $intro['uppro'] == 1) {
                // 查看该产品是否是升级产品，该用户是否升级(密友升级为密天使)
                if ($_GET['paytype'] == "1") {
                    $jingbag = bcsub($userinfo['jingbag'], $pro['price'], 2);
                    $menber->where(array('uid' => session('uid')))->save(array('jingbag' => $jingbag, 'usertype' => 2));
                } else {
                    $chargebag = bcsub($userinfo['chargebag'], $pro['price'], 2);
                    $menber->where(array('uid' => session('uid')))->save(array('chargebag' => $chargebag, 'usertype' => 2));
                }

            } else {
                if ($_GET['paytype'] == "1") {
                    $jingbag = bcsub($userinfo['jingbag'], $pro['price'], 2);
                    $menber->where(array('uid' => session('uid')))->save(array('jingbag' => $jingbag));
                } else {
                    $chargebag = bcsub($userinfo['chargebag'], $pro['price'], 2);
                    $menber->where(array('uid' => session('uid')))->save(array('chargebag' => $chargebag));
                }

            }
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
                    // echo $key;
                    // echo "||";
                    // echo $val;
                    // echo "##";
                    // echo ($result10[$key]['value']);
                    M("menber")->where(array('uid' => $val))->setInc("chargebag", $result10[$key]['value'] * (1 - $resultcx['value']));
                    M("menber")->where(array('uid' => $val))->setInc("jingbag", $result10[$key]['value'] * $resultcx['value']);
                    $incomelog0['userid']  = $val;
                    $incomelog0['type']    = 11;
                    $incomelog0['state']   = 1;
                    $incomelog0['reson']   = session('uid') . "购买产品(重消)";
                    $incomelog0['addymd']  = date('Y-m-d', time());
                    $incomelog0['addtime'] = time();
                    $incomelog0['income']  = $result10[$key]['value'];
                    M("incomelog")->add($incomelog0);
                    unset($incomelog0);
                    // 增加发奖记录
                    $incomelog['userid']  = $val;
                    $incomelog['type']    = 9;
                    $incomelog['state']   = 1;
                    $incomelog['reson']   = session('uid') . "购买产品";
                    $incomelog['addymd']  = date('Y-m-d', time());
                    $incomelog['addtime'] = time();
                    $incomelog['income']  = $result10[$key]['value'];
                    M("incomelog")->add($incomelog);
                    unset($incomelog);
                }
            }
            // 检查密大使升级 查看升级的团队消费额
            $result2 = M('config')->where("id=2")->find();
            $upnum   = $result2['value'];
            // echo $upnum;

            // 获取当前用户的团队消费额度
            //print_r($userinfo);
            $subQuery0   = $menber->field('uid')->where("fuids like '%," . $userinfo['uid'] . ",%'")->select(false);
            $sumconsume0 = M('orderlog')->where("userid in(" . $subQuery0 . ")")->sum('totals');
                 
            //print_r($sumconsume0."|0|");
            if ($sumconsume0 >= $upnum) {
                // 升级密大使
                $menber->where(array('uid' => $userinfo['uid']))->save(array('usertype' => 3, 'typeuptime' => time()));
            }

            // 1
            $userinfo1 = $menber->where(array('uid' => $userinfo['fuid']))->find();
            if ($userinfo1['fuid'] > 0) {
                $subQuery1   = $menber->field('uid')->where("fuids like '%," . $userinfo1['uid'] . ",%'")->select(false);
                $sumconsume1 = M('orderlog')->where("userid in(" . $subQuery1 . ")")->sum('totals');
                //print_r($sumconsume1."|1|");
                if ($sumconsume1 >= $upnum) {
                    // 升级密大使
                    $menber->where(array('uid' => $userinfo1['uid']))->save(array('usertype' => 3, 'typeuptime' => time()));
                }
                // 2
                $userinfo2 = $menber->where(array('uid' => $userinfo1['fuid']))->find();
                if ($userinfo2['fuid'] > 0) {
                    $subQuery2   = $menber->field('uid')->where("fuids like '%," . $userinfo2['uid'] . ",%'")->select(false);
                    $sumconsume2 = M('orderlog')->where("userid in(" . $subQuery2 . ")")->sum('totals');
                    //print_r($sumconsume2."|2|");
                    if ($sumconsume2 >= $upnum) {
                        // 升级密大使
                        $menber->where(array('uid' => $userinfo2['uid']))->save(array('usertype' => 3, 'typeuptime' => time()));
                    }
                    // 3
                    $userinfo3 = $menber->where(array('uid' => $userinfo2['fuid']))->find();
                    if ($userinfo3['fuid'] > 0) {
                        $subQuery3   = $menber->field('uid')->where("fuids like '%," . $userinfo3['uid'] . ",%'")->select(false);
                        $sumconsume3 = M('orderlog')->where("userid in(" . $subQuery3 . ")")->sum('totals');
                        //print_r($sumconsume3."|3|");
                        if ($sumconsume3 >= $upnum) {
                            // 升级密大使
                            $menber->where(array('uid' => $userinfo3['uid']))->save(array('usertype' => 3, 'typeuptime' => time()));
                        }
                        // 4
                        $userinfo4 = $menber->where(array('uid' => $userinfo3['fuid']))->find();
                        if ($userinfo4['fuid'] > 0) {
                            $subQuery4   = $menber->field('uid')->where("fuids like '%," . $userinfo4['uid'] . ",%'")->select(false);
                            $sumconsume4 = M('orderlog')->where("userid in(" . $subQuery4 . ")")->sum('totals');
                            // print_r($sumconsume4."|4|");
                            if ($sumconsume4 >= $upnum) {
                                // 升级密大使
                                $menber->where(array('uid' => $userinfo4['uid']))->save(array('usertype' => 3, 'typeuptime' => time()));
                            }
                            // 5
                            $userinfo5 = $menber->where(array('uid' => $userinfo4['fuid']))->find();
                            if ($userinfo5['fuid'] > 0) {
                                $subQuery5   = $menber->field('uid')->where("fuids like '%," . $userinfo5['uid'] . ",%'")->select(false);
                                $sumconsume5 = M('orderlog')->where("userid in(" . $subQuery5 . ")")->sum('totals');
                                // print_r($sumconsume5."|5|");
                                if ($sumconsume5 >= $upnum) {
                                    // 升级密大使
                                    $menber->where(array('uid' => $userinfo5['uid']))->save(array('usertype' => 3, 'typeuptime' => time()));
                                }
                                // 6
                                $userinfo6 = $menber->where(array('uid' => $userinfo5['fuid']))->find();
                                if ($userinfo6['fuid'] > 0) {
                                    $subQuery6   = $menber->field('uid')->where("fuids like '%," . $userinfo6['uid'] . ",%'")->select(false);
                                    $sumconsume6 = M('orderlog')->where("userid in(" . $subQuery6 . ")")->sum('totals');
                                    // print_r($sumconsume6."|6|");
                                    if ($sumconsume6 >= $upnum) {
                                        // 升级密大使
                                        $menber->where(array('uid' => $userinfo6['uid']))->save(array('usertype' => 3, 'typeuptime' => time()));
                                    }
                                    // 7
                                    $userinfo7 = $menber->where(array('uid' => $userinfo6['fuid']))->find();
                                    if ($userinfo7['fuid'] > 0) {
                                        $subQuery7   = $menber->field('uid')->where("fuids like '%," . $userinfo7['uid'] . ",%'")->select(false);
                                        $sumconsume7 = M('orderlog')->where("userid in(" . $subQuery7 . ")")->sum('totals');
                                        // print_r($sumconsume7."|7|");
                                        if ($sumconsume7 >= $upnum) {
                                            // 升级密大使
                                            $menber->where(array('uid' => $userinfo7['uid']))->save(array('usertype' => 3, 'typeuptime' => time()));
                                        }
                                        // 8
                                        $userinfo8 = $menber->where(array('uid' => $userinfo7['fuid']))->find();
                                        if ($userinfo8['fuid'] > 0) {
                                            $subQuery8   = $menber->field('uid')->where("fuids like '%," . $userinfo8['uid'] . ",%'")->select(false);
                                            $sumconsume8 = M('orderlog')->where("userid in(" . $subQuery8 . ")")->sum('totals');
                                            // print_r($sumconsume8."|8|");
                                            if ($sumconsume8 >= $upnum) {
                                                // 升级密大使
                                                $menber->where(array('uid' => $userinfo8['uid']))->save(array('usertype' => 3, 'typeuptime' => time()));
                                            }
                                            // 9
                                            $userinfo9 = $menber->where(array('uid' => $userinfo8['fuid']))->find();
                                            if ($userinfo9['fuid'] > 0) {
                                                $subQuery9   = $menber->field('uid')->where("fuids like '%," . $userinfo9['uid'] . ",%'")->select(false);
                                                $sumconsume9 = M('orderlog')->where("userid in(" . $subQuery9 . ")")->sum('totals');
                                                // print_r($sumconsume9."|9|");
                                                if ($sumconsume9 >= $upnum) {
                                                    // 升级密大使
                                                    $menber->where(array('uid' => $userinfo9['uid']))->save(array('usertype' => 3, 'typeuptime' => time()));
                                                }
                                                // 10
                                                $userinfo10 = $menber->where(array('uid' => $userinfo9['fuid']))->find();
                                                if ($userinfo10['fuid'] > 0) {
                                                    $subQuery10   = $menber->field('uid')->where("fuids like '%," . $userinfo10['uid'] . ",%'")->select(false);
                                                    $sumconsume10 = M('orderlog')->where("userid in(" . $subQuery10 . ")")->sum('totals');
                                                    // print_r($sumconsume10."|10|");
                                                    if ($sumconsume10 >= $upnum) {
                                                        // 升级密大使
                                                        $menber->where(array('uid' => $userinfo10['uid']))->save(array('usertype' => 3, 'typeuptime' => time()));
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }

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
