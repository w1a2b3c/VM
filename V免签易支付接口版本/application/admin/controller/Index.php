<?php
namespace app\admin\controller;

use think\App;
use think\Db;
use Zxing\QrReader;

class Index
{
    public function __construct()
    {
        $this->cver = '2.7';// 当前版本
    }
    
    public function index()
    {
        return 'by:vone';
    }

    public function getReturn($code = 1,$msg = "成功",$data = null){
        return array("code"=>$code,"msg"=>$msg,"data"=>$data);
    }

    //后台菜单
    public function getMenu()
    {
        $menu = array(
            array(
                "name" => "系统设置",
                "type" => "url",
                "url" => "admin/setting.html?t=" . time(),
            ),
            array(
                "name" => "监控端设置",
                "type" => "url",
                "url" => "admin/jk.html?t=" . time(),
            ),
            array(
                "name" => "免挂设置",
                "type" => "url",
                "url" => "admin/hang_free.html?t=" . time(),
            ), array(
                "name" => "邮箱设置",
                "type" => "url",
                "url" => "admin/email.html?t=" . time(),
            ), array(
                "name" => "订单列表",
                "type" => "url",
                "url" => "admin/orderlist.html?t=" . time(),
            ),
            array(
                "name" => "微信二维码",
                "type" => "menu",
                "node" => array(
                    array(
                        "name" => "添加",
                        "type" => "url",
                        "url" => "admin/addwxqrcode.html?t=" . time(),
                    ),
                    array(
                        "name" => "管理",
                        "type" => "url",
                        "url" => "admin/wxqrcodelist.html?t=" . time(),
                    )
                ),
            ), array(
                "name" => "支付宝二维码",
                "type" => "menu",
                "node" => array(
                    array(
                        "name" => "添加",
                        "type" => "url",
                        "url" => "admin/addzfbqrcode.html?t=" . time(),
                    ),
                    array(
                        "name" => "管理",
                        "type" => "url",
                        "url" => "admin/zfbqrcodelist.html?t=" . time(),
                    )
                ),
            ), array(
                "name" => "QQ二维码",
                "type" => "menu",
                "node" => array(
                    array(
                        "name" => "添加",
                        "type" => "url",
                        "url" => "admin/addqqqrcode.html?t=" . time(),
                    ),
                    array(
                        "name" => "管理",
                        "type" => "url",
                        "url" => "admin/qqqrcodelist.html?t=" . time(),
                    )
                ),
            ), array(
                "name" => "Api说明",
                "type" => "url",
                "url" => "api.html?t=" . time(),
            )
        );

        return json($menu);

    }

    public function getMain(){
        $today = strtotime(date("Y-m-d"),time());

        $todayOrder = Db::name("pay_order")
            ->where("create_date >=".$today)
            ->where("create_date <=".($today+86400))
            ->count();


        $todaySuccessOrder = Db::name("pay_order")
            ->where("state >=1")
            ->where("create_date >=".$today)
            ->where("create_date <=".($today+86400))
            ->count();



        $todayCloseOrder = Db::name("pay_order")
            ->where("state",-1)
            ->where("create_date >=".$today)
            ->where("create_date <=".($today+86400))
            ->count();

        $todayMoney = Db::name("pay_order")
            ->where("state >=1")
            ->where("create_date >=".$today)
            ->where("create_date <=".($today+86400))
            ->sum("price");


        $countOrder = Db::name("pay_order")
            ->count();
        $countMoney = Db::name("pay_order")
            ->where("state >=1")
            ->sum("price");

        $v = Db::query("SELECT VERSION();");
        $v=$v[0]['VERSION()'];

        if(function_exists("gd_info")) {
            $gd_info = @gd_info();
            $gd = $gd_info["GD Version"];
        }else{
            $gd = '<font color="red">GD库未开启！</font>';
        }

        return json($this->getReturn(1,"成功",array(
            "todayOrder"=>$todayOrder,
            "todaySuccessOrder"=>$todaySuccessOrder,
            "todayCloseOrder"=>$todayCloseOrder,
            "todayMoney"=>round($todayMoney,2),
            "countOrder"=>$countOrder,
            "countMoney"=>round($countMoney),

            "PHP_VERSION"=>PHP_VERSION,
            "PHP_OS"=>PHP_OS,
            "SERVER"=>$_SERVER ['SERVER_SOFTWARE'],
            "MySql"=>$v,
            "Thinkphp"=>"v".App::VERSION,
            "RunTime"=>$this->sys_uptime(),
            "ver"=>"V".$this->cver,
            "gd"=>$gd,
        )));

    }
    private function sys_uptime() {
        $output='';
        if (false === ($str = @file("/proc/uptime"))) return false;
        $str = explode(" ", implode("", $str));
        $str = trim($str[0]);
        $min = $str / 60;
        $hours = $min / 60;
        $days = floor($hours / 24);
        $hours = floor($hours - ($days * 24));
        $min = floor($min - ($days * 60 * 24) - ($hours * 60));
        if ($days !== 0) $output .= $days."天";
        if ($hours !== 0) $output .= $hours."小时";
        if ($min !== 0) $output .= $min."分钟";
        return $output;
    }
    // public function checkUpdate(){
    //     $ver = get_curl("https://www.zzwws.cn/api.php?q=V免签易支付版");
    //     $arr = json_decode($ver,true);
    //     if ($arr['version'] > $this->cver){
    //         return json($this->getReturn(1,"[v".$arr['version']."已于".$arr['updateTime']."发布]","{$arr['url']}"));
    //     }else{
    //         return json($this->getReturn(0,"程序是最新版"));
    //     }
    // }

    
    public function checkUpdate(){
        return json($this->getReturn(0,"程序是最新版"));
    }
    public function getSettings(){
        $user = Db::name("setting")->where("vkey","user")->find();
        $pass = Db::name("setting")->where("vkey","pass")->find();
        $notifyUrl = Db::name("setting")->where("vkey","notifyUrl")->find();
        $returnUrl = Db::name("setting")->where("vkey","returnUrl")->find();
        $key = Db::name("setting")->where("vkey","key")->find();
        $lastheart = Db::name("setting")->where("vkey","lastheart")->find();
        $lastpay = Db::name("setting")->where("vkey","lastpay")->find();
        $jkstate = Db::name("setting")->where("vkey","jkstate")->find();
        $close = Db::name("setting")->where("vkey","close")->find();
        $payQf = Db::name("setting")->where("vkey","payQf")->find();
        $wxpay = Db::name("setting")->where("vkey","wxpay")->find();
        $zfbpay = Db::name("setting")->where("vkey","zfbpay")->find();
        $qqpay = Db::name("setting")->where("vkey","qqpay")->find();
        $pid = Db::name('setting')->where('vkey','pid')->find();
        $email = Db::name('setting')->where('vkey','email')->find();
        $alipayId = Db::name('setting')->where('vkey','alipay_id')->find();
        $timeInterval = Db::name('setting')->where('vkey','time_interval')->find();
        $payNum = Db::name("setting")->where("vkey","pay_num")->find();
        $bdNum = Db::name("setting")->where("vkey","bd_num")->find();
        $cronKey = Db::name("setting")->where("vkey","cron_key")->find();
        $jump = Db::name("setting")->where("vkey","home_jump")->find();
        $transfer = Db::name("setting")->where("vkey","transfer")->find();
        $confirm = Db::name("setting")->where("vkey","confirm")->find();
        $voice = Db::name("setting")->where("vkey","pay_voice")->find();
        if ($key['vvalue']==""){
            $key['vvalue'] = md5(time());
            Db::name("setting")->where("vkey","key")->update(array(
                "vvalue"=>$key['vvalue']
            ));
        }
        if($cronKey['vvalue'] == ''){
            $cronKey['vvalue'] = md5(time());
            Db::name("setting")->where("vkey","cron_key")->update(array(
                "vvalue"=>$cronKey['vvalue']
            ));
        }
        
        return json($this->getReturn(1,"成功",array(
            "user"=>$user['vvalue'],
            "pass"=>$pass['vvalue'],
            "notifyUrl"=>$notifyUrl['vvalue'],
            "returnUrl"=>$returnUrl['vvalue'],
            "key"=>$key['vvalue'],
            "lastheart"=>$lastheart['vvalue'],
            "lastpay"=>$lastpay['vvalue'],
            "jkstate"=>$jkstate['vvalue'],
            "close"=>$close['vvalue'],
            "payQf"=>$payQf['vvalue'],
            "wxpay"=>$wxpay['vvalue'],
            "zfbpay"=>$zfbpay['vvalue'],
            "qqpay"=>$qqpay['vvalue'],
            "pid"=>$pid['vvalue'],
            "email"=>$email['vvalue'],
            "alipayId"=>$alipayId['vvalue'],
            "timeInterval"=>$timeInterval['vvalue'],
            'payNum'=>$payNum['vvalue'],
            'bdNum'=>$bdNum['vvalue'],
            'jump'=>$jump['vvalue'],
            'transfer'=>$transfer['vvalue'],
            'confirm'=>$confirm['vvalue'],
            "voice"=>$voice['vvalue']
        )));


    }
    public function saveSetting(){
        Db::name("setting")->where("vkey","user")->update(array("vvalue"=>input("user")));
        if(input("pass") != ''){
            Db::name("setting")->where("vkey","pass")->update(array("vvalue"=>password_hash(input("pass"),PASSWORD_BCRYPT)));
        }
        // Db::name("setting")->where("vkey","notifyUrl")->update(array("vvalue"=>input("notifyUrl")));
        // Db::name("setting")->where("vkey","returnUrl")->update(array("vvalue"=>input("returnUrl")));
        Db::name("setting")->where("vkey","key")->update(array("vvalue"=>input("key")));
        Db::name("setting")->where("vkey","close")->update(array("vvalue"=>input("close")));
        Db::name("setting")->where("vkey","payQf")->update(array("vvalue"=>input("payQf")));
        Db::name("setting")->where("vkey","wxpay")->update(array("vvalue"=>input("wxpay")));
        Db::name("setting")->where("vkey","zfbpay")->update(array("vvalue"=>input("zfbpay")));
        Db::name("setting")->where("vkey","qqpay")->update(array("vvalue"=>input("qqpay")));
        Db::name("setting")->where("vkey","pid")->update(array("vvalue"=>input("pid")));
        Db::name("setting")->where("vkey","email")->update(array("vvalue"=>input("email")));
        Db::name("setting")->where("vkey","alipay_id")->update(array("vvalue"=>input("alipayId")));
        Db::name("setting")->where("vkey","time_interval")->update(array("vvalue"=>input("timeInterval")));
        Db::name("setting")->where("vkey","pay_num")->update(array("vvalue"=>input("payNum")));
        Db::name("setting")->where("vkey","bd_num")->update(array("vvalue"=>input("bdNum")));
        Db::name("setting")->where("vkey","home_jump")->update(array("vvalue"=>input("jump")));
        Db::name("setting")->where("vkey","transfer")->update(array("vvalue"=>input("transfer")));
        Db::name("setting")->where("vkey","confirm")->update(array("vvalue"=>input("confirm")));
        Db::name("setting")->where("vkey","pay_voice")->update(array("vvalue"=>input("voice")));
        return json($this->getReturn());


    }


    public function addPayQrcode(){
        $db = Db::name("pay_qrcode")->insert(array(
            "type"=>input("type"),
            "pay_url"=>input("pay_url"),
            "price"=>input("price"),
        ));
        return json($this->getReturn());

    }

    public function getPayQrcodes(){
        $page = input("page");
        $size = input("limit");

        $obj = Db::table('pay_qrcode')->page($page,$size);

        $obj = $obj->where("type",input("type"));

        $array = $obj->order("id","desc")->select();

        //echo $obj->getLastSql();
        return json(array(
            "code"=>0,
            "msg"=>"获取成功",
            "data"=>$array,
            "count"=> $obj->count()
        ));
    }
    public function delPayQrcode(){
        Db::name("pay_qrcode")->where("id",input("id"))->delete();
        return json($this->getReturn());

    }

    public function getOrders(){
        $page = input("page");
        $size = input("limit");

        $obj = Db::table('pay_order')->page($page,$size);
        if (input("type")){
            $obj = $obj->where("type",input("type"));
        }
        if (input("state")){
            $obj = $obj->where("state",input("state"));
        }
        if(input('orderId')){
            $obj = $obj->where("order_id='".input('orderId')."' or pay_id='".input('orderId')."' or param like '%".input('orderId')."%'");
        }

        $array = $obj->order("id","desc")->select();

        //echo $obj->getLastSql();
        return json(array(
            "code"=>0,
            "msg"=>"获取成功",
            "data"=>$array,
            "count"=> $obj->count()
        ));
    }
    public function delOrder(){
        $res = Db::name("pay_order")->where("id",input("id"))->find();

        Db::name("pay_order")->where("id",input("id"))->delete();
        if ($res['state']==0){
            Db::name("tmp_price")
                ->where("oid",$res['order_id'])
                ->delete();
        }

        return json($this->getReturn());

    }

    public function setBd(){

        $res = Db::name("pay_order")->where("id",input("id"))->find();

        if ($res){

            $url = $res['notify_url'];

            $res2 = Db::name("setting")->where("vkey","key")->find();
            $key = $res2['vvalue'];

            $str = $res['param'];
            $stri = stripos($str,'_');
            if($stri !== false){
              $strs = substr($str,$stri+1);
            }else{
              $strs = '';
            }
            $type = $res['type'];
            if($type == 1){
                $type = 'wxpay';
            }
            if($type == 2){
                $type = 'alipay';
            }
            if($type == 3){
                $type = 'qqpay';
            }
            $data = [
                'pid' => $res['pid'],
                'type' => $type,
                'out_trade_no' => $res['pay_id'],
                'notify_url' => $res['notify_url'],
                'return_url' => $res['return_url'],
                'name' => $strs,
                'money' => $res['price'],
                'trade_no' => $res['order_id'],
                'trade_status' => 'TRADE_SUCCESS'
                ];
            $param = createLinkstring(argSort($data));
            $p = http_build_query(argSort($data)).'&sign='.md5($param.$key).'&sign_type=MD5';
            // $p = $param.'&sign='.md5($param.$key).'&sign_type=MD5';
            if (strpos($url,"?")===false){
                $url = $url."?".$p;
            }else{
                $url = $url."&".$p;
            }

            $re = get_curl($url);

            if ($re=="success"){
                if ($res['state']==0){
                    Db::name("tmp_price")
                        ->where("oid",$res['order_id'])
                        ->delete();
                }

                $row = Db::name("pay_order")->field('email,state,type,really_price,pay_id')->where("id",$res['id'])->find();
                if(!empty($row['email']) && $row['state'] != 1){
                    if($row['type'] == 1){
                        $type = '微信';
                    }
                    if($row['type'] == 2){
                        $type = '支付宝';
                    }
                    if($row['type'] == 3){
                        $type = 'QQ';
                    }

                    $httpType = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
                    $from_name = Db::name("setting")->where("vkey","e_from_name")->find()['vvalue'];
                    $password = Db::name("setting")->where("vkey","e_password")->find()['vvalue'];
                    $mailName = '补单成功通知 - '.$from_name;
                    $mailContent = '你好，你的订单已经成功补单，补单时间：'.date('Y-m-d H:i:s').'，支付方式：'.$type.'，实际金额：'.$row['really_price'].'元，商户号：'.$row['pay_id'];
                    $url = $httpType.$_SERVER['HTTP_HOST'].'/mail/api.php?name='.$mailName.'&content='.$mailContent.'&address='.$row['email'].'&sign='.md5($mailName.$mailContent.$row['email'].$password);
                    $data = get_curl($url);
                    $arr = json_decode($data,true);
                    if($arr['code'] == 0){
                        $emailMsg = '邮件发送失败';
                    }
                }

                Db::name("pay_order")->where("id",$res['id'])->update(array("state"=>1));

                return json($this->getReturn(1,isset($emailMsg) ? $emailMsg : '补单成功',$re));
            }else{
                return json($this->getReturn(-2,"补单失败",$re));
            }
        }else{
            return json($this->getReturn(-1,"订单不存在"));

        }


    }

    public function delGqOrder(){
        Db::name("pay_order")->where("state","-1")->delete();
        return json($this->getReturn());
    }
    public function delLastOrder(){
        Db::name("pay_order")->where("create_date <".(time()-604800))->delete();
        return json($this->getReturn());
    }

    //获取客户IP
    public function ip() {

        return $_SERVER['REMOTE_ADDR'];
    }
    
    // 免挂
    public function hangFree()
    {
        $aliState = Db::name("setting")->where("vkey","ali_cookie_state")->find()['vvalue'];
        $cronKey = Db::name("setting")->where("vkey","cron_key")->find()['vvalue'];
        $aliCookieTime = Db::name("setting")->where("vkey","ali_cookie_time")->find()['vvalue'];
        $passageway = Db::name("setting")->where("vkey","passageway")->find()['vvalue'];
        $qqCookieTime = Db::name("setting")->where("vkey","qq_cookie_time")->find()['vvalue'];
        $qqState = Db::name("setting")->where("vkey","qq_cookie_state")->find()['vvalue'];
        $aliTime = Db::name('setting')->where('vkey','ali_running_time')->find()['vvalue'];
        $qqTime = Db::name('setting')->where('vkey','qq_running_time')->find()['vvalue'];
        $aliFrequency = Db::name('setting')->where('vkey','ali_frequency')->find()['vvalue'];
        $qqFrequency = Db::name('setting')->where('vkey','qq_frequency')->find()['vvalue'];
        return json([
            'code' => 1,
            'msg' => '获取成功',
            'data' => [
                'aliState' => $aliState,
                'cronKey' => $cronKey,
                'aliCookieTime' => date('Y-m-d H:i:s',$aliCookieTime),
                'passageway' => $passageway,
                'qqCookieTime' => date('Y-m-d H:i:s',$qqCookieTime),
                'qqState' => $qqState,
                'aliTime' => date('Y-m-d H:i:s',$aliTime),
                'qqTime' => date('Y-m-d H:i:s',$qqTime),
                'aliFrequency' => $aliFrequency,
                'qqFrequency' => $qqFrequency
                ]
            ]);
    }
    
    // 免挂修改
    public function hangFreeEdit()
    {
        $post = input('post.');
        if(empty($post)){
            return json(['code' => 0,'msg' => '请传入参数']);
        }
        
        $httpType = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        if($post['act'] == 'getqrcode' && !empty($post['type'])){
            if($post['type'] == 2){
                echo get_curl($httpType.$_SERVER['HTTP_HOST'].'/get_cookie/zfb_gck.php?act=getqrcode');
            }
            if($post['type'] == 3){
                echo get_curl($httpType.$_SERVER['HTTP_HOST'].'/get_cookie/qq_gck.php?do=getqrpic');
            }
        }else if($post['act'] == 'getcookie' && !empty($post['type'])){
            if($post['type'] == 2){
                $data = get_curl($httpType.$_SERVER['HTTP_HOST'].'/get_cookie/zfb_gck.php?act=getcookie&loginid='.$post['loginid']);
                echo $data;
                $arr = json_decode($data,true);
                if($arr['code'] == 1){
                    Db::name("setting")->where("vkey","ali_cookie_state")->update(array("vvalue"=>1));
                    Db::name("setting")->where("vkey","ali_cookie")->update(array("vvalue"=>$arr['cookie']));
                    Db::name("setting")->where("vkey","ali_cookie_time")->update(array("vvalue"=>time()));
                }
            }
            
            if($post['type'] == 3){
                $data = get_curl($httpType.$_SERVER['HTTP_HOST'].'/get_cookie/qq_gck.php?do=qrlogin&qrsig='.$post['qrsig']);
                echo $data;
                $arr = json_decode($data,true);
                if($arr['code'] == 1){
                    Db::name("setting")->where("vkey","qq_cookie_state")->update(array("vvalue"=>1));
                    Db::name("setting")->where("vkey","qq_cookie")->update(array("vvalue"=>$arr['cookie']));
                    Db::name("setting")->where("vkey","qq_cookie_time")->update(array("vvalue"=>time()));
                }
            }
        }else if($post['act'] == 'save'){
            Db::name("setting")->where("vkey","cron_key")->update(array("vvalue"=>$post['key']));
            Db::name("setting")->where("vkey","passageway")->update(array("vvalue"=>$post['passageway']));
            Db::name("setting")->where("vkey","ali_frequency")->update(array("vvalue"=>$post['aliFrequency']));
            Db::name("setting")->where("vkey","qq_frequency")->update(array("vvalue"=>$post['qqFrequency']));
            return json(['code' => 1,'msg' => '成功']);
        }
    }
    
    // 修改邮箱配置
    public function setEmail()
    {
        Db::name("setting")->where("vkey","e_host")->update(array("vvalue"=>input('host')));
        Db::name("setting")->where("vkey","e_port")->update(array("vvalue"=>input('port')));
        Db::name("setting")->where("vkey","e_from_name")->update(array("vvalue"=>input('from_name')));
        Db::name("setting")->where("vkey","e_user_name")->update(array("vvalue"=>input('user_name')));
        Db::name("setting")->where("vkey","e_password")->update(array("vvalue"=>input('password')));
        Db::name("setting")->where("vkey","e_from")->update(array("vvalue"=>input('from')));

        return json($this->getReturn());
    }
    
    // 获取邮箱配置
    public function getEmail()
    {
        $state = Db::name("setting")->where("vkey","e_state")->find()['vvalue'];
        $host = Db::name("setting")->where("vkey","e_host")->find()['vvalue'];
        $port = Db::name("setting")->where("vkey","e_port")->find()['vvalue'];
        $from_name = Db::name("setting")->where("vkey","e_from_name")->find()['vvalue'];
        $user_name = Db::name("setting")->where("vkey","e_user_name")->find()['vvalue'];
        $password = Db::name("setting")->where("vkey","e_password")->find()['vvalue'];
        $from = Db::name("setting")->where("vkey","e_from")->find()['vvalue'];
        $data = [
            'code' => 1,
            'msg' => '获取成功',
            'state' => $state,
            'host' => $host,
            'port' => $port,
            'from_name' => $from_name,
            'user_name' => $user_name,
            'password' => $password,
            'from' => $from
            ];
        return json($data);
    }
}
