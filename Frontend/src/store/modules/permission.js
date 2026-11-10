import { asyncRoutes, constantRoutes } from '@/router'
import { getAdminMenus } from '@/api/index'
import { recursion, kebabToCamel } from '@/utils'
import routeMap from '@/router/map'
import Layout from '@/layout'

/**
 * Use meta.role to determine if the current user has permission
 * @param roles
 * @param route
 */
function hasPermission(roles, route) {
  if (route.meta && route.meta.roles) {
    return roles.some(role => route.meta.roles.includes(role))
  } else {
    return true
  }
}

/**
 * Filter asynchronous routing tables by recursion
 * @param routes asyncRoutes
 * @param roles
 */
export function filterAsyncRoutes(routes, roles) {
  const res = []

  routes.forEach(route => {
    const tmp = { ...route }
    if (hasPermission(roles, tmp)) {
      if (tmp.children) {
        tmp.children = filterAsyncRoutes(tmp.children, roles)
      }
      res.push(tmp)
    }
  })
  return res
}

const state = {
  routes: [],
  addRoutes: []
}

const mutations = {
  SET_ROUTES: (state, routes) => {
    state.addRoutes = routes
    state.routes = constantRoutes.concat(routes)
  }
}

const actions = {
  generateRoutes({ commit }, roles) {
    return new Promise(async resolve => {
      // let accessedRoutes
      // if (roles.includes('admin')) {
      //   accessedRoutes = asyncRoutes || []
      // } else {
      //   accessedRoutes = filterAsyncRoutes(asyncRoutes, roles)
      // }
      const {results: data} = await getAdminMenus()
      const {result: formate} = recursion({data,match: [['menu_name', 'path', 'icon', 'parent', 'power'], ['menu_name', 'path', 'icon', 'parent', 'power']]})
      const {result: accessedRoutes} = recursion(
        {
          data: formate, 
          match: [['name', 'path', 'meta', 'component'], ['name', 'path', 'meta', 'component']]
        }, 
        item => {
          const menuTitle = item.menu_name
          item.name = kebabToCamel(item.path.substr(1))
          item.path = item.parent ? item.path.substr(1) : item.path
          item.component = item.parent ? routeMap[item.name] : Layout
          item.meta = Object.assign({title: menuTitle, icon: !item.parent ? (item.icon || 'el-icon-platform-eleme') : ''}, item.power ? {power: item.power} : {})
        }
      )
      accessedRoutes.push({ path: '*', redirect: '/404', hidden: true })
      commit('SET_ROUTES', accessedRoutes)
      resolve(accessedRoutes)
    })
  }
}

export default {
  namespaced: true,
  state,
  mutations,
  actions
}
