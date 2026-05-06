import api from './index'

/**
 * 用户登录
 * @param {string} username
 * @param {string} password
 */
export function login(username, password) {
  return api.post('/auth/login', { username, password })
}

/**
 * 用户注册
 * @param {string} username
 * @param {string} password
 * @param {string} email
 */
export function register(username, password, email) {
  return api.post('/auth/register', { username, password, email })
}

/**
 * 刷新令牌
 * @param {string} refresh_token
 */
export function refreshToken(refresh_token) {
  return api.post('/auth/refresh', { refresh_token })
}

/**
 * 用户登出
 */
export function logout() {
  return api.post('/auth/logout')
}
