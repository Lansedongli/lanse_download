import request from '@/utils/request'

// 搜索管理（注意：公开接口不走 /admin/api，需要单独请求）
import axios from 'axios'

const publicRequest = axios.create({ baseURL: '/api/v1', timeout: 15000 })

export const searchAll = (params) => publicRequest.get('/search', { params })
export const hotKeywords = (limit = 20) => publicRequest.get('/search/hot', { params: { limit } })
