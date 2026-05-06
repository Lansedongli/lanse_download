import request from '@/utils/request'
export const getList = (params) => request.get('/users', { params })
export const getDetail = (id) => request.get(`/users/${id}`)
export const adjustPoints = (id, data) => request.put(`/users/${id}/points`, data)
export const changeGroup = (id, data) => request.put(`/users/${id}/group`, data)
export const ban = (id) => request.put(`/users/${id}/ban`)
