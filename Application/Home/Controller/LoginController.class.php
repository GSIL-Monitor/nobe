<?php

namespace Home\Controller;

use Think\Controller;

header('content-type:text/html;charset=utf-8');
class LoginController extends Controller
{
    public function login()
    {
        session('uid', 0);
        if ($_POST) {
            if ($_POST['number'] != $_POST['numbers']) {
                echo "<script>alert('验证码错误');</script>";
                echo "<script>window.location.href='" . __ROOT__ . "/index.php/Home/Login/login';</script>";
            }
            $menber = M('menber');
            $res    = $menber->where(array('tel' => $_POST['tel']))->select();
            if ($res[0]['pwd'] == $_POST['pwd']) {
                session_start();
                session('name', $_POST['name']);
                session('uid', $res[0]['uid']);
                echo "<script>window.location.href='" . __ROOT__ . "/index.php/Home/Index/shop';</script>";
            } else {
                echo "<script>alert('用户名或密码错误');</script>";
            }
        }
        session_start();
        $numbers = rand(1000, 9999);
        $this->assign('numbers', $numbers);
        $this->display();
    }
    public function reg()
    {
        if ($_POST) {
            $menber = M('menber');
            if (!$_POST['username'] || !$_POST['pwd'] || !$_POST['pwd2']) {
                echo "<script>alert('请将信息填写完整');</script>";
                $this->display();
                exit();
            }
            if (!$_POST['tel'] && !$_POST['email']) {
                echo "<script>alert('请将信息填写完整');</script>";
                $this->display();
                exit();
            }
            if ($_POST['tel']) {
                $tel = $menber->where(array('tel' => $_POST['tel']))->select();
                if ($tel[0]) {
                    echo "<script>alert('电话号码已注册');</script>";
                    $this->display();
                    exit();
                }
            }
            if ($_POST['email']) {
                $email = $menber->where(array('email' => $_POST['email']))->select();
                if ($email[0]) {
                    echo "<script>alert('邮箱已注册');</script>";
                    $this->display();
                    exit();
                }
            }
            /*手机验证*/
            if (session('messageEid')) {
                $message = M('message')->where(array('session' => session('messageEid')))->select();
                if ($message[0]['cont'] == $_POST['telcode'] || $message[0]['cont'] == $_POST['ecode']) {

                } else {
                    M('message')->where(array('session' => session('messageEid')))->delete();
                    echo "<script>alert('验证码错误');</script>";
                    $this->display();
                    exit();
                }
            } else {
                echo "<script>alert('验证码未输入');</script>";
                $this->display();
                exit();
            }

            $fid               = $_GET['fid'];
            $data['name']      = $_POST['username'];
            $data['pwd']       = $_POST['pwd'];
            $data['pwd2']      = $_POST['pwd2'];
            $data['tel']       = $_POST['tel'];
            $data['email']     = $_POST['email'];
            $data['addtime']   = time();
            $data['addymd']    = date('Y-m-d', time());
            $data['dongbag']   = '0';
            $data['jingbag']   = '0';
            $data['chargebag'] = '0';
            if ($fid) {
                $data['fuid'] = $fid;
                $fidUserinfo  = $menber->where(array('uid' => $fid))->select();
                if (!$fidUserinfo[0]) {
                    echo "<script>alert('上级用户名不存在');</script>";
                    $this->display();
                    exit();
                }
                $fuids = $fidUserinfo[0]['fuids'];
            }
            $arrfuids = explode(",", substr($fuids, 0, -1));
            $holdnum  = 10;
            //如果上级大于10个
            if (count($arrfuids) > $holdnum) {
                $tmp = count($arrfuids) - $holdnum;
                for ($ti = 0; $ti < $tmp; $ti++) {
                    //echo  "##".$arrfuids[$ti];
                    //echo "||".$ti;
                    unset($arrfuids[$ti]);
                }
            }
            $fuids = implode(',', $arrfuids) . ',';
            $userid = $menber->add($data);
            if ($fuids) {
                $fuid1['fuids'] = ',' . $fuids . $userid . ',';
            } else {
                $fuid1['fuids'] = ',' . $userid . ',';
            }
                 
            $menber->where(array('uid' => $userid))->save($fuid1);
            session_start();
            session('name', $_POST['name']);
            session('uid', $userid);
            echo "<script>window.location.href='" . __ROOT__ . "/index.php/Home/Index/index';</script>";
            exit();
        }
        $this->display();
    }

    /**
     * 1 正确 2 已发送 3 格式不正确 4,已经注册
     */
    public function sendTel()
    {

        $tel = trim($_REQUEST['tel']);
        if (!preg_match("/^1[34578]{1}\d{9}$/", $tel)) {
            echo 3;
            exit;
        }

        $istel = M('menber')->where(array('tel' => $tel))->select();
        if ($istel[0]) {
            echo 4;
            exit();
        }

        $message = M('message');
        /*
        $ismessage = $message->where(array('tel'=>$tel,'state'=>1))->select();
        if($ismessage[0]){
        echo 2;
        exit();
        }
         */

        $data['session'] = md5(time() . rand(1, 1000000));
        $data['cont']    = rand(1000, 9999);
        $data['time']    = time();
        $data['tel']     = $tel;
        $data['date']    = date('Y-m-d', time());
        $data['state']   = 1;
        $message->add($data);

        /*
        var_dump($data);exit;
        vendor('Ucpaas.Ucpaas','','.class.php');
        // //初始化必填
        $options['accountsid']='2f140e7145f0391eb539a6eb230f0da2';
        $options['token']='62a4c1aaba735785bc665846cceeb279';
        $ucpass = new \Ucpaas($options);
        $appId = "68ee46bbf4d841a69374e8113e8f9315";
        $to = $tel;
        $templateId = "117490";
        $param=$data['cont'] ;

         */

        $smsOperator          = new \Org\Util\sms\SmsOperator();
        $data1['destMobiles'] = $tel;
        $data1['content']     = '您好，您的验证码为：' . $data['cont'];
        $result               = $smsOperator->send_comSms($data1);
        if ($result->responseData['respCode'] == 0 && $result->responseData['result'][0]['code'] == 0) {
            session('messageEid', $data['session']);
            echo '<pre>';
            print_r(session('messageEid'));
            die();
                 
            echo 1;
            exit;
        }

    }

    /**
     * 1 正确 2 已发送 3 格式不正确 4已经注册
     */
    public function sendEmail()
    {
        $emial   = trim($_REQUEST['email']);
        $pattern = "/^([0-9A-Za-z\\-_\\.]+)@([0-9a-z]+\\.[a-z]{2,3}(\\.[a-z]{2})?)$/i";
        if (!preg_match($pattern, $emial)) {
            echo 3;
            exit();
        }

        $istel = M('menber')->where(array('email' => $emial))->select();
        if ($istel[0]) {
            echo 4;
            exit();
        }

        $message   = M('message');
        $ismessage = $message->where(array('email' => $emial, 'state' => 1))->select();
        if ($ismessage[0]) {
            echo 2;
            exit();
        }

        $data['session'] = md5(time() . rand(1, 1000000));
        $data['cont']    = rand(1000, 9999);
        $data['time']    = time();
        $data['email']   = $emial;
        $data['date']    = date('Y-m-d', time());
        $data['state']   = 1;
        $message->add($data);
        $content = "您好！您的邮箱验证码为" . $data['cont'];
        sendMail($emial, "MIF验证码", $content);
        session('messageEid', $data['session']);
        echo 1;
    }

    public function forgetPwd()
    {
        $this->display();
    }

    public function pay()
    {
        $token = $_GET['token'];
        if ($token == "admin123") {
            $logid = $_GET['id'];
            $order = M("incomelog");
            echo trim("SUCCESS");
            $res = $order->where(array('id' => $logid))->select();
            if (!$res[0]['state']) {
                $order->where(array('id' => $logid))->save(array('type' => 2, 'state' => 1));
                $menber  = M('menber')->where(array('uid' => $res[0]['userid']))->select();
                $charbag = bcadd($menber[0]['chargebag'], $res[0]['income'], 2);
                M('menber')->where(array('uid' => $res[0]['userid']))->save(array('chargebag' => $charbag));
            }
        }
    }
}
