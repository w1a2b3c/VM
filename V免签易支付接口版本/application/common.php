<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2006-2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 流年 <liu21st@gmail.com>
// +----------------------------------------------------------------------

// 应用公共文件

// 把数组所有元素，按照“参数=参数值”的模式用“&”字符拼接成字符串
function createLinkstring($para) {
	/*$arg  = "";
	while (list ($key, $val) = each ($para)) {
		$arg.=$key."=".$val."&";
	}
	//去掉最后一个&字符
	$arg = substr($arg,0,count($arg)-2);
	
	//如果存在转义字符，那么去掉转义
	if(get_magic_quotes_gpc()){$arg = stripslashes($arg);}
	
	return $arg;*/
	
	return urldecode(http_build_query($para));
}
        
// 对数组排序
function argSort($para) {
	ksort($para);
	reset($para);
	return $para;
}

/**
 * 检测用户是否登录
 * @return bool 
 */
function acc()
{
    if (!cookie('zpay_name') || !cookie('zpay_code') || !cookie('zpay_token')) {
        return false;
    }
    return cookie('zpay_code') == c_code(cookie('zpay_name'),cookie('zpay_token'));
}

/**
 * 加密用户名
 * @param string $name 
 * @param string $token 
 * @return string 
 */
function c_code($name,$token)
{
    $pass = Db::name("setting")->where("vkey", "pass")->find()['vvalue'];
    return md5($name.'|'.$token.'|'.$pass.'|'.c_salt());
}

function c_salt()
{
    $root = $_SERVER['DOCUMENT_ROOT'];
    $route = $root.'/../config/salt.php';
    if(is_file($route)){
        include($route);
        if(empty($salt)){
            $salt = rand_str(20,true);
            file_put_contents($route,"<?php \r\n \$salt = '{$salt}';");
        }
    }else{
        $salt = rand_str(20,true);
        file_put_contents($route,"<?php \r\n \$salt = '{$salt}';");
    }
    return $salt;
}

/**
* 获取ip地址
* @return string
*/ 
function get_real_ip() {
    $realip = '';
    if(getenv('REMOTE_ADDR')) {
        $realip = getenv('REMOTE_ADDR');
    }else if(getenv('HTTP_CLIENT_IP')) {
        $realip = getenv('HTTP_CLIENT_IP');
    }else if(getenv('HTTP_X_FROWARD_FOR')) {
        $realip = getenv('HTTP_X_FROWARD_FOR');
    } 
    return $realip; 
}
   
function isMobile()
{
    // 如果有HTTP_X_WAP_PROFILE则一定是移动设备
    if (isset ($_SERVER['HTTP_X_WAP_PROFILE'])) {
        return true;
    }
    // 如果via信息含有wap则一定是移动设备,部分服务商会屏蔽该信息
    if (isset ($_SERVER['HTTP_VIA']) && stristr($_SERVER['HTTP_VIA'], 'wap')) return true;
    // 脑残法，判断手机发送的客户端标志,兼容性有待提高
    if (isset ($_SERVER['HTTP_USER_AGENT'])) {
        $clientkeywords = array('nokia',
            'sony',
            'ericsson',
            'mot',
            'samsung',
            'htc',
            'sgh',
            'lg',
            'sharp',
            'sie-',
            'philips',
            'panasonic',
            'alcatel',
            'lenovo',
            'iphone',
            'ipod',
            'blackberry',
            'meizu',
            'android',
            'netfront',
            'symbian',
            'ucweb',
            'windowsce',
            'palm',
            'operamini',
            'operamobi',
            'openwave',
            'nexusone',
            'cldc',
            'midp',
            'wap',
            'mobile'
        );
        // 从HTTP_USER_AGENT中查找手机浏览器的关键字
        if (preg_match("/(" . implode('|', $clientkeywords) . ")/i", strtolower($_SERVER['HTTP_USER_AGENT']))) {
            return true;
        }
    }
    // 协议法，因为有可能不准确，放到最后判断
    if (isset ($_SERVER['HTTP_ACCEPT'])) {
        // 如果只支持wml并且不支持html那一定是移动设备
        // 如果支持wml和html但是wml在html之前则是移动设备
        if ((strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') !== false) && (strpos($_SERVER['HTTP_ACCEPT'], 'text/html') === false || (strpos($_SERVER['HTTP_ACCEPT'], 'vnd.wap.wml') < strpos($_SERVER['HTTP_ACCEPT'], 'text/html')))) {
            return true;
        }
    }
    return false;
}

function get_curl($url,$post = '',$cookie = '',$referer = '',$proxy = '',$header = 0,$userAgent = '')
{
   $httpheader = [];
   $curl = curl_init(); 
   // 配置curl中的http协议->可配置的荐可以查PHP手册中的curl_  
   curl_setopt($curl, CURLOPT_URL, $url);
   if($post){
      // POST数据
      curl_setopt($curl, CURLOPT_POST, 1);
      // 把post的变量加上
      if(is_array($post)){
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($post));
      }else{
        curl_setopt($curl, CURLOPT_POSTFIELDS, $post);
      }
      if($arr = @json_decode($post,true)){
         if(is_array($arr)){
            $httpheader[] = 'Content-Type: application/json; charset=utf-8';
            $httpheader[] = 'Content-Length: ' . strlen($post);
         }
      }
   }
   if($referer){
      $httpheader[] = 'Referer: '.$referer;//模拟来路
      $httpheader[] = 'Origin: '.$referer;
   }else{
      $httpheader[] = 'Referer: '.$url;//模拟来路
      $httpheader[] = 'Origin: '.$url;
   }
   if($cookie){
      $httpheader[] = 'Cookie: '.$cookie;//模拟cookie
   }
   if($proxy){
      $proxy = explode(':',$proxy);
      if(!empty($proxy[1])){
         curl_setopt($curl, CURLOPT_PROXY, $proxy[0]); //代理服务器地址
         curl_setopt($curl, CURLOPT_PROXYPORT, $proxy[1]); //代理服务器端口
      }
      $httpheader[] = 'X-FORWARDED-FOR: '.$proxy[0];//模拟ip
      $httpheader[] = 'CLIENT-IP: '.$proxy[0];//模拟ip
   }else{
      $httpheader[] = 'X-FORWARDED-FOR: '.$_SERVER['REMOTE_ADDR'];//模拟ip
      $httpheader[] = 'CLIENT-IP: '.$_SERVER['REMOTE_ADDR'];//模拟ip
   }
   if($header){
      curl_setopt($curl, CURLOPT_HEADER, TRUE);//获取响应头信息
   }
   if($userAgent){
      $httpheader[] = 'User-Agent: '.$userAgent;//模拟用户浏览器信息 
   }else{
      $httpheader[] = 'User-Agent: '.(!empty($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/86.0.4240.198 Safari/537.36');
   }
   $httpheader[] = 'Host: '.@parse_url($url)['host'];
   curl_setopt($curl, CURLOPT_HTTPHEADER, $httpheader); //模拟请求头
   curl_setopt($curl, CURLOPT_TIMEOUT,10);//只需要设置一个秒的数量就可以  
   curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);//返回字符串，而非直接输出到屏幕上
   curl_setopt($curl, CURLOPT_FOLLOWLOCATION,1);//跟踪爬取重定向页面
   curl_setopt($curl, CURLOPT_SSL_VERIFYPEER,false);
   curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,false); 
   curl_setopt($curl, CURLOPT_ENCODING, '');//解决网页乱码问题
   // 执行这个请求  
   $ret = curl_exec($curl);
   if($header){
         $headerSize = curl_getinfo($curl, CURLINFO_HEADER_SIZE);
         $header = substr($ret, 0, $headerSize);
         $body = substr($ret, $headerSize);
         $ret=array();
         $ret['header'] = $header;
         $ret['body'] = $body;
      }
   curl_close($curl);
   return $ret;
}

function rand_str($num = 6,$special = false)
{
    $str = 'abcedfghjkmnpqrstuvwxyzABCEDFGHJKMNPQRSTUVWXYZ0123456789';
    if($special){
        $str = 'abcedfghjkmnpqrstuvwxyzABCEDFGHJKMNPQRSTUVWXYZ0123456789!@#$%^&*';
    }
    return substr(str_shuffle($str), 0, $num);
}