import request from '@/utils/request'

export function login(data) {
  return request({
    url: '/AdminLoginWeb',
    method: 'post',
    data
  })
}

// 登錄頁配置（是否開啟短信驗證）
export function getLoginConfig() {
  return request({
    url: '/getAdminLoginConfigWeb',
    method: 'get'
  })
}

// 發送登錄短信驗證碼
export function sendLoginSmsCode(data) {
  return request({
    url: '/sendAdminLoginSms',
    method: 'post',
    data
  })
}

// 第二階段：發送短信/郵箱驗證碼
export function sendVerifyCode(data) {
  return request({
    url: '/sendAdminVerifyCode',
    method: 'post',
    data
  })
}

// 第二階段：提交二次驗證完成登錄
export function verifyLogin(data) {
  return request({
    url: '/AdminVerifyLogin',
    method: 'post',
    data
  })
}

// 個人中心：獲取資料
export function getAdminProfile() {
  return request({
    url: '/getAdminProfileWeb',
    method: 'get'
  })
}

// 個人中心：修改綁定手機/郵箱
export function editAdminProfile(data) {
  return request({
    url: '/editAdminProfileWeb',
    method: 'post',
    data
  })
}

// 個人中心：修改頭像
export function editAdminAvatar(data) {
  return request({
    url: '/editAdminAvatarWeb',
    method: 'post',
    data
  })
}

// 個人中心：保存安全問題（多個）
export function saveAdminQuestion(data) {
  return request({
    url: '/saveAdminQuestionWeb',
    method: 'post',
    data
  })
}

export function getInfo() {
  return request({
    url: '/getAdminInfoWeb',
    method: 'get'
  })
}

export function logout() {
  return request({
    url: '/vue-admin-template/user/logout',
    method: 'post'
  })
}
