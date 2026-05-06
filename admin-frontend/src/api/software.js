import request from '@/utils/request'
export const getList = (params) => request.get('/software', { params })
export const getDetail = (id) => request.get(`/software/${id}`)
export const create = (data) => request.post('/software', data)
export const update = (id, data) => request.put(`/software/${id}`, data)
export const del = (id) => request.delete(`/software/${id}`)
export const audit = (id, status) => request.put(`/software/${id}/audit`, { status })
