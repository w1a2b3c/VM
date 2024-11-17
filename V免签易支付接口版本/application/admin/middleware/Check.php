<?php
namespace app\admin\middleware;
use think\Db;
use think\facade\Session;

class Check
{
    public function handle($request, \Closure $next)
    {
        if (!acc()){
            return json(array("code"=>-1,"msg"=>'未登录'));
        }

        return $next($request);
    }
}