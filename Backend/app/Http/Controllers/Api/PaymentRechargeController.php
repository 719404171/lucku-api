<?php
/*
 * File: PaymentRechargeController.php
 * Project: NewDemo
 * Created Time: 2024-07-08 05:18:42 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-09-10 3:30:13 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2024 成都睿創科技有限公司
 */

namespace App\Http\Controllers\Api;

use App\Models\User;
use App\Models\SystemInfo;
use App\Models\UserWalletRecord;
use App\Models\UserIntegralRecord;
use App\Http\Controllers\Controller;
use PayPal\Api\Amount;
use PayPal\Api\Details;
use PayPal\Api\Item;
use PayPal\Api\ItemList;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\RedirectUrls;
use PayPal\Api\Refund;
use PayPal\Api\Transaction;
use PayPal\Api\ExecutePayment;
use PayPal\Api\PaymentExecution;
use PayPal\Exception\PayPalConnectionException;
use PayPal\Api\Sale;
use PayPal\Rest\ApiContext;
use PayPal\Auth\OAuthTokenCredential;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Notifications\Notifiable;
//Stripe
use Stripe\Customer;
use Stripe\Checkout\Session;
use Laravel\Cashier\Cashier;
use Laravel\Cashier\Checkout;
use App\Http\Controllers\Api\MailController;
use App\Http\Controllers\Common\GeTuiController;

class PaymentRechargeController extends Controller{

    private $clientId;
    private $clientSecret;
    private $apiContext;
    private $Order;
    private $User;


    public function __construct(){
        $this->User = new User();
        // paypal充值回調-成功鏈接
        $this->paypal_recharge_success = config('services.basics.base_uri').'/api/PaypalRechargeSuccess';
        // paypal充值回調-失敗鏈接
        $this->paypal_recharge_error = config('services.basics.base_uri').'/api/PaypalRechargeError';
        // Stripe充值
        $this->stripe_recharge= config('services.basics.base_uri').'/api/StripeRecharge';
        // Stripe充值回調-成功鏈接
        $this->stripe_recharge_success = config('services.basics.base_uri').'/api/StripeRechargeSuccess';
        // Stripe充值回調-失敗鏈接
        $this->stripe_recharge_error = config('services.basics.base_uri').'/api/StripeRechargeError';
        // Wonder充值
        $this->wonder_recharge= config('services.basics.base_uri').'/api/WonderRecharge';
        // Wonder充值回調-成功鏈接
        $this->wonder_recharge_success = config('services.basics.base_uri').'/api/WonderRechargeSuccess';
        // Wonder充值回調-失敗鏈接
        $this->wonder_recharge_error = config('services.basics.base_uri').'/api/WonderRechargeError';
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
     * 充值
     */
    public function PaymentRecharge(Request $request){
        // 接收參數
        $info=$request->post();
        // 用戶 ID（強制從 token 取，不信任客戶端傳參）
        $userId = auth('api')->user()->user_id;

        // 目前僅支援 Wonder 充值（payment_mode=3）；Paypal(1)/Stripe(2) 已停用
        $paymentMode = (int)($info['payment_mode'] ?? 0);
        if (!in_array($paymentMode, [3], true)) {
            return $this->error('當前僅支援 Wonder 充值');
        }

        $rechargeMoney = $info['recharge_money'] ?? null;
        $exchangeId    = $info['exchange_id'] ?? null;

        if (empty($rechargeMoney) || (float)$rechargeMoney <= 0) {
            return $this->error('充值金額必須大於 0');
        }

        // 返回 WonderRecharge API 端點 URL（與原代碼行為一致：由 APP 端自行帶 auth header 再次調用）
        // 保持原返回格式：裸數組 ['url' => '...']，不包裝 status/msg/results
        $result = [];
        $result['url'] = $this->wonder_recharge . "?recharge_money=" . $rechargeMoney . "&exchange_id=" . ($exchangeId ?? '');
        return $result;
    }



/**************************************************支付方式-Paypal支付*************************************************************************/
    /**
     * 調起充值支付
     * @param $money
     * @param $userId
     * @return null|string
     * data['recharge_money']
     * data['user_id']
     */
    public function PaypalRecharge($data){
        // 创建支付对象
        $payer = new Payer();
        $payer->setPaymentMethod('paypal');
        // 创建项目列表
        $item = new Item();
        $item->setName('賬戶充值')->setCurrency('HKD') ->setQuantity(1)->setPrice($data['recharge_money']);
        $itemList = new ItemList();
        $itemList->setItems([$item]);

        //設置運費  支付金額
        $details = new Details();
        $details->setShipping(0)->setTax(0)->setSubtotal($data['recharge_money']);
        //設置總金額  運費+產品支付
        $amount = new Amount();
        $amount->setCurrency("HKD")->setTotal($data['recharge_money'])->setDetails($details);
        // 设置重定向的URL
        $transaction = new Transaction();
        $transaction->setAmount($amount)->setItemList($itemList) ->setDescription("Payment description")->setInvoiceNumber(uniqid());
        $redirectUrls = new RedirectUrls();
        $redirectUrls->setReturnUrl($this->paypal_recharge_success."?success=true&user_id=".$data['user_id']."&recharge_money=".$data['recharge_money'])
                     ->setCancelUrl($this->paypal_recharge_error."?success=false&user_id=".$data['user_id']."&recharge_money=".$data['recharge_money']);
        // 创建支付
        $payment = new Payment();
        $payment->setIntent("sale")->setPayer($payer)->setRedirectUrls($redirectUrls)->setTransactions(array(array('amount' => $amount, 'item_list' => $itemList)));
        $payment->create($this->apiContext);
        //生成地址
        $approvalUrl = $payment->getApprovalLink();
        $result['url']=$approvalUrl;
        return $result;
    }

    /**
     * 充值回調
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function PaypalRechargeSuccess(Request $request){
        $user_id = $request->get('user_id');//用戶ID
        $recharge_money = $request->get('recharge_money');//充值金額
        $success = $request->get('success');
		$paymentId = $request->get('paymentId');//支付ID
        $PayerID=$request->get('PayerID');//支付人ID
        if (!empty($success)) {
            try {
                // 首次回調確實在這裡執行支付；用戶刷新/重複跳轉時 execute 會拋「payment already done」，
                // 此時改為讀回支付結果，同樣能拿到 sale id 走幂等入帳，避免「錢扣了卻沒到賬」。
                try {
                    $payment = Payment::get($paymentId, $this->apiContext);
                    $execution = new PaymentExecution();
                    $execution->setPayerId($PayerID);
                    $result = $payment->execute($execution, $this->apiContext)->toArray();
                } catch (\Throwable $e) {
                    \Log::info('paypal execute skipped, fallback to read result', ['payment_id' => $paymentId, 'error' => $e->getMessage()]);
                    $result = Payment::get($paymentId, $this->apiContext)->toArray();
                }
                $refund_id = $result['transactions'][0]['related_resources'][0]['sale']['id'] ?? '';
                // 安全：入帳金額一律採用 PayPal 回傳的實付金額，不信任 URL 參數
                $paidTotal = $result['transactions'][0]['amount']['total'] ?? null;
                if (empty($refund_id) || $paidTotal === null || (float)$paidTotal <= 0) {
                    \Log::error('paypal recharge failed: sale id/amount not found', ['user_id' => $user_id, 'payment_id' => $paymentId]);
                    return $this->language == "en" ? view('Payment/en_Error') : view('Payment/Error');
                }
                // $this->LogInfo('paypal退款ID:' , $orderRefundId , 'PaypalCallback' , true);//开启日志记录
                //支付完成更改用戶信息(郵箱 系統消息 ---推送已關閉)；以 sale id 為幂等鍵防止回調重複入帳
                $effects=$this->RechargeComplete($user_id, $recharge_money ,1, $refund_id);// 支付完成更改用戶信息
                if(!empty($effects)){
                    //支付完成
                    if($this->language == "en" ){
                        return view('Payment/en_Success');
                    }else{
                        return view('Payment/Success');
                    }
                }else{
                    // 入帳失敗（非重複回調），退款
                    $Refund['refund_id']=$refund_id;//退款id
                    $Refund['recharge_money']=$paidTotal;//退款金額（實付金額）
                    $returnInfo = $this->PayPalReturnMoney($Refund);
                    //支付失敗
                    if($this->language == "en" ){
                        return view('Payment/en_Error');
                    }else{
                        return view('Payment/Error');
                    }
                }
            } catch (\Throwable $e) {
                \Log::error('paypal recharge callback exception', ['user_id' => $user_id, 'error' => $e->getMessage()]);
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
            $amt->setCurrency('HKD')->setTotal($data['recharge_money']);  // 退款的费用
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


/**************************************************支付方式-Stripe支付*************************************************************************/
    /**
     * @param Request $request
     * @return array
     * 支付方式--Stripe充值
     * data['recharge_money']
     * 卡号  Visa:4242424242424242  4000000320000021 4000000760000002
     * 测试卡号位置：https://docs.stripe.com/testing#international-cards
     */
    public function StripeRecharge(Request $request){
        //用户id
        $user_id = $request->get('user_id');
        $recharge_money = $request->get('recharge_money');//充值金額;
        $exchange_id = $request->get('exchange_id');//兌換方案ID

        // 如果 recharge_money 為空，從 exchange_settings 獲取
        if (empty($recharge_money) && !empty($exchange_id)) {
            $exchange = \App\Models\ExchangeSetting::where('exchange_id', $exchange_id)->where('status', 1)->first();
            if (!$exchange) {
                return [
                    'status' => 500,
                    'msg' => __('api.exchange_not_found'),
                    'url' => null
                ];
            }
            $recharge_money = $exchange->amount;
        }

        // 支付方式:   2：Stripe
        // Stripe支付最小金額為4元
        if($recharge_money < 4){
            return $this->error(__('api.stripe_lowest'));
        }
        //查看有沒有該客戶，沒有就創建
        $UserInfo = $this->User->where('user_id',$user_id)->first();
        //修復stripe_id是空的時候報錯，只能是NULL
        if (empty($UserInfo['stripe_id'])) {
            $this->User->where('user_id',$UserInfo['user_id'])->update(['stripe_id'=>NULL]);
        }
        // 如果你想为给定的计费模型检索 Stripe 客户对象，但不确定该计费模型是否已经是 Stripe 中的客户，
        // 则可以使用 createOrGetStripeCustomer 方法。 如果尚不存在，此方法将在 Stripe 中创建一个新客户：
        $info['name']=$UserInfo['user_name'];
        $info['phone']=$UserInfo['user_phone'];
        // $info['email']=$UserInfo['user_email'];
        $stripeCustomer = $this->User->where('user_id',$user_id)->first()->createOrGetStripeCustomer($info);
        //創建完成-end
        // Stripe支付
        //产品金额(單位：分)
        $amount = $recharge_money * 100;//充值金額
        // Stripe支付最小金额为4元
        if($amount < 400) {
            return [
                'status' => 500,
                'msg' => __('api.stripe_lowest'), // 充值金额过低提示
                'url' => null
            ];
        }
        try {
            $name = '賬戶充值'; // 产品名称
            $quantity = 1; // 产品数量
            $sessionOptions = [
                'mode'        => 'payment',
                'locale'      => 'zh-HK', // 设置为繁体中文
                'success_url' => $this->stripe_recharge_success."?success=true&user_id=".$user_id."&recharge_money=".$recharge_money."&exchange_id=".$exchange_id.'&session_id={CHECKOUT_SESSION_ID}', // 同步回调
                'cancel_url'  => $this->stripe_recharge_error."?success=false&user_id=".$user_id."&recharge_money=".$recharge_money."&exchange_id=".$exchange_id, // 异步回调
                'metadata'    => [
                    'user_id' => $user_id,
                    'recharge_money' => $recharge_money,
                    'exchange_id' => $exchange_id,
                ]
            ];
            $UserCheckout = $this->User->where('user_id', $user_id)->first();
            $checkout = $UserCheckout->checkoutCharge($amount, $name, $quantity, $sessionOptions);
//            $results = ['success_url' => $sessionOptions["success_url"]]; // Stripe支付成功链接
            // 成功返回
            return [
                'status' => 200,
                'msg' => '充值成功', // 成功提示信息
//                'url'    => $checkout->url, // Stripe 支付需要的页面链接
                'results' => ['url'=>$checkout->url]//stripe支付頁面鏈接(必要)
            ];
        } catch (\Exception $e) {
            // 失败返回
            $results =['url' => $this->stripe_recharge_error . "?success=false&user_id=" . $user_id . "&recharge_money=" . $recharge_money];
            //results作为顶级字段
            return [
                'status' => 500,
                'msg' => '充值失败: ' . $e->getMessage(), // 失败提示信息+错误详情
                'results' => $results  // results直接作为顶级字段
            ];
        }
    }

    /**
     * @param Request $request
     * @return array
     * Stripe支付回調-成功鏈接
     */
    public function StripeRechargeSuccess(Request $request){
        $sessionId = (string)$request->get('session_id');
        // 安全：必須持有有效 session_id，並向 Stripe 查詢確認已支付；用戶與金額一律取服務端/ metadata 值
        if ($sessionId === '') {
            \Log::error('stripe recharge callback rejected: missing session_id');
            return $this->language == "en" ? view('Payment/en_Error') : view('Payment/Error');
        }
        try {
            $stripe = new \Stripe\StripeClient(config('cashier.secret'));
            $session = $stripe->checkout->sessions->retrieve($sessionId, []);
        } catch (\Throwable $e) {
            \Log::error('stripe recharge callback verify failed', ['session_id' => $sessionId, 'error' => $e->getMessage()]);
            return $this->language == "en" ? view('Payment/en_Error') : view('Payment/Error');
        }
        if (($session->payment_status ?? '') !== 'paid') {
            \Log::error('stripe recharge callback rejected: not paid', ['session_id' => $sessionId]);
            return $this->language == "en" ? view('Payment/en_Error') : view('Payment/Error');
        }
        $user_id = (int)($session->metadata->user_id ?? 0);
        $exchange_id = $session->metadata->exchange_id ?? null;
        if ($user_id <= 0) {
            \Log::error('stripe recharge callback rejected: user_id missing in metadata', ['session_id' => $sessionId]);
            return $this->language == "en" ? view('Payment/en_Error') : view('Payment/Error');
        }
        try {
            // 到帳金額：有兌換方案時以方案原價為準（服務端），否則用 Stripe 實付金額
            $originalAmount = ((float)$session->amount_total) / 100;
            if (!empty($exchange_id)) {
                $exchange = \App\Models\ExchangeSetting::where('exchange_id', $exchange_id)->where('status', 1)->first();
                if ($exchange) {
                    $originalAmount = $exchange->amount;
                }
            }
            // 支付完成更改用戶信息（到帳金額 = 原價）；以 Stripe session_id 為幂等鍵防止回調重複入帳
            $effects = $this->RechargeComplete($user_id, $originalAmount, 2, $sessionId);
            if(!empty($effects)){
                //支付完成
                if($this->language == "en" ){
                    return view('Payment/en_Success');
                }else{
                    return view('Payment/Success');
                }
            }else{
                // 支付不成功，退款
                // $returnInfo = $this->Striperefund($RefundId);
                //支付失敗
                if($this->language == "en" ){
                    return view('Payment/en_Error');
                }else{
                    return view('Payment/Error');
                }
            }
        } catch (\Throwable $e) {
            \Log::error('stripe recharge callback exception', ['user_id' => $user_id, 'error' => $e->getMessage()]);
            //支付失敗
            // return  $e->getMessage();
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
     * Stripe支付回調-失敗
     */
    public function StripeRechargeError(Request $request){
            //支付失敗
            // return  $e->getMessage();
            if($this->language == "en" ){
                return view('Payment/en_Error');
            }else{
                return view('Payment/Error');
            }
    }




/**************************************************支付完成訂單修改****************************************************************************/
    /**
     * @param Request $request
     * @return array
     * 支付完成訂單修改
     */
    /**
     * 充值完成（入帳核心）
     * 寫入顺序：幂等標記 → 加 user_balance（餘額）→ 加 user_integral（積分）→ 寫 wallet 記錄
     * 注意：所有"金幣"註釋一律是 user_balance（餘額），不是 user_coin（鑽石/點數）
     */
    public function RechargeComplete($user_id, $recharge_money, $payment_mode=1, $tradeNo='', $wonderOrderNo='', $exchangeId=0, $paidAmount=0){
        $user_id = (int)$user_id;
        $recharge_money = (float)$recharge_money;
        $paidAmount = (float)$paidAmount;

        // 入帳金額下限保護：0 或負數一律拒絕
        if ($recharge_money <= 0) {
            \Log::error('recharge rejected: amount <= 0', ['user_id'=>$user_id, 'amount'=>$recharge_money, 'trade_no'=>$tradeNo]);
            return false;
        }

        DB::beginTransaction();
        try {
            // 檔位優惠（贈送積分）：每個用戶、每個檔位僅首次充值贈送
            $bonusIntegral = 0;
            $exchangeId = (int)$exchangeId;
            if ($exchangeId > 0) {
                $bonus = (int) DB::table('exchange_settings')
                    ->where('exchange_id', $exchangeId)
                    ->where('status', 1)
                    ->value('bonus_coin');
                if ($bonus > 0) {
                    if (!\App\Models\Base::hasTableCached('user_recharge_bonus')) {
                        $bonusIntegral = $bonus;
                    } else {
                        $claimed = DB::table('user_recharge_bonus')->insertOrIgnore([
                            'user_id'     => $user_id,
                            'exchange_id' => $exchangeId,
                            'bonus_coin'  => $bonus,
                            'created_at'  => date('Y-m-d H:i:s'),
                            'updated_at'  => date('Y-m-d H:i:s')
                        ]);
                        if ($claimed > 0) {
                            $bonusIntegral = $bonus;
                        }
                    }
                }
            }
            // 本次實際到帳餘額 = 基礎充值金額 + 首次檔位優惠
            $creditBalance = $recharge_money + $bonusIntegral;

            // F-14 冪等：以支付網關流水號為唯一鍵寫標記，重複回調/Webhook 重試直接跳過，防止重複加幣
            if (!empty($tradeNo)) {
                $logData = [
                    'user_id' => $user_id,
                    'trade_no' => (string)$tradeNo,
                    'recharge_money' => $creditBalance,  // 到帳金幣數（含贈送）
                    'payment_mode' => $payment_mode,
                    'status' => 1,
                    'created_at' => date('Y-m-d H:i:s'),
                    'updated_at' => date('Y-m-d H:i:s')
                ];
                // 未跑遷移前跳過該欄位，避免影響其它支付方式的入帳
                if (\App\Models\Base::hasColumnCached('payment_recharge_log', 'wonder_order_no')) {
                    $logData['wonder_order_no'] = (string)$wonderOrderNo;
                }
                // 實際支付金額 + 贈送金幣（遷移後才有）
                if (\App\Models\Base::hasColumnCached('payment_recharge_log', 'paid_amount')) {
                    $logData['paid_amount'] = $paidAmount;
                }
                if (\App\Models\Base::hasColumnCached('payment_recharge_log', 'bonus_coin')) {
                    $logData['bonus_coin'] = $bonusIntegral;
                }
                $markInserted = DB::table('payment_recharge_log')->insertOrIgnore($logData);
                if ($markInserted === 0) {
                    DB::rollBack();
                    \Log::info('recharge skipped: duplicated callback', ['user_id' => $user_id, 'trade_no' => $tradeNo]);
                    return 'already_completed';
                }
            }
           $UserBalance = User::where('user_id',$user_id)->increment('user_balance',$creditBalance);
            //添加钱包记录
            $WalletInfo = [
                'user_id' => $user_id,
                'wallet_type' => 2,
                'payment_mode' => $payment_mode,
                'wallet_explain'=>'賬號充值',
                'en_wallet_explain'=>'アカウントチャージ',
                'wallet_money' => $creditBalance,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ];
            $effects=UserWalletRecord::insert($WalletInfo);

            // 充值赠送积分（1:1，按實際到帳金額）
            $integralNum = intval($creditBalance);
            User::where('user_id',$user_id)->increment('user_integral', $integralNum);
            User::where('user_id',$user_id)->increment('user_integral_all', $integralNum);
            UserIntegralRecord::insert([
                'user_id' => $user_id,
                'integral_type' => 1,
                'integral_explain' => '充值赠送積分',
                'en_integral_explain' => 'Recharge bonus points',
                'integral_number' => $integralNum,
                'created_at' => date('Y-m-d H:i:s'),
                'updated_at' => date('Y-m-d H:i:s')
            ]);

            if(!empty($effects)){
                // Wonder 充值（payment_mode=3）不發系統消息，僅保留充值記錄
                if ($payment_mode != 3) {
                    $en_Message = 'アカウントチャージが成功しました。チャージ金額: '.$creditBalance.' !';
                    $Message = '賬號充值成功，充值金額 '.$creditBalance.' !';
                    // 站內系統消息；充值不再發送郵件（外部 SMTP 同步發信會把入帳事務一起拖住）
                    (new SystemInfo())->addSystemInfo($user_id, 3, $Message, $en_Message);
                }
                DB::commit();
                \Log::info('recharge completed', [
                    'user_id'      => $user_id,
                    'amount'       => $creditBalance,
                    'bonus_coin'   => $bonusIntegral,
                    'payment_mode' => $payment_mode,
                    'trade_no'     => $tradeNo,
                ]);
                return  true;
            }else{
                DB::rollBack();
                \Log::error('recharge failed: wallet record not inserted', ['user_id' => $user_id, 'amount' => $creditBalance]);
                return  false;

            }
        }catch (\Throwable $e) {
            DB::rollBack();
            \Log::error('recharge failed', ['user_id' => $user_id, 'amount' => $creditBalance ?? $recharge_money, 'error' => $e->getMessage()]);
            return  false;
        }
    }






/**************************************************支付方式-Wonder支付*************************************************************************/
    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * 支付方式--Wonder充值
     * 依據文檔 https://developer.wonder.today/api/api_references/create-an-order
     * data['recharge_money']
     * data['user_id']
     * data['exchange_id'](可選)
     */
    public function WonderRecharge(Request $request){
        // 統一從 token 取 user_id（路由已加 checkLogin middleware），不信任客戶端傳參
        $user_id = (int)(auth('api')->user()->user_id ?? 0);
        if ($user_id <= 0) {
            return response()->json(['status' => 401, 'msg' => __('api.no_login'), 'results' => null]);
        }

        // 支持 POST 和 GET（PaymentRecharge 可能以 GET 調用），但 user_id 一律從 auth 取
        $recharge_money = $request->post('recharge_money') ?? $request->get('recharge_money');
        $exchange_id    = $request->post('exchange_id')    ?? $request->get('exchange_id');

        // 如有 exchange_id，直接從 exchange_settings 獲取
        if (!empty($exchange_id)) {
            $exchange = \App\Models\ExchangeSetting::where('exchange_id', $exchange_id)->where('status', 1)->first();
            if (!$exchange) {
                return response()->json([
                    'status' => 500,
                    'msg' => '充值檔位不存在或已停用',
                    'results' => null
                ]);
            }
            // 支付金額 = 檔位 amount；到帳金額 = exchange_coin（Webhook 處理）
            $recharge_money = $exchange->amount;
        }

        if (empty($recharge_money) || (float)$recharge_money <= 0) {
            return response()->json([
                'status' => 500,
                'msg' => '充值金額必須大於 0',
                'results' => null
            ]);
        }

        try {
            $wonderPayment = new \App\Extend\WonderPayment();

            // 檢查是否有有效的 Wonder 支付配置
            if (!$wonderPayment->hasValidConfig()) {
                \Log::error('Wonder Recharge: Wonder payment not configured');
                return response()->json([
                    'status' => 500,
                    'msg' => 'Wonder payment not configured',
                    'results' => null
                ]);
            }

            // 生成唯一的參考號（Webhook 回調時解析 user_id 與 exchange_id；尾隨隨機數避免同一秒並發建單衝突）
            $referenceNumber = 'RECHARGE_' . time() . '_' . $user_id . '_' . ($exchange_id ?: '0') . '_' . substr(md5(uniqid('', true)), 0, 6);

            // 構建訂單數據（Create an order 請求體）
            $orderData = [
                'order' => [
                    'charge_fee' => (string)$recharge_money,
                    'tips' => '0.00',
                    'currency' => config('services.wonder.currency', 'HKD'),
                    'reference_number' => $referenceNumber,
                    'note' => 'Account recharge for user ' . $user_id,
                    'callback_url' => config('services.basics.base_uri') . '/api/WonderRechargeWebhook',
                    'redirect_url' => $this->wonder_recharge_success . "?success=true&user_id=" . $user_id . "&exchange_id=" . ($exchange_id ?? ''),
                    'line_items' => [
                        [
                            'uuid' => $this->generateUuid(),
                            'purchasable_type' => 'Charge',
                            'label' => 'Account Recharge',
                            'price' => (string)$recharge_money,
                            'quantity' => '1',
                            'total' => (string)$recharge_money
                        ]
                    ]
                ]
            ];

            // 調用 Wonder API 創建訂單（with_payment_link=true 返回支付鏈接）
            $result = $wonderPayment->createOrder($orderData, true);

            if ($result['success'] && isset($result['data']['data']['payment_link'])) {
                return response()->json([
                    'status' => 200,
                    'msg' => 'success',
                    'results' => [
                        'url' => $result['data']['data']['payment_link'],
                        'order' => $result['data']['data']['order'] ?? null
                    ]
                ]);
            }

            // API 調用失敗：返回錯誤信息（不返回虛假成功鏈接）
            $errorMsg = !empty($result['message'])
                ? $result['message']
                : (isset($result['data']['message']) ? $result['data']['message'] : 'Payment initialization failed');
            \Log::error('Wonder Recharge Error', ['result' => $result]);

            return response()->json([
                'status' => 500,
                'msg' => $errorMsg,
                'results' => null
            ]);
        } catch (\Throwable $e) {
            // 失敗返回（改用 Throwable 捕獲 PHP Error，並記錄完整追蹤便於排查）
            \Log::error('Wonder Recharge Exception', [
                'error' => $e->getMessage(),
                'class' => get_class($e),
                'trace' => $e->getTraceAsString()
            ]);

            $errorMsg = !empty($e->getMessage())
                ? $e->getMessage()
                : 'Payment initialization failed (' . get_class($e) . ')';

            return response()->json([
                'status' => 500,
                'msg' => $errorMsg,
                'results' => null
            ]);
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
     * Wonder 充值 Webhook 处理
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function WonderRechargeWebhook(Request $request)
    {
        try {
            $body = $request->getContent();
            $signature = $request->header('Signature');
            $nonce = $request->header('Nonce');
            $credential = $request->header('Credential');
            $action = $request->header('X-Action');
            
            \Log::info('Wonder Recharge Webhook Received', [
                'action' => $action,
                'body' => json_decode($body, true)
            ]);
            
            // 強制驗簽：未配置公鑰或簽名不合法一律拒絕，避免偽造 order.paid 入帳
            $wonderPayment = new \App\Extend\WonderPayment();
            $isValid = $wonderPayment->verifyWebhookSignature(
                'POST',
                '/api/WonderRechargeWebhook',
                $body,
                $nonce,
                $credential,
                $signature
            );

            if (!$isValid) {
                \Log::error('Wonder Webhook Signature Verification Failed');
                return response()->json(['success' => false], 400);
            }
            
            $payload = json_decode($body, true);
            
            // 处理支付成功事件
            // 實測 Webhook body 為頂層訂單物件（無 data 包裹），兼容兩種結構
            if ($action === 'order.paid' && is_array($payload)) {
                $orderData = (isset($payload['data']) && is_array($payload['data']))
                    ? $payload['data']
                    : $payload;
                $referenceNumber = $orderData['reference_number'] ?? '';
                
                // 从参考号中解析用户 ID 和兌換方案
                if (strpos($referenceNumber, 'RECHARGE_') === 0) {
                    $parts = explode('_', $referenceNumber);
                    if (count($parts) >= 3) {
                        $userId = $parts[2];
                        $exchangeId = $parts[3] ?? null;
                        $paidAmount = $orderData['paid_total'] ?? 0;

                        // 到帳金額：有兌換方案時基礎到帳 = 兌換金幣（exchange_coin）；
                        // 優惠金幣（bonus_coin）由 RechargeComplete 判斷「該用戶是否首次充值該檔位」後加贈
                        $creditBalance = $paidAmount;
                        if (!empty($exchangeId)) {
                            $exchange = \App\Models\ExchangeSetting::where('exchange_id', $exchangeId)->where('status', 1)->first();
                            if ($exchange) {
                                $creditBalance = (int)($exchange->exchange_coin ?? 0);
                            }
                        }

                        // Wonder 網關訂單號，供後台到 Wonder 商家後台（hq.wonder.app）搜單對賬
                        $wonderOrderNo = $orderData['number'] ?? '';

                        // 完成充值（reference_number 為幂等鍵，Webhook 重試不會重複入帳）
                        $effects = $this->RechargeComplete($userId, $creditBalance, 3, $referenceNumber, $wonderOrderNo, (int)$exchangeId, $paidAmount);
                        if ($effects) {
                            \Log::info('Wonder Recharge Webhook Processed Successfully', ['user_id' => $userId, 'amount' => $creditBalance]);
                        } else {
                            \Log::error('Wonder Recharge Webhook Failed to Complete', ['user_id' => $userId]);
                        }
                    }
                }
            }
            
            return response()->json(['success' => true]);
        } catch (\Throwable $e) {
            \Log::error('Wonder Recharge Webhook Exception', ['error' => $e->getMessage()]);
            return response()->json(['success' => false], 500);
        }
    }

    /**
     * @param Request $request
     * @return array
     * Wonder支付回調-成功鏈接
     */
    public function WonderRechargeSuccess(Request $request){
        $success = $request->get('success');
        // 入帳統一由 WonderRechargeWebhook（order.paid 回調）處理，跳轉頁僅展示結果，避免重複入帳
        $isSuccess = $success === 'true' || $success === true || (int)$success === 1;
        if($isSuccess){
            //支付完成
            if($this->language == "en" ){
                return view('Payment/en_Success');
            }else{
                return view('Payment/Success');
            }
        }else{
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
     * Wonder支付回調-失敗
     */
    public function WonderRechargeError(Request $request){
            //支付失敗
            if($this->language == "en" ){
                return view('Payment/en_Error');
            }else{
                return view('Payment/Error');
            }
    }
/**************************************************end****************************************************************************/
}
