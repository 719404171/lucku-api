import i18n from '@/lang'
import store from '@/store'

export default function getPageTitle(key) {
  const title = store.getters.title
  const hasKey = i18n.te(`route.${key}`)
  if (hasKey) {
    const pageName = i18n.t(`route.${key}`)
    return `${pageName} - ${title}`
  }
  return `${title}`
}
