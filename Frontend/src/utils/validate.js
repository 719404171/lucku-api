/**
 * Created by PanJiaChen on 16/11/18.
 */

/**
 * @param {string} path
 * @returns {Boolean}
 */
export function isExternal(path) {
  return /^(https?:|mailto:|tel:)/.test(path)
}

/**
 * 字母、数字、下划线、减号（3-16）
 * @param {string} str
 * @returns {Boolean}
 */
export function validUsername(str) {
  return  /^[\w\s]{3,16}$/.test(str.trim())
}

/**
 * 字母、数字（5-18）
 * @param {string} str
 * @returns {Boolean}
 */
export function validPassword(str) {
  return /^[\w]{5,18}$/.test(str.trim())
}

/**
 * @param {string} str
 * @returns {Boolean}
 */
export function validEmail(str) {
  return /^(([^<>()[\]\\.,;:\s@"]+(\.[^<>()[\]\\.,;:\s@"]+)*)|(".+"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/.test(str.trim())
}

/**
 * @param {string} str
 * @returns {Boolean}
 */
export function validMemberName(str) {
  return /^[\w\s]{3,18}$/.test(str.trim())
}