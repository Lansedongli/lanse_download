import api from './index'

// ==================== 用户信息 ====================

/**
 * 获取当前用户信息
 */
export function getUserProfile() {
  return api.get('/user/me')
}

/**
 * 更新用户信息
 * @param {object} data
 */
export function updateUserProfile(data) {
  return api.put('/user/me', data)
}

/**
 * 修改密码
 * @param {string} old_password
 * @param {string} new_password
 */
export function changePassword(old_password, new_password) {
  return api.put('/user/password', { old_password, new_password })
}

/**
 * 获取积分记录
 * @param {number} page
 */
export function getPointsLog(page = 1) {
  return api.get('/user/points-log', { params: { page } })
}

// ==================== 下载 ====================

/**
 * 获取下载链接
 * @param {number} software_id
 */
export function getDownloadUrl(software_id) {
  return api.post('/download/url', { software_id })
}

// ==================== 点卡充值 ====================

/**
 * 点卡充值
 * @param {string} card_no
 * @param {string} card_password
 */
export function rechargeCard(card_no, card_password) {
  return api.post('/recharge/card', { card_no, card_password })
}

/**
 * 获取充值记录
 * @param {number} page
 */
export function getRechargeRecords(page = 1) {
  return api.get('/recharge/records', { params: { page } })
}

// ==================== 收藏 ====================

/**
 * 获取收藏列表
 * @param {number} page
 */
export function getFavorites(page = 1) {
  return api.get('/favorites', { params: { page } })
}

/**
 * 添加收藏
 * @param {number} software_id
 */
export function addFavorite(software_id) {
  return api.post('/favorites', { software_id })
}

/**
 * 取消收藏
 * @param {number} id
 */
export function removeFavorite(id) {
  return api.delete(`/favorites/${id}`)
}

// ==================== 评论 ====================

/**
 * 获取评论列表
 * @param {number} software_id
 * @param {number} page
 */
export function getComments(software_id, page = 1) {
  return api.get('/comments', { params: { software_id, page } })
}

/**
 * 发表评论
 * @param {object} data - { software_id, content, rating }
 */
export function postComment(data) {
  return api.post('/comments', data)
}
