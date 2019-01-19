<?php
namespace Admin\Controller;
use Think\Controller;
class UserController extends Controller {

    public function qrcode(){
        Vendor('phpqrcode.phpqrcode');
        $id=I('get.id');
        //生成二维码图片 http://localhost/index.php/Home/Login/reg
        $object = new \QRcode();
        $url="http://".$_SERVER['HTTP_HOST'].'/index.php/Home/Login/reg/fid/'.$id;

        $level=3;
        $size=5;
        $errorCorrectionLevel =intval($level) ;//容错级别
        $matrixPointSize = intval($size);//生成图片大小
        $object->png($url, false, $errorCorrectionLevel, $matrixPointSize, 2);
    }

	public function login(){
        if(IS_POST){
            $name = I('post.name');
            $pwd = I('post.pwd');
            $user = M('user');
            if(!$name || !$pwd){
                echo "<script>alert('用户名或密码不存在');";
                echo "window.history.go(-1);";
                echo "</script>";
            }
            $result= $user->where(array('name'=>$name))->select();
            if($result[0]['password'] ==$pwd){
                $_SESSION['uname']=$name;
                echo "<script>window.location.href = '".__ROOT__."/index.php/Admin/Index/main';</script>";
            }else{
                    echo "<script>alert('用户名或密码不存在');";
                    echo "window.history.go(-1);";
                    echo "</script>";
                }
        }
        $this->display();
    }

    public function logOut(){
        session('uname',null);
        cookie('is_login',null);
        echo "<script>window.location.href = '".__ROOT__."/index.php/Admin/User/login';</script>";
    }
	
	//刷新奖衔
	public function crontab1(){
	 //获得营业额
	 $configobj =M('config')->where(array('id'=>1))->find();
     $config2 =$configobj['value'];
	 //echo $config2."||";
	 
	 
	 //获得所有当月的密大使
	 //获得当月的开始结束时间 
	 $begin_time = '0';//strtotime(date('Y-m-01 00:00:00',strtotime('-1 month')));
	 $end_time = '2556115199';//strtotime(date("Y-m-d 23:59:59", strtotime(-date('d').'day')));
	 $userlist=M("menber")-> field('uid')->where("typeuptime> '".$begin_time."' and typeuptime< '".$end_time."' and usertype='3' ")->select();
	 //print_r($userlist);
	 //print("##".count($userlist)."##");
	 $jxmoney=$config2*0.08*0.5/count($userlist);
	 $jxmoney=floor($jxmoney*100)/100;
	 
	 //echo  $jxmoney;
	 //重消 
	 $resultcx = M('config') -> where("id = '20'") -> order('id asc') -> find();
	 foreach($userlist as $key=>$val) {
		 

     M("menber") -> where(array('uid' => $val['uid'])) -> setInc("chargebag", $jxmoney*(1-$resultcx['value']));
     M("menber") -> where(array('uid' => $val['uid'])) -> setInc("jingbag", $jxmoney*$resultcx['value']); 						
     $incomelog0['userid'] = $val['uid'];
	 $incomelog0['type'] = 11;
	 $incomelog0['state'] = 1;
	 $incomelog0['reson'] = date('Y-m-d', time())."||奖衔(重消)";
 	 $incomelog0['addymd'] = date('Y-m-d', time());
	 $incomelog0['addtime'] = time();
	 $incomelog0['income'] = $jxmoney*$resultcx['value'];
	 M("incomelog") -> add($incomelog0);
	 unset($incomelog0);		 
	 $incomelog['userid'] = $val['uid'];
	 $incomelog['type'] = 10;
	 $incomelog['state'] = 1;
	 $incomelog['reson'] = date('Y-m-d', time())."||奖衔";
	 $incomelog['addymd'] = date('Y-m-d', time());
	 $incomelog['addtime'] = time();
	 $incomelog['income'] = $jxmoney*(1-$resultcx['value']);
	 M("incomelog") -> add($incomelog);
	 unset($incomelog);
	 
	 }
	
	echo "奖衔刷新成功"; 
	exit;
	 
	}
	//刷新业绩
	public function crontab2(){
		//获得营业额
	 $configobj =M('config')->where(array('id'=>1))->find();
     $config2 =$configobj['value'];
	 
	 
	 //获得所有当月的密大使
	 //获得当月的开始结束时间
	 $begin_time = '0';//strtotime(date('Y-m-01 00:00:00',strtotime('-1 month')));
	 $end_time = '2556115199';//strtotime(date("Y-m-d 23:59:59", strtotime(-date('d').'day')));
	 
	 $userlist=M("menber")-> field('uid')->where("typeuptime> '".$begin_time."' and typeuptime< '".$end_time."' and usertype='3' ")->select();
	  
	 //获得每个密大使 推荐的人数
	 
	$arraymirec=array();
	$summirec=0;
	//print_r($userlist);
	foreach($userlist as $key=>$val) {
		$arraymirec[$val['uid']] = M("menber") -> where("addtime> '".$begin_time."' and addtime< '".$end_time."' and uid !='" . $val['uid'] . "' and fuids like '%," . $val['uid'] . ",%'") -> count();
	    $summirec=$summirec+$arraymirec[$val['uid']];
	}
	
	//print_r($summirec);
	//print_r($arraymirec);
	//exit();
	 //重消 
	 $resultcx = M('config') -> where("id = '20'") -> order('id asc') -> find();
	 foreach($userlist as $key=>$val) {
		 
	 $yjmoney=$config2*0.08*0.5*$arraymirec[$val['uid']]/$summirec;
	 $yjmoney=floor($yjmoney*100)/100;
	 
	 if($yjmoney>0){ 
     M("menber") -> where(array('uid' => $val['uid'])) -> setInc("chargebag", $yjmoney*(1-$resultcx['value']));
     M("menber") -> where(array('uid' => $val['uid'])) -> setInc("jingbag", $yjmoney*$resultcx['value']); 						
     $incomelog0['userid'] = $val['uid'];
	 $incomelog0['type'] = 11;
	 $incomelog0['state'] = 1;
	 $incomelog0['reson'] = date('Y-m-d', time())."||业绩(重消)";
 	 $incomelog0['addymd'] = date('Y-m-d', time());
	 $incomelog0['addtime'] = time();
	 $incomelog0['income'] = $yjmoney*$resultcx['value'];
	 M("incomelog") -> add($incomelog0);
	 unset($incomelog0);	
	
	 $incomelog['userid'] = $val['uid'];
	 $incomelog['type'] = 10;
	 $incomelog['state'] = 1;
	 $incomelog['reson'] = date('Y-m-d', time())."||业绩";
	 $incomelog['addymd'] = date('Y-m-d', time());
	 $incomelog['addtime'] = time();
	 $incomelog['income'] = $yjmoney*(1-$resultcx['value']);
	 M("incomelog") -> add($incomelog);
	 unset($incomelog);
	 }
	 $yjmoney=0;
	 
	 }
	echo "业绩刷新成功"; 
	exit;
	}


    /**
     * @return int ok
     * 是否有每日收益
     */
    public function getusernums($userid,$num){
        $income =M('incomelog');
        $daycomelogs = $income->where(array('type'=>10,'userid'=>$userid,'state'=>1))->select();
        $daycome =0;
        foreach($daycomelogs as $k=>$v){
            $daycome=bcadd($daycome,$v['income'],2);
        }
        $conf = M("config")->where(array('id'=>1))->select();
        $endmoney = bcmul($conf[0]['value'],$num,2);
        if($daycome>=$endmoney){
            return 0;
        }else{
            return 1;
        }
    }

    private function savelog($data){
        $incomelog =M('incomelog');
        return $incomelog->add($data);
    }


    public function crantabUserIncome(){
        $menber =M('menber');
        $income =M('incomelog');
        if($_GET['uid']){
            $map['uid']  = $_GET['uid'];
        }else{
            $map['uid']  = array('gt',9);
        }
        $result_user = $menber->where($map)->select();
        foreach($result_user as $k=>$v){
            $chargebag = $v['chargebag'];
            $incomebag = $v['incomebag'];
            $allIncome =bcadd($chargebag,$incomebag,2);  // 所有钱包

            $daycomelogs = $income->where(array('state'=>1,'userid'=>$v['uid']))->select();
            $userIncome = 0;
            foreach($daycomelogs as $k1=>$v1){         // 收益
                $userIncome =bcadd($userIncome,$v1['income'],2);
            }
            if($_GET['uid']){
                print_r("每日收益==》".$userIncome);
            }
            $dayoutlogs = $income->where(array('state'=>2,'userid'=>$v['uid']))->select();

            $userOut = 0;                              // 支出
            foreach($dayoutlogs as $k2=>$v2){
                $userOut =bcadd($userOut,$v2['income'],2);
            }
            if($_GET['uid']){
                print_r("<br>总支出==》".$userOut);
            }
            $allIncomesUser =bcsub($userIncome,$userOut,2);      // 总收入
            if($allIncomesUser < 0){
                print_r("userID".$v['uid']."收入日志异常");
            }
            $layout =$allIncomesUser-$allIncome;
            if($layout!=0){
               print_r("用户ID：".$v['uid']."<br>");
               print_r("钱包总额：".$allIncome."<br>");
               print_r("收入总额：".$allIncomesUser."<br><br><br>");
            }
        }
//        print_r($result_user);die;
    }


    function crontabRite(){
        $today = date('m-d',time());
        $isdate = M("Rite")->where(array('date'=>$today))->select();
        if($isdate[0]){
//            $config= M("Config")->where(array('name'=>'daily_income'))->select();
//            M("Rite")->where(array('date'=>$today))->save(array('cont'=>$config[0]['val'],'date'=>$today));
            echo 2;exit();
        }else{
            $config= M("Config")->where(array('id'=>1))->select();
            M("Rite")->add(array('cont'=>$config[0]['value'],'date'=>$today));
            echo 1;exit();
        }
    }
}



 ?>