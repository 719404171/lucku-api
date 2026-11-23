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
use App\Services\AliyunSmsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
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
     * 登錄頁配置：是否開啟短信驗證
     * @return array
     */
    public function getAdminLoginConfigWeb(){
        return $this->success(__('web.success'), [
            'sms_enabled' => (bool)config('services.aliyun_sms.enabled'),
        ]);
    }

    /**
     * 發送後台登錄短信驗證碼（先校驗帳號密碼，避免被用來刷短信）
     * @param Request $request
     * @return array
     */
    public function sendAdminLoginSms(Request $request){
        $username = trim((string)$request->post('username'));
        $password = (string)$request->post('password');
        if($username === '' || $password === ''){
            return $this->parametersError();
        }
        $smsConfig = config('services.aliyun_sms');
        if(empty($smsConfig['enabled'])){
            return $this->error(__('web.sms_not_enabled'));
        }

        $hasAdmin = Admins::where('username',$username)->where('status',1)->first();
        if(empty($hasAdmin) || !self::checkAdminPassword($hasAdmin, $password)){
            self::writeLoginFailLog($request, $username, __('web.login_error'));
            return $this->error(__('web.login_error'));
        }

        $phone = trim((string)$hasAdmin->phone);
        if(!preg_match('/^[4-9]\d{7}$/', $phone)){
            return $this->error(__('web.sms_phone_invalid'));
        }

        // 重發間隔
        $last = DB::table('admin_sms_code')->where('admin_id',$hasAdmin->admin_id)->orderByDesc('id')->first();
        if(!empty($last) && strtotime($last->created_at) > time() - (int)$smsConfig['resend_interval']){
            return $this->error(__('web.sms_too_frequent'));
        }
        // 每小時上限
        $hourCount = DB::table('admin_sms_code')
            ->where('admin_id',$hasAdmin->admin_id)
            ->where('created_at','>=',date('Y-m-d H:i:s', time() - 3600))
            ->count();
        if($hourCount >= (int)$smsConfig['hour_limit']){
            return $this->error(__('web.sms_hour_limit'));
        }

        $code = str_pad((string)random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        $send = AliyunSmsService::sendCode($phone, $code);
        if(empty($send['success'])){
            return $this->error(__('web.sms_send_fail'), $send['error'] ?? '');
        }

        // 發送成功才落庫，並作廢該帳號未使用的舊驗證碼
        $now = date('Y-m-d H:i:s');
        DB::table('admin_sms_code')->where('admin_id',$hasAdmin->admin_id)->whereNull('used_at')->delete();
        DB::table('admin_sms_code')->insert([
            'admin_id'   => $hasAdmin->admin_id,
            'phone'      => $phone,
            'code_hash'  => hash('sha256', $code),
            'expire_at'  => date('Y-m-d H:i:s', time() + (int)$smsConfig['code_expire']),
            'attempts'   => 0,
            'used_at'    => null,
            'send_ip'    => (string)$request->ip(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        return $this->success(__('web.sms_send_success'), [
            'phone'  => substr($phone, 0, 3) . '****' . substr($phone, -2),
            'expire' => (int)$smsConfig['code_expire'],
        ]);
    }

    /**
     * 校驗登錄短信驗證碼
     * @param int $adminId
     * @param string $code
     * @return true|string true 表示通過，字符串為錯誤提示
     */
    private static function verifyAdminSmsCode($adminId, $code){
        $row = DB::table('admin_sms_code')
            ->where('admin_id',$adminId)
            ->whereNull('used_at')
            ->orderByDesc('id')
            ->first();
        if(empty($row)){
            return __('web.sms_code_required');
        }
        if(strtotime($row->expire_at) < time()){
            return __('web.sms_code_expired');
        }
        if((int)$row->attempts >= 5){
            return __('web.sms_code_locked');
        }
        if(!hash_equals($row->code_hash, hash('sha256', $code))){
            DB::table('admin_sms_code')->where('id',$row->id)->increment('attempts');
            return __('web.sms_code_error');
        }
        DB::table('admin_sms_code')->where('id',$row->id)->update([
            'used_at'    => date('Y-m-d H:i:s'),
            'updated_at' => date('Y-m-d H:i:s'),
        ]);
        return true;
    }

    /**
     * 登錄
     * @param Request $request
     * @return array
     */
    public function AdminLoginWeb(Request $request){
        $username = trim((string)$request->post('username'));
        $password = (string)$request->post('password');
        if($username === '' || $password === ''){
            return $this->parametersError();
        }

        // 登錄失敗限流：同一帳號 10 分鐘內失敗 5 次即鎖定
        $failCount = AdminLog::where('login_admin_name',$username)
            ->where('login_status',0)
            ->where('created_at','>=',date('Y-m-d H:i:s', time() - 600))
            ->count();
        if($failCount >= 5){
            return $this->error(__('web.login_locked'));
        }

        $hasAdmin = Admins::where('username',$username)->where('status',1)->first();
        if(empty($hasAdmin) || !self::checkAdminPassword($hasAdmin, $password)){
            self::writeLoginFailLog($request, $username, __('web.login_error'));
            return $this->error(__('web.login_error'));
        }

        // 短信二次驗證（帳號密碼正確後才校驗，避免洩露帳號是否存在）
        if(config('services.aliyun_sms.enabled')){
            $phone = trim((string)$hasAdmin->phone);
            if(!preg_match('/^[4-9]\d{7}$/', $phone)){
                return $this->error(__('web.sms_phone_invalid'));
            }
            $smsCode = trim((string)$request->post('sms_code'));
            if($smsCode === ''){
                return $this->error(__('web.sms_code_required'));
            }
            $check = self::verifyAdminSmsCode($hasAdmin->admin_id, $smsCode);
            if($check !== true){
                return $this->error($check);
            }
        }

        // 舊 MD5 密碼在登錄成功時自動升級為 bcrypt
        if(!self::isBcryptHash((string)$hasAdmin->password)){
            Admins::where('admin_id',$hasAdmin->admin_id)->update(['password'=>Hash::make($password)]);
        }

        $token = AdminTokenController::createToken($hasAdmin['admin_id'], "");
        // 數據庫只保存 token 的 sha256，並記錄服務端過期時間（默認 7 天）
        $tokenTtl = (int)config('services.admin_auth.token_ttl', 604800);
        $state = Admins::where('admin_id',$hasAdmin['admin_id'])->update([
            'token' => hash('sha256', $token),
            'token_expire_at' => date('Y-m-d H:i:s', time() + $tokenTtl),
        ]);
        $adminLog = [
            'login_admin_id' => $hasAdmin['admin_id'],
            'login_admin_name' => $hasAdmin['username'],
            'login_ip' => $request->ip(),
            'login_status' => 1,
            'login_msg' => '',
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
    }

    /**
     * 是否為 bcrypt/argon 哈希
     * @param string $stored
     * @return bool
     */
    private static function isBcryptHash($stored){
        return strpos($stored, '$2y$') === 0 || strpos($stored, '$2a$') === 0 || strpos($stored, '$argon') === 0;
    }

    /**
     * 校驗管理員密碼（兼容舊 MD5 與新 bcrypt）
     * @param Admins $admin
     * @param string $plain
     * @return bool
     */
    private static function checkAdminPassword($admin, $plain){
        $stored = (string)$admin->password;
        if(self::isBcryptHash($stored)){
            return Hash::check($plain, $stored);
        }
        return hash_equals($stored, md5($plain));
    }

    /**
     * 記錄登錄失敗（用於限流與審計）
     * @param Request $request
     * @param string $username
     * @param string $msg
     */
    private static function writeLoginFailLog(Request $request, $username, $msg){
        try {
            DB::table('admin_log')->insert([
                'login_admin_id'   => 0,
                'login_admin_name' => mb_substr((string)$username, 0, 250),
                'login_ip'         => (string)$request->ip(),
                'login_status'     => 0,
                'login_msg'        => mb_substr((string)$msg, 0, 250),
                'created_at'       => date('Y-m-d H:i:s'),
            ]);
        } catch (\Throwable $e) {
            // 日誌寫入失敗不影響登錄流程
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
        $data = Admins::where('token',hash('sha256',$token))
            ->select('admin_id','username','avatar','email','phone','status')
            ->first();
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
        $admin = Admins::where('token',hash('sha256',$token))->first();
        if(empty($admin)){
            return $this->error(__('web.login_error'));
        }
        if(!self::checkAdminPassword($admin, $oldPassword)){
            return $this->error(__('web.old_password_error'));
        }
        // 升級為 bcrypt，並強制當前令牌失效（需要重新登錄）
        $state = Admins::where('admin_id',$admin->admin_id)->update([
            'password' => Hash::make($newPassword),
            'token' => '',
            'token_expire_at' => null,
        ]);
        Cache::forget('admin:token:v2:' . md5((string)$token));
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
