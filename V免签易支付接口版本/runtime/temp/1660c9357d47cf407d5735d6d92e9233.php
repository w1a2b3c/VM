<?php /*a:1:{s:26:"../public/admin/login.html";i:1640244011;}*/ ?>

<!DOCTYPE HTML>

<html>
<head>
	<title>至尊码支付</title>
	<meta name="keywords" content="" />
	<meta name="description" content="" />
	<meta charset="utf-8" />
	<link rel="shortcut icon" href="favicon.ico" />
	<link rel="bookmark" href="favicon.ico" />
	<meta name="viewport" content="width=device-width, initial-scale=1, user-scalable=no" />
	<link rel="stylesheet" href="/assets/css/main.css" />
	<noscript><link rel="stylesheet" href="/assets/css/noscript.css" /></noscript>
</head>
<body>
<div id="wrapper">

	<header id="header">
		<div class="logo">
			<span class="icon fa-rmb"></span>
		</div>
		<div class="content">
			<div class="inner">
				<h1>至尊码支付</h1>

				<p>个人用户收款解决方案</p>
			</div>
		</div>
		<nav>
			<ul>
				<li><a href="#login">登录</a></li>
			</ul>
		</nav>
	</header>

	<div id="main">
		<article id="login">
			<h2 class="major">登录</h2>
			<form method="post" action="javascript:login();">
				<div class="field half first">
					<label for="user">账号</label>
					<input type="text" name="user" id="user" />
				</div>
				<div class="field half">
					<label for="pass">密码</label>
					<input type="password" name="pass" id="pass" />
				</div>

				<ul class="actions">
					<li><input id="login2" type="submit" value="登录" class="special" /></li>
					<li><input type="reset" value="清空" /></li>
				</ul>
			</form>
			<br>
			<div id="result" role="dialog">
				<p color class="h5 margin-top-sm text-black-hint" id="msg"></p>
			</div>
		</article>

	</div>

	<footer id="footer">
		<p class="copyright">&copy;<script>document.write((new Date).getFullYear());</script> 至尊码支付</p>
	</footer>

</div>

<div id="bg"></div>

<script src="https://cdn.jsdelivr.net/npm/jquery@1.11.3"></script>
<script src="https://cdn.jsdelivr.net/gh/ajlkn/skel@3.0.1/dist/skel.min.js"></script>
<script src="/assets/js/util.js"></script>
<script src="/assets/js/main.js"></script>
<script src="https://lib.baomitu.com/layer/3.1.1/layer.js"></script>

<style>
	.outtext{
		color: red;
	}
</style>
<script>
	try {
		$.post("index/index/getReturn",function (data) {});
		$.ajax({
			type:"POST",
			url:"index/index/getReturn",
			success:function(resp,status){

			},
			error:function(xhr,errorText,errorType){
				if(xhr.status == 404){
					layer.open({
						type: 1,
						skin: 'outtext', //样式类名
						closeBtn: 0, //不显示关闭按钮
						anim: 2,
						title:'运行环境检测',
						content: "<div style='padding: 10px;'>检测到搭建出现问题：伪静态规则未设置！<br>请在宝塔面板-网站-设置-伪静态->选择thinkphp并保存!</div>"
					});

				}else{
					layer.open({
						type: 1,
						skin: 'outtext', //样式类名
						closeBtn: 0, //不显示关闭按钮
						anim: 2,
						title:'运行环境检测',

						content: "<div style='padding: 10px;'>检测到搭建出现问题：PHP版本必须大于或等于5.6！</div>"
					});


				}
			},
			complete:function(){
				//do something
			}
		})
	}catch (e) {
		layer.alert("检测到搭建服务端出现问题：伪静态规则未设置！<br>请在宝塔面板-网站-设置-伪静态->选择thinkphp并保存!");
	}

	function login() {
		try {
			$.post(location.href,"user="+$("#user").val()+"&pass="+$("#pass").val(),function (data) {
				layer.msg(data.msg,{time: 1000},function(){
					if (data.code == 1){
						window.location.href = "aaa.html";
					}
				});
			});
		}catch (e) {
			layer.alert("程序出错，请检查：<br>1、伪静态是否设置成功<br>2、是否设置好数据库账号密码并导入数据库脚本")
		}

	}
</script>
</body>
</html>
