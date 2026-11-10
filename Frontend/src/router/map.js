import { kebabToCamel } from '@/utils'


let requireComponent = require.context('@/views', true, /index\.vue$/)
let fileList = requireComponent.keys().filter(item => ![
  './login/index.vue',
  './redirect/index.vue',
  './dashboard/index.vue'
].includes(item))
let routeMap = {}
fileList.forEach(item => {
  let paths = item.split('/')
  let key = kebabToCamel(paths[paths.length-2])
  // routeMap[key] = () => import('@'+item.substr(1).replace('.vue', ''))
  routeMap[key] = resolve => require(['@/views'+item.substr(1)],resolve)
})

export default routeMap

// export default {
//   Administrator: () => import('@/views/authority/administrator/index'),
//   Role: () => import('@/views/authority/role/index'),
//   CouponList: () => import('@/views/coupon/coupon-list/index'),
//   Loss: () => import('@/views/inventory-records/loss/index'),
//   Purchase: () => import('@/views/inventory-records/purchase/index'),
//   MemberList: () => import('@/views/member/member-list/index'),
//   CalendarOrders: () => import('@/views/order/calendar-orders/index'),
//   OrderDetails: () => import('@/views/order/order-details/index'),
//   OrderList: () => import('@/views/order/order-list/index'),
//   Classification: () => import('@/views/product/classification/index'),
//   ProductList: () => import('@/views/product/product-list/index'),
//   SpecsCate: () => import('@/views/specs/specs-cate/index'),
//   PrivacyAgreement: () => import('@/views/agreement/privacy-agreement/index'),
//   UserAgreement: () => import('@/views/agreement/user-agreement/index'),
//   BannerList: () => import('@/views/banner/banner-list/index'),
//   FaqList: () => import('@/views/faq/faq-list/index'),
//   UserList: () => import('@/views/user/user-list/index'),
// }