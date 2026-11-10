<?php
/*
 * File: Order.php (merged CardOrder → Order)
 * Project: NewDemo
 * Modified: Card order logic now uses `order` table instead of `card_order`
 * -----
 * Last Modified: 2026-06-11 11:45:28 am
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2026 成都睿創科技有限公司
 */

namespace App\Models;

use App\Http\Controllers\Api\MailController;
use DateTimeInterface;
use App\Models\Base;
use App\Models\SystemInfo;
use App\Models\User;
use Illuminate\Support\Carbon;


use Illuminate\Support\Facades\DB;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;

class CardOrder extends Base{

    use Notifiable;
    public $timestamps = true;
    protected $table = 'order';
    protected $primaryKey = 'order_id';
    protected $appends = ['card_order_no'];

    public function getCardOrderNoAttribute(){
        return $this->attributes['order_no'] ?? '';
    }

    protected function serializeDate(DateTimeInterface $date){
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }

    public function CardOrderDetails(){
        return $this->hasMany('App\Models\CardOrderDetails', 'order_id', 'order_id');
    }

    public function __construct(){
        $this->language = app('global_language') == "en" ? "en_" : "";
    }

    protected static function boot(){
       parent::boot();
       static::addGlobalScope('order', function (Builder $builder) {
          return $builder->where('order.order_status', '<>', -1);
       });
    }

    /*********************************************************admin**************************************************************************/

    public function getOrderListWeb($data){
        \Log::info('CardOrder getOrderListWeb INPUT', [
            'raw' => $data,
            'subType' => $data['subType'] ?? null,
            'exchange_status' => $data['exchange_status'] ?? null,
            'logistics_status' => $data['logistics_status'] ?? null,
            'indent_type' => $data['indent_type'] ?? null,
        ]);
        $limit = $data['limit'];
        $page =  ( $data['page'] - 1 ) * $limit;
        $time = [];
        $queryKeyword = null;
        $where[] = array('order.order_status', '<>', -1);
        $where[] = array('users.user_status', '<>', -1);
        if (!empty($data['delivery_area'])) {
            $where[] = array('order.delivery_area', 'like', '%' . $data['delivery_area'] . '%');
        }
        if(!empty($data['payment_status'])){
            $where['order.payment_status'] = $data['payment_status'];
        }
        if(!empty($data['order_no'])){
            $where['order.order_no'] = $data['order_no'];
        }
        // 用戶名/手機號/郵箱搜索（同時匹配收件人信息）
        if(!empty($data['keyword'])){
            $keyword = $data['keyword'];
            $queryKeyword = function ($q) use ($keyword) {
                $q->where(function ($qq) use ($keyword) {
                    $qq->where('users.user_name', 'like', '%'.$keyword.'%')
                       ->orWhere('users.user_phone', 'like', '%'.$keyword.'%')
                       ->orWhere('users.user_email', 'like', '%'.$keyword.'%')
                       ->orWhere('order.delivery_name', 'like', '%'.$keyword.'%')
                       ->orWhere('order.delivery_phone', 'like', '%'.$keyword.'%')
                       ->orWhere('order.delivery_mail', 'like', '%'.$keyword.'%');
                });
            };
        }
        if(!empty($data['time'])){
            $time['start_time'] = $data['time'][0];
            $time['end_time'] = $data['time'][1];
        }

        // === 处理前端传来的 exchange_status / logistics_status 过滤 ===
        // 前端 shipping-order 页面：
        //   subType=2 待发货 → exchange_status=2, logistics_status=0
        //   subType=3 已发货 → exchange_status=2, logistics_status=1
        // 前端可能直接传 exchange_status/logistics_status，也可能传 subType
        $detailExchangeStatus = isset($data['exchange_status']) && $data['exchange_status'] !== ''
            ? (int)$data['exchange_status'] : null;
        $detailLogisticsStatus = isset($data['logistics_status']) && $data['logistics_status'] !== ''
            ? (int)$data['logistics_status'] : null;

        // 前端傳 subType → 映射成 exchange_status + logistics_status
        $subType = isset($data['subType']) ? (int)$data['subType'] : null;
        if ($subType !== null) {
            if ($subType === 2) {
                // 待發貨
                $detailExchangeStatus = 2;
                $detailLogisticsStatus = 0;
            } elseif ($subType === 3) {
                // 已發貨
                $detailExchangeStatus = 2;
                $detailLogisticsStatus = 1;
            }
        }
        $detailWhereHas = null;
        if ($detailExchangeStatus !== null || $detailLogisticsStatus !== null) {
            // 構建 whereHas 條件：至少有一條 order_details 符合狀態
            // 抽卡：exchange_status 匹配（logistics_status 可選）
            // 產品：logistics_status 匹配
            $detailWhereHas = function ($q) use ($detailExchangeStatus, $detailLogisticsStatus) {
                // 抽卡訂單的 details
                $q->where(function ($q2) use ($detailExchangeStatus, $detailLogisticsStatus) {
                    $q2->whereHas('CardOrderDetails', function ($qd) use ($detailExchangeStatus, $detailLogisticsStatus) {
                        if ($detailExchangeStatus !== null) {
                            $qd->where('order_details.exchange_status', '=', $detailExchangeStatus);
                        }
                        if ($detailLogisticsStatus !== null) {
                            $qd->where('order_details.logistics_status', '=', $detailLogisticsStatus);
                        }
                    });
                });
            };
        }

        CardOrderDetails::$modify = true;

        // === 終極方案：永遠查 [0,2]，用 details 狀態自動區分頁面 ===
        // 不再猜前端傳什麼 indent_type！永遠查 發貨合併單[0] + 抽卡原訂單[2]
        // 然後用 whereHas CardOrderDetails 的 exchange_status 自動過濾：
        //
        //   發貨詳情頁 → 前端有 subType/exchange_status/logistics_status
        //              → whereHas 按傳的狀態過濾（合併單有 exchange_status=2 ✅）
        //
        //   卡牌訂單頁 → 前端什麼狀態都沒傳
        //              → 默認 whereHas exchange_status=0
        //              → 合併單 exchange_status=2 被擋 ✅
        //              → 已兌換的原訂單 exchange_status=1 被擋 ✅
        //              → 只剩未處理的抽卡原訂單 ✅
        $indentTypes = [0, 2];

        // 沒任何狀態參數 → 卡牌訂單頁 → 默認只顯示未處理的
        if ($detailExchangeStatus === null && $detailLogisticsStatus === null) {
            $detailExchangeStatus = 0;
        }

        $query = $this->with(['CardOrderDetails'])
            ->join('users','users.user_id','=','order.user_id')
            ->where($where)
            ->when(!empty($queryKeyword), $queryKeyword)
            ->whereIn('order.indent_type', $indentTypes)
            ->where(function ($query) use ($time){
                if(!empty($time) && $time['start_time'] != ''){
                    return $query->whereBetween('order.payment_at',$time);
                }
            });

        \Log::info('CardOrder getOrderListWeb QUERY', [
            'indentTypes' => $indentTypes,
            'exchangeStatus' => $detailExchangeStatus,
            'logisticsStatus' => $detailLogisticsStatus,
            'hasWhereHas' => ($detailExchangeStatus !== null || $detailLogisticsStatus !== null),
        ]);

        // 如果前端指定了 exchange_status / logistics_status，用 whereHas 過濾
        if ($detailExchangeStatus !== null || $detailLogisticsStatus !== null) {
            // 用 whereHas 而不是 join，避免影響分頁 count
            $query->whereHas('CardOrderDetails', function ($qd) use ($detailExchangeStatus, $detailLogisticsStatus) {
                if ($detailExchangeStatus !== null) {
                    $qd->where('order_details.exchange_status', '=', $detailExchangeStatus);
                }
                if ($detailLogisticsStatus !== null) {
                    $qd->where('order_details.logistics_status', '=', $detailLogisticsStatus);
                }
            });
        }

        $datas['data'] = $query
            ->select('order.*','users.user_name','users.user_email','users.user_phone')
            ->offset($page)->limit($limit)
            ->orderBy('order.payment_at', 'desc')
            ->get()->toArray();
        $datas['count'] = $this->join('users','users.user_id','=','order.user_id')
            ->where($where)
            ->when(!empty($queryKeyword), $queryKeyword)
            ->whereIn('order.indent_type', $indentTypes)
            ->where(function ($query) use ($time){
                if(!empty($time) && $time['start_time'] != ''){
                    return $query->whereBetween('order.payment_at',$time);
                }
            })
            // count 查詢也要加相同的 whereHas 過濾
            ->when($detailExchangeStatus !== null || $detailLogisticsStatus !== null, function ($q) use ($detailExchangeStatus, $detailLogisticsStatus) {
                $q->whereHas('CardOrderDetails', function ($qd) use ($detailExchangeStatus, $detailLogisticsStatus) {
                    if ($detailExchangeStatus !== null) {
                        $qd->where('order_details.exchange_status', '=', $detailExchangeStatus);
                    }
                    if ($detailLogisticsStatus !== null) {
                        $qd->where('order_details.logistics_status', '=', $detailLogisticsStatus);
                    }
                });
            })
            ->count();
        $money = $this->getGoodsSale($data['indent_type'] ?? [1,2], $time);
        $datas['total_money'] = $money['total_money'];
        $datas['day_money'] = $money['day_money'];
        $datas['month_money'] = $money['month_money'];
        return $datas;
    }

    public function getGoodsSale($type,$time){
        $data['total_money'] = CardOrder::join('users','order.user_id','=','users.user_id')
            ->when(!empty($type), function ($query) use ($type) {
                return $query->whereIn('order.indent_type', $type);
            })
            ->where('order.order_status','<>',-1)
            ->where('order.payment_status','>',2)
            ->where('users.user_status','<>',-1)
            ->where(function ($query) use ($time){
                if(!empty($time) && $time['start_time'] != ''){
                    return $query->whereBetween('order.payment_at',$time);
                }
            })
            ->sum('order.order_money');

        $day_time['start_time'] = date('Y-m-d 00:00:00');
        $day_time['end_time'] = date('Y-m-d 23:59:59');
        $data['day_money'] = CardOrder::join('users','order.user_id','=','users.user_id')
            ->where('order.order_status','<>',-1)
            ->where('order.payment_status','>',2)
            ->where('users.user_status','<>',-1)
            ->when(!empty($type), function ($query) use ($type) {
                return $query->whereIn('order.indent_type', $type);
            })
            ->where(function ($query) use ($day_time){
                return $query->whereBetween('order.payment_at',$day_time);
            })
            ->sum('order.order_money');

        $month_time['start_time'] = date('Y-m-01 00:00:00' ,  strtotime('this month'));
        $month_time['end_time'] = date('Y-m-t 23:59:59',  strtotime('this month'));
        $data['month_money'] = CardOrder::join('users','order.user_id','=','users.user_id')
            ->where('order.order_status','<>',-1)
            ->where('order.payment_status','>',2)
            ->where('users.user_status','<>',-1)
            ->when(!empty($type), function ($query) use ($type) {
                return $query->whereIn('order.indent_type', $type);
            })
            ->where(function ($query) use ($month_time){
                return $query->whereBetween('order.payment_at',$month_time);
            })
            ->sum('order.order_money');
        return $data;
    }

    public function confirmPaymentWeb($orderID,$token){
        $admin = Admins::where('token',$token)->select('admin_id','username')->first();
        $data = $this->where('order_id',$orderID)->update(['payment_status'=>3,'payment_at'=>self::getDate()]);
        if($data){
            $info = $this->where('order_id',$orderID)->select('order_no','user_id','delivery_mail')->first();
            $cn_content = '訂單：'.$info['order_no'].',已完成支付！';
            $en_content = '注文: '.$info['order_no'].' 支払いが完了しました！';
            if($this->language == "en" ){
                $languagenow = 'en';
                $EmailInfo['Message']=$en_content;
                @self::pushOrderMessage($orderID,$cn_content,$en_content,$languagenow);
            }else{
                $languagenow = 'cn';
                $EmailInfo['Message']=$cn_content;
                @self::pushOrderMessage($orderID,$cn_content,$en_content,$languagenow);
            }
            $this->OrderMessage( $info['user_id'] ,$cn_content , $en_content );
            $email=new MailController();
            $EmailInfo['Email']=$info['delivery_mail'];
            $email->MessageEmails($EmailInfo);
            $configInfo = [
                'admin_name' => $admin['username'],
                'admin_id' => $admin['admin_id'],
                'order_id' => $orderID,
                'activity_type' => 7,
                'activity_value' => $cn_content,
                'en_activity_value' => $en_content,
                'created_at' =>  self::getDate()
            ];
            $configInsert = ActivityLog::insert($configInfo);
            if($configInsert){
                return $this->success(__('web.edit_success'));
            }else{
                return $this->error(__('web.activitylog_fail'));
            }
        }else{
            return $this->error(__('web.edit_fail'));
        }
    }

    public function deleteCardOrderWeb($orderID,$token){
        DB::beginTransaction();
        $admin = Admins::where('token',$token)->select('admin_id','username')->first();
        // 統一轉數組，支持批量刪除
        $orderIDs = is_array($orderID) ? $orderID : explode(',', $orderID);
        $orderIDs = array_filter(array_map('intval', $orderIDs));
        if (empty($orderIDs)) {
            DB::rollBack();
            return $this->error('參數錯誤');
        }
        $updateDetails = CardOrderDetails::whereIn('order_id', $orderIDs)->update(['details_status' => -1]);
        $updateOrder = $this->whereIn('order_id', $orderIDs)->update(['order_status'=>-1]);
        $orders = $this->whereIn('order_id', $orderIDs)->select('order_id', 'order_no')->get();
        $configInfo = [];
        $currentTime = self::getDate();
        foreach ($orders as $order) {
            $content = '訂單：'.$order['order_no'].',已刪除！';
            $en_content ='注文: '.$order['order_no'].' が削除されました！';
            $configInfo[] = [
                'admin_name' => $admin['username'],
                'admin_id' => $admin['admin_id'],
                'order_id' => $order['order_id'],
                'activity_type' => 9,
                'activity_value' => $content,
                'en_activity_value' => $en_content,
                'created_at' =>  $currentTime
            ];
        }
        $configInsert = ActivityLog::insert($configInfo);
        if($updateDetails && $updateOrder && $configInsert) {
            DB::commit();
            return $this->success(__('web.delete_success'));
        }else{
            DB::rollBack();
            return $this->error(__('web.delete_fail'));
        }
    }

    /**********************************************************api****************************************************************************/

    public function getOrderListApi($data){
        $limit = empty($data['limit']) ? 10 : $data['limit'];
        $page =  ($data['page'] - 1) * $limit;
        $where[] = array('users.user_status', '<>', -1);
        $where[] = array('order.order_status', '<>', -1);
        $where[] = array('order.user_id', '=', $data['user_id']);

        if (!empty($data['payment_type'])) {
            $where['order.payment_type'] = $data['payment_type'];
        }
        if (!empty($data['indent_type'])) {
            $where['order.indent_type'] = $data['indent_type'];
        }

        $logisticsCondition = null;
        if (!empty($data['payment_status']) && $data['payment_status'] == 6) {
            $logisticsCondition = function ($query) {
                $query->where('logistics_status', 3);
            };
        }

        CardOrderDetails::$modify = true;
        CardOrderSnapshot::$modify = true;

        $query = $this->with(['CardOrderDetails' => function ($query) use ($logisticsCondition) {
            $query->with('CardOrderSnapshot')
                ->select('*');
            if ($logisticsCondition) {
                $query->where($logisticsCondition);
            }
        }])
            ->join('users', 'users.user_id', '=', 'order.user_id')
            ->where($where);

        if ($logisticsCondition) {
            $query->whereHas('CardOrderDetails', $logisticsCondition);
        } elseif (!empty($data['payment_status']) && $data['payment_status'] != 6) {
            $query->where('order.payment_status', $data['payment_status']);
        }

        $info = $query->select('order.*')
            ->distinct('order.order_id')
            ->orderBy('order.created_at', 'desc')
            ->paginate($limit, ['*'], 'page', $data['page']);

        $effects = $info->items();
        foreach ($effects as $key => $value) {
            if (empty($effects[$key]['order_code'])) {
                $effects[$key]['order_code'] = $this->getQRCode($effects[$key]['order_no']);
            }
        }

        $results = [
            'count' => $info->total(),
            'currentPage' => $info->currentPage(),
            'limit' => $info->perPage(),
            'lastPage' => $info->lastPage(),
            'data' => $effects
        ];

        if (!empty($effects)) {
            return $this->success(__('api.list_success'), $results);
        } else {
            return $this->success(__('api.list_null'), $results);
        }
    }

    public function getOrderDetailsApi($data){
        $where[] = array('users.user_status', '<>', -1);
        $where[] = array('order.order_status', '<>', -1);
        $where[] = array('order.user_id', '=', $data['user_id']);
        $where[] = array('order.order_id', '=', $data['card_order_id']);
        $effects=array();
        CardOrderDetails::$modify = true;
        CardOrderSnapshot::$modify = true;
        $effects= $this->with(['CardOrderDetails'=>function($query){
            $query->with('CardOrderSnapshot')->select('*');
            }]) ->join('users','users.user_id','=','order.user_id')->where($where)->select('order.*')->first();
            if(empty($effects['order_code'])){
                $effects['order_code']=$this->getQRCode($effects['order_no']);
            }
        if(!empty($effects)){
            return $this->success( __('api.list_success'), $effects);
        }else{
            return $this->success( __('api.list_null'), $effects);
        }
    }

    public function CheckOrderExpired($data){
        $expiredTime = Carbon::now()->subMinutes(5);
        $expiredNum = $this->where('order.user_id', $data['user_id'])
            ->where('payment_status', '<', 2)
            ->where('order_status', '=', 1)
            ->where('created_at', '<=', $expiredTime)
            ->count();

        if ($expiredNum > 0){
            $expiredOrders = $this->where('order.user_id', $data['user_id'])
                ->where('payment_status', '<', 2)
                ->where('order_status', '=', 1)
                ->where('created_at', '<=', $expiredTime)
                ->select('order_no')
                ->get();

            $orderNos = $expiredOrders->pluck('order_no')->toArray();
            $orderNoStr = implode(',', $orderNos);
            $this->where('order.user_id', $data['user_id'])
                ->where('payment_status', '<', 2)
                ->where('order_status', '=', 1)
                ->where('created_at', '<=', $expiredTime)
                ->update([
                    'payment_status' => 4,
                    'order_remark' => "訂單長時間未支付已自動取消,如還有需要請重新購買,謝謝!"
                    ]);
            $Message = '溫馨提示，您的訂單號為：' . $orderNoStr . ' 的訂單已過期，請重新下單!';
            $en_Message = 'お知らせ: 注文番号 ' . $orderNoStr . ' は期限切れです。新しい注文を再度お願いします。';
            $Sends = '溫馨提示，您有'.$expiredNum.'個訂單訂單已過期，請重新下單!';
            $en_Sends = 'お知らせ: '.$expiredNum.'件の注文が期限切れです。新しい注文を再度お願いします。';
            $SystemInfo = new SystemInfo();
            $code = $SystemInfo->addSystemInfo($data['user_id'], 3, $Message, $en_Message);
            if($code) {
                return $this->success( __('api.list_success'),$expiredNum);
            }
        }else{
            return $this->success( __('api.list_null'),0);
        }
    }

    public function ConfirmReceipt($data){
        $effects = $this->where('order_id',$data['card_order_id'])->where('user_id',$data['user_id'])->update(['payment_status'=>5]);
        $languagenow = $this->language == "en_" ? "en" : "cn";
        if($effects){
            $info = $this->where('order_id',$data['card_order_id'])->select('order_no','delivery_mail')->first();
            $cn_content = '訂單：'.$info['order_no'].',已經确认收貨！';
            $en_content = '注文: '.$info['order_no'].' 受取が確認されました！';
            if($languagenow == "en" ){
                $EmailInfo['Message']=$en_content;
                @self::pushOrderMessage($data['card_order_id'],$cn_content,$en_content,$languagenow);
            }else{
                $EmailInfo['Message']=$cn_content;
                @self::pushOrderMessage($data['card_order_id'],$cn_content,$en_content,$languagenow);
            }
            $this->OrderMessage( $data['user_id'] ,$cn_content , $en_content );
            $email=new MailController();
            $EmailInfo['Email']=$info['delivery_mail'];
            $email->MessageEmails($EmailInfo);
            return $this->success(__('api.edit_success'));
        }else{
            return $this->error(__('api.edit_fail'));
        }
    }

    public function scanConfirmCardOrderWeb($data,$token){
        $effects = $this->where('order_no',$data['order_no'])->update(['payment_status'=>5]);
        $admin = Admins::where('token',$token)->select('username','admin_id')->first();
        $languagenow = $this->language == "en_" ? "en" : "cn";
        if($effects){
            $info = $this->where('order_no',$data['order_no'])->select('order_id','user_id','order_no','delivery_mail')->first();
            $cn_content = '訂單：'.$info['order_no'].',已通過掃碼完成預約！';
            $en_content = '注文: '.$info['order_no'].' をスキャンして予約完了しました！';
            if($languagenow == "en" ){
                $EmailInfo['Message']=$en_content;
                @self::pushOrderMessage($info['order_id'],$cn_content,$en_content,$languagenow);
            }else{
                $EmailInfo['Message']=$cn_content;
                @self::pushOrderMessage($info['order_id'],$cn_content,$en_content,$languagenow);
            }
            $this->OrderMessage( $info['user_id'] ,$cn_content , $en_content );
            $email=new MailController();
            $EmailInfo['Email']=$info['delivery_mail'];
            $email->MessageEmails($EmailInfo);
            $configInfo = [
                'admin_name' => $admin['username'],
                'admin_id' => $admin['admin_id'],
                'order_id' => $info['order_id'],
                'activity_type' => 7,
                'activity_value' => $cn_content,
                'en_activity_value' => $en_content,
                'created_at' =>  self::getDate()
            ];
            $configInsert = ActivityLog::insert($configInfo);
            if($configInsert){
                return $this->success(__('web.edit_success'));
            }else{
                return $this->error(__('web.activitylog_fail'));
            }
        }else{
            return $this->error(__('api.edit_fail'));
        }
    }

    public function deleteOrderApi($data){
        DB::beginTransaction();
        $updateDetails = CardOrderDetails::where('order_id',$data['order_id'])->where('user_id',$data['user_id'])->update(['details_status' => -1]);
        $updateOrder = $this->where('order_id',$data['order_id'])->where('user_id',$data['user_id'])->update(['order_status'=>-1]);
        if($updateDetails && $updateOrder){
            $info = $this->where('order_id',$data['order_id'])->select('order_no')->first();
            $content = '訂單：'.$info['order_no'].', 已經被刪除!!';
            $en_content = '注文: '.$info['order_no'].' が削除されました !!';
            $this->OrderMessage($data['user_id'] ,$content , $en_content );
            DB::commit();
            return $this->success(__('api.delete_success'));
        }else{
            DB::rollBack();
            return $this->error(__('api.delete_fail'));
        }
    }

    /**************************************************common*****************************************************************/
    public function OrderMessage($user_id,$content,$en_content){
        DB::beginTransaction();
        try {
            $info['user_id'] = $user_id;
            $info['system_type'] = 3;
            $info['system_content'] = $content;
            $info['en_system_content'] = $en_content;
            $info['is_read'] = 1;
            $info['created_at'] = date('Y-m-d H:i:s');
            $info['updated_at'] = date('Y-m-d H:i:s');
            $effects = $this->insert($info);
            if(!empty($effects)){
                DB::commit();
                return true;
            }else{
                DB::rollBack();
                return false;
            }
        }catch (\Exception $e) {
            DB::rollBack();
            return false;
        }
    }

    public function getOrderNumber(){
        list($msec, $sec) = explode(' ', microtime());
        $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        // 併發安全：毫秒時間戳 + 隨機尾碼，避免同一毫秒內多筆訂單號重複
        return $msectime . str_pad((string)random_int(0, 99999999), 8, '0', STR_PAD_LEFT);
    }

    public function generateCardOrder($data){
        try {
            $orders['order_no'] = self::getOrderNumber();
            $orders['user_id'] = $data['user_id'];
            $orders['coupon_id'] = $data['coupon_id'];
            $orders['original_cost'] = $data['original_cost'];
            $orders['order_money']= empty($data['order_money']) ? 0 : $data['order_money'];
            $orders['freight_price']= empty($data['freight_price']) ? 0 : $data['freight_price'];
            $orders['booking_price']= empty($data['booking_price']) ? 0 : $data['booking_price'];
            $orders['coupon_money'] = empty($data['coupon_money']) ? 0 : $data['coupon_money'];
            $orders['payment_type']=empty($data['payment_type']) ? 1 : $data['payment_type'];
            $orders['payment_mode']=empty($data['payment_mode'] )? 1 : $data['payment_mode'];
            $orders['order_type']=empty($data['order_type']) ? 1 : $data['order_type'];
            $orders['indent_type']=empty($data['indent_type']) ? 1 : $data['indent_type'];
            $orders['receiving_type']=empty($data['receiving_type']) ? 1 : $data['receiving_type'];
            $orders['delivery_area']=empty($data['delivery_area']) ? '': $data['delivery_area'];
            $orders['delivery_address']=empty($data['delivery_address']) ? '': $data['delivery_address'];
            $orders['delivery_name']=empty($data['delivery_name']) ? '': $data['delivery_name'];
            $orders['delivery_phone']=empty($data['delivery_phone']) ? '': $data['delivery_phone'];
            $orders['delivery_mail']=empty($data['delivery_mail']) ? '': $data['delivery_mail'];
            $orders['order_time']=empty($data['order_time']) ? '': $data['order_time'];
            $orders['order_num']=empty($data['order_num']) ? '': $data['order_num'];
            $orders['order_remark']=empty($data['order_remark']) ? '': $data['order_remark'];
            $orders['payment_status'] = empty($data['payment_status']) ? 1 : $data['payment_status'];
            if (!empty($data['payment_at'])) {
                $orders['payment_at'] = $data['payment_at'];
            }
            $orders['return_status'] = 0;
            $orders['push_state'] = 1;
            $orders['mail_state'] = 1;
            $orders['order_status'] = 1;
            $orders['created_at'] = date('Y-m-d H:i:s');
            $orders['updated_at'] = date('Y-m-d H:i:s');
            $OrderId = $this->insertGetId($orders);
            if(!empty($OrderId)){
                return $OrderId;
            }else{
                return false;
            }
        }catch (\Exception $e) {
            return  $e->getMessage();
        }
    }

    public function setDetailsCardOrder($data){
        $CardInfo=$data['CardInfo'];
        foreach ($CardInfo as $key => $value) {
            $logistics_status= $data['receiving_type']==2 ? 1 : 0;
            $details_id = DB::table('order_details')->insertGetId([
                'order_id' => $data['card_order_id'],
                'user_id' => $data['user_id'],
                'product_id' => $CardInfo[$key]['card_id'],
                'product_name' => $CardInfo[$key]['card_name'],
                'en_product_name' => $CardInfo[$key]['en_card_name'] ?? $CardInfo[$key]['card_name'],
                'product_img' => $CardInfo[$key]['card_img'],
                'product_number' => $CardInfo[$key]['card_number'],
                'product_price' => $CardInfo[$key]['card_price'],
                'product_money' => $CardInfo[$key]['card_number']*$CardInfo[$key]['card_price'],
                'product_type' => $CardInfo[$key]['card_type'],
                'logistics_status' => $logistics_status,
                'details_remark' => $CardInfo[$key]['details_remark'] ?? '',
                'details_status' => 1,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);
            $details[$key] = $details_id;

            //添加訂單詳情快照
            $now = date('Y-m-d H:i:s');
            $CardModel = new \App\Models\Card();
            $User = new User();
            DB::table('order_snapshot')->insert([
                'details_id' => $details_id,
                'coupon_snapshot' => '',
                'product_snapshot' => $CardModel->CardSnapshot($CardInfo[$key]['card_id']),
                'user_snapshot' => $User->UserSnapshot($data['user_id']),
                'snapshot_status' => 1,
                'created_at' => $now,
                'updated_at' => $now
            ]);

            //添加訂單物流記錄（按 order_id）
            DB::table('order_logistics')->insert([
                'user_id' => $data['user_id'],
                'order_id' => $data['card_order_id'],
                'logistics_status' => $logistics_status,
                'created_at' => $now,
                'updated_at' => $now
            ]);
        }
        return implode(',',$details);
    }
}
