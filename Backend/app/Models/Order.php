<?php
/*
 * File: Order.php
 * Project: NewDemo
 * Created Time: 2024-07-15 03:16:47 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-09-09 4:44:36 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */



namespace App\Models;

use App\Http\Controllers\Api\MailController;
use App\Http\Controllers\Common\GeTuiController;
use DateTimeInterface;
use App\Models\Base;
use App\Models\SystemInfo;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Auth\User as Authenticatable;

/**
 * App\Models\Order
 */
class Order extends Base{

    use Notifiable;
    public $timestamps = true;
    protected $table = 'order';

    /**
     * 为数组 / JSON 序列化准备日期。
     */
    protected function serializeDate(DateTimeInterface $date){
        return $date->format($this->dateFormat ?: 'Y-m-d H:i:s');
    }

    /**
     * 關聯訂單詳情表
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function OrderDetails(){
        return $this->hasMany('App\Models\OrderDetails', 'order_id', 'order_id');
    }

    public function CardOrderDetails(){
        return $this->hasMany('App\Models\CardOrderDetails', 'order_id', 'order_id');
    }

    /**
     * 函數構造
     */
    public function __construct(){
        $this->language = app('global_language') == "en" ? "en_" : "";
    }




/*********************************************************全局作用域注册**********************************************************************/
    protected static function boot(){
       parent::boot();
	   static::addGlobalScope('order', function (Builder $builder) {
		  return $builder->where('order.order_status', '<>', -1);
	   });
    }

/*********************************************************admin**************************************************************************/

    /**
     * 獲取訂單列表
     * @param $data
     * @return mixed
     */
    public function getOrderListWeb($data){
        $limit = $data['limit'];
        $page =  ( $data['page'] - 1 ) * $limit;
        $time = [];
        $queryKeyword = null;  // ← 修復：先初始化，避免 Undefined variable
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
        // 用戶名/手機號/郵箱搜索（模糊匹配，必須顯式包裹避免 OR 破壞 AND）
        // 同時搜 users 表（註冊信息）和 order 表（收件人信息）
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
        // 兌換狀態篩選（抽卡訂單適用，0=未兌換, 1=已兌換, 2=發貨）
        $exchangeStatus = isset($data['exchange_status']) && $data['exchange_status'] !== '' ? (int)$data['exchange_status'] : null;
        $logisticsStatus = isset($data['logistics_status']) && $data['logistics_status'] !== '' ? (int)$data['logistics_status'] : null;

        // === indent_type 默認：發貨詳情只查合併單 ===
        $indentTypes = $data['indent_type'] ?? [0];
        if (!is_array($indentTypes)) $indentTypes = [$indentTypes];

        OrderDetails::$modify = true;
        $query = $this->join('users','users.user_id','=','order.user_id')
            ->where($where)
            ->when(!empty($queryKeyword), $queryKeyword)
            ->whereIn('order.indent_type', $indentTypes)
            ->where(function ($query) use ($time){
                if(!empty($time) && $time['start_time'] != ''){
                    return $query->whereBetween('order.payment_at',$time);
                }
            })
            ->select('order.*','users.user_name','users.user_email','users.user_phone');
        // 有 keyword/order_no 搜索時，跳過 exchange_status/logistics_status 篩選
        // 避免搜索時因狀態不匹配把正確訂單過濾掉
        $isSearch = !empty($data['keyword']) || !empty($data['order_no']);
        // 只有 indent_type=2（純卡牌訂單）才走 CardOrderDetails 篩選
        // indent_type=0（合併發貨單）沒有 card_order_details 記錄，不能用 whereHas 篩選
        $isPureCard = !empty(array_intersect($indentTypes, [2]));
        if ($isPureCard && !$isSearch && $exchangeStatus !== null && $exchangeStatus >= 0) {
            $query->whereHas('CardOrderDetails', function ($q) use ($exchangeStatus, $logisticsStatus) {
                $q->where('exchange_status', $exchangeStatus);
                if ($logisticsStatus !== null && $logisticsStatus >= 0) {
                    $q->where('logistics_status', $logisticsStatus);
                }
            });
        }
        // 產品訂單：根據 logistics_status 篩選（通過 OrderDetails）
        $hasProductType = !empty(array_intersect($indentTypes, [1]));
        if ($hasProductType && !$isSearch && $logisticsStatus !== null && $logisticsStatus >= 0) {
            $query->whereHas('OrderDetails', function ($q) use ($logisticsStatus) {
                $q->where('logistics_status', $logisticsStatus);
            });
        }
        // 合併發貨單（indent_type=0）：按 order_details 的 logistics_status 篩選
        // handleCardPack 搬運時把 order_details logistics_status 設為 0
        // 後台發貨後更新為 1
        $isMerge = !empty(array_intersect($indentTypes, [0]));
        if ($isMerge && !$isSearch && $logisticsStatus !== null && $logisticsStatus >= 0) {
            $query->whereHas('OrderDetails', function ($q) use ($logisticsStatus) {
                $q->where('logistics_status', $logisticsStatus);
            });
        }
        $datas['data'] = $query->offset($page)->limit($limit)
            ->orderBy('order.payment_at', 'desc')
            ->get()->toArray();
        // 補上 order_details（產品訂單 indent_type=1 + 合併發貨單 indent_type=0）
        if (!empty(array_intersect($indentTypes, [0, 1]))) {
            $orderIds = array_column($datas['data'], 'order_id');
            if (!empty($orderIds)) {
                $orderDetails = \App\Models\OrderDetails::whereIn('order_id', $orderIds)
                    ->where('details_status', '<>', -1)
                    ->get()->groupBy('order_id');
                // 產品訂單物流（按 details_id）
                $logisticsRecords = DB::table('order_logistics')
                    ->whereIn('order_id', $orderIds)
                    ->where('logistics_status', '<>', -1)
                    ->get()->groupBy('order_id');
                foreach ($datas['data'] as &$item) {
                    $item['order_details'] = isset($orderDetails[$item['order_id']])
                        ? $orderDetails[$item['order_id']]->toArray()
                        : [];
                    // 填充物流信息
                    $logis = $logisticsRecords[$item['order_id']] ?? collect();
                    foreach ($item['order_details'] as &$od) {
                        $od['order_logistics'] = $logis;
                        // 狀態字段轉 int：MySQL 返回字符串，前端 === 嚴格比較會失敗
                        $od['exchange_status'] = (int)($od['exchange_status'] ?? 0);
                        $od['logistics_status'] = (int)($od['logistics_status'] ?? 0);
                    }
                    unset($od);
                    // 訂單級別物流（取第一條物流記錄）
                    $firstLogi = !$logis->isEmpty() ? $logis->first() : null;
                    // 根據 order_details 的 logistics_status 判斷是否已發貨（比 order_logistics 更準確）
                    $hasShippedDetail = collect($item['order_details'])->some(fn($d) => ($d['logistics_status'] ?? 0) == 1);
                    $item['logistics_status'] = $hasShippedDetail ? 1 : 0;
                    $item['logistics_number'] = $firstLogi ? $firstLogi->logistics_number : '';
                }
            }
        }
        // 為純卡牌訂單（indent_type=2）+ 合併發貨單（indent_type=0）補 card_order_details
        // handleCardPack 已把卡牌的 card_order_details 搬到合併單
        if (!empty(array_intersect($indentTypes, [0, 2]))) {
            $orderIds = array_column($datas['data'], 'order_id');
            if (!empty($orderIds)) {
                $cardDetails = \App\Models\CardOrderDetails::whereIn('order_id', $orderIds)
                    ->where('details_status', '<>', -1)
                    ->get()->groupBy('order_id');
                // 訂單級別物流
                $logisticsRecords = DB::table('order_logistics')
                    ->whereIn('order_id', $orderIds)
                    ->where('logistics_status', '<>', -1)
                    ->get()->keyBy('order_id');
                foreach ($datas['data'] as &$item) {
                    $item['card_order_details'] = isset($cardDetails[$item['order_id']])
                        ? $cardDetails[$item['order_id']]->toArray()
                        : [];
                    // 狀態字段轉 int：MySQL 返回字符串，前端 === 嚴格比較會失敗導致狀態顯示錯誤
                    foreach ($item['card_order_details'] as &$cd) {
                        $cd['exchange_status'] = (int)($cd['exchange_status'] ?? 0);
                        $cd['logistics_status'] = (int)($cd['logistics_status'] ?? 0);
                    }
                    unset($cd);
                    // 填充訂單級別物流信息
                    $logi = $logisticsRecords[$item['order_id']] ?? null;
                    $item['logistics_status'] = $logi ? (int)$logi->logistics_status : 0;
                    $item['logistics_number'] = $logi ? $logi->logistics_number : '';
                }
            }
        }
        $countQuery = $this->join('users','users.user_id','=','order.user_id')
            ->where($where)
            ->when(!empty($queryKeyword), $queryKeyword)
            ->whereIn('order.indent_type', $indentTypes)
            ->where(function ($query) use ($time){
                if(!empty($time) && $time['start_time'] != ''){
                    return $query->whereBetween('order.payment_at',$time);
                }
            });
        if ($isPureCard && !$isSearch && $exchangeStatus !== null && $exchangeStatus >= 0) {
            $countQuery->whereHas('CardOrderDetails', function ($q) use ($exchangeStatus, $logisticsStatus) {
                $q->where('exchange_status', $exchangeStatus);
                if ($logisticsStatus !== null && $logisticsStatus >= 0) {
                    $q->where('logistics_status', $logisticsStatus);
                }
            });
        }
        // 產品訂單 count 篩選
        if ($hasProductType && !$isSearch && $logisticsStatus !== null && $logisticsStatus >= 0) {
            $countQuery->whereHas('OrderDetails', function ($q) use ($logisticsStatus) {
                $q->where('logistics_status', $logisticsStatus);
            });
        }
        // 合併發貨單 count 篩選
        if ($isMerge && !$isSearch && $logisticsStatus !== null && $logisticsStatus >= 0) {
            $countQuery->whereHas('OrderDetails', function ($q) use ($logisticsStatus) {
                $q->where('logistics_status', $logisticsStatus);
            });
        }
        $datas['count'] = $countQuery->count();
        $money = $this->getGoodsSale($data['indent_type'], $time);//獲取訂單銷售額
        $datas['total_money'] = $money['total_money'];
        $datas['day_money'] = $money['day_money'];
        $datas['month_money'] = $money['month_money'];
        return $datas;
    }

    /**
     * 獲取訂單銷售額
     * @param $type
     * @param $time
     * @return mixed
     */
    public function getGoodsSale($type,$time){
        $data['total_money'] = Order::join('users','order.user_id','=','users.user_id')
            ->whereIn('order.indent_type',$type)
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
        $data['day_money'] = Order::join('users','order.user_id','=','users.user_id')
            ->where('order.order_status','<>',-1)
            ->where('order.payment_status','>',2)
            ->where('users.user_status','<>',-1)
            ->whereIn('order.indent_type',$type)
            ->where(function ($query) use ($day_time){
                return $query->whereBetween('order.payment_at',$day_time);
            })
            ->sum('order.order_money');

        $month_time['start_time'] = date('Y-m-01 00:00:00' ,  strtotime('this month'));
        $month_time['end_time'] = date('Y-m-t 23:59:59',  strtotime('this month'));
        $data['month_money'] = Order::join('users','order.user_id','=','users.user_id')
            ->where('order.order_status','<>',-1)
            ->where('order.payment_status','>',2)
            ->where('users.user_status','<>',-1)
            ->whereIn('order.indent_type',$type)
            ->where(function ($query) use ($month_time){
                return $query->whereBetween('order.payment_at',$month_time);
            })
            ->sum('order.order_money');
        return $data;
    }

    /**
     * 修改预约时间
     * @param $data
     * @return array
     */
    public function editOrderTimeWeb($data,$token){
        $admin = Admins::where('token',$token)->select('admin_id','username')->first();
        $update = $this->where('order_id',$data['order_id'])->update(['order_time'=>$data['order_time']]);
        if($update){
            $info = $this->where('order_id',$data['order_id'])->select('order_no','delivery_mail','user_id')->first();
            $cn_content = '訂單：'.$info['order_no'].',預約時間改變！';
            $en_content = 'Order：'.$info['order_no'].',Reservation Time Change！';
            if($this->language == "en" ){
                $languagenow = 'en';//英文模式
                $EmailInfo['Message']=$en_content;
                @self::pushOrderMessage($data['order_id'],$cn_content,$en_content,$languagenow);
            }else{
                $languagenow = 'cn';
                $EmailInfo['Message']=$cn_content;
                @self::pushOrderMessage($data['order_id'],$cn_content,$en_content,$languagenow);
            }
            $this->OrderMessage( $info['user_id'] ,$cn_content , $en_content );
            $email=new MailController();
            $EmailInfo['Email']=$info['delivery_mail'];
            $email->MessageEmails($EmailInfo);
            $editInfo = [//增加訂單操作日誌
                'admin_name' => $admin['username'],
                'admin_id' => $admin['admin_id'],
                'order_id' => $data['order_id'],
                'activity_type' => 6,//修改預約時間
                'activity_value' => $cn_content,
                'en_activity_value' => $en_content,
                'created_at' =>  self::getDate()
            ];
            $editInsert = ActivityLog::insert($editInfo);
            if($editInsert){
                return $this->success(__('web.edit_success'));
            }else{
                return $this->error(__('web.activitylog_fail'));
            }
        }else{
            return $this->error(__('web.edit_fail'));
        }
    }

    /**
     * 修改订单支付时间(修改订单支付時間/与订单详情同步)后缀?order=true
     * @param $datas
     * @return array
     */
    public function editPaymentTime($datas){
        DB::beginTransaction();
        if (!empty($datas['order_id']) || !empty($datas['payment_at'])) {
            Order::where('order_id', $datas['order_id'])->update(['payment_at' => $datas['payment_at']]);
            DB::commit();
            return $this->success(__('web.edit_success'));
        } else {
            DB::rollBack();
            return $this->error(__('web.edit_fail'));
        }
    }

    /**
     * 确认支付
     * @param $orderID
     * @return array
     */
    public function confirmPaymentWeb($orderID,$token){
        $admin = Admins::where('token',$token)->select('admin_id','username')->first();
        $data = $this->where('order_id',$orderID)->update(['payment_status'=>3,'payment_at'=>self::getDate()]);
        if($data){
            $info = $this->where('order_id',$orderID)->select('order_no','user_id','delivery_mail')->first();
            $cn_content = '訂單：'.$info['order_no'].',已完成支付！';
            $en_content = 'Order：'.$info['order_no'].',has completed payment！';
            if($this->language == "en" ){
                $languagenow = 'en';//英文模式
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
            $configInfo = [//增加訂單操作日誌
                'admin_name' => $admin['username'],
                'admin_id' => $admin['admin_id'],
                'order_id' => $orderID,
                'activity_type' => 7,//確認訂單支付
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

    /**
     * 刪除訂單
     * @param $orderID
     * @return array
     */
    public function deleteOrderWeb($orderID,$token){
        DB::beginTransaction();
        $admin = Admins::where('token',$token)->select('admin_id','username')->first();
        // 統一轉數組，支持批量刪除
        $orderIDs = is_array($orderID) ? $orderID : explode(',', $orderID);
        $orderIDs = array_filter(array_map('intval', $orderIDs));
        if (empty($orderIDs)) {
            DB::rollBack();
            return $this->error('參數錯誤');
        }
        $updateDetails = OrderDetails::whereIn('order_id', $orderIDs)->update(['details_status' => -1]);
        $updateOrder = $this->whereIn('order_id', $orderIDs)->update(['order_status'=>-1]);
        $orders = $this->whereIn('order_id', $orderIDs)->select('order_id', 'order_no')->get();
        // 准备日志数据
        $configInfo = [];
        $currentTime = self::getDate();
        foreach ($orders as $order) {
            $content = '訂單：'.$order['order_no'].',已刪除！';
            $en_content ='Order：'.$order['order_no'].',has been deleted！';

            $configInfo[] = [
                'admin_name' => $admin['username'],
                'admin_id' => $admin['admin_id'],
                'order_id' => $order['order_id'],
                'activity_type' => 9,//刪除訂單
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

    /**
     * 獲取日曆訂單
     * @param $data
     * @return mixed
     */
    public function getCalendarOrdersWeb($data){
        $where[] = array('order_details.details_status','=',1);
        $where[] = array('users.user_status','=',1);
        $datas = $this->join('order_details', 'order_details.order_id', '=', 'order.order_id')
            ->join('product', 'order_details.product_id', '=', 'product.product_id')
            ->join('users', 'order_details.user_id', '=', 'users.user_id')
            ->whereIn('order.indent_type', $data['indent_type'])//訂單類型  1產品訂單  2預約訂單
            ->where($where)
            ->select('order.order_id','order.order_no','order.created_at','order.updated_at','order.order_time','order.payment_at')
            ->orderBy('order.updated_at', 'desc')
            ->get()->toArray();
        return $datas;
    }

    /**
     * 添加線下訂單
     * @param $data
     * @return array
     */
    public function addOfflineOrdersWeb($data,$token){
        $admin = Admins::where('token',$token)->select('username','admin_id')->first();
        DB::beginTransaction();
        $order = [
            'user_id' => $data[0]['user_id'],   //用戶id
            'order_no' =>  self::getOrderNumber(),  //訂單號
            'order_money' => $data[0]['order_money'],   //訂單支付金額
            'original_cost' => $data[0]['original_cost'],   //訂單原价
            'delivery_address' => '線下訂單',   //收货地址-地址
            'delivery_area' => '線下訂單',  //收货地址-区域
            'delivery_name' => '線下訂單',  //收货地址-姓名
            'delivery_phone' => '線下訂單', //收货地址-电话
            'delivery_mail' => '線下訂單',  //收货地址-邮件
            'payment_type' => 1,    //支付类型：1：立即支付，2：預約到店查看
            'payment_mode' => 5,    //支付方式 1：paypal  2：Stripe，3：錢包 ，4：其他支付（上傳支付圖片）5：線下支付
            'order_type' => 2,  //訂單類型：1：線上訂單，2：線下訂單
            'indent_type' =>$data[0]['product_type'],   //訂單類型  1產品訂單  2預約訂單
            'order_time' =>empty($data[0]['order_time']) ? '': $data[0]['order_time'],   //訂單類型  1產品訂單  2預約訂單
            'receiving_type' => 3,  //取货方式：1：商家发货，2：自取
            'payment_at' =>  self::getDate(),   //支付成功時間
            'created_at' =>  self::getDate(),
            'updated_at' =>  self::getDate()
        ];
		if ($data[0]['product_type']==2) {
            $order['payment_status'] = 3;  //支付状态：1：待支付  2：待核验（其他支付的）   3：已支付  4：已取消  5：已完成
        }else{
            $order['payment_status'] = 5;  //支付状态：1：待支付  2：待核验（其他支付的）   3：已支付  4：已取消  5：已完成
        }
        $orderID = $this->insertGetId($order);
        if(!empty($orderID)){
            $addDetails = true;
            for($i=0;$i<count($data);$i++){
                $product_specs[$i] = [];
                $productRelation = true;
                $product = true;
                // 只有在product_relation_id不为空时才处理规格
                if (!empty($data[$i]['product_relation_id'])) {
                    $productRelationRecord = ProductRelation::where('product_relation_id', $data[$i]['product_relation_id'])->first();
                
                    if ($productRelationRecord) {
                        $product_specs_value_id = $productRelationRecord['product_specs_value_id'];
                        $product_specs = ProductSpecsValue::whereIn('product_specs_value_id', explode('-', $product_specs_value_id))->get()->toArray();
                    
                        if ($data[$i]['product_type'] == 1) {
                            $productRelation = ProductRelation::where('product_relation_id', $data[$i]['product_relation_id'])->decrement('stock', $data[$i]['product_number']);
                        }
                    } else {
                        $productRelation = false;
                    }
                }
                $detailsInfo[$i] = [
                    'order_id' => $orderID,
                    'user_id' => $data[$i]['user_id'],
                    'product_id' => $data[$i]['product_id'],
                    'product_relation_id' => $data[$i]['product_relation_id'],
                    'product_specs' => json_encode($product_specs[$i]),
                    'product_name' => $data[$i]['product_name'],
                    'en_product_name' => $data[$i]['en_product_name'] ?? $data[$i]['product_name'],//没有英文名就用中文名
                    'product_img' => $data[$i]['product_img'],
                    'product_number' => $data[$i]['product_number'],
                    'product_price' => $data[$i]['product_price'],
                    'product_money' => $data[$i]['product_money'],
                    'product_type' => $data[$i]['product_type'],
                    'logistics_status' => 2,
                    'created_at' => self::getDate(),
                    'updated_at' => self::getDate()
                ];
                $details = OrderDetails::insertGetId($detailsInfo[$i]);
                if($data[$i]['product_type'] == 1){
                    $product = Product::where('product_id',$data[$i]['product_id'])->decrement('product_stock',$data[$i]['product_number']);
                }
                //添加訂單詳情快照-begin
                $Snapshot['details_id']=$details;
                $Coupon=new Coupon;
                $Product=new Product;
                $Snapshot['product_snapshot']=$Product->ProductSnapshot($data[$i]['product_id']);
                $User=new User;
                $Snapshot['user_snapshot']=$User->UserSnapshot($data[$i]['user_id']);
                $OrderSnapshot=new OrderSnapshot;
                $SnapshotInfo=$OrderSnapshot->addOrderSnapshot($Snapshot);
                //添加訂單詳情快照-end
                if( empty($details) || !$product || !$productRelation){
                    $addDetails = false;
                    break;
                }
            }
            if($addDetails){
                $cn_content= '新增了單號爲: '.$order["order_no"].' 的綫下訂單';
                $en_content= 'Added offline order with order no: '.$order["order_no"];
                $configInfo = [//增加操作日誌
                    'admin_name' => $admin['username'],
                    'admin_id' => $admin['admin_id'],
                    'activity_type' => 12,//activity_type=12新增綫下訂單
                    'activity_value' => $cn_content,
                    'en_activity_value' => $en_content,
                    'created_at' =>  self::getDate()
                ];
                $configInsert = ActivityLog::insert($configInfo);
                DB::commit();
                return $this->success(__('web.add_success'));
            }else{
                DB::rollBack();
                return $this->error(__('web.add_fail'));
            }
        }else{
            DB::rollBack();
            return $this->error(__('web.add_fail'));
        }
    }

    /**
     * web創建訂單
     * @param $data
     * @return array
     */
    public function WebAddOrder($data){
        DB::beginTransaction();
        $order = [
            'user_id' => $data[0]['user_id'],   //用戶id
            'order_no' =>  self::getOrderNumber(),  //訂單號
            'order_money' => $data[0]['order_money'],   //訂單支付金額
            'original_cost' => $data[0]['original_cost'],   //訂單原价
            'delivery_address' => $data[0]['delivery_address'],   //delivery_address收货地址-地址--非必要
            'delivery_area' => $data[0]['delivery_area'],  //delivery_area收货区域--非必要
            'delivery_name' => $data[0]['delivery_name'],  //delivery_name收货姓名--非必要
            'delivery_phone' => $data[0]['delivery_phone'], //delivery_phone收货电话--非必要
            'delivery_mail' => $data[0]['delivery_mail'],  //delivery_mail收货邮件--非必要
            'payment_type' => $data[0]['payment_type'],    //payment_type支付类型：1：立即支付，2：預約到店查看--必要
            'payment_mode' => $data[0]['payment_mode'],    //payment_mode支付方式 1：paypal  2：Stripe，3：錢包 ，4：其他支付（上傳支付圖片）5：線下支付--必要
            'order_type' => $data[0]['order_type'],  //order_type訂單類型：1：線上訂單，2：線下訂單--必要
            'order_time' => $data[0]['order_time'],  //order_time預約時間
            'indent_type' =>$data[0]['product_type'],   //訂單類型  1產品訂單  2預約訂單
            'receiving_type' => $data[0]['receiving_type'],  //receiving_type取货方式：1：商家发货，2：自取--必要
            'payment_status' => $data[0]['payment_status'],  //payment_status支付状态：1：待支付  2：待核验（其他支付的）   3：已支付  4：已取消  5：已完成--必要
            'payment_at' =>  $data[0]['payment_at'],   //payment_at支付成功時間--非必要
            'order_remark' => $data[0]['order_remark'] ?? '', //訂單備注
            'created_at' =>  empty($data[0]['created_at']) ? self::getDate(): $data[0]['created_at'],   //created_at訂單創建時間--必要
            'updated_at' =>  self::getDate()
        ];
        $orderID = $this->insertGetId($order);
        if(!empty($orderID)){
            $addDetails = true;
            for($i=0;$i<count($data);$i++){
                $specsData = []; // 改名，避免冲突
                $productRelation = true;
                $product = true;
                // 只有在product_relation_id不为空时才处理规格
                if (!empty($data[$i]['product_relation_id'])) {
                    $productRelationRecord = ProductRelation::where('product_relation_id', $data[$i]['product_relation_id'])->first();
                
                    if ($productRelationRecord) {
                        $product_specs_value_id = $productRelationRecord['product_specs_value_id'];
                        $specsData = ProductSpecsValue::whereIn('product_specs_value_id', explode('-', $product_specs_value_id))->get()->toArray(); // 改名
                    
                        if ($data[$i]['product_type'] == 1) {
                            $productRelation = ProductRelation::where('product_relation_id', $data[$i]['product_relation_id'])->decrement('stock', $data[$i]['product_number']);
                        }
                    } else {
                        $productRelation = false;
                    }
                }
                $detailsInfo[$i] = [
                    'order_id' => $orderID,
                    'user_id' => $data[$i]['user_id'],
                    'product_id' => $data[$i]['product_id'],
                    'product_relation_id' => $data[$i]['product_relation_id'],
                    'product_specs' => json_encode($specsData), // 使用新变量名
                    'product_name' => $data[$i]['product_name'],
                    'en_product_name' => $data[$i]['en_product_name'] ?? $data[$i]['product_name'],//没有英文名就用中文名
                    'product_img' => $data[$i]['product_img'],
                    'product_number' => $data[$i]['product_number'],
                    'product_price' => $data[$i]['product_price'],
                    'product_money' => $data[$i]['product_money'],
                    'product_type' => $data[$i]['product_type'],
                    'logistics_status' => 2,
                    'created_at' => $data[0]['created_at'],
                    'updated_at' => self::getDate()
                ];
                $details = OrderDetails::insertGetId($detailsInfo[$i]);
                if($data[$i]['product_type'] == 1){
                    $product = Product::where('product_id',$data[$i]['product_id'])->decrement('product_stock',$data[$i]['product_number']);
                }
                $logisticsInfo[$i] = [
                    'details_id' => $details,
                    'user_id' => $data[$i]['user_id'],
                    'logistics_status' => 2,
                    'created_at' => self::getDate(),
                    'updated_at' => self::getDate()
                ];
                $logistics = OrderLogistics::insert($logisticsInfo[$i]);
                //添加訂單詳情快照-begin
                $Snapshot['details_id']=$details;
                $Coupon=new Coupon;
                $Product=new Product;
                $Snapshot['product_snapshot']=$Product->ProductSnapshot($data[$i]['product_id']);
                $User=new User;
                $Snapshot['user_snapshot']=$User->UserSnapshot($data[$i]['user_id']);
                $OrderSnapshot=new OrderSnapshot;
                $SnapshotInfo=$OrderSnapshot->addOrderSnapshot($Snapshot);
                //添加訂單詳情快照-end
                if( empty($details) || !$product || !$productRelation || !$logistics){
                    $addDetails = false;
                    break;
                }
            }
            if($addDetails){
                DB::commit();
                return $this->success(__('web.add_success'));
            }else{
                DB::rollBack();
                return $this->error(__('web.add_fail'));
            }
        }else{
            DB::rollBack();
            return $this->error(__('web.add_fail'));
        }
    }

    /**
     * 獲取訂單分析
     * @param $data
     * @param $language
     * @return array
     */
    public function getOrderAnalysisWeb($data,$language){
        $limit = $data['limit'];
        $page = ($data['page'] - 1) * $limit;

        $fieldName = 'sale_number';
        $sort = 'desc';
        $time = [];

        if(!empty($data['time'])){
            $time['start_time'] = $data['time'][0];
            $time['end_time'] = $data['time'][1];
        }
        if(!empty($data['sort'])){
            $sort = $data['sort'];
        }
        if(!empty($data['fieldName'])){
            $fieldName = $data['fieldName'];
        }

        $where[] = array('order.payment_status', '>=', 3);
        $where[] = array('order.order_status', '=', 1);
        $where[] = array('users.user_status', '=', 1);

        //查詢產品信息和 瀏覽量 和 收藏量 和 庫存
        $productInfo = Product::leftjoin('order_details','order_details.product_id','=','product.product_id')
            ->where('product.product_status','<>', -1)
            // ->where('product.product_type','=', 1) //不再只統計即時產品,預約服務產品也加入統計
            ->where(function ($query) use($data){
                if(!empty($data['product_name'])){
                    $query->whereRaw('(concat(product.product_name,product.en_product_name) like "%' . $data['product_name'] . '%")');
                }
            })
            ->where(function ($query) use ($time) {
                if ($time != null)
                    $query->whereBetween('order_details.created_at', $time);
            })
            ->select('product.product_id','product.'.$language.'product_name as product_name','product.product_stock','product.product_img','product.product_price','product.product_type','product.browse_number')
            ->withCount(['userCollect as user_collect_number'=>function($query){
                $query->select(DB::raw("COUNT(collect_id)"));
            }])
            ->groupBy("product.product_id")
            ->get()->toArray();

        //该商品销售总额和銷售總量
        if(!empty($productInfo)){
            foreach ($productInfo as $key => $value) {
                $info[$key] = OrderDetails::join('users','users.user_id','=','order_details.user_id')
                    ->join('order','order_details.order_id','=','order.order_id')
                    ->where($where)
                    ->where('order_details.product_id', $productInfo[$key]['product_id'])
                    ->where(function ($query) use ($time) {
                        if ($time != null)
                            $query->whereBetween('order_details.created_at', $time);
                    })
                    ->select('order_details.details_id',DB::raw('SUM(order_details.product_money) as product_money'),DB::raw('SUM(order_details.product_number) as product_number'))
                    ->groupBy("order_details.product_id")
                    ->first();
                $productInfo[$key]['sale_money'] = empty(json_decode(json_encode($info[$key], true),true)['product_money']) ? 0 : json_decode(json_encode($info[$key], true),true)['product_money'];
                $productInfo[$key]['sale_number'] = empty(json_decode(json_encode($info[$key], true),true)['product_number']) ? 0 : json_decode(json_encode($info[$key], true),true)['product_number'];
            }
        }

        $last_names = array_column($productInfo,$fieldName);
        if($sort == 'asc'){
            array_multisort($last_names,SORT_ASC,$productInfo);
        }else if($sort == 'desc'){
            array_multisort($last_names,SORT_DESC,$productInfo);
        }

        $datas['data'] = array_slice($productInfo,$page,$limit);
        $datas['count'] = count($productInfo);

        return $this->success(__('web.success'),$datas);

    }

    /**
     * 獲取銷售方向分析
     * @param $data
     * @param $language
     * @return array
     */
    public function getSalesDirectionWeb($data,$language){
        $threeMonths = Carbon::now()->subMonths(3)->format('Y-m-d');  //三个月前的时间
        $where[] = array('order.payment_status', '>=', 3);
        $where[] = array('order.order_status', '=', 1);
        $where[] = array('users.user_status', '=', 1);
        $where[] = array('product.product_status', '=', 1);
        $detailsData = OrderDetails::join('users','users.user_id','=','order_details.user_id')
            ->join('order','order_details.order_id','=','order.order_id')
            ->join('product','order_details.product_id','=','product.product_id')
            ->join('product_sort','product_sort.product_sort_id','=','product.product_sort_id')
            ->where($where)
            ->whereNotNull('order.payment_at')
            ->whereBetween('order.payment_at',[$threeMonths,self::getDate()])
            ->select('product.'.$language.'product_name as product_name','product_sort.'.$language.'product_sort_name as product_sort_name',
                DB::raw('SUM(order_details.product_number) as product_number'),DB::raw('DATE(order.payment_at) as payment_date'),
                DB::raw('SUM(order_details.product_money) as money'))
            ->groupBy('payment_date','order_details.product_id')
            ->orderBy('payment_date')
            ->get()->toArray();

        $datas['saleData'] = $detailsData;
        $datas['totalSales'] = Collection::make($detailsData)
            ->groupBy('product_sort_name') // 按 product_sort_name 分组
            ->map(function ($item, $productName) {
                // 计算每个组的 product_number 的总和
                $totalNumber = $item->sum('product_number');

                // 返回一个包含 product_name 和 product_number 总和的数组
                return [
                    'product_sort_name' => $productName,
                    'sort_number' => $totalNumber,
                ];
            })
            ->sortByDesc('product_number')
            ->take(3)
            ->values() // 重置键名
            ->all();

        return $this->success(__('web.success'),$datas);
    }

/**********************************************************api****************************************************************************/
    /**
     * 獲取訂單列表
     * @param $data
     * @return mixed
     */
    public function getOrderListApi($data){
        $limit = empty($data['limit']) ? 10 : $data['limit'];
        $page =  ($data['page'] - 1) * $limit;
        $where[] = array('users.user_status', '<>', -1);
        $where[] = array('order.order_status', '<>', -1);
        $where[] = array('order.user_id', '=', $data['user_id']);

        if (!empty($data['payment_type'])) {
            $where['order.payment_type'] = $data['payment_type'];
        }
        // indent_type 統一在下面處理（L820 起），這裡不加，避免重複條件

        OrderDetails::$modify = true;
        OrderSnapshot::$modify = true;

        // 先查詢訂單列表（不帶 details），獲取 indent_type
        $orderQuery = $this->join('users', 'users.user_id', '=', 'order.user_id')
            ->where($where);

        // 支付狀態篩選
        if (!empty($data['payment_status'])) {
            if ($data['payment_status'] == 6) {
                // 已評論：透過 order_details.logistics_status=3 篩選
                $orderQuery->whereHas('OrderDetails', function ($q) {
                    $q->where('logistics_status', 3);
                });
            } else {
                $orderQuery->where('order.payment_status', $data['payment_status']);
            }
        }

        // 關鍵字搜索：訂單號 / 商品名（含英文名）
        if (!empty($data['keyword'])) {
            $keyword = trim($data['keyword']);
            $orderQuery->where(function ($q) use ($keyword) {
                $q->where('order.order_no', 'like', '%' . $keyword . '%')
                  ->orWhereHas('OrderDetails', function ($qd) use ($keyword) {
                      $qd->where('order_details.product_name', 'like', '%' . $keyword . '%')
                         ->orWhere('order_details.en_product_name', 'like', '%' . $keyword . '%');
                  });
            });
        }

        // === indent_type 過濾 ===
        // 注意：indent_type=0 的發貨合併單**有** order_details（搬過來的），要顯示給用戶看！
        // 舊版廢合併單（沒 details）通過 order_status=-1 或 remark LIKE '%發貨合併%' 擋
        // APP 傳 "0" 或沒傳 → 顯示 0+1+2（全部）
        // APP 傳 "1" → 只產品；傳 "2" → 只抽卡
        $indentType = isset($data['indent_type']) ? (int)$data['indent_type'] : -1;
        if (in_array($indentType, [1, 2], true)) {
            // APP 明確指定了產品或抽卡 → 精確篩選
            $orderQuery->where('order.indent_type', '=', $indentType);
        } else {
            // 全部：包含 0（發貨合併）、1（產品）、2（抽卡）
            $orderQuery->whereIn('order.indent_type', [0, 1, 2]);
        }

        // 雙保險：舊版 handleCardPack 留下的廢合併單（沒 order_details 的）
        // 通過 order_remark LIKE '%發貨合併%' 且 order_status != -1 擋掉
        // 新合併單 order_status=1 但有 details，通過 has details 檢查

        // 只顯示「已處理」的訂單：
        // 發貨合併單(indent_type=0)：本身就是已申請發貨，直接顯示
        // 抽卡訂單(indent_type=2)：有 detail 的 exchange_status >= 1（已兌換/已發貨）
        // 產品訂單(indent_type=1)：有 detail 的 logistics_status = 1（已發貨）
        if (empty($data['show_all'])) {
            $orderQuery->where(function ($q) {
                // 發貨合併單（已申請發貨）→ 直接顯示
                $q->where('order.indent_type', 0)
                // 抽卡已處理
                ->orWhere(function ($q2) {
                    $q2->where('order.indent_type', 2)
                       ->whereHas('OrderDetails', function ($qd) {
                           $qd->where('order_details.exchange_status', '>=', 1);
                       });
                })
                // 產品已發貨
                ->orWhere(function ($q2) {
                    $q2->where('order.indent_type', 1)
                       ->whereHas('OrderDetails', function ($qd) {
                           $qd->where('order_details.logistics_status', '=', 1);
                       });
                });
            });
        }

        // 發貨狀態篩選（在原有「只顯示已處理」基礎上疊加）：
        // 1=已發貨：後台已填寫物流單號；0=未發貨：尚未填寫物流單號
        $shippingStatus = (isset($data['shipping_status']) && $data['shipping_status'] !== '')
            ? (int)$data['shipping_status'] : null;
        if ($shippingStatus === 1) {
            $orderQuery->whereExists(function ($q) {
                $q->from('order_logistics')
                  ->whereColumn('order_logistics.order_id', 'order.order_id')
                  ->where('order_logistics.logistics_status', '<>', -1)
                  ->whereNotNull('order_logistics.logistics_number')
                  ->where('order_logistics.logistics_number', '<>', '');
            });
        } elseif ($shippingStatus === 0) {
            $orderQuery->whereNotExists(function ($q) {
                $q->from('order_logistics')
                  ->whereColumn('order_logistics.order_id', 'order.order_id')
                  ->where('order_logistics.logistics_status', '<>', -1)
                  ->whereNotNull('order_logistics.logistics_number')
                  ->where('order_logistics.logistics_number', '<>', '');
            });
        }

        $info = $orderQuery->select('order.*')
            ->distinct('order.order_id')
            ->orderBy('order.created_at', 'desc')
            ->paginate($limit, ['*'], 'page', $data['page']);

        $effects = $info->items();

        // 根據 indent_type 分批載入 details
        // 注意：$effects 是 Eloquent Model 數組，PHP 原生 array_column 讀不到
        // protected $attributes 裡的屬性，必須用 collect()->pluck() 或 foreach
        $orderIds = [];
        $orderIdToType = [];
        foreach ($effects as $item) {
            $oid = $item['order_id'];
            $orderIds[] = $oid;
            $orderIdToType[$oid] = $item['indent_type'];
        }

        // 批量載入 OrderDetails（含物流信息）
        $allDetails = \App\Models\OrderDetails::with('OrderLogistics')
            ->whereIn('order_id', $orderIds)
            ->where('details_status', '<>', -1)
            ->get()
            ->groupBy('order_id');

        // 訂單級別物流（order_logistics 按 order_id）
        $orderLogistics = DB::table('order_logistics')
            ->whereIn('order_id', $orderIds)
            ->where('logistics_status', '<>', -1)
            ->get()
            ->keyBy('order_id');

        $codeUpdates = [];

        foreach ($effects as $key => &$order) {
            $oid = $order['order_id'];
            $isCardOrder = ($orderIdToType[$oid] == 2);

            if (isset($allDetails[$oid])) {
                $details = array_values($allDetails[$oid]->toArray());
                // 狀態字段轉 int：MySQL 返回字符串，前端 === 嚴格比較會失敗導致狀態顯示錯誤
                foreach ($details as &$d) {
                    $d['exchange_status'] = (int)($d['exchange_status'] ?? 0);
                    $d['logistics_status'] = (int)($d['logistics_status'] ?? 0);
                }
                unset($d);
                // 兩個 key 都塞同一份產品數據：
                // 舊版 APP 可能只讀 order_details（不管訂單類型）
                // 新版/後台可能讀 card_order_details
                // 兩個都塞 = 誰來取都不會空
                $order['order_details'] = $details;
                $order['card_order_details'] = $details;
            } else {
                $order['order_details'] = [];
                $order['card_order_details'] = [];
            }

            // 訂單級別物流信息
            $logi = $orderLogistics[$oid] ?? null;
            $order['logistics_status'] = $logi ? (int)$logi->logistics_status : 0;
            $order['logistics_number'] = $logi ? $logi->logistics_number : '';

            if (empty($order['order_code'])) {
                // 只對缺失的訂單生成一次並落庫，避免每次翻頁重複上傳 OSS
                $orderCode = $this->getQRCode($order['order_no']);
                $order['order_code'] = $orderCode;
                if (!empty($orderCode)) {
                    $codeUpdates[] = ['order_id' => $oid, 'order_code' => $orderCode];
                }
            }
            // 計算訂單詳情價格之和（使用 OrderDetails 的 product_money）
            $detailsMoney = 0;
            foreach ($order['order_details'] as $detail) {
                $detailsMoney += (float)($detail['product_money'] ?? 0);
            }
            if ($detailsMoney == 0) {
                // order_money 已在主查詢 select('order.*') 載入，無需再逐條查庫
                $detailsMoney = (float)($order['order_money'] ?? 0);
            }
            // 計算物流單號數量（排除空值）
            $logisticsNum = 0;
            foreach ($order['order_details'] as $detail) {
                if (!empty($detail['order_logistics'])) {
                    foreach ($detail['order_logistics'] as $logi) {
                        if (!empty($logi['logistics_number'])) {
                            $logisticsNum++;
                        }
                    }
                }
            }
            $order['logistics_num'] = $logisticsNum;
            $order['details_money'] = round($detailsMoney, 2);
        }
        unset($order);

        // 一次寫回本次生成的 order_code，避免後續每次請求重複上傳 OSS
        if (!empty($codeUpdates)) {
            foreach ($codeUpdates as $codeItem) {
                DB::table('order')->where('order_id', $codeItem['order_id'])
                    ->update(['order_code' => $codeItem['order_code']]);
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



    /**
     * 獲取訂單詳情
     * @param $data
     * @return mixed
     */
    public function getOrderDetailsApi($data){
        $where[] = array('users.user_status', '<>', -1);
        $where[] = array('order.order_status', '<>', -1);
        $where[] = array('order.user_id', '=', $data['user_id']);
        $where[] = array('order.order_id', '=', $data['order_id']);
        OrderDetails::$modify = true;
        OrderSnapshot::$modify = true;

        // 先查詢訂單主體（不帶 details），獲取 indent_type
        $orderFields = ['order.*'];
        $effects = $this->join('users', 'users.user_id', '=', 'order.user_id')
            ->where($where)
            ->select('order.*')
            ->first();

        if (empty($effects)) {
            return $this->success(__('api.list_null'), $effects);
        }

        // 根據 indent_type 條件載入 details（不再載入 OrderLogistics，改為訂單級別查詢）
        $isCardOrder = ($effects['indent_type'] == 2);
        if ($isCardOrder) {
            // 抽卡訂單：只載入 exchange_status=2（待發貨和已發貨）的卡牌
            $effects->load(['OrderDetails' => function ($query) {
                $query->where('exchange_status', 2);
            }]);
        } else {
            // 產品訂單：載入全部
            $effects->load('OrderDetails');
        }

        // 訂單級別物流
        $logisticsInfo = DB::table('order_logistics')
            ->where('order_id', $effects['order_id'])
            ->where('logistics_status', '<>', -1)
            ->first();

        if (empty($effects['order_code'])) {
            // 生成一次並落庫，避免每次請求重複上傳 OSS
            $orderCode = $this->getQRCode($effects['order_no']);
            $effects['order_code'] = $orderCode;
            if (!empty($orderCode)) {
                DB::table('order')->where('order_id', $effects['order_id'])
                    ->update(['order_code' => $orderCode]);
            }
        }
        // 訂單級別物流信息
        $effects['logistics_number'] = $logisticsInfo->logistics_number ?? '';
        $effects['logistics_status'] = $logisticsInfo->logistics_status ?? 0;
        // 計算訂單詳情價格之和（直接從資料庫查詢，不經過 model）
        $detailsMoney = DB::table('order_details')
            ->where('order_id', $effects['order_id'])
            ->where('details_status', '<>', -1);
        if ($isCardOrder) {
            $detailsMoney = $detailsMoney->where('exchange_status', 2);
        }
        $detailsMoney = (float)$detailsMoney->sum('product_money');
        if ($detailsMoney == 0) {
            $detailsMoney = (float)DB::table('order')
                ->where('order_id', $effects['order_id'])
                ->value('order_money');
        }
        $effects['details_money'] = round($detailsMoney, 2);
        return $this->success(__('api.list_success'), $effects);
    }

    /**
     * @param mixed $data
     * @return mixed
     * 檢查并取消用戶超時訂單
     */
    public function CheckOrderExpired($data){
        // $data['client_id'] = User::where('user_id',$data['user_id'])->whereNotNull('client_id')->first()->client_id;
        $expiredTime = Carbon::now()->subMinutes(5);
        //統計取消的數量
        $expiredNum = $this->where('order.user_id', $data['user_id'])
            ->where('payment_status', '<', 2)
            ->where('order_status', '=', 1)
            ->where('created_at', '<=', $expiredTime)
            ->count();

        $checkCoupon = $this->CheckCouponExpired($data['user_id']);//檢查有沒有要過期的優惠券,并提醒
        $checkOrderTime =$this->CheckOrderTimed($data['user_id']);//檢查有沒有要到時間的預約訂單,并提醒
        if ($expiredNum > 0){
            // 获取需要取消的订单号
            $expiredOrders = $this->where('order.user_id', $data['user_id'])
                ->where('payment_status', '<', 2)
                ->where('order_status', '=', 1)
                ->where('created_at', '<=', $expiredTime)
                ->select('order_no')
                ->get();

            //提取所有订单号并拼接
            $orderNos = $expiredOrders->pluck('order_no')->toArray();
            $orderNoStr = implode(',', $orderNos);
            //更新订单状态
            $this->where('order.user_id', $data['user_id'])
                ->where('payment_status', '<', 2)
                ->where('order_status', '=', 1)
                ->where('created_at', '<=', $expiredTime)
                ->update([
                    'payment_status' => 4,
                    'order_remark' => "訂單長時間未支付已自動取消,如還有需要請重新購買,謝謝!"
                    ]);
            //系統消息
            $Message = '溫馨提示，您的訂單號為：' . $orderNoStr . ' 的訂單已過期，請重新下單!';
            $en_Message = 'Kind reminder, Your order number(s): ' . $orderNoStr . ' has expired. Please place a new order!';
            //推送内容
            $Sends = '溫馨提示，您有'.$expiredNum.'個訂單訂單已過期，請重新下單!';
            $en_Sends = 'Kind reminder, you have '.$expiredNum.' orders that have expired. Please place a new order!';
            //推送標題
            $titles = '訂單過期提醒';
            $en_title = 'Order expiration reminder';
            $currentLang = app('global_language');
            //加入系統消息
            $SystemInfo = new SystemInfo();
            $code = $SystemInfo->addSystemInfo($data['user_id'], 3, $Message, $en_Message);
            // //推送
            // $SendMessage = $currentLang == "en" ? $en_Sends : $Sends;
            // $SendTitle = $currentLang == "en" ? $en_title : $titles;
            // $GeTui = new GeTuiController();
            // @$PushInfo = $GeTui->pushToSingleByCid($data['client_id'], $SendTitle, $SendMessage, 1);
            if($code && $checkCoupon && $checkOrderTime) {
                return $this->success( __('api.list_success'),$expiredNum);
            }
        }else if ($checkCoupon = false || $checkOrderTime = false) {
                return $this->success( __('api.list_success'));
        }else{
            return $this->success( __('api.list_null'),0);
        }

    }

    public function CheckCouponExpired($user_id){
        $now = Carbon::now();
        $expiredTime = Carbon::now()->addDay(1);
        // 获取即将过期的优惠券
        $expireduserscoupon = DB::table('user_exchange')
            ->join('coupon', 'user_exchange.coupon_id', '=', 'coupon.coupon_id')
            ->where('user_exchange.exchange_status', '=', 1)//未使用優惠券
            ->where('user_exchange.reminder_status', '=', 1)//未提醒過的優惠券 reminder_status 1未提醒/2已提醒
            ->where('user_exchange.user_id', '=', $user_id)
            ->where('coupon.coupon_status', '=', 1)
            ->where('coupon.expiration_time', '>', $now)//未過期的優惠券
            ->where('coupon.expiration_time', '<=', $expiredTime)//還有一天過期的優惠券
            ->select('user_exchange.exchange_id', 'coupon.coupon_name', 'coupon.en_coupon_name')
            ->get();
            if ($expireduserscoupon->isNotEmpty()) {
                // 提取所有优惠券名称
                $couponNames = $expireduserscoupon->pluck('coupon_name')->toArray();
                $enCouponNames = $expireduserscoupon->pluck('en_coupon_name')->toArray();
                if (count($couponNames) == 1) {
                    $Message = '您的' . $couponNames[0] . '還有一天過期，請盡快使用！';
                } else {
                    $Message = '您的' . implode('、', $couponNames) . '等優惠券還有一天過期，請盡快使用！';
                }
                if (count($enCouponNames) == 1) {
                    $en_Message = 'Your ' . $enCouponNames[0] . ' will expire in one day, please use it as soon as possible!';
                } else {
                    $en_Message = 'Your ' . implode(', ', $enCouponNames) . ' will expire in one day, please use it as soon as possible!';
                }
            
                $titles = '優惠券提醒';
                $en_title = 'Coupon reminder';
            
                //加入系統消息
                $SystemInfo = new SystemInfo();
                $code=$SystemInfo->addSystemInfo($user_id, 3, $Message, $en_Message);
                if($code){
                    $ids = $expireduserscoupon->pluck('exchange_id')->toArray();
                    // 更新reminder_status为 2(已提醒)
                    $updateResult = DB::table('user_exchange')
                        ->whereIn('exchange_id', $ids)
                        ->update(['reminder_status' => 2]);
                    return true;
                }else{
                    return false;
                }
            }else{
                return false;
            }
    }
    public function CheckOrderTimed($user_id){
        $now = Carbon::now();
        $expiredTime = Carbon::now()->addDay(1);
        // 先获取已通知过的订单ID
        $notifiedOrderIds = DB::table('system_info')
            ->where('user_id', $user_id)
            ->where('system_type', 3)  // 预约类型通知
            ->whereNotNull('order_id')
            ->where('order_id', '!=', '')  // 排除空字符串
            ->pluck('order_id')
            ->toArray();
    
        // 获取即将到期的预约订单，排除已通知的
        $expiredOrders = DB::table('order')
            ->where('user_id', $user_id)
            ->where('order_status', '=', 1)
            ->where('indent_type', '=', 2)  // 预约类型订单
            ->where('order_time', '>', $now)  // 未到期预约
            ->where('order_time', '<=', $expiredTime)  // 还有一天的预约单
            ->when(!empty($notifiedOrderIds), function($query) use ($notifiedOrderIds) {
                return $query->whereNotIn('order_id', $notifiedOrderIds);
            })
            ->select('order_no','order_id')
            ->get();
       if ($expiredOrders->isNotEmpty()) {
            $orderNos = $expiredOrders->pluck('order_no')->toArray();
        
            // 构建消息内容
            if (count($orderNos) == 1) {
                $Message = '您的預約訂單 ' . $orderNos[0] . ' 還有一天到期，請注意安排時間！';
                $en_Message = 'Your reservation order '. $orderNos[0] .' has one more day to expire, please pay attention to scheduling your time!';
            } else {
                $Message = '您的多個預約訂單' . implode('、', $orderNos) . '還有大約一天到期，請注意安排時間！';
                $en_Message = 'Your multiple appointment orders '. implode('、', $orderNos) .' will expire in approximately one day, please arrange your schedule accordingly!';
            }

            $SystemInfo = new SystemInfo();
            $allSuccess = true;
        
            // 为每个订单单独记录
            foreach ($expiredOrders as $order) {
                $code = $SystemInfo->addSystemInfo($user_id, 3, $Message, $en_Message, $order->order_id);  // 单个订单
                if (!$code) {
                    $allSuccess = false;
                }
            }
        
            return $allSuccess;
        }
    return false;
}


    /**
     * 确认收貨
     * @param $orderID
     * @return array
     */
    public function ConfirmReceipt($data){
        $effects = $this->where('order_id',$data['order_id'])->where('user_id',$data['user_id'])->update(['payment_status'=>5]);
        $languagenow = $this->language == "en_" ? "en" : "cn";
        if($effects){
            $info = $this->where('order_id',$data['order_id'])->select('order_no','delivery_mail')->first();
            $cn_content = '訂單：'.$info['order_no'].',已經确认收貨！';
            $en_content = 'Order:'.$info['order_no'].' , receipt has been confirmed!';
            if($languagenow == "en" ){
                $EmailInfo['Message']=$en_content;
                @self::pushOrderMessage($data['order_id'],$cn_content,$en_content,$languagenow);
            }else{
                $EmailInfo['Message']=$cn_content;
                @self::pushOrderMessage($data['order_id'],$cn_content,$en_content,$languagenow);
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

    /**
     * 掃碼完成預約
     * @param $orderID
     * @param $token
     * @return array
     */
    public function scanConfirmOrderWeb($data,$token){
        $effects = $this->where('order_no',$data['order_no'])->update(['payment_status'=>5]);
        $admin = Admins::where('token',$token)->select('username','admin_id')->first();
        $languagenow = $this->language == "en_" ? "en" : "cn";
        if($effects){
            $info = $this->where('order_no',$data['order_no'])->select('order_id','user_id','order_no','delivery_mail')->first();
            $cn_content = '訂單：'.$info['order_no'].',已通過掃碼完成預約！';
            $en_content = 'Completed reservation order:'.$info['order_no'].' , by scanning the code!';
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
            $configInfo = [//增加訂單操作日誌
                'admin_name' => $admin['username'],
                'admin_id' => $admin['admin_id'],
                'order_id' => $info['order_id'],
                'activity_type' => 7,//確認訂單支付
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


    /**
     * 刪除訂單
     * @param $orderID
     * @return array
     */
    public function deleteOrderApi($data){
        DB::beginTransaction();
        $updateDetails = OrderDetails::where('order_id',$data['order_id'])->where('user_id',$data['user_id'])->update(['details_status' => -1]);
        $updateOrder = $this->where('order_id',$data['order_id'])->where('user_id',$data['user_id'])->update(['order_status'=>-1]);
        if($updateDetails && $updateOrder){
            $info = $this->where('order_id',$data['order_id'])->select('order_no')->first();
            $content = '訂單：'.$info['order_no'].', 已經被刪除!!';
            $en_content = 'Order:'.$info['order_no'].' , has been deleted !!';
            $this->OrderMessage($data['user_id'] ,$content , $en_content );
            DB::commit();
            return $this->success(__('api.delete_success'));
        }else{
            DB::rollBack();
            return $this->error(__('api.delete_fail'));
        }
    }




/**************************************************common(api和admin共用)*****************************************************************/
    /**
     * @param $data
     * @return array
     * 添加系統消息
     */
    public function OrderMessage($user_id,$content,$en_content){
        DB::beginTransaction();
        try {
            $info['user_id'] = $user_id;
            $info['system_type'] = 3;//消息類型  1,服務 2,產品  3,系統 4,其他
            $info['system_content'] = $content;
            $info['en_system_content'] = $en_content;
            $info['is_read'] = 1;
            $info['created_at'] = date('Y-m-d H:i:s');
            $info['updated_at'] = date('Y-m-d H:i:s');
            $effects = $this->insert($info);
            if(!empty($effects)){
                // $this->LogInfo('多语言日志' , $results , 'lang' , true);//开启日志记录
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


/**********************************************************支付相关调用**********************************************************************/

    /**
     * @return string
     * 生成訂單號
     */
    public function getOrderNumber(){
        list($msec, $sec) = explode(' ', microtime());
        $msectime = (float)sprintf('%.0f', (floatval($msec) + floatval($sec)) * 1000);
        return $msectime;
    }


    /**
     * @param $token
     * @param $data
     * @return mixed
     * 生成訂單信息(api)支付
     */
    public function generateOrder($data){
        DB::beginTransaction();
        try {
            //生成订单号
            $orders['order_no'] = self::getOrderNumber();
             //生成订单二维码
            $order_no=(string)$orders['order_no'];
            $orders['order_code'] = $this->getQRCode($order_no);
            //用户id
            $orders['user_id'] = $data['user_id'];
            //優惠券id
            $orders['coupon_id'] = $data['coupon_id'];
            //產品原價
            $orders['original_cost'] = $data['original_cost'];
            //產品支付金额
            $orders['order_money']= empty($data['order_money']) ? 0 : $data['order_money'];
            //產品運費
            $orders['freight_price']= empty($data['freight_price']) ? 0 : $data['freight_price'];
            //预约到店金额
            $orders['booking_price']= empty($data['booking_price']) ? 0 : $data['booking_price'];
            //優惠金额
            $orders['coupon_money'] = empty($data['coupon_money']) ? 0 : $data['coupon_money'];
            // 支付类型：1：立即支付，2：預約到店查看
            $orders['payment_type']=empty($data['payment_type']) ? 1 : $data['payment_type'];
            // 支付方式: 4=餘額(user_balance)、5=鑽石(user_coin)
            // 1=PayPal、2=Stripe、3=Wonder 已停用，禁止落庫
            $orders['payment_mode']=empty($data['payment_mode'] )? 4 : (int)$data['payment_mode'];
            // 訂單類型：1：線上訂單，2：線下訂單
            $orders['order_type']=empty($data['order_type']) ? 1 : $data['order_type'];
            // 訂單類型：1：產品訂單，2：預約訂單
            $orders['indent_type']=empty($data['indent_type']) ? 1 : $data['indent_type'];
            // 取货方式：1：商家发货，2：自取
            $orders['receiving_type']=empty($data['receiving_type']) ? 1 : $data['receiving_type'];
            // 收货地址-区域
            $orders['delivery_area']=empty($data['delivery_area']) ? '': $data['delivery_area'];
            // 收货地址-地址
            $orders['delivery_address']=empty($data['delivery_address']) ? '': $data['delivery_address'];
            // 收货地址-姓名
            $orders['delivery_name']=empty($data['delivery_name']) ? '': $data['delivery_name'];
            // 收货地址-电话
            $orders['delivery_phone']=empty($data['delivery_phone']) ? '': $data['delivery_phone'];
            // 收货地址-邮件
            $orders['delivery_mail']=empty($data['delivery_mail']) ? '': $data['delivery_mail'];
            // 訂單预约时间
            $orders['order_time']=empty($data['order_time']) ? '': $data['order_time'];
            // 訂單预约人数
            $orders['order_num']=empty($data['order_num']) ? '': $data['order_num'];
            // 訂單备注
            $orders['order_remark']=empty($data['order_remark']) ? '': $data['order_remark'];
            // 支付状态：1：待支付2：已支付3：已收货4：已评论 5：已取消
            $orders['payment_status'] = 1;
            // 退货状态：0：不可退款1：可退款2：退款中3：已退款
            $orders['return_status'] = 0;
            // 推送状态：1：未推送 2：已推送
            $orders['push_state'] = 1;
            // 郵件發送狀態 ： 1：未發送  2：已發送
            $orders['mail_state'] = 1;
            // 状态(1可用，2禁用,-1删除)
            $orders['order_status'] = 1;
            // 创建时间
            $orders['created_at'] = date('Y-m-d H:i:s');
            // 更新时间
            $orders['updated_at'] = date('Y-m-d H:i:s');
            //添加订单
            $OrderId = $this->insertGetId($orders);
            if(!empty($OrderId)){
                // $this->LogInfo('添加收貨地址' , $effects , 'addUserAddress' , true);//开启日志记录
                DB::commit();   return $OrderId;
            }else{
                DB::rollBack();   return false;
            }
        }catch (\Exception $e) {
            DB::rollBack();   return  $e->getMessage();
        }
    }



    /**
     * @param $token
     * @param $data
     * @return string
     * 添加訂單產品詳情(api)支付
     */
    public function setDetailsOrder($data){
        DB::beginTransaction();
        $ProductInfo=$data['ProductInfo'];
        try {
            // 先批量获取所有产品的英文名称，避免在循环中多次查询
            $productIds = array_column($ProductInfo, 'product_id');
            $products = Product::whereIn('product_id', $productIds)
                ->select('product_id', 'en_product_name')
                ->get()
                ->keyBy('product_id');
            foreach ($ProductInfo as $key => $value) {
                //查詢規格
                $product_specs=[];
                if(!empty($ProductInfo[$key]['product_relation_id'])){
                    $product_specs_value_id=ProductRelation::where('product_relation_id',$ProductInfo[$key]['product_relation_id'])->first()['product_specs_value_id'];
                    $product_specs = ProductSpecsValue::whereIn('product_specs_value_id',explode('-',$product_specs_value_id))->get()->toArray();
                    // $this->LogInfo('產品規格' , $product_specs , 'product_specs' , true);//开启日志记录
                }
                //發貨方式  receiving_type  自取沒有物流
               $logistics_status= $data['receiving_type']==2 ? 1 : 0;
                $info = [
                    'order_id'=>$data['order_id'],
                    'user_id' =>$data['user_id'],
                    'product_id' => $ProductInfo[$key]['product_id'],
                    'product_relation_id' => $ProductInfo[$key]['product_relation_id'],
                    'product_specs' =>json_encode($product_specs),
                    'product_name' => $ProductInfo[$key]['product_name'],
                    'en_product_name' => $products[$ProductInfo[$key]['product_id']]->en_product_name ?? $ProductInfo[$key]['product_name'], // 添加英文产品名 數據庫表需要新增
                    'product_img' => $ProductInfo[$key]['product_img'],
                    'product_number' => $ProductInfo[$key]['product_number'],
                    'product_price' => $ProductInfo[$key]['product_price'],
                    'logistics_status' => $logistics_status,
                    'product_money' => $ProductInfo[$key]['product_number']*$ProductInfo[$key]['product_price'],
                    'product_type' => $ProductInfo[$key]['product_type'],
                    'details_remark' => $ProductInfo[$key]['details_remark'],
                    'details_status' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                $details[$key] = $this->from('order_details')->insertGetId($info);
                //添加訂單詳情快照-begin
                $Snapshot['details_id']=$details[$key];
                $Coupon=new Coupon;
                $couponinfo=$this->where('order_id',$data['order_id'])->select('coupon_id')->first()->toArray();
                if(!empty($couponinfo['coupon_id'])){
                    $Snapshot['coupon_snapshot']=$Coupon->CouponSnapshot($couponinfo['coupon_id']);
                }else{
                    $Snapshot['coupon_snapshot']='';
                }
                $Product=new Product;
                $Snapshot['product_snapshot']=$Product->ProductSnapshot($ProductInfo[$key]['product_id']);
                $User=new User;
                $Snapshot['user_snapshot']=$User->UserSnapshot($data['user_id']);
                $OrderSnapshot=new OrderSnapshot;
                $SnapshotInfo=$OrderSnapshot->addOrderSnapshot($Snapshot);
                //添加訂單詳情快照-end
                //添加訂單詳情物流-begin
                $OrderLogistics=new OrderLogistics;
                $SnapshotInfo=$OrderLogistics->addOrderLogistics($data['user_id'],$data['order_id']);
                //添加訂單詳情物流-end
            }
            if(!empty($SnapshotInfo)){
                // $this->LogInfo('添加收貨地址' , $effects , 'addUserAddress' , true);//开启日志记录
                DB::commit();
                return implode(',',$details);
            }else{
                DB::rollBack();
                return false;
            }
        }catch (\Exception $e) {
            DB::rollBack();
//            return  $e->getMessage();
            return false;
        }
    }















/**************************************************END***********************************************************/

}
