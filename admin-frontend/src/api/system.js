import request from '@/utils/request'

export function getConfigs(params) { return request.get('/admin/api/system-configs', { params }) }
export function getGroups() { return request.get('/admin/api/system-configs/groups') }
export function createConfig(data) { return request.post('/admin/api/system-configs', data) }
export function updateConfig(id, data) { return request.put(`/admin/api/system-configs/${id}`, data) }
export function deleteConfig(id) { return request.delete(`/admin/api/system-configs/${id}`) }
export function batchUpdateConfig(data) { return request.put('/admin/api/system-configs/batch', data) }
