import request from '@/utils/request'
export const getTree = () => request.get('/categories')
export const create = (data) => request.post('/categories', data)
export const update = (id, data) => request.put(`/categories/${id}`, data)
export const del = (id) => request.delete(`/categories/${id}`)
