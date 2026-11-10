<?php
/*
 * File: User.php
 * Created Time: 2024-05-29 11:27:52 am
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-09-10 4:16:05 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2024 成都睿創科技有限公司
 */


namespace App\Models;

use App\Extend\Com;
use DateTimeInterface;
use Laravel\Cashier\Billable;//Cashier支付
use Laravel\Passport\HasApiTokens;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use App\Http\Controllers\Api\BaseController;
use App\Models\UserWalletRecord;
use Illuminate\Foundation\Auth\User as Authenticatable;
/**
 * App\Models\User
 *
 */
class User extends Base
{

    protected $table = 'users';           //定义用户表名称
    protected $primaryKey = "user_id";          //定义用户表主键
    protected $hidden = ['user_password'];      //不返回用戶密碼

    use Notifiable,HasApiTokens,Billable;

    /**
     * 自定义的密码字段, 无此，对于密码字段名不为password的登录时将出现密码不存在
     * @return string
     */
    public function getAuthPassword() {
        return $this->user_password;        // 一定要返回加密了的密码
    }

    /**
     * 为数组 / JSON 序列化准备日期。
     */
    protected function serializeDate(DateTimeInterface $date){
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }


/*********************************************************关联**********************************************************************/
    /**
     * 关联用户优惠券
     */
    public function getUserExchange(){
        return $this->hasMany('App\Models\UserExchange','user_id','user_id');
    }

    /**
     * 关联用户收藏
     */
    public function getUserCollect(){
        return $this->hasMany('App\Models\UserCollect','user_id','user_id');
    }

    /**
     * 关联用户钱包记录
     */
    public function getUserWallet(){
        return $this->hasMany('App\Models\UserWalletRecord','user_id','user_id');
    }

    /**
     * 关联用户积分记录
     */
    public function getUserIntegral(){
        return $this->hasMany('App\Models\UserIntegralRecord','user_id','user_id');
    }

/*********************************************************全局作用域注册**********************************************************************/

    protected static function boot(){
        parent::boot();
	    static::addGlobalScope('user_status', function (Builder $builder) {
		    return $builder->where('users.user_status',  '<>', -1);
	    });
    }


/*********************************************************admin**********************************************************************/

    /**
     * 獲取用戶列表
     * @param $data
     * @return array
     */
    public function getUserListWeb($data){
        $limit = $data['limit'];
        $page = ($data['page'] - 1) * $limit;
        $where[] = array('user_status','<>',-1);
        $where[] = array('user_id','<>',1);//不顯示等於1的用戶
        if(isset($data['user_status']) && (!empty($data['user_status']) || is_numeric($data['user_status']))){
            $where['user_status'] = $data['user_status'];
        }
        
        // 构建搜索查询条件（用于用户列表）
        $searchQuery = function ($query) use ($data) {
            if(!empty($data['multi'])){
                $query->whereRaw('(
                            concat(
                            COALESCE(user_phone, ""),
                            COALESCE(user_name, ""),
                            COALESCE(user_email, ""),
                            COALESCE(user_number, ""),
                            COALESCE(last_login_ip, ""),
                            COALESCE(register_ip, "")) like "%' . $data['multi'] . '%")');
            }
        };
        
        // 获取符合搜索条件的用户总数
        $totalCount = $this->where($where)
            ->where($searchQuery)
            ->count();

        // 排序映射：前端字段名 → DB 字段名
        $sortMap = [
            'created_at'   => 'created_at',
            'login_time'   => 'login_time',
            'finally_at'   => 'finally_at',
            'user_id'      => 'user_id',
        ];
        $sort  = isset($data['sort']) && isset($sortMap[$data['sort']]) ? $sortMap[$data['sort']] : 'user_id';
        $order = isset($data['order']) && in_array(strtolower($data['order']), ['asc', 'desc']) ? $data['order'] : 'desc';

        // 获取用户列表数据（带搜索条件）
        $userList = $this->where($where)
            ->where($searchQuery)
            ->offset($page)->limit($limit)
            ->orderBy($sort, $order)
            ->get()->toArray();
        
        // 获取全局的在线用户列表（基于所有正常状态的用户，不包括禁用的用户）
        $globalOnlineUsers = $this->getGlobalOnlineUsers($data);
        
        // 为列表中的用户设置在线状态（基于全局在线列表）
        $userList = $this->getUserListWithOnlineStatus($userList, $globalOnlineUsers);
        
        $datas['data'] = $userList;
        $datas['count'] = $totalCount;
        
        return $this->success(__('web.success'), $datas);
    }

    /**
     * 获取全局在线用户列表（基于保底机制，不包含搜索条件）
     * @param array $data 请求数据
     * @return array
     */
    private function getGlobalOnlineUsers($data){
    // 基础条件：只包括正常状态的用户（user_status = 1）
    $baseWhere[] = array('user_status', '=', 1);
    $baseWhere[] = array('user_id', '<>', 1); // 排除管理员
    
    // 获取最近30分钟内有过登录的正常用户（真实的在线用户）
    // 舊邏輯按「當前小時」判斷，整點切換會瞬間全部掉線（如 13:59 登錄 14:00 顯示離線），改為滑動窗口
    $activeSince = date('Y-m-d H:i:s', strtotime('-30 minutes'));
    $currentHourLoginUsers = $this->where($baseWhere)
        ->whereNotNull('finally_at')
        ->where('finally_at', '>=', $activeSince)
        ->pluck('user_id')
        ->toArray();
    
    // 检查 userlogin_mechanism 开关：1=启用保底机制和随机登录次数，2=禁用
    $mechanism = app('global_userlogin_mechanism');
    if ($mechanism == 2) {
        // 禁用保底机制：只返回真实在线用户，不补充虚拟用户
        return $currentHourLoginUsers;
    }
    
    // 以下为保底机制逻辑（mechanism == 1 时执行）
    // 获取所有正常用户总数
    $totalCount = $this->where($baseWhere)->count();
    
    // 计算保底在线用户数量（总用户的20%并向上取整）
    $minOnlineCount = ceil($totalCount * 0.2);
    
    // 获取当前日期信息
    $currentDay = date('d');
    $isEvenDay = ($currentDay % 2 == 0);
    
    // 获取所有正常用户ID列表
    $allUserIds = $this->where($baseWhere)
        ->pluck('user_id')
        ->toArray();
    
    // 判断是否需要启用保底机制
    $needMinimumMechanism = (count($currentHourLoginUsers) < $minOnlineCount);
    
    if ($needMinimumMechanism) {
        // 计算还需要补充多少用户
        $needToAdd = $minOnlineCount - count($currentHourLoginUsers);
        
        // 获取不在当前小时登录的用户（包括从未登录的用户）
        $otherUsers = array_diff($allUserIds, $currentHourLoginUsers);
        
        // 根据优先级排序获取需要补充的用户
        $additionalUsers = $this->getPriorityUsers($otherUsers, $isEvenDay, $needToAdd, $baseWhere, $data);
        
        // 合并所有保底在线用户
        return array_merge($currentHourLoginUsers, $additionalUsers);
    } else {
        // 当前小时内登录用户数已达到20%标准
        return $currentHourLoginUsers;
    }
}


    /**
     * 根据优先级获取需要补充的用户
     * @param array $userIds 可选的用户ID列表
     * @param bool $isEvenDay 是否是偶数天
     * @param int $needCount 需要获取的数量
     * @param array $where 查询条件
     * @param array $data 请求数据
     * @return array
     */
    private function getPriorityUsers($userIds, $isEvenDay, $needCount, $where, $data){
        if (empty($userIds) || $needCount <= 0) {
            return [];
        }
    
        // 获取优先级用户（根据user_number的奇偶性）
        $priorityQuery = $this->where($where)
            ->whereIn('user_id', $userIds);
    
        if ($isEvenDay) {
            // 偶数天：优先找user_number为偶数的用户
            $priorityQuery->whereRaw('MOD(user_number, 2) = 0');
        } else {
            // 奇数天：优先找user_number为奇数的用户
            $priorityQuery->whereRaw('MOD(user_number, 2) = 1');
        }
    
        $priorityUserIds = $priorityQuery
            ->orderBy('finally_at', 'desc')
            ->limit($needCount)
            ->pluck('user_id')
            ->toArray();
    
        // 如果优先级用户不够，再从剩余用户中补充
        if (count($priorityUserIds) < $needCount) {
            $remainingNeeded = $needCount - count($priorityUserIds);
        
            // 获取剩余用户（非优先级的用户）
            $remainingUserIds = array_diff($userIds, $priorityUserIds);
        
            $remainingUsers = $this->where($where)
                ->whereIn('user_id', $remainingUserIds)
                ->orderBy('finally_at', 'desc')
                ->limit($remainingNeeded)
                ->pluck('user_id')
                ->toArray();
        
            $priorityUserIds = array_merge($priorityUserIds, $remainingUsers);
        }
    
        return $priorityUserIds;
    }

    /**
     * 为用户列表设置在线状态
     * @param array $userList 用户列表
     * @param array $onlineUserIds 在线用户ID数组
     * @return array
     */
    private function getUserListWithOnlineStatus($userList, $onlineUserIds){
        foreach ($userList as &$user) {
            // 禁用的用户不能在线
            if ($user['user_status'] == 0) {
                $user['is_online'] = 2; // 不在线
            } elseif (in_array($user['user_id'], $onlineUserIds)) {
                $user['is_online'] = 1; // 在线
            } else {
                $user['is_online'] = 2; // 不在线
            }
        }
        return $userList;
    }

    /**
     * 後台-臨時封禁用戶（對賬發現異常賬單時使用）
     * 同時把 user_status 置 0，App 端無法登錄與請求；到期後由 CheckToken 自動解封
     * @param int $userId
     * @param int $days 封禁天數，<=0 表示永久
     * @param string $reason
     * @return bool
     */
    public function banUserWeb($userId, $days = 0, $reason = '')
    {
        $userId = (int)$userId;
        if ($userId <= 0) {
            return false;
        }
        $days = (int)$days;
        $now = date('Y-m-d H:i:s');

        $affected = User::where('user_id', $userId)->update([
            'user_status' => 0,
            'ban_until'   => $days > 0 ? date('Y-m-d H:i:s', strtotime($now) + $days * 86400) : null,
            'ban_reason'  => $reason !== '' ? $reason : null,
            'updated_at'  => $now,
        ]);

        // 有效性快取 5 分鐘，封禁後必須清掉才能立即生效
        Cache::forget('user:active:' . $userId);
        return $affected > 0;
    }

    /**
     * 後台-解除封禁
     * @param int $userId
     * @return bool
     */
    public function unbanUserWeb($userId)
    {
        $userId = (int)$userId;
        if ($userId <= 0) {
            return false;
        }

        $affected = User::where('user_id', $userId)->update([
            'user_status' => 1,
            'ban_until'   => null,
            'ban_reason'  => null,
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);

        Cache::forget('user:active:' . $userId);
        return $affected > 0;
    }

    /**
     * 後台-查詢用戶封禁狀態
     * @param int $userId
     * @return array
     */
    public function getBanInfoWeb($userId)
    {
        $userId = (int)$userId;
        if ($userId <= 0) {
            return ['user_status' => 0, 'ban_until' => null, 'ban_reason' => null, 'is_banned' => 0];
        }
        $row = User::where('user_id', $userId)->select('user_status', 'ban_until', 'ban_reason')->first();
        $status = empty($row) ? 0 : (int)$row->user_status;
        return [
            'user_status' => $status,
            'ban_until'   => empty($row) ? null : $row->ban_until,
            'ban_reason'  => empty($row) ? null : $row->ban_reason,
            'is_banned'   => (!empty($row) && ($status !== 1 || !empty($row->ban_until))) ? 1 : 0,
        ];
    }

    /**
     * 後台-臨時封禁到期自動解封（登錄前調用，避免解封時間已過仍無法登錄）
     * @param array $where 定位條件，如 ['user_phone' => ..., 'area_code' => ...] 或 ['user_email' => ...]
     * @return void
     */
    public function autoUnbanExpired(array $where)
    {
        if (empty($where)) {
            return;
        }
        $row = User::where($where)->select('user_id', 'user_status', 'ban_until')->first();
        if (empty($row) || (int)$row->user_status === 1 || empty($row->ban_until)) {
            return;
        }
        if (strtotime($row->ban_until) > time()) {
            return;
        }
        User::where('user_id', $row->user_id)->update([
            'user_status' => 1,
            'ban_until'   => null,
            'ban_reason'  => null,
            'updated_at'  => date('Y-m-d H:i:s'),
        ]);
        Cache::forget('user:active:' . $row->user_id);
    }


    /**
     * 新增用戶
     * @param $data
     * @return array
     */
    public function addUserWeb($data,$token){
        // 手機號唯一性校驗（僅在提交了有效手機號時才查重）
        if (!empty($data['user_phone']) && !empty($data['area_code'])) {
            $dupPhone = $this->where('user_phone', $data['user_phone'])
                ->where('area_code', $data['area_code'])
                ->where('user_status', '<>', -1)
                ->value('user_id');
            if (!empty($dupPhone)) {
                return $this->error(__('web.phone_exist'));
            }
        }
        // 郵箱唯一性校驗（僅在提交了有效郵箱時才查重）
        if (!empty($data['user_email'])) {
            $dupEmail = $this->where('user_email', $data['user_email'])
                ->where('user_status', '<>', -1)
                ->value('user_id');
            if (!empty($dupEmail)) {
                return $this->error(__('web.email_exist'));
            }
        }
        DB::beginTransaction();
        $user = new User();
        $user->user_number = time().rand(100001,999999).$user->user_id;
        $user->user_email = empty($data["user_email"]) ? null : trim($data["user_email"]);
        $user->user_phone = $data["user_phone"];
        $user->user_password = bcrypt($data["user_password"]);
        $user->user_sex = $data["user_sex"];
        $user->area_code = $data["area_code"];
        $user->user_name = $data["user_name"];
        $user->user_coin = $data["user_coin"];
        $user->user_integral_all = $data["user_coin"];
        $user->user_balance = $data["user_balance"];
        $user->user_avatar = config('services.basics.user_avatar');
        $user->login_time = (empty($data['login_time']) || $data['login_time']==0)
            ? (app('global_userlogin_mechanism') == 1 ? random_int(1,9) : 0)
            : $data['login_time'];
        $user->created_at = self::getDate();
        $res = $user->save();
        $adminInfo = Admins::where('token',$token)->select('username','admin_id')->first();
        if ($res) {//添加用户不再记录操作日志
            // $cn_content= '新增了名爲 '.$data["user_name"].' 的用戶';
            // $en_content= 'Added a new user named '.$data["user_name"];
            // $configInfo = [//增加操作日誌
            //     'admin_name' => $adminInfo['username'],
            //     'admin_id' => $adminInfo['admin_id'],
            //     'activity_type' => 10,//activity_type=10新增用户
            //     'activity_value' => $cn_content,
            //     'en_activity_value' => $en_content,
            //     'created_at' =>  self::getDate()
            // ];
            // $configInsert = ActivityLog::insert($configInfo);
            DB::commit();
            return $this->success(__('web.add_success'));
        } else {
            DB::rollBack();
            return $this->error(__('web.add_fail'));
        }
    }

    /**
     * 編輯用戶
     * @param $data
     * @param $admin_id
     * @return array
     */
    public function editUserWeb($data,$admin_id){
        // 手機號唯一性校驗（僅在提交了有效手機號時才查重，避免空條件全表掃描誤判「已存在」）
        if (!empty($data['user_phone']) && !empty($data['area_code'])) {
            $dupPhone = $this->where('user_phone', $data['user_phone'])
                ->where('area_code', $data['area_code'])
                ->where('user_status', '<>', -1)
                ->where('user_id', '<>', $data['user_id'])
                ->value('user_id');
            if (!empty($dupPhone)) {
                return $this->error(__('web.phone_exist'));
            }
        }
        // 郵箱唯一性校驗（僅在提交了有效郵箱時才查重）
        if (!empty($data['user_email'])) {
            $dupEmail = $this->where('user_email', $data['user_email'])
                ->where('user_status', '<>', -1)
                ->where('user_id', '<>', $data['user_id'])
                ->value('user_id');
            if (!empty($dupEmail)) {
                return $this->error(__('web.email_exist'));
            }
        }

        // 空手機號/郵箱歸一化為 null（保持「未綁定」語義，避免把 null 用戶改寫成空字符串）
        if (array_key_exists('user_phone', $data) && empty($data['user_phone'])) {
            $data['user_phone'] = null;
        }
        if (array_key_exists('user_email', $data) && isset($data['user_email']) && trim((string)$data['user_email']) === '') {
            $data['user_email'] = null;
        }

        $userInfo = $this->where('user_id',$data['user_id'])->select('user_integral','user_integral_all','user_coin','user_balance')->first();
        $admin = Admins::where('admin_id',$admin_id)->select('username','admin_id')->first();
        DB::beginTransaction();
        $integralNumber = 0;
        $AddIntegralLog = true;
        $AddBalanceLog = true;
        if($userInfo['user_coin'] != $data['user_coin']){
            $integralNumber = $data['user_coin'] - $userInfo['user_coin'];
            if($integralNumber > 0 && $integralNumber != 0){
                $str = 'ID為'.$data['user_id'].'的用戶  +'.$integralNumber.'點數';
                $en_str =  'User with ID'.$data['user_id'].'+'.$integralNumber.'points';
                $mode = 1;
            }else if($integralNumber < 0 && $integralNumber != 0){
                $str = 'ID為'.$data['user_id'].'的用戶  -'.$integralNumber.'點數';
                $en_str = 'User with ID'.$data['user_id'].'- '.$integralNumber.'points';
                $mode = 2;
            }
            $integralInfo = [
                'admin_name' => $admin['username'],
                'admin_id' => $admin['admin_id'],
                'activity_type' => 1,
                'activity_mode' => $mode,
                'activity_value' => $str,
                'en_activity_value' => $en_str,
                'created_at' => self::getDate()
            ];
            $AddIntegralLog = ActivityLog::insert($integralInfo);
        }
        if($userInfo['user_balance'] != $data['user_balance']){
            $balanceNumber = $data['user_balance'] - $userInfo['user_balance'];
            if($balanceNumber > 0 && $balanceNumber != 0){
                $message = 'ID為'.$data['user_id'].'的用戶  +'.$balanceNumber.'餘額';
                $en_message = 'User with ID '.$data['user_id'].'+'.$balanceNumber.'balance';
                $activity_mode = 1;
            }else if($balanceNumber < 0 && $balanceNumber != 0){
                $message = 'ID為'.$data['user_id'].'的用戶  '.$balanceNumber.'餘額';
                $en_message =  'User with ID '.$data['user_id'].'-'.$balanceNumber.'balance';
                $activity_mode = 2;
            }
            $balanceInfo = [
                'admin_name' => $admin['username'],
                'admin_id' => $admin['admin_id'],
                'activity_type' => 2,
                'activity_mode' => $activity_mode,
                'activity_value' => $message,
                'en_activity_value' => $en_message,
                'created_at' => self::getDate()
            ];
            $AddBalanceLog = ActivityLog::insert($balanceInfo);
        }
        // 積分修改：正差累加 user_integral_all（會員等級按累計積分即時判定，加積分即提升等級），並記錄操作日誌
        $AddIntegralPointLog = true;
        $integralDiff = 0;
        if (isset($data['user_integral']) && $userInfo['user_integral'] != $data['user_integral']) {
            $integralDiff = (float)$data['user_integral'] - (float)$userInfo['user_integral'];
            if ($integralDiff > 0) {
                $p_str = 'ID為'.$data['user_id'].'的用戶  +'.$integralDiff.'積分';
                $en_p_str = 'User with ID'.$data['user_id'].'+'.$integralDiff.'integral';
                $p_mode = 1;
            } else {
                $p_str = 'ID為'.$data['user_id'].'的用戶  '.$integralDiff.'積分';
                $en_p_str = 'User with ID '.$data['user_id'].$integralDiff.'integral';
                $p_mode = 2;
            }
            $pointInfo = [
                'admin_name' => $admin['username'],
                'admin_id' => $admin['admin_id'],
                'activity_type' => 1,
                'activity_mode' => $p_mode,
                'activity_value' => $p_str,
                'en_activity_value' => $en_p_str,
                'created_at' => self::getDate()
            ];
            $AddIntegralPointLog = ActivityLog::insert($pointInfo);
        }
        if($AddIntegralLog && $AddBalanceLog && $AddIntegralPointLog){
            $integralAllAdd = ($integralNumber > 0 ? $integralNumber : 0) + ($integralDiff > 0 ? $integralDiff : 0);
            if($integralAllAdd > 0){
                $data['user_integral_all'] = $userInfo['user_integral_all'] + $integralAllAdd;
            }
            // 空郵箱統一存 NULL，避免多條空串撞上 user_email 唯一索引
            if (array_key_exists('user_email', $data) && trim((string)$data['user_email']) === '') {
                $data['user_email'] = null;
            }
            // 帳號狀態與封禁資訊同步：對賬單的「臨時封禁」與用戶列表的「啟用/禁用」共用同一套狀態
            if (self::hasColumnCached('users', 'ban_until')) {
                $prevStatus = (int)$this->where('user_id', $data['user_id'])->value('user_status');
                $nextStatus = isset($data['user_status']) ? (int)$data['user_status'] : $prevStatus;
                // 由禁用改回正常 = 解除封禁，清掉封禁標記；由正常改為禁用時不改動既有封禁資訊
                if ($nextStatus === 1 && $prevStatus !== 1) {
                    $data['ban_until'] = null;
                    $data['ban_reason'] = null;
                }
            }
            $editUserStatus = $this->where('user_id',$data['user_id'])->update($data);
            if($editUserStatus){
                DB::commit();
                // 有效性快取 5 分鐘，狀態變更後必須清掉才能立即生效
                Cache::forget('user:active:' . $data['user_id']);
                return $this->success(__('web.edit_success'));
            }else{
                DB::rollBack();
                return $this->error(__('web.edit_fail'));
            }
        }else{
            DB::rollBack();
            return $this->error(__('web.edit_fail'));
        }
    }

    /**
     * 刪除用戶
     * 軟刪除（user_status=-1）的同時把 email/phone 清空為 NULL，
     * 避免唯一索引 uniq_users_email 攔截後續同郵箱/手機號的重新註冊
     * @param $userID
     * @return array
     */
    public function deleteUserWeb($userID){
        $data = self::softDelete('users','user_id',$userID,'user_status',-1);
        if($data){
            // 清空 email/phone，避免唯一索引衝突導致無法重新註冊
            $this->where('user_id', $userID)->update([
                'user_email' => null,
                'user_phone' => null,
            ]);
            return $this->success(__('web.delete_success'));
        }else{
            return $this->error(__('web.delete_fail'));
        }
    }

    /**
     * 獲取用戶消費分析
     * @param $data
     * @param $language
     * @return array
     */
    public function getUserAnalysisWeb($data,$language){
        $limit = $data['limit'];
        $page = ($data['page'] - 1) * $limit;

        $time = [];
        $type = $data['type'];

        if(!empty($data['time'])){
            $time['start_time'] = $data['time'][0];
            $time['end_time'] = $data['time'][1];
        }

        if($type == 0){
            $fieldName = 'order_money';
        }else if($type == 1){
            $fieldName = 'order_number';
        }else{
            $fieldName = 'order_money';
        }

        $where[] = array('order.payment_status', '>=', 3);
        $where[] = array('order.order_status', '=', 1);
        $where[] = array('users.user_status', '=', 1);
        if(!empty($data['user_name'])){
            $where[] = array('users.user_name', 'like', '%' . $data['user_name'] . '%');
        }

        //獲取用戶消費總金額和消費次數
        $orderMoney = Order::join('users','users.user_id','=','order.user_id')
            ->where($where)
            ->where(function ($query) use ($time){
                if(!empty($time) && $time['start_time'] != ''){
                    return $query->whereBetween('order.payment_at',$time);
                }
            })->select('order.user_id','order.order_type',DB::raw('SUM(order.order_money) as order_money'),DB::raw('COUNT(order.order_id) as order_number'),'users.user_name','users.user_integral_all')
            ->groupBy("order.user_id")
            ->get()->toArray();
        $last_names = array_column($orderMoney,$fieldName);
        array_multisort($last_names,SORT_DESC,$orderMoney);
        $datas['data'] = array_slice($orderMoney,$page,$limit);
        $datas['count'] = count($orderMoney);
        $member = UserMember::get()->toArray();
        for($i=0;$i<count($datas['data']);$i++){
//            $wheres[] = array('member_start','<=',$datas['data'][$i]['user_integral_all']);
//            $wheres[] = array('member_end','>=',$datas['data'][$i]['user_integral_all']);
//            $member = UserMember::where($wheres)->first();
            $userMember = $this->levelMember($datas['data'][$i]['user_integral_all'],$member);
            if($datas['data'][$i]['order_type']==2 && $datas['data'][$i]['user_id'] ==1){
               $datas['data'][$i]['member_name'] = '——';
            }else{
               $datas['data'][$i]['member_name'] = current($userMember)[$language.'member_name'];
            }
//            $datas['data'][$i]['member_name'] = $member[$language.'member_name'];
//            $wheres =array();
        }
        return $this->success(__('web.success'),$datas);
    }

    public function levelMember($score,$filter){
        // 二分查找前提是按 member_end 升序。調用方傳入的是主鍵順序（member_id），
        // 而「初心」這類後加的檔位 member_id 較大、區間卻是最低，會使低積分用戶被判成高檔位，
        // 這裡統一先排序，保證任何調用方傳入都能得到正確檔位。
        usort($filter, function ($a, $b) { return $a['member_end'] <=> $b['member_end']; });
        $half = floor(count($filter) / 2);
        if($score <= $filter[$half - 1]['member_end']){
            $filter = array_slice($filter,0,$half);
        }else{
            $filter = array_slice($filter,$half,count($filter));
        }

        if(count($filter) != 1){
            $filter = $this->levelMember($score,$filter);
        }
        return $filter;
    }




/**********************************************************api**********************************************************************/

    /**
     * 根據會員等級添加登錄獎勵（金幣 + 鑽石）
     * @param int $user_id
     * @return void
     */
    /**
     * 判斷用戶今日是否已領取過每日金幣/鑽石（以錢包流水為憑證）
     * @param int $user_id
     * @return bool
     */
    public function hasClaimedTodayBonus(int $user_id): bool
    {
        return UserWalletRecord::where('user_id', $user_id)
            ->whereIn('wallet_explain', ['每日登錄送金幣', '每日登錄送鑽石'])
            ->whereBetween('created_at', [date('Y-m-d 00:00:00'), date('Y-m-d 23:59:59')])
            ->exists();
    }

    /**
     * 根據會員等級發送每日登錄獎勵（金幣/鑽石），寫入錢包流水
     * @param int $user_id
     * @return void
     */
    public function addLoginBonus($user_id)
    {
        $language = app('global_language') == "en" ? "en_" : "";
        $user = $this->where('user_id', $user_id)->first();
        if (!$user) return;

        // 每日僅發放一次：以當日錢包流水為憑證（避免每次登錄/簽到重複發放）
        if ($this->hasClaimedTodayBonus((int)$user_id)) return;

        // 積分低於最低檔起點即「未定級」：不發放等級獎勵（後台可調整檔位區間，故不寫死門檻）
        $lowestMember = UserMember::where('member_status', '=', 1)->orderBy('member_end', 'asc')->first();
        if (!empty($lowestMember) && (int)$user['user_integral_all'] < (int)$lowestMember->member_start) {
            return;
        }

        $maxmember = UserMember::max('member_end');
        if ($user['user_integral_all'] > $maxmember) {
            $memberinfo = UserMember::orderBy('member_end', 'desc')->first();
        } else {
            $member = UserMember::where('member_status', '=', 1)->get()->toArray();
            $memberinfo = current($this->levelMember($user['user_integral_all'], $member));
        }

        $bonusCoin = $memberinfo['login_bonus_coin'] ?? 0;
        $bonusDiamond = $memberinfo['login_bonus_diamond'] ?? 0;

        DB::beginTransaction();
        try {
            if ($bonusCoin > 0) {
                // 增加金幣（user_balance）
                $this->where('user_id', $user_id)->increment('user_balance', $bonusCoin);
                // 記錄錢包明細
                UserWalletRecord::insert([
                    'user_id' => $user_id,
                    'wallet_type' => 2,
                    'payment_mode' => 3,
                    'wallet_explain' => '每日登錄送金幣',
                    'en_wallet_explain' => 'Daily login bonus coins',
                    'wallet_money' => $bonusCoin,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
            if ($bonusDiamond > 0) {
                // 增加鑽石（user_coin）
                $this->where('user_id', $user_id)->increment('user_coin', $bonusDiamond);
                // 記錄鑽石明細
                UserWalletRecord::insert([
                    'user_id' => $user_id,
                    'wallet_type' => 4,
                    'payment_mode' => 4,
                    'wallet_explain' => '每日登錄送鑽石',
                    'en_wallet_explain' => 'Daily login bonus diamonds',
                    'wallet_money' => $bonusDiamond,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);
            }
            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
        }
    }

    /**
     * 獲取用戶會員等級 + 今日領取的獎勵數量（供登錄接口返回）
     */
    public function getLoginBonusInfo(int $user_id): array
    {
        $user = $this->where('user_id', $user_id)->first();
        if (!$user) return ['member_name' => '', 'member_grade' => 0, 'bonus_coin' => 0, 'bonus_diamond' => 0, 'today_bonus_coin' => 0, 'today_bonus_diamond' => 0];

        // 會員等級（和 getUsersInfo 邏輯一致）
        $lowestMember = UserMember::where('member_status', '=', 1)->orderBy('member_end', 'asc')->first();
        if (!empty($lowestMember) && (int)$user['user_integral_all'] < (int)$lowestMember->member_start) {
            $memberName = '未定級';
            $memberGrade = 0;
            $bonusCoin = 0;
            $bonusDiamond = 0;
        } else {
            $maxmember = UserMember::max('member_end');
            if ($user['user_integral_all'] > $maxmember) {
                $memberinfo = UserMember::orderBy('member_end', 'desc')->first();
            } else {
                $member = UserMember::where('member_status', '=', 1)->get()->toArray();
                $memberinfo = current($this->levelMember($user['user_integral_all'], $member));
            }
            $memberName = $memberinfo[$this->language() . 'member_name'] ?? $memberinfo['member_name'] ?? '';
            $memberGrade = $memberinfo['member_id'] ?? 0;
            $bonusCoin = $memberinfo['login_bonus_coin'] ?? 0;
            $bonusDiamond = $memberinfo['login_bonus_diamond'] ?? 0;
        }

        // 今日已領取數量
        $todayStart = date('Y-m-d 00:00:00');
        $todayEnd   = date('Y-m-d 23:59:59');
        $todayBonusCoin = (int)UserWalletRecord::where('user_id', $user_id)
            ->where('wallet_explain', '每日登錄送金幣')
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->sum('wallet_money');
        $todayBonusDiamond = (int)UserWalletRecord::where('user_id', $user_id)
            ->where('wallet_explain', '每日登錄送鑽石')
            ->whereBetween('created_at', [$todayStart, $todayEnd])
            ->sum('wallet_money');

        return [
            'member_name'        => $memberName,
            'member_grade'       => $memberGrade,
            'bonus_coin'         => (int)$bonusCoin,
            'bonus_diamond'      => (int)$bonusDiamond,
            'today_bonus_coin'   => $todayBonusCoin,
            'today_bonus_diamond'=> $todayBonusDiamond,
        ];
    }

    private function language(): string
    {
        return app('global_language') == 'en' ? 'en_' : '';
    }

    /**
     * @param $data
     * @return array
     * 獲取用戶信息
     */
    public function getUsersInfo($user_id){
        $language = app('global_language') == "en" ? "en_" : "";
        $where[] = array('user_id','=',$user_id);
        $results = $this ->where($where)->select('*')->first();
        if (empty($results['user_code'])) {
            $CodeInfo=(string)$user_id;
            $BaseController = new BaseController();
            $Usercode=$BaseController->getQRCode($CodeInfo);
            $results['user_code']=$Usercode['results'];
        }
        //不顯示密碼
        unset($results['user_password']);
        if(!empty($results)){
            // 積分低於最低檔起點即「未定級」：不屬於任何檔位，也不享受等級獎勵。
            // 後台可調整檔位區間（最低檔起點可能改成 800/700...），故門檻取當前最低檔的 member_start，不寫死。
            $lowestMember = UserMember::where('member_status','=',1)->orderBy('member_end','asc')->first();
            if (!empty($lowestMember) && (int)$results['user_integral_all'] < (int)$lowestMember->member_start) {
                $results['member_name'] = $language == 'en_' ? 'Unranked' : '未定級';
                $results['memberGrade'] = 0;
                $results['member_start'] = 0;
                $results['member_end'] = max(0, (int)$lowestMember->member_start - 1);
                $results['login_bonus_coin'] = 0;
                $results['login_bonus_diamond'] = 0;
                $results['next_name'] = $lowestMember[$language.'member_name'];
                return $this->success( __('api.list_success'), $results);
            }
            //超出最高分級時使用最高的等級
            $maxmember = UserMember::max('member_end');
            if($results['user_integral_all'] > $maxmember){
                $memberinfo = UserMember::orderBy('member_end', 'desc')->first();
                //显示下个等级
                $results['next_name'] = $memberinfo[$language.'member_name'];
            } else {
                $maxLevel = UserMember::where('member_status','=',1)->orderBy('member_end', 'desc')->first();
                if($results['user_integral_all'] <= $maxLevel->member_end) {
					$member = UserMember::where('member_status','=',1)->get()->toArray();
					$memberinfo = current($this->levelMember($results['user_integral_all'],$member));
                    //显示下个等级：按 member_end 取相鄰的下一檔。
                    // 不能用 member_id+1，因為 member_id 順序 ≠ 等級順序（如 id=3 明月 的下一檔是 id=5 飛鳥，不是 id=4 初心）
                    $nextMember = UserMember::where('member_status','=',1)
                        ->where('member_end','>',$memberinfo['member_end'])
                        ->orderBy('member_end','asc')->first();
                    $results['next_name'] = $nextMember
                        ? $nextMember[$language.'member_name']
                        : $memberinfo[$language.'member_name'];

                }else{
                    //直接使用設置的最高等级
                    $results['next_name'] = $maxLevel[$language . 'member_name'];
                    $memberinfo = $maxLevel;
                }
                // $this->LogInfo('memberinfo' , $memberinfo , 'memberinfo' , true);//开启日志记录
            }
            $results['member_name'] = $memberinfo[$language.'member_name'];
            $results['memberGrade'] = $memberinfo['member_id'];
            $results['member_start'] = $memberinfo['member_start'];
            $results['member_end'] = $memberinfo['member_end'];
            $results['login_bonus_coin'] = $memberinfo['login_bonus_coin'] ?? 0;
            $results['login_bonus_diamond'] = $memberinfo['login_bonus_diamond'] ?? 0;
            return $this->success( __('api.list_success'), $results);
        }else{
            return $this->success( __('api.list_null'), $results);
        }
    }




    /**
     * @param $data
     * @return array
     * 修改用戶信息
     */
    public function UpdateUsers($user_id , $data){
        DB::beginTransaction();
        try {
            if(!empty($data['user_password'])){
                $data['user_password']=bcrypt($data['user_password']);
            }
            $effects = $this->where('user_id',$user_id)->update($data);
            if(!empty($effects)){
                // $this->LogInfo('多语言日志' , $results , 'lang' , true);//开启日志记录
                DB::commit();
                return $this->success( __('api.edit_success'));
            }else{
                DB::rollBack();
                return $this->error( __('api.edit_fail'));
            }
        }catch (\Exception $e) {
            DB::rollBack();
            return $this->error( __('api.edit_fail') , $e->getMessage());
        }

    }


    /**
     * @param $data
     * @return mixed
     * 修改綁定手機號
     */
    public function UpdatePhone($user_id , $data){
        DB::beginTransaction();
        try {
            $effects = $this->where('user_id',$user_id)
                            ->update([
                                     'user_phone'=>$data['user_phone'],
                                     'area_code'=>$data['area_code'],
                                     'updated_at'=>date('Y-m-d H:i:s')
                                    ]);
            if(!empty($effects)){
                // $this->LogInfo('多语言日志' , $results , 'lang' , true);//开启日志记录
                DB::commit();
                return $this->success( __('api.edit_success'));
            }else{
                DB::rollBack();
                return $this->error( __('api.edit_fail'));
            }
        }catch (\Exception $e) {
            DB::rollBack();
            return $this->error( __('api.edit_fail') , $e->getMessage());
        }
    }



/**************************************************common(api和admin共用)***********************************************************/


    /**
     * @param $data
     * @return array
     * 獲取用戶快照
     */
    public function UserSnapshot($user_id){
        $where[] = array('user_id','=',$user_id);
        $effects = $this ->where($where)->select('*')->first();
        //轉json
        return  json_encode($effects);
    }




}
