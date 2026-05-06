import api from './index'

/**
 * 获取分类树
 */
export function getCategories() {
  return api.get('/categories')
}

/**
 * 获取软件列表
 * @param {object} params - { category_id, page, pageSize, sort }
 */
export function getSoftwareList(params = {}) {
  return api.get('/software', { params })
}

/**
 * 获取热门软件
 * @param {number} limit
 */
export function getHotSoftware(limit = 10) {
  return api.get('/software/hot', { params: { limit } })
}

/**
 * 获取推荐软件
 * @param {number} limit
 */
export function getRecommendSoftware(limit = 10) {
  return api.get('/software/recommend', { params: { limit } })
}

/**
 * 软件内搜索
 * @param {string} keyword
 */
export function searchSoftware(keyword) {
  return api.get('/software/search', { params: { keyword } })
}

/**
 * 获取软件详情
 * @param {number|string} id
 */
export function getSoftwareDetail(id) {
  return api.get(`/software/${id}`)
}

/**
 * 全站搜索
 * @param {object} params - { keyword, type }
 */
export function searchAll(params = {}) {
  return api.get('/search', { params })
}

/**
 * 获取热门搜索词
 * @param {number} limit
 */
export function getHotSearch(limit = 10) {
  return api.get('/search/hot', { params: { limit } })
}

/**
 * 获取下载文件
 * @param {string} token
 */
export function downloadFile(token) {
  return api.get('/download/file', { params: { token } })
}
