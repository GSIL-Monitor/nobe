<?php
namespace Admin\Controller;
use Think\Controller;
class ConfigController extends CommonController {
	public function index(){
	    $Config = M('config');
	    if($_POST){
	        $Config->where(array('id'=>1))->save(array('value'=>$_POST['jiage']));
            $Config->where(array('id'=>2))->save(array('value'=>$_POST['jintai']));

            $Config->where(array('id'=>3))->save(array('value'=>$_POST['tuijian1']));
            $Config->where(array('id'=>4))->save(array('value'=>$_POST['tuijian2']));
            $Config->where(array('id'=>5))->save(array('value'=>$_POST['tuijian3']));
            $Config->where(array('id'=>6))->save(array('value'=>$_POST['tuijian4']));
            $Config->where(array('id'=>7))->save(array('value'=>$_POST['tuijian5']));
            $Config->where(array('id'=>8))->save(array('value'=>$_POST['tuijian6']));

            $Config->where(array('id'=>9))->save(array('value'=>$_POST['tuijian7']));
            $Config->where(array('id'=>10))->save(array('value'=>$_POST['tuijian8']));
            $Config->where(array('id'=>11))->save(array('value'=>$_POST['tuijian9']));
            $Config->where(array('id'=>12))->save(array('value'=>$_POST['tuijian10']));
            $Config->where(array('id'=>13))->save(array('value'=>$_POST['huikui5']));
            $Config->where(array('id'=>14))->save(array('value'=>$_POST['huikui6']));

            $Config->where(array('id'=>15))->save(array('value'=>$_POST['tixiannum']));
            $Config->where(array('id'=>16))->save(array('value'=>$_POST['tixiantime']));

            $Config->where(array('id'=>17))->save(array('value'=>$_POST['gongpai']));

            $Config->where(array('id'=>18))->save(array('value'=>$_POST['jintaifei']));
            $Config->where(array('id'=>19))->save(array('value'=>$_POST['dongtaifei']));
			$Config->where(array('id'=>20))->save(array('value'=>$_POST['chongxiao']));
            if($_POST['jintai']){
                $today = date('m-d',time());
                $isdate = M("Rite")->where(array('date'=>$today))->select();
                if($isdate[0]){
                 M("Rite")->where(array('date'=>$today))->save(array('cont'=>$_POST['jintai'],'date'=>$today));

                }else{
                    $config= M("Config")->where(array('id'=>2))->select();
                    M("Rite")->add(array('cont'=>$config[0]['value'],'date'=>$today));

                }
            }
            echo "<script>alert('修改成功');window.location.href = '".__ROOT__."/index.php/Admin/Config/index';</script>";
        }
        $result = $Config->order('id asc')->select();
        $this->assign('res',$result);
	    $this->display();
    }

    public function bonus(){
        $Config = M('config');
        if($_POST){
            foreach ($_POST as $id => $value) {
                $Config->where(array('id'=>$id))->save(array('value'=>$value));
            }
             
            echo "<script>alert('修改成功');window.location.href = '".__ROOT__."/index.php/Admin/Config/bonus';</script>";
        }
        $result = $Config->where(['id'=>['gt',20]])->select();
             
        $this->assign('res',$result);
        $this->display();
    }

}



 ?>