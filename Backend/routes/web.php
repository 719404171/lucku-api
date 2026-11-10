<?php
/*
 * File: web.php
 * Project: NewDemo
 * Created Time: 2026-06-04 04:11:12 pm
 * Author: WangYuQiang && WangpeiGang (719404171@qq.com)
 * -----
 * Last Modified: 2026-09-10 4:28:56 pm
 * Modified By: WangYuQiang (719404171@qq.com>)
 * -----
 * Copyright (c) 2023 - 2026 成都睿創科技有限公司
 */


Route::post("AdminLoginWeb", "Admin\AdminsController@AdminLoginWeb");   //登錄
Route::post("getShopFoundationWeb", "Admin\ShopSettingController@getShopFoundationWeb");   //获取店铺基础信息
Route::post("uploadImageWeb", "Admin\AdminsController@uploadImageWeb");   //上傳圖片


Route::group(['middleware'=>'RefreshToken'],function (){    //检查是否登录
    Route::get("getAdminMenuWeb", "Admin\AdminsController@getAdminMenuWeb");//获取管理员权限及菜单
    Route::get("getAdminInfoWeb", "Admin\AdminsController@getAdminInfoWeb");//获取管理员信息
    Route::post("getAllMenuWeb", "Admin\MenusController@getAllMenuWeb");//获取所有菜单和權限
    Route::post("getHomeDataWeb", "Admin\AdminsController@getHomeDataWeb");//获取首頁數據
    /***************************************** 角色管理 **************************************************************/
    Route::post("getRoleListWeb", "Admin\RoleController@getRoleListWeb");//获取角色列表
    Route::post("getRolePowerWeb", "Admin\RoleController@getRolePowerWeb");//获取角色權限
    Route::post("addRolePower", "Admin\RoleController@addRolePower");//新增角色權限
    Route::post("editRolePower", "Admin\RoleController@editRolePower");//编辑角色權限
    Route::post("deleteRolePower", "Admin\RoleController@deleteRolePower");//删除角色
    /***************************************** 管理員管理 **************************************************************/
    Route::post("getAdminListWeb", "Admin\AdminsController@getAdminListWeb");//获取管理員列表
    Route::post("getAllRoleWeb", "Admin\RoleController@getAllRoleWeb");//获取所有角色
    Route::post("addAdminWeb", "Admin\AdminsController@addAdminWeb");//新增管理員
    Route::post("editAdminWeb", "Admin\AdminsController@editAdminWeb");//編輯管理員
    Route::post("editAdminPasswordWeb", "Admin\AdminsController@editAdminPasswordWeb");//修改當前管理員密碼
    Route::post("deleteAdminWeb", "Admin\AdminsController@deleteAdminWeb");//刪除管理員
    /***************************************** 規格分類管理 **************************************************************/
    Route::post("getSpecsSortListWeb", "Admin\SpecsController@getSpecsSortListWeb");//獲取規格分類列表
    Route::post("addSpecsSortWeb", "Admin\SpecsController@addSpecsSortWeb");//新增規格分類
    Route::post("editSpecsSortWeb", "Admin\SpecsController@editSpecsSortWeb");//編輯規格分類
    Route::post("deleteSpecsSortWeb", "Admin\SpecsController@deleteSpecsSortWeb");//删除規格分類
    /***************************************** 產品管理 **************************************************************/
    Route::post("getAllProductSortWeb", "Admin\ProductSortController@getAllProductSortWeb");//獲取所有分類列表
    Route::post("getProductListWeb", "Admin\ProductController@getProductListWeb");//獲取產品列表
    Route::post("addProductWeb", "Admin\ProductController@addProductWeb");//添加產品
    Route::post("editProductWeb", "Admin\ProductController@editProductWeb");//編輯產品
    Route::post("deleteProductWeb", "Admin\ProductController@deleteProductWeb");//刪除產品
    Route::post("getProductSpecsWeb", "Admin\ProductController@getProductSpecsWeb");//獲取產品規格
    Route::post("getAllSpecsSortWeb", "Admin\SpecsController@getAllSpecsSortWeb");//獲取所有規格分類
    Route::post("addProductSpecsKeyWeb", "Admin\ProductController@addProductSpecsKeyWeb");//添加產品規格組合
    Route::post("editProductSpecsWeb", "Admin\ProductController@editProductSpecsWeb");//編輯產品規格
    /***************************************** 分類管理 **************************************************************/
    Route::post("getProductSortListWeb", "Admin\ProductSortController@getProductSortListWeb");//獲取分類列表
    Route::post("handleProductSortListWeb", "Admin\ProductSortController@handleProductSortListWeb");//添加/編輯產品分類
    Route::post("deleteProductSortListWeb", "Admin\ProductSortController@deleteProductSortListWeb");//刪除產品分類
    /***************************************** 庫銷記錄管理 **************************************************************/
    Route::post("getWastageRecordListWeb", "Admin\PurchaseRecordController@getWastageRecordListWeb");//獲取損耗記錄
    Route::post("getAllProductWeb", "Admin\ProductController@getAllProductWeb");//獲取所有產品及規格
    Route::post("addWastageRecordListWeb", "Admin\PurchaseRecordController@addWastageRecordListWeb");//添加損耗記錄
    Route::post("editWastageRecordListWeb", "Admin\PurchaseRecordController@editWastageRecordListWeb");//编辑損耗記錄
    Route::post("deleteWastageRecordListWeb", "Admin\PurchaseRecordController@deleteWastageRecordListWeb");//删除損耗記錄
    Route::post("getPurchaseRecordListWeb", "Admin\PurchaseRecordController@getPurchaseRecordListWeb");//获取采购记录
    Route::post("handlePurchaseRecordListWeb", "Admin\PurchaseRecordController@handlePurchaseRecordListWeb");//添加/编辑采购记录
    Route::post("deletePurchaseRecordListWeb", "Admin\PurchaseRecordController@deletePurchaseRecordListWeb");//删除采购记录
    Route::post("purchaseRecordEmailWeb", "Admin\PurchaseRecordController@purchaseRecordEmailWeb");//采购邮箱发送
    Route::post("purchaseWarehouseWeb", "Admin\PurchaseRecordController@purchaseWarehouseWeb");//采购進倉/退倉
    /***************************************** 文件管理 **************************************************************/
    Route::post("getFileListWeb", "Admin\FileController@getFileListWeb");//獲取文件列表
    Route::post("getFileSortListWeb", "Admin\FileController@getFileSortListWeb");//獲取分类列表
    Route::post("handleFileSortWeb", "Admin\FileController@handleFileSortWeb");//新增/編輯文件分類
    Route::post("deleteFileSortWeb", "Admin\FileController@deleteFileSortWeb");//刪除文件分類
    Route::post("addFileWeb", "Admin\FileController@addFileWeb");//新增文件
    Route::post("deleteFileWeb", "Admin\FileController@deleteFileWeb");//刪除文件
    /***************************************** 優惠券管理 **************************************************************/
    Route::post("getCouponListWeb", "Admin\CouponController@getCouponListWeb");//獲取優惠券列表
    Route::post("handleCouponListWeb", "Admin\CouponController@handleCouponListWeb");//編輯/添加優惠券列表
    Route::post("deleteCouponWeb", "Admin\CouponController@deleteCouponWeb");//刪除優惠券
    /***************************************** 等級管理 **************************************************************/
    Route::post("getMemberListWeb", "Admin\UserMemberController@getMemberListWeb");//獲取會員等級列表
    Route::post("addMemberListWeb", "Admin\UserMemberController@addMemberListWeb");//增加會員等級
    Route::post("editMemberListWeb", "Admin\UserMemberController@editMemberListWeb");//修改會員等級
    Route::post("deleteMemberListWeb", "Admin\UserMemberController@deleteMemberListWeb");//删除會員等級
    /***************************************** 免责条款管理 **************************************************************/
    Route::post("getClauseWeb", "Admin\ClauseController@getClauseWeb");//獲取免责条款
    Route::post("editClauseWeb", "Admin\ClauseController@editClauseWeb");//修改免责条款
    /***************************************** 隱私協議管理 **************************************************************/
    Route::post("getPrivacyWeb", "Admin\PrivacyController@getPrivacyWeb");//獲取隱私協議
    Route::post("editPrivacyWeb", "Admin\PrivacyController@editPrivacyWeb");//修改隱私協議
    /***************************************** 用户协议/隐私政策管理 **************************************************************/
    Route::post("getUserAgreementWeb", "Admin\UserAgreementController@getUserAgreementWeb");//獲取用户协议
    Route::post("editUserAgreementWeb", "Admin\UserAgreementController@editUserAgreementWeb");//修改用户协议
    Route::post("getPrivacyPolicyWeb", "Admin\PrivacyPolicyController@getPrivacyPolicyWeb");//獲取隐私政策
    Route::post("editPrivacyPolicyWeb", "Admin\PrivacyPolicyController@editPrivacyPolicyWeb");//修改隐私政策
    /***************************************** Banner管理 **************************************************************/
    Route::post("getBannerListWeb", "Admin\BannerController@getBannerListWeb");//獲取Banner列表
    Route::post("handleBannerListWeb", "Admin\BannerController@handleBannerListWeb");//添加/編輯Banner
    Route::post("deleteBannerListWeb", "Admin\BannerController@deleteBannerListWeb");//刪除Banner
    /***************************************** Banner弹窗管理 **********************************************************/
    Route::post("getBannerPopupWeb", "Admin\BannerPopupController@getBannerPopupWeb");//獲取Banner彈窗配置
    Route::post("handleBannerPopupWeb", "Admin\BannerPopupController@handleBannerPopupWeb");//保存Banner彈窗配置
    /***************************************** 常見問題管理 **************************************************************/
    Route::post("getFaqListWeb", "Admin\FaqController@getFaqListWeb");//獲取常見問題列表
    Route::post("handleFaqWeb", "Admin\FaqController@handleFaqWeb");//添加/編輯常見問題
    Route::post("deleteFaqWeb", "Admin\FaqController@deleteFaqWeb");//刪除常見問題
    /***************************************** ChatGPT **************************************************************/
    Route::post("sendingBaiDuGPTWeb", "Admin\ChatGPTController@sendingBaiDuGPTWeb");//發送文言一心ai請求
    /***************************************** 店鋪設置 **************************************************************/
    Route::post("getShopSettingWeb", "Admin\ShopSettingController@getShopSettingWeb");//獲取店鋪信息
    Route::post("editShopSettingWeb", "Admin\ShopSettingController@editShopSettingWeb");//編輯店鋪信息
    /***************************************** 維護公告 **************************************************************/
    Route::post("getMaintenanceNoticeWeb", "Admin\MaintenanceNoticeController@getMaintenanceNoticeWeb");//獲取維護公告
    Route::post("editMaintenanceNoticeWeb", "Admin\MaintenanceNoticeController@editMaintenanceNoticeWeb");//編輯維護公告
    /***************************************** 客服設置 **************************************************************/
    Route::post("getShopServiceListWeb", "Admin\ShopServiceController@getShopServiceListWeb");//獲取客服列表
    Route::post("handleShopServiceWeb", "Admin\ShopServiceController@handleShopServiceWeb");//添加/編輯客服信息
    Route::post("deleteShopServiceWeb", "Admin\ShopServiceController@deleteShopServiceWeb");//刪除客服信息
    /***************************************** 系統推送 **************************************************************/
    Route::post("pushMessageWeb", "Admin\PushController@pushMessageWeb");//推送消息
    Route::post("getPushMessageListWeb", "Admin\PushController@getPushMessageListWeb");//獲取推送消息列表
    Route::post("deletePushMessageWeb", "Admin\PushController@deletePushMessageWeb");//刪除推送消息
    Route::post("getAllUsersWeb", "Admin\UserController@getAllUsersWeb");//獲取所有用戶
    /***************************************** 推送設置 **************************************************************/
    Route::post("getShopGeTuiWeb", "Admin\ShopGeTuiController@getShopGeTuiWeb");//獲取推送配置
    Route::post("handleShopGeTuiWeb", "Admin\ShopGeTuiController@handleShopGeTuiWeb");//添加/編輯推送配置
    /***************************************** PayPal设置 **************************************************************/
    Route::post("getShopPayPalListWeb", "Admin\ShopPaypalController@getShopPayPalListWeb");//獲取PayPal列表
    Route::post("handleShopPayPalWeb", "Admin\ShopPaypalController@handleShopPayPalWeb");//添加/編輯PayPal信息
    Route::post("deleteShopPayPalWeb", "Admin\ShopPaypalController@deleteShopPayPalWeb");//刪除PayPal信息
    /***************************************** Stripe设置 **************************************************************/
    Route::post("getShopStripeListWeb", "Admin\ShopStripeController@getShopStripeListWeb");//獲取Stripe列表
    Route::post("handleShopStripeWeb", "Admin\ShopStripeController@handleShopStripeWeb");//添加/編輯Stripe信息
    Route::post("deleteShopStripeWeb", "Admin\ShopStripeController@deleteShopStripeWeb");//刪除Stripe信息
    /***************************************** 留言管理 **************************************************************/
    Route::post("getMessageListWeb", "Admin\UserMessageController@getMessageListWeb");//获取留言列表
    Route::post("deleteMessageWeb", "Admin\UserMessageController@deleteMessageWeb");//刪除留言信息
    /***************************************** 日志管理 **************************************************************/
    Route::post("getActivityLogListWeb", "Admin\ActivityLogController@getActivityLogListWeb");//獲取員工操作日誌
    Route::post("deleteActivityLogWeb", "Admin\ActivityLogController@deleteActivityLogWeb");//刪除員工操作日誌
    Route::post("getAdminLogListWeb", "Admin\AdminLogController@getAdminLogListWeb");//獲取管理員登錄日誌
    Route::post("getOrderActivityLogListWeb","Admin\ActivityLogController@getOrderActivityLogListWeb");//獲取員工訂單操作日誌
    /***************************************** 分析系统 **************************************************************/
    Route::post("getSalesDirectionWeb", "Admin\OrderController@getSalesDirectionWeb");//獲取銷售方向分析
    Route::post("getUserAnalysisWeb", "Admin\UserController@getUserAnalysisWeb");//獲取用戶消費分析
    Route::post("getOrderAnalysisWeb", "Admin\OrderController@getOrderAnalysisWeb");//獲取訂單分析
    /***************************************** 用户管理 **************************************************************/
    Route::post("getUserListWeb", "Admin\UserController@getUserListWeb");//獲取用戶列表
    Route::post("addUserWeb", "Admin\UserController@addUserWeb");//新增用戶
    Route::post("editUserWeb", "Admin\UserController@editUserWeb");//編輯用戶
    Route::post("editUserPasswordWeb", "Admin\UserController@editUserPasswordWeb");//更改用戶密碼
    Route::post("deleteUserWeb", "Admin\UserController@deleteUserWeb");//刪除用戶
    Route::post("verifyUserEmailWeb", "Admin\UserController@verifyUserEmailWeb");//單個：通過用戶郵箱驗證
    Route::post("verifyAllUserEmailWeb", "Admin\UserController@verifyAllUserEmailWeb");//批量：通過所有未驗證用戶郵箱
    /***************************************** 郵件發送日誌 **************************************************************/
    Route::post("getEmailSendLogListWeb", "Admin\EmailLogController@getEmailSendLogListWeb");//郵件發送日誌列表
    /***************************************** 每日登錄獎勵 **************************************************************/
    Route::post("getDailyBonusLogListWeb", "Admin\DailyBonusLogController@getDailyBonusLogListWeb");//每日登錄獎勵日誌列表
    /***************************************** 订单管理 **************************************************************/
    Route::post("getOrderListWeb", "Admin\OrderController@getOrderListWeb");//獲取訂單列表
    Route::post("addOrderLogisticsWeb", "Admin\OrderController@addOrderLogisticsWeb");//添加訂單物流
    Route::post("editOrderTimeWeb", "Admin\OrderController@editOrderTimeWeb");//修改预约时间
    Route::post("confirmReceiptWeb", "Admin\OrderController@confirmReceiptWeb");//确认完成/確認收貨
    Route::post("deleteOrderWeb", "Admin\OrderController@deleteOrderWeb");//刪除訂單
    Route::post("confirmPaymentWeb", "Admin\OrderController@confirmPaymentWeb");//确认支付
    Route::post("scanConfirmOrderWeb", "Admin\OrderController@scanConfirmOrderWeb");//掃描確認訂單
    Route::post("getCalendarOrdersWeb", "Admin\OrderController@getCalendarOrdersWeb");//獲取日曆訂單
    Route::post("getAllSortProductWeb", "Admin\ProductSortController@getAllSortProductWeb");//獲取所有分類商品
    Route::post("addOfflineOrdersWeb", "Admin\OrderController@addOfflineOrdersWeb");//添加線下訂單
    Route::post("WebAddOrder", "Admin\OrderController@WebAddOrder");//web創建訂單
    Route::post("editPaymentTime", "Admin\OrderController@editPaymentTime");//web修改訂單支付时间
//    Route::post("getGoodsSaleWeb", "Admin\OrderController@getGoodsSaleWeb");//獲取訂單銷售額
    /***************************************** 評論管理 **************************************************************/
    Route::post("getOrderCommentListWeb", "Admin\OrderController@getOrderCommentListWeb");//獲取評論列表
    Route::post("deleteOrderCommentWeb", "Admin\OrderController@deleteOrderCommentWeb");//刪除評論
    /***************************************** 预约排号管理 **************************************************************/
    Route::post("getReservationProjectWeb", "Admin\ReservationProjectController@getReservationProjectWeb");//獲取預約項目列表
    Route::post("handleReservationProjectWeb", "Admin\ReservationProjectController@handleReservationProjectWeb");//添加/編輯預約項目
    Route::post("deleteReservationProjectWeb", "Admin\ReservationProjectController@deleteReservationProjectWeb");//刪除預約項目
    Route::post("getUserReservationWeb", "Admin\UserReservationController@getUserReservationWeb");//獲取用戶排號列表
    Route::post("editUserReservationWeb", "Admin\UserReservationController@editUserReservationWeb");//修改用戶排號狀態
    Route::post("deleteUserReservationWeb", "Admin\UserReservationController@deleteUserReservationWeb");//刪除用戶排號記錄
    /***************************************** 咨询管理 **************************************************************/
    Route::post("getNewsListWeb", "Admin\NewsController@getNewsListWeb");//获取咨询列表
    Route::post("handleNewsWeb", "Admin\NewsController@handleNewsWeb");//添加/編輯咨询
    Route::post("deleteNewsWeb", "Admin\NewsController@deleteNewsWeb");//刪除咨询

    Route::post("getAddressAreaWeb", "Admin\AddressAreaController@getAddressAreaWeb"); // 獲取区域选择列表
    /***************************************** 兌換設置管理 **************************************************************/
    Route::post("getExchangeListWeb", "Admin\ExchangeSettingController@getExchangeListWeb");//獲取兌換設置列表
    Route::post("handleExchangeWeb", "Admin\ExchangeSettingController@handleExchangeWeb");//添加/編輯兌換設置
    Route::post("deleteExchangeWeb", "Admin\ExchangeSettingController@deleteExchangeWeb");//刪除兌換設置
    /***************************************** 抽卡预设管理 **************************************************************/
    Route::post("getDrawPresetListWeb", "Admin\DrawPresetController@getDrawPresetListWeb");//獲取预设列表
    Route::post("getPresetCardsWeb", "Admin\DrawPresetController@getPresetCardsWeb");//獲取卡包卡片列表
    Route::post("saveDrawPresetWeb", "Admin\DrawPresetController@saveDrawPresetWeb");//保存预设排序
    Route::post("shuffleDrawPresetWeb", "Admin\DrawPresetController@shuffleDrawPresetWeb");//随机打乱
    Route::post("autoCreatePresetsWeb", "Admin\DrawPresetController@autoCreatePresetsWeb");//自动创建预设
    Route::post("deleteDrawPresetWeb", "Admin\DrawPresetController@deleteDrawPresetWeb");//删除预设
    Route::post("unlockDrawPresetWeb", "Admin\DrawPresetController@unlockDrawPresetWeb");//解锁预设
    /***************************************** 卡包分类管理 **************************************************************/
    Route::post("getCardSortListWeb", "Admin\CardSortController@getCardSortListWeb");//獲取分類列表
    Route::post("getAllCardBrandsWeb", "Admin\CardSortController@getAllCardBrandsWeb");//獲取全部已維護的卡池品牌
    Route::post("handleCardSortListWeb", "Admin\CardSortController@handleCardSortListWeb");//添加/編輯分類
    Route::post("deleteCardSortListWeb", "Admin\CardSortController@deleteCardSortListWeb");//刪除分類
    Route::post("editCardSortDrawEnableWeb", "Admin\CardSortController@editCardSortDrawEnableWeb");//單個卡池抽卡開關
    Route::post("editCardSortOnceOnlyWeb", "Admin\CardSortController@editCardSortOnceOnlyWeb");//單個卡池優惠卡包開關（每帳號僅抽一次）
    Route::post("batchCardSortDrawEnableWeb", "Admin\CardSortController@batchCardSortDrawEnableWeb");//一鍵開關所有卡池抽卡
    Route::post("getAllCardSortWeb", "Admin\CardSortController@getAllCardSortWeb");//獲取所有分類列表
    Route::post("getAllSortProductWeb", "Admin\CardSortController@getAllSortProductWeb");//獲取分類卡片
    Route::post("getCardsBySortWeb", "Admin\CardSortController@getCardsBySortWeb");//獲取卡池關聯/未關聯卡片
    Route::post("associateCardsWeb", "Admin\CardSortController@associateCardsWeb");//批量關聯/取消關聯卡片
    Route::post("updateCardTagWeb", "Admin\CardSortController@updateCardTagWeb");//更新卡片標籤分組
    Route::post("updateCardTagBatchWeb", "Admin\CardSortController@updateCardTagBatchWeb");//批量更新卡片標籤分組
    Route::post("saveCardTagOrderWeb", "Admin\CardSortController@saveCardTagOrderWeb");//保存標籤順序
    Route::post("saveCardTagAnimationWeb", "Admin\CardSortController@saveCardTagAnimationWeb");//保存標籤中獎動畫
    Route::post("updateCardPriorityWeb", "Admin\CardSortController@updateCardPriorityWeb");//更新卡片是否展示
    Route::post("updateCardSortDrawPriceWeb", "Admin\CardSortController@updateCardSortDrawPriceWeb");//保存卡池抽卡價格公式設置（百分比/自動更新開關）並重算
    Route::post("initCardSortWeb", "Admin\CardSortController@initCardSortWeb");//卡池初始化（複製新池+預設，原池軟刪除）
    /***************************************** 卡片管理 **************************************************************/
    Route::post("getCardListWeb", "Admin\CardController@getCardListWeb");//獲取卡片列表
    Route::post("addCardWeb", "Admin\CardController@addCardWeb");//添加卡片
    Route::post("editCardWeb", "Admin\CardController@editCardWeb");//編輯卡片
    Route::post("deleteCardWeb", "Admin\CardController@deleteCardWeb");//刪除卡片
    Route::post("getAllCardWeb", "Admin\CardController@getAllCardWeb");//獲取所有卡片
    Route::post("getCardDrawerInfo", "Api\CardController@getCardDrawerInfo");//獲取卡片抽取人信息
    Route::post("getSpiderCardListWeb", "Admin\SpiderCardController@getSpiderCardListWeb");//獲取爬蟲卡片列表
    Route::post("addSpiderCardWeb", "Admin\SpiderCardController@addSpiderCardWeb");//新增卡牌
    Route::post("editSpiderCardWeb", "Admin\SpiderCardController@editSpiderCardWeb");//編輯卡牌
    Route::post("deleteSpiderCardWeb", "Admin\SpiderCardController@deleteSpiderCardWeb");//刪除卡牌
    /***************************************** 邀請碼管理 **************************************************************/
    Route::post("getInvitationCodeListWeb", "Admin\InvitationCodeController@getListWeb");//獲取邀請碼列表
    Route::post("addInvitationCodeWeb", "Admin\InvitationCodeController@addWeb");//新增邀請碼
    Route::post("editInvitationCodeWeb", "Admin\InvitationCodeController@editWeb");//編輯邀請碼
    Route::post("batchGenerateInvitationCodeWeb", "Admin\InvitationCodeController@batchGenerateWeb");//批量生成邀請碼
    Route::post("deleteInvitationCodeWeb", "Admin\InvitationCodeController@deleteWeb");//刪除邀請碼
    Route::post("getInvitationCodeUsageLogListWeb", "Admin\InvitationCodeController@getUsageLogListWeb");//邀請碼使用記錄
    Route::post("getDiamondExchangeLogListWeb", "Admin\DiamondExchangeLogController@getListWeb");//金幣兌換記錄
    /***************************************** 記錄查詢 **************************************************************/
    Route::post("getRechargeLogListWeb", "Admin\PaymentRechargeLogController@getRechargeLogListWeb");//獲取用戶充值記錄
    Route::post("getDrawTaskListWeb", "Admin\CardDrawRecordController@getDrawTaskListWeb");//獲取抽卡批次記錄
    Route::post("getDrawDetailListWeb", "Admin\CardDrawRecordController@getDrawDetailListWeb");//獲取抽卡明細記錄
    Route::post("getMultiDrawCheckListWeb", "Admin\CardDrawRecordController@getMultiDrawCheckListWeb");//卡牌多用戶抽取檢測列表
    Route::post("getMultiDrawCheckDetailWeb", "Admin\CardDrawRecordController@getMultiDrawCheckDetailWeb");//卡牌多用戶抽取檢測詳情
    Route::post("getDrawAuditListWeb", "Admin\AuditController@getDrawAuditListWeb");//查賬-抽卡對賬
    Route::post("getDrawBillWeb", "Admin\AuditController@getDrawBillWeb");//查賬-單批次對賬單（付款/發卡/資金追溯）
    Route::post("banUserWeb", "Admin\AuditController@banUserWeb");//對賬-臨時封禁賬號
    Route::post("unbanUserWeb", "Admin\AuditController@unbanUserWeb");//對賬-解除封禁

});
Route::get("generateInvoiceWeb", "Admin\OrderController@generateInvoiceWeb");//发票/收據下载

// ====== 卡牌订单 ======
Route::post("getCardOrderListWeb", "Admin\CardOrderController@getCardOrderListWeb");//卡牌订单列表
Route::post("deleteCardOrderWeb", "Admin\CardOrderController@deleteCardOrderWeb");//删除卡牌订单
Route::post("addCardOrderLogisticsWeb", "Admin\CardOrderController@addCardOrderLogisticsWeb");//卡牌订单物流
Route::post("editCardOrderTimeWeb", "Admin\CardOrderController@editCardOrderTimeWeb");//卡牌订单改预约时间
Route::post("confirmReceiptCardOrderWeb", "Admin\CardOrderController@confirmReceiptWeb");//确认收货
Route::post("confirmPaymentCardOrderWeb", "Admin\CardOrderController@confirmPaymentWeb");//确认支付
Route::post("scanConfirmCardOrderWeb", "Admin\CardOrderController@scanConfirmCardOrderWeb");//扫码核销
Route::post("getExchangedCardsWeb", "Admin\CardOrderController@getExchangedCardsWeb");//已兑换卡片列表
Route::post("restoreExchangedCardsWeb", "Admin\CardOrderController@restoreExchangedCardsWeb");//恢复已兑换卡片（扣点）

//首页
Route::get('/', function () {
    $APP_NAME = env('APP_NAME'); //项目名称
    $APP_URL = env('APP_URL'); //项目地址
    $APP_LOGO = env('APP_LOGO'); //项目logo
    $DB_USERNAME = env('DB_USERNAME'); //数据库名称
    return view('index')->with('APP_NAME', $APP_NAME)->with('APP_URL', $APP_URL)->with('APP_LOGO', $APP_LOGO)->with('DB_USERNAME', $DB_USERNAME);
});


