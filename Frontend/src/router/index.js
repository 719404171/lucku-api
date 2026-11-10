import Vue from 'vue'
import Router from 'vue-router'

Vue.use(Router)

/* Layout */
import Layout from '@/layout'

/**
 * Note: sub-menu only appear when route children.length >= 1
 * Detail see: https://panjiachen.github.io/vue-element-admin-site/guide/essentials/router-and-nav.html
 *
 * hidden: true                   if set true, item will not show in the sidebar(default is false)
 * alwaysShow: true               if set true, will always show the root menu
 *                                if not set alwaysShow, when item has more than one children route,
 *                                it will becomes nested mode, otherwise not show the root menu
 * redirect: noRedirect           if set noRedirect will no redirect in the breadcrumb
 * name:'router-name'             the name is used by <keep-alive> (must set!!!)
 * meta : {
    roles: ['admin','editor']    control the page roles (you can set multiple roles)
    title: 'title'               the name show in sidebar and breadcrumb (recommend set)
    icon: 'svg-name'/'el-icon-x' the icon show in the sidebar
    breadcrumb: false            if set false, the item will hidden in breadcrumb(default is true)
    activeMenu: '/example/list'  if set path, the sidebar will highlight the path you set
  }
 */

/**
 * constantRoutes
 * a base page that does not have permission requirements
 * all roles can be accessed
 */
export const constantRoutes = [
  {
    path: '/redirect',
    component: Layout,
    hidden: true,
    children: [
      {
        path: '/redirect/:path(.*)',
        component: () => import('@/views/redirect/index')
      }
    ]
  },
  {
    path: '/login',
    component: () => import('@/views/login/index'),
    hidden: true
  },
  {
    path: '/auth-redirect',
    component: () => import('@/views/login/auth-redirect'),
    hidden: true
  },
  {
    path: '/404',
    component: () => import('@/views/error-page/404'),
    hidden: true
  },
  {
    path: '/401',
    component: () => import('@/views/error-page/401'),
    hidden: true
  },
  {
    path: '/',
    component: Layout,
    redirect: '/dashboard',
    children: [
      {
        path: 'dashboard',
        component: () => import('@/views/dashboard/index'),
        name: 'Dashboard',
        meta: { title: 'dashboard', icon: 'dashboard', affix: true }
      }
    ]
  },
  {
    path: '/CreateOrder',
    component: Layout,
    redirect: '/CreateOrder/index',
    hidden: true,
    children: [
      {
        path: 'index',
        component: () => import('@/views/order/order-create/index'),
        name: 'CreateOrder',
        meta: { title: 'CreateOrder', power: [] }
      }
    ]
  }
]

export const asyncRoutes = [

  // {
  //   path: '/goods',
  //   component: Layout,
  //   redirect: 'noRedirect',
  //   name: 'Goods',
  //   meta: {
  //     title: 'goods',
  //     icon: 'el-icon-s-goods'
  //   },
  //   children: [
  //     {
  //       path: 'list',
  //       component: () => import('@/views/goods/list'),
  //       name: 'GoodsList',
  //       meta: { title: 'goodsList' }
  //     },
  //     {
  //       path: 'category',
  //       component: () => import('@/views/goods/category'),
  //       name: 'GoodsCategory',
  //       meta: { title: 'goodsCategory' }
  //     }
  //   ]
  // },

  // {
  //   path: '/orders',
  //   component: Layout,
  //   redirect: 'noRedirect',
  //   name: 'Orders',
  //   meta: {
  //     title: 'orders',
  //     icon: 'el-icon-s-order'
  //   },
  //   children: [
  //     {
  //       path: 'list',
  //       component: () => import('@/views/orders/list'),
  //       name: 'OrdersList',
  //       meta: { title: 'ordersList' }
  //     },
  //     {
  //       path: 'comments',
  //       component: () => import('@/views/orders/comments'),
  //       name: 'Comments',
  //       meta: { title: 'comments' }
  //     }
  //   ]
  // },

  // {
  //   path: '/statistics',
  //   component: Layout,
  //   redirect: 'noRedirect',
  //   name: 'Statistics',
  //   meta: {
  //     title: 'statistics',
  //     icon: 'el-icon-s-data'
  //   },
  //   children: [
  //     {
  //       path: 'sales',
  //       component: () => import('@/views/statistics/sales'),
  //       name: 'Sales',
  //       meta: { title: 'sales' }
  //     },
  //     {
  //       path: 'goods',
  //       component: () => import('@/views/statistics/goods'),
  //       name: 'StttGoods',
  //       meta: { title: 'stttGoods' }
  //     },
  //     {
  //       path: 'consumption',
  //       component: () => import('@/views/statistics/consumption'),
  //       name: 'Consumption',
  //       meta: { title: 'consumption' }
  //     },
  //   ]
  // },

  // {
  //   path: '/shop',
  //   component: Layout,
  //   redirect: 'noRedirect',
  //   name: 'Shop',
  //   meta: {
  //     title: 'shop',
  //     icon: 'el-icon-s-shop'
  //   },
  //   children: [
  //     {
  //       path: 'banner',
  //       component: () => import('@/views/shop/banner'),
  //       name: 'Banner',
  //       meta: { title: 'banner' }
  //     },
  //     {
  //       path: 'coupons',
  //       component: () => import('@/views/shop/coupons'),
  //       name: 'Coupons',
  //       meta: { title: 'coupons' }
  //     },
  //     {
  //       path: 'setting',
  //       component: () => import('@/views/shop/setting'),
  //       name: 'shopSet',
  //       meta: { title: 'shopSet' }
  //     },
  //     {
  //       path: 'aboutus',
  //       component: () => import('@/views/shop/aboutus'),
  //       name: 'Aboutus',
  //       meta: { title: 'aboutus' }
  //     }
  //   ]
  // },

  // {
  //   path: '/member',
  //   component: Layout,
  //   redirect: 'noRedirect',
  //   name: 'Member',
  //   meta: {
  //     title: 'member',
  //     icon: 'el-icon-user-solid'
  //   },
  //   children: [
  //     {
  //       path: 'list',
  //       component: () => import('@/views/member/list'),
  //       name: 'MemberList',
  //       meta: { title: 'members' }
  //     },
  //     {
  //       path: 'grade',
  //       component: () => import('@/views/member/grade'),
  //       name: 'MemberGrade',
  //       meta: { title: 'memberGrade' }
  //     }
  //   ]
  // },

  // {
  //   path: '/power',
  //   component: Layout,
  //   redirect: 'noRedirect',
  //   name: 'Power',
  //   meta: {
  //     title: 'power',
  //     icon: 'el-icon-s-custom'
  //   },
  //   children: [
  //     {
  //       path: 'menu',
  //       component: () => import('@/views/power/menu'),
  //       name: 'Menu',
  //       meta: { title: 'menu' }
  //     },
  //     {
  //       path: 'role',
  //       component: () => import('@/views/power/role'),
  //       name: 'Role',
  //       meta: { title: 'role' }
  //     },
  //     {
  //       path: 'admin',
  //       component: () => import('@/views/power/admin'),
  //       name: 'Admin',
  //       meta: { title: 'admin' }
  //     }
  //   ]
  // },
  
  // {
  //   path: '/system',
  //   component: Layout,
  //   redirect: 'noRedirect',
  //   name: 'System',
  //   meta: {
  //     title: 'system',
  //     icon: 'el-icon-s-tools'
  //   },
  //   children: [
  //     {
  //       path: 'agreements',
  //       component: () => import('@/views/system/agreements'),
  //       name: 'Agreements',
  //       meta: { title: 'agreements' }
  //     },
  //     {
  //       path: 'personal',
  //       component: () => import('@/views/system/personal'),
  //       name: 'Personal',
  //       meta: { title: 'personal' }
  //     }
  //   ]
  // },

  // 404 page must be placed at the end !!!
  { path: '*', redirect: '/404', hidden: true }
]

const createRouter = () => new Router({
  // mode: 'history', // require service support
  scrollBehavior: () => ({ y: 0 }),
  routes: constantRoutes
})

const router = createRouter()

// Detail see: https://github.com/vuejs/vue-router/issues/1234#issuecomment-357941465
export function resetRouter() {
  const newRouter = createRouter()
  router.matcher = newRouter.matcher // reset router
}

export default router
