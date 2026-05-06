import request from '@/utils/request'

// 广告位
export function getPlaces() { return request.get('/admin/api/ads/places') }
export function createPlace(data) { return request.post('/admin/api/ads/places', data) }
export function updatePlace(id, data) { return request.put(`/admin/api/ads/places/${id}`, data) }
export function deletePlace(id) { return request.delete(`/admin/api/ads/places/${id}`) }

// 广告内容
export function getRecords(params) { return request.get('/admin/api/ads/records', { params }) }
export function createRecord(data) { return request.post('/admin/api/ads/records', data) }
export function updateRecord(id, data) { return request.put(`/admin/api/ads/records/${id}`, data) }
export function deleteRecord(id) { return request.delete(`/admin/api/ads/records/${id}`) }

// 统计
export function getAdStats(params) { return request.get('/admin/api/ads/stats', { params }) }
