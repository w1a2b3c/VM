<?php
namespace app\index\controller;

use think\Db;
use app\service\QrcodeServer;

class Index
{
    public function __construct()
    {
        $this->jump = 'login';// 默认首页跳转地址
    }
    
    public function index()
    {
        $jump = Db::name('setting')->where('vkey','home_jump')->find()['vvalue'];
        if(!$jump){
            header('location: '.$this->jump);
        }else{
            header('location: '.$jump);
        }
        /*return '<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>存在搭建问题</title>
    <meta name="renderer" content="webkit">
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1">
    <meta name="apple-mobile-web-app-status-bar-style" content="black">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="format-detection" content="telephone=no">
</head>
<body class="body">
<div style="padding: 15px;color: red;">
    <h1 style="text-align: center">检测到默认文档未设定成index.html</h1><br><br>
    <h1 style="text-align: center">请在宝塔面板-网站-设置-默认文档->将index.html放到第一行并保存！</h1><br><br>
</div>
</body>
</html>
';*/
    }

    public function getReturn($code = 1, $msg = "成功", $data = null)
    {
        return array("code" => $code, "msg" => $msg, "data" => $data);
    }

    //创建订单
    public function createOrder()
    {
        $this->closeEndOrder();

        $maxNum = Db::name('setting')->where('vkey','pay_num')->find()['vvalue'];//同一ip最多待支付订单
        $count = Db::name('pay_order')->where([['ip','=',sprintf('%u', ip2long(get_real_ip()))],['state','=',0]])->count();
        if($maxNum && $count >= $maxNum){
            return json($this->getReturn(-1, "待支付订单不能超过{$maxNum}个"));
        }
        
        $ms = Db::name('setting')->where('vkey','time_interval')->find()['vvalue'];// 时间间隔秒数
        if($ms){
            if($count){
                $ms = $ms*$count;
            }
            $count2 = Db::name('pay_order')->where([['ip','=',sprintf('%u', ip2long(get_real_ip()))],['state','=',0],['create_date','>=',time()-$ms]])->count();
            
            if($count2 || cookie('zpay_order')){
                return json($this->getReturn(-1, "请在{$ms}秒后再发起支付"));
            }else{
                cookie('zpay_order',sprintf('%u', ip2long(get_real_ip())),$ms);
            }
        }
        
        $pid = input("pid");
        $pid_st = Db::name("setting")->where("vkey", "pid")->find();
        if (!$pid || $pid == "") {
            return json($this->getReturn(-1, "请传入商户ID"));
        }else if($pid != $pid_st['vvalue']){
            return json($this->getReturn(-1, "商户ID错误"));
        }
        
        $payId = input("out_trade_no");
        if (!$payId || $payId == "") {
            return json($this->getReturn(-1, "请传入商户订单号"));
        }
        
        $type = input("type");
        if ($type != 'wxpay' && $type != 'alipay' && $type != 'qqpay') {
            return json($this->getReturn(-1, "请传入支付方式=>wxpay|微信 alipay|支付宝 qqpay|QQ钱包"));
        }
        
        $price = input("money");
        if (!$price || $price == "") {
            return json($this->getReturn(-1, "请传入订单金额"));
        }else if ($price < 0.01) {
            return json($this->getReturn(-1, "订单金额不能小于0.01"));
        }else if ($price <= 0) {
            return json($this->getReturn(-1, "订单金额必须大于0"));
        }

        $sign = input("sign");
        if (!$sign || $sign == "") {
            return json($this->getReturn(-1, "请传入签名"));
        }

        $isHtml = input("isHtml");
        if (!$isHtml || $isHtml == "") {
            $isHtml = 1;
        }

        $res = Db::name("setting")->where("vkey", "key")->find();
        $key = $res['vvalue'];

        $notify_url = input("notify_url");
        if (!$notify_url || $notify_url == "") {
            return json($this->getReturn(-1, "请传入异步回调地址"));
        }
        
        $return_url = input("return_url");
        if (!$return_url || $return_url == "") {
            return json($this->getReturn(-1, "请传入同步回调地址"));
        }
        
        $name = input("name");
        if (!$name || $name == "") {
            return json($this->getReturn(-1, "请传入商品名称"));
        }
        
        $sitename = input("sitename");
        if (!$sitename || $sitename == "") {
            // return json($this->getReturn(-1, "请传入网站名称"));
        }
        
        $sign_type = input("sign_type");
        if (!$sign_type || $sign_type == "") {
            return json($this->getReturn(-1, "请传入签名类型"));
        }
        
        $arrs = input('get.');
        unset($arrs['sign']);
        unset($arrs['sign_type']);
        $_sign = createLinkstring(argSort($arrs));
        $_sign = md5($_sign.$key);
        
        if ($sign != $_sign) {
            return json($this->getReturn(-1, "签名错误"));
        }
        
        /*$jkstate = Db::name("setting")->where("vkey", "jkstate")->find();
        $jkstate = $jkstate['vvalue'];
        if ($jkstate!="1"){
            return json($this->getReturn(-1, "监控端状态异常，请检查"));

        }*/
        
        if($type == 'wxpay'){
            $type = 1;
        }
        if($type == 'alipay'){
            $type = 2;
        }
        if($type == 'qqpay'){
            $type = 3;
        }

        $reallyPrice = bcmul($price ,100);
        $reallyPrice = intval($reallyPrice);
        $payQf = Db::name("setting")->where("vkey", "payQf")->find();
        $payQf = $payQf['vvalue'];
        $pay_order = Db::name("pay_order")->where("pay_id", $payId)->find();
        $tmp_price = Db::name('tmp_price')->where('oid',$pay_order['order_id'])->find();
        $alipayId = Db::name('setting')->where('vkey','alipay_id')->find()['vvalue'];
        $passageway = Db::name('setting')->where('vkey','passageway')->find()['vvalue'];
        $orderId = date("YmdHms") . mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9) . mt_rand(0, 9);
        if($type != 2 || !$alipayId || $passageway != 5){
            $ok = false;
            for ($i = 0; $i < 50; $i++) {
                $tmpPrice = $reallyPrice . "-" . $type;
                if($pay_order['param'] == $sitename.'_'.$name && $pay_order['state'] != 1 && $price == $pay_order['price'] && $tmp_price){
                    $orderId = $pay_order['order_id'];
                    $res = Db::name('tmp_price')->where([['price','=',$tmpPrice],['oid','<>',$orderId]])->find();
                    if($res){
                        $row = false;
                    }else{
                        Db::name('tmp_price')->where('oid',$orderId)->update(['price' => $tmpPrice]);
                        $row = true;
                    }
                }else{
                    $row = Db::execute("INSERT IGNORE INTO tmp_price (price,oid) VALUES ('".$tmpPrice."','".$orderId."')");
                }
                if ($row) {
                    $ok = true;
                    break;
                }
                if ($payQf == 1) {
                    $reallyPrice++;
                } else if ($payQf == 2) {
                    $reallyPrice--;
                }
            }
            if (!$ok) {
                return json($this->getReturn(-1, "订单超出负荷，请稍后重试"));
            }
        }
        
        //echo $reallyPrice;

        $reallyPrice = bcdiv($reallyPrice, 100,2);

        if ($type == 1) {
            $payUrl = Db::name("setting")->where("vkey", "wxpay")->find();
            $payUrl = $payUrl['vvalue'];

        } else if ($type == 2) {
            $payUrl = Db::name("setting")->where("vkey", "zfbpay")->find();
            $payUrl = $payUrl['vvalue'];
        }else if($type == 3){
            $payUrl = Db::name("setting")->where("vkey", "qqpay")->find()['vvalue'];
        }

        if ($payUrl == "") {
            return json($this->getReturn(-1, "请您先进入后台配置程序"));
        }
        $isAuto = 1;
        $_payUrl = Db::name("pay_qrcode")
            ->where("price", $reallyPrice)
            ->where("type", $type)
            ->find();
        if ($_payUrl) {
            $payUrl = $_payUrl['pay_url'];
            $isAuto = 0;
        }
        
        $createDate = time();
        $data = array(
            'pid' => $pid,
            "close_date" => 0,
            "create_date" => $createDate,
            "is_auto" => $isAuto,
            "notify_url" => $notify_url,
            "order_id" => $orderId,
            "param" => $sitename.'_'.$name,
            "pay_date" => 0,
            "pay_id" => $payId,
            "pay_url" => $payUrl,
            "price" => $price,
            "really_price" => $reallyPrice,
            "return_url" => $return_url,
            "state" => 0,
            "type" => $type,
            "ip" => sprintf('%u', ip2long(get_real_ip()))

        );
        
        // $pay_order = Db::name("pay_order")->where("pay_id", $payId)->find();
        if($pay_order){
            if($pay_order['param'] == $data['param'] && $pay_order['state'] != 1){
                unset($data['pay_id']);
                $res = Db::name("pay_order")->where("pay_id", $payId)->update($data);
                if(!$res){
                    return json($this->getReturn(-1, "订单信息修改失败"));
                }
            }else{
                return json($this->getReturn(-1, "商户订单号已存在"));
            }
        }else{
            Db::name("pay_order")->insert($data);
        }

        //return "<script>window.location.href = '/payPage/pay.html?orderId=" + c.getOrderId() + "'</script>";
        if ($isHtml == 1) {

            echo "<script>window.location.href = 'payPage/pay.php?orderId=" . $orderId . "'</script>";

        } else {
            $time = Db::name("setting")->where("vkey", "close")->find();
            $data = array(
                "payId" => $payId,
                "orderId" => $orderId,
                "payType" => $type,
                "price" => $price,
                "reallyPrice" => $reallyPrice,
                "payUrl" => $payUrl,
                "isAuto" => $isAuto,
                "state" => 0,
                "timeOut" => $time['vvalue'],
                "date" => $createDate
            );
            return json($this->getReturn(1, "成功", $data));

        }


    }
    //获取订单信息
    public function getOrder()
    {

        $res = Db::name("pay_order")->where("order_id", input("orderId"))->find();
        if ($res){
            $time = Db::name("setting")->where("vkey", "close")->find();

            $data = array(
                "id" => $res['id'],
                "payId" => $res['pay_id'],
                "orderId" => $res['order_id'],
                "payType" => $res['type'],
                "price" => $res['price'],
                "reallyPrice" => $res['really_price'],
                "payUrl" => $res['pay_url'],
                "isAuto" => $res['is_auto'],
                "state" => $res['state'],
                "timeOut" => $time['vvalue'],
                "date" => $res['create_date'],
                "param" => $res['param']
            );
            return json($this->getReturn(1, "成功", $data));
        }else{
            return json($this->getReturn(-1, "云端订单编号不存在"));
        }
    }
    //查询订单状态
    public function checkOrder()
    {
        $res = Db::name("pay_order")->where("order_id", input("orderId"))->find();
        if ($res){
            if ($res['state']==0){
                return json($this->getReturn(-1, "订单未支付"));
            }
            if ($res['state']==-1){
                return json($this->getReturn(-1, "订单已过期"));
            }

            $res2 = Db::name("setting")->where("vkey","key")->find();
            $key = $res2['vvalue'];

            $res['price'] = number_format($res['price'],2,".","");
            $res['really_price'] = number_format($res['really_price'],2,".","");


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

            $url = $res['return_url'];



            if (strpos($url,"?")===false){
                $url = $url."?".$p;
            }else{
                $url = $url."&".$p;
            }

            return json($this->getReturn(1, "成功", $url));
        }else{
            return json($this->getReturn(-1, "云端订单编号不存在"));
        }

    }
    //关闭订单
    public function closeOrder(){
        $res2 = Db::name("setting")->where("vkey","key")->find();
        $key = $res2['vvalue'];
        $orderId = input("orderId");

        $_sign = $orderId.$key;

        if (md5($_sign)!=input("sign")){
            return json($this->getReturn(-1, "签名校验不通过"));
        }

        $res = Db::name("pay_order")->where("order_id",$orderId)->find();

        if ($res){
            if ($res['state']!=0){
                return json($this->getReturn(-1, "订单状态不允许关闭"));
            }
            Db::name("pay_order")->where("order_id",$orderId)->update(array("state"=>-1,"close_date"=>time()));
            Db::name("tmp_price")
                ->where("oid",$res['order_id'])
                ->delete();
            return json($this->getReturn(1, "成功"));
        }else{
            return json($this->getReturn(-1, "云端订单编号不存在"));

        }

    }
    //获取监控端状态
    public function getState(){
        $res2 = Db::name("setting")->where("vkey","key")->find();
        $key = $res2['vvalue'];
        $t = input("t");

        $_sign = $t.$key;

        if (md5($_sign)!=input("sign")){
            return json($this->getReturn(-1, "签名校验不通过"));
        }

        $res = Db::name("setting")->where("vkey","lastheart")->find();
        $lastheart = $res['vvalue'];
        $res = Db::name("setting")->where("vkey","lastpay")->find();
        $lastpay = $res['vvalue'];
        $res = Db::name("setting")->where("vkey","jkstate")->find();
        $jkstate = $res['vvalue'];

        return json($this->getReturn(1, "成功",array("lastheart"=>$lastheart,"lastpay"=>$lastpay,"jkstate"=>$jkstate)));

    }

    //App心跳接口
    public function appHeart(){
        $this->closeEndOrder();

        $res2 = Db::name("setting")->where("vkey","key")->find();
        $key = $res2['vvalue'];
        $t = input("t");

        $_sign = $t.$key;

        if (md5($_sign)!=input("sign")){
            return json($this->getReturn(-1, "签名校验不通过"));
        }

//        $jg = time()*1000 - $t;
//        if ($jg>50000 || $jg<-50000){
//            return json($this->getReturn(-1, "客户端时间错误"));
//        }

        Db::name("setting")->where("vkey","lastheart")->update(array("vvalue"=>time()));
        Db::name("setting")->where("vkey","jkstate")->update(array("vvalue"=>1));
        return json($this->getReturn());
    }
    //App推送付款数据接口
    public function appPush(){
        $this->closeEndOrder();

        $res2 = Db::name("setting")->where("vkey","key")->find();
        $key = $res2['vvalue'];
        $t = input("t");
        $type = input("type");
        $price = input("price");
        $sign = input("sign");
        $transMemo = input('transMemo');
        $_sign = $type.$price.$t.$transMemo.$key;

        if (md5($_sign)!=$sign){
            return json($this->getReturn(-1, "签名校验不通过"));
        }

//        $jg = time()*1000 - $t;
//        if ($jg>50000 || $jg<-50000){
//            return json($this->getReturn(-1, "客户端时间错误"));
//        }

        Db::name("setting")
            ->where("vkey","lastpay")
            ->update(
                array(
                    "vvalue"=>time()
                )
            );
        
        $data = [
            ['really_price','=',$price],
            ['state','=',0],
            ['type','=',$type],
            ];
        if($transMemo){
            $data[] = ['id','=',$transMemo];
        }
        
        $res = Db::name("pay_order")->where($data)->find();
        if ($res){

            Db::name("tmp_price")
                ->where("oid",$res['order_id'])
                ->delete();

            Db::name("pay_order")->where("id",$res['id'])->update(array("state"=>1,"pay_date"=>time(),"close_date"=>time()));

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

            if (strpos($url,"?")===false){
                $url = $url."?".$p;
            }else{
                $url = $url."&".$p;
            }


            $re = get_curl($url);
            if ($re=="success"){
                return json($this->getReturn());
            }else{
                Db::name("pay_order")->where("id",$res['id'])->update(array("state"=>2));

                return json($this->getReturn(-1,"异步通知失败"));
            }


        }/*else{
            $data = array(
                "close_date" => 0,
                "create_date" => time(),
                "is_auto" => 0,
                "notify_url" => "",
                "order_id" => "无订单转账",
                "param" => "无订单转账",
                "pay_date" => 0,
                "pay_id" => "无订单转账",
                "pay_url" => "",
                "price" => $price,
                "really_price" => $price,
                "return_url" => "",
                "state" => 1,
                "type" => $type

            );

            Db::name("pay_order")->insert($data);
            return json($this->getReturn());

        }*/


    }

    //关闭过期订单接口(请用定时器至少1分钟调用一次)
    public function closeEndOrder(){
        $res = Db::name("setting")->where("vkey","lastheart")->find();
        $lastheart = $res['vvalue'];
        if ((time()-$lastheart)>60){
            Db::name("setting")->where("vkey","jkstate")->update(array("vvalue"=>0));
        }
        
        $time = Db::name("setting")->where("vkey", "close")->find();
        $closeTime = time()-60*$time['vvalue'];
        $close_date = time();
        $num = 0;
        $res = Db::name("pay_order")
            ->where("create_date <= ".$closeTime)
            ->where("state",0)
            ->update(array("state"=>-1,"close_date"=>$close_date));
        if ($res){
            $rows = Db::name("pay_order")->where("close_date",$close_date)->select();
            foreach ($rows as $row){
                $res = Db::name("tmp_price")->where("oid",$row['order_id'])->delete();
                if($res){
                    $num++;
                }
            }
        }else{
            $rows = Db::name("tmp_price")->select();
            if($rows){
                foreach ($rows as $row){
                    $re = Db::name("pay_order")->where("order_id",$row['oid'])->find();
                    if(!$re){
                        $res = Db::name("tmp_price")->where("oid",$row['oid'])->delete();
                        if($res){
                            $num++;
                        }
                    }
                }
            }
        }
        
        return json($this->getReturn(1,"成功清理".$num."条订单"));
    }
    
    // 生成二维码
    public function enQrcode($url){
        if($url){
            if(stripos($url,'scheme=') !== false){
                $url1 = substr($url,0,stripos($url,'scheme=')+7);
                $url2 = substr($url,stripos($url,'scheme=')+7);
                $url = $url1.urlencode($url2);
                // return $url;
            }
            $qr_code = new QrcodeServer(['generate'=>"display","size",200]);
            $content = $qr_code->createServer($url);
            return response($content,200,['Content-Length'=>strlen($content)])->contentType('image/png');
        }else{
            return json(['code' => 0,'msg' => 'url参数不能为空']);
        }
    }
    
    // 提交补单通知
    public function submitBd()
    {
        $maxNum = Db::name('setting')->where('vkey','bd_num')->find()['vvalue'];// 同一ip最多提交补单次数
        $count = Db::name('pay_order')->where([['ip','=',sprintf('%u', ip2long(get_real_ip()))],['state','=',0],['send_mail' ,'=',1]])->count();
        if($maxNum && $count >= $maxNum){
            return json(['code' => 0,'msg' => '提交补单的订单不能超过'.$maxNum.'个']);
        }
        
        $payId = input('payId');
        $sEmail = input('email');
        
        if(!$payId){
            return json(['code' => 0,'msg' => '请传入商户号']);
        }

        $row = Db::name("pay_order")->field('send_mail,really_price,type,create_date')->where("pay_id",$payId)->find();
        
        if(!$row){
            return json(['code' => 0,'msg' => '商户号不存在']);
        }
        
        if($row['send_mail'] == 1){
            return json(['code' => 0,'msg' => '已提交过补单，请勿再次提交']);
        }
        if(!($row['create_date'] <= time()-60)){
            return json(['code' => 0,'msg' => '请等一分钟后提交补单']);
        }
        if($sEmail){
            if(!preg_match('/\w+([-+.]\w+)*@\w+([-.]\w+)*\.\w+([-.]\w+)*/',$sEmail)){
                return json(['code' => 0,'msg' => '邮箱格式错误']);
            }
            Db::name("pay_order")->where("pay_id",$payId)->update(array("email"=>$sEmail));
        }

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
        
        $mailContent = '有一条新的补单，提交时间：'.date('Y-m-d H:i:s').'，支付方式：'.$type.'，实际金额：'.$row['really_price'].'元，商户号：'.$payId;
        $res = $this->sendMail('补单通知',$mailContent);
        if($res){
            Db::name("pay_order")->where("pay_id",$payId)->update(array("send_mail"=>1));
            return json(['code' => 1,'msg' => '提交补单成功']);
        }else{
            return json(['code' => 0,'msg' => '提交补单失败']);
        }
    }
    
    // 获取支付宝
    public function alipayInfo()
    {
        /*if(empty($_SERVER['HTTP_REFERER'])){
            return json(['code' => 0,'msg' => '禁止请求']);
        }
        $re = $_SERVER['HTTP_REFERER'];
        $re = parse_url($re);
        if($re['host'] != $_SERVER['HTTP_HOST']){
            return json(['code' => 0,'msg' => '禁止请求']);
        }*/
        
        $post = input('post.');
        if(!empty($post['orderId'])){
            $res = Db::name('pay_order')->where('order_id',$post['orderId'])->find();
            if(!$res){
                return json(['code' => 0,'msg' => '没有找到该订单信息']);
            }
            $alipayId = Db::name('setting')->where('vkey','alipay_id')->find()['vvalue'];
            $passageway = Db::name('setting')->where('vkey','passageway')->find()['vvalue'];
            $transfer = Db::name('setting')->where('vkey','transfer')->find()['vvalue'];
            $confirm = Db::name('setting')->where('vkey','confirm')->find()['vvalue'];
            $voice = Db::name('setting')->where('vkey','pay_voice')->find()['vvalue'];
            return json(['code' => 1,'msg' => '获取成功','alipayId' => $alipayId,'passageway' => $passageway,'transfer' => $transfer,'confirm' => $confirm,'voice' => $voice]);
        }else{
            return json(['code' => 0,'msg' => '禁止请求']);
        }
    }
    
    // 免挂监控
    public function cron()
    {
        $key = Db::name('setting')->where('vkey','cron_key')->find()['vvalue'];
        if(input('key') != $key){
            return json(['code' => 0,'msg' => '密钥错误']);
        }
        
        $httpType = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        
        $lastheart = Db::name("setting")->where("vkey","lastheart")->find()['vvalue'];
        if($lastheart <= time()-30){
            $xtKey = Db::name("setting")->where("vkey","key")->find()['vvalue'];
            $t = time();
            $sign = md5($t.$xtKey);
            get_curl($httpType.$_SERVER['HTTP_HOST'].'/appHeart?t='.$t.'&sign='.$sign);
        }
        
        $aliCookieState = Db::name("setting")->where("vkey","ali_cookie_state")->find()['vvalue'];
        if($aliCookieState == 1){
            $aliCookie = Db::name('setting')->where('vkey','ali_cookie')->find()['vvalue'];
            if(!$aliCookie){
                echo '支付宝cookie为空<br>';
            }else{
                $aliCookie = base64_decode($aliCookie);
                $aliCookie = str_replace(' ','',$aliCookie);
                $aliCookie = rtrim($aliCookie,';').';';
                preg_match('/ctoken=(.*?);/',$aliCookie,$token);
                
                $ref = [
                    'https://mrchportalweb.alipay.com/user/home',
                    'https://mrchportalweb.alipay.com/assetmanage/user/asset/view_v2',
                    'https://mbillexprod.alipay.com/enterprise/mainIndex.htm#/',
                    'https://b.alipay.com/signing/productSetV2.htm?mrchportalwebServer=https%3A%2F%2Fmrchportalweb.alipay.com',
                    'https://mrchportalweb.alipay.com/dynlink/operationCenter/operateTool.htm',
                    'https://mrchportalweb.alipay.com/user/datacenter/b/home',
                    'https://uemprod.alipay.com/baseinfo/merchantShopBaseInfo.htm#/infomanage?_k=2fp4tc',
                    'https://mbillexprod.alipay.com/enterprise/accountTotalAssetQuery.htm#/',
                    'https://mbillexprod.alipay.com/enterprise/fundAccountDetail.htm'
                    ];
                
                $alipayId = Db::name('setting')->where('vkey','alipay_id')->find()['vvalue'];
                $passageway = Db::name('setting')->where('vkey','passageway')->find()['vvalue'];
                $aliTime = Db::name('setting')->where('vkey','ali_running_time')->find()['vvalue'];
                if(!$passageway){
                    echo '没有选择监控通道<br>';
                }else{
                    if($passageway != 5 && $aliTime <= time()-1){
                        if($passageway == 1){
                        $threadData = get_curl('https://mrchportalweb.alipay.com/user/asset/queryData?_ksTS=1564846095488_41&_input_charset=utf-8&ctoken='.@$token[1],0,$aliCookie);
                        preg_match('/"totalAvailableBalance":"(.*?)"/', $threadData, $trStr);
                        }
                
                		if($passageway == 2){
                		    if(!$alipayId){
                		        echo '未填写支付宝商户号<br>';
                		    }else{
                		        $json = get_curl('https://mbillexprod.alipay.com/enterprise/accountTotalAssetQuery.json?ctoken='.$token[1],[
                                'billUserId' => $alipayId,
                                'pageNum' => 1,
                                'pageSize' => 20,
                                'startTime' => date('Y-m-d').' 00:00:00',
                                'endTime' => date('Y-m-d',strtotime('+1day')).' 00:00:00',
                                'status' => 'ALL',
                                'sortType' => 0,
                                '_input_charset' => 'gbk'
                                ],$aliCookie);
                                preg_match('/"availableBalance":"(.*?)"/', $json, $trStr);
                		    }
                		}
                
                		if($passageway == 3){
                		    $json = get_curl('https://uemprod.alipay.com/service.json?ctoken='.$token[1].'&_input_charset=utf-8&_ksTS=1640246363734&operation=mrchcenter.artisan.v2.ext.query&data=%7B%22pageSource%22%3A%22b_site_mrchenter_home_index_route%22%2C%22parameters%22%3A%7B%22appId%22%3A%22merchant_homepage_b%22%7D%7D',0,$aliCookie);
                    		preg_match('/"subTitle":"","text":"(.*?)","eyeIcon"/', $json, $trStr);
                		}
                		
                		if($passageway == 4){
                		    $json = get_curl('https://uemprod.alipay.com/service.json?operation=mrchcenter.artisan.v2.query&ctoken='.$token[1].'&_input_charset=utf-8',['data' => '{"pageSource":"fund_home_pc"}'],$aliCookie);
                		    preg_match('/"totalAmount":"(.*?)"/', $json, $trStr);
                		}
                		Db::name("setting")->where("vkey","ali_running_time")->update(array("vvalue"=>time()));
                		$money = str_replace(',','',@$trStr[1]);
                		if(!$money){
                		    $aliMoney = 1;
                		}
                    }
                    // return substr(@$json,0,200).'支付宝余额：'.@$money;
                    
            		$passFile = 'cache/' . md5('alipay') . '.tmp';
            		$str = @file_get_contents($passFile);
            		$aliArr = explode(',',$str);
            		$aliExecutionTime = Db::name("setting")->where("vkey","ali_frequency")->find()['vvalue'];//执行时间，单位秒
            		if($passageway == 5 && $aliTime <= time()-$aliExecutionTime){
            		    if(!$alipayId){
            		        echo '未填写支付宝商户号<br>';
            		    }else{
            		        $start = date('Y-m-d H:i:s',time()-$aliExecutionTime);
                		    $end = date('Y-m-d H:i:s');
                		    $data = get_curl('https://mbillexprod.alipay.com/enterprise/fundAccountDetail.json?ctoken='.$token[1],[
                            	'endDateInput' => $end,
                            	'precisionQueryKey' => 'tradeNo',
                            	'showType' => 1,
                            	'startDateInput' => $start,
                            	'billUserId' => $alipayId,
                            	'pageNum' => 1,
                            	'pageSize' => 20,
                            	'startTime' => $start,
                            	'endTime' => $end,
                            	'status' => 1,
                            	'queryEntrance' => 1,
                            	'sortTarget' => 'tradeTime',
                            	'activeTargetSearchItem' => 'tradeNo',
                            	'sortType' => 0,
                            	'total' => 21,
                            	'_input_charset' => 'gbk'
                            ],$aliCookie);
                		  $data2 = @iconv('gbk','utf-8',$data);
                		  $arrs = json_decode($data2,true);
                		  //print_r($data); 
                		  if(!empty($arrs['result']['detail'])){
                		      foreach($arrs['result']['detail'] as $v){
                		        $price = $v['tradeAmount'];
                                if($price && $price > 0 && is_numeric($v['transMemo'])){
                                    $price = round($price,2);
                                    $res2 = Db::name("setting")->where("vkey","key")->find();
                                    $key = $res2['vvalue'];
                                    $t = time();
                                    $type = 2;
                                    $transMemo = $v['transMemo'];
                                    $sign = $type.$price.$t.$transMemo.$key;
                                    get_curl($httpType.$_SERVER['HTTP_HOST'].'/appPush?type='.$type.'&price='.$price.'&t='.$t.'&transMemo='.$transMemo.'&sign='.md5($sign));
                                }
                		      }
                		  }
            		      if(strpos($data,'访问过于频繁') !== false){
            		          echo '您的访问过于频繁，请稍后重试。<br>';
            		      }
            		    }
            		  file_put_contents($passFile, $aliArr[0].',0');
            		  Db::name("setting")->where("vkey","ali_running_time")->update(array("vvalue"=>time()));
            		  get_curl('https://mrchportalweb.alipay.com/user/asset/queryData?_ksTS=1564846095488_41&_input_charset=utf-8&ctoken='.@$token[1],0,$aliCookie);
            		}

            	    if(($passageway != 5 && empty($money) && !empty($aliMoney)) || ($passageway == 5 && @$arrs['status'] == 'deny')){
            	        if(@$aliArr[1] <= 10){
            	            file_put_contents($passFile,$aliArr[0].','.(@$aliArr[1]+1));
            	        }else{
            	            Db::name("setting")->where("vkey","ali_cookie_state")->update(array("vvalue"=>0));
            	            file_put_contents($passFile, $aliArr[0].',0');
                	        $this->sendMail('cookie失效通知','支付宝cookie已失效，请及时更新！');
            	        }
                	}else if($passageway != 5 && !empty($money)){
            			$last = $aliArr[0];
            // 			echo($last."<br>".$money);
            			if($str == ''){
            				file_put_contents($passFile,$money.',0');
            			}else{
            			    if($aliArr[1] != 0){
            			        file_put_contents($passFile, $money.',0');
            			    }
            			    if($last != $money){
            			        file_put_contents($passFile, $money.',0');
            			        $price = $money - $last;
            			        if($price && $price > 0){
            			            $price = round($price,2);
                			        $res2 = Db::name("setting")->where("vkey","key")->find();
                                    $key = $res2['vvalue'];
                                    $t = time();
                                    $type = 2;
                                    $sign = $type.$price.$t.$key;
                			        get_curl($httpType.$_SERVER['HTTP_HOST'].'/appPush?type='.$type.'&price='.$price.'&t='.$t.'&sign='.md5($sign));
            			        }
            			    }
            			}
                	}
                	
                	$aliHome = Db::name("setting")->where("vkey","ali_home")->find()['vvalue'];
                    if($aliHome <= time()-60){
                        get_curl($ref[array_rand($ref)],0,$aliCookie);
                        Db::name("setting")->where("vkey","ali_home")->update(array("vvalue"=>time()));
                    }
                }
            }
        }else{
            echo '支付宝cookie失效<br>';
        }
        
        $qqExecutionTime = Db::name("setting")->where("vkey","qq_frequency")->find()['vvalue'];//执行时间，单位秒
        $passFile = 'cache/' . md5('qq') . '.tmp';
    	$qqStr = @file_get_contents($passFile);
        $qqCookieState = Db::name("setting")->where("vkey","qq_cookie_state")->find()['vvalue'];
        if($qqCookieState == 1){
            $qqCookie = Db::name('setting')->where('vkey','qq_cookie')->find()['vvalue'];
            if($qqCookie){
                $qqTime = Db::name('setting')->where('vkey','qq_running_time')->find()['vvalue'];
                if($qqTime <= time()-$qqExecutionTime){
                    $qqCookie = base64_decode($qqCookie);
                    preg_match('/uin=o(.*?);/',$qqCookie, $uin);
                    
                    $data = get_curl('https://myun.tenpay.com/cgi-bin/clientv1.0/qwallet_record_list.cgi?limit=15&offset=0&s_time='.date('Y-m-d').'&ref_param=&source_type=7&time_type=0&bill_type=3&uin='.$uin[1],0,$qqCookie);
        			$arr = json_decode($data, true);

        			if ($arr['retcode'] != '0' && $arr['retmsg'] != 'OK') {
        				if($qqStr <= 10){
            	            file_put_contents($passFile,(empty($qqStr) ? 1 : $qqStr+1));
            	        }else{
            	            Db::name("setting")->where("vkey","qq_cookie_state")->update(array("vvalue"=>0));
            	            file_put_contents($passFile, 0);
                	        $this->sendMail('cookie失效通知','QQcookie已失效，请及时更新！');
            	        }
        			}else if(!empty($arr['records'])){
                        foreach ($arr['records'] as $v){
                            if($v['pay_time'] <= date('Y-m-d H:i:s') && $v['pay_time'] >= date('Y-m-d H:i:s',time()-$qqExecutionTime)){
                                $price = $v['price'] / 100;
                                if($price && $price > 0){
                			        $res2 = Db::name("setting")->where("vkey","key")->find();
                                    $key = $res2['vvalue'];
                                    $t = time();
                                    $type = 3;
                                    $sign = $type.$price.$t.$key;
                			        get_curl($httpType.$_SERVER['HTTP_HOST'].'/appPush?type='.$type.'&price='.$price.'&t='.$t.'&sign='.md5($sign));
            			        }
                            }
                        }
                        if($qqStr != 0){
                            file_put_contents($passFile, 0);
                        }
        			}
                    Db::name("setting")->where("vkey","qq_running_time")->update(array("vvalue"=>time()));
                }    
                
                $qqHome = Db::name("setting")->where("vkey","qq_home")->find()['vvalue'];
                if($qqHome <= time()-60){
                    get_curl('https://www.tenpay.com/v4/index.html',0,$qqCookie);
                    Db::name("setting")->where("vkey","qq_home")->update(array("vvalue"=>time()));
                }
            }else{
                echo 'QQcookie为空<br>';
            }
        }else{
            echo 'QQcookie失效<br>';
        }
    }
    
    protected function sendMail($name,$content)
    {
        $eFromName = Db::name("setting")->where("vkey","e_from_name")->find()['vvalue'];
        $ePassword = Db::name("setting")->where("vkey","e_password")->find()['vvalue'];
        $email = Db::name("setting")->where("vkey","email")->find()['vvalue'];
        $httpType = ((isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] == 'on') || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] == 'https')) ? 'https://' : 'http://';
        
        $mailName = $name.' - '.$eFromName;
        $mailContent = $content;
        $url = $httpType.$_SERVER['HTTP_HOST'].'/mail/api.php?name='.$mailName.'&content='.$mailContent.'&address='.$email.'&sign='.md5($mailName.$mailContent.$email.$ePassword);

        $data = get_curl($url);
        $arr = json_decode($data,true);
        if($arr['code'] == 1){
            return true;
        }else{
            return false;
        }
    }
}