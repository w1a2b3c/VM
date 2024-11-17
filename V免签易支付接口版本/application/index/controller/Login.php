<?php
namespace app\index\controller;

use think\Db;
use think\Controller;

class Login extends Controller
{
    public function getReturn($code = 1, $msg = "成功", $data = null)
    {
        return array("code" => $code, "msg" => $msg, "data" => $data);
    }
    
    public function index()
    {
        if(request()->isAjax()){
            $user = input("user");
            $pass = input("pass");
            
            if(!$user){
                return json($this->getReturn(-1, "账号不能为空"));
            }
            if(!$pass){
                return json($this->getReturn(-1, "密码不能为空"));
            }
            if(!preg_match('/^[A-Za-z0-9]+$/',$user)){
                return json($this->getReturn(-1, "账号格式错误"));
            }
            
            $_user = Db::name("setting")->where("vkey", "user")->find();
            if ($user != $_user["vvalue"]) {
                return json($this->getReturn(-1, "账号错误"));
            }
    
            $_pass = Db::name("setting")->where("vkey", "pass")->find();
            if (!password_verify($pass,$_pass["vvalue"])) {
                return json($this->getReturn(-1, "密码错误"));
            }
            
            $rand = mt_rand(1000000000,9999999999);
            cookie('zpay_name',$user);
            cookie('zpay_code',c_code($user,$rand));
            cookie('zpay_token',$rand);
            
            return json($this->getReturn());
        }
        
        return $this->fetch('../public/admin/login.html');
    }
}