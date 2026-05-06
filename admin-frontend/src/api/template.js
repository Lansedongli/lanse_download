import request from '@/utils/request'

export function getVars() { return request.get('/admin/api/template-vars') }
export function getVarMap() { return request.get('/admin/api/template-vars/map') }
export function createVar(data) { return request.post('/admin/api/template-vars', data) }
export function updateVar(id, data) { return request.put(`/admin/api/template-vars/${id}`, data) }
export function deleteVar(id) { return request.delete(`/admin/api/template-vars/${id}`) }
