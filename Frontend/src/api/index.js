import request from '@/utils/request'

/* 公共相关接口 */

export const reqUpload = data => request({ url: '/uploadImageWeb', method: 'post', data })

export const reqLogo = data => request({ url: '/getShopFoundationWeb', method: 'post', data })

/** 首页数据 */

export const getHomeData = data => request({ url: '/getHomeDataWeb', method: 'post', data })

/** 百度ai */

export const ask = data => request({ url: '/sendingBaiDuGPTWeb', method: 'post', data })

/** 文件管理相关接口 */

export const editFileCate = data => request({ url: '/handleFileSortWeb', method: 'post', data })

export const delFileCate = data => request({ url: '/deleteFileSortWeb', method: 'post', data })

export const addFile = data => request({ url: '/addFileWeb', method: 'post', data })

export const delFile = data => request({ url: '/deleteFileWeb', method: 'post', data })

export const getFileCate = data => request({ url: '/getFileSortListWeb', method: 'post', data })

const reqFile = data => request({ url: '/getFileListWeb', method: 'post', data })

/* 角色权限相关接口 */

export const getAdminMenus = () => request({ url: '/getAdminMenuWeb', method: 'get' })

export const getAllMenus = () => request({ url: '/getAllMenuWeb', method: 'post' })

export const getPower = data => request({ url: '/getRolePowerWeb', method: 'post', data })

export const addRole = data => request({ url: '/addRolePower', method: 'post', data })

export const editRole = data => request({ url: '/editRolePower', method: 'post', data })

export const delRole = data => request({ url: '/deleteRolePower', method: 'post', data })

const reqRole = data => request({ url: '/getRoleListWeb', method: 'post', data })

/** 管理员相关接口 */

export const getAllRole = () => request({ url: '/getAllRoleWeb', method: 'post' })

export const addAdmin = data => request({ url: '/addAdminWeb', method: 'post', data })

export const editAdmin = data => request({ url: '/editAdminWeb', method: 'post', data })

export const delAdmin = data => request({ url: '/deleteAdminWeb', method: 'post', data })

export const editAdminPassword = data => request({ url: '/editAdminPasswordWeb', method: 'post', data })

const reqAdmin = data => request({ url: '/getAdminListWeb', method: 'post', data })

/** 规格分类相关接口 */

export const addSpecs = data => request({ url: '/addSpecsSortWeb', method: 'post', data })

export const editSpecs = data => request({ url: '/editSpecsSortWeb', method: 'post', data })

export const delSpecs = data => request({ url: '/deleteSpecsSortWeb', method: 'post', data })

const reqSpecs = data => request({ url: '/getSpecsSortListWeb', method: 'post', data })

/** 产品分类相关接口 */

export const addGoodsCate = data => request({ url: '/handleProductSortListWeb', method: 'post', data })

export const delGoodsCate = data => request({ url: '/deleteProductSortListWeb', method: 'post', data })

const reqGoodsCate = data => request({ url: '/getProductSortListWeb', method: 'post', data })

/** 产品相关接口 */

export const getGoodsCate = () => request({ url: '/getAllProductSortWeb', method: 'post' })

export const getGoodsSpace = () => request({ url: '/getAllSpecsSortWeb', method: 'post' })

export const addGoods = data => request({ url: '/addProductWeb', method: 'post', data })

export const editGoods = data => request({ url: '/editProductWeb', method: 'post', data })

export const delGoods = data => request({ url: '/deleteProductWeb', method: 'post', data })

const reqGoods = data => request({ url: '/getProductListWeb', method: 'post', data })

/** 规格产品相关接口 */

export const addSpecsGoods = data => request({ url: '/addProductSpecsKeyWeb', method: 'post', data })

export const editSpecsGoods = data => request({ url: '/editProductSpecsWeb', method: 'post', data })

export const getSpecsGoods = data => request({ url: '/getProductSpecsWeb', method: 'post', data })

/** 优惠券管理相关 */

export const editCoupon = data => request({ url: '/handleCouponListWeb', method: 'post', data })

export const delCoupon = data => request({ url: '/deleteCouponWeb', method: 'post', data })

const reqCoupon = data => request({ url: '/getCouponListWeb', method: 'post', data })

/** 损耗记录相关 */

export const getSku = () => request({ url: '/getAllProductWeb', method: 'post' })

export const addLoss = data => request({ url: '/addWastageRecordListWeb', method: 'post', data })

export const editLoss = data => request({ url: '/editWastageRecordListWeb', method: 'post', data })

export const delLoss = data => request({ url: '/deleteWastageRecordListWeb', method: 'post', data })

const reqLoss = data => request({ url: '/getWastageRecordListWeb', method: 'post', data })

/** 采购记录相关 */

export const editPurchase = data => request({ url: '/handlePurchaseRecordListWeb', method: 'post', data })

export const delPurchase = data => request({ url: '/deletePurchaseRecordListWeb', method: 'post', data })

export const emailPurchase = data => request({ url: '/purchaseRecordEmailWeb', method: 'post', data })

export const warehousePurchase = data => request({ url: '/purchaseWarehouseWeb', method: 'post', data })

const reqPurchase = data => request({ url: '/getPurchaseRecordListWeb', method: 'post', data })

/** 会员等级相关 */

export const addLevel = data => request({ url: '/addMemberListWeb', method: 'post', data })

export const editLevel = data => request({ url: '/editMemberListWeb', method: 'post', data })

export const delLevel = data => request({ url: '/deleteMemberListWeb', method: 'post', data })

export const reqLevel = data => request({ url: '/getMemberListWeb', method: 'post', data })

/** 协议相关 */

export const getClause = () => request({ url: '/getClauseWeb', method: 'post' })

export const editClause = data => request({ url: '/editClauseWeb', method: 'post', data })

export const getPrivacy = () => request({ url: '/getPrivacyWeb', method: 'post' })

export const editPrivacy = data => request({ url: '/editPrivacyWeb', method: 'post', data })

export const getUserAgreement = () => request({ url: '/getUserAgreementWeb', method: 'post' })

export const editUserAgreement = data => request({ url: '/editUserAgreementWeb', method: 'post', data })

export const getPrivacyPolicy = () => request({ url: '/getPrivacyPolicyWeb', method: 'post' })

export const editPrivacyPolicy = data => request({ url: '/editPrivacyPolicyWeb', method: 'post', data })

/** banner相关 */

export const editBanner = data => request({ url: '/handleBannerListWeb', method: 'post', data })

export const delBanner = data => request({ url: '/deleteBannerListWeb', method: 'post', data })

const reqBanner = data => request({ url: '/getBannerListWeb', method: 'post', data })

/** banner弹窗相关（单例配置） */

const getBannerPopup = data => request({ url: '/getBannerPopupWeb', method: 'post', data })

export const editBannerPopup = data => request({ url: '/handleBannerPopupWeb', method: 'post', data })

/** 常见问题相关 */

export const editFaq = data => request({ url: '/handleFaqWeb', method: 'post', data })

export const delFaq = data => request({ url: '/deleteFaqWeb', method: 'post', data })

const reqFaq = data => request({ url: '/getFaqListWeb', method: 'post', data })

/** 用户列表相关 */

export const addUser = data => request({ url: '/addUserWeb', method: 'post', data })

export const editUser = data => request({ url: '/editUserWeb', method: 'post', data })

export const editUserPsd = data => request({ url: '/editUserPasswordWeb', method: 'post', data })

export const delUser = data => request({ url: '/deleteUserWeb', method: 'post', data })

export const verifyUserEmail = data => request({ url: '/verifyUserEmailWeb', method: 'post', data })

export const verifyAllUserEmail = data => request({ url: '/verifyAllUserEmailWeb', method: 'post', data })

const reqUser = data => request({ url: '/getUserListWeb', method: 'post', data })

/** 店铺设置相关 */

export const getStore = () => request({ url: '/getShopSettingWeb', method: 'post' })

export const editStore = data => request({ url: '/editShopSettingWeb', method: 'post', data })

/** 维护公告相关 */
export const getMaintenanceNotice = () => request({ url: '/getMaintenanceNoticeWeb', method: 'post' })

export const editMaintenanceNotice = data => request({ url: '/editMaintenanceNoticeWeb', method: 'post', data })

/** 客服设置相关 */

export const editService = data => request({ url: '/handleShopServiceWeb', method: 'post', data })

export const delService = data => request({ url: '/deleteShopServiceWeb', method: 'post', data })

const reqService = data => request({ url: '/getShopServiceListWeb', method: 'post', data })

/** 店铺推送相关 */

export const getPushShop = () => request({ url: '/getShopGeTuiWeb', method: 'post' })

export const editPushShop = data => request({ url: '/handleShopGeTuiWeb', method: 'post', data })

/** paypal设置相关 */

export const editPaypal = data => request({ url: '/handleShopPayPalWeb', method: 'post', data })

export const delPaypal = data => request({ url: '/deleteShopPayPalWeb', method: 'post', data })

const reqPaypal = data => request({ url: '/getShopPayPalListWeb', method: 'post', data })

/** Stripe设置相关 */

export const editStripe = data => request({ url: '/handleShopStripeWeb', method: 'post', data })

export const delStripe = data => request({ url: '/deleteShopStripeWeb', method: 'post', data })

const reqStripe = data => request({ url: '/getShopStripeListWeb', method: 'post', data })

/** 兑换设置相关 */

export const getExchangeList = data => request({ url: '/getExchangeListWeb', method: 'post', data })

export const handleExchange = data => request({ url: '/handleExchangeWeb', method: 'post', data })

export const delExchange = data => request({ url: '/deleteExchangeWeb', method: 'post', data })

/** 邀请码相关 */
export const getInvitationCodeList = data => request({ url: '/getInvitationCodeListWeb', method: 'post', data })
export const addInvitationCode = data => request({ url: '/addInvitationCodeWeb', method: 'post', data })
export const editInvitationCode = data => request({ url: '/editInvitationCodeWeb', method: 'post', data })
export const batchGenerateInvitationCode = data => request({ url: '/batchGenerateInvitationCodeWeb', method: 'post', data })
export const deleteInvitationCode = data => request({ url: '/deleteInvitationCodeWeb', method: 'post', data })
const reqInvitationCode = data => request({ url: '/getInvitationCodeListWeb', method: 'post', data })

const reqExchange = data => request({ url: '/getExchangeListWeb', method: 'post', data })

/** 系统推送相关 */

export const sendPush = data => request({ url: '/pushMessageWeb', method: 'post', data })

export const delPushMsg = data => request({ url: '/deletePushMessageWeb', method: 'post', data })

export const getUser = data => request({ url: '/getAllUsersWeb', method: 'post', data })

const reqPushMsg = data => request({ url: '/getPushMessageListWeb', method: 'post', data })

/** 订单相关 */

export const getArea = () => request({ url: '/getAddressAreaWeb', method: 'post' })

export const addLogistic = data => request({ url: '/addOrderLogisticsWeb', method: 'post', data })

export const editAppoTime = data => request({ url: '/editOrderTimeWeb', method: 'post', data })

export const completeOrder = data => request({ url: '/confirmReceiptWeb', method: 'post', data })

export const confirmPay = data => request({ url: '/confirmPaymentWeb', method: 'post', data })

export const scanConfirmOrder = data => request({ url: '/scanConfirmOrderWeb', method: 'post', data })


export const delOrder = data => request({ url: '/deleteOrderWeb', method: 'post', data })

export const editPaymentat = data => request({ url: '/editPaymentTime', method: 'post', data })

export const controlRecord = data => request({ url: '/getOrderActivityLogListWeb', method: 'post', data })

export const DlFile = params => request({ url: '/generateInvoiceWeb', method: 'get', responseType: 'blob', params })

const reqOrders = data => request({ url: '/getOrderListWeb', method: 'post', data })

export const getCalendarOrder = data => request({ url: '/getCalendarOrdersWeb', method: 'post', data })

export const getOffUser = data => request({ url: '/getAllUsersWeb', method: 'post', data })

export const addOfflineOrder = data => request({ url: '/addOfflineOrdersWeb', method: 'post', data })

export const addOrder = data => request({ url: '/WebAddOrder', method: 'post', data })

const reqOfflineGoods = data => request({ url: '/getAllSortProductWeb', method: 'post', data })

/** 卡牌订单相关 */
const reqCardOrders = data => request({ url: '/getCardOrderListWeb', method: 'post', data })

export const addCardLogistic = data => request({ url: '/addCardOrderLogisticsWeb', method: 'post', data })
export const delCardOrder = data => request({ url: '/deleteCardOrderWeb', method: 'post', data })
export const confirmCardOrderPay = data => request({ url: '/confirmPaymentCardOrderWeb', method: 'post', data })
export const confirmCardReceipt = data => request({ url: '/confirmReceiptCardOrderWeb', method: 'post', data })
export const editCardOrderTime = data => request({ url: '/editCardOrderTimeWeb', method: 'post', data })
export const scanConfirmCardOrder = data => request({ url: '/scanConfirmCardOrderWeb', method: 'post', data })

/** 已兑换卡片恢复 */
const reqExchangedCards = data => request({ url: '/getExchangedCardsWeb', method: 'post', data })
export const restoreExchangedCards = data => request({ url: '/restoreExchangedCardsWeb', method: 'post', data })

/** 预约排号相关 */

export const editAppoNum = data => request({ url: '/handleReservationProjectWeb', method: 'post', data })

export const delAppoNum = data => request({ url: '/deleteReservationProjectWeb', method: 'post', data })

const reqAppoNum = data => request({ url: '/getReservationProjectWeb', method: 'post', data })

export const editUserNum = data => request({ url: '/editUserReservationWeb', method: 'post', data })

export const delUserNum = data => request({ url: '/deleteUserReservationWeb', method: 'post', data })

const reqUserNum = data => request({ url: '/getUserReservationWeb', method: 'post', data })

/** 评论管理相关 */

export const delComment = data => request({ url: '/deleteOrderCommentWeb', method: 'post', data })

const reqComment = data => request({ url: '/getOrderCommentListWeb', method: 'post', data })

/** 资讯管理相关 */

export const editNews = data => request({ url: '/handleNewsWeb', method: 'post', data })

export const delNews = data => request({ url: '/deleteNewsWeb', method: 'post', data })

const reqNews = data => request({ url: '/getNewsListWeb', method: 'post', data })

/** 留言管理相关 */

export const delMessage = data => request({ url: '/deleteMessageWeb', method: 'post', data })

const reqMessage = data => request({ url: '/getMessageListWeb', method: 'post', data })

/** 日志管理相关 */

const reqLoginLog = data => request({ url: '/getAdminLogListWeb', method: 'post', data })

export const delActLog = data => request({ url: '/deleteActivityLogWeb', method: 'post', data })

const reqActLog = data => request({ url: '/getActivityLogListWeb', method: 'post', data })

/** 记录查询相关 */

const reqRechargeRecord = data => request({ url: '/getRechargeLogListWeb', method: 'post', data })

const reqDrawRecord = data => request({ url: '/getDrawTaskListWeb', method: 'post', data })

const reqDrawDetail = data => request({ url: '/getDrawDetailListWeb', method: 'post', data })

// 卡牌多用户抽取检测（同一张卡被多个用户抽到）
const reqMultiDrawCheck = data => request({ url: '/getMultiDrawCheckListWeb', method: 'post', data })

// 卡牌多用户抽取检测-卡片详情（完整抽取链路）
const reqMultiDrawDetail = data => request({ url: '/getMultiDrawCheckDetailWeb', method: 'post', data })

// 查账-抽卡对账（每次抽卡一行，含扣款/建单校验）
const reqDrawAudit = data => request({ url: '/getDrawAuditListWeb', method: 'post', data })

// 查账-单批次对账单（付款/发卡/账户资金追溯）
const reqDrawBill = data => request({ url: '/getDrawBillWeb', method: 'post', data })

// 邀请码使用记录
const reqInvitationCodeLog = data => request({ url: '/getInvitationCodeUsageLogListWeb', method: 'post', data })

// 邮件发送日志
const reqEmailSendLog = data => request({ url: '/getEmailSendLogListWeb', method: 'post', data })
// 每日登录奖励日志
const reqDailyBonusLog = data => request({ url: '/getDailyBonusLogListWeb', method: 'post', data })

// 金币兑换记录（钻石兑换金币）
const reqDiamondExchangeLog = data => request({ url: '/getDiamondExchangeLogListWeb', method: 'post', data })

// 查账-对账单临时封禁账号
const reqBanUser = data => request({ url: '/banUserWeb', method: 'post', data })

// 查账-对账单解除封禁
const reqUnbanUser = data => request({ url: '/unbanUserWeb', method: 'post', data })

/** 分析系统管理相关 */

export const getSD = data => request({ url: '/getSalesDirectionWeb', method: 'post', data })

const reqOA = data => request({ url: '/getOrderAnalysisWeb', method: 'post', data })

const reqUA = data => request({ url: '/getUserAnalysisWeb', method: 'post', data })

/** 抽卡预设相关 */
export const getPresetCards = data => request({ url: '/getPresetCardsWeb', method: 'post', data })

export const saveDrawPreset = data => request({ url: '/saveDrawPresetWeb', method: 'post', data })

export const shuffleDrawPreset = data => request({ url: '/shuffleDrawPresetWeb', method: 'post', data })

export const autoCreatePresets = () => request({ url: '/autoCreatePresetsWeb', method: 'post' })

export const delDrawPreset = data => request({ url: '/deleteDrawPresetWeb', method: 'post', data })

export const unlockDrawPreset = data => request({ url: '/unlockDrawPresetWeb', method: 'post', data })

const reqDrawPreset = data => request({ url: '/getDrawPresetListWeb', method: 'post', data })

/** 卡包分类相关 */
export const addCardCate = data => request({ url: '/handleCardSortListWeb', method: 'post', data })

export const delCardCate = data => request({ url: '/deleteCardSortListWeb', method: 'post', data })

export const getAllCardSort = () => request({ url: '/getAllCardSortWeb', method: 'post' })

// 全部已维护过的卡池品牌（卡池表单品牌下拉用）
export const getAllCardBrands = () => request({ url: '/getAllCardBrandsWeb', method: 'post' })

export const getCardsBySort = data => request({ url: '/getCardsBySortWeb', method: 'post', data })
export const associateCards = data => request({ url: '/associateCardsWeb', method: 'post', data })
export const updateCardTag = data => request({ url: '/updateCardTagWeb', method: 'post', data })
export const updateCardTagBatch = data => request({ url: '/updateCardTagBatchWeb', method: 'post', data })
export const saveCardTagOrder = data => request({ url: '/saveCardTagOrderWeb', method: 'post', data })
export const saveCardTagAnimation = data => request({ url: '/saveCardTagAnimationWeb', method: 'post', data })
export const updateCardPriority = data => request({ url: '/updateCardPriorityWeb', method: 'post', data })

// 卡池抽卡入口开关（单个 / 一键全部）
export const editCardSortDrawEnable = data => request({ url: '/editCardSortDrawEnableWeb', method: 'post', data })
export const batchCardSortDrawEnable = data => request({ url: '/batchCardSortDrawEnableWeb', method: 'post', data })
// 卡池优惠卡包开关（每账号仅能抽一次、仅单抽）
export const editCardSortOnceOnly = data => request({ url: '/editCardSortOnceOnlyWeb', method: 'post', data })
// 保存卡池抽卡价格公式设置（手动百分比 / 是否自动更新）并立即重算抽卡价格
export const updateCardSortDrawPrice = data => request({ url: '/updateCardSortDrawPriceWeb', method: 'post', data })
// 卡池初始化（复制一个设置/预设相同的新池，原池软删除）
export const initCardSort = data => request({ url: '/initCardSortWeb', method: 'post', data })
// 读取卡池额外奖励规则（含已抽张数 + 各规则已发放次数）
export const getCardSortBonusRules = data => request({ url: '/getCardSortBonusRulesWeb', method: 'post', data })
// 保存卡池额外奖励规则（全量覆盖）
export const saveCardSortBonusRules = data => request({ url: '/saveCardSortBonusRulesWeb', method: 'post', data })

const reqCate = data => request({ url: '/getCardSortListWeb', method: 'post', data })

/** 卡片管理相关 */
export const addCard = data => request({ url: '/addCardWeb', method: 'post', data })

export const editCard = data => request({ url: '/editCardWeb', method: 'post', data })

export const delCard = data => request({ url: '/deleteCardWeb', method: 'post', data })

export const getCardDrawerInfo = data => request({ url: '/getCardDrawerInfo', method: 'post', data })

const reqCard = data => request({ url: '/getCardListWeb', method: 'post', data })
const reqSpiderCard = data => request({ url: '/getSpiderCardListWeb', method: 'post', data })
export const addSpiderCard = data => request({ url: '/addSpiderCardWeb', method: 'post', data })
export const deleteSpiderCard = data => request({ url: '/deleteSpiderCardWeb', method: 'post', data })
export const editSpiderCard = data => request({ url: '/editSpiderCardWeb', method: 'post', data })

export { reqSpiderCard }

/** 邮箱验证码相关 */
export const sendEmailVerifyCode = data => request({ url: '/api/sendEmailVerifyCode', method: 'post', data })
export const resetPasswordByEmailApi = data => request({ url: '/api/resetPasswordByEmail', method: 'post', data })

export default {
  reqFile,

  reqRole,

  reqAdmin,

  reqSpecs,

  reqGoodsCate,

  reqGoods,

  reqDrawPreset,

  reqCate,

  reqCard,

  reqSpiderCard,
  addSpiderCard,
  editSpiderCard,

  reqCoupon,

  reqLoss,

  reqPurchase,

  reqLevel,

  reqBanner,

  getBannerPopup,
  editBannerPopup,

  reqFaq,

  reqUser,

  reqService,

  reqPaypal,

  reqStripe,

  reqExchange,

  reqInvitationCode,

  reqPushMsg,

  reqOrders,
  reqCardOrders,
  reqExchangedCards,
  restoreExchangedCards,

  reqOfflineGoods,

  reqAppoNum,

  reqUserNum,

  reqComment,

  reqNews,

  reqMessage,

  reqLoginLog,

  reqActLog,

  reqRechargeRecord,
  reqDrawRecord,
  reqDrawDetail,
  reqMultiDrawCheck,
  reqMultiDrawDetail,
  reqDrawAudit,
  reqDrawBill,
  reqInvitationCodeLog,
  reqEmailSendLog,
  reqDailyBonusLog,
  reqDiamondExchangeLog,
  reqBanUser,
  reqUnbanUser,

  reqOA,

  reqUA
}
