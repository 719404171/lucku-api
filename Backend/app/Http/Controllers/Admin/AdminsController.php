<?php
/*
 * File: AdminsController.php
 * Created Time: 2024-05-29 11:27:52 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2024-05-30 4:13:42 pm
 * Modified By: WangYuQiang && WangpeiGang (719404171@qq.com>)
 * -----
 * Copyright (c) 2024 成都睿創科技有限公司
 */


namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Common\AdminTokenController;
use App\Http\Controllers\Controller;
use App\Models\AdminLog;
use App\Models\Admins;
use App\Models\Order;
use App\Models\Role;
use App\Models\User;
use App\Models\UserMessage;
use Illuminate\Http\Request;
use OSS\Core\OssException;
use OSS\OssClient;

class AdminsController extends Controller
{
    protected $Admins;
    private $Date;

    public function __construct()
    {
        $this->Admins = new Admins();
        $this->Date = [
            [date('Y-m-d 00:00:00', strtotime('-' . 0 . " day")), date('Y-m-d 23:59:59', strtotime('-' . 0 . " day"))],
            [date('Y-m-d 00:00:00', strtotime('-' . 1 . " day")), date('Y-m-d 23:59:59', strtotime('-' . 1 . " day"))],
            [date('Y-m-d 00:00:00', strtotime('-' . 7 . " day")), date('Y-m-d 23:59:59', strtotime('-' . 0 . " day"))],
            [date('Y-m-d 00:00:00', strtotime('-' . 30 . " day")), date('Y-m-d 23:59:59', strtotime('-' . 0 . " day"))]
        ];
    }

    /**
     * 登錄
     * @param Request $request
     * @return array
     */
    public function AdminLoginWeb(Request $request){
        if(empty($request->post('username')) || empty($request->post('password'))){
            return $this->parametersError();
        }else{
            $hasAdmin = Admins::where('username',$request->post('username'))->where('password',md5($request->post('password')))->first();
            if(!empty($hasAdmin)){
                $token = AdminTokenController::createToken($hasAdmin['admin_id'], "");
                $state = Admins::where('admin_id',$hasAdmin['admin_id'])->update(['token'=>$token]);
                $adminLog = [
                    'login_admin_id' => $hasAdmin['admin_id'],
                    'login_admin_name' => $hasAdmin['username'],
                    'login_ip' => $request->ip(),
                    'created_at' => date('Y-m-d H:i:s')
                ];
                // 解析登錄 IP 歸屬地（失敗不影響登錄）
                $location = self::resolveIpLocation($request->ip());
                $adminLog = array_merge($adminLog, $location);
                $addAdminLog = AdminLog::insert($adminLog);
                if($state && $addAdminLog){
                    return $this->success(__('web.success'),['token'=>$token]);
                }else{
                    return $this->error();
                }
            }else{
                return $this->error(__('web.login_error'));
            }
        }
    }

    /**
     * 获取管理员权限及菜单
     * @param Request $request
     * @return array
     */
    public function getAdminMenuWeb(Request $request){
        $token = $request->header('TOKEN');
        $language = self::getLanguageWeb();
        $data = $this->Admins->getAdminMenuWeb($token,$language);
        return $this->success(__('web.success'),$data);
    }

    /**
     * 获取管理员信息
     * @param Request $request
     * @return array
     */
    public function getAdminInfoWeb(Request $request){
        $token = $request->header('TOKEN');
        $data = Admins::where('token',$token)->first();
        return $this->success(__('web.success'),$data);
    }

    /**
     * 获取管理員列表
     * @param Request $request
     * @return array
     */
    public function getAdminListWeb(Request $request){
        $data = $request->post();
        $token = $request->header('TOKEN');
        $language = self::getLanguageWeb();
        $datas = $this->Admins->getAdminListWeb($data,$token,$language);
        return $this->success(__('web.success'),$datas);
    }

    /**
     * 新增管理員
     * @param Request $request
     * @return array
     */
    public function addAdminWeb(Request $request){
        $adminInfo = $request->except('role_id');
        $role_id = $request->post('role_id');
        $data = $this->Admins->addAdminWeb($adminInfo,$role_id);
        return $data;
    }

    /**
     * 編輯管理員
     * @param Request $request
     * @return array
     */
    public function editAdminWeb(Request $request){
        $adminInfo = $request->except('role_id');
        $role_id = $request->post('role_id');
        $data = $this->Admins->editAdminWeb($adminInfo,$role_id);
        return $data;
    }

    /**
     * 解析 IP 歸屬地（使用 ip-api.com，免費無需 key）
     * 失敗時返回空值，不影響登錄流程
     * @param string $ip
     * @return array
     */
    private static function resolveIpLocation($ip){
        $result = [
            'login_nation' => '',
            'login_region' => '',
            'login_city' => '',
            'login_lng' => '',
            'login_lat' => '',
        ];
        try {
            // 內網/本機 IP 不解析
            if(empty($ip) || !filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)){
                return $result;
            }
            $ctx = stream_context_create(['http' => ['timeout' => 3]]);
            $json = @file_get_contents("http://ip-api.com/json/{$ip}?lang=zh-CN&fields=status,country,regionName,city,lat,lon", false, $ctx);
            if($json){
                $data = json_decode($json, true);
                if(!empty($data) && ($data['status'] ?? '') === 'success'){
                    $result['login_nation'] = $data['country'] ?? '';
                    $result['login_region'] = $data['regionName'] ?? '';
                    $result['login_city'] = $data['city'] ?? '';
                    $result['login_lng'] = isset($data['lon']) ? (string)$data['lon'] : '';
                    $result['login_lat'] = isset($data['lat']) ? (string)$data['lat'] : '';
                }
            }
        } catch (\Exception $e) {
            // 忽略，歸屬地解析失敗不影響登錄
        }
        return $result;
    }

    /**
     * 修改當前登錄管理員密碼
     * @param Request $request
     * @return array
     */
    public function editAdminPasswordWeb(Request $request){
        $oldPassword = $request->post('old_password');
        $newPassword = $request->post('new_password');
        if(empty($oldPassword) || empty($newPassword)){
            return $this->parametersError();
        }
        if(strlen($newPassword) < 6){
            return $this->error(__('web.password_too_short'));
        }
        $token = $request->header('TOKEN');
        $admin = Admins::where('token',$token)->first();
        if(empty($admin)){
            return $this->error(__('web.login_error'));
        }
        if($admin->password !== md5($oldPassword)){
            return $this->error(__('web.old_password_error'));
        }
        $state = Admins::where('admin_id',$admin->admin_id)->update(['password'=>md5($newPassword),'token'=>'']);
        if($state){
            return $this->success(__('web.edit_success'));
        }else{
            return $this->error(__('web.edit_fail'));
        }
    }

    /**
     * 刪除管理員
     * @param Request $request
     * @return array
     */
    public function deleteAdminWeb(Request $request){
        $adminID = $request->post('adminID');
        $data = $this->Admins->deleteAdminWeb($adminID);
        return $data;
    }

    /**
     * 上傳圖片
     * @param Request $request
     * @return array
     */
    public function uploadImageWeb(Request $request){
        $accessKeyId = config('services.aliyun_oss.access_key_id');
        $accessKeySecret = config('services.aliyun_oss.access_key_secret');
        // Endpoint以杭州为例，其它Region请按实际情况填写。
        $endpoint = config('services.aliyun_oss.endpoint');
        // 存储空间名称
        $bucket = config('services.aliyun_oss.bucket');
        // 文件名称
        $filePath = $request->file("file");
        $object = date("Y-m-d") . "/" . time() .rand(111111,999999). "." . $filePath->getClientOriginalExtension();
        try {
            $ossClient = new OssClient($accessKeyId, $accessKeySecret, $endpoint);
            $ossClient->uploadFile($bucket, $object, $filePath);
            $results['file_path']=config('services.aliyun_oss.url') . '/' . $object;
            return $this->success( __('api.picture_success'), $results);
        } catch (OssException $e) {
            return $this->Error(__('api.picture_error'), $e);
        }
    }

    /**
     * 获取首頁數據
     * @return mixed
     */
    public function getHomeDataWeb(){
        $data['user'] = $this->getUserNumber();
        $data['user_message'] = $this->getUserMessage();
        $data['sales_volume'] = $this->getSalesVolume();
        $data['order'] = $this->getOrderAnalysis();
        return $data;
    }

    /**
     * 獲取用戶數據
     * @return mixed
     */
    public function getUserNumber(){
        $data['count'] = User::where('user_status', '<>', -1)->where('user_id','<>',1)->count();

        $dataInfo = [];
        for ($i = 0; $i < count($this->Date); $i++) {
            $count = User::whereBetween('created_at', $this->Date[$i])->where('user_status', '<>', -1)->where('user_id','<>',1)->count();
            array_push($dataInfo, $count);
        }
        array_push($dataInfo, User::where('user_status', '<>', -1)->where('user_id','<>',1)->count());
        $data['data'] = $dataInfo;
        return $data;
    }

    /**
     * 獲取用戶留言數據
     * @return mixed
     */
    public function getUserMessage(){
        $data['count'] = UserMessage::join('users','users.user_id','=','user_message.user_id')->where('users.user_status','<>',-1)->where('user_message.message_status', '<>', -1)->count();

        $dataInfo = [];
        for ($i = 0; $i < count($this->Date); $i++) {
            $count = UserMessage::join('users','users.user_id','=','user_message.user_id')->where('users.user_status','<>',-1)->whereBetween('user_message.created_at', $this->Date[$i])->where('message_status', '<>', -1)->count();
            array_push($dataInfo, $count);
        }
        array_push($dataInfo, UserMessage::join('users','users.user_id','=','user_message.user_id')->where('users.user_status','<>',-1)->where('user_message.message_status', '<>', -1)->count());
        $data['data'] = $dataInfo;
        return $data;
    }

    /**
     * 獲取銷售額數據
     * @return mixed
     */
    public function getSalesVolume(){
        $data['count'] = Order::join('users','users.user_id','=','order.user_id')->where('users.user_status','<>',-1)->where('payment_status', '>=', 3)->where('order_status','<>',-1)->sum('order_money');

        $dataInfo = [];
        for ($i = 0; $i < count($this->Date); $i++) {
            $count = Order::join('users','users.user_id','=','order.user_id')->where('users.user_status','<>',-1)->whereBetween('payment_at', $this->Date[$i])->where('payment_status', '>=', 3)->where('order_status','<>',-1)->sum('order_money');
            array_push($dataInfo, $count);
        }
        array_push($dataInfo, Order::join('users','users.user_id','=','order.user_id')->where('users.user_status','<>',-1)->where('payment_status', '>=', 3)->where('order_status','<>',-1)->sum('order_money'));
        $data['data'] = $dataInfo;
        return $data;
    }

    /**
     * 獲取訂單數據
     * @return mixed
     */
    public function getOrderAnalysis(){
        $data['count'] = Order::join('users','users.user_id','=','order.user_id')->where('users.user_status','<>',-1)->where('payment_status', '>=', 3)->where('order_status','<>',-1)->count();

        $dataInfo = [];
        for ($i = 0; $i < count($this->Date); $i++) {
            $count = Order::join('users','users.user_id','=','order.user_id')->where('users.user_status','<>',-1)->whereBetween('payment_at', $this->Date[$i])->where('payment_status', '>=', 3)->where('order_status','<>',-1)->count();
            array_push($dataInfo, $count);
        }
        array_push($dataInfo, Order::join('users','users.user_id','=','order.user_id')->where('users.user_status','<>',-1)->where('payment_status', '>=', 3)->where('order_status','<>',-1)->count());
        $data['data'] = $dataInfo;
        return $data;
    }

}
