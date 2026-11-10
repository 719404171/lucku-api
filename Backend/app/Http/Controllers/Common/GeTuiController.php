<?php
/*
 * File: GeTuiController.php
 * Created Time: 2024-05-29 11:27:52 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-08-01 4:22:45 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2024 成都睿創科技有限公司
 */


namespace App\Http\Controllers\Common;


use App\Http\Controllers\Controller;

class GeTuiController extends Controller{
    private $AppID;
    private $AppSecret;
    private $AppKey;
    private $MasterSecret;
    private $HOST;

    public function __construct(){
        $this->AppID = app('global_app_id');
        $this->AppSecret = app('global_app_secret');
        $this->AppKey = app('global_app_key');
        $this->MasterSecret = app('global_master_secret');
        $this->HOST = app('global_host');

    }

    /**
     * @param $cid 用户cid
     * @param $title 推送标题
     * @param $content 推送内容
     * @param int $type 1：单推；2：批量推
     * @param null $parameter 自定义参数（数组）
     * @return mixed|null
     */
    public function pushToSingleByCid($cid, $title, $content, $type = 1, $parameter = null) {
        $api = new \GTClient($this->HOST, $this->AppKey, $this->AppID, $this->MasterSecret);
        $push = new \GTPushRequest();
        $requestId = md5(uniqid());

        // 处理payload参数
        $payload = empty($parameter) ? '{}' : json_encode($parameter);

        $push->setRequestId($requestId);

        // 在线消息 - 透传
        $message = new \GTPushMessage();
        $transmissionData = json_encode([
            'title' => $title,
            'content' => $content,
            'payload' => $payload
        ]);
        $message->setTransmission($transmissionData);
        $push->setPushMessage($message);

        // 离线消息 - 通知
        $channel = new \GTPushChannel();

        // Android配置
        $ups = new \GTUps();
        $notify = new \GTThirdNotification();
        $notify->setTitle($title);
        $notify->setBody($content);
        $notify->setClickType("intent");

        // 确保payload正确编码在intent中
        $encodedPayload = urlencode($payload);
        $intent = "intent://com.getui.push/detail?#Intent;scheme=gtpushscheme;launchFlags=0x4000000;package=com.sccbv.jieyou;component=com.sccbv.jieyou/io.dcloud.PandoraEntry;S.UP-OL-SU=true;S.title=".urlencode($title).";S.content=".urlencode($content).";S.payload=".$encodedPayload.";end";
        $notify->setIntent($intent);

        $ups->setNotification($notify);
        $android = new \GTAndroid();
        $android->setUps($ups);
        $channel->setAndroid($android);

        // iOS配置
        $ios = new \GTIos();
        $ios->setType('notify');

        $aps = new \GTAps();
        $alert = new \GTAlert();
        $alert->setTitle($title);
        $alert->setBody($content);

        $aps->setAlert($alert);
        $aps->setContentAvailable(0);
        $aps->setSound('default');
        $aps->setCategory('');

        $ios->setAps($aps);
        // 注意：payload不应该覆盖通知内容
        $ios->setPayload($transmissionData); // 使用与透传相同的数据

        $channel->setIos($ios);
        $push->setPushChannel($channel);

        // 执行推送
        if($type == 1) {
            $push->setCid($cid);
            $result = $api->pushApi()->pushToSingleByCid($push);
        } else if($type == 2) {
            $taskid = $api->pushApi()->createListMsg($push);
            if(!is_array($cid)) {
                $cid = [$cid];
            }
            $push->setCidList($cid);
            $push->setTaskId($taskid['data']['taskid']);
            $result = $api->pushApi()->pushListByCid($push);
        }

        return $result;
    }















}
