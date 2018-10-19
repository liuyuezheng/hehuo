<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/12
 * Time: 15:57
 */

namespace app\push\controller;


//use app\push\jssdk\Jssdk;
use app\push\model\Answer;
use app\push\model\BeescrmAppPizzaStaff;
use app\push\model\BeescrmPizzaPoints;
use app\push\model\BeescrmAppPizzaQuestion;
use app\push\model\BeescrmWechat;
use app\push\model\BeescrmWechatOut;
use app\push\model\Relation;
use app\push\model\BeescrmPizzaRoom;
use app\push\model\Topic;
use app\push\model\BeescrmPizzaDan;
use app\push\model\User;
use app\push\model\UserToken;
use GatewayWorker\Lib\Gateway;
use think\Controller;
use think\Db;
use think\Request;
use think\worker\Server;

class push extends Controller
{

    public function bind()
    {
        // 设置GatewayWorker服务的Register服务ip和端口，请根据实际情况改成实际值
        Gateway::$registerAddress = '127.0.0.1:1238';
       $uid = request()->param('uid');
        $room_id = request()->param('room_id');
        $client_id = request()->param('client_id');
        // client_id与uid绑定
        Gateway::bindUid($client_id, $uid);
        Gateway::joinGroup($client_id, $room_id);
        // //用户进入房
        $count=BeescrmPizzaRoom::where('room_id',$room_id)->find();
        $point=new BeescrmPizzaPoints();
        if($count['p_isstart']==0){
            $info=json_encode(['type'=>'message', 'content' =>0]);
            Gateway::sendToUid($uid,$info);
            $data=0;
        }else{
            $num=$point->countNum($room_id,$count['type']);//计算房间内有多少人
            $res=$point->ifThere($room_id,$uid,$count['type']); //用户是否在房间内
            if($res==3){
//                $info=json_encode(['type'=>'message', 'content' =>0]);
                $data=0;
            }elseif($res==0){
                if($num==$count['counts']){
                    $info=json_encode(['type'=>'message', 'content' =>0]);
                    Gateway::sendToUid($uid,$info);
                    return $this->error('人数以满','');
                }else{
                    //房间增加人
                    $aa=$point->addNum($room_id,$uid,$count['type']);
                    $data=$point->infoList2($room_id,$uid,$count['type']);
                }
            }else{
                //查询房间内用户信息
                $data=$point->infoList2($room_id,$uid,$count['type']);
//                $info=json_encode(['type'=>'message', 'content' =>$data]);
//                Gateway::sendToGroup($room_id,$info);
//                return $this->success('请求成功','',$data);
            }
            $info=json_encode(['type'=>'message', 'content' =>$data]);
            Gateway::sendToGroup($room_id,$info);
        }

        return $this->success('请求成功','',$data);
    }
    //在线答题人数
    public function onlineAnswer(){
        $point=new BeescrmPizzaPoints();
        $data=$point->onlineAn();
        return $this->success('请求成功','',$data);
    }
    //点击去挑战
    // 个人 type=1   题目量  num=5
    // 团队 type=2   题目量  num=10
    public function challenge(){
        // Gateway::$registerAddress = '47.96.8.108:1238';

       $uid = request()->param('uid');
        $type = request()->param('type');
        $point=new BeescrmPizzaPoints();
        $res=$point->clickChallenge($type,$uid);
//        if($res['code']==1){
//            $data['room_id']=$res['room_id'];
//        }else{
//            $data['room_id']=$res['room_id'];
//        }
        return $this->success('请求成功','',$res);
    }
    public function delRooms(){
        $room_id = request()->param('room_id');
        $res=BeescrmPizzaRoom::where('room_id',$room_id)->update(['p_isstart'=>0]);
        $res2=BeescrmPizzaPoints::where('room_id',$room_id)->update(['isend'=>1]);
        if($res&$res2){
            $data=1;
          return $this->success('删除房间成功','',$data);
        }else{
            $data=0;
            return $this->error('操作失败','',$data);
        }
    }
    //开启游戏
    public function startGame(){
//        Gateway::$registerAddress='127.0.0.1:1238';
        Gateway::$registerAddress = '127.0.0.1:1238';
        $uid = request()->param('uid');

        $room_id=request()->param('room_id');
//        $client_id = request()->param('client_id');
//        // client_id与uid绑定
//        Gateway::bindUid($client_id, $uid);
//        Gateway::joinGroup($client_id, $room_id);
//        $count=Room::where('room_id',$room_id)->find();
        //是否开启
        $point=new BeescrmPizzaPoints();
        $res=$point->startGames($uid,$room_id);
//            return $this->error('还有人未进入房间','');
       if($res==0){
           $info=json_encode(['type'=>'message', 'content' =>0]);
           Gateway::sendToUid($uid,$info);
            return $this->error('等待房主开启','',$res);
        }else{
           $info=json_encode(['type'=>'message', 'content' =>1]);
           Gateway::sendToGroup($room_id,$info);
           return $this->success('开启成功，进入答题模式','',$res);
        }
    }
    public function tj() {

        $appid = 'wxbd25e61a0ca3f524';
        $redirect_uri = urlencode("http://bsk.mumarenkj.com/push/push/openid");
        $url = "https://open.weixin.qq.com/connect/oauth2/authorize?appid=$appid&redirect_uri=$redirect_uri&response_type=code&scope=snsapi_userinfo&state=1#wechat_redirect";
        header("Location:" . $url);
    }
    public function openid(){
        $code = $_GET["code"];
        $appid = "wxbd25e61a0ca3f524";
        $secret = "6616135451128d730a8e3082cdc52025";
        $url = "https://api.weixin.qq.com/sns/oauth2/access_token?appid=$appid&secret=$secret&code=" . $code . "&grant_type=authorization_code";
        $abs = file_get_contents($url);
        //return $abs;
//        $obj = json_decode($abs);
//        $obj = json_encode($abs);
//        $openid = $obj->openid;
        return $abs;
    }
    /*appid: wxbd25e61a0ca3f524
      appsecret: 6616135451128d730a8e3082cdc52025*/
    public function weixin(){
//        Db::name("beescrm_pizza_count")->insert(['count'=>111]);
        //从测试服进入
        $mWechat = new BeescrmWechatOut();
        $wechatInfo = $mWechat->where('wechat_id',36)->find();
//        return $this->success('开启成功，进入答题模式','',$wechatInfo);
//        $way = $this->_get('way');
        $room_id =request()->param('room_id');
        $type =request()->param('type');
//
        if (empty($wechatInfo)) {
            return $this->error('公众号信息丢失','');
        }
        if (request()->param('code')) {
            $authCode =request()->param('code');
            // 获取openid
            $url = 'https://api.weixin.qq.com/sns/oauth2/access_token?appid=' . $wechatInfo['appid'] . '&secret=' . $wechatInfo['appsecret'] . '&code=' . $authCode . '&grant_type=authorization_code';
            $array = json_decode(file_get_contents($url), true);
            $access_token=$array['access_token'];

           $url2='http://yunying.greencampus.cc/activity/pizza/userInfo/act_id/1/room_id='.$room_id.'/type/'.$type;
            return $this->redirect($url2);
        } else {
            $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' || $_SERVER['SERVER_PORT'] == 443) ? "https://" : "http://";
            $redirectUrl = $protocol . $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
            //
            $url = 'https://open.weixin.qq.com/connect/oauth2/authorize?appid=' . $wechatInfo['appid'] . '&redirect_uri=' . urlencode($redirectUrl) . '&response_type=code&scope=snsapi_base&state=repair#wechat_redirect';
            return $this->redirect($url);
//            header('Location:' . $url);
//            exit;
//            $authCode =request()->param('code');
        }

    }

//    //个人答题
//    public function answerQuestions(){
////        Gateway::$registerAddress = '47.96.8.108:1238';
//           $uid = request()->param('uid');
//        $anid=request()->param('answer_id');//选择id
//        $correct=request()->param('type');//正确性
//        $room=request()->param('room_id');//房间id
//        $topic=request()->param('topic_id');//题目id
//        $pktime=request()->param('pktime');//pk时间
//        $where=[];
//        if($room){
//            $where['room_id']= ['=',$room];
//        }
//        if($topic){
//            $where['topic_id']=['=',$topic];
//        }
//        $count=Room::where('room_id',$room)->find();
////        $data=[];
//        $point=new Points();
//        if($count['type']==1){
//            $res=Relation::where($where)->where('user_id',$uid)->find();
//             $data=$point->infoList($room,$uid,$count['type']);
//            if($res){
//                return $this->error('已答题','',$data);
//            }else{
//                $data['correct']=$point->correctNess($room,$uid,$correct,$topic,$anid,$count['type'],$pktime);
//            }
//        }else{
//            $res=Relation::where($where)->find();
//            $data=$point->infoList($room,$uid,$count['type']);
//            if($res){
//                return $this->error('已抢答','',$data);
//            }else{
//                $data['correct']=$point->correctNess($room,$uid,$correct,$topic,$anid,$count['type'],$pktime);
//            }
//        }
//        return $this->success('请求成功','',$data);
//    }
    //用户信息
    public function listInfo(){
        $uid = request()->param('uid');
        $points=new BeescrmPizzaPoints();
        $room = request()->param('room_id');
        $count=BeescrmPizzaRoom::where('room_id',$room)->find();
        $data=$points->infoList2($room,$uid,$count['type']);
        return $this->success('请求成功','',$data['users']);
    }
    public function listInfo2(){
        $uid = request()->param('uid');
        $points=new BeescrmPizzaPoints();
        $room = request()->param('room_id');
        $count=BeescrmPizzaRoom::where('room_id',$room)->find();
        $data=$points->infoList($room,$uid,$count['type']);
        return $this->success('请求成功','',$data['users']);
    }
    //个人答题完毕
     /*uid 用户id
      * room_id 房间id
      * point pk积分
      * pktime pk时间
      * num 答对题目数量
      *
      * */

     public function results(){
         $uid = request()->param('uid');
         $room = request()->param('room_id');
         $point=request()->param('point');
         $pktime=request()->param('pktime');
         $num=request()->param('num');
         $count=BeescrmPizzaRoom::where('room_id',$room)->find();
         $points=new BeescrmPizzaPoints();
         $data=$points->updatePoint($uid,$room,$point,$pktime,$num,$count['type']);
         return $this->success('请求成功','',$data);
     }
     //结束页
    public function answerEnd(){
//        Gateway::$registerAddress = '47.96.8.108:1238';
        $uid = request()->param('uid');
        $points=new BeescrmPizzaPoints();
        $room = request()->param('room_id');
//        $client_id = request()->param('client_id');
        $count=BeescrmPizzaRoom::where('room_id',$room)->find();
        $arr=$points->infoList($room,$uid,$count['type']);
        $res=$points->calculate($arr['users'],$count['type']);
        $data=$points->infoList($room,$uid,$count['type']);
        return $this->success('请求成功','',$data);
    }
    /*
    //选择项及题目
    num 题目数量
    */
    public function chooseList(){
         $room_id=request()->param('room_id');
         $res=BeescrmPizzaRoom::where('room_id',$room_id)->find();
         $tid=explode(",",$res['topic']);
        for($i=0;$i<count($tid);$i++){
//            $str=$str.",".$tmp[$res[$i]];

            $arr=BeescrmAppPizzaQuestion::where('question_id',$tid[$i])
                ->find();
            $info['question_id']=$arr['question_id'];
            $info['content']=$arr['content'];
            $info['answer']=json_decode($arr['answer']);
            $info['right']=$arr['right'];
            $data[]=$info;
        }
        return $this->success('请求成功','',$data);
    }
    //分享sign_url
    /*appid: wxbd25e61a0ca3f524
      appsecret: 6616135451128d730a8e3082cdc52025
    */
    public function share_wx(){
        vendor('Jssdks.jssdk');
        $jssdk = new \JSSDK("wxbd25e61a0ca3f524", "6616135451128d730a8e3082cdc52025");
        $signPackage = $jssdk->GetSignPackage();
        return $this->success('请求成功','',$signPackage);
    }
    public function danWei(){
        $uid = request()->param('uid');
        $user=new BeescrmAppPizzaStaff();
        $res=$user->where('staff_id',$uid)->find();
        $data['dan']=BeescrmPizzaDan::select();
        $data['pizza_num']=$res['pizza_num'];
        return $this->success('请求成功','',$data);
    }
    //排位页
    public function qualifying($page=1,$size=20){
//        $data=[];
//        $uid=request()->param('uid');
//     $list=BeescrmAppPizzaStaff::with('dan')->order('dan_id desc,pizza_num desc')->select();
        $list=BeescrmAppPizzaStaff::with('dan')->order('dan_id desc,pizza_num desc')->paginate($size,false,['page'=>$page])->toArray();
//     $num=0;
        $res['total']=$list['total'];
        $res['per_page']=$list['per_page'];
        $res['current_page']=$list['current_page'];
        $res['last_page']=$list['last_page'];
     foreach ($list['data'] as $k=>$v){
//         $num+=1;
         $data['staff_id']=$v['staff_id'];
         $data['staff_number']=$v['staff_number'];
         $data['staff_name']=$v['staff_name'];
         $data['pizza_num']=$v['pizza_num'];
         $data['personal_score']=$v['personal_score'];
         $data['group_score']=$v['group_score'];
         $data['dan_id']=$v['dan_id'];
         $data['headimgurl']=$v['headimgurl'];
         foreach ($v['dan'] as $val){
             $data['dan_name']=$val['dan_name'];
             $data['img']=$val['img'];
         }
//         $data['num']=$num;
         $res['data'][]=$data;
//         for
//         $data['dan_name']=$v['dan'][0]['dan_name'];
//         $data['num']=$num;
//         if($uid==$v['staff_id']){
//            $res['myself'][]=$data;
//         }
//         $res['all'][]=$data;
     }
     return $this->success('请求成功','',$res);
    }
    public function updan(){
//        set_time_limit(0);
        $info=BeescrmAppPizzaStaff::where('staff_id','BETWEEN','17001,17500')->select();
        $dan=new BeescrmPizzaDan();
        foreach ($info as $k=>$v){
          $num=$dan->iddan($v['pizza_num']);
          BeescrmAppPizzaStaff::where('staff_id',$v['staff_id'])->update(['dan_id'=>$num]);
        }
        return $this->success('请求成功','',$info);
    }
    //判断用户段位
//    public function iddan(){
////        $uid = request()->param('uid');
//        $pizza_num=request()->param('pizza_num');
//        $res=BeescrmPizzaDan::where('min_count','<=',$pizza_num)->where('max_count','>=',$pizza_num)->find();
//        if($res){
//            $data=$res['dan_id'];
//        }else{
//            $data=0;
//        }
//        return $this->success('请求成功','',$data);
//    }
//    protected $socket = 'websocket://127.0.0.1:2346';
//    /**
//     * 收到信息
//     * @param $connection
//     * @param $data
//     */
//    public function onMessage($connection, $data)
//    {
//        $connection->send('我收到你的信息了');
//    }
//
//    /**
//     * 当连接建立时触发的回调函数
//     * @param $connection
//     */
//    public function onConnect($connection)
//    {
//
//    }
//
//    /**
//     * 当连接断开时触发的回调函数
//     * @param $connection
//     */
//    public function onClose($connection)
//    {
//
//    }
//    /**
//     * 当客户端的连接上发生错误时触发
//     * @param $connection
//     * @param $code
//     * @param $msg
//     */
//    public function onError($connection, $code, $msg)
//    {
//        echo "error $code $msg\n";
//    }
//
//    /**
//     * 每个进程启动
//     * @param $worker
//     */
//    public function onWorkerStart($worker)
//    {
//
//    }
}