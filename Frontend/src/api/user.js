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
