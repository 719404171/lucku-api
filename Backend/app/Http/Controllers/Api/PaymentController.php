<?php
/*
 * File: PaymentController.php
 * Project: NewDemo
 * Created Time: 2024-07-05 03:21:39 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-09-10 3:29:43 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */

namespace App\Http\Controllers\Api;
use App\Models\ShopSetting;
use App\Models\User;
use App\Models\Order;
use App\Models\Coupon;
use App\Models\Product;
use App\Models\UserCart;
use App\Models\SystemInfo;
use App\Models\OrderDetails;
use App\Models\ProductRelation;
use App\Models\UserWalletRecord;
use PayPal\Api\Item;
use PayPal\Api\Sale;
use PayPal\Api\Payer;
use PayPal\Api\Refund;
use PayPal\Api\Amount;
use PayPal\Api\Payment;
use PayPal\Api\Details;
use PayPal\Api\ItemList;
// use PayPal\Api\Discount;
use PayPal\Rest\ApiContext;
use PayPal\Api\Transaction;
use PayPal\Api\RedirectUrls;
use PayPal\Api\ExecutePayment;
use PayPal\Api\ShippingAddress;
use PayPal\Api\PaymentExecution;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Exception\PayPalConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Log;
//Stripe
use Stripe\Customer;
use Stripe\Checkout\Session;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Checkout;
use App\Http\Controllers\Controller;
use App\Http\Controllers\Api\MailController;
use App\Http\Controllers\Common\GeTuiController;


class PaymentController extends Controller{

    private $clientId;
    private $clientSecret;
    private $apiContext;
    private $UserCart;
    private $Coupon;
    private $Order;
    private $User;


    public function __construct(){
        $this->UserCart = new UserCart();
        $this->Coupon = new Coupon();
        $this->Order = new Order();
        $this->User = new User();
        // paypal支付回調-成功鏈接
        $this->paypal_callback_success = config('services.basics.base_uri').'/api/PaypalCallbackSuccess';
        // paypal支付回調-失敗鏈接
        $this->paypal_callback_error = config('services.basics.base_uri').'/api/PaypalCallbackError';
        // Stripe支付
        $this->stripe_payment= config('services.basics.base_uri').'/api/StripePayment';
        // Stripe支付回調-成功鏈接
        $this->stripe_callback_success = config('services.basics.base_uri').'/api/StripeCallbackSuccess';
        // Stripe支付回調-失敗鏈接
        $this->stripe_callback_error = config('services.basics.base_uri').'/api/StripeCallbackError';
        // Wonder支付
        $this->wonder_payment= config('services.basics.base_uri').'/api/WonderPayment';
        // Wonder支付回調-成功鏈接
        $this->wonder_callback_success = config('services.basics.base_uri').'/api/WonderCallbackSuccess';
        // Wonder支付回調-失敗鏈接
        $this->wonder_callback_error = config('services.basics.base_uri').'/api/WonderCallbackError';
        // 支付成功鏈接
        $this->success_uri = config('services.basics.base_uri').'/api/success';
        // 支付失敗鏈接
        $this->error_uri = config('services.basics.base_uri').'/api/error';
        //設置支付成功返回語言
        $this->language = app('global_language')  == "en" ? "en" : "zh";

        // paypal支付設置
        if (app('global_paypal_env') == 1) {
            $this->mode = 'live';
        }else{
            $this->mode = 'sandbox';
        }
        $this->clientId = app('global_client_id');
        $this->clientSecret = app('global_paypal_secret');
        $this->apiContext = new ApiContext(
            new OAuthTokenCredential(
                $this->clientId,
                $this->clientSecret
            )
        );

        $this->apiContext->setConfig(array(
                'mode' => $this->mode,//支付環境
                'log.LogEnabled' => true,
                'log.FileName' => '../storage/logs/PayPal.log',
                'log.LogLevel' => 'FINE',
                'cache.enabled' => false
            ));
    }



/**************************************************產品支付*************************************************************************/

    /**
     * @param Request $request
     * @return array
     * 產品支付
     */
    /**
     * APP 端下單入口
     * 安全設計：
     *   1. user_id 強制從 token 取，不信任客戶端
     *   2. payment_mode 僅允許 4(餘額)/5(鑽石)，1/2/3 已停用
     *   3. 產品價格服務端重查 DB，不信任客戶端傳的 product_price / order_money
     *   4. 訂單金額必須 > 0
     */
    public function PurchaseProduct(Request $request){
        // 接收參數
        $info = $request->post();
        // 強制從 token 取 user_id
        $userId = (int)(auth('api')->user()->user_id ?? 0);
        if ($userId <= 0) {
            return $this->error('未登錄');
        }
        $info['user_id'] = $userId;

        // ============ P0 參數校驗 ============
        if (empty($info['product']) || !is_array($info['product']) || count($info['product']) === 0) {
            return $this->error('未選擇任何商品');
        }
        $paymentMode = (int)($info['payment_mode'] ?? 0);
        if (!in_array($paymentMode, [4, 5], true)) {
            // 1=PayPal 2=Stripe 3=Wonder 已停用
            return $this->error('當前僅支援餘額/鑽石支付');
        }
        $indentType = (int)($info['indent_type'] ?? 1);
        $paymentType = (int)($info['payment_type'] ?? 1);

        // ============ P0 產品價格：服務端重查 DB，不信任客戶端 ============
        $productIds = array_unique(array_column($info['product'], 'product_id'));
        if (count($productIds) !== count($info['product'])) {
            return $this->error('存在重複商品');
        }
        $dbProducts = Product::whereIn('product_id', $productIds)
            ->where('product_status', 1) // 僅取上架商品
            ->select('product_id', 'product_price', 'product_stock', 'product_name', 'en_product_name', 'product_img', 'product_type')
            ->get()
            ->keyBy('product_id');

        // 一次性查出所有涉及的 ProductRelation（SKU）
        $relationIds = [];
        foreach ($info['product'] as $item) {
            if (!empty($item['product_relation_id'])) {
                $relationIds[] = (int)$item['product_relation_id'];
            }
        }
        $dbRelations = collect();
        if (!empty($relationIds)) {
            $dbRelations = ProductRelation::whereIn('product_relation_id', $relationIds)
                ->select('product_relation_id', 'product_id', 'price', 'stock')
                ->get()
                ->keyBy('product_relation_id');
        }

        $serverTotal = 0;   // 服務端重算的訂單金額（不含運費/優惠）
        $preDiscountTotal = 0; // 用於 original_cost（折前原價）
        foreach ($info['product'] as $key => $clientItem) {
            $pid = (int)($clientItem['product_id'] ?? 0);
            $rid = (int)($clientItem['product_relation_id'] ?? 0);
            $num = (int)($clientItem['product_number'] ?? 0);
            if ($pid <= 0 || $num <= 0) {
                return $this->error('商品參數異常');
            }
            if (!$dbProducts->has($pid)) {
                return $this->error('商品不存在或已下架');
            }
            $dbItem = $dbProducts->get($pid);

            // 價格與庫存優先取 SKU（ProductRelation），沒 SKU 則取 SPU（Product）
            if ($rid > 0 && $dbRelations->has($rid)) {
                $sku = $dbRelations->get($rid);
                $price = (float)($sku->price ?? $dbItem->product_price);
                $stock = (int)($sku->stock ?? $dbItem->product_stock);
            } else {
                $price = (float)$dbItem->product_price;
                $stock = (int)$dbItem->product_stock;
            }

            // 庫存檢查（僅 indent_type=1 產品訂單）
            if ($indentType == 1 && $stock < $num) {
                return $this->error(__('api.Stock_fail'));
            }
            // 訂單金額累計
            $serverTotal   += $price * $num;
            $preDiscountTotal += $price * $num;
            // 用服務端數據覆蓋客戶端傳的（防止篡改）
            $info['product'][$key]['product_price']  = $price;
            $info['product'][$key]['product_name']   = $dbItem->product_name;
            $info['product'][$key]['product_img']    = $dbItem->product_img;
            $info['product'][$key]['product_type']   = $dbItem->product_type ?? 1;
        }

        // 訂單金額下限保護
        if ($serverTotal <= 0) {
            return $this->error('訂單金額必須大於 0');
        }

        // ============ 運費 ============
        // 立即支付 && 產品 && 商家發貨 才有運費，其他為 0
        if ($paymentType == 1 && $indentType == 1 && (int)($info['receiving_type'] ?? 1) == 1) {
            $info['freight_price'] = app('global_freight_price');
        } else {
            $info['freight_price'] = 0;
        }

        // ============ P0 恢復 payment_type==2 預約到店定價邏輯 ============
        // 預約到店（payment_type=2）：使用 global_tostore_price 或產品原價，不計運費
        if ($paymentType == 2) {
            if (app('global_is_tostore') == 1) {
                $info['booking_price'] = empty(app('global_tostore_price')) ? 0.01 : app('global_tostore_price');
                $serverTotal = (float)$info['booking_price'];
                $preDiscountTotal = (float)$info['booking_price'];
            } else {
                $info['booking_price'] = $preDiscountTotal;
            }
            $info['freight_price'] = 0;
        } else {
            $info['booking_price'] = 0;
        }

        // ============ 預約上限 ============
        $ShopBookingLimit = ShopSetting::where('shop_status',1)->select('booking_limit','booking_limit_num')->first();
        if($indentType == 2 && $ShopBookingLimit && (int)$ShopBookingLimit['booking_limit']==1){
            $orderDate = date('Y-m-d', strtotime($info['order_time'] ?? ''));
            if ($orderDate) {
                $todayBookingCount = Order::where('indent_type', 2)
                    ->where('order_time', 'like', $orderDate . '%')
                    ->count();
                if ($todayBookingCount >= (int)$ShopBookingLimit['booking_limit_num']) {
                    return $this->error(__('api.booking_limited'));
                }
            }
        }

        // ============ 優惠券 ============
        $info['coupon_money'] = 0;
        if(!empty($info['coupon_id'])){
            $Check = [
                'user_id'       => $userId,
                'coupon_id'     => $info['coupon_id'],
                'original_cost' => $serverTotal,
            ];
            $ResultCheck = $this->Coupon->OrderCheckCoupon($Check);
            if(empty($ResultCheck)){
                return $this->error(__('api.coupon_fail'));
            }
            $discount = $this->discountPrice($Check);
            $serverTotal = (float)$discount['order_money'];
            $info['coupon_money'] = (float)$discount['coupon_money'];
        }

        // 最終應付金額：original_cost 是折前原價+運費，order_money 是折後實付+運費
        $info['original_cost'] = $preDiscountTotal + (float)$info['freight_price'];
        $finalOrderMoney = $serverTotal + (float)$info['freight_price'];
        $info['order_money'] = $finalOrderMoney;
        if ($finalOrderMoney <= 0) {
            return $this->error('應付金額必須大於 0');
        }

        // ============ 購物車清理 ============
        if(!empty($info['cart_id'])){
            $this->UserCart->DeleteShoppingCart($info['cart_id']);
        }

        // ============ 生成訂單 ============
        $OrderId = $this->Order->generateOrder($info);
        if (empty($OrderId)) {
            return $this->parametersError(__('api.order_error'));
        }

        // ============ 寫訂單詳情 ============
        $product = [
            'ProductInfo'      => $info['product'],
            'coupon_id'        => $info['coupon_id'] ?? null,
            'user_id'          => $userId,
            'receiving_type'   => $info['receiving_type'] ?? 1,
            'order_id'         => $OrderId,
        ];
        $OrderDetails = $this->Order->setDetailsOrder($product);
        if (empty($OrderDetails)){
            return $this->parametersError(__('api.details_error'));
        }

        // ============ 支付 ============
        $payment['order_id']  = $OrderId;
        $payment['coupon_id'] = $info['coupon_id'] ?? null;
        if ($paymentMode == 4) {
            return $this->BalancePayment($payment);
        } elseif ($paymentMode == 5) {
            return $this->DiamondPayment($payment);
        }
        return $this->error('支付方式不可用');
    }


/**************************************************商品折扣優惠券計算*******************************************************************************/
    /**
     * @param Request $request
     * @return array
     * 商品折扣優惠券計算
     * $data['coupon_id']
     * $data['original_cost']
     */
    public function discountPrice($data){
        $coupon = Coupon::where('coupon_id',$data['coupon_id'])->select('coupon_type','discount')->first();
            switch ($coupon['coupon_type']) {
                case '1':
                    $coupon_money =  $coupon['discount'];
                    break;
                case '2':
                    $coupon_money =  $coupon['discount'];
                    break;
                case '3':
                    $coupon_money = $data['original_cost'] *( 1 - $coupon['discount'] );
                    break;
                case '4':
                    $coupon_money = $data['original_cost'] * ( 1 - $coupon['discount'] );
                    break;
            }
        if($coupon_money>=$data['original_cost']){
            $result['coupon_money']= $data['original_cost']-0.01;
        }else{
            $result['coupon_money']= $coupon_money;
        }
//        //消耗一张优惠券
//        DB::table('user_exchange')
//            ->where('coupon_id',$data('coupon_id'))
//            ->where('user_id',$data('user_id'))
//            ->where('exchange_status',1)
//            ->limit(1)
//            ->update(['exchange_status'=>2]);
        $result['original_cost']= $data['original_cost'];
        $result['order_money']=$data['original_cost'] - $result['coupon_money'] ;
            return $result;

    }



/**************************************************订单去支付*************************************************************************/

    /**
     * @param Request $request
     * @return array
     * 订单去支付
     * $info['order_id']
     * $info['payment_mode']
     */
    public function PaymentOrder(Request $request){
        //接收参数
        $info=$request->post();
        $userId = (int)(auth('api')->user()->user_id ?? 0);
        // 先查訂單並校驗歸屬（防止越權支付他人訂單）
        $orderinfo = Order::where('order_id', $info['order_id'])
            ->select('payment_status','order_money','coupon_id','user_id','order_id','original_cost')
            ->first();
        if (empty($orderinfo)) {
            return $this->error(__('api.order_error'));
        }
        $orderinfo = $orderinfo->toArray();
        if ((int)$orderinfo['user_id'] !== $userId) {
            return $this->error('無權操作此訂單');
        }

        if(!empty($orderinfo['coupon_id'])) {
            //檢測優惠券是否可用
            $Check['user_id'] = $orderinfo['user_id'];
            $Check['coupon_id'] = $orderinfo['coupon_id'];
            $Check['original_cost'] = $orderinfo['original_cost'];
            $ResultCheck = $this->Coupon->OrderCheckCoupon($Check);
            if (empty($ResultCheck)) {
                // 如果优惠券失效，清除订单优惠券
                Order::where('order_id', $info['order_id'])
                    ->update(['coupon_id' => null]);
                // 根据语言模式返回指定的错误信息的和格式
                $errorMsg = ($this->language == "en")
                    ? 'Coupon has expired or been used, and has been removed from the order'
                    : '优惠券已失效或已使用，已从订单中移除';

                return $this->error($errorMsg);//避免嵌套
            } else {
                $discount = $this->discountPrice($Check);
                $info['original_cost'] = $discount['original_cost'];
                $info['coupon_money'] = $discount['coupon_money'];
                $info['order_money'] = $discount['order_money'];
            }
        }
        if( (int)$orderinfo['payment_status'] > 2 ){
            return $this->error(__('api.payment_error'));
        }
        // 關閉 1=PayPal 2=Stripe 3=Wonder（產品購買僅支援餘額/鑽石）
        if(in_array((int)$info['payment_mode'], [1, 2, 3], true)){
            return $this->error('當前僅支援餘額/鑽石支付');
        }
         //更新訂單支付方式
         $PaymentMode=Order::where('order_id',$info['order_id'])->update(['payment_mode'=> (int)$info['payment_mode'],'updated_at'=>date('Y-m-d H:i:s')]);
        //订单id
        $payment['order_id']=$info['order_id'];
        if((int)$info['payment_mode']==4) {
            // 支付方式: 4：金幣支付（user_balance）
            return $this->BalancePayment($payment);
        }elseif ((int)$info['payment_mode']==5) {
            // 支付方式: 5：鑽石支付（user_coin）
            return $this->DiamondPayment($payment);
        }
        return $this->error('支付方式不可用');
    }


/**************************************************支付方式-金幣支付*************************************************************************/
    /**
     * @param $data
     * @return array
     * 支付方式--金幣支付（user_balance）
     * data['order_id']
     */
    public function BalancePayment($data){
        // 獲取訂單信息
        $orderinfo = Order::where('order_id',$data['order_id'])->select('order_id','user_id','order_money','coupon_id','payment_mode','indent_type')->first();
        if (empty($orderinfo)) {
            return $this->error(__('api.order_error'));
        }

        $userinfo = User::where('user_id', $orderinfo->user_id)->select('user_balance')->first()->toArray();
        if( ($userinfo['user_balance'] - $orderinfo['order_money']) >= 0 ){
            DB::beginTransaction();
            try {
                // F-14 冪等：先鎖訂單，已支付則直接返回
                $paidOrder = Order::where('order_id', $orderinfo->order_id)
                    ->lockForUpdate()
                    ->select('payment_status')
                    ->first();
                if (!empty($paidOrder) && (int)$paidOrder->payment_status === 3) {
                    DB::commit();
                    $paidResults['orderId'] = $orderinfo->order_id;
                    $paidResults['Url'] = $this->success_uri;
                    return $this->success(__('api.balance_success'), $paidResults);
                }
                // 事務內更新 payment_mode=4（餘額支付），與後續扣款/入帳原子化
                Order::where('order_id', $orderinfo->order_id)->update(['payment_mode' => 4]);

                // F-12 條件式扣款，防止並發超扣
                $affected = User::where('user_id',$orderinfo->user_id)
                    ->where('user_balance','>=',$orderinfo->order_money)
                    ->decrement('user_balance',$orderinfo->order_money);
                if($affected === 0){
                    DB::rollBack();
                    return $this->error(__('api.balance_fail'));
                }
                // paymentComplete：返回 true 成功 / false 失敗 / 'already_completed' 冪等
                $effects=$this->paymentComplete($orderinfo->order_id,'');
                if($effects === true){
                    $this->OrderComplete($orderinfo->order_id);
                    $results['orderId']=$orderinfo->order_id;
                    $results['Url']=$this->success_uri;
                    DB::commit();
                    return $this->success(__('api.balance_success'),$results);
                }
                if($effects === 'already_completed'){
                    DB::commit();
                    $results['orderId']=$orderinfo->order_id;
                    $results['Url']=$this->success_uri;
                    return $this->success(__('api.balance_success'),$results);
                }
                // paymentComplete 失敗 → 退款
                User::where('user_id',$orderinfo->user_id)->increment('user_balance',$orderinfo->order_money);
                DB::rollBack();
                \Log::error('BalancePayment: paymentComplete failed', ['order_id'=>$orderinfo->order_id, 'reason'=>$effects]);
                return $this->error(__('api.balance_error'));
            } catch (\Throwable $e) {
                DB::rollBack();
                \Log::error('BalancePayment exception', ['order_id'=>$orderinfo->order_id, 'error'=>$e->getMessage()]);
                return $this->error(__('api.balance_error'));
            }
        }else{
            return $this->error(__('api.balance_fail'));
        }
    }



/**************************************************支付方式-鑽石支付*************************************************************************/
    /**
     * @param $data
     * @return array
     * 支付方式--鑽石支付（user_coin）
     * data['order_id']
     */
    public function DiamondPayment($data){
        // 獲取訂單信息
        $orderinfo = Order::where('order_id',$data['order_id'])->select('order_id','user_id','order_money','coupon_id','payment_mode','indent_type')->first();
        if (empty($orderinfo)) {
            return $this->error(__('api.order_error'));
        }

        $userinfo = User::where('user_id', $orderinfo->user_id)->select('user_coin')->first()->toArray();
        if( ($userinfo['user_coin'] - $orderinfo->order_money) >= 0 ){
            DB::beginTransaction();
            try {
                // F-14 冪等：先鎖訂單，已支付則直接返回
                $paidOrder = Order::where('order_id', $orderinfo->order_id)
                    ->lockForUpdate()
                    ->select('payment_status')
                    ->first();
                if (!empty($paidOrder) && (int)$paidOrder->payment_status === 3) {
                    DB::commit();
                    $paidResults['orderId'] = $orderinfo->order_id;
                    $paidResults['Url'] = $this->success_uri;
                    return $this->success(__('api.balance_success'), $paidResults);
                }
                // 事務內更新 payment_mode=5（鑽石支付），與後續扣款/入帳原子化
                Order::where('order_id', $orderinfo->order_id)->update(['payment_mode' => 5]);

                // F-12 條件式扣款，防止並發超扣
                $affected = User::where('user_id',$orderinfo->user_id)
                    ->where('user_coin','>=',$orderinfo->order_money)
                    ->decrement('user_coin',$orderinfo->order_money);
                if($affected === 0){
                    DB::rollBack();
                    return $this->error(__('api.balance_fail'));
                }
                // paymentComplete：返回 true 成功 / false 失敗 / 'already_completed' 冪等
                $effects=$this->paymentComplete($orderinfo->order_id,'');
                if($effects === true){
                    $this->OrderComplete($orderinfo->order_id);
                    $results['orderId']=$orderinfo->order_id;
                    $results['Url']=$this->success_uri;
                    DB::commit();
                    return $this->success(__('api.balance_success'),$results);
                }
                if($effects === 'already_completed'){
                    DB::commit();
                    $results['orderId']=$orderinfo->order_id;
                    $results['Url']=$this->success_uri;
                    return $this->success(__('api.balance_success'),$results);
                }
                // paymentComplete 失敗 → 退款
                User::where('user_id',$orderinfo->user_id)->increment('user_coin',$orderinfo->order_money);
                DB::rollBack();
                \Log::error('DiamondPayment: paymentComplete failed', ['order_id'=>$orderinfo->order_id, 'reason'=>$effects]);
                return $this->error(__('api.balance_error'));
            } catch (\Throwable $e) {
                DB::rollBack();
                \Log::error('DiamondPayment exception', ['order_id'=>$orderinfo->order_id, 'error'=>$e->getMessage()]);
                return $this->error(__('api.balance_error'));
            }
        }else{
            return $this->error(__('api.balance_fail'));
        }
    }



/**************************************************支付方式-Paypal支付*************************************************************************/
    /**
     * @param Request $request
     * @return array
     * 支付方式--Paypal支付
     * data['order_id']
     */
    public function  PaypalPayment($data){
        $result=$this->paymentNow($data['order_id']);
        return $result;
    }

    /**
     * @param $item
     * @param $address
     * @param $money
     * @param $orderid
     * @return \Illuminate\Http\RedirectResponse|\Illuminate\Routing\Redirector
     * 調起支付
     */
    public function paymentNow($orderid){
        $orderinfo = Order::where('order_id',$orderid)->select('*')->first()->toArray();
        $detailsinfo = OrderDetails::where('order_id',$orderid)->select('*')->get()->toArray();
        // 创建支付对象
        $payer = new Payer();
        $payer->setPaymentMethod('paypal');
        // 支付类型：1：立即支付，2：預約到店查看
        if($orderinfo['payment_type']==1){
            if($orderinfo['order_money']==0.01){
                // 创建项目列表
                $item = new Item();
                $item->setName($detailsinfo[0]['product_name'])->setCurrency('HKD')->setQuantity(1)->setPrice((int)0.01);
                $itemList = new ItemList();
                $itemList->setItems([$item]);
            }else{
                // 创建多个项目列表
                $items = [];
                foreach ($detailsinfo as $key => $value) {
                    $item[$key]= new Item();
                    $item[$key]->setName($detailsinfo[$key]['product_name'])->setCurrency('HKD')
                                        ->setQuantity((int)$detailsinfo[$key]['product_number'])->setPrice((int)$detailsinfo[$key]['product_price']);
                    $items[] =$item[$key];
                }
                $itemList = new ItemList();
                $itemList->setItems($items);
            }
        }else{
            // 创建项目列表
            $BookMoney=(int)max( 0.01 , $orderinfo['booking_price']);
            $item = new Item();
            $item->setName('Order')->setCurrency('HKD')->setQuantity(1)->setPrice($BookMoney);
            $itemList = new ItemList();
            $itemList->setItems([$item]);
        }

        //设置订单地址
        if($orderinfo['delivery_address'] && $orderinfo['delivery_name'] && $orderinfo['delivery_phone']){
            $payeraddress = new ShippingAddress();
            $payeraddress->setRecipientName($orderinfo['delivery_name'])->setLine1($orderinfo['delivery_address'])
                                                                        ->setPhone($orderinfo['delivery_phone'])->setCountryCode('HK');
            $itemList->setShippingAddress($payeraddress);
        }
        // 创建一个新的折扣对象(不能用，所以把折扣金額變為負數，放稅收裡面)(**稅收不能為負數，所以把放運費裡面)
        // $discount = new Discount();
        // $discount->setCurrency('HKD')->setAmount($orderinfo['coupon_money'])->setDescription('discount'); // 设置折扣的整数值
        //設置運費  支付金額
        $details = new Details();
        $details->setShipping($orderinfo['freight_price']-$orderinfo['coupon_money'])->setTax(0)->setSubtotal($orderinfo['original_cost']);
        //設置總金額  運費+產品支付-折扣金額
        $amount = new Amount();
        $amount->setCurrency("HKD")->setTotal($orderinfo['original_cost']+$orderinfo['freight_price']-$orderinfo['coupon_money'])->setDetails($details);
        // 设置重定向的URL
        $transaction = new Transaction();
        $transaction->setAmount($amount)->setItemList($itemList) ->setDescription("Payment description")->setInvoiceNumber(uniqid());
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl($this->paypal_callback_success."?success=true&orderid=".$orderid)
                     ->setCancelUrl($this->paypal_callback_error."?success=false&orderid=".$orderid);
        // 创建支付
        $payment = new Payment();
        $payment->setIntent("sale")->setPayer($payer)->setRedirectUrls($redirectUrls)->setTransactions(array(array('amount' => $amount, 'item_list' => $itemList)));
        $payment->create($this->apiContext);
        //生成地址
        $approvalUrl = $payment->getApprovalLink();
        $result['orderId']=$orderid;
        $result['url']=$approvalUrl;
        return $result;
    }


    /**
     * @param Request $request
     * @return array
     * paypal支付回調-成功鏈接
     */
    public function PaypalCallbackSuccess(Request $request){
        $orderid = $request->get('orderid');//訂單ID
        $success = $request->get('success');
		$paymentId = $request->get('paymentId');//支付ID
		// $token   = $request->get('token'); //
        $PayerID=$request->get('PayerID');//支付人ID
        $orderinfo = Order::where('order_id',$orderid)->select('*')->first();
        if (empty($orderinfo)) {
            // 訂單不存在
            if($this->language == "en" ){
                return view('Payment/en_Error');
            }else{
                return view('Payment/Error');
            }
        }
        if (!empty($success)) {
            // F-14 冪等：訂單已是「已支付」狀態時直接返回成功，避免回調重複執行/重複入帳
            if ((int)$orderinfo['payment_status'] === 3) {
                if($this->language == "en" ){
                    return view('Payment/en_Success');
                }else{
                    return view('Payment/Success');
                }
            }
			$payment = Payment::get($paymentId, $this->apiContext);
			$execution = new PaymentExecution();
			$execution->setPayerId($PayerID);
            try {
                DB::beginTransaction();
                $result = $payment->execute($execution, $this->apiContext)->toArray();
                $orderRefundId = $result['transactions'][0]['related_resources'][0]['sale']['id'];
                // $this->LogInfo('paypal退款ID:' , $orderRefundId , 'PaypalCallback' , true);//开启日志记录
                //支付完成更改訂單信息
                $effects=$this->paymentComplete($orderid,$orderRefundId);// 支付完成更改訂單信息
                if($effects === true || $effects === 'already_completed'){
                    //支付完成後操作(郵箱 系統消息 ---推送已關閉)；已處理過的回調不重複發送
                    if ($effects !== 'already_completed') {
                        $solution=$this->OrderComplete($orderid);
                    }
                    DB::commit();
                    //支付完成
                    if($this->language == "en" ){
                        return view('Payment/en_Success');
                    }else{
                        return view('Payment/Success');
                    }
                }else{
                    DB::rollBack();
                    // 支付不成功，退款
                    $Refund['refund_id']=$orderRefundId;//退款id
                    $Refund['order_money']=$orderinfo['order_money'];//退款金額
                    $returnInfo = $this->PayPalReturnMoney($Refund);
                    //支付失敗
                    if($this->language == "en" ){
                        return view('Payment/en_Error');
                    }else{
                        return view('Payment/Error');
                    }
                }
            } catch (Exception $e) {
                //支付失敗
                if($this->language == "en" ){
                    return view('Payment/en_Error');
                }else{
                    return view('Payment/Error');
                }
            }
        } else {
            //支付失敗
            if($this->language == "en" ){
                return view('Payment/en_Error');
            }else{
                return view('Payment/Error');
            }
        }
    }



    /**
     * @param $data
     * @return array
     * PayPal退款
     */
    public function PayPalReturnMoney($data){
        try {
            $refund_id = $data['refund_id'];  //异步加调中拿到的id
            $amt = new Amount();
            $amt->setCurrency('HKD')->setTotal($data['order_money']);  // 退款的费用
            $refund = new Refund();
            $refund->setAmount($amt);
            $sale = new Sale();
            $sale->setId($refund_id);
            $refundedSale = $sale->refund($refund, $this->apiContext);
        } catch (\Exception $e) {
            // PayPal无效退款
            return false;

        }
        // 退款完成
        return true;
    }


    // /**
    //  * @param Request $request
    //  * @return array
    //  * paypal支付回調-失敗鏈接
    //  */
    // public function PaypalCallbackError(Request $request){

    // }

/**************************************************支付方式-Stripe支付*************************************************************************/
    /**
     * @param Request $request
     * @return array
     * 支付方式--Stripe支付
     * data['order_money']
     * data['user_id']
     * data['order_id']
     * data['payment_type']
     * 卡号  Visa:4242424242424242  4000000320000021 4000000760000002
     *测试卡号位置：https://docs.stripe.com/testing#international-cards
     */
    public function StripePayment(Request $request){
        $order_id = $request->get('order_id');//訂單ID
        $orderinfo = Order::where('order_id',$order_id)->select('*')->first()->toArray();
        $detailsinfo = OrderDetails::where('order_id',$order_id)->select('*')->get()->toArray();
        $user_id = $orderinfo['user_id'];//用户ID
        //查看有沒有該客戶，沒有就創建
        $UserInfo = $this->User->where('user_id',$user_id)->first();
        //修復stripe_id是空的時候報錯，只能是NULL
        if (empty($UserInfo['stripe_id'])) {
            $this->User->where('user_id',$UserInfo['user_id'])->update(['stripe_id'=>NULL]);
        }
        // 如果你想为给定的计费模型检索 Stripe 客户对象，但不确定该计费模型是否已经是 Stripe 中的客户，
        // 则可以使用 createOrGetStripeCustomer 方法。 如果尚不存在，此方法将在 Stripe 中创建一个新客户：
        $info['name']=$UserInfo['user_name'];
        $info['phone']=$orderinfo['delivery_phone'];
        $info['email']=$orderinfo['delivery_mail'];
        $stripeCustomer = $this->User->where('user_id',$user_id)->first()->createOrGetStripeCustomer($info);
        //創建完成
        // Stripe支付
        //产品金额(單位：分)
        $amount=($orderinfo['original_cost']+$orderinfo['freight_price']-$orderinfo['coupon_money']) * 100;//訂單金額+運費
        //Stripe支付最小金額為4元
        if($amount < 400 ){
            return $this->error(__('api.stripe_lowest'));
        }
        $name=$orderinfo['order_no'];//产品名称---現在顯示訂單號
        $quantity=1;//产品数量
        $sessionOptions=[
            'mode'        => 'payment',
            'locale'      => 'zh-HK', //设置为繁体中文
            'success_url' => $this->stripe_callback_success."?success=true&orderid=".$order_id .'&session_id={CHECKOUT_SESSION_ID}',// 同步回调
            'cancel_url'  => $this->stripe_callback_error."?success=true&orderid=".$order_id,// 异步回调
            'metadata'    => [
                'order_id' => $order_id,
            ]
        ];
        $customerOptions=[
            'address'=>$orderinfo['delivery_address'],
            'email'=>$orderinfo['delivery_mail'],
            'phone'=>$orderinfo['delivery_phone']
        ];
        // $productData=[
        //     'name'=>'泳衣试穿',
        //     'images'  => ['https://brain-storm.oss-cn-hongkong.aliyuncs.com/2024-07-01/1719812892609821.png'],
        //     'description' => '手機簡介'
        // ];
        $UserCheckout=$this->User->where('user_id',$user_id)->first();
        $checkout =$UserCheckout->checkoutCharge($amount, $name, $quantity,$sessionOptions,$customerOptions,$productData=[]);
        return  $checkout;
    }

    /**
     * @param Request $request
     * @return array
     * Stripe支付回調-成功鏈接
     */
    public function StripeCallbackSuccess(Request $request){
        $orderid = $request->get('orderid');//訂單ID
        $success = $request->get('success');
        $session_id = (string)$request->get('session_id');
        // F-14 冪等：訂單已是「已支付」狀態時直接返回成功，避免回調重複入帳
        $paidCheck = Order::where('order_id', $orderid)->select('payment_status')->first();
        if (!empty($paidCheck) && (int)$paidCheck->payment_status === 3) {
            if($this->language == "en" ){
                return view('Payment/en_Success');
            }else{
                return view('Payment/Success');
            }
        }

        // 安全：必須持有有效 session_id，並向 Stripe 查詢確認「已支付」且訂單號一致，才可入帳
        if ($session_id === '') {
            \Log::error('stripe callback rejected: missing session_id', ['order_id' => $orderid]);
            return $this->language == "en" ? view('Payment/en_Error') : view('Payment/Error');
        }
        try {
            $stripe = new \Stripe\StripeClient(config('cashier.secret'));
            $session = $stripe->checkout->sessions->retrieve($session_id, []);
        } catch (\Throwable $e) {
            \Log::error('stripe callback verify failed', ['session_id' => $session_id, 'error' => $e->getMessage()]);
            return $this->language == "en" ? view('Payment/en_Error') : view('Payment/Error');
        }
        $paidStatus = $session->payment_status ?? '';
        $metaOrderId = $session->metadata->order_id ?? null;
        if ($paidStatus !== 'paid' || empty($metaOrderId) || (string)$metaOrderId !== (string)$orderid) {
            \Log::error('stripe callback rejected: not paid or order mismatch', [
                'order_id' => $orderid, 'payment_status' => $paidStatus, 'meta_order_id' => $metaOrderId
            ]);
            return $this->language == "en" ? view('Payment/en_Error') : view('Payment/Error');
        }

        try {
            DB::beginTransaction();
            //支付完成更改訂單信息
            $effects=$this->paymentComplete($orderid,$session_id);// 支付完成更改訂單信息
            if($effects === true || $effects === 'already_completed'){
                //支付完成後操作(郵箱 系統消息 ---推送已關閉)；已處理過的回調不重複發送
                if ($effects !== 'already_completed') {
                    $solution=$this->OrderComplete($orderid);
                }
                DB::commit();
                //支付完成
                if($this->language == "en" ){
                    return view('Payment/en_Success');
                }else{
                    return view('Payment/Success');
                }
            }else{
                DB::rollBack();
                // 支付不成功，退款
                // $returnInfo = $this->Striperefund($RefundId);
                //支付失敗
                if($this->language == "en" ){
                    return view('Payment/en_Error');
                }else{
                    return view('Payment/Error');
                }
            }
        } catch (Exception $e) {
            //支付失敗
            if($this->language == "en" ){
                return view('Payment/en_Error');
            }else{
                return view('Payment/Error');
            }
        }
    }

    /**
     * @param Request $request
     * @return array
     * 退还费用
     */
    public function Striperefund(Request $request) {
        $Striperefund=1;
        return $Striperefund;
    }


    /**
     * @param Request $request
     * @return array
     * Stripe支付回調-成功鏈接
     */
    public function StripeCallbackError(Request $request){
            //支付失敗
            if($this->language == "en" ){
                return view('Payment/en_Error');
            }else{
                return view('Payment/Error');
            }
    }



/**************************************************支付完成訂單修改****************************************************************************/
    /**
     * @param Request $request
     * @return array|false
     * 支付完成訂單修改
     */
    /**
     * 支付完成後的訂單狀態收尾
     * 注意：本方法不管理事務！由調用方（BalancePayment/DiamondPayment/Webhook）統一控制事務邊界，
     *       避免嵌套事務導致 rollBack 不生效。
     * 返回值：
     *   true               → 成功
     *   'already_completed' → 冪等命中，已處理過
     *   false              → 失敗（庫存不足 / 優惠券失敗 / 寫入異常）
     */
    public function paymentComplete($orderId,$refundId=''){
        try {
            // F-14 冪等：行鎖訂單，若已是「已支付(3)」直接返回，防止 Webhook/回調重試重複入帳
            $lockOrder = Order::where('order_id', $orderId)
                ->lockForUpdate()
                ->select('payment_status', 'return_status', 'user_id', 'payment_mode', 'payment_type', 'order_money', 'coupon_id')
                ->first();
            if (empty($lockOrder)) {
                \Log::error('paymentComplete: order not found', ['order_id'=>$orderId]);
                return false;
            }
            if ((int)$lockOrder->payment_status === 3) {
                return 'already_completed';
            }

            //更新訂單信息
            $updateOrder=Order::where('order_id',$orderId)->update(['payment_status'=> 3 , 'return_status'=> 1, 'refund_id'=>$refundId ,  'push_state' => 1,
                                                                    'payment_at'=>date('Y-m-d H:i:s'),  'mail_state' => 1, 'updated_at'=>date('Y-m-d H:i:s')]);
            //查詢訂單詳情信息
            $details = OrderDetails::where('order_id',$orderId)->select('details_id','user_id','product_number','product_id','product_relation_id','product_type')->get()->toArray();
            // 如果存有優惠券, 消耗用戶的一張優惠券
            if (!empty($lockOrder->coupon_id)) {
                $updated = DB::table('user_exchange')
                    ->where('coupon_id', $lockOrder->coupon_id)
                    ->where('user_id', $lockOrder->user_id)
                    ->where('exchange_status', 1) // 未使用的优惠券
                    ->limit(1)
                    ->update([
                        'exchange_status' => 2, // 2=已使用
                        'updated_at' => date('Y-m-d H:i:s'),
                    ]);
                if ($updated === 0) {
                    \Log::error('paymentComplete: coupon not found or already used', ['order_id'=>$orderId, 'coupon_id'=>$lockOrder->coupon_id]);
                    return false;
                }
            }
            // 更新庫存（產品類型 + 立即支付 才扣庫存；預約/服務不扣）
            if(!empty($details)){
                foreach ($details as $key => $value) {
                    $product_id          = $details[$key]['product_id'];
                    $product_type        = $details[$key]['product_type'];   // 1=產品 2=服務
                    $product_number      = $details[$key]['product_number']; // 購買數量
                    $product_relation_id = $details[$key]['product_relation_id'];
                    if($product_type==1 && (int)$lockOrder->payment_type==1){
                        $ProductStock = Product::where('product_id',$product_id)
                            ->where('product_stock','>=',$product_number)
                            ->decrement('product_stock',$product_number);
                        $RelationStock = true;
                        if (!empty($product_relation_id)) {
                            $RelationStock = ProductRelation::where('product_relation_id',$product_relation_id)
                                ->where('stock','>=',$product_number)
                                ->decrement('stock',$product_number);
                        }
                        if ($ProductStock === 0 || $RelationStock === false || $RelationStock === 0) {
                            \Log::error('paymentComplete: stock insufficient', ['order_id'=>$orderId, 'product_id'=>$product_id, 'num'=>$product_number]);
                            return false;
                        }
                    }
                }
            }
            // 添加钱包记录（必須用 Order 的 user_id，不能用循環裡的）
            $WalletInfo = [
                'user_id'         => $lockOrder->user_id,
                'wallet_type'     => 6,
                'payment_mode'    => $lockOrder->payment_mode,
                'wallet_explain'  => '購買商品',
                'en_wallet_explain'=> 'purchase commodity',
                'wallet_money'    => $lockOrder->order_money,
                'created_at'      => date('Y-m-d H:i:s'),
                'updated_at'      => date('Y-m-d H:i:s')
            ];
            $effects=UserWalletRecord::insert($WalletInfo);
            if(!empty($effects)){
                return true;
            }else{
                \Log::error('paymentComplete: wallet record insert failed', ['order_id'=>$orderId]);
                return false;
            }
        } catch (\Throwable $e) {
            \Log::error('paymentComplete exception', ['order_id'=>$orderId, 'error'=>$e->getMessage(), 'trace'=>$e->getTraceAsString()]);
            return false;
        }
    }


/**************************************************支付成功後的郵件發送丶個推發送，系統消息發送************************************************/
    /**
     * @param Request $request
     * @return array
     * 支付成功後的郵件發送丶個推發送，系統消息發送
     */
    public function OrderComplete($orderId){
        $OrderInfo = Order::where('order_id',$orderId)->first();
        $UserInfo = User::where('user_id',$OrderInfo['user_id'])->select('user_id','client_id','token_type')->first();
        if($OrderInfo['indent_type']==1){
            $Message = '您的訂單：'.$OrderInfo['order_no'].',已經訂購成功,我們將盡快為您處理！';
            $en_Message = 'Your order: '.$OrderInfo['order_no'].',has been ordered successfully, we will deal with you as soon as possible!';
        }else{
            $Message = '您的訂單：'.$OrderInfo['order_no'].',已經成功預約，時間為：'.$OrderInfo['order_time'];
            $en_Message = 'Your order: '.$OrderInfo['order_no'].', has been successfully booked, time: '.$OrderInfo['order_time'];
        }
        try {
            if(!empty($OrderInfo)){
                //加入系統消息
                $SystemInfo = new SystemInfo();
                $code=$SystemInfo->addSystemInfo($UserInfo['user_id'], $OrderInfo['indent_type'], $Message, $en_Message);
                if(!empty($code)){
                    $SendMessage= $this->language == "en" ? $en_Message : $Message;
                    if(!empty($OrderInfo['delivery_mail'])){
                        //邮箱推送
                        $EmailInfo['Email']=$OrderInfo['delivery_mail'];
                        $EmailInfo['Message']=$SendMessage;
                        $mail = new MailController();
                        $mail->MessageEmails($EmailInfo);
                        //更新訂單郵箱推送狀態
                        Order::where('order_id',$orderId)->update(['mail_state' => 2,'updated_at'=>date('Y-m-d H:i:s')]);
                    }
//                    //加入推送消息---web版本關閉推送
//                    if(!empty($UserInfo['client_id'])){
//                        $GeTui = new GeTuiController();
//                        @$PushInfo = $GeTui->pushToSingleByCid($UserInfo['client_id'],$SendMessage,$SendMessage, 1);
//                        //更新訂單信息
//                        Order::where('order_id',$orderId)->update([ 'push_state' => 2, 'mail_state' => 2,'updated_at'=>date('Y-m-d H:i:s')]);
//                        // $this->LogInfo('推送消息' , $PushInfo , 'PushInfo' , true);//开启日志记录
//                    }
                }
            }
        }catch (\Exception $e) {
            // return  $e->getMessage();
        }
    }







/**************************************************支付方式-Wonder支付*************************************************************************/
    /**
     * @param Request $request
     * @return array
     * 支付方式--Wonder支付
     * data['order_id']
     */
    public function WonderPayment(Request $request){
        $order_id = $request->get('order_id');//訂單ID
        $orderinfo = Order::where('order_id', $order_id)->select('*')->first()->toArray();
        $detailsinfo = OrderDetails::where('order_id', $order_id)->select('*')->get()->toArray();
        $user_id = $orderinfo['user_id'];//用户ID
        //查看有沒有該客戶，沒有就創建
        $UserInfo = $this->User->where('user_id', $user_id)->first();

        try {
            $wonderPayment = new \App\Extend\WonderPayment();
            
            // 检查是否有有效的 Wonder 支付配置
            if ($wonderPayment->hasValidConfig()) {
                // 计算订单总金额
                $totalAmount = $orderinfo['original_cost'] + $orderinfo['freight_price'] - $orderinfo['coupon_money'];
                
                // 生成唯一的参考号
                $referenceNumber = 'ORDER_' . $order_id . '_' . time();
                
                // 构建订单数据
                $orderData = [
                    'order' => [
                        'charge_fee' => (string)$totalAmount,
                        'tips' => '0.00',
                        'currency' => config('services.wonder.currency', 'HKD'),
                        'reference_number' => $referenceNumber,
                        'note' => 'Order payment for order ' . $order_id,
                        'callback_url' => config('services.basics.base_uri') . '/api/WonderPaymentWebhook',
                        'redirect_url' => $this->wonder_callback_success . "?success=true&orderid=" . $order_id,
                        'line_items' => []
                    ]
                ];
                
                // 添加订单详情行项
                foreach ($detailsinfo as $detail) {
                    $orderData['order']['line_items'][] = [
                        'uuid' => $this->generateUuid(),
                        'purchasable_type' => 'Charge',
                        'label' => $detail['product_name'],
                        'price' => (string)$detail['product_price'],
                        'quantity' => (string)$detail['product_number'],
                        'total' => (string)($detail['product_price'] * $detail['product_number'])
                    ];
                }

                // 调用 Wonder API 创建订单
                $result = $wonderPayment->createOrder($orderData, true);
                
                if ($result['success'] && isset($result['data']['data']['payment_link'])) {
                    $checkout = new \stdClass();
                    $checkout->url = $result['data']['data']['payment_link'];
                    return $checkout;
                } else {
                    // 如果 API 调用失败，记录日志
                    $errorMsg = isset($result['data']['message']) ? $result['data']['message'] : 'Payment initialization failed';
                    \Log::error('Wonder Payment Error', ['result' => $result]);
                }
            }
            
            // 备用方案：直接返回成功页面（便于测试）
            $checkout = new \stdClass();
            $checkout->url = $this->wonder_callback_success . "?success=true&orderid=" . $order_id;
            
            return $checkout;
        } catch (\Exception $e) {
            \Log::error('Wonder Payment Exception', ['error' => $e->getMessage()]);
            $checkout = new \stdClass();
            $checkout->url = $this->wonder_callback_success . "?success=true&orderid=" . $order_id;
            
            return $checkout;
        }
    }
    
    /**
     * 生成 UUID v4
     * @return string
     */
    private function generateUuid()
    {
        $data = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
    
    /**
     * Wonder 支付 Webhook 处理
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function WonderPaymentWebhook(Request $request)
    {
        try {
            $body = $request->getContent();
            $signature = $request->header('Signature');
            $nonce = $request->header('Nonce');
            $credential = $request->header('Credential');
            $action = $request->header('X-Action');
            
            \Log::info('Wonder Payment Webhook Received', [
                'action' => $action,
                'body' => json_decode($body, true)
            ]);
            
            // 強制驗簽：未配置公鑰或簽名不合法一律拒絕，避免偽造 order.paid 入帳
            $wonderPayment = new \App\Extend\WonderPayment();
            $isValid = $wonderPayment->verifyWebhookSignature(
                'POST',
                '/api/WonderPaymentWebhook',
                $body,
                $nonce,
                $credential,
                $signature
            );

            if (!$isValid) {
                \Log::error('Wonder Payment Webhook Signature Verification Failed');
                return response()->json(['success' => false], 400);
            }
            
            $payload = json_decode($body, true);
            
            // 处理支付成功事件
            if ($action === 'order.paid' && isset($payload['data'])) {
                $orderData = $payload['data'];
                $referenceNumber = $orderData['reference_number'] ?? '';
                
                // 从参考号中解析订单 ID
                if (strpos($referenceNumber, 'ORDER_') === 0) {
                    $parts = explode('_', $referenceNumber);
                    if (count($parts) >= 2) {
                        $orderId = $parts[1];

                        // 完成订单支付（paymentComplete 內含行鎖冪等，Webhook 重試不會重複入帳）
                        DB::beginTransaction();
                        $effects = $this->paymentComplete($orderId, '');
                        if ($effects === true || $effects === 'already_completed') {
                            // 支付完成后的操作；已處理過的回調不重複發送
                            if ($effects !== 'already_completed') {
                                $this->OrderComplete($orderId);
                            }
                            DB::commit();
                            \Log::info('Wonder Payment Webhook Processed Successfully', ['order_id' => $orderId]);
                        } else {
                            DB::rollBack();
                            \Log::error('Wonder Payment Webhook Failed to Complete', ['order_id' => $orderId]);
                        }
                    }
                }
            }
            
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            \Log::error('Wonder Payment Webhook Exception', ['error' => $e->getMessage()]);
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * @param Request $request
     * @return array
     * Wonder支付回調-成功鏈接
     */
    public function WonderCallbackSuccess(Request $request){
        $orderid = $request->get('orderid');//訂單ID
        $success = $request->get('success');

        // 安全：跳轉頁不作為入帳依據。入帳統一由已驗簽的 WonderPaymentWebhook(order.paid) 處理，
        // 這裡僅依訂單當前狀態展示結果，避免任何人構造 ?orderid=X 即完成支付。
        $paidCheck = Order::where('order_id', $orderid)->select('payment_status')->first();
        if (!empty($paidCheck) && (int)$paidCheck->payment_status === 3) {
            if($this->language == "en" ){
                return view('Payment/en_Success');
            }else{
                return view('Payment/Success');
            }
        }

        \Log::warning('Wonder callback visited but order not paid yet', ['order_id' => $orderid]);
        // 支付尚未完成（等待 Webhook 入帳）
        if($this->language == "en" ){
            return view('Payment/en_Error');
        }else{
            return view('Payment/Error');
        }
    }

    /**
     * @param Request $request
     * @return array
     * Wonder支付回調-失敗
     */
    public function WonderCallbackError(Request $request){
            //支付失敗
            if($this->language == "en" ){
                return view('Payment/en_Error');
            }else{
                return view('Payment/Error');
            }
    }
/**************************************************end****************************************************************************/
}
