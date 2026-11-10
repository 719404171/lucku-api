<?php

/*
 * File: AuthServiceProvider.php
 * Created Time: 2023-10-12 3:11:16 pm
 * Author: Wang Yu Qiang (719404171@qq.com)
 * -----
 * Last Modified: 2026-09-09 6:00:12 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright 2023 - 2023 Brainstorm Ltd, 成都睿創科技有限公司
 *
 *APP端接口
 *
 */

/*
 *算術驗證碼（註冊用）
 */
Route::get("captcha/generate", 'Api\CaptchaController@generate')->middleware(['throttle:20,1']); //生成算術題，返回 token + question（每 IP 每分鐘 20 次）

/*
 *reCAPTCHA v3 配置（公開，前端註冊頁面加載時調用）
 *返回 { enabled: bool, sitekey: string }
 */
Route::get("recaptcha/config", function () {
    return response()->json([
        'status'  => 200,
        'msg'     => 'ok',
        'results' => [
            'enabled' => \App\Services\RecaptchaService::enabled(),
            'sitekey' => \App\Services\RecaptchaService::sitekey(),
        ],
    ]);
})->middleware(['throttle:60,1']);

/*
 *用戶註冊
 */
Route::post("register", 'Api\RegistrationController@register')->middleware(['prevent.duplicate:5', 'throttle:10,1']); //用戶註冊（手機號）
Route::post("registerEmail", 'Api\RegistrationController@registerEmail')->middleware(['prevent.duplicate:5', 'throttle:10,1']); //用戶註冊（郵箱）
Route::get("verifyEmail", 'Api\RegistrationController@verifyEmail'); //郵箱驗證（點擊郵箱鏈接）

/*
 *用戶登錄
 */
Route::post("login", 'Api\LoginController@login')->middleware(['prevent.duplicate:5', 'throttle:10,1']); //用戶登錄（手機號）
Route::post("loginEmail", 'Api\LoginController@loginEmail')->middleware(['prevent.duplicate:5', 'throttle:10,1']); //用戶登錄（郵箱）

/*
 *用戶相關接口
 */
Route::post("getUsersInfo", "Api\UserController@getUsersInfo")->middleware('checkLogin'); // 獲取個人信息
Route::post("UpdateUsers", "Api\UserController@UpdateUsers")->middleware('checkLogin'); // 修改用戶信息
Route::post("UpdatePhone", "Api\UserController@UpdatePhone")->middleware('checkLogin'); // 修改綁定手機號
Route::post("forgetPass", "Api\UserController@forgetPass")->middleware(['prevent.duplicate:5', 'throttle:10,1']); // 忘記/修改密碼
Route::post("PasswordVerify", "Api\UserController@PasswordVerify")->middleware('checkLogin'); // 密碼驗證
Route::post("deleteUser", "Api\UserController@deleteUser")->middleware('checkLogin'); // 删除賬戶

/*
 *蘋果登錄相關接口
 */
Route::post("/AppleLogin", "Api\AppleLoginController@AppleLogin")->middleware(['prevent.duplicate:5', 'throttle:10,1']); //蘋果登錄

/*
 *谷歌登錄相關接口
 */
Route::post("/GoogleLogin", "Api\GoogleLoginController@GoogleLogin")->middleware(['prevent.duplicate:5', 'throttle:10,1']); //谷歌登錄


/*
 *註銷賬戶頁面
 */
Route::get('/logout', function () {
    $base_uri = config('services.basics.base_uri'); //app的base_uri
    $this->language = app('global_language') == "en" ? "en_" : "";
    return view($this->language.'deleteUser')->with('base_uri', $base_uri);
});

/*
 *註銷賬戶接口
 */
Route::post("BannedUser", "Api\UserController@BannedUser")->middleware('throttle:5,1'); // 註銷賬戶接口（需校驗帳號密碼）

/*
 *免责条款相關接口
 */
Route::post("getClauseApi", "Api\ClauseController@getClauseApi"); // 獲取免责条款

/*
 *隱私協議相關接口
 */
Route::post("getPrivacyApi", "Api\PrivacyController@getPrivacyApi"); // 獲取隱私協議

/*
 *用户协议相關接口
 */
Route::post("getUserAgreementApi", "Api\UserAgreementController@getUserAgreementApi"); // 獲取用户协议
/*
 *隐私政策相關接口
 */
Route::post("getPrivacyPolicyApi", "Api\PrivacyPolicyController@getPrivacyPolicyApi"); // 獲取隐私政策

/*
 *一些公共函數相關接口
 */
Route::post("uploadImage", "Api\BaseController@uploadImage"); // 上傳圖片接口
Route::post("sendMessage", "Api\BaseController@sendMessage")->middleware(['prevent.duplicate:5', 'throttle:5,1']); // 發送短信驗證碼
Route::post("sendEmailVerifyCode", "Api\MailController@sendEmailVerifyCode")->middleware(['prevent.duplicate:5', 'throttle:5,1']); // 發送郵箱驗證碼
Route::post("resetPasswordByEmail", "Api\UserController@resetPasswordByEmail")->middleware(['prevent.duplicate:5', 'throttle:10,1']); // 郵箱驗證碼重置密碼
Route::post("getUserCode", "Api\BaseController@getUserCode"); // 獲取用戶二維碼

/*
 *會員等級相關接口
 */
Route::post("getUserMemberrApi", "Api\UserMemberController@getUserMemberrApi"); // 獲取會員等級信息（分頁）
Route::post("getMemberApi", "Api\UserMemberController@getMemberApi"); // 獲取所有會員等級信息（不分頁）

/*
 *系統消息相關接口
 */
Route::post("/getSystemInfo", "Api\SystemInfoController@getSystemInfo"); //  獲取系統消息列表（中獎資訊 type=4 不需登入）
Route::post("/editSystemInfo", "Api\SystemInfoController@editSystemInfo")->middleware('checkLogin'); // 編輯系統消息（全部標記為已讀）
Route::post("/DelSystemInfo", "Api\SystemInfoController@DelSystemInfo")->middleware('checkLogin'); // 刪除系統消息

/*
 *Banner相關接口
 */
Route::post("getBannerApi", "Api\BannerController@getBannerApi"); // 獲取Banner
Route::post("getBannerPopupApi", "Api\BannerPopupController@getBannerPopupApi"); // 獲取Banner彈窗

/*
 *店鋪設置相關接口
 */
Route::post("/getShopSettingApi", "Api\ShopSettingController@getShopSettingApi"); //獲取所有店鋪設置

/*
 *APP版本相關接口
 */
Route::post("/getAppVersions", "Api\AppVersionsController@getAppVersions"); // 获取APP版本

/*
 *客服接口
 */
Route::post("/getShopServiceApi", "Api\ShopServiceController@getShopServiceApi"); // 獲取客服設置列表

/*
 *最新資訊接口
 */
Route::post("/getNewsApi", "Api\NewsController@getNewsApi"); // 獲取最新資訊
Route::post("/getNewsDetails", "Api\NewsController@getNewsDetails"); // 獲取最新資訊详情

/*
 *问答接口
 */
Route::post("/getShopFAQApi", "Api\ShopFAQController@getShopFAQApi"); // 獲取问答列表

/*
 *收貨地址相關接口
 */
Route::post("getAddressAreaApi", "Api\AddressAreaController@getAddressAreaApi"); // 獲取区域选择列表
Route::post("addUserAddress", "Api\UserAddressController@addUserAddress")->middleware('checkLogin'); // 添加收貨地址
Route::post("getUserAddress", "Api\UserAddressController@getUserAddress")->middleware('checkLogin'); // 獲取收貨地址列表
Route::post("editUserAddress", "Api\UserAddressController@editUserAddress")->middleware('checkLogin'); // 編輯收貨地址
Route::post("delUserAddress", "Api\UserAddressController@delUserAddress")->middleware('checkLogin'); // 刪除收貨地址

/*
 *产品分类相关接口
 */
// Route::post("/getProductSortListApi", "Api\ProductSortController@getProductSortListApi"); // 獲取產品分类列表
Route::post("/getSortByType", "Api\ProductSortController@getSortByType"); // 根據服務或者產品獲得產品分类列表

/*
 *产品相关接口
 */
Route::post("/getProductListApi", "Api\ProductController@getProductListApi"); // 獲取產品列表
Route::post("/getProductDetails", "Api\ProductController@getProductDetails"); // 獲取產品详情

/*
 *用戶收藏相关接口
 */
Route::post("getUsersCollect", "Api\UserCollectController@getUsersCollect")->middleware('checkLogin'); // 獲取用戶收藏
Route::post("CollectProduct", "Api\UserCollectController@CollectProduct")->middleware('checkLogin'); // 收藏/取消收藏商品

/*
 *購物車相關接口
 */
Route::post("getUserCart", "Api\UserCartController@getUserCart")->middleware('checkLogin'); // 獲取購物車列表
Route::post("addUserCart", "Api\UserCartController@addUserCart")->middleware('checkLogin'); // 添加購物車
Route::post("editUserCart", "Api\UserCartController@editUserCart")->middleware('checkLogin'); // 編輯購物車
Route::post("delUserCart", "Api\UserCartController@delUserCart")->middleware('checkLogin'); // 刪除購物車

/*
 *用戶積分明細相關接口
 */
Route::post("/getUserIntegral", "Api\UserIntegralRecordController@getUserIntegral")->middleware('checkLogin'); // 獲取用戶積分明細
Route::post("/invitationCodeVerify", "Api\RegistrationController@invitationCodeVerify"); // 驗證邀請碼
Route::post("/useInvitationCode", "Api\RegistrationController@useInvitationCode")->middleware(['checkLogin', 'prevent.duplicate:5']); // 使用邀請碼（每人每碼一次，可被多人使用）

/*
 *用戶錢包相關接口
 */
Route::post("/getUserWallet", "Api\UserWalletRecordController@getUserWallet")->middleware('checkLogin'); // 獲取用戶錢包明細
Route::post("/convertDiamondToCoin", "Api\UserController@convertDiamondToCoin")->middleware(['checkLogin', 'prevent.duplicate:5']); // 鑽石兌換金幣（1:1）

/*
 *支付相關接口
 */
Route::post("/PurchaseProduct", "Api\PaymentController@PurchaseProduct")->middleware(['checkLogin', 'prevent.duplicate:5']); // 產品支付
Route::get("/PaypalCallbackSuccess", "Api\PaymentController@PaypalCallbackSuccess"); // paypal支付回調-成功鏈接
Route::get("/PaypalCallbackError", "Api\PaymentController@PaypalCallbackError"); // paypal支付回調-失敗鏈接
Route::get("/StripePayment", "Api\PaymentController@StripePayment"); // / Stripe支付
Route::get("/StripeCallbackSuccess", "Api\PaymentController@StripeCallbackSuccess"); // Stripe支付回調-成功鏈接
Route::get("/StripeCallbackError", "Api\PaymentController@StripeCallbackError"); // Stripe支付回調-失敗鏈接
Route::get("/WonderPayment", "Api\PaymentController@WonderPayment"); // / Wonder支付
Route::get("/WonderCallbackSuccess", "Api\PaymentController@WonderCallbackSuccess"); // Wonder支付回調-成功鏈接
Route::get("/WonderCallbackError", "Api\PaymentController@WonderCallbackError"); // Wonder支付回調-失敗鏈接
Route::post("/WonderPaymentWebhook", "Api\PaymentController@WonderPaymentWebhook"); // Wonder支付Webhook
Route::post("/PaymentOrder", "Api\PaymentController@PaymentOrder")->middleware(['checkLogin', 'prevent.duplicate:5']); // 订单去支付
/*
 *充值相關接口
 */
Route::post("/PaymentRecharge", "Api\PaymentRechargeController@PaymentRecharge")->middleware(['checkLogin', 'prevent.duplicate:5']); // 賬戶充值
Route::get("/PaypalRechargeSuccess", "Api\PaymentRechargeController@PaypalRechargeSuccess"); // paypal賬戶充值回調-成功鏈接
Route::get("/PaypalRechargeError", "Api\PaymentRechargeController@PaypalRechargeError"); // paypal賬戶充值回調-失敗鏈接
Route::get("/StripeRecharge", "Api\PaymentRechargeController@StripeRecharge"); // / Stripe賬戶充值
Route::get("/StripeRechargeSuccess", "Api\PaymentRechargeController@StripeRechargeSuccess"); // Stripe賬戶充值回調-成功鏈接
Route::get("/StripeRechargeError", "Api\PaymentRechargeController@StripeRechargeError"); // Stripe賬戶充值回調-失敗鏈接
Route::match(['get', 'post'], "/WonderRecharge", "Api\PaymentRechargeController@WonderRecharge")->middleware(['checkLogin', 'prevent.duplicate:5']); // / Wonder賬戶充值
Route::get("/WonderRechargeSuccess", "Api\PaymentRechargeController@WonderRechargeSuccess"); // Wonder賬戶充值回調-成功鏈接
Route::get("/WonderRechargeError", "Api\PaymentRechargeController@WonderRechargeError"); // Wonder賬戶充值回調-失敗鏈接
Route::post("/WonderRechargeWebhook", "Api\PaymentRechargeController@WonderRechargeWebhook"); // Wonder賬戶充值Webhook
/*
 * 餘額 / 維護公告 → 改為前端短輪询（APP 每 5 秒 GET 一次）
 * 移除 SSE stream 長連接：PHP-FPM worker 會被鎖死 25 秒，
 * 併發 50 時即全部阻塞，充值回調 / 後台請求會排隊超時。
 *
 * APP 端短轮询接口：
 *   餘額：POST /api/getUsersInfo        （已存在，取 user_balance 字段）
 *   維護：GET  /api/maintenance/status   （下方保留，返回 maintenance_enabled + notice_content）
 */
// Route::get("/balance/stream", "Api\BalanceSseController@stream")->middleware(['checkLogin', 'throttle:120,1']);
Route::get("/maintenance/status", "Api\MaintenanceSseController@status");
// Route::get("/maintenance/stream", "Api\MaintenanceSseController@stream")->middleware('throttle:120,1');
/*
 *订单相關接口
 */
Route::post("/getOrderListApi", "Api\OrderController@getOrderListApi")->middleware('checkLogin'); // 獲取订单列表
Route::post("/getOrderDetailsApi", "Api\OrderController@getOrderDetailsApi")->middleware('checkLogin'); // 獲取訂單詳情
Route::post("/ConfirmReceipt", "Api\OrderController@ConfirmReceipt")->middleware(['checkLogin', 'prevent.duplicate:3']); // 确认收貨
Route::post("/deleteOrderApi", "Api\OrderController@deleteOrderApi")->middleware('checkLogin'); // 刪除訂單
Route::post("/CheckOrderExpired", "Api\OrderController@CheckOrderExpired")->middleware("checkLogin"); //檢查并取消用戶超時訂單

/*
 *收藏相關接口
 *卡包分類相關接口
 */
Route::post("/getCardSort", "Api\CardSortController@getCardSort"); // 獲取卡包分類列表
Route::post("/searchCardsByKeyword", "Api\CardSortController@searchCardsByKeyword"); // 關鍵字搜索卡包
Route::post("/getCardBrands", "Api\CardSortController@getCardBrands"); // 獲取全部卡牌品牌（不重複）
Route::post("/getCardTags", "Api\CardSortController@getCardTags"); // 獲取全部卡池標籤（不重複，供篩選卡池）
Route::post("/getCardSortByBrand", "Api\CardSortController@getCardSortByBrand"); // 通過品牌查找卡池

/*
 *卡片相關接口
 */
Route::post("/getCardsBySort", "Api\CardController@getCardsBySort"); // 根據卡包ID獲取卡片列表
Route::post("/confirmDrawOrder", "Api\CardController@confirmDrawOrder")->middleware(['checkLogin', 'prevent.duplicate:3']); // 抽卡並創建訂單（合併接口，僅錢包支付）
Route::post("/getDrawTaskStatus", "Api\CardController@getDrawTaskStatus")->middleware('checkLogin'); // 查詢異步建單任務狀態


/*
 *卡包（用戶卡包）相關接口
 */
Route::post("/getUserCardPack", "Api\CardOrderDetailsController@getUserBackpack")->middleware('checkLogin'); // 獲取用戶背包（舊名，向下相容）
Route::post("/getUserBackpack", "Api\CardOrderDetailsController@getUserBackpack")->middleware('checkLogin'); // 獲取用戶背包（訂單詳情列表）
Route::post("/handleCardPack", "Api\CardOrderDetailsController@handleCardPack")->middleware(['checkLogin', 'prevent.duplicate:3']); // 處理卡包卡片（兌換/發貨）

/*
 *卡包收藏相關接口
 */
Route::post("/getCardCollect", "Api\CardCollectController@getCardCollect")->middleware('checkLogin'); // 獲取卡包收藏列表
Route::post("/CollectCard", "Api\CardCollectController@CollectCard")->middleware('checkLogin'); // 收藏/取消收藏卡包

/*
 *兌換設置接口
 */
Route::post("getExchangeSettings", 'Api\ExchangeSettingController@getExchangeSettings'); //獲取兌換方案

/*
 *評論相關接口
 */
Route::post("/PublishComment", "Api\OrderCommentController@PublishComment")->middleware(['checkLogin', 'prevent.duplicate:3']); // 發佈評論
Route::post("/getProductComment", "Api\OrderCommentController@getProductComment")->middleware('checkLogin'); // 獲取商品评论
Route::post("/getMyComment", "Api\OrderCommentController@getMyComment")->middleware('checkLogin'); // 獲取我的所有评论

/*
 *優惠券相關接口
 */
Route::post("/getAllCoupons", "Api\CouponController@getAllCoupons"); // 獲取優惠券列表
Route::post("/exchangeCoupon", "Api\CouponController@exchangeCoupon")->middleware(['checkLogin', 'prevent.duplicate:3']); // 兌換優惠券
Route::post("/getUserHaveCoupon", "Api\CouponController@getUserHaveCoupon")->middleware('checkLogin'); // 獲取用戶已有優惠券列表
Route::post("/getUsercheckCoupon", "Api\CouponController@getUsercheckCoupon")->middleware('checkLogin'); // 獲取用戶已有且可用優惠券列表

/*
 *用戶反饋相關接口
 */
Route::post("/addUserMessage", "Api\UserMessageController@addUserMessage")->middleware('checkLogin'); // 添加用戶反饋

/*
 *排队项目相關接口
 */
Route::post("/getReservationProject", "Api\ReservationProjectController@getReservationProject")->middleware('checkLogin'); // 獲取排队项目列表
Route::post("/addUserReservation", "Api\UserReservationController@addUserReservation")->middleware(['checkLogin', 'prevent.duplicate:3']); // 取票
Route::post("/getUserReservation", "Api\UserReservationController@getUserReservation")->middleware('checkLogin'); // 獲取用戶項目票據列表
Route::post("/CheckReservation", "Api\UserReservationController@CheckReservation")->middleware('checkLogin'); // 签到
Route::post("/CancelReservation", "Api\UserReservationController@CancelReservation")->middleware('checkLogin'); // 取消排队

/*
 *签到
 */
Route::post("/userCheckIn", "Api\UserController@userCheckIn")->middleware(['checkLogin', 'prevent.duplicate:5']); // 签到

/*
 *分享
 */
Route::get('/share/{gid?}', function ($gid = 0) {
    $gid = $gid; //goods_id
    $app_logo = env('user_avatar'); //app的logo
    $app_name = env('APP_NAME').'share://'; //app的名稱
    $app_logo = env('user_avatar'); //app的logo
    $google_download = env('google_download'); //app谷歌下载地址
    $apple_download = env('apple_download'); //app苹果下载地址
    return view('share')->with('app_logo', $app_logo)->with('app_name', $app_name)->with('gid', $gid)->with('google_download', $google_download)->with('apple_download', $apple_download);
});
