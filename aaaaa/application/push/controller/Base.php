<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/12
 * Time: 17:02
 */

namespace app\push\controller;

use app\push\model\UserToken;
use think\Controller;

class Base extends Controller
{
    public function _initialize()
    {
        parent::_initialize(); // TODO: Change the autogenerated stub
        $ut = new UserToken();
        $uid =$ut->getCurrentUid();//取id
        if($uid){
            return true;
        }else{
            return $this->error('登录失败');
        }
    }
//header('Access-Control-Allow-Origin: *');
//header("Access-Control-Allow-Headers: token, Origin, X-Requested-With, Content-Type, Accept");
//header('Access-Control-Allow-Methods: GET, POST, PUT,DELETE,OPTIONS,PATCH');
}