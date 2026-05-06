import request from '@/utils/request'
export const getList = (params) => request.get('/recharges', { params })
export const manualRecharge = (data) => request.post('/recharges/manual', data)
