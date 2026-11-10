import request from '@/utils/request'

export function login(data) {
  return request({
    url: '/AdminLoginWeb',
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
